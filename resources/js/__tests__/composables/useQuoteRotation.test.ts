import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { defineComponent, nextTick, ref, type Ref } from 'vue'
import { useQuoteRotation } from '@/composables/useQuoteRotation'
import type { Quote } from '@/types'

/**
 * Stands in for Inertia's history state: useRemember restores what was saved under a key and
 * saves every change, like the real one (which needs a running Inertia app).
 */
const rememberedState = vi.hoisted(() => new Map<string, string>())

vi.mock('@inertiajs/vue3', async () => {
    const { isReactive, reactive, ref, watch } = await import('vue')

    return {
        // Like the real one: reactive in, reactive out; anything else comes back as a ref
        useRemember: <T extends object>(data: T, key: string) => {
            const saved = rememberedState.get(key)
            const initial = saved === undefined ? data : JSON.parse(saved)
            const state = isReactive(data) ? reactive(initial) : ref(initial)
            watch(state, (value) => rememberedState.set(key, JSON.stringify(value)), { deep: true, immediate: true })

            return state
        },
    }
})

function makeQuotes(count: number, startId = 1): Quote[] {
    return Array.from({ length: count }, (_, index) => ({ id: startId + index, text: `Quote ${startId + index}` }) as Quote)
}

function mountRotation(quotes: Ref<Quote[]>, isHeld: Ref<boolean> = ref(false)) {
    const onBackgroundAdvance = vi.fn()
    const onRunningLow = vi.fn()
    let rotation!: ReturnType<typeof useQuoteRotation>

    const wrapper = mount(defineComponent({
        setup() {
            rotation = useQuoteRotation(quotes, { onBackgroundAdvance, onRunningLow, isHeld })
            return () => null
        },
    }))

    return { rotation, onBackgroundAdvance, onRunningLow, wrapper }
}

describe('useQuoteRotation', () => {
    beforeEach(() => {
        vi.useFakeTimers()
        rememberedState.clear()
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

    describe('holding (card hovered or focused)', () => {
        it('stops auto-advance without changing isPaused', async () => {
            const isHeld = ref(false)
            const { rotation } = mountRotation(ref(makeQuotes(10)), isHeld)

            isHeld.value = true
            await nextTick()
            vi.advanceTimersByTime(30_000)

            expect(rotation.currentQuote.value?.id).toBe(1)
            expect(rotation.isPaused.value).toBe(false)
        })

        it('resumes with a full interval once released', async () => {
            const isHeld = ref(false)
            const { rotation } = mountRotation(ref(makeQuotes(10)), isHeld)

            vi.advanceTimersByTime(9_000)
            isHeld.value = true
            await nextTick()
            isHeld.value = false
            await nextTick()

            vi.advanceTimersByTime(9_000)
            expect(rotation.currentQuote.value?.id).toBe(1)
            vi.advanceTimersByTime(1_000)
            expect(rotation.currentQuote.value?.id).toBe(2)
        })

        it('stays paused after release when the visitor paused while holding', async () => {
            const isHeld = ref(false)
            const { rotation } = mountRotation(ref(makeQuotes(10)), isHeld)

            isHeld.value = true
            await nextTick()
            rotation.togglePause()
            isHeld.value = false
            await nextTick()
            vi.advanceTimersByTime(30_000)

            expect(rotation.isPaused.value).toBe(true)
            expect(rotation.currentQuote.value?.id).toBe(1)
        })

        it('does not start while held, even when play is pressed', async () => {
            const isHeld = ref(true)
            const { rotation } = mountRotation(ref(makeQuotes(10)), isHeld)

            rotation.togglePause()
            rotation.togglePause()
            vi.advanceTimersByTime(30_000)

            expect(rotation.isPaused.value).toBe(false)
            expect(rotation.currentQuote.value?.id).toBe(1)
        })
    })

    describe('remembered position (returning with Back)', () => {
        it('restores the quote and history the visitor left on', async () => {
            const quotes = ref(makeQuotes(10))
            const first = mountRotation(quotes)
            first.rotation.goToNext()
            first.rotation.goToNext()
            first.rotation.goToPrev()
            await nextTick()
            first.wrapper.unmount()

            const { rotation } = mountRotation(quotes)

            expect(rotation.currentQuote.value?.id).toBe(2)
            expect(rotation.canGoBack.value).toBe(true)
            rotation.goToNext()
            expect(rotation.currentQuote.value?.id).toBe(3)
        })

        it('starts over when the remembered history no longer fits the quotes', async () => {
            const first = mountRotation(ref(makeQuotes(10)))
            for (let step = 0; step < 6; step++) first.rotation.goToNext()
            await nextTick()
            first.wrapper.unmount()

            const { rotation } = mountRotation(ref(makeQuotes(3)))

            expect(rotation.currentQuote.value?.id).toBe(1)
            expect(rotation.canGoBack.value).toBe(false)
        })
    })
})
