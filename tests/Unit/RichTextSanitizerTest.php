<?php

namespace Tests\Unit;

use App\Services\RichTextSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RichTextSanitizerTest extends TestCase
{
    private const STORAGE_URL = 'https://images.example.com/';

    private const OUR_IMAGE = 'https://images.example.com/subject-images/photo.jpg';

    private RichTextSanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = new RichTextSanitizer(self::STORAGE_URL);
    }

    // -------------------------------------------------------------------------
    // Allowed content
    // -------------------------------------------------------------------------

    public function test_allowed_formatting_is_kept(): void
    {
        $html = '<h3>Heading</h3><h4>Subheading</h4>'
            .'<p>Plain <strong>bold</strong> <em>italic</em> <s>struck</s><br>next line</p>'
            .'<ul><li><p>Bullet</p></li></ul><ol><li><p>Number</p></li></ol>'
            .'<blockquote><p>Quoted</p></blockquote><hr>';

        // Void elements come back self-closed
        $this->assertSame(str_replace(['<br>', '<hr>'], ['<br />', '<hr />'], $html), $this->sanitizer->sanitize($html));
    }

    public function test_a_table_with_merged_cells_is_kept(): void
    {
        $html = '<table><colgroup><col /><col /></colgroup><tbody>'
            .'<tr><th colspan="2"><p>Header</p></th></tr>'
            .'<tr><td rowspan="2"><p>Tall</p></td><td><p>A</p></td></tr>'
            .'<tr><td><p>B</p></td></tr>'
            .'</tbody></table>';

        $this->assertSame($html, $this->sanitizer->sanitize($html));
    }

    public function test_a_table_with_a_thead_is_kept(): void
    {
        $html = '<table><thead><tr><th>Year</th></tr></thead><tbody><tr><td>2020</td></tr></tbody></table>';

        $this->assertSame($html, $this->sanitizer->sanitize($html));
    }

    public function test_long_content_is_not_truncated(): void
    {
        $paragraph = '<p>'.str_repeat('Long content — with multibyte characters. ', 50).'</p>';
        $html = str_repeat($paragraph, (int) floor(RichTextSanitizer::MAX_LENGTH / mb_strlen($paragraph)));

        $this->assertGreaterThan(20_000, strlen($html));
        $this->assertSame($html, $this->sanitizer->sanitize($html));
    }

    // -------------------------------------------------------------------------
    // Removed content
    // -------------------------------------------------------------------------

    /**
     * @return array<string, array{string}>
     */
    public static function droppedElements(): array
    {
        return [
            'script' => ['<script>alert(1)</script>'],
            'style' => ['<style>p { color: red }</style>'],
            'iframe' => ['<iframe src="https://example.com">fallback</iframe>'],
            'object' => ['<object data="x.swf">fallback</object>'],
            'embed' => ['<embed src="x.swf">'],
            'svg' => ['<svg><text>drawn</text></svg>'],
            'noscript' => ['<noscript>no js</noscript>'],
            'title' => ['<title>Page title</title>'],
            'form controls' => ['<form><textarea>typed</textarea><button>Send</button></form>'],
            'unknown element' => ['<o:p>office markup</o:p>'],
            'video' => ['<video src="x.mp4">fallback</video>'],
        ];
    }

    #[DataProvider('droppedElements')]
    public function test_dangerous_elements_are_removed_with_their_contents(string $dangerous): void
    {
        $clean = $this->sanitizer->sanitize("<p>Before</p>{$dangerous}<p>After</p>");

        $this->assertSame('<p>Before</p><p>After</p>', $clean);
    }

    public function test_event_handler_style_and_class_attributes_are_removed(): void
    {
        $clean = $this->sanitizer->sanitize(
            '<p class="lead" style="color:red" onclick="alert(1)">Text <strong onmouseover="x()">bold</strong></p>'
            .'<table style="min-width: 50px"><tbody><tr><td style="width: 20px" data-colwidth="20">Cell</td></tr></tbody></table>'
        );

        $this->assertSame('<p>Text <strong>bold</strong></p><table><tbody><tr><td>Cell</td></tr></tbody></table>', $clean);
    }

    // -------------------------------------------------------------------------
    // Images
    // -------------------------------------------------------------------------

    public function test_a_figure_with_our_image_is_kept_and_loads_lazily(): void
    {
        $clean = $this->sanitizer->sanitize(
            '<figure><img src="'.self::OUR_IMAGE.'" alt="A ballot box" width="1200" height="800"><figcaption>Caption — Credit</figcaption></figure>'
        );

        $this->assertSame(
            '<figure><img src="'.self::OUR_IMAGE.'" alt="A ballot box" width="1200" height="800" loading="lazy" /><figcaption>Caption — Credit</figcaption></figure>',
            $clean
        );
    }

    public function test_image_attributes_other_than_src_alt_width_and_height_are_removed(): void
    {
        $clean = $this->sanitizer->sanitize(
            '<figure><img src="'.self::OUR_IMAGE.'" alt="x" onerror="alert(1)" style="width:9999px" class="big" srcset="https://evil.example/x.jpg 2x"></figure>'
        );

        $this->assertSame('<figure><img src="'.self::OUR_IMAGE.'" alt="x" loading="lazy" /></figure>', $clean);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function imagesFromElsewhere(): array
    {
        return [
            'another host' => ['https://evil.example/subject-images/photo.jpg'],
            'a Google Docs paste' => ['https://lh7-us.googleusercontent.com/docsz/AD_4nX.png'],
            'our host but another folder' => ['https://images.example.com/backgrounds/photo.jpg'],
            'our host over http' => ['http://images.example.com/subject-images/photo.jpg'],
            'a lookalike host' => ['https://images.example.com.evil.example/subject-images/photo.jpg'],
            'a data URL' => ['data:image/png;base64,iVBORw0KGgo='],
            'a local file' => ['file:///C:/Users/me/photo.jpg'],
            'relative' => ['/storage/subject-images/photo.jpg'],
        ];
    }

    #[DataProvider('imagesFromElsewhere')]
    public function test_images_from_anywhere_else_are_removed_with_their_figure(string $src): void
    {
        $clean = $this->sanitizer->sanitize(
            '<p>Before</p><figure><img src="'.$src.'" alt="x"><figcaption>Caption</figcaption></figure>'
            .'<img src="'.$src.'" alt="bare"><p>After</p>'
        );

        $this->assertSame('<p>Before</p><p>After</p>', $clean);
    }

    public function test_only_the_figure_with_a_removed_image_goes(): void
    {
        $clean = $this->sanitizer->sanitize(
            '<figure><img src="https://evil.example/a.jpg" alt="a"><figcaption>Theirs</figcaption></figure>'
            .'<figure><img src="'.self::OUR_IMAGE.'" alt="b"><figcaption>Ours</figcaption></figure>'
        );

        $this->assertSame('<figure><img src="'.self::OUR_IMAGE.'" alt="b" loading="lazy" /><figcaption>Ours</figcaption></figure>', $clean);
    }

    public function test_an_image_on_its_own_counts_as_content(): void
    {
        $this->assertNotNull($this->sanitizer->sanitize('<figure><img src="'.self::OUR_IMAGE.'" alt="x"></figure>'));
    }

    public function test_a_relative_storage_url_allows_relative_images_in_that_folder_only(): void
    {
        $sanitizer = new RichTextSanitizer('/storage/');

        $this->assertStringContainsString(
            'src="/storage/subject-images/photo.jpg"',
            $sanitizer->sanitize('<figure><img src="/storage/subject-images/photo.jpg" alt="x"></figure>')
        );
        $this->assertNull($sanitizer->sanitize('<figure><img src="/storage/backgrounds/photo.jpg" alt="x"></figure>'));
        $this->assertNull($sanitizer->sanitize('<figure><img src="https://images.example.com/subject-images/photo.jpg" alt="x"></figure>'));
    }

    public function test_a_local_storage_url_with_a_port_is_allowed(): void
    {
        $sanitizer = new RichTextSanitizer('http://localhost:8000/storage/');
        $src = 'http://localhost:8000/storage/subject-images/photo.jpg';

        $this->assertStringContainsString('src="'.$src.'"', $sanitizer->sanitize('<figure><img src="'.$src.'" alt="x"></figure>'));
        $this->assertNull($sanitizer->sanitize('<figure><img src="http://localhost:9999/storage/subject-images/photo.jpg" alt="x"></figure>'));
    }

    public function test_wrapper_elements_are_removed_but_their_text_is_kept(): void
    {
        $clean = $this->sanitizer->sanitize(
            '<div><p><span style="font-size:14px">Span</span> <font color="red">font</font> '
            .'<b style="font-weight:normal">b</b> <i>i</i> <u>u</u> <code>code</code> <sup>1</sup></p></div>'
            .'<section><pre>preformatted</pre></section>'
        );

        $this->assertSame('<p>Span font b i u code 1</p>preformatted', $clean);
    }

    public function test_other_heading_levels_are_mapped_to_heading_and_subheading(): void
    {
        $clean = $this->sanitizer->sanitize(
            '<h1>One</h1><h2 class="x">Two</h2><h3>Three</h3><h4>Four</h4><h5>Five</h5><h6>Six</h6>'
        );

        $this->assertSame('<h3>One</h3><h3>Two</h3><h3>Three</h3><h4>Four</h4><h4>Five</h4><h4>Six</h4>', $clean);
    }

    // -------------------------------------------------------------------------
    // Links
    // -------------------------------------------------------------------------

    public function test_links_get_rel_and_target_forced(): void
    {
        $clean = $this->sanitizer->sanitize('<p><a href="https://example.com" rel="ugc" target="_self" title="x">Link</a></p>');

        $this->assertSame(
            '<p><a href="https://example.com" rel="nofollow noopener noreferrer" target="_blank">Link</a></p>',
            $clean
        );
    }

    public function test_mailto_and_http_links_are_kept(): void
    {
        $clean = $this->sanitizer->sanitize('<p><a href="mailto:hi@example.com">Mail</a> <a href="http://example.com">Web</a></p>');

        // The sanitizer entity-encodes "@", which browsers decode back
        $this->assertStringContainsString('href="mailto:hi&#64;example.com"', $clean);
        $this->assertStringContainsString('href="http://example.com"', $clean);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unsafeHrefs(): array
    {
        return [
            'javascript' => ['javascript:alert(1)'],
            'javascript with spaces and case' => [' JaVaScRiPt:alert(1)'],
            'data' => ['data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg=='],
            'vbscript' => ['vbscript:msgbox(1)'],
            'tel' => ['tel:123'],
            'relative' => ['/admin'],
        ];
    }

    #[DataProvider('unsafeHrefs')]
    public function test_links_with_other_schemes_lose_their_href(string $href): void
    {
        $clean = $this->sanitizer->sanitize('<p><a href="'.$href.'">Click</a></p>');

        $this->assertStringNotContainsString('href', $clean);
        $this->assertStringContainsString('Click', $clean);
    }

    // -------------------------------------------------------------------------
    // Empty content
    // -------------------------------------------------------------------------

    /**
     * @return array<string, array{?string}>
     */
    public static function emptyContent(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
            'whitespace' => ["  \n\t "],
            'empty paragraph' => ['<p></p>'],
            'paragraph with a line break' => ['<p><br></p>'],
            'non-breaking spaces' => ['<p>&nbsp; &nbsp;</p>'],
            'divider only' => ['<hr>'],
            'only dropped content' => ['<script>alert(1)</script><style>p{}</style>'],
        ];
    }

    #[DataProvider('emptyContent')]
    public function test_content_without_text_becomes_null(?string $html): void
    {
        $this->assertNull($this->sanitizer->sanitize($html));
    }

    public function test_a_table_without_text_still_counts_as_content(): void
    {
        $this->assertNotNull($this->sanitizer->sanitize('<table><tbody><tr><td></td></tr></tbody></table>'));
    }

    public function test_trailing_empty_paragraphs_are_removed(): void
    {
        $this->assertSame(
            '<p></p><p>Keep the gap above</p><ul><li><p>List</p></li></ul>',
            $this->sanitizer->sanitize('<p></p><p>Keep the gap above</p><ul><li><p>List</p></li></ul><p></p><p><br></p> ')
        );
    }

    public function test_surrounding_whitespace_is_trimmed(): void
    {
        $this->assertSame('<p>Text</p>', $this->sanitizer->sanitize("\n  <p>Text</p>  \n"));
    }
}
