@props(['icon', 'label', 'value', 'delta' => null, 'down' => false])
<div {{ $attributes->merge(['class' => 'gpn-stat']) }}>
    <span class="gpn-stat-icon"><x-icon :name="$icon" class="h-6 w-6" /></span>
    <div class="min-w-0">
        <div class="text-sm text-gpn-fg-faint">{{ $label }}</div>
        <div class="flex items-baseline gap-3">
            <span class="text-3xl font-semibold tracking-tight">{{ $value }}</span>
            @if ($delta)
                <span class="gpn-badge {{ $down ? 'gpn-badge-down' : '' }}">{{ $delta }}</span>
                <span class="text-sm text-gpn-fg-faint">vs last month</span>
            @endif
        </div>
    </div>
</div>
