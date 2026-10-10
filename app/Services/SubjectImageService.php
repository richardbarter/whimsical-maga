<?php

namespace App\Services;

use App\Models\Subject;
use App\Models\SubjectImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SubjectImageService
{
    public function __construct(private ImageMetadataStripper $metadataStripper) {}

    /**
     * Strip the photo's metadata (e.g. GPS location), store it and record it. If the database
     * insert fails, the stored file is deleted so no orphan is left behind.
     *
     * @param  array<string, mixed>  $data  Validated SubjectImageRequest data, without the file
     */
    public function create(Subject $subject, array $data, UploadedFile $image): SubjectImage
    {
        $this->metadataStripper->strip($image->getRealPath());

        $dimensions = getimagesize($image->getRealPath());
        $filePath = $image->store(SubjectImage::FOLDER);

        try {
            return $subject->images()->create([
                ...$data,
                'file_path' => $filePath,
                'file_size' => filesize($image->getRealPath()),
                'dimensions' => $dimensions ? $dimensions[0].'x'.$dimensions[1] : null,
            ]);
        } catch (Throwable $exception) {
            Storage::delete($filePath);

            throw $exception;
        }
    }
}
