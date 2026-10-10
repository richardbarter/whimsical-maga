/** Typography for full details, shared by the editor and RichTextContent (see .rich-text in app.css). */
export const RICH_TEXT_CLASSES = 'rich-text prose prose-stone max-w-none'

const ALLOWED_LINK = /^(https?:\/\/|mailto:)/i

/** A scheme like "javascript:" — but not "example.com:8080", where a port follows the colon. */
const ANY_SCHEME = /^[a-z][a-z0-9+.-]*:(?!\d)/i

/** Links may only point to web pages or email addresses (the sanitizer enforces the same). */
export function isAllowedLinkUrl(url: string): boolean {
    return ALLOWED_LINK.test(url.trim())
}

/**
 * Turn what an admin typed into a link href: "example.com" becomes "https://example.com".
 * Returns null for empty input or any scheme other than http, https and mailto.
 */
export function normalizeLinkUrl(input: string): string | null {
    const url = input.trim()

    if (!url) return null
    if (isAllowedLinkUrl(url)) return url
    if (ANY_SCHEME.test(url)) return null

    return `https://${url}`
}

/**
 * The editor has two heading levels: Heading (h3) and Subheading (h4), because the subject
 * title is the h2 on the quote page. Map pasted headings onto them so they aren't lost:
 * h1–h2 → h3 and h5–h6 → h4. The server's RichTextSanitizer does the same.
 */
export function normalizePastedHeadings(html: string): string {
    return html
        .replace(/<(\/?)h[12](?=[\s>/])/gi, '<$1h3')
        .replace(/<(\/?)h[56](?=[\s>/])/gi, '<$1h4')
}
