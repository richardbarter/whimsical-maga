<?php

namespace Tests\Unit;

use App\Services\ImageMetadataStripper;
use PHPUnit\Framework\TestCase;
use Tests\Concerns\CreatesImagesWithMetadata;

class ImageMetadataStripperTest extends TestCase
{
    use CreatesImagesWithMetadata;

    private const SECRET = 'LAT-38.8977-LNG-77.0365';

    private ImageMetadataStripper $stripper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stripper = new ImageMetadataStripper;
    }

    public function test_jpeg_exif_xmp_and_comments_are_removed_and_the_image_still_decodes(): void
    {
        $stripped = $this->stripper->stripBytes($this->jpegWithMetadata(self::SECRET));

        $this->assertStringNotContainsString(self::SECRET, $stripped);
        $this->assertNotFalse(imagecreatefromstring($stripped));
    }

    public function test_jpeg_orientation_is_preserved_so_rotated_photos_stay_upright(): void
    {
        $stripped = $this->stripper->stripBytes($this->jpegWithMetadata(self::SECRET, orientation: 6));

        $this->assertSame(6, $this->readJpegOrientation($stripped));
    }

    public function test_jpeg_with_default_orientation_gets_no_exif_block_at_all(): void
    {
        $stripped = $this->stripper->stripBytes($this->jpegWithMetadata(self::SECRET, orientation: 1));

        $this->assertStringNotContainsString("Exif\0\0", $stripped);
    }

    public function test_png_text_chunks_are_removed_and_the_image_still_decodes(): void
    {
        $stripped = $this->stripper->stripBytes($this->pngWithMetadata(self::SECRET));

        $this->assertStringNotContainsString(self::SECRET, $stripped);
        $this->assertStringNotContainsString('tEXt', $stripped);
        $this->assertNotFalse(imagecreatefromstring($stripped));
    }

    public function test_webp_exif_chunk_and_flag_are_removed_and_the_file_stays_valid(): void
    {
        $stripped = $this->stripper->stripBytes($this->webpWithMetadata(self::SECRET));

        $this->assertStringNotContainsString(self::SECRET, $stripped);
        $this->assertSame(0, ord($stripped[20]) & 0x08, 'VP8X "has EXIF" flag should be cleared');
        $this->assertSame(strlen($stripped) - 8, unpack('V', substr($stripped, 4, 4))[1], 'RIFF size should match');
        $this->assertNotFalse(imagecreatefromstring($stripped));
    }

    public function test_stripping_an_already_clean_file_changes_nothing(): void
    {
        foreach ([$this->jpegWithMetadata(self::SECRET), $this->pngWithMetadata(self::SECRET), $this->webpWithMetadata(self::SECRET)] as $image) {
            $clean = $this->stripper->stripBytes($image);

            $this->assertSame($clean, $this->stripper->stripBytes($clean));
        }
    }

    public function test_unknown_formats_are_returned_unchanged(): void
    {
        $gif = "GIF89a\x01\x00\x01\x00\x00\x00\x00;";

        $this->assertSame($gif, $this->stripper->stripBytes($gif));
    }

    public function test_a_truncated_jpeg_segment_is_returned_unchanged_rather_than_corrupted(): void
    {
        $truncated = "\xFF\xD8\xFF\xE1\x7F\xFFExif\0\0";

        $this->assertSame($truncated, $this->stripper->stripBytes($truncated));
    }

    public function test_strip_rewrites_the_file_in_place(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'strip');
        file_put_contents($path, $this->jpegWithMetadata(self::SECRET));

        $this->stripper->strip($path);

        $this->assertStringNotContainsString(self::SECRET, (string) file_get_contents($path));
        unlink($path);
    }

    private function readJpegOrientation(string $jpeg): ?int
    {
        $path = tempnam(sys_get_temp_dir(), 'exif');
        file_put_contents($path, $jpeg);
        $exif = @exif_read_data($path);
        unlink($path);

        return $exif['Orientation'] ?? null;
    }
}
