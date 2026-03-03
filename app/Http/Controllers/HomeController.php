<?php

namespace App\Http\Controllers;

use App\Models\Background;
use App\Models\Quote;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $quotes = Quote::with('speaker')
            ->published()
            ->orderByDesc('is_featured')
            ->orderByRaw('RANDOM()')
            ->get(['id', 'text', 'context', 'occurred_at', 'is_featured', 'speaker_id']);

        $backgrounds = Background::query()
            ->get(['id', 'file_path', 'alt_text', 'credit']);

        return Inertia::render('Public/Home', [
            'quotes' => $quotes,
            'backgrounds' => $backgrounds,
        ]);
    }
}
