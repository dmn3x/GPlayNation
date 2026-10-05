<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dev\PromotionRequest;
use App\Models\Game;
use App\Models\Promotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PromotionController extends Controller
{
    public function index(): View
    {
        $developer = auth()->user()->developer;

        $promotions = Promotion::whereHas('game', fn ($q) => $q->where('developer_id', $developer->id))
            ->with('game')
            ->latest('id')
            ->get()
            ->map(function (Promotion $promotion) {
                $promotion->status_label = $promotion->ends_at < now()
                    ? 'ended'
                    : ($promotion->starts_at > now() ? 'scheduled' : 'active');

                return $promotion;
            });

        return view('developer.promotion', [
            'promotions' => $promotions,
            'games' => $developer->games()->published()->orderBy('name')->get(),
        ]);
    }

    public function store(PromotionRequest $request): RedirectResponse
    {
        $game = Game::findOrFail($request->validated()['game_id']);
        abort_unless($game->developer_id === auth()->user()->developer->id, 403);

        $game->promotions()->create($request->validated());

        return redirect()->route('dev.promotion.index')->with('success', 'Promotion created.');
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        abort_unless($promotion->game->developer_id === auth()->user()->developer->id, 403);
        abort_unless($promotion->starts_at > now(), 403);

        $promotion->delete();

        return back()->with('success', 'Promotion deleted.');
    }
}
