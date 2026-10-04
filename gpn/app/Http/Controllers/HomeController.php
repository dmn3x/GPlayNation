<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $hero = Game::published()
            ->whereHas('promotions', fn ($q) => $q->where('starts_at', '<=', now())->where('ends_at', '>=', now()))
            ->withCount('downloads')
            ->orderByDesc('downloads_count')
            ->first()
            ?? Game::published()->withCount('downloads')->orderByDesc('downloads_count')->first();

        $popular = Game::published()->withCount('downloads')->orderByDesc('downloads_count')->limit(10)->get();
        $latest = Game::published()->latest()->limit(10)->get();

        $showIntro = ! session()->has('intro_seen');
        session()->put('intro_seen', true);

        return view('storefront.home', compact('hero', 'popular', 'latest', 'showIntro'));
    }
}
