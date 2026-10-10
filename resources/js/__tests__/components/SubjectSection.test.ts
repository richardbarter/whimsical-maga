import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import SubjectSection from '@/Pages/Public/Quotes/components/SubjectSection.vue'
import RichTextContent from '@/Components/ui/rich-text-editor/RichTextContent.vue'
import type { QuotePageSubject } from '@/types'

function makeSubject(overrides: Partial<QuotePageSubject> = {}): QuotePageSubject {
    return {
        id: 1,
        name: 'Mail-in ballots are rife with fraud',
        slug: 'mail-in-ballots-are-rife-with-fraud',
        status: 'published',
        full_details_html: '<h3>What the evidence shows</h3><p>Fraud is <strong>rare</strong>.</p>',
        sources: [],
        ...overrides,
    }
}

const sourceTypes = [
    { value: 'study', label: 'Study / Research' },
    { value: 'court_ruling', label: 'Court ruling' },
]

function mountSection(subject: QuotePageSubject) {
    return mount(SubjectSection, {
        props: { subject, anchorId: `subject-${subject.slug}`, sourceTypes },
        global: { stubs: { BookOpenText: true, BookOpen: true, ExternalLink: true } },
    })
}

describe('SubjectSection', () => {
    it('titles the section with the subject name as its anchor target', () => {
        const wrapper = mountSection(makeSubject())
        const heading = wrapper.find('h2')

        expect(heading.text()).toBe('Mail-in ballots are rife with fraud')
        expect(heading.attributes('id')).toBe('subject-mail-in-ballots-are-rife-with-fraud')
        expect(wrapper.find('section').attributes('aria-labelledby')).toBe('subject-mail-in-ballots-are-rife-with-fraud')
    })

    it('renders the full details as formatted HTML', () => {
        const wrapper = mountSection(makeSubject())

        expect(wrapper.find('h3').text()).toBe('What the evidence shows')
        expect(wrapper.find('strong').text()).toBe('rare')
    })

    it('marks a draft subject', () => {
        expect(mountSection(makeSubject({ status: 'draft' })).text()).toContain('Draft, only admins see this')
        expect(mountSection(makeSubject()).text()).not.toContain('Draft')
    })

    it('lists the sources under the full details, numbered, with their type labels', () => {
        const wrapper = mountSection(makeSubject({
            sources: [
                { url: 'https://example.com/study', title: 'The study', source_type: 'study', archived_url: null },
                { url: 'https://courts.example.gov/ruling', title: null, source_type: 'court_ruling', archived_url: null },
            ],
        }))

        expect(wrapper.findAll('h3').at(-1)?.text()).toBe('Sources')
        const items = wrapper.findAll('ol li')
        expect(items).toHaveLength(2)
        expect(items[0].text()).toContain('The study')
        expect(items[0].text()).toContain('Study / Research')
        expect(items[1].text()).toContain('courts.example.gov')
        expect(items[1].text()).toContain('Court ruling')
        expect(items[0].find('a').attributes('rel')).toBe('nofollow noopener noreferrer')
    })

    it('has no sources heading when there are none', () => {
        expect(mountSection(makeSubject()).text()).not.toContain('Sources')
    })

    it('says so when no full details have been written', () => {
        const wrapper = mountSection(makeSubject({ full_details_html: null }))

        expect(wrapper.findComponent(RichTextContent).exists()).toBe(false)
        expect(wrapper.text()).toContain("haven't been written yet")
    })
})

describe('RichTextContent', () => {
    it('uses the shared rich text typography', () => {
        const wrapper = mount(RichTextContent, { props: { html: '<p>Text</p>' } })

        expect(wrapper.classes()).toEqual(expect.arrayContaining(['rich-text', 'prose', 'prose-stone', 'max-w-none']))
        expect(wrapper.html()).toContain('<p>Text</p>')
    })
})
