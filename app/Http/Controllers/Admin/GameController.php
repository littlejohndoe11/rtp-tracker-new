<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GameController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        return view('admin.games.index', [
            'games' => Game::with('provider')
                ->orderBy('name')
                ->paginate(20)
        ]);
    }

    public function create(): \Illuminate\View\View
    {
        return view('admin.games.create', [
            'providers' => Provider::all()
        ]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'max:255'],
            'provider_id' => ['required', 'exists:providers,id'],
            'theoretical_rtp' => ['required', 'numeric', 'between:1,100'],
            'current_rtp' => ['required', 'numeric', 'between:1,100'],
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'category' => ['required', 'string'],
            'is_trending' => ['boolean'],
            'is_popular' => ['boolean'],
            'is_hot' => ['boolean']
        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '_' . Str::slug($request->name) . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('public/games', $filename);
            $validated['image'] = Storage::url($path);
        }

        $validated['slug'] = Str::slug($request->name);
        Game::create($validated);

        return redirect()->route('admin.games.index')
            ->with('success', 'Game created successfully');
    }

    public function edit(Game $game): \Illuminate\View\View
    {
        return view('admin.games.edit', [
            'game' => $game,
            'providers' => Provider::all()
        ]);
    }

    public function update(Request $request, Game $game): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'max:255'],
            'provider_id' => ['required', 'exists:providers,id'],
            'theoretical_rtp' => ['required', 'numeric', 'between:1,100'],
            'current_rtp' => ['required', 'numeric', 'between:1,100'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'is_trending' => ['boolean'],
            'is_popular' => ['boolean'],
            'is_hot' => ['boolean']
        ]);

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($game->image && Storage::disk('public')->exists(str_replace('/storage/', '', $game->image))) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $game->image));
            }

            $image = $request->file('image');
            $filename = time() . '_' . Str::slug($request->name) . '.' . $image->getClientOriginalExtension();

            // Store in the public disk under games directory
            $path = $image->storeAs('games', $filename, 'public');

            $validated['image'] = '/storage/' . $path;
        }

        $validated['is_trending'] = $request->boolean('is_trending');
        $validated['is_popular'] = $request->boolean('is_popular');
        $validated['is_hot'] = $request->boolean('is_hot');
        $validated['slug'] = Str::slug($request->name);

        $game->update($validated);

        return redirect()->route('admin.games.index')
            ->with('success', 'Game updated successfully');
    }
    public function updateImage(Request $request, Game $game): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($game->image && Storage::disk('public')->exists(str_replace('/storage/', '', $game->image))) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $game->image));
            }

            $image = $request->file('image');
            $filename = time() . '_' . Str::slug($game->name) . '.' . $image->getClientOriginalExtension();

            // Store in the public disk under games directory
            $path = $image->storeAs('games', $filename, 'public');

            // Update game with the correct path
            $game->update([
                'image' => '/storage/' . $path
            ]);
        }

        return redirect()->route('admin.games.index')
            ->with('success', 'Game image updated successfully');
    }

    public function destroy(Game $game): \Illuminate\Http\RedirectResponse
    {
        if ($game->image) {
            Storage::delete(str_replace('/storage', 'public', $game->image));
        }

        $game->delete();

        return redirect()->route('admin.games.index')
            ->with('success', 'Game deleted successfully');
    }
}


