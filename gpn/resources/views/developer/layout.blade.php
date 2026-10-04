@extends('layouts.app')

@section('content')
{{-- Figma "DEVELOPER TOOLS" sidebar, shared by dev dashboard / sales / tags / settings --}}
<div class="flex">
    <aside class="gpn-sidebar w-[220px] flex-none p-6" aria-label="Developer tools">
        <p class="text-xs font-semibold uppercase tracking-wider text-gpn-fg-faint">Developer Tools</p>

        <nav class="mt-6 space-y-1">
            @php
                $menu = [
                    ['developer.dashboard', 'chart', 'Overview'],
                    ['developer.sales', 'flame', 'Sales'],
                    ['developer.promotion', 'bolt', 'Promotion'],
                    ['developer.tags', 'tag', 'Tags'],
                    ['developer.settings', 'settings', 'Settings'],
                    ['developer.payouts', 'download', 'Payouts'],
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
        @yield('dev')
    </div>
</div>
@endsection
