<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GPlayNation</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-bg text-fg font-sans antialiased">
    <section class="relative h-[932px] overflow-hidden">
        @if ($hero?->cover_path)
            <img src="{{ asset('storage/'.$hero->cover_path) }}" alt="" aria-hidden="true" class="absolute inset-0 h-full w-full object-cover opacity-60">
        @else
            <div class="absolute inset-0 bg-[#0F0F10]"></div>
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_30%_30%,rgba(29,78,216,0.22),transparent_55%)]"></div>
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_80%_80%,rgba(40,123,247,0.10),transparent_50%)]"></div>
        @endif
        <div class="absolute inset-0 bg-gradient-to-b from-[#0F0F10] via-[#0F0F10]/80 to-transparent"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-[#0F0F10] via-black/75 to-transparent"></div>

        <div class="absolute inset-x-0 top-0">
            @include('partials.marketing-header', ['active' => 'store'])
        </div>

        <div class="absolute bottom-[260px] left-[85px] max-w-[864px]">
            <p class="text-[18px] font-bold uppercase leading-6 tracking-[0.9px] text-[#1D4ED8]">Game of the Week</p>

            @if ($hero)
                <h1 class="mt-[30px] max-w-[538px] text-[72px] font-bold leading-[72px] tracking-[-1.8px] text-white">'{{ $hero->name }}'</h1>
                <p class="mt-3 text-[27px] font-medium leading-[42px] text-white/60">by {{ $hero->developer?->studio_name ?? 'Nusantara Studios' }}</p>
                <p class="mt-[30px] max-w-[672px] text-[21px] leading-[34.125px] text-white/70">{{ $hero->description }}</p>
                <a href="{{ route('store.show', $hero->slug) }}"
                   class="mt-[30px] inline-flex h-[72px] w-[240px] items-center justify-center rounded-3xl bg-[#1D4ED8] px-12 text-[24px] font-medium whitespace-nowrap text-white shadow-[0_0_30px_rgba(29,78,216,0.4)] transition hover:brightness-110 hover:shadow-[0_0_36px_rgba(29,78,216,0.55)]">
                    Explore Now
                </a>
            @endif
        </div>

        <div class="absolute bottom-[104px] left-1/2 flex w-20 -translate-x-1/2 items-center gap-2">
            <span class="h-1 w-8 rounded-full bg-[#1D4ED8]"></span>
            <span class="h-1 w-2 rounded-full bg-white/20"></span>
            <span class="h-1 w-2 rounded-full bg-white/20"></span>
            <span class="h-1 w-2 rounded-full bg-white/20"></span>
        </div>
    </section>

    @if ($popular->isNotEmpty())
        <section class="mx-auto max-w-[1440px] px-12 py-14">
            <h2 class="mb-6 text-[18.9px] font-semibold leading-[27px]">Popular</h2>
            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-5">
                @foreach ($popular as $game)
                    <x-game-card :game="$game" />
                @endforeach
            </div>
        </section>
    @endif

    @if ($latest->isNotEmpty())
        <section class="mx-auto max-w-[1440px] px-12 pb-16">
            <h2 class="mb-6 text-[18.9px] font-semibold leading-[27px]">New Releases</h2>
            <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-5">
                @foreach ($latest as $game)
                    <x-game-card :game="$game" />
                @endforeach
            </div>
        </section>
    @endif

    @if ($showIntro)
        <div id="intro-overlay" class="overlay-leave fixed inset-0 z-50 grid place-items-center bg-bg" role="presentation" aria-hidden="true" onclick="this.remove()">
            <div class="flex items-center gap-4">
                <span class="logo-glow inline-flex"><x-logo class="h-16 w-auto" /></span>
                <span class="overlay-enter text-[27px] font-semibold tracking-[-0.675px]">GPlayNation</span>
            </div>
        </div>
        <script>
            (function () {
                var overlay = document.getElementById('intro-overlay');
                if (! overlay) return;
                overlay.addEventListener('animationend', function (event) {
                    if (event.target === overlay) overlay.remove();
                });
            })();
        </script>
    @endif

    @if (session('account_created'))
        <div id="account-created" class="overlay-leave pointer-events-none fixed inset-0 z-50 flex items-center justify-center bg-bg/80 backdrop-blur-sm" role="status">
            <div class="card overlay-enter flex flex-col items-center gap-3 px-8 py-7 text-center">
                <x-icon name="check" class="h-10 w-10 text-success" />
                <p class="section-heading">Account created!</p>
                @if (session('account_created') === 'developer')
                    <p class="text-[16.2px] text-subtle">Your studio is awaiting admin verification.</p>
                @endif
            </div>
        </div>
        <script>
            (function () {
                var accountCreated = document.getElementById('account-created');
                if (! accountCreated) return;
                accountCreated.addEventListener('animationend', function (event) {
                    if (event.target !== accountCreated) return;
                    accountCreated.remove();
                });
            })();
        </script>
    @endif

    <x-toasts />
</body>
</html>
