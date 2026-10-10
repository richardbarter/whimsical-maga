<?php

namespace App\Services;

use App\Models\SubjectImage;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerAction;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Cleans rich text (subject full details) down to the allow-list in docs/full-details-plan.md
 * ("Editor and Content Rules"). Keep it in sync with the editor's extensions in
 * resources/js/Components/ui/rich-text-editor/extensions.ts: anything the editor can produce
 * must be allowed here, and everything else is removed.
 *
 * Runs on save and again on display, so content written outside the admin form is safe too.
 *
 * Images are only kept when they come from our own storage (uploaded through the editor),
 * because the CSP blocks other hosts and pasted document images are temporary or local files.
 */
class RichTextSanitizer
{
    /** Stored HTML limit in characters: about 100,000 characters of visible text plus tags. */
    public const MAX_LENGTH = 150_000;

    /**
     * Allowed elements and their allowed attributes.
     *
     * @var array<string, list<string>>
     */
    private const ALLOWED_ELEMENTS = [
        'p' => [],
        'br' => [],
        'strong' => [],
        'em' => [],
        's' => [],
        'h3' => [],
        'h4' => [],
        'ul' => [],
        'ol' => [],
        'li' => [],
        'blockquote' => [],
        'hr' => [],
        'a' => ['href'],
        'table' => [],
        'thead' => [],
        'tbody' => [],
        'tr' => [],
        'th' => ['colspan', 'rowspan'],
        'td' => ['colspan', 'rowspan'],
        'colgroup' => [],
        'col' => [],
        'figure' => [],
        'figcaption' => [],
        'img' => ['src', 'alt', 'width', 'height'],
    ];

    /**
     * Wrappers that are unwrapped: the tag goes, its text stays (fonts, spans and divs from
     * pasted documents, and markup the editor doesn't support, like code). Every element that
     * is neither allowed nor listed here is dropped together with its contents, including
     * script, style, iframe, object, embed, svg and form controls.
     *
     * @var list<string>
     */
    private const UNWRAPPED_ELEMENTS = [
        'span', 'div', 'font', 'b', 'i', 'u', 'small', 'big', 'sub', 'sup', 'mark', 'q',
        'abbr', 'cite', 'dfn', 'time', 'code', 'pre', 'kbd', 'samp', 'var', 'del', 'ins',
        'bdi', 'bdo', 'label', 'center', 'section', 'article', 'main', 'header', 'footer',
        'nav', 'aside', 'address', 'dl', 'dt', 'dd', 'details', 'summary', 'caption', 'tfoot',
    ];

    private HtmlSanitizer $sanitizer;

    /**
     * @param  string|null  $storageUrl  Base URL of the storage disk, e.g. "https://pub-….r2.dev/" in
     *                                   production or "http://localhost:8000/storage/" locally (relative
     *                                   under Storage::fake()). Defaults to the default disk's URL.
     */
    public function __construct(?string $storageUrl = null)
    {
        $storageUrl ??= Storage::disk()->url('');

        // Drop is the default action already; it's set explicitly because the security depends on it
        $config = (new HtmlSanitizerConfig)
            ->defaultAction(HtmlSanitizerAction::Drop)
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            ->forceAttribute('a', 'rel', 'nofollow noopener noreferrer')
            ->forceAttribute('a', 'target', '_blank')
            ->forceAttribute('img', 'loading', 'lazy')
            ->withAttributeSanitizer(new StorageImageSourceSanitizer(rtrim($storageUrl, '/').'/'.SubjectImage::FOLDER.'/'))
            // The default silently cuts input at 20,000 bytes; allow MAX_LENGTH characters of up to 4 bytes each
            ->withMaxInputLength(self::MAX_LENGTH * 4);

        // The built-in URL check runs first, so it must accept the storage URL's form too
        $storage = parse_url($storageUrl);
        $config = isset($storage['host'], $storage['scheme'])
            ? $config->allowMediaSchemes([$storage['scheme']])->allowMediaHosts([$storage['host']])
            : $config->allowMediaSchemes([])->allowRelativeMedias();

        foreach (self::ALLOWED_ELEMENTS as $element => $attributes) {
            $config = $config->allowElement($element, $attributes);
        }

        foreach (self::UNWRAPPED_ELEMENTS as $element) {
            $config = $config->blockElement($element);
        }

        $this->sanitizer = new HtmlSanitizer($config);
    }

    /**
     * Returns null when nothing meaningful is left, so an empty editor is stored as null.
     */
    public function sanitize(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $clean = $this->removeImagesWithoutSource($this->sanitizer->sanitize($this->normalizeHeadings($html)));

        // The editor keeps an empty paragraph after a final list, heading or table so the cursor can reach it
        $clean = trim(preg_replace('/(\s*<p>(\s|<br \/>)*<\/p>)+\s*$/', '', $clean));

        return $this->hasContent($clean) ? $clean : null;
    }

    /**
     * Map other heading levels onto the two the editor supports (h1–h2 → h3, h5–h6 → h4),
     * as the editor does when pasting, so imported content keeps its structure.
     */
    private function normalizeHeadings(string $html): string
    {
        return preg_replace(['/<(\/?)h[12](?=[\s>\/])/i', '/<(\/?)h[56](?=[\s>\/])/i'], ['<$1h3', '<$1h4'], $html);
    }

    /**
     * An image from another host keeps its tag but loses its src, which would show as a broken
     * image. Remove it, and any figure left without an image (its caption described that image).
     * This runs on the sanitizer's own output, which is normalised, so these patterns are reliable.
     */
    private function removeImagesWithoutSource(string $html): string
    {
        $html = preg_replace('/<img(?![^>]*\ssrc=)[^>]*>/', '', $html);

        return preg_replace('/<figure>(?:(?!<img\b).)*?<\/figure>/s', '', $html);
    }

    /**
     * Text, a table or an image counts as content; empty paragraphs, line breaks and dividers don't.
     */
    private function hasContent(string $html): bool
    {
        if (str_contains($html, '<table') || str_contains($html, '<img')) {
            return true;
        }

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim($text, " \t\n\r\0\x0B\u{A0}") !== '';
    }
}
