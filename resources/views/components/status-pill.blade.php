@props(['status'])
@php
    [$classes, $label] = match ($status) {
        \App\Enums\TransaksiStatus::Success => ['bg-[#30D158]/10 text-[#30D158]', 'Berhasil'],
        \App\Enums\TransaksiStatus::Pending => ['bg-amber-400/10 text-amber-400', 'Menunggu'],
        \App\Enums\TransaksiStatus::Failed => ['bg-[#FF453A]/10 text-[#FF453A]', 'Gagal'],
        \App\Enums\TransaksiStatus::Expired => ['bg-white/5 text-white/60', 'Kedaluwarsa'],
    };
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2 py-[5px] text-[10px] font-medium $classes"]) }}>
    {{ $label }}
</span>
