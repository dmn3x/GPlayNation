@props(['title' => null])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? "$title · GPlayNation" : 'GPlayNation' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    <header class="sticky top-0 z-40 border-b border-border bg-bg/90 backdrop-blur">
        <div class="mx-auto flex h-[72px] max-w-[1440px] items-center gap-6 px-12">
            <a href="{{ route('home') }}" class="flex items-center gap-3"><x-logo class="h-9 w-9" /><span class="text-[18.9px] font-semibold">GPlayNation</span></a>
            <nav class="flex items-center gap-1">
                <a href="{{ route('home') }}" @class(['nav-pill', 'nav-pill-active' => request()->routeIs('home')])>Home</a>
                <a href="{{ route('store.index') }}" @class(['nav-pill', 'nav-pill-active' => request()->routeIs('store.*')])>Store</a>
                <a href="{{ route('community.index') }}" @class(['nav-pill', 'nav-pill-active' => request()->routeIs('community.*')])>Community</a>
            </nav>
            <form action="{{ route('store.index') }}" method="GET" class="ml-auto w-72">
                <input name="q" value="{{ request('q') }}" placeholder="Search local games…" class="input !py-2">
            </form>
            @auth
                <a href="{{ route('profile.show') }}" class="flex items-center gap-2 rounded-full px-3 py-1.5 hover:bg-elevated">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-accent text-sm font-semibold">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    <span class="text-[16.2px] font-medium">{{ auth()->user()->name }}</span>
                </a>
            @else
                <a href="{{ route('login') }}" class="btn-primary !py-2">Sign in</a>
            @endauth
        </div>
    </header>
    <main class="mx-auto max-w-[1440px] px-12 py-10">
        {{ $slot }}
    </main>
    <x-toasts />
</body>
</html>
