import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import SourceList from '@/Pages/Public/Quotes/components/SourceList.vue'
import type { SelectOption, Source } from '@/types'

type SourceListItem = Pick<Source, 'url' | 'title' | 'source_type' | 'archived_url'>

const sourceTypes: SelectOption[] = [
    { value: 'article', label: 'Article' },
    { value: 'rally', label: 'Rally' },
]

function mountList(sources: SourceListItem[]) {
    return mount(SourceList, {
        props: { sources, sourceTypes },
        global: { stubs: { ExternalLink: true } },
    })
}

describe('SourceList', () => {
    it('renders one numbered item per source', () => {
        const wrapper = mountList([
            { url: 'https://example.com/a', title: 'First' },
            { url: 'https://example.com/b', title: 'Second' },
        ])

        expect(wrapper.find('ol').exists()).toBe(true)
        expect(wrapper.findAll('li')).toHaveLength(2)
    })

    it('links the title, opening in a new tab without referrer', () => {
        const wrapper = mountList([{ url: 'https://example.com/story', title: 'The story' }])
        const link = wrapper.find('a')

        expect(link.text()).toBe('The story')
        expect(link.attributes('href')).toBe('https://example.com/story')
        expect(link.attributes('target')).toBe('_blank')
        expect(link.attributes('rel')).toBe('nofollow noopener noreferrer')
    })

    it('falls back to the domain without www when there is no title', () => {
        const wrapper = mountList([{ url: 'https://www.example.com/story' }])

        expect(wrapper.find('a').text()).toBe('example.com')
    })

    it('falls back to the raw URL when it cannot be parsed', () => {
        const wrapper = mountList([{ url: 'not a url' }])

        expect(wrapper.find('a').text()).toBe('not a url')
    })

    it('shows the type label from the options', () => {
        const wrapper = mountList([{ url: 'https://example.com', source_type: 'rally' }])

        expect(wrapper.text()).toContain('Rally')
    })

    it('omits the type when it is missing or unknown', () => {
        const wrapper = mountList([
            { url: 'https://example.com/a', title: 'A' },
            { url: 'https://example.com/b', title: 'B', source_type: 'unknown' },
        ])

        expect(wrapper.text()).not.toContain('·')
    })

    it('links the archived copy when there is one', () => {
        const wrapper = mountList([
            { url: 'https://example.com', title: 'A', archived_url: 'https://web.archive.org/example' },
        ])
        const links = wrapper.findAll('a')

        expect(links).toHaveLength(2)
        expect(links[1].text()).toBe('archived copy')
        expect(links[1].attributes('href')).toBe('https://web.archive.org/example')
        expect(links[1].attributes('rel')).toBe('nofollow noopener noreferrer')
    })

    it('has no archived link when there is no archived URL', () => {
        const wrapper = mountList([{ url: 'https://example.com', title: 'A' }])

        expect(wrapper.findAll('a')).toHaveLength(1)
    })
})
