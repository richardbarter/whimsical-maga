<?php

namespace App\Services;

/**
 * Removes privacy-sensitive metadata (EXIF incl. GPS location, XMP, IPTC, comments) from
 * JPEG, PNG and WebP files by dropping the container segments that hold it.
 *
 * The pixel data is copied byte-for-byte, so there is no recompression and no need to
 * decode the image into memory — a large photo costs only its file size. A JPEG's EXIF
 * orientation is preserved (as a minimal EXIF block) so rotated photos still display
 * upright. Files in any other format, or that don't parse, are left unchanged.
 */
class ImageMetadataStripper
{
    private const JPEG_SOI = "\xFF\xD8";

    private const PNG_SIGNATURE = "\x89PNG\r\n\x1A\n";

    /** APP1 (EXIF/XMP), APP13 (IPTC/Photoshop), COM (comments). ICC profiles (APP2) are kept. */
    private const JPEG_METADATA_MARKERS = [0xE1, 0xED, 0xFE];

    private const PNG_METADATA_CHUNKS = ['tEXt', 'zTXt', 'iTXt', 'eXIf', 'tIME'];

    private const WEBP_METADATA_CHUNKS = ['EXIF', 'XMP '];

    /** VP8X feature flags for "has EXIF" (0x08) and "has XMP" (0x04). */
    private const WEBP_METADATA_FLAGS = 0x0C;

    private const EXIF_ORIENTATION_TAG = 0x0112;

    public function strip(string $path): void
    {
        $bytes = file_get_contents($path);

        if ($bytes === false) {
            return;
        }

        $stripped = $this->stripBytes($bytes);

        if ($stripped !== $bytes) {
            file_put_contents($path, $stripped);
            clearstatcache(true, $path);
        }
    }

    public function stripBytes(string $bytes): string
    {
        return match (true) {
            str_starts_with($bytes, self::JPEG_SOI) => $this->stripJpeg($bytes),
            str_starts_with($bytes, self::PNG_SIGNATURE) => $this->stripPng($bytes),
            str_starts_with($bytes, 'RIFF') && substr($bytes, 8, 4) === 'WEBP' => $this->stripWebp($bytes),
            default => $bytes,
        };
    }

    private function stripJpeg(string $bytes): string
    {
        $length = strlen($bytes);
        $offset = 2;
        $segments = '';
        $orientation = null;

        // Walk the header segments up to Start-Of-Scan; everything after it is image data.
        while ($offset + 4 <= $length && $bytes[$offset] === "\xFF") {
            $marker = ord($bytes[$offset + 1]);

            if ($marker === 0xFF) {
                $offset++;

                continue;
            }

            if ($marker === 0xDA || $marker === 0xD9) {
                break;
            }

            $segmentLength = unpack('n', substr($bytes, $offset + 2, 2))[1];

            if ($segmentLength < 2 || $offset + 2 + $segmentLength > $length) {
                return $bytes;
            }

            $segment = substr($bytes, $offset, $segmentLength + 2);

            if ($marker === 0xE1 && substr($segment, 4, 6) === "Exif\0\0") {
                $orientation ??= $this->readExifOrientation(substr($segment, 10));
            }

            if (! in_array($marker, self::JPEG_METADATA_MARKERS, true)) {
                $segments .= $segment;
            }

            $offset += $segmentLength + 2;
        }

        $orientationSegment = $orientation !== null && $orientation !== 1
            ? $this->minimalExifSegment($orientation)
            : '';

        return self::JPEG_SOI.$orientationSegment.$segments.substr($bytes, $offset);
    }

    /**
     * Read the Orientation tag (0x0112) from IFD0 of a TIFF-structured EXIF block.
     */
    private function readExifOrientation(string $tiff): ?int
    {
        $byteOrder = substr($tiff, 0, 2);

        if ($byteOrder !== 'II' && $byteOrder !== 'MM') {
            return null;
        }

        $shortFormat = $byteOrder === 'MM' ? 'n' : 'v';
        $longFormat = $byteOrder === 'MM' ? 'N' : 'V';

        if (strlen($tiff) < 8) {
            return null;
        }

        $ifdOffset = unpack($longFormat, substr($tiff, 4, 4))[1];

        if (strlen($tiff) < $ifdOffset + 2) {
            return null;
        }

        $entryCount = unpack($shortFormat, substr($tiff, $ifdOffset, 2))[1];

        for ($index = 0; $index < $entryCount; $index++) {
            $entryOffset = $ifdOffset + 2 + ($index * 12);

            if (strlen($tiff) < $entryOffset + 10) {
                return null;
            }

            if (unpack($shortFormat, substr($tiff, $entryOffset, 2))[1] === self::EXIF_ORIENTATION_TAG) {
                $orientation = unpack($shortFormat, substr($tiff, $entryOffset + 8, 2))[1];

                return $orientation >= 1 && $orientation <= 8 ? $orientation : null;
            }
        }

        return null;
    }

    /**
     * An APP1 EXIF segment containing nothing but the Orientation tag.
     */
    private function minimalExifSegment(int $orientation): string
    {
        $tiff = 'MM'."\x00\x2A".pack('N', 8)     // big-endian TIFF header, IFD0 at offset 8
            .pack('n', 1)                         // one IFD entry
            .pack('nnN', self::EXIF_ORIENTATION_TAG, 3, 1) // tag, type SHORT, count 1
            .pack('n', $orientation)."\x00\x00"   // value, padded to 4 bytes
            .pack('N', 0);                        // no next IFD

        $payload = "Exif\0\0".$tiff;

        return "\xFF\xE1".pack('n', strlen($payload) + 2).$payload;
    }

    private function stripPng(string $bytes): string
    {
        $length = strlen($bytes);
        $offset = strlen(self::PNG_SIGNATURE);
        $chunks = '';

        while ($offset + 12 <= $length) {
            $chunkLength = unpack('N', substr($bytes, $offset, 4))[1];
            $type = substr($bytes, $offset + 4, 4);
            $chunkSize = 12 + $chunkLength; // length + type + data + CRC

            if ($offset + $chunkSize > $length) {
                return $bytes;
            }

            if (! in_array($type, self::PNG_METADATA_CHUNKS, true)) {
                $chunks .= substr($bytes, $offset, $chunkSize);
            }

            $offset += $chunkSize;

            if ($type === 'IEND') {
                break;
            }
        }

        return self::PNG_SIGNATURE.$chunks.substr($bytes, $offset);
    }

    private function stripWebp(string $bytes): string
    {
        $length = strlen($bytes);
        $offset = 12;
        $chunks = '';

        while ($offset + 8 <= $length) {
            $fourCc = substr($bytes, $offset, 4);
            $chunkLength = unpack('V', substr($bytes, $offset + 4, 4))[1];
            $chunkSize = 8 + $chunkLength + ($chunkLength % 2); // chunks are padded to even sizes

            if ($offset + $chunkSize > $length) {
                return $bytes;
            }

            $chunk = substr($bytes, $offset, $chunkSize);

            if ($fourCc === 'VP8X') {
                $chunk[8] = chr(ord($chunk[8]) & ~self::WEBP_METADATA_FLAGS & 0xFF);
            }

            if (! in_array($fourCc, self::WEBP_METADATA_CHUNKS, true)) {
                $chunks .= $chunk;
            }

            $offset += $chunkSize;
        }

        $body = 'WEBP'.$chunks.substr($bytes, $offset);

        return 'RIFF'.pack('V', strlen($body)).$body;
    }
}
