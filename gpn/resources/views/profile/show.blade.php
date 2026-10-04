@extends('layouts.app')
@section('title', ($user->pengguna_nama ?: $user->name).' — GPlayNation')

@section('content')
{{-- Figma: Profile frame 6:1890 --}}
<div class="bg-gradient-to-b from-[#0d1b3a]/40 to-transparent pb-2 pt-12 text-center">
    <img src="{{ $user->avatar ?? '' }}" alt=""
         onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22/>'"
         class="mx-auto h-24 w-24 rounded-full border border-gpn-border-strong bg-gpn-elevated object-cover">
    <h1 class="mt-5 text-2xl font-semibold">{{ $user->pengguna_nama ?: $user->name }}</h1>

    <dl class="mt-5 inline-flex items-center divide-x divide-gpn-border-strong">
        @foreach (['assets' => 'Assets', 'slots' => 'Slots', 'totalHours' => 'Stats'] as $key => $label)
            <div class="px-8">
                <dt class="sr-only">{{ $label }}</dt>
                <dd class="text-lg font-semibold">
                    {{ $key === 'totalHours' ? number_format($stats[$key], 0) : number_format($stats[$key]) }}
                </dd>
                <p class="text-sm text-gpn-fg-faint">{{ $label }}</p>
            </div>
        @endforeach
    </dl>
</div>

<div class="border-y border-gpn-border">
    <nav class="mx-auto flex max-w-[1512px] justify-center" role="tablist" aria-label="Profile sections">
        @php $tabs = ['library' => 'My Library', 'purchased' => 'Purchased', 'wishlist' => 'Wishlist', 'settings' => 'Settings']; @endphp
        @foreach ($tabs as $value => $label)
            <button type="button" role="tab" class="gpn-tab" aria-selected="{{ $loop->first }}"
                    data-tab="{{ $value }}">{{ $label }}</button>
        @endforeach
    </nav>
</div>

<div class="mx-auto max-w-[1512px] px-12 py-10">
    {{-- SRS 3.1.1.3 Kelola Profil --}}
    <section id="settings" class="hidden">
        <h2 class="text-2xl font-semibold">Edit profile</h2>
        <form method="POST" action="{{ route('profile.update') }}" class="gpn-panel mt-6 max-w-lg space-y-5 p-6">
            @csrf
            @method('PUT')
            <div>
                <label for="name" class="gpn-label">Name</label>
                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" class="gpn-input">
            </div>
            <div>
                <label for="email" class="gpn-label">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" class="gpn-input">
            </div>
            <button class="gpn-btn gpn-btn-primary">Save</button>
        </form>
    </section>

    @foreach (['library' => $library, 'purchased' => $purchased, 'wishlist' => $wishlist] as $tab => $items)
        <section id="{{ $tab }}" @if ($tab !== 'library') class="hidden" @endif>
            @if ($items->isEmpty())
                <x-empty-state icon="bookmark"
                               title="{{ ['library' => 'Library is empty', 'purchased' => 'No purchases yet', 'wishlist' => 'Wishlist is empty'][$tab] }}"
                               message="Games you buy or save will show up here." />
            @else
                <div class="grid grid-cols-2 gap-6 md:grid-cols-3 xl:grid-cols-4">
                    @foreach ($items as $row)
                        @php $game = $row->game; @endphp
                        <article class="gpn-card overflow-hidden">
                            <a href="{{ route('store.show', $game) }}">
                                <div class="aspect-[16/10] bg-gpn-elevated">
                                    @if ($game->cover)
                                        <img src="{{ $game->cover }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                    @endif
                                </div>
                            </a>
                            <div class="p-4">
                                <h3 class="font-medium">{{ $game->game_nama }}</h3>
                                <p class="mt-1 text-sm text-gpn-fg-faint">
                                    @if ($tab === 'library')
                                        {{ number_format((float) $row->jam_main, 0) }} hours
                                    @elseif ($tab === 'purchased')
                                        {{ \Illuminate\Support\Carbon::parse($row->transaksi_tanggal)->format('j M Y') }}
                                    @else
                                        {{ $game->game_harga > 0 ? 'Rp '.number_format($game->game_harga, 0, ',', '.') : 'Free' }}
                                    @endif
                                </p>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    @endforeach
</div>

@push('scripts')
<script>
    const tabs = document.querySelectorAll('[data-tab]');

    tabs.forEach((tab) => tab.addEventListener('click', () => {
        tabs.forEach((t) => {
            const active = t === tab;
            t.setAttribute('aria-selected', active);
            document.getElementById(t.dataset.tab)?.classList.toggle('hidden', !active);
        });
    }));
</script>
@endpush
@endsection
