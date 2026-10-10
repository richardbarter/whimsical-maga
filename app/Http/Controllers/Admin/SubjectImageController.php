<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SubjectImageRequest;
use App\Models\Subject;
use App\Models\SubjectImage;
use App\Services\SubjectImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Image uploads from the full details editor, which inserts the returned URL into the text.
 */
class SubjectImageController extends Controller
{
    public function __construct(private SubjectImageService $subjectImageService) {}

    /**
     * Authorize: Route middleware (EnsureUserIsAdmin) + SubjectImageRequest::authorize()
     * Validate: SubjectImageRequest
     */
    public function store(SubjectImageRequest $request, Subject $subject): JsonResponse
    {
        // Act
        $image = $this->subjectImageService->create(
            $subject,
            $request->safe()->except('image'),
            $request->file('image'),
        );

        // Respond
        [$width, $height] = $image->dimensions ? array_map('intval', explode('x', $image->dimensions)) : [null, null];

        return response()->json([
            'id' => $image->id,
            'url' => $image->url,
            'width' => $width,
            'height' => $height,
        ], 201);
    }

    /**
     * Soft delete: the file stays, since saved full details (or an undo) may still show it.
     */
    public function destroy(Subject $subject, SubjectImage $image): Response
    {
        $image->delete();

        return response()->noContent();
    }
}
