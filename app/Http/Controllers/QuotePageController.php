<?php

namespace App\Http\Controllers;

use App\Enums\QuoteStatus;
use App\Enums\QuoteType;
use App\Enums\SourceType;
use App\Enums\SubjectSourceType;
use App\Models\Quote;
use App\Services\QuotePageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The public quote page. Named to avoid clashing with Admin\QuoteController in routes/web.php.
 */
class QuotePageController extends Controller
{
    public function __construct(private QuotePageService $quotePage) {}

    /**
     * Authorize: Route middleware (can:view,quote → QuotePolicy::view)
     *
     * Quotes are found by ID; the slug is cosmetic and changes when the text is edited,
     * so a missing or outdated slug is a 301 to the current URL and old links keep working.
     */
    public function show(Request $request, Quote $quote, ?string $slug = null): Response|RedirectResponse
    {
        if ($slug !== $quote->slug) {
            return redirect()->route('quotes.show', [$quote, $quote->slug], 301);
        }

        // Act
        $this->quotePage->load($quote);

        // Respond
        return Inertia::render('Public/Quotes/Show', [
            'quote' => $quote,
            'subjects' => $this->quotePage->subjects($quote, $request->user()),
            'isPreview' => $quote->status !== QuoteStatus::Published,
            'quoteTypes' => QuoteType::options(),
            'sourceTypes' => SourceType::options(),
            'subjectSourceTypes' => SubjectSourceType::options(),
        ])->withViewData('meta', $this->quotePage->meta($quote));
    }
}
