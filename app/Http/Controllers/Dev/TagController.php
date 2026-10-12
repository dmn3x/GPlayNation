<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dev\TagRequest;
use App\Models\Game;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TagController extends Controller
{
    public function index(): View
    {
        return view('developer.tags', [
            'tags' => Tag::withCount('games')->orderBy('name')->get(),
            'games' => auth()->user()->developer->games()->orderBy('name')->get(),
        ]);
    }

    public function store(TagRequest $request): RedirectResponse
    {
        Tag::firstOrCreate(['name' => $request->validated()['name']]);

        return redirect()->route('dev.tags.index')->with('success', 'Tag created.');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        abort_if($tag->games()->exists(), 403);

        $tag->delete();

        return back()->with('success', 'Tag deleted.');
    }

    public function attach(Request $request, Tag $tag): RedirectResponse
    {
        $developer = auth()->user()->developer;
        $validated = $request->validate([
            'game_id' => ['required', 'integer', 'exists:games,id'],
        ]);
        $game = Game::findOrFail($validated['game_id']);
        abort_unless($game->developer_id === $developer->id, 403);

        $game->tags()->syncWithoutDetaching([$tag->id]);

        return back()->with('success', 'Tag attached.');
    }

    public function detach(Request $request, Tag $tag): RedirectResponse
    {
        $developer = auth()->user()->developer;
        $validated = $request->validate([
            'game_id' => ['required', 'integer', 'exists:games,id'],
        ]);
        $game = Game::findOrFail($validated['game_id']);
        abort_unless($game->developer_id === $developer->id, 403);

        $game->tags()->detach($tag->id);

        return back()->with('success', 'Tag detached.');
    }
}
