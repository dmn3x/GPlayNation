<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SalesController extends Controller
{
    public function index(Request $request, ReportService $reports): View
    {
        $developer = auth()->user()->developer;

        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'game_id' => ['nullable', 'integer', Rule::exists('games', 'id')->where('developer_id', $developer->id)],
        ]);

        $month = Carbon::parse(($validated['month'] ?? now()->format('Y-m')) . '-01');
        $gameId = $validated['game_id'] ?? null;

        return view('developer.sales', [
            'month' => $month->format('Y-m'),
            'gameId' => $gameId,
            'games' => $developer->games()->orderBy('name')->get(),
            'kpis' => $reports->developerKpis($developer, $month, $gameId),
            'series' => $reports->developerSeries($developer, $month, $gameId),
            'rows' => $reports->developerRows($developer, $month, $gameId),
        ]);
    }
}
