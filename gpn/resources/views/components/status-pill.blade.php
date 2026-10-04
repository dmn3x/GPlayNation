@props(['status'])
@php
    $classes = match ($status) {
        \App\Enums\TransaksiStatus::Success => 'bg-success/15 text-success',
        \App\Enums\TransaksiStatus::Pending => 'bg-amber-400/15 text-amber-400',
        \App\Enums\TransaksiStatus::Failed, \App\Enums\TransaksiStatus::Expired => 'bg-danger/15 text-danger',
    };
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-sm font-medium $classes"]) }}>
    {{ ucfirst($status->value) }}
</span>
