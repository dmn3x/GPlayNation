<x-app-layout title="Home">
    <div class="space-y-12">
        @if ($hero)
            <section class="card overflow-hidden">
                <div class="grid md:grid-cols-2">
                    <div class="aspect-video bg-elevated">
                        <img src="{{ asset('storage/'.$hero->cover_path) }}" alt="{{ $hero->name }} cover" class="h-full w-full object-cover">
                    </div>
                    <div class="flex flex-col justify-center gap-4 p-8 md:p-10">
                        <span class="w-fit rounded bg-accent/10 px-2 py-1 text-xs font-medium uppercase tracking-wide text-accent-hi">Featured</span>
                        <h1 class="screen-title">{{ $hero->name }}</h1>
                        <p class="line-clamp-3 text-[16.2px] text-muted">{{ $hero->description }}</p>
                        <div class="text-[18.9px]"><x-price :game="$hero" /></div>
                        <div>
                            <a href="{{ route('store.show', $hero->slug) }}" class="btn-primary w-fit">View game</a>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        @if ($popular->isNotEmpty())
            <section>
                <h2 class="section-heading mb-4">Popular</h2>
                <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-5">
                    @foreach ($popular as $game)
                        <x-game-card :game="$game" />
                    @endforeach
                </div>
            </section>
        @endif

        @if ($latest->isNotEmpty())
            <section>
                <h2 class="section-heading mb-4">New Releases</h2>
                <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-5">
                    @foreach ($latest as $game)
                        <x-game-card :game="$game" />
                    @endforeach
                </div>
            </section>
        @endif

        @if (! $hero && $popular->isEmpty() && $latest->isEmpty())
            <section class="card flex flex-col items-center gap-3 px-8 py-16 text-center">
                <x-icon name="gamepad" class="h-10 w-10 text-faint" />
                <h2 class="section-heading">No games yet</h2>
                <p class="text-[16.2px] text-subtle">Games published by local studios will appear here.</p>
            </section>
        @endif
    </div>

    @if ($showIntro)
        <div id="intro-overlay" class="fixed inset-0 z-[60] flex cursor-pointer flex-col items-center justify-center gap-4 bg-bg sm:flex-row" role="presentation" aria-hidden="true" onclick="this.remove()">
            <span class="intro-logo inline-flex"><x-logo class="h-16 w-16" /></span>
            <span class="intro-word text-[27px] font-semibold tracking-[-0.675px]">GPlayNation</span>
        </div>
        <style>
            #intro-overlay { animation: intro-out 1.2s ease forwards; }
            .intro-logo { opacity: 0; animation: intro-pulse 1.2s ease forwards; }
            .intro-word { opacity: 0; animation: intro-word 1.2s ease forwards; }
            @keyframes intro-out { 0%, 82% { opacity: 1; } 100% { opacity: 0; visibility: hidden; } }
            @keyframes intro-pulse { 0% { opacity: 0; transform: scale(.7); } 25% { opacity: 1; transform: scale(1); } 50% { transform: scale(1.08); } 75% { transform: scale(1); } 100% { opacity: 1; } }
            @keyframes intro-word { 0%, 35% { opacity: 0; transform: translateY(6px); } 70%, 100% { opacity: 1; transform: none; } }
            @media (prefers-reduced-motion: reduce) { #intro-overlay { display: none; } }
        </style>
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
</x-app-layout>
