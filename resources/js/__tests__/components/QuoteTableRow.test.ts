import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import QuoteTableRow from '@/Pages/Admin/Quotes/components/QuoteTableRow.vue'
import type { Quote } from '@/types'

vi.mock('@inertiajs/vue3', () => ({
    router: { patch: vi.fn() },
    Link: { template: '<a><slot /></a>' },
}))

// Stub child UI components so we don't pull in reka-ui in jsdom
const stubs = {
    Link: { template: '<a><slot /></a>' },
    Button: { template: '<button v-bind="$attrs"><slot /></button>' },
    Badge: { template: '<span><slot /></span>' },
    TableRow: { template: '<tr><slot /></tr>' },
    TableCell: { template: '<td><slot /></td>' },
    Tooltip: { template: '<div><slot /></div>' },
    TooltipTrigger: { template: '<div><slot /></div>' },
    TooltipContent: { template: '<div><slot /></div>' },
    Pencil: true,
    ShieldCheck: true,
    Star: true,
    Trash2: true,
    Check: true,
    Minus: true,
}

function makeQuote(overrides: Partial<Quote> = {}): Quote {
    return {
        id: 1,
        text: 'I am a very stable genius.',
        slug: 'i-am-a-very-stable-genius',
        speaker: { id: 1, name: 'Donald Trump', slug: 'donald-trump' },
        status: 'published',
        is_verified: false,
        is_featured: false,
        view_count: 0,
        created_at: '2025-01-15T00:00:00Z',
        updated_at: '2025-01-15T00:00:00Z',
        ...overrides,
    }
}

// route() is a Vue global property injected by Ziggy — mock it via global.mocks
const mockRoute = vi.fn((name: string, id?: number) => `/${name}/${id ?? ''}`)

const globalConfig = {
    stubs,
    mocks: { route: mockRoute },
}

describe('QuoteTableRow', () => {
    it('renders the quote text', () => {
        const wrapper = mount(QuoteTableRow, {
            props: { quote: makeQuote() },
            global: globalConfig,
        })

        expect(wrapper.text()).toContain('I am a very stable genius.')
    })

    it('renders the speaker name', () => {
        const wrapper = mount(QuoteTableRow, {
            props: { quote: makeQuote() },
            global: globalConfig,
        })

        expect(wrapper.text()).toContain('Donald Trump')
    })

    it('shows a dash when no speaker is set', () => {
        const wrapper = mount(QuoteTableRow, {
            props: { quote: makeQuote({ speaker: undefined, speaker_id: undefined }) },
            global: globalConfig,
        })

        expect(wrapper.text()).toContain('—')
    })

    it('emits confirmDelete with the quote when the delete button is clicked', async () => {
        const quote = makeQuote()
        const wrapper = mount(QuoteTableRow, {
            props: { quote },
            global: globalConfig,
        })

        await wrapper.find('button[title="Delete"]').trigger('click')

        expect(wrapper.emitted('confirmDelete')).toBeTruthy()
        expect(wrapper.emitted('confirmDelete')![0][0]).toMatchObject({ id: 1, text: quote.text })
    })
})
