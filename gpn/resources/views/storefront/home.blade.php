<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GPlayNation</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-bg text-fg font-sans antialiased">
    <section class="relative flex min-h-[560px] flex-col justify-end overflow-hidden pb-20 pt-28 sm:min-h-[640px] md:h-[932px] md:justify-normal md:pb-0 md:pt-0">
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

        <div class="relative z-10 mx-auto w-full max-w-[864px] px-4 pb-16 sm:px-8 md:absolute md:bottom-[260px] md:left-[85px] md:mx-0 md:px-0 md:pb-0">
            <p class="text-[14px] font-bold uppercase leading-6 tracking-[0.9px] text-[#1D4ED8] md:text-[18px]">Game of the Week</p>

            @if ($hero)
                <h1 class="mt-4 max-w-[538px] text-4xl font-bold leading-[1.05] tracking-[-1.8px] text-white sm:text-6xl md:mt-[30px] md:text-[72px] md:leading-[72px]">'{{ $hero->name }}'</h1>
                <p class="mt-3 text-[18px] font-medium leading-[28px] text-white/60 md:text-[27px] md:leading-[42px]">by {{ $hero->developer?->studio_name ?? 'Nusantara Studios' }}</p>
                <p class="mt-4 max-w-[672px] text-[15px] leading-[24px] text-white/70 md:mt-[30px] md:text-[21px] md:leading-[34.125px]">{{ $hero->description }}</p>
                <a href="{{ route('store.show', $hero->slug) }}"
                   class="mt-6 inline-flex h-12 w-full items-center justify-center rounded-2xl bg-[#1D4ED8] px-8 text-[18px] font-medium whitespace-nowrap text-white shadow-[0_0_30px_rgba(29,78,216,0.4)] transition hover:brightness-110 hover:shadow-[0_0_36px_rgba(29,78,216,0.55)] sm:w-[240px] md:mt-[30px] md:h-[72px] md:rounded-3xl md:px-12 md:text-[24px]">
                    Explore Now
                </a>
            @endif
        </div>

        <div class="absolute bottom-[104px] left-1/2 hidden w-20 -translate-x-1/2 items-center gap-2 md:flex">
            <span class="h-1 w-8 rounded-full bg-[#1D4ED8]"></span>
            <span class="h-1 w-2 rounded-full bg-white/20"></span>
            <span class="h-1 w-2 rounded-full bg-white/20"></span>
            <span class="h-1 w-2 rounded-full bg-white/20"></span>
        </div>
    </section>

    @if ($popular->isNotEmpty())
        <section class="mx-auto max-w-[1440px] px-4 py-10 md:px-12 md:py-14">
            <h2 class="mb-6 text-[18.9px] font-semibold leading-[27px]">Popular</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-5">
                @foreach ($popular as $game)
                    <x-game-card :game="$game" />
                @endforeach
            </div>
        </section>
    @endif

    @if ($latest->isNotEmpty())
        <section class="mx-auto max-w-[1440px] px-4 pb-12 md:px-12 md:pb-16">
            <h2 class="mb-6 text-[18.9px] font-semibold leading-[27px]">New Releases</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-5">
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
