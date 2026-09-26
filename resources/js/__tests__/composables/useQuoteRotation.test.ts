import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { defineComponent, ref, type Ref } from 'vue'
import { useQuoteRotation } from '@/composables/useQuoteRotation'
import type { Quote } from '@/types'

function makeQuotes(count: number, startId = 1): Quote[] {
    return Array.from({ length: count }, (_, index) => ({ id: startId + index, text: `Quote ${startId + index}` }) as Quote)
}

function mountRotation(quotes: Ref<Quote[]>) {
    const onBackgroundAdvance = vi.fn()
    const onRunningLow = vi.fn()
    let rotation!: ReturnType<typeof useQuoteRotation>

    mount(defineComponent({
        setup() {
            rotation = useQuoteRotation(quotes, { onBackgroundAdvance, onRunningLow })
            return () => null
        },
    }))

    return { rotation, onBackgroundAdvance, onRunningLow }
}

describe('useQuoteRotation', () => {
    beforeEach(() => {
        vi.useFakeTimers()
    })

    afterEach(() => {
        vi.useRealTimers()
    })

    it('plays quotes in the order the server sent them', () => {
        const { rotation } = mountRotation(ref(makeQuotes(20)))

        expect(rotation.currentQuote.value?.id).toBe(1)
        rotation.goToNext()
        rotation.goToNext()
        expect(rotation.currentQuote.value?.id).toBe(3)
    })

    it('advances on a timer until paused', () => {
        const { rotation } = mountRotation(ref(makeQuotes(20)))

        vi.advanceTimersByTime(10_000)
        expect(rotation.currentQuote.value?.id).toBe(2)

        rotation.togglePause()
        vi.advanceTimersByTime(30_000)
        expect(rotation.currentQuote.value?.id).toBe(2)
    })

    it('wraps back to the first quote after the last one', () => {
        const { rotation } = mountRotation(ref(makeQuotes(3)))

        rotation.goToNext()
        rotation.goToNext()
        rotation.goToNext()

        expect(rotation.currentQuote.value?.id).toBe(1)
    })

    it('continues into quotes appended by a later batch', () => {
        const quotes = ref(makeQuotes(2))
        const { rotation } = mountRotation(quotes)

        rotation.goToNext()
        quotes.value = [...quotes.value, ...makeQuotes(2, 3)]
        rotation.goToNext()

        expect(rotation.currentQuote.value?.id).toBe(3)
    })

    it('asks for more quotes only when few unseen ones remain', () => {
        const { rotation, onRunningLow } = mountRotation(ref(makeQuotes(10)))

        expect(onRunningLow).not.toHaveBeenCalled()

        for (let step = 0; step < 4; step++) rotation.goToNext()
        expect(onRunningLow).toHaveBeenCalled()
    })

    it('steps back through history and can step forward again', () => {
        const { rotation } = mountRotation(ref(makeQuotes(10)))

        expect(rotation.canGoBack.value).toBe(false)
        rotation.goToNext()
        rotation.goToNext()
        rotation.goToPrev()

        expect(rotation.currentQuote.value?.id).toBe(2)
        rotation.goToNext()
        expect(rotation.currentQuote.value?.id).toBe(3)
    })

    it('changes the background every third quote', () => {
        const { rotation, onBackgroundAdvance } = mountRotation(ref(makeQuotes(10)))

        rotation.goToNext()
        rotation.goToNext()
        expect(onBackgroundAdvance).not.toHaveBeenCalled()

        rotation.goToNext()
        expect(onBackgroundAdvance).toHaveBeenCalledTimes(1)
    })
})
