@props(['game'])
@php $promo = $game->activePromotion(); @endphp
@if ($game->isFree())
    <span class="font-semibold text-success">Free</span>
@elseif ($promo)
    <span class="flex items-center gap-2">
        <span class="text-faint line-through">{{ \App\Support\Money::format($game->price) }}</span>
        <span class="font-semibold">{{ \App\Support\Money::format($game->effectivePrice()) }}</span>
        <span class="rounded bg-success/15 px-1.5 py-0.5 text-xs font-medium text-success">-{{ $promo->discount_percent }}%</span>
    </span>
@else
    <span class="font-semibold">{{ \App\Support\Money::format($game->price) }}</span>
@endif
