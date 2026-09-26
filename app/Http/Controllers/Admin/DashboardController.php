<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Background;
use App\Models\Category;
use App\Models\Quote;
use App\Models\Tag;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'quotesCount' => Quote::count(),
            'backgroundsCount' => Background::count(),
            'tagsCount' => Tag::count(),
            'categoriesCount' => Category::count(),
        ]);
    }
}
