<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(\Illuminate\Http\Request $request): View
    {
        if (! $request->session()->has('gpn_opened')) {
            $request->session()->put('gpn_opened', true);
            $request->session()->flash('gpn_anim', 'loading');
        }

        return view('home', [
            'featured' => Game::tayang()->orderByDesc('rating')->first(),
            'popular' => Game::tayang()
                ->orderByDesc('unduhan')
                ->take(6)
                ->get(),
            'latest' => Game::tayang()->latest('rilis')->take(6)->get(),
        ]);
    }
}
