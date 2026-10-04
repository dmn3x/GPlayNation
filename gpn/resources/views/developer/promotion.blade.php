@extends('developer.layout')
@section('title', 'Promotion — Developer Tools')

@section('dev')
<h1 class="text-3xl font-semibold">Promotion</h1>
<p class="mt-1 text-gpn-fg-muted">Run temporary discounts on your published games.</p>

<div class="gpn-panel mt-6 max-w-lg p-7">
    <form method="POST" action="{{ route('developer.promotion.store') }}" class="space-y-4">
        @csrf
        <div>
            <label for="game_id" class="gpn-label">Game</label>
            <select id="game_id" name="game_id" class="gpn-input" required>
                @foreach ($games as $game)
                    <option value="{{ $game->id }}">{{ $game->game_nama }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="diskon" class="gpn-label">Discount (%)</label>
                <input id="diskon" name="diskon" type="number" min="1" max="90" required class="gpn-input" value="{{ old('diskon', 20) }}">
            </div>
            <div>
                <label for="berlaku_sampai" class="gpn-label">Valid until</label>
                <input id="berlaku_sampai" name="berlaku_sampai" type="date" required class="gpn-input" value="{{ old('berlaku_sampai') }}">
            </div>
        </div>
        <button class="gpn-btn gpn-btn-primary w-full">Apply promo</button>
    </form>
</div>

<h2 class="mt-8 text-lg font-medium">Published games</h2>
<ul class="mt-4 space-y-3">
    @foreach ($games as $game)
        <li class="gpn-panel flex items-center gap-4 px-5 py-4">
            <img src="{{ $game->cover }}" alt="" loading="lazy" class="h-11 w-11 rounded-lg object-cover">
            <div>
                <p class="font-medium">{{ $game->game_nama }}</p>
                <p class="text-sm text-gpn-fg-faint">Rp {{ number_format($game->game_harga, 0, ',', '.') }}</p>
            </div>
        </li>
    @endforeach
</ul>
@endsection
