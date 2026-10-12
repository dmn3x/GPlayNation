<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Wishlist;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;

class WishlistController extends Controller
{
    public function toggle(Game $game): RedirectResponse
    {
        $existing = Wishlist::where('user_id', auth()->id())->where('game_id', $game->id)->first();

        if ($existing) {
            $existing->delete();

            return back()->with('success', 'Removed from wishlist');
        }

        try {
            Wishlist::create(['user_id' => auth()->id(), 'game_id' => $game->id]);
        } catch (QueryException) {
            // Unique (user_id, game_id) violation — a concurrent request already wishlisted.
        }

        return back()->with('success', 'Added to wishlist');
    }
}
