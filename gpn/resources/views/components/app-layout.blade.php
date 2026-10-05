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
        <div class="mx-auto flex h-[72px] max-w-[1440px] items-center gap-6 px-4 md:px-12">
            <a href="{{ route('home') }}" class="flex items-center gap-3"><x-logo class="h-9 w-9" /><span class="text-[18.9px] font-semibold">GPlayNation</span></a>
            <nav class="flex items-center gap-1">
                <a href="{{ route('home') }}" @class(['nav-pill', 'nav-pill-active' => request()->routeIs('home')])>Home</a>
                <a href="{{ route('store.index') }}" @class(['nav-pill', 'nav-pill-active' => request()->routeIs('store.*')])>Store</a>
                <a href="{{ route('community.index') }}" @class(['nav-pill', 'nav-pill-active' => request()->routeIs('community.*')])>Community</a>
            </nav>
            <form action="{{ route('store.index') }}" method="GET" class="ml-auto hidden w-72 lg:block">
                <input name="q" value="{{ request('q') }}" placeholder="Search local games…" class="input !py-2">
            </form>
            @auth
                <details class="relative ml-auto lg:ml-0">
                    <summary class="flex cursor-pointer list-none items-center gap-2 rounded-full px-3 py-1.5 hover:bg-elevated [&::-webkit-details-marker]:hidden">
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-accent text-sm font-semibold">{{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="text-[16.2px] font-medium">{{ auth()->user()->name }}</span>
                    </summary>
                    <div class="absolute right-0 z-50 mt-2 w-56 overflow-hidden rounded-xl border border-border bg-bg shadow-lg">
                        <a href="{{ route('profile.show') }}" class="flex items-center gap-3 px-4 py-2.5 text-[15px] hover:bg-elevated">Profile</a>
                        <a href="{{ route('transactions.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-[15px] hover:bg-elevated">Transactions</a>
                        @if (auth()->user()->isVerifiedDeveloper())
                            <a href="{{ route('dev.dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 text-[15px] hover:bg-elevated">Developer portal</a>
                        @endif
                        @if (auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 text-[15px] hover:bg-elevated">Admin panel</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-[15px] text-danger hover:bg-elevated">Log out</button>
                        </form>
                    </div>
                </details>
            @else
                <a href="{{ route('login') }}" class="btn-primary !py-2">Sign in</a>
            @endauth
        </div>
    </header>
    <main class="mx-auto max-w-[1440px] px-4 py-10 md:px-12">
        {{ $slot }}
    </main>
    <x-toasts />
</body>
</html>
