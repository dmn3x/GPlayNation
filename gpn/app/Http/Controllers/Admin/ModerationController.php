<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GameStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ModerateRequest;
use App\Models\Game;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ModerationController extends Controller
{
    public function index(): View // SRS 3.1.4.1
    {
        return view('admin.moderation.index', [
            'games' => Game::where('status', GameStatus::PendingReview)
                ->with('developer')
                ->oldest()
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function show(Game $game): View
    {
        $game->load('developer', 'screenshots', 'iaps');
        $fileSize = $game->file_path && Storage::disk('public')->exists($game->file_path)
            ? Storage::disk('public')->size($game->file_path)
            : null;

        return view('admin.moderation.show', ['game' => $game, 'fileSize' => $fileSize]);
    }

    public function update(ModerateRequest $request, Game $game): RedirectResponse
    {
        $data = $request->validated();

        if ($data['action'] === 'approve') {
            $game->update(['status' => GameStatus::Published, 'rejection_feedback' => null]);

            return redirect()->route('admin.moderation.index')->with('success', 'Game published.');
        }

        $game->update(['status' => GameStatus::Rejected, 'rejection_feedback' => $data['feedback']]);

        return redirect()->route('admin.moderation.index')->with('success', 'Game rejected with feedback.');
    }
}
