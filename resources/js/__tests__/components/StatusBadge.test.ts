import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import { StatusBadge } from '@/Components/ui/status-badge'

describe('StatusBadge', () => {
    it.each([
        ['published', 'bg-green-100', 'text-green-800'],
        ['pending', 'bg-yellow-100', 'text-yellow-800'],
        ['draft', 'bg-gray-100', 'text-gray-600'],
    ] as const)('shows %s with its colours', (status, background, text) => {
        const wrapper = mount(StatusBadge, { props: { status } })

        expect(wrapper.text()).toBe(status)
        expect(wrapper.classes()).toContain(background)
        expect(wrapper.classes()).toContain(text)
    })

    it('replaces the base variant colours rather than stacking them', () => {
        const wrapper = mount(StatusBadge, { props: { status: 'published' } })

        expect(wrapper.classes()).not.toContain('bg-primary')
    })
})
