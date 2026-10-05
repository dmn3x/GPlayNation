<x-portal-layout title="Dashboard" section="developer">
    <h1 class="screen-title">Dashboard</h1>
    <p class="mt-1 text-subtle">Sales performance for {{ now()->format('F Y') }}</p>

    <div class="mt-6 grid grid-cols-2 gap-4 xl:grid-cols-4">
        <div class="card p-5">
            <p class="text-sm text-faint">Gross revenue</p>
            <p class="mt-1 text-2xl font-semibold">{{ \App\Support\Money::format($gross) }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-faint">Net earnings</p>
            <p class="mt-1 text-2xl font-semibold">{{ \App\Support\Money::format($net) }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-faint">Sales</p>
            <p class="mt-1 text-2xl font-semibold">{{ number_format($sales) }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-faint">Downloads</p>
            <p class="mt-1 text-2xl font-semibold">{{ number_format($downloads) }}</p>
        </div>
    </div>

    <div class="mt-6">
        <h2 class="section-heading">Revenue — {{ now()->format('F Y') }}</h2>
        @if ($sales > 0)
            <x-area-chart :points="$series" class="mt-3" />
        @else
            <div class="card mt-3 p-5">
                <p class="text-subtle">No sales yet — your chart will fill in once players buy.</p>
                <x-area-chart :points="$series" class="mt-3 border-0 !bg-transparent p-0 shadow-none" />
            </div>
        @endif
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <div>
            <h2 class="section-heading">Recent sales</h2>
            <div class="card mt-3 overflow-hidden">
                @if ($recent->isEmpty())
                    <p class="p-5 text-subtle">No sales yet.</p>
                @else
                    <table class="w-full text-left text-[15px]">
                        <thead>
                            <tr class="border-b border-border text-sm text-faint">
                                <th class="px-4 py-3 font-medium">Buyer</th>
                                <th class="px-4 py-3 font-medium">Game</th>
                                <th class="px-4 py-3 font-medium">Total</th>
                                <th class="px-4 py-3 font-medium">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recent as $trx)
                                <tr class="border-b border-border last:border-0">
                                    <td class="px-4 py-3">{{ $trx->user->name }}</td>
                                    <td class="px-4 py-3">{{ $trx->game->name }}</td>
                                    <td class="px-4 py-3">{{ \App\Support\Money::format($trx->total) }}</td>
                                    <td class="px-4 py-3 text-subtle">{{ $trx->created_at->format('d M Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        <div>
            <h2 class="section-heading">Your games</h2>
            <div class="card mt-3 divide-y divide-border">
                @forelse (auth()->user()->developer->games()->latest()->get() as $game)
                    <div class="flex items-center gap-3 px-4 py-3">
                        <span class="min-w-0 flex-1 truncate font-medium">{{ $game->name }}</span>
                        <span @class([
                            'inline-flex items-center rounded-full px-2.5 py-0.5 text-sm font-medium',
                            'bg-success/15 text-success' => $game->status === \App\Enums\GameStatus::Published,
                            'bg-amber-400/15 text-amber-400' => $game->status === \App\Enums\GameStatus::PendingReview,
                            'bg-danger/15 text-danger' => $game->status === \App\Enums\GameStatus::Rejected,
                            'bg-elevated text-subtle' => in_array($game->status, [\App\Enums\GameStatus::Draft, \App\Enums\GameStatus::Archived]),
                        ])>{{ str_replace('_', ' ', $game->status->value) }}</span>
                    </div>
                @empty
                    <p class="p-5 text-subtle">No games yet — upload your first game to get started.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-portal-layout>
