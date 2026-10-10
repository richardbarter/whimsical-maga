import { afterEach, describe, expect, it } from 'vitest'
import { Editor } from '@tiptap/vue-3'
import { createRichTextExtensions } from '@/Components/ui/rich-text-editor/extensions'
import { isAllowedLinkUrl, normalizeLinkUrl, normalizePastedHeadings } from '@/Components/ui/rich-text-editor/utils'

// jsdom has no ClipboardEvent; ProseMirror's pasteHTML() creates one for its paste handlers
globalThis.ClipboardEvent ??= class extends Event {
    clipboardData: DataTransfer | null = null
} as unknown as typeof ClipboardEvent

const IMAGES = 'https://images.example.com/subject-images/'

let editor: Editor | null = null

function makeEditor(content = ''): Editor {
    editor = new Editor({
        extensions: createRichTextExtensions({ imageUrlPrefix: IMAGES }),
        content,
        editorProps: { transformPastedHTML: normalizePastedHeadings },
    })

    return editor
}

/**
 * Paste through ProseMirror's real clipboard parsing into an empty editor. StarterKit's
 * trailing node adds an empty paragraph after a final heading or list so the cursor can get
 * below it; the server strips it, so it's removed here too.
 */
function paste(html: string): string {
    const instance = makeEditor()
    instance.view.pasteHTML(html)

    return instance.getHTML().replace(/<p><\/p>$/, '')
}

afterEach(() => {
    editor?.destroy()
    editor = null
})

describe('normalizeLinkUrl', () => {
    it.each([
        ['https://example.com/a', 'https://example.com/a'],
        ['http://example.com', 'http://example.com'],
        ['mailto:hi@example.com', 'mailto:hi@example.com'],
        ['  example.com/path  ', 'https://example.com/path'],
        ['www.example.com', 'https://www.example.com'],
        ['example.com:8080/x', 'https://example.com:8080/x'],
    ])('%s → %s', (input, expected) => {
        expect(normalizeLinkUrl(input)).toBe(expected)
    })

    it.each(['', '   ', 'javascript:alert(1)', 'JavaScript:alert(1)', 'data:text/html,x', 'ftp://example.com'])(
        'rejects %j',
        (input) => {
            expect(normalizeLinkUrl(input)).toBeNull()
        },
    )

    it('only allows http, https and mailto links', () => {
        expect(isAllowedLinkUrl('https://example.com')).toBe(true)
        expect(isAllowedLinkUrl('javascript:alert(1)')).toBe(false)
        expect(isAllowedLinkUrl('/relative')).toBe(false)
    })
})

describe('normalizePastedHeadings', () => {
    it('maps h1–h2 to h3 and h5–h6 to h4, keeping attributes', () => {
        expect(normalizePastedHeadings('<h1 id="a">One</h1><H2>Two</H2><h3>Three</h3><h4>Four</h4><h5>Five</h5><h6>Six</h6>'))
            .toBe('<h3 id="a">One</h3><h3>Two</h3><h3>Three</h3><h4>Four</h4><h4>Five</h4><h4>Six</h4>')
    })

    it('leaves other tags that start with h alone', () => {
        expect(normalizePastedHeadings('<hr><header>x</header><html>')).toBe('<hr><header>x</header><html>')
    })
})

describe('editor schema', () => {
    it('keeps everything in the content rules', () => {
        const html = '<h3>Heading</h3><h4>Subheading</h4>'
            + '<p><strong>bold</strong> <em>italic</em> <s>struck</s><br>line</p>'
            + '<ul><li><p>bullet</p></li></ul><ol><li><p>number</p></li></ol>'
            + '<blockquote><p>quote</p></blockquote><hr>'

        expect(makeEditor(html).getHTML()).toBe(html)
    })

    it('outputs links with rel and target', () => {
        expect(makeEditor('<p><a href="https://example.com">link</a></p>').getHTML())
            .toBe('<p><a target="_blank" rel="nofollow noopener noreferrer" href="https://example.com">link</a></p>')
    })

    it('keeps tables with merged cells', () => {
        const html = makeEditor(
            '<table><tbody><tr><th colspan="2"><p>Header</p></th></tr><tr><td><p>A</p></td><td><p>B</p></td></tr></tbody></table>',
        ).getHTML()

        expect(html).toContain('<table')
        expect(html).toContain('colspan="2"')
        expect(html).toContain('<td colspan="1" rowspan="1"><p>A</p></td>')
    })

    it('has no code, code block or underline', () => {
        expect(makeEditor('<p><code>code</code> <u>under</u></p><pre><code>block</code></pre>').getHTML())
            .toBe('<p>code under</p><p>block</p>')
    })

    it('drops links with other schemes but keeps their text', () => {
        expect(makeEditor('<p><a href="javascript:alert(1)">click</a></p>').getHTML()).toBe('<p>click</p>')
    })

    it('drops images from anywhere but our storage', () => {
        expect(makeEditor('<p>before</p><img src="https://example.com/x.png"><figure><img src="https://example.com/y.png"></figure><p>after</p>').getHTML())
            .toBe('<p>before</p><p>after</p>')
    })
})

describe('figures', () => {
    const figure = `<figure><img src="${IMAGES}chart.png" alt="A bar chart" width="1200" height="800"><figcaption>Fraud rates — Brennan Center</figcaption></figure>`

    it('keeps a figure with our image, its size and caption', () => {
        expect(makeEditor(figure).getHTML()).toBe(figure)
    })

    it('leaves out the figcaption when there is no caption', () => {
        expect(makeEditor(`<figure><img src="${IMAGES}a.png" alt="x"></figure>`).getHTML())
            .toBe(`<figure><img src="${IMAGES}a.png" alt="x"></figure>`)
    })

    it('wraps a bare image of ours in a figure', () => {
        expect(makeEditor(`<p>Text</p><img src="${IMAGES}a.png" alt="x">`).getHTML())
            .toBe(`<p>Text</p><figure><img src="${IMAGES}a.png" alt="x"></figure>`)
    })

    it('ignores nonsense sizes', () => {
        expect(makeEditor(`<figure><img src="${IMAGES}a.png" alt="x" width="-5" height="big"></figure>`).getHTML())
            .toBe(`<figure><img src="${IMAGES}a.png" alt="x"></figure>`)
    })

    it('inserts and updates a figure with commands', () => {
        const instance = makeEditor('<p>Intro</p>')
        instance.commands.setTextSelection(instance.state.doc.content.size)
        instance.commands.insertFigure({ src: `${IMAGES}b.png`, alt: 'Alt', width: 10, height: 20, caption: '' })

        expect(instance.getHTML()).toContain(`<figure><img src="${IMAGES}b.png" alt="Alt" width="10" height="20"></figure>`)

        instance.commands.setNodeSelection(instance.state.doc.child(0).nodeSize)
        instance.commands.updateFigure({ alt: 'Better alt', caption: 'New caption' })

        expect(instance.getHTML()).toContain(`<figure><img src="${IMAGES}b.png" alt="Better alt" width="10" height="20"><figcaption>New caption</figcaption></figure>`)
    })

    it('allows no images at all without a storage prefix', () => {
        editor = new Editor({ extensions: createRichTextExtensions(), content: figure })

        expect(editor.getHTML()).toBe('<p></p>')
    })
})

describe('pasting', () => {
    it('maps pasted h1/h2 to Heading and h5/h6 to Subheading', () => {
        expect(paste('<h1>One</h1><h2>Two</h2><h5>Five</h5>')).toBe('<h3>One</h3><h3>Two</h3><h4>Five</h4>')
    })

    it('ignores the bold wrapper Google Docs puts around everything, but keeps real bold', () => {
        const googleDocs = '<meta charset="utf-8"><b style="font-weight:normal;" id="docs-internal-guid-1">'
            + '<p dir="ltr"><span style="font-weight:400">Plain </span><span style="font-weight:700">bold</span></p>'
            + '<ul><li dir="ltr"><p dir="ltr"><span>Item</span></p></li></ul>'
            + '</b>'

        expect(paste(googleDocs)).toBe('<p>Plain <strong>bold</strong></p><ul><li><p>Item</p></li></ul>')
    })

    it('strips fonts, colours and classes from pasted text', () => {
        expect(paste('<p class="MsoNormal"><span style="font-family:Calibri;color:red">Styled</span> <font face="Arial">text</font></p>'))
            .toBe('<p>Styled text</p>')
    })

    it('keeps pasted links', () => {
        expect(paste('<p>See <a href="https://example.com/report" style="color:blue">the report</a></p>'))
            .toBe('<p>See <a target="_blank" rel="nofollow noopener noreferrer" href="https://example.com/report">the report</a></p>')
    })

    it('keeps pasted tables', () => {
        expect(paste('<table><tr><td>A</td><td>B</td></tr></table>')).toContain('<td colspan="1" rowspan="1"><p>A</p></td>')
    })

    it('drops pasted images from documents', () => {
        expect(paste('<p>Text</p><img src="blob:local-file"><img src="https://lh7-us.googleusercontent.com/x.png">')).toBe('<p>Text</p>')
    })

    it('keeps pasted figures of ours, e.g. copied from another subject', () => {
        expect(paste(`<figure><img src="${IMAGES}c.png" alt="x"><figcaption>Cap</figcaption></figure>`))
            .toBe(`<figure><img src="${IMAGES}c.png" alt="x"><figcaption>Cap</figcaption></figure>`)
    })
})
