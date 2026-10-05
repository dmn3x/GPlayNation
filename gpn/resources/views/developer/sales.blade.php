<x-portal-layout title="Sales" section="developer">
    <h1 class="text-2xl font-bold tracking-tight text-white">Sales</h1>
    <p class="mt-1 text-sm text-white/50">View and manage your sales performance.</p>

    <form method="GET" action="{{ route('dev.sales.index') }}" class="mt-6 flex flex-wrap items-end gap-3">
        <label class="flex flex-col gap-1 text-sm text-subtle">Month
            <input type="month" name="month" value="{{ $month }}" class="input w-auto">
        </label>
        <label class="flex flex-col gap-1 text-sm text-subtle">Game
            <select name="game_id" class="input w-auto">
                <option value="">All games</option>
                @foreach ($games as $game)
                    <option value="{{ $game->id }}" @selected((int) $gameId === $game->id)>{{ $game->name }}</option>
                @endforeach
            </select>
        </label>
        <button class="btn-primary">Apply</button>
    </form>
    @error('month')<p class="mt-2 text-sm text-danger">{{ $message }}</p>@enderror
    @error('game_id')<p class="mt-2 text-sm text-danger">{{ $message }}</p>@enderror

    <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-5">
        <div class="card p-5">
            <p class="text-sm text-faint">Gross</p>
            <p class="mt-1 text-2xl font-semibold">{{ \App\Support\Money::format($kpis['gross']) }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-faint">Commission</p>
            <p class="mt-1 text-2xl font-semibold">{{ \App\Support\Money::format($kpis['commission']) }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-faint">Net</p>
            <p class="mt-1 text-2xl font-semibold">{{ \App\Support\Money::format($kpis['net']) }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-faint">Units</p>
            <p class="mt-1 text-2xl font-semibold">{{ number_format($kpis['units']) }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-faint">Downloads</p>
            <p class="mt-1 text-2xl font-semibold">{{ number_format($kpis['downloads']) }}</p>
        </div>
    </div>

    <h2 class="section-heading mt-6">Revenue</h2>
    <x-area-chart :points="$series" class="mt-3" />

    <h2 class="section-heading mt-6">Transactions</h2>
    <div class="card mt-3 overflow-hidden">
        @if ($rows->isEmpty())
            <p class="p-5 text-subtle">No sales in this period.</p>
        @else
            <table class="w-full text-left text-[15px]">
                <thead>
                    <tr class="border-b border-border text-sm text-faint">
                        <th class="px-4 py-3 font-medium">Date</th>
                        <th class="px-4 py-3 font-medium">Order</th>
                        <th class="px-4 py-3 font-medium">Buyer</th>
                        <th class="px-4 py-3 font-medium">Game</th>
                        <th class="px-4 py-3 font-medium">Gross</th>
                        <th class="px-4 py-3 font-medium">Commission</th>
                        <th class="px-4 py-3 font-medium">Net</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr class="border-b border-border last:border-0">
                            <td class="px-4 py-3 text-subtle">{{ $row->pembayaran?->paid_at?->format('d M Y H:i') ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $row->order_id }}</td>
                            <td class="px-4 py-3">{{ $row->user?->name ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $row->game?->name ?? '—' }}</td>
                            <td class="px-4 py-3">{{ \App\Support\Money::format($row->total) }}</td>
                            <td class="px-4 py-3">{{ \App\Support\Money::format($row->komisiTransaksi?->amount ?? 0) }}</td>
                            <td class="px-4 py-3">{{ \App\Support\Money::format($row->komisiTransaksi?->developer_amount ?? 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
    <div class="mt-4">{{ $rows->links() }}</div>
</x-portal-layout>
