<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BackgroundRequest;
use App\Models\Background;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BackgroundController extends Controller
{
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
        $file = $request->file('image');
        $path = $file->store('backgrounds', 'public');
        $size = getimagesize($file->getRealPath());

        Background::create([
            ...$request->safe()->except('image'),
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'dimensions' => $size ? $size[0].'x'.$size[1] : null,
        ]);

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
        $data = $request->only(['title', 'alt_text', 'description', 'credit', 'source_url']);

        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($background->file_path);

            $file = $request->file('image');
            $path = $file->store('backgrounds', 'public');
            $size = getimagesize($file->getRealPath());

            $data['file_path'] = $path;
            $data['file_size'] = $file->getSize();
            $data['dimensions'] = $size ? $size[0].'x'.$size[1] : null;
        }

        $background->update($data);

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
