<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dev\IapRequest;
use App\Models\Game;
use App\Models\Iap;
use Illuminate\Http\RedirectResponse;

class IapController extends Controller
{
    public function store(IapRequest $request, Game $game): RedirectResponse // SRS 3.1.2.5 IAP
    {
        abort_unless($game->developer_id === auth()->user()->developer->id, 403);

        $game->iaps()->create($request->validated());

        return back()->with('success', 'IAP added.');
    }

    public function update(IapRequest $request, Iap $iap): RedirectResponse
    {
        abort_unless($iap->game->developer_id === auth()->user()->developer->id, 403);

        $iap->update($request->validated());

        return back()->with('success', 'IAP updated.');
    }
}
