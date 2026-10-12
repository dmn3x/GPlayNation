<div {{ $attributes->merge(['class' => 'card p-5']) }}>
    <svg viewBox="0 0 720 {{ $height }}" class="w-full" preserveAspectRatio="none" role="img" aria-label="Chart">
        <defs>
            <linearGradient id="areaFill" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#1D4ED8" stop-opacity="0.15" />
                <stop offset="100%" stop-color="#1D4ED8" stop-opacity="0" />
            </linearGradient>
        </defs>
        @for ($i = 1; $i <= 3; $i++)
            <line x1="0" x2="720" y1="{{ $height * $i / 4 }}" y2="{{ $height * $i / 4 }}" stroke="#222224" stroke-width="1" />
        @endfor
        <polygon points="{{ $area }}" fill="url(#areaFill)" />
        <path d="M {{ $line }}" fill="none" stroke="#1D4ED8" stroke-width="1.5" stroke-linejoin="round" stroke-linecap="round" />
    </svg>
    <div class="mt-2 flex justify-between text-xs text-faint">
        @foreach (array_filter($points, fn ($p, $i) => $i % (int) max(1, ceil(count($points) / 7)) === 0, ARRAY_FILTER_USE_BOTH) as $p)
            <span>{{ $p['label'] }}</span>
        @endforeach
    </div>
</div>
