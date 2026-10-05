<x-portal-layout title="Games" section="developer">
    <div class="flex items-center justify-between">
        <h1 class="screen-title">Games</h1>
        <a href="{{ route('dev.games.create') }}" class="btn-primary">Upload game</a>
    </div>

    <div class="card mt-6 overflow-hidden">
        @if ($games->isEmpty())
            <div class="p-10 text-center">
                <p class="text-lg font-medium">No games yet</p>
                <p class="mt-1 text-subtle">Upload your first game to start selling on GPlayNation.</p>
                <a href="{{ route('dev.games.create') }}" class="btn-primary mt-4">Upload game</a>
            </div>
        @else
            <table class="w-full text-left text-[15px]">
                <thead>
                    <tr class="border-b border-border text-sm text-faint">
                        <th class="px-4 py-3 font-medium">Game</th>
                        <th class="px-4 py-3 font-medium">Price</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Sales</th>
                        <th class="px-4 py-3 font-medium"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($games as $game)
                        <tr class="border-b border-border align-top last:border-0">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <img src="{{ Storage::url($game->cover_path) }}" alt="" class="h-12 w-20 rounded-lg object-cover">
                                    <span class="font-medium">{{ $game->name }}</span>
                                </div>
                                @if ($game->status === \App\Enums\GameStatus::Rejected && $game->rejection_feedback)
                                    <p class="mt-2 text-sm text-danger">{{ $game->rejection_feedback }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ \App\Support\Money::format($game->price) }}</td>
                            <td class="px-4 py-3">
                                <span @class([
                                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-sm font-medium',
                                    'bg-success/15 text-success' => $game->status === \App\Enums\GameStatus::Published,
                                    'bg-amber-400/15 text-amber-400' => $game->status === \App\Enums\GameStatus::PendingReview,
                                    'bg-danger/15 text-danger' => $game->status === \App\Enums\GameStatus::Rejected,
                                    'bg-elevated text-subtle' => in_array($game->status, [\App\Enums\GameStatus::Draft, \App\Enums\GameStatus::Archived]),
                                ])>{{ str_replace('_', ' ', $game->status->value) }}</span>
                            </td>
                            <td class="px-4 py-3">{{ number_format($game->transaksis_count) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="{{ route('dev.games.edit', $game) }}" class="btn-ghost px-3 py-1.5 text-sm">Edit</a>
                                    @if (in_array($game->status, [\App\Enums\GameStatus::Published, \App\Enums\GameStatus::Archived]))
                                        <form method="POST" action="{{ route('dev.games.archive', $game) }}">
                                            @csrf
                                            <button class="btn-ghost px-3 py-1.5 text-sm">{{ $game->status === \App\Enums\GameStatus::Archived ? 'Unarchive' : 'Archive' }}</button>
                                        </form>
                                    @endif
                                </div>
                                <form method="POST" action="{{ route('dev.iaps.store', $game) }}" class="mt-2 flex flex-wrap items-center gap-2">
                                    @csrf
                                    <input type="text" name="name" placeholder="IAP name" required maxlength="150" class="input w-32 !py-1.5 text-sm">
                                    <input type="number" name="price" placeholder="Price" required min="0" step="0.01" class="input w-28 !py-1.5 text-sm">
                                    <select name="type" class="input w-auto !py-1.5 text-sm">
                                        <option value="currency">currency</option>
                                        <option value="item">item</option>
                                        <option value="upgrade">upgrade</option>
                                        <option value="cosmetic">cosmetic</option>
                                    </select>
                                    <button class="btn-ghost px-3 py-1.5 text-sm">Add IAP</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</x-portal-layout>
