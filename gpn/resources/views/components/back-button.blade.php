@props(['href'])
<a href="{{ $href }}" {{ $attributes->merge(['class' => 'gpn-btn gpn-btn-quiet']) }}>
    <x-icon name="arrow-left" class="h-5 w-5" /> Back
</a>
