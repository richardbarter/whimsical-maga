<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BackgroundRequest;
use App\Models\Background;
use App\Services\BackgroundService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BackgroundController extends Controller
{
    public function __construct(private BackgroundService $backgroundService) {}

    public function index(): Response
    {
        $backgrounds = Background::latest()->paginate(15);

        return Inertia::render('Admin/Backgrounds/Index', [
            'backgrounds' => $backgrounds,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Backgrounds/Create');
    }

    /**
     * Authorize: Route middleware (EnsureUserIsAdmin) + BackgroundRequest::authorize()
     * Validate: BackgroundRequest
     */
    public function store(BackgroundRequest $request): RedirectResponse
    {
        // Act
        $this->backgroundService->create($request->validated(), $request->file('image'));

        // Respond
        return redirect()->route('admin.backgrounds.index')
            ->with('success', 'Background added successfully.');
    }

    public function edit(Background $background): Response
    {
        return Inertia::render('Admin/Backgrounds/Edit', [
            'background' => $background,
        ]);
    }

    /**
     * Authorize: Route middleware (EnsureUserIsAdmin) + BackgroundRequest::authorize()
     * Validate: BackgroundRequest
     */
    public function update(BackgroundRequest $request, Background $background): RedirectResponse
    {
        // Act
        $this->backgroundService->update($background, $request->validated(), $request->file('image'));

        // Respond
        return redirect()->route('admin.backgrounds.index')
            ->with('success', 'Background updated successfully.');
    }

    public function destroy(Background $background): RedirectResponse
    {
        $background->delete();

        return redirect()->route('admin.backgrounds.index')
            ->with('success', 'Background deleted successfully.');
    }
}
