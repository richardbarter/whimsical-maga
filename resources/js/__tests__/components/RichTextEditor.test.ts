import { describe, it, expect } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { EditorContent, type Editor } from '@tiptap/vue-3'
import { RichTextEditor } from '@/Components/ui/rich-text-editor'

// The toolbar is built from reka-ui components; its behaviour is covered by tests/e2e/rich-text-editor.spec.ts
const global = { stubs: { RichTextToolbar: true } }

async function mountEditor(modelValue: string) {
    const wrapper = mount(RichTextEditor, {
        props: {
            modelValue,
            label: 'Full details',
            'onUpdate:modelValue': (value: string) => wrapper.setProps({ modelValue: value }),
        },
        global,
        attachTo: document.body,
    })
    await flushPromises()
    const editor = wrapper.findComponent(EditorContent).props('editor') as Editor

    return { wrapper, editor }
}

describe('RichTextEditor', () => {
    it('shows the initial HTML', async () => {
        const { wrapper } = await mountEditor('<p>Hello <strong>world</strong></p>')

        expect(wrapper.find('.ProseMirror').html()).toContain('Hello <strong>world</strong>')
        wrapper.unmount()
    })

    it('labels the editing area for screen readers', async () => {
        const { wrapper } = await mountEditor('')

        const area = wrapper.find('.ProseMirror')
        expect(area.attributes('role')).toBe('textbox')
        expect(area.attributes('aria-label')).toBe('Full details')
        wrapper.unmount()
    })

    it('emits the HTML when the content changes', async () => {
        const { wrapper, editor } = await mountEditor('')

        editor.commands.setContent('<p>Typed</p>', { emitUpdate: true })

        expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual(['<p>Typed</p>'])
        wrapper.unmount()
    })

    it('emits an empty string, not <p></p>, when the editor is emptied', async () => {
        const { wrapper, editor } = await mountEditor('<p>Something</p>')

        editor.commands.clearContent(true)

        expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual([''])
        wrapper.unmount()
    })

    it('replaces the content when the model changes from outside', async () => {
        const { wrapper, editor } = await mountEditor('<p>Old</p>')

        await wrapper.setProps({ modelValue: '<p>Reset</p>' })

        expect(editor.getHTML()).toBe('<p>Reset</p>')
        wrapper.unmount()
    })

    it('marks the editor invalid', async () => {
        const wrapper = mount(RichTextEditor, { props: { modelValue: '', invalid: true }, global })

        expect(wrapper.classes()).toContain('border-destructive')
        wrapper.unmount()
    })
})
