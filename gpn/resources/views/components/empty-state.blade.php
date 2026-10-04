@props(['title', 'message' => null, 'icon' => 'search'])
<div {{ $attributes->merge(['class' => 'gpn-panel grid place-items-center gap-3 px-8 py-16 text-center']) }}>
    <span class="gpn-stat-icon"><x-icon :name="$icon" class="h-6 w-6" /></span>
    <p class="font-semibold">{{ $title }}</p>
    @if ($message)<p class="max-w-sm text-sm text-gpn-fg-faint">{{ $message }}</p>@endif
    {{ $slot }}
</div>
