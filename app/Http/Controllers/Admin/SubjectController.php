<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SubjectSourceType;
use App\Enums\SubjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SubjectRequest;
use App\Models\Subject;
use App\Models\SubjectImage;
use App\Services\SubjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SubjectController extends Controller
{
    public function __construct(private SubjectService $subjectService) {}

    public function index(): Response
    {
        // select() before withCount(): once withCount() sets a select, paginate()'s column list is ignored
        $subjects = Subject::select(['id', 'name', 'slug', 'status', 'updated_at'])
            ->withCount('quotes')
            ->latest('updated_at')
            ->paginate(15);

        return Inertia::render('Admin/Subjects/Index', [
            'subjects' => $subjects,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Subjects/Create', [
            'subjectStatuses' => SubjectStatus::options(),
            'sourceTypes' => SubjectSourceType::options(),
            'imageUrlPrefix' => $this->imageUrlPrefix(),
        ]);
    }

    /**
     * Authorize: Route middleware (EnsureUserIsAdmin) + SubjectRequest::authorize()
     * Validate: SubjectRequest
     */
    public function store(SubjectRequest $request): RedirectResponse
    {
        // Act
        $this->subjectService->create($request->validated());

        // Respond
        return redirect()->route('admin.subjects.index')
            ->with('success', 'Subject created successfully.');
    }

    public function edit(Subject $subject): Response
    {
        $subject->load('sources');

        return Inertia::render('Admin/Subjects/Edit', [
            'subject' => $subject,
            'subjectStatuses' => SubjectStatus::options(),
            'sourceTypes' => SubjectSourceType::options(),
            'imageUrlPrefix' => $this->imageUrlPrefix(),
        ]);
    }

    /**
     * Authorize: Route middleware (EnsureUserIsAdmin) + SubjectRequest::authorize()
     * Validate: SubjectRequest
     */
    public function update(SubjectRequest $request, Subject $subject): RedirectResponse
    {
        // Act
        $this->subjectService->update($subject, $request->validated());

        // Respond
        return redirect()->route('admin.subjects.index')
            ->with('success', 'Subject updated successfully.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        $subject->delete();

        return redirect()->route('admin.subjects.index')
            ->with('success', 'Subject deleted successfully.');
    }

    /**
     * Where uploaded subject images live. The editor keeps only images under this URL, as the
     * server's RichTextSanitizer does, so a pasted image from anywhere else is dropped at once.
     */
    private function imageUrlPrefix(): string
    {
        return Storage::url(SubjectImage::FOLDER.'/');
    }
}
