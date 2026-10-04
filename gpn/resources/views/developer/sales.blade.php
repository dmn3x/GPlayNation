@extends('developer.layout')
@section('title', 'Sales — Developer Tools')

@section('dev')
<h1 class="text-3xl font-semibold">Sales</h1>
<p class="mt-1 text-gpn-fg-muted">View and manage your sales and revenue.</p>

{{-- SRS 3.1.5.1 / 3.1.5.2 — period filter drives every figure on this screen --}}
<form method="GET" class="mt-6 flex items-end gap-3">
    <div>
        <label for="periode" class="gpn-label">Period</label>
        <input id="periode" name="periode" type="month" value="{{ $periode }}" class="gpn-input">
    </div>
    <button class="gpn-btn gpn-btn-primary">Tampilkan</button>
</form>

<div class="mt-6 grid gap-4 md:grid-cols-4">
    <x-stat-tile icon="users" label="Total Revenue" :value="'Rp '.number_format($totalRevenue, 0, ',', '.')" />
    <x-stat-tile icon="cart" label="Total Orders" :value="number_format($totalOrders)" />
    <x-stat-tile icon="heart" label="Units Sold" :value="number_format($unitsSold)" />
    <x-stat-tile icon="star" label="Refunds" value="0" />
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
    <div class="gpn-panel p-6">
        <h2 class="font-medium">Sales Overview</h2>
        @include('developer.partials.chart', ['series' => $series])
        <div class="mt-3 flex justify-between text-sm text-gpn-fg-faint">
            @foreach ($labels as $label)<span>{{ $label }}</span>@endforeach
        </div>
    </div>

    <div class="gpn-panel p-6">
        <h2 class="font-medium">Top Selling Games</h2>
        <ul class="mt-4 divide-y divide-gpn-border">
            @foreach ($topGames as $game)
                <li class="flex items-center gap-3 py-3">
                    <img src="{{ $game->cover }}" alt="" loading="lazy" class="h-11 w-11 rounded-lg object-cover">
                    <div class="min-w-0">
                        <p class="truncate font-medium">{{ $game->game_nama }}</p>
                        <p class="text-sm text-gpn-fg-faint">{{ number_format($game->unduhan) }} Units</p>
                    </div>
                    <span class="ml-auto whitespace-nowrap text-sm">
                        Rp {{ number_format($game->unduhan * $game->game_harga, 0, ',', '.') }}
                    </span>
                </li>
            @endforeach
        </ul>
    </div>
</div>
@endsection
