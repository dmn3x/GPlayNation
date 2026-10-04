@extends('layouts.app')

@section('content')
<div class="flex">
    <aside class="gpn-sidebar w-[220px] flex-none p-6" aria-label="Administration">
        <p class="text-xs font-semibold uppercase tracking-wider text-gpn-fg-faint">Administration</p>

        <nav class="mt-6 space-y-1">
            @php
                $menu = [
                    ['admin.dashboard', 'chart', 'Overview'],
                    ['admin.users', 'users', 'Verifikasi Akun'],
                    ['admin.moderation', 'monitor', 'Moderasi Game'],
                    ['admin.komisi', 'tag', 'Komisi'],
                    ['admin.payouts', 'download', 'Payout'],
                    ['admin.reports', 'bolt', 'Laporan'],
                ];
            @endphp
            @foreach ($menu as [$route, $icon, $label])
                <a href="{{ route($route) }}" class="gpn-side-link"
                   @if (request()->routeIs($route)) aria-current="page" @endif>
                    <x-icon :name="$icon" class="h-5 w-5" /> {{ $label }}
                </a>
            @endforeach
        </nav>

        <x-back-button :href="route('home')" class="absolute bottom-6 w-[188px]" />
    </aside>

    <div class="min-w-0 flex-1 px-10 py-8">
        @yield('admin')
    </div>
</div>
@endsection
