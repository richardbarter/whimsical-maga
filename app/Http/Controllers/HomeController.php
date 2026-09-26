<?php

namespace App\Http\Controllers;

use App\Models\Background;
use App\Services\QuoteFeedService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(private QuoteFeedService $quoteFeed) {}

    /**
     * The first visit gets batch 1 and a fresh seed; the page then requests later batches
     * with partial reloads (?seed=…&page=…), which Inertia appends to the quotes prop.
     *
     * The query values are coerced rather than validated: this is a public page, and a
     * mangled URL should still show quotes instead of a validation error.
     */
    public function index(Request $request): Response
    {
        $seed = $request->integer('seed');
        $seed = $seed >= 1 && $seed <= QuoteFeedService::MAX_SEED ? $seed : $this->quoteFeed->newSeed();
        $page = max(1, $request->integer('page', 1));

        $batch = $this->quoteFeed->batch($seed, $page);

        return Inertia::render('Public/Home', [
            'quotes' => Inertia::merge($batch['quotes'])->matchOn('id'),
            'quoteFeed' => [
                'seed' => $seed,
                'page' => $page,
                'hasMore' => $batch['hasMore'],
            ],
            'backgrounds' => fn () => Background::query()->get(['id', 'file_path', 'alt_text', 'credit']),
        ]);
    }
}
