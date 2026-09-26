<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SavedContextRequest;
use App\Models\SavedContext;
use App\Models\Tag;
use App\Services\SavedContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    public function store(SavedContextRequest $request): RedirectResponse
    {
        // Act
        $savedContext = SavedContext::create($request->safe()->only(['subject', 'body']));
        $this->savedContextService->syncTags($savedContext, $request->input('tags'));

        // Respond
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
        $savedContext->update($request->safe()->only(['subject', 'body']));
        $this->savedContextService->syncTags($savedContext, $request->input('tags'));

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

    public function search(Request $request): JsonResponse
    {
        $terms = $request->q
            ? array_filter(array_map('trim', explode(',', $request->q)))
            : [];

        $savedContexts = SavedContext::with('tags')
            ->when($terms, function ($query) use ($terms) {
                foreach ($terms as $term) {
                    $query->where(function ($q) use ($term) {
                        $q->whereRaw('LOWER(subject) LIKE ?', ['%'.strtolower($term).'%'])
                            ->orWhereHas('tags', fn ($t) => $t->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower($term).'%']));
                    });
                }
            })
            ->orderBy('subject')
            ->limit(20)
            ->get(['id', 'subject', 'body']);

        return response()->json($savedContexts);
    }
}
