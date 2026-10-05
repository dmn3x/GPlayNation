<?php

namespace App\Http\Controllers;

use App\Enums\GameStatus;
use App\Http\Requests\StoreFilterRequest;
use App\Models\Game;
use App\Models\Library;
use App\Models\Tag;
use App\Models\Wishlist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class StoreController extends Controller
{
    public function index(StoreFilterRequest $request): View
    {
        $filters = $request->validated();

        $games = Game::published()
            ->with(['tags', 'promotions'])
            ->when($filters['q'] ?? null, fn (Builder $query, string $q) => $query->where('name', 'like', "%{$q}%"))
            ->when($filters['genre'] ?? null, fn (Builder $query, string $genre) => $query->where('genre', $genre))
            ->when($filters['tag'] ?? null, fn (Builder $query, string $tag) => $query->whereHas('tags', fn (Builder $tags) => $tags->where('name', $tag)))
            ->when($filters['price'] ?? null, fn (Builder $query, string $band) => $this->applyPriceBand($query, $band))
            ->when(($filters['sort'] ?? 'newest') === 'newest', fn (Builder $query) => $query->latest())
            ->when(($filters['sort'] ?? null) === 'price_asc', fn (Builder $query) => $query->orderBy('price'))
            ->when(($filters['sort'] ?? null) === 'price_desc', fn (Builder $query) => $query->orderByDesc('price'))
            ->when(($filters['sort'] ?? null) === 'name', fn (Builder $query) => $query->orderBy('name'))
            ->paginate(12)
            ->withQueryString();

        return view('storefront.store', [
            'games' => $games,
            'genres' => Game::GENRES,
            'tags' => Tag::orderBy('name')->get(),
        ]);
    }

    public function show(Game $game): View
    {
        $viewer = auth()->user();

        $isOwner = $viewer !== null && (
            ($viewer->isDeveloper() && $game->developer?->user_id === $viewer->id)
            || $viewer->isAdmin()
        );

        abort_unless($game->status === GameStatus::Published || $isOwner, 404);

        $game->load(['screenshots', 'tags', 'developer']);
        $iaps = $game->iaps()->active()->get();

        $inLibrary = $viewer !== null
            && Library::where('user_id', $viewer->id)->where('game_id', $game->id)->exists();

        $wishlisted = $viewer !== null
            && Wishlist::where('user_id', $viewer->id)->where('game_id', $game->id)->exists();

        return view('storefront.game', compact('game', 'iaps', 'inLibrary', 'wishlisted'));
    }

    private function applyPriceBand(Builder $query, string $band): void
    {
        match ($band) {
            'free' => $query->where('price', '=', 0),
            'under50' => $query->where('price', '>', 0)->where('price', '<', 50000),
            '50to150' => $query->where('price', '>=', 50000)->where('price', '<=', 150000),
            'above150' => $query->where('price', '>', 150000),
        };
    }
}
