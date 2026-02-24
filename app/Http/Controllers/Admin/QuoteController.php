<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\QuoteRequest;
use App\Models\Category;
use App\Models\Quote;
use App\Models\Speaker;
use App\Models\Tag;
use App\Services\QuoteService;
use App\Services\SpeakerService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class QuoteController extends Controller
{
    public function __construct(
        private SpeakerService $speakerService,
        private QuoteService $quoteService,
    ) {}

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
        return Inertia::render('Admin/Quotes/Create', [
            'tags' => Tag::orderBy('name')->get(['id', 'name', 'slug']),
            'categories' => Category::orderBy('name')->get(['id', 'name', 'slug', 'color']),
            'speakers' => Speaker::with('aliases')->orderBy('name')->get(['id', 'name', 'slug']),
        ]);
    }

    /**
     * Authorize: Route middleware (EnsureUserIsAdmin) + QuoteRequest::authorize()
     * Validate: QuoteRequest
     */
    public function store(QuoteRequest $request): RedirectResponse
    {
        // Act
        $speakerId = $this->speakerService->resolveFromName($request->input('speaker'));
        $slug = Quote::generateSlug($request->input('text'));

        try {
            $quote = Quote::create([
                ...$request->safe()->only(['text', 'context', 'location', 'occurred_at', 'is_verified', 'is_featured', 'status', 'quote_type', 'quote_type_note']),
                'speaker_id' => $speakerId,
                'slug' => $slug,
                'user_id' => $request->user()->id,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Race condition: two requests generated the same slug at the same moment.
            // The DB unique constraint caught it. Append a timestamp and retry once.
            $quote = Quote::create([
                ...$request->safe()->only(['text', 'context', 'location', 'occurred_at', 'is_verified', 'is_featured', 'status', 'quote_type', 'quote_type_note']),
                'speaker_id' => $speakerId,
                'slug' => $slug.'-'.time(),
                'user_id' => $request->user()->id,
            ]);
        }

        $this->quoteService->syncRelations($quote, $request->input('tags'), $request->input('categories'), $request->input('sources'));

        // Respond
        return redirect()->route('admin.quotes.index')
            ->with('success', 'Quote created successfully.');
    }

    public function edit(Quote $quote): Response
    {
        $quote->load(['speaker.aliases', 'tags', 'categories', 'sources']);

        return Inertia::render('Admin/Quotes/Edit', [
            'quote' => $quote,
            'tags' => Tag::orderBy('name')->get(['id', 'name', 'slug']),
            'categories' => Category::orderBy('name')->get(['id', 'name', 'slug', 'color']),
            'speakers' => Speaker::with('aliases')->orderBy('name')->get(['id', 'name', 'slug']),
        ]);
    }

    /**
     * Authorize: Route middleware (EnsureUserIsAdmin) + QuoteRequest::authorize()
     * Validate: QuoteRequest
     */
    public function update(QuoteRequest $request, Quote $quote): RedirectResponse
    {
        // Act
        $speakerId = $this->speakerService->resolveFromName($request->input('speaker'));

        $slug = $quote->slug;
        if ($request->input('text') !== $quote->text) {
            $slug = Quote::generateSlug($request->input('text'), $quote->id);
        }

        try {
            $quote->update([
                ...$request->safe()->only(['text', 'context', 'location', 'occurred_at', 'is_verified', 'is_featured', 'status', 'quote_type', 'quote_type_note']),
                'speaker_id' => $speakerId,
                'slug' => $slug,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Race condition: two requests tried to update to the same slug simultaneously.
            $quote->update([
                ...$request->safe()->only(['text', 'context', 'location', 'occurred_at', 'is_verified', 'is_featured', 'status', 'quote_type', 'quote_type_note']),
                'speaker_id' => $speakerId,
                'slug' => $slug.'-'.time(),
            ]);
        }

        $this->quoteService->syncRelations($quote, $request->input('tags'), $request->input('categories'), $request->input('sources'));

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
}
