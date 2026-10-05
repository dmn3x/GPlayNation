<x-portal-layout title="Dashboard" section="developer">
    <div class="flex flex-wrap items-center justify-between gap-4 pb-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-white">Dashboard</h1>
            <p class="mt-1 text-sm text-white/50">Welcome back, {{ auth()->user()->developer?->studio_name ?? auth()->user()->name }}.</p>
        </div>
        <a href="{{ route('dev.games.create') }}" class="flex h-10 items-center gap-2 rounded-2xl bg-[#1D4ED8] px-4 text-sm font-medium text-white shadow-[0_0_15px_rgba(29,78,216,0.3)] transition hover:brightness-110">
            <x-icon name="plus" class="h-4 w-4" /> Submit New Game
        </a>
    </div>

    <div class="grid grid-cols-3 gap-4">
        <div class="flex items-center gap-4 rounded-2xl border border-[#222224] bg-[#161618] p-5">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-[#1D4ED8]/10 text-[#1D4ED8]"><x-icon name="users" class="h-5 w-5" /></span>
            <span>
                <span class="block text-xs font-medium text-white/60">Active Players</span>
                <span class="block text-2xl font-bold text-white">{{ number_format($downloads) }}</span>
            </span>
        </div>
        <div class="flex items-center gap-4 rounded-2xl border border-[#222224] bg-[#161618] p-5">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-[#F6339A]/10 text-[#F6339A]"><x-icon name="heart" class="h-5 w-5" /></span>
            <span>
                <span class="block text-xs font-medium text-white/60">Wishlist Adds</span>
                <span class="block text-2xl font-bold text-white">{{ number_format($wishlistAdds) }}</span>
            </span>
        </div>
        <div class="flex items-center gap-4 rounded-2xl border border-[#222224] bg-[#161618] p-5">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-[#F0B100]/10 text-[#F0B100]"><x-icon name="gamepad" class="h-5 w-5" /></span>
            <span>
                <span class="block text-xs font-medium text-white/60">Published Games</span>
                <span class="block text-2xl font-bold text-white">{{ number_format($publishedCount) }}</span>
            </span>
        </div>
    </div>

    <div class="mt-4 rounded-2xl border border-[#222224] bg-[#161618] p-6">
        <h2 class="text-sm font-semibold text-white">Performance Overview (IDR)</h2>
        <x-area-chart :points="$series" class="mt-6 border-0 !bg-transparent p-0 shadow-none" />
    </div>

    <div class="mt-8">
        <h2 class="text-sm font-semibold text-white">My Published Games</h2>
        <div class="mt-3 space-y-3">
            @forelse ($publishedGames as $game)
                <div class="flex items-center gap-4 rounded-2xl border border-[#222224] bg-[#161618] p-4">
                    @if ($game->cover_path)
                        <img src="{{ Storage::url($game->cover_path) }}" alt="" aria-hidden="true" class="h-12 w-16 shrink-0 rounded-xl object-cover">
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-white">{{ $game->name }}</p>
                        <p class="text-xs text-[#30D158]">Live</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <a href="{{ route('dev.games.edit', $game) }}" class="flex h-[30px] items-center rounded-xl border border-[#222224] px-3 text-xs font-medium text-white transition hover:bg-white/5">Edit Details</a>
                        <a href="{{ route('dev.games.edit', $game) }}" class="flex h-[30px] items-center rounded-xl border border-[#222224] px-3 text-xs font-medium text-white transition hover:bg-white/5">Update Build</a>
                        <form method="POST" action="{{ route('dev.games.archive', $game) }}">
                            @csrf
                            <button class="flex h-[30px] items-center rounded-xl border border-[#222224] px-3 text-xs font-medium text-[#FF6467] transition hover:bg-white/5">Archive</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-[#222224] bg-[#161618] p-6 text-center">
                    <p class="text-sm text-white/60">No published games yet.</p>
                    <a href="{{ route('dev.games.create') }}" class="mt-3 inline-flex h-10 items-center rounded-2xl bg-[#1D4ED8] px-4 text-sm font-medium text-white transition hover:brightness-110">Submit New Game</a>
                </div>
            @endforelse
        </div>
    </div>
</x-portal-layout>
