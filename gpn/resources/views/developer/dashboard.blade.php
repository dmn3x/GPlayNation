@extends('developer.layout')
@section('title', 'Dashboard — Developer Tools')

@section('dev')
<div class="flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1 class="text-3xl font-semibold">Dashboard</h1>
        <p class="mt-1 text-gpn-fg-muted">Welcome back, {{ $developer->nama_studio }}.</p>
    </div>
    <button type="button" class="gpn-btn gpn-btn-primary" data-modal-open="upload-modal">
        <x-icon name="plus" class="h-5 w-5" /> Submit New Game
    </button>
</div>

<div class="mt-8 grid gap-4 md:grid-cols-3">
    <x-stat-tile icon="users" label="Active Players" :value="$stats['activePlayers']" />
    <x-stat-tile icon="heart" label="Wishlist Adds" :value="$stats['wishlistAdds']" />
    <x-stat-tile icon="star" label="Game Rating" :value="$stats['rating']" />
</div>

<div class="gpn-panel mt-6 p-6">
    <h2 class="font-medium">Performance Overview (30D)</h2>
    @include('developer.partials.chart', ['series' => $series])
</div>

<h2 class="mt-8 text-lg font-medium">My Published Games</h2>

@if ($published->isEmpty())
    <x-empty-state class="mt-4" icon="monitor" title="Belum ada game"
                   message="Ajukan game pertama Anda untuk ditinjau Admin." />
@else
    <ul class="mt-4 space-y-3">
        @foreach ($published as $game)
            <li class="gpn-panel flex flex-wrap items-center gap-4 px-5 py-4">
                <img src="{{ $game->cover }}" alt="" loading="lazy"
                     class="h-11 w-11 rounded-lg object-cover">
                <div class="min-w-0">
                    <p class="font-medium">{{ $game->game_nama }}</p>
                    <span class="gpn-tag mt-1">{{ $game->status_label }}</span>
                </div>

                <div class="ml-auto flex items-center gap-3">
                    <a href="{{ route('developer.games.update', $game) }}" class="gpn-btn gpn-btn-quiet gpn-btn-sm">
                        <x-icon name="edit" class="h-4 w-4" /> Edit Details
                    </a>
                    <a href="{{ route('store.show', $game) }}" class="gpn-btn gpn-btn-ghost gpn-btn-sm">
                        <x-icon name="monitor" class="h-4 w-4" /> View
                    </a>
                </div>
            </li>
        @endforeach
    </ul>
@endif

{{-- SRS 3.1.2.4 Mengunggah Game --}}
<div id="upload-modal" class="fixed inset-0 z-50 hidden place-items-center bg-black/70 p-6">
    <div class="gpn-panel w-full max-w-lg p-7">
        <div class="flex items-start justify-between">
            <h2 class="text-2xl font-semibold">Submit New Game</h2>
            <button type="button" class="gpn-stat-icon !h-8 !w-8" data-modal-close>&times;</button>
        </div>

        <form method="POST" action="{{ route('developer.games.store') }}" enctype="multipart/form-data"
              class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="game_nama" class="gpn-label">Game name</label>
                <input id="game_nama" name="game_nama" required class="gpn-input" value="{{ old('game_nama') }}">
                @error('game_nama')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="deskripsi" class="gpn-label">Description</label>
                <textarea id="deskripsi" name="deskripsi" rows="4" required class="gpn-input">{{ old('deskripsi') }}</textarea>
                @error('deskripsi')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="game_genre" class="gpn-label">Genre</label>
                    <input id="game_genre" name="game_genre" required class="gpn-input" value="{{ old('game_genre') }}">
                </div>
                <div>
                    <label for="kategori" class="gpn-label">Category</label>
                    <input id="kategori" name="kategori" class="gpn-input" value="{{ old('kategori') }}">
                </div>
            </div>
            <div>
                <label for="game_harga" class="gpn-label">Price (IDR)</label>
                <input id="game_harga" name="game_harga" type="number" min="0" required class="gpn-input" value="{{ old('game_harga', 0) }}">
            </div>
            <div>
                <label for="berkas" class="gpn-label">Installer / cover file</label>
                <input id="berkas" name="berkas" type="file" class="gpn-input">
                @error('berkas')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
            </div>

            <button class="gpn-btn gpn-btn-primary w-full">Submit for review</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const modal = document.getElementById('upload-modal');

    document.querySelectorAll('[data-modal-open]').forEach((b) =>
        b.addEventListener('click', () => modal.classList.replace('hidden', 'grid')));

    document.querySelectorAll('[data-modal-close]').forEach((b) =>
        b.addEventListener('click', () => modal.classList.replace('grid', 'hidden')));
</script>
@endpush
