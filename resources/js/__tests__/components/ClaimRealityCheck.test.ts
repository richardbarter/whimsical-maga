import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ClaimRealityCheck from '@/Pages/Public/Quotes/components/ClaimRealityCheck.vue'

describe('ClaimRealityCheck', () => {
    it('renders nothing when both are empty', () => {
        const wrapper = mount(ClaimRealityCheck, { props: { claim: null, realityCheck: null } })

        expect(wrapper.find('section').exists()).toBe(false)
    })

    it('shows both side by side on wider screens', () => {
        const wrapper = mount(ClaimRealityCheck, {
            props: { claim: 'The claim.', realityCheck: 'The reality.' },
        })

        expect(wrapper.findAll('section')).toHaveLength(2)
        expect(wrapper.text()).toContain('The claim.')
        expect(wrapper.text()).toContain('The reality.')
        expect(wrapper.classes()).toContain('sm:grid-cols-2')
    })

    it('shows only the claim, full width, when there is no reality check', () => {
        const wrapper = mount(ClaimRealityCheck, { props: { claim: 'The claim.', realityCheck: null } })

        expect(wrapper.findAll('section')).toHaveLength(1)
        expect(wrapper.text()).toContain('The claim')
        expect(wrapper.text()).not.toContain('Reality check')
        expect(wrapper.classes()).not.toContain('sm:grid-cols-2')
    })

    it('shows only the reality check when there is no claim', () => {
        const wrapper = mount(ClaimRealityCheck, { props: { claim: '', realityCheck: 'The reality.' } })

        expect(wrapper.findAll('section')).toHaveLength(1)
        expect(wrapper.text()).toContain('Reality check')
        expect(wrapper.text()).not.toContain('The claim')
    })

    it('keeps line breaks in plain text', () => {
        const wrapper = mount(ClaimRealityCheck, { props: { claim: 'Line one\nLine two', realityCheck: null } })

        expect(wrapper.find('p').classes()).toContain('whitespace-pre-line')
        expect(wrapper.find('p').text()).toBe('Line one\nLine two')
    })
})
