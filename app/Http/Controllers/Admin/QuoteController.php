<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QuoteStatus;
use App\Enums\QuoteType;
use App\Enums\SourceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\QuoteRequest;
use App\Models\Category;
use App\Models\Quote;
use App\Models\Speaker;
use App\Models\Tag;
use App\Services\QuoteService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class QuoteController extends Controller
{
    public function __construct(private QuoteService $quoteService) {}

    public function index(): Response
    {
        $quotes = Quote::with('speaker')
            ->latest()
            ->paginate(15);

        return Inertia::render('Admin/Quotes/Index', [
            'quotes' => $quotes,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Quotes/Create', $this->formOptions());
    }

    /**
     * Authorize: Route middleware (EnsureUserIsAdmin) + QuoteRequest::authorize()
     * Validate: QuoteRequest
     */
    public function store(QuoteRequest $request): RedirectResponse
    {
        // Act
        $this->quoteService->create($request->validated(), $request->user());

        // Respond
        return redirect()->route('admin.quotes.index')
            ->with('success', 'Quote created successfully.');
    }

    public function edit(Quote $quote): Response
    {
        $quote->load(['speaker.aliases', 'tags', 'categories', 'sources']);

        return Inertia::render('Admin/Quotes/Edit', [
            'quote' => $quote,
            ...$this->formOptions(),
        ]);
    }

    /**
     * Authorize: Route middleware (EnsureUserIsAdmin) + QuoteRequest::authorize()
     * Validate: QuoteRequest
     */
    public function update(QuoteRequest $request, Quote $quote): RedirectResponse
    {
        // Act
        $this->quoteService->update($quote, $request->validated());

        // Respond
        return redirect()->route('admin.quotes.index')
            ->with('success', 'Quote updated successfully.');
    }

    public function destroy(Quote $quote): RedirectResponse
    {
        $quote->delete();

        return redirect()->route('admin.quotes.index')
            ->with('success', 'Quote deleted successfully.');
    }

    public function toggleVerified(Quote $quote): RedirectResponse
    {
        $quote->update(['is_verified' => ! $quote->is_verified]);

        return back();
    }

    public function toggleFeature(Quote $quote): RedirectResponse
    {
        $quote->update(['is_featured' => ! $quote->is_featured]);

        return back();
    }

    /**
     * Lookup data shared by the create and edit forms.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'tags' => Tag::orderBy('name')->get(['id', 'name', 'slug']),
            'categories' => Category::orderBy('name')->get(['id', 'name', 'slug', 'color']),
            'speakers' => Speaker::with('aliases')->orderBy('name')->get(['id', 'name', 'slug']),
            'quoteTypes' => QuoteType::options(),
            'quoteStatuses' => QuoteStatus::options(),
            'sourceTypes' => SourceType::options(),
        ];
    }
}
