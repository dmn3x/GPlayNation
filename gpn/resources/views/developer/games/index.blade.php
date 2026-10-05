<x-portal-layout title="Games" section="developer">
    <p class="text-[12px] text-white/60">GPlayNation / Developer Dashboard</p>

    <div class="mt-2 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-[30px] font-bold leading-[36px] tracking-tight text-white">Manage Games</h1>
            <p class="mt-1 text-[14px] text-white/60">Upload, publish, and track all your games in one place.</p>
        </div>
        <a href="{{ route('dev.games.create') }}" class="flex h-[42px] items-center gap-2 rounded-2xl bg-[#1D4ED8] px-4 text-sm font-medium text-white shadow-[0_0_15px_rgba(29,78,216,0.3)] transition hover:brightness-110">
            <x-icon name="plus" class="h-4 w-4" /> Add New Game
        </a>
    </div>

    <div class="mt-6 grid grid-cols-3 gap-6">
        <div class="rounded-2xl border border-[#222224] bg-[#161618] p-5">
            <p class="text-[14px] text-white/60">Total Games</p>
            <p class="mt-1 text-[28px] font-bold text-white">{{ number_format($stats['total']) }}</p>
            <p class="mt-1 text-[12px] text-white/60">Across all statuses</p>
        </div>
        <div class="rounded-2xl border border-[#222224] bg-[#161618] p-5">
            <p class="text-[14px] text-white/60">Units Sold</p>
            <p class="mt-1 text-[28px] font-bold text-white">{{ number_format($stats['units']) }}</p>
            <p class="mt-1 text-[12px] text-white/60">Settled transactions</p>
        </div>
        <div class="rounded-2xl border border-[#222224] bg-[#161618] p-5">
            <p class="text-[14px] text-white/60">Gross Revenue</p>
            <p class="mt-1 text-[28px] font-bold text-white">{{ \App\Support\Money::format($stats['revenue']) }}</p>
            <p class="mt-1 text-[12px] text-white/60">Before platform commission</p>
        </div>
    </div>

    <div class="mt-6 overflow-hidden rounded-2xl border border-[#222224] bg-[#161618]">
        <div class="flex flex-wrap items-center justify-between gap-3 p-4">
            <form method="GET" action="{{ route('dev.games.index') }}" class="flex h-10 w-[320px] max-w-full items-center gap-3 rounded-2xl border border-[#222224] bg-[#0F0F10] px-4">
                <x-icon name="search" class="h-5 w-5 shrink-0 text-white/40" />
                <input name="q" value="{{ request('q') }}" placeholder="Search your games…" class="w-full bg-transparent text-[14px] text-white placeholder:text-white/50 focus:outline-none">
            </form>
            <p class="text-[12px] text-white/60">Showing {{ $games->count() }} of {{ $stats['total'] }}</p>
        </div>

        @if ($games->isEmpty())
            <div class="border-t border-[#222224] p-10 text-center">
                <p class="text-lg font-medium text-white">No games found</p>
                <p class="mt-1 text-sm text-white/60">Upload your first game to start selling on GPlayNation.</p>
                <a href="{{ route('dev.games.create') }}" class="mt-4 inline-flex h-10 items-center rounded-2xl bg-[#1D4ED8] px-4 text-sm font-medium text-white transition hover:brightness-110">Add New Game</a>
            </div>
        @else
            <table class="w-full text-left">
                <thead>
                    <tr class="h-11 border-y border-[#222224] bg-white/[0.03] text-xs font-medium uppercase tracking-wide text-white/60">
                        <th class="px-4 font-medium">Title</th>
                        <th class="px-4 font-medium">Status</th>
                        <th class="px-4 font-medium">Price</th>
                        <th class="px-4 font-medium">Sales</th>
                        <th class="px-4"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($games as $game)
                        <tr class="h-[68px] border-t border-[#222224] first:border-t-0">
                            <td class="px-4">
                                <span class="flex items-center gap-3">
                                    <img src="{{ Storage::url($game->cover_path) }}" alt="" aria-hidden="true" class="h-12 w-[72px] shrink-0 rounded-lg object-cover">
                                    <span class="min-w-0">
                                        <span class="block truncate text-[14px] font-semibold text-white">{{ $game->name }}</span>
                                        <span class="block text-[12px] text-white/60">{{ $game->genre }}</span>
                                    </span>
                                </span>
                                @if ($game->status === \App\Enums\GameStatus::Rejected && $game->rejection_feedback)
                                    <p class="mt-1 text-[12px] text-[#FF6467]">{{ $game->rejection_feedback }}</p>
                                @endif
                            </td>
                            <td class="px-4">
                                <span @class([
                                    'inline-flex h-[25px] items-center rounded-full px-3 text-xs font-medium',
                                    'bg-[#30D158]/10 text-[#30D158]' => $game->status === \App\Enums\GameStatus::Published,
                                    'bg-white/5 text-white/60' => $game->status === \App\Enums\GameStatus::Draft,
                                    'bg-[#F0B100]/10 text-[#EAB308]' => $game->status === \App\Enums\GameStatus::PendingReview,
                                    'bg-[#FF453A]/10 text-[#FF453A]' => in_array($game->status, [\App\Enums\GameStatus::Rejected, \App\Enums\GameStatus::Archived]),
                                ])>{{ ucfirst(str_replace('_', ' ', $game->status->value)) }}</span>
                            </td>
                            <td class="px-4 text-[14px] text-white">{{ \App\Support\Money::format($game->price) }}</td>
                            <td class="px-4 text-[14px] text-white">{{ number_format($game->transaksis_count) }}</td>
                            <td class="px-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('dev.games.edit', $game) }}" class="flex h-[30px] items-center whitespace-nowrap rounded-xl border border-[#222224] px-3 text-xs font-medium text-white transition hover:bg-white/5">Edit Details</a>
                                    <a href="{{ route('dev.games.edit', $game) }}" class="flex h-[30px] items-center whitespace-nowrap rounded-xl border border-[#222224] px-3 text-xs font-medium text-white transition hover:bg-white/5">Update Build</a>
                                    @if (in_array($game->status, [\App\Enums\GameStatus::Published, \App\Enums\GameStatus::Archived]))
                                        <form method="POST" action="{{ route('dev.games.archive', $game) }}">
                                            @csrf
                                            <button class="flex h-[30px] items-center whitespace-nowrap rounded-xl border border-[#222224] px-3 text-xs font-medium text-[#FF6467] transition hover:bg-white/5">{{ $game->status === \App\Enums\GameStatus::Archived ? 'Unarchive' : 'Archive' }}</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="flex items-center justify-between border-t border-[#222224] px-4 py-3 text-[12px] text-white/60">
                <span>Showing {{ $games->count() }} of {{ $stats['total'] }}</span>
                <span>Page 1 of 1</span>
            </div>
        @endif
    </div>
</x-portal-layout>
