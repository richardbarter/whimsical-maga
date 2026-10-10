import type { Extensions } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import { TableKit } from '@tiptap/extension-table'
import { Figure } from './figure'
import { isAllowedLinkUrl } from './utils'

interface RichTextExtensionOptions {
    /** URL that uploaded images start with; images from anywhere else are dropped. Null allows none. */
    imageUrlPrefix?: string | null
}

/**
 * Everything the editor can produce. Keep in sync with app/Services/RichTextSanitizer.php
 * and the "Editor and Content Rules" in docs/full-details-plan.md: pasted content keeps only
 * what this schema allows.
 */
export function createRichTextExtensions({ imageUrlPrefix = null }: RichTextExtensionOptions = {}): Extensions {
    return [
        StarterKit.configure({
            code: false,
            codeBlock: false,
            // Underlined text looks like a link on the web
            underline: false,
            heading: { levels: [3, 4] },
            link: {
                openOnClick: false,
                autolink: true,
                defaultProtocol: 'https',
                isAllowedUri: (url) => isAllowedLinkUrl(url),
                HTMLAttributes: { rel: 'nofollow noopener noreferrer', target: '_blank' },
            },
        }),
        TableKit.configure({
            table: { resizable: false },
        }),
        Figure.configure({
            isAllowedSrc: (src) => !!imageUrlPrefix && src.startsWith(imageUrlPrefix),
        }),
    ]
}
