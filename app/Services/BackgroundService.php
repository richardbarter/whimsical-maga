<?php

namespace App\Services;

use App\Models\Background;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Throwable;

class BackgroundService
{
    public function __construct(private ImageMetadataStripper $metadataStripper) {}

    /**
     * @param  array<string, mixed>  $data  Validated BackgroundRequest data
     */
    public function create(array $data, UploadedFile $image): Background
    {
        $imageAttributes = $this->storeImage($image);

        try {
            return Background::create([...Arr::except($data, 'image'), ...$imageAttributes]);
        } catch (Throwable $exception) {
            Storage::delete($imageAttributes['file_path']);

            throw $exception;
        }
    }

    /**
     * Update the details and, optionally, replace the image.
     *
     * The new file is stored and the row updated before the old file is deleted, so a
     * failure part-way through never leaves the background pointing at a missing file.
     *
     * @param  array<string, mixed>  $data  Validated BackgroundRequest data
     */
    public function update(Background $background, array $data, ?UploadedFile $image): Background
    {
        $attributes = Arr::except($data, 'image');
        $previousFilePath = $background->file_path;

        if ($image === null) {
            $background->update($attributes);

            return $background;
        }

        $imageAttributes = $this->storeImage($image);

        try {
            $background->update([...$attributes, ...$imageAttributes]);
        } catch (Throwable $exception) {
            Storage::delete($imageAttributes['file_path']);

            throw $exception;
        }

        Storage::delete($previousFilePath);

        return $background;
    }

    /**
     * Strip metadata from the upload, then store it.
     *
     * @return array{file_path: string, file_size: int, dimensions: string|null}
     */
    private function storeImage(UploadedFile $image): array
    {
        $this->metadataStripper->strip($image->getRealPath());

        $dimensions = getimagesize($image->getRealPath());

        return [
            'file_path' => $image->store('backgrounds'),
            'file_size' => filesize($image->getRealPath()),
            'dimensions' => $dimensions ? $dimensions[0].'x'.$dimensions[1] : null,
        ];
    }
}
