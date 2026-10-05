@props(['title' => null, 'section' => 'developer'])
@php
    $nav = $section === 'admin'
        ? [['admin.dashboard', 'Dashboard', 'chart'], ['admin.verification.index', 'Verification', 'check'], ['admin.moderation.index', 'Moderation', 'shield'], ['admin.commission.index', 'Commission', 'wallet'], ['admin.payouts.index', 'Payouts', 'banknote'], ['admin.reports.index', 'Reports', 'document']]
        : [['dev.dashboard', 'Dashboard', 'chart'], ['dev.games.index', 'Games', 'gamepad'], ['dev.sales.index', 'Sales', 'banknote'], ['dev.promotion.index', 'Promotion', 'tag'], ['dev.tags.index', 'Tags', 'tags'], ['dev.payouts.index', 'Payouts', 'wallet'], ['dev.settings.edit', 'Settings', 'cog']];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? "$title · GPlayNation" : 'GPlayNation' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    <div class="flex min-h-screen">
        <aside class="flex w-[220px] shrink-0 flex-col border-r border-border bg-raised p-4">
            <a href="{{ route('home') }}" class="mb-6 flex items-center gap-2 px-2"><x-logo class="h-8 w-8" /><span class="font-semibold">GPlayNation</span></a>
            <p class="mb-2 px-2 text-xs font-medium uppercase tracking-wide text-faint">{{ $section === 'admin' ? 'Administration' : 'Developer Tools' }}</p>
            <nav class="flex flex-col gap-1">
                @foreach ($nav as [$route, $label, $icon])
                    @if (Route::has($route))
                    <a href="{{ route($route) }}" @class(['flex items-center gap-3 rounded-lg px-3 py-2 text-[16.2px] font-medium transition text-subtle hover:bg-accent/10 hover:text-fg', 'bg-accent/10 !text-fg' => request()->routeIs($route)])>
                        <x-icon :name="$icon" class="h-5 w-5" /> {{ $label }}
                    </a>
                    @endif
                @endforeach
            </nav>
            <form method="POST" action="{{ route('logout') }}" class="mt-auto">
                @csrf
                <button class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-[16.2px] font-medium text-subtle transition hover:bg-accent/10 hover:text-fg"><x-icon name="logout" class="h-5 w-5" /> Log out</button>
            </form>
        </aside>
        <main class="min-w-0 flex-1 p-10">
            {{ $slot }}
        </main>
    </div>
    <x-toasts />
</body>
</html>
