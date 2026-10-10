import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import { SourceFormRow } from '@/Components/ui/source-form-row'
import type { SourceForm } from '@/types'

// Stub the reka-ui based inputs so the row renders in jsdom
const stubs = {
    Button: { template: '<button v-bind="$attrs"><slot /></button>' },
    Input: { template: '<input />' },
    Label: { template: '<label><slot /></label>' },
    Switch: { template: '<button role="switch" />' },
    Select: { template: '<div><slot /></div>' },
    SelectTrigger: { template: '<div><slot /></div>' },
    SelectValue: true,
    SelectContent: { template: '<div><slot /></div>' },
    SelectItem: { template: '<div><slot /></div>' },
    Trash2: true,
}

const source: SourceForm = {
    _key: 'a',
    url: 'https://example.com',
    title: '',
    source_type: '',
    archived_url: '',
}

function mountRow(props: Record<string, unknown> = {}) {
    return mount(SourceFormRow, {
        props: { source, index: 0, sourceTypes: [{ value: 'study', label: 'Study / Research' }], errors: {}, ...props },
        global: { stubs },
    })
}

describe('SourceFormRow', () => {
    it('shows the Primary source switch by default (quote sources)', () => {
        const wrapper = mountRow()

        expect(wrapper.find('[role="switch"]').exists()).toBe(true)
        expect(wrapper.text()).toContain('Primary source')
    })

    it('hides the Primary source switch when showPrimary is false (subject sources)', () => {
        const wrapper = mountRow({ showPrimary: false })

        expect(wrapper.find('[role="switch"]').exists()).toBe(false)
        expect(wrapper.text()).not.toContain('Primary source')
    })

    it('shows URL errors for its own index', () => {
        const wrapper = mountRow({ index: 1, errors: { 'sources.1.url': 'The source URL must be a valid URL.', 'sources.0.url': 'Not mine' } })

        expect(wrapper.text()).toContain('The source URL must be a valid URL.')
        expect(wrapper.text()).not.toContain('Not mine')
    })

    it('asks to be removed', async () => {
        const wrapper = mountRow()

        await wrapper.find('button[aria-label="Remove source 1"]').trigger('click')

        expect(wrapper.emitted('remove')).toHaveLength(1)
    })
})
