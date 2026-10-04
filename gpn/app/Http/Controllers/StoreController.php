<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * SRS 3.1.2 — Modul Manajemen Game (sisi pengguna).
 */

class StoreController extends Controller
{
    /** SRS 3.1.2.1 Mencari Game + F-03 Pencarian dan Filter */
    public function index(Request $request): View
    {
        $query = Game::tayang()->with('developer');

        if ($request->filled('q')) {
            $term = $request->string('q')->toString();
            $query->where(fn ($q) => $q
                ->where('game_nama', 'like', "%{$term}%")
                ->orWhere('deskripsi', 'like', "%{$term}%")
                ->orWhere('game_genre', 'like', "%{$term}%"));
        }

        foreach (['genre' => 'game_genre', 'kategori' => 'kategori'] as $param => $column) {
            if ($request->filled($param)) {
                $query->where($column, $request->string($param)->toString());
            }
        }

        // F-03 filter by price band.
        if ($request->filled('harga')) {
            match ($request->string('harga')->toString()) {
                'gratis' => $query->where('game_harga', 0),
                'murah' => $query->whereBetween('game_harga', [1, 100000]),
                'menengah' => $query->whereBetween('game_harga', [100001, 300000]),
                'mahal' => $query->where('game_harga', '>', 300000),
                default => null,
            };
        }

        match ($request->string('sort')->toString()) {
            'terlaris' => $query->orderByDesc('unduhan'),
            'terbaru' => $query->orderByDesc('rilis'),
            'rating' => $query->orderByDesc('rating'),
            default => $query->latest('rilis'),
        };

        return view('store.index', [
            'games' => $query->paginate(12)->withQueryString(),
            'genres' => Game::tayang()->distinct()->orderBy('game_genre')->pluck('game_genre'),
            'kategori' => Game::tayang()->distinct()->orderBy('kategori')->pluck('kategori'),
        ]);
    }

    /** SRS 3.1.2.2 Melihat Informasi Game */
    public function show(Game $game): View
    {
        abort_unless($game->game_status === 'published', 404);

        return view('store.show', [
            'game' => $game->load('developer.user', 'iaps'),
            'inWishlist' => auth()->check() && auth()->user()->wishlist()->where('game_id', $game->id)->exists(),
        ]);
    }

    /** SRS 3.1.2.3 Mengunduh Game */
    public function download(Game $game)
    {
        abort_unless($game->game_status === 'published', 404);

        if (! auth()->check()) {
            return redirect()->route('login');
        }

        if ($game->game_harga > 0 && ! auth()->user()->sudahBeli($game)) {
            return redirect()->route('store.show', $game)->withErrors([
                'download' => 'Game ini berbayar dan belum Anda beli.',
            ]);
        }

        $game->increment('unduhan');

        return response()->streamDownload(function () {
            echo "GPlayNation installer placeholder for demo purposes.\n";
        }, Str::slug($game->game_nama).'.txt');
    }

    /** SRS 4.2 — Profile: wishlist */
    public function toggleWishlist(Game $game)
    {
        $user = request()->user();
        $existing = Wishlist::where('pengguna_id', $user->id)->where('game_id', $game->id)->first();

        $existing ? $existing->delete() : Wishlist::create([
            'pengguna_id' => $user->id,
            'game_id' => $game->id,
        ]);

        return back()->with('status', $existing ? 'Dihapus dari wishlist.' : 'Ditambahkan ke wishlist.');
    }
}
