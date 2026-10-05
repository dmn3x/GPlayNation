<?php

namespace App\Http\Controllers\Dev;

use App\Enums\GameStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dev\GameStoreRequest;
use App\Http\Requests\Dev\GameUpdateRequest;
use App\Models\Game;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GameController extends Controller
{
    public function index(): View
    {
        return view('developer.games.index', ['games' => auth()->user()->developer->games()->withCount('transaksis')->latest()->get()]);
    }

    public function create(): View
    {
        return view('developer.games.create', ['genres' => Game::GENRES]);
    }

    public function edit(Game $game): View
    {
        abort_unless($game->developer_id === auth()->user()->developer->id, 403);

        return view('developer.games.edit', ['game' => $game->load('screenshots', 'iaps'), 'genres' => Game::GENRES]);
    }

    public function store(GameStoreRequest $request): RedirectResponse // SRS 3.1.2.4
    {
        $data = $request->validated();
        $game = DB::transaction(function () use ($request, $data) {
            $game = auth()->user()->developer->games()->create([
                ...Arr::only($data, ['name', 'description', 'genre']),
                'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(4)),
                'price' => $data['price'],
                'trailer_url' => $data['trailer_url'] ?? null,
                'cover_path' => $request->file('cover')->store('covers', 'public'),
                'file_path' => $request->file('file')->store('installers', 'public'),
                'status' => GameStatus::PendingReview,
            ]);
            foreach ($request->file('screenshots', []) as $i => $shot) {
                $game->screenshots()->create(['path' => $shot->store('screenshots', 'public'), 'sort' => $i]);
            }

            return $game;
        });

        return redirect()->route('dev.games.index')->with('success', 'Game submitted for review.');
    }

    public function update(GameUpdateRequest $request, Game $game): RedirectResponse // SRS 3.1.2.5
    {
        abort_unless($game->developer_id === auth()->user()->developer->id, 403);
        $data = $request->validated();

        $metadataChanged = $game->name !== $data['name']
            || $game->description !== $data['description']
            || $game->genre !== $data['genre']
            || $game->trailer_url !== ($data['trailer_url'] ?? $game->trailer_url)
            || $request->hasFile('cover') || $request->hasFile('file') || $request->hasFile('screenshots');

        DB::transaction(function () use ($request, $game, $data, $metadataChanged) {
            $updates = Arr::only($data, ['name', 'description', 'genre']) + ['price' => $data['price'], 'trailer_url' => $data['trailer_url'] ?? $game->trailer_url];
            if ($request->hasFile('cover')) {
                $oldCover = $game->cover_path;
                $updates['cover_path'] = $request->file('cover')->store('covers', 'public');
            }
            if ($request->hasFile('file')) {
                $oldFile = $game->file_path;
                $updates['file_path'] = $request->file('file')->store('installers', 'public');
            }
            if ($metadataChanged && in_array($game->status, [GameStatus::Published, GameStatus::Rejected])) {
                $updates['status'] = GameStatus::PendingReview;
            }
            $game->update($updates);
            foreach ($request->file('screenshots', []) as $i => $shot) {
                $game->screenshots()->create(['path' => $shot->store('screenshots', 'public'), 'sort' => $game->screenshots()->max('sort') + 1 + $i]);
            }
            if (isset($oldCover) && $oldCover !== $updates['cover_path'] && Storage::disk('public')->exists($oldCover)) {
                Storage::disk('public')->delete($oldCover);
            }
            if (isset($oldFile) && $oldFile !== $updates['file_path'] && Storage::disk('public')->exists($oldFile)) {
                Storage::disk('public')->delete($oldFile);
            }
        });

        $message = $metadataChanged && $game->fresh()->status === GameStatus::PendingReview
            ? 'Changes submitted for re-review.' : 'Game updated.';

        return redirect()->route('dev.games.index')->with('success', $message);
    }

    public function archive(Game $game): RedirectResponse // SRS 3.1.2.6
    {
        abort_unless($game->developer_id === auth()->user()->developer->id, 403);
        abort_unless(in_array($game->status, [GameStatus::Published, GameStatus::Archived]), 403);
        $game->update(['status' => $game->status === GameStatus::Archived ? GameStatus::Published : GameStatus::Archived]);

        return back()->with('success', 'Availability updated.');
    }
}
