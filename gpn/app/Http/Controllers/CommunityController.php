<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Iap;
use App\Models\Post;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * SRS 3.1.2 — Modul Manajemen Game (sisi pengguna).
 */

class CommunityController extends Controller
{
    public function index(): View
    {
        $sort = request()->string('sort')->toString();

        $posts = Post::with(['pengguna', 'game'])
            ->when($sort === 'top', fn ($q) => $q->orderByDesc('skor'),
                $sort === 'rising' ? fn ($q) => $q->orderByDesc('created_at') : null)
            ->latest('created_at')
            ->paginate(10);

        $trending = Post::query()
            ->select('isi')
            ->where('isi', 'like', '%#%')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($p) => preg_match_all('/#[A-Za-z0-9_]+/', $p->isi, $m) ? $m[0] : [])
            ->flatten()
            ->countBy()
            ->sortDesc()
            ->take(5);

        return view('community.index', [
            'posts' => $posts,
            'trending' => $trending,
            'popularGames' => Game::tayang()->orderByDesc('unduhan')->take(4)->get(),
        ]);
    }

    /** SRS 4.3 — create a post */
    public function store(Request $request)
    {
        $data = $request->validate([
            'isi' => ['required', 'string', 'max:5000'],
            'game_id' => ['nullable', 'exists:games,id'],
        ]);

        Post::create([
            'pengguna_id' => $request->user()->id,
            'game_id' => $data['game_id'] ?? null,
            'isi' => $data['isi'],
            'jenis' => 'diskusi',
        ]);

        return back()->with('status', 'Postinganmu telah dipublikasikan.');
    }

    public function vote(Post $post)
    {
        abort_if($post->pengguna_id === request()->user()->id, 403);

        $vote = $post->votes()->firstOrNew(['pengguna_id' => request()->user()->id]);
        $vote->naik = ! $vote->naik;
        $vote->save();

        $post->update([
            'skor' => $post->votes()->where('naik', true)->count()
                - $post->votes()->where('naik', false)->count(),
        ]);

        return back();
    }
}
