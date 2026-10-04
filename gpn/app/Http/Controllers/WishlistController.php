<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Wishlist;
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

        Wishlist::create(['user_id' => auth()->id(), 'game_id' => $game->id]);

        return back()->with('success', 'Added to wishlist');
    }
}
