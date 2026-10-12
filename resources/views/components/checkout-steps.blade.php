@props(['current' => 1, 'doneAll' => false])
@php
    $steps = ['Pembelian', 'Pembayaran', 'Selesai'];
@endphp
<div class="flex items-center" aria-label="Checkout progress">
    @foreach ($steps as $i => $label)
        @php
            $n = $i + 1;
            $done = $doneAll || $n < $current;
            $isCurrent = ! $doneAll && $n === $current;
        @endphp
        @if ($i > 0)
            <span class="h-px w-6 sm:w-[58px] {{ $done || $isCurrent ? 'bg-[#1D4ED8]' : 'bg-[#28282C]' }}"></span>
        @endif
        <span class="flex flex-col items-center gap-1">
            <span class="grid h-7 w-7 place-items-center rounded-full text-[11px] font-semibold {{ ($done || $isCurrent) ? 'bg-[#1D4ED8] text-white' : 'border border-[#28282C] bg-[#161618] text-white/40' }}">
                @if ($done)
                    <x-icon name="check" class="h-3.5 w-3.5" />
                @else
                    {{ $n }}
                @endif
            </span>
            <span class="text-[11px] {{ ($done || $isCurrent) ? 'text-white' : 'text-white/40' }}">{{ $label }}</span>
        </span>
    @endforeach
</div>
