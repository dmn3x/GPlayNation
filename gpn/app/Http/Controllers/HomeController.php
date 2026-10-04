<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $hero = Game::published()
            ->with('tags')
            ->whereHas('promotions', fn ($q) => $q->where('starts_at', '<=', now())->where('ends_at', '>=', now()))
            ->withCount('downloads')
            ->orderByDesc('downloads_count')
            ->first()
            ?? Game::published()->with('tags')->withCount('downloads')->orderByDesc('downloads_count')->first();

        $popular = Game::published()->with('tags')->withCount('downloads')->orderByDesc('downloads_count')->limit(10)->get();
        $latest = Game::published()->with('tags')->latest()->limit(10)->get();

        $showIntro = ! session()->has('intro_seen');
        session()->put('intro_seen', true);

        return view('storefront.home', compact('hero', 'popular', 'latest', 'showIntro'));
    }
}
