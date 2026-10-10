<?php

namespace App\Services;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

/**
 * Keeps an <img src> only when it points into our own subject image folder, e.g.
 * https://pub-….r2.dev/subject-images/abc.jpg. Anything else (a hot-linked image, a pasted
 * Google Docs link, a data: URL) loses its src, and RichTextSanitizer then removes the image.
 */
class StorageImageSourceSanitizer implements AttributeSanitizerInterface
{
    public function __construct(private string $allowedPrefix) {}

    public function getSupportedElements(): ?array
    {
        return ['img'];
    }

    public function getSupportedAttributes(): ?array
    {
        return ['src'];
    }

    public function sanitizeAttribute(string $element, string $attribute, string $value, HtmlSanitizerConfig $config): ?string
    {
        return str_starts_with($value, $this->allowedPrefix) ? $value : null;
    }
}
