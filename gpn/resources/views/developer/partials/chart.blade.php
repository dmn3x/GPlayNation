{{-- Figma chart: 1.5px accent line over an accent gradient area fill --}}
@php
    $points = $series ?? [];
    $w = 1000;
    $h = 260;
    $max = max(1, max($points ?: [1]));
    $step = $points ? $w / max(1, count($points) - 1) : $w;
@endphp

@if (count($points) > 1)
    <svg viewBox="0 0 {{ $w }} {{ $h }}" preserveAspectRatio="none" class="mt-6 h-56 w-full">
        <defs>
            <linearGradient id="gpn-area" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#1d4ed8" stop-opacity="0.45" />
                <stop offset="100%" stop-color="#1d4ed8" stop-opacity="0" />
            </linearGradient>
        </defs>

        @php
            $coords = collect($points)->map(fn ($v, $i) => [
                round($i * $step), round($h - ($v / $max) * ($h - 20) - 10),
            ]);
            $line = $coords->map(fn ($p, $i) => ($i ? 'L' : 'M').$p[0].' '.$p[1])->implode(' ');
            $area = $line.' L'.$w.' '.$h.' L0 '.$h.' Z';
        @endphp

        <path d="{{ $area }}" fill="url(#gpn-area)" />
        <path d="{{ $line }}" fill="none" stroke="#1d4ed8" stroke-width="1.5"
              stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
    </svg>
@else
    <p class="mt-6 text-sm text-gpn-fg-faint">Belum ada data penjualan untuk ditampilkan.</p>
@endif
