<?php

namespace App\Http\Controllers;

use App\Enums\GameStatus;
use App\Enums\LibrarySource;
use App\Models\Download;
use App\Models\Game;
use App\Models\Library;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class DownloadController extends Controller
{
    public function show(Game $game): StreamedResponse|RedirectResponse
    {
        $user = auth()->user();

        $owned = Library::where('user_id', $user->id)->where('game_id', $game->id)->exists();

        // Owners keep access even if the game later leaves the published state.
        // Everyone else may only touch published games.
        abort_unless($owned || $game->status === GameStatus::Published, 404);

        if (! $owned && ! $game->isFree()) {
            return redirect()->route('store.show', $game->slug)
                ->with('error', 'Purchase this game to download it.');
        }

        if (! $owned) {
            try {
                Library::create([
                    'user_id' => $user->id,
                    'game_id' => $game->id,
                    'source' => LibrarySource::Free,
                ]);
            } catch (Throwable) {
                // Unique (user_id, game_id) violation — another request already claimed it.
            }
        }

        Download::create(['user_id' => $user->id, 'game_id' => $game->id]);

        return Storage::disk('public')->download($game->file_path, $game->slug.'-installer.zip');
    }
}
