import { Node } from '@tiptap/vue-3'

/** An uploaded image with its alt text and caption ("Caption — Credit"). */
export interface FigureAttributes {
    src: string
    alt: string
    width: number | null
    height: number | null
    caption: string
}

interface FigureOptions {
    /** Only images from our own storage are kept; pasted images from anywhere else are dropped. */
    isAllowedSrc: (src: string) => boolean
}

declare module '@tiptap/core' {
    interface Commands<ReturnType> {
        figure: {
            insertFigure: (attributes: FigureAttributes) => ReturnType
            updateFigure: (attributes: Partial<FigureAttributes>) => ReturnType
        }
    }
}

/** The <img> of a <figure>, or the element itself for a bare <img>. */
function imageIn(element: HTMLElement): HTMLElement | null {
    return element.tagName === 'IMG' ? element : element.querySelector('img')
}

function numberAttribute(element: HTMLElement | null, name: string): number | null {
    const value = Number.parseInt(element?.getAttribute(name) ?? '', 10)

    return Number.isFinite(value) && value > 0 ? value : null
}

/**
 * <figure><img src alt width height><figcaption>caption</figcaption></figure>, as one block
 * that's selected, moved and deleted as a unit. The alt text and caption are edited in the
 * image dialog rather than inline, so the figure can't end up half-deleted.
 */
export const Figure = Node.create<FigureOptions>({
    name: 'figure',
    group: 'block',
    atom: true,
    draggable: true,
    selectable: true,

    addOptions() {
        return {
            isAllowedSrc: () => false,
        }
    },

    addAttributes() {
        return {
            src: { default: null, parseHTML: (element) => imageIn(element)?.getAttribute('src') ?? null },
            alt: { default: '', parseHTML: (element) => imageIn(element)?.getAttribute('alt') ?? '' },
            width: { default: null, parseHTML: (element) => numberAttribute(imageIn(element), 'width') },
            height: { default: null, parseHTML: (element) => numberAttribute(imageIn(element), 'height') },
            caption: {
                default: '',
                parseHTML: (element) => element.querySelector('figcaption')?.textContent?.trim() ?? '',
            },
        }
    },

    parseHTML() {
        const isAllowed = (element: HTMLElement) => {
            const src = imageIn(element)?.getAttribute('src')

            return src && this.options.isAllowedSrc(src) ? null : false
        }

        return [
            { tag: 'figure', getAttrs: (element) => isAllowed(element as HTMLElement) },
            { tag: 'img[src]', getAttrs: (element) => isAllowed(element as HTMLElement) },
            // Any other figure goes entirely, caption included, as the server's sanitizer does
            { tag: 'figure', ignore: true },
        ]
    },

    renderHTML({ node }) {
        const { src, alt, width, height, caption } = node.attrs as FigureAttributes
        const image = ['img', { src, alt, width, height }]

        return caption ? ['figure', {}, image, ['figcaption', {}, caption]] : ['figure', {}, image]
    },

    addCommands() {
        return {
            insertFigure: (attributes) => ({ commands }) => commands.insertContent({ type: this.name, attrs: attributes }),
            updateFigure: (attributes) => ({ commands }) => commands.updateAttributes(this.name, attributes),
        }
    },
})
