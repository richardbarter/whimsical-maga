<?php

namespace Tests\Concerns;

/**
 * Builds real JPEG/PNG/WebP files (via GD) with metadata planted in them, so tests can
 * prove the metadata is removed while the image still decodes.
 */
trait CreatesImagesWithMetadata
{
    protected function jpegWithMetadata(string $secret, int $orientation = 6): string
    {
        $jpeg = $this->encodeWithGd('imagejpeg');

        $exifTiff = 'II'."\x2A\x00".pack('V', 8)
            .pack('v', 1)
            .pack('vvV', 0x0112, 3, 1).pack('v', $orientation)."\x00\x00"
            .pack('V', 0)
            ."GPS {$secret}";

        $segments = $this->jpegSegment(0xE1, "Exif\0\0".$exifTiff)
            .$this->jpegSegment(0xE1, "http://ns.adobe.com/xap/1.0/\0<x:xmpmeta>XMP {$secret}</x:xmpmeta>")
            .$this->jpegSegment(0xFE, "COMMENT {$secret}");

        return substr($jpeg, 0, 2).$segments.substr($jpeg, 2);
    }

    protected function pngWithMetadata(string $secret): string
    {
        $png = $this->encodeWithGd('imagepng');
        $data = "Comment\0{$secret}";
        $textChunk = pack('N', strlen($data)).'tEXt'.$data.pack('N', crc32('tEXt'.$data));

        // Signature (8 bytes) + IHDR chunk (25 bytes), then the planted chunk.
        return substr($png, 0, 33).$textChunk.substr($png, 33);
    }

    protected function webpWithMetadata(string $secret): string
    {
        $webp = $this->encodeWithGd('imagewebp');
        $imageChunk = substr($webp, 12);

        $vp8x = 'VP8X'.pack('V', 10)
            .chr(0x08)."\0\0\0"                    // flags: has EXIF
            .substr(pack('V', 15), 0, 3)           // canvas width - 1
            .substr(pack('V', 9), 0, 3);           // canvas height - 1

        $exifData = "II\x2A\x00 GPS {$secret}";
        $exifChunk = 'EXIF'.pack('V', strlen($exifData)).$exifData.(strlen($exifData) % 2 ? "\0" : '');

        $body = 'WEBP'.$vp8x.$exifChunk.$imageChunk;

        return 'RIFF'.pack('V', strlen($body)).$body;
    }

    private function encodeWithGd(string $encoder): string
    {
        $image = imagecreatetruecolor(16, 10);
        imagefill($image, 0, 0, imagecolorallocate($image, 200, 60, 40));

        ob_start();
        $encoder($image);

        return (string) ob_get_clean();
    }

    private function jpegSegment(int $marker, string $payload): string
    {
        return "\xFF".chr($marker).pack('n', strlen($payload) + 2).$payload;
    }
}
