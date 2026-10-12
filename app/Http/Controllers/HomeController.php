<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Promotion;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $hero = Game::published()
            ->with(['tags', 'promotions'])
            ->whereHas('promotions', fn ($q) => $q->where('starts_at', '<=', now())->where('ends_at', '>=', now()))
            ->withCount('downloads')
            ->orderByDesc(Promotion::select('starts_at')
                ->whereColumn('promotions.game_id', 'games.id')
                ->where('starts_at', '<=', now())
                ->where('ends_at', '>=', now())
                ->orderByDesc('starts_at')
                ->limit(1))
            ->orderByDesc('downloads_count')
            ->first()
            ?? Game::published()->with(['tags', 'promotions'])->withCount('downloads')->orderByDesc('downloads_count')->first();

        $popular = Game::published()->with(['tags', 'promotions'])->withCount('downloads')->orderByDesc('downloads_count')->limit(10)->get();
        $latest = Game::published()->with(['tags', 'promotions'])->latest()->limit(10)->get();

        $showIntro = ! session()->has('intro_seen');
        session()->put('intro_seen', true);

        return view('storefront.home', compact('hero', 'popular', 'latest', 'showIntro'));
    }
}
