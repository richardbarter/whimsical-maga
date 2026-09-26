<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SavedContextRequest;
use App\Http\Requests\Admin\SavedContextSearchRequest;
use App\Models\SavedContext;
use App\Models\Tag;
use App\Services\SavedContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SavedContextController extends Controller
{
    public function __construct(private SavedContextService $savedContextService) {}

    public function index(): Response
    {
        $savedContexts = SavedContext::with('tags')->latest()->paginate(15);

        return Inertia::render('Admin/SavedContexts/Index', [
            'savedContexts' => $savedContexts,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/SavedContexts/Create', [
            'tags' => Tag::orderBy('name')->get(['id', 'name', 'slug']),
        ]);
    }

    /**
     * Authorize: Route middleware (EnsureUserIsAdmin) + SavedContextRequest::authorize()
     * Validate: SavedContextRequest
     */
    public function store(SavedContextRequest $request): RedirectResponse|JsonResponse
    {
        // Act
        $savedContext = $this->savedContextService->create($request->validated());

        // Respond — JSON for the quote form's "Save context" dialog, a redirect for the admin page
        if ($request->wantsJson()) {
            return response()->json($savedContext->load('tags'), 201);
        }

        return redirect()->route('admin.saved-contexts.index')
            ->with('success', 'Saved context created successfully.');
    }

    public function edit(SavedContext $savedContext): Response
    {
        $savedContext->load('tags');

        return Inertia::render('Admin/SavedContexts/Edit', [
            'savedContext' => $savedContext,
            'tags' => Tag::orderBy('name')->get(['id', 'name', 'slug']),
        ]);
    }

    /**
     * Authorize: Route middleware (EnsureUserIsAdmin) + SavedContextRequest::authorize()
     * Validate: SavedContextRequest
     */
    public function update(SavedContextRequest $request, SavedContext $savedContext): RedirectResponse
    {
        // Act
        $this->savedContextService->update($savedContext, $request->validated());

        // Respond
        return redirect()->route('admin.saved-contexts.index')
            ->with('success', 'Saved context updated successfully.');
    }

    public function destroy(SavedContext $savedContext): RedirectResponse
    {
        $savedContext->delete();

        return redirect()->route('admin.saved-contexts.index')
            ->with('success', 'Saved context deleted successfully.');
    }

    public function search(SavedContextSearchRequest $request): JsonResponse
    {
        $savedContexts = SavedContext::with('tags')
            ->matchingAllTerms($request->terms())
            ->orderBy('subject')
            ->limit(20)
            ->get(['id', 'subject', 'body']);

        return response()->json($savedContexts);
    }
}
