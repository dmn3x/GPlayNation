@extends('admin.layout')
@section('title', 'Moderasi Game — Admin')

@section('admin')
<h1 class="text-3xl font-semibold">Moderasi Game</h1>
<p class="mt-1 text-gpn-fg-muted">SRS 3.1.4.1 — review submissions before they reach the catalog.</p>

@if ($games->isEmpty())
    <x-empty-state class="mt-8" icon="monitor" title="Tidak ada game menunggu moderasi" />
@else
    <ul class="mt-8 space-y-4">
        @foreach ($games as $game)
            <li class="gpn-panel flex flex-wrap gap-5 p-5">
                <img src="{{ $game->cover }}" alt="" loading="lazy" class="h-28 w-48 rounded-xl object-cover">

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="text-xl font-semibold">{{ $game->game_nama }}</h2>
                        <span class="gpn-tag">{{ $game->game_status }}</span>
                    </div>
                    <p class="mt-1 text-sm text-gpn-fg-faint">
                        {{ $game->developer?->nama_studio }} · {{ $game->game_genre }} ·
                        Rp {{ number_format($game->game_harga, 0, ',', '.') }}
                    </p>
                    <p class="mt-3 max-w-[70ch] text-sm leading-relaxed text-gpn-fg-muted">{{ $game->deskripsi }}</p>
                </div>

                <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto sm:flex-col">
                    <form method="POST" action="{{ route('admin.moderation.store', $game) }}">
                        @csrf
                        <button name="aksi" value="setujui" class="gpn-btn gpn-btn-primary w-full">
                            <x-icon name="check" class="h-5 w-5" /> Setujui
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.moderation.store', $game) }}"
                          class="flex w-full items-center gap-2 sm:w-auto">
                        @csrf
                        <input type="hidden" name="aksi" value="tolak">
                        <label for="catatan-{{ $game->id }}" class="sr-only">Catatan perbaikan</label>
                        <input id="catatan-{{ $game->id }}" name="catatan" placeholder="Catatan perbaikan"
                               class="gpn-input py-2 text-sm">
                        <button class="gpn-btn gpn-btn-ghost">Tolak</button>
                    </form>
                </div>
            </li>
        @endforeach
    </ul>

    <div class="mt-8">{{ $games->links() }}</div>
@endif
@endsection
