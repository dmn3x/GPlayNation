<?php

namespace App\Http\Controllers;

use App\Http\Requests\PostRequest;
use App\Models\Game;
use App\Models\Post;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class CommunityController extends Controller
{
    public function index(): View
    {
        $posts = Post::with('user', 'votes')->latest()
            ->when(request('tag'), fn ($q) => $q->whereJsonContains('hashtags', request('tag')))
            ->paginate(20)
            ->withQueryString(); // score via Post::getScoreAttribute()

        $trending = Post::where('created_at', '>=', now()->subDays(7))
            ->get()                 // hydrate models so the hashtags 'array' cast applies
            ->pluck('hashtags')
            ->flatten()
            ->countBy()
            ->sortDesc()
            ->take(8);

        $topGames = Game::published()
            ->withCount(['downloads' => fn ($q) => $q->where('created_at', '>=', now()->startOfMonth())])
            ->orderByDesc('downloads_count')
            ->get()
            ->filter(fn ($game) => $game->downloads_count > 0)
            ->take(5)
            ->values();

        return view('community.index', compact('posts', 'trending', 'topGames'));
    }

    public function store(PostRequest $request): RedirectResponse
    {
        preg_match_all('/#([\p{L}\p{N}_]+)/u', $request->validated('body'), $m);
        $hashtags = array_values(array_unique(array_map(fn ($t) => mb_strtolower($t), $m[1])));

        Post::create([
            'user_id' => $request->user()->id,
            'body' => $request->validated('body'),
            'image_path' => $request->hasFile('image') ? $request->file('image')->store('posts', 'public') : null,
            'hashtags' => $hashtags,
        ]);

        return redirect()->route('community.index')->with('success', 'Posted!');
    }
}
