@props(['game'])
@php
    $promo = $game->activePromotion();
    $price = \App\Support\Money::format($game->price);
    $total = \App\Support\Money::format($game->effectivePrice());
@endphp
<div id="buy-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-label="Complete your purchase">
    <div class="w-[640px] max-w-full rounded-2xl border border-[#222224] bg-[#161618] p-5 shadow-2xl sm:p-8">
        <div class="flex items-start justify-between gap-6">
            <div>
                <h2 class="text-[24px] font-bold leading-[32px] text-white">Complete your purchase</h2>
                <p class="mt-1 text-[14px] text-white/60">One more step to your next adventure.</p>
            </div>
            <button type="button" data-close-buy aria-label="Close" class="rounded-lg p-1 text-[20px] leading-none text-white/60 transition hover:bg-white/10 hover:text-white">
                <x-icon name="x" class="h-5 w-5" />
            </button>
        </div>

        <div class="mt-6 flex items-center gap-4 rounded-xl border border-[#222224] bg-[#0F0F10] p-4">
            @if ($game->cover_path)
                <img src="{{ asset('storage/'.$game->cover_path) }}" alt="" aria-hidden="true" class="h-[80px] w-[112px] shrink-0 rounded-xl object-cover">
            @endif
            <div class="min-w-0">
                <p class="truncate text-[16px] font-semibold text-white">{{ $game->name }}</p>
                <p class="mt-0.5 text-[12px] text-white/60">Standard Edition · Windows, Mac</p>
                <p class="mt-1 text-[14px] font-bold text-white">{{ $price }}</p>
            </div>
        </div>

        <dl class="mt-6 space-y-2 text-[14px]">
            <div class="flex justify-between"><dt class="text-white/60">Subtotal</dt><dd class="font-semibold text-white">{{ $price }}</dd></div>
            <div class="flex justify-between"><dt class="text-white/60">Discount</dt><dd class="font-semibold text-white">{{ $promo ? '-'.$promo->discount_percent.'%' : \App\Support\Money::format(0) }}</dd></div>
            <div class="flex justify-between"><dt class="text-white/60">Service fee</dt><dd class="font-semibold text-white">{{ \App\Support\Money::format(0) }}</dd></div>
            <div class="flex justify-between border-t border-[#222224] pt-4"><dt class="text-[16px] font-semibold text-white">Total payment</dt><dd class="text-[18px] font-bold text-white">{{ $total }}</dd></div>
        </dl>
        <p class="mt-1 text-right text-[12px] text-white/60">Applicable taxes included in the game price.</p>

        <form method="POST" action="{{ route('checkout.store', ['game', $game->id]) }}" class="mt-6">
            @csrf
            <button type="submit" class="flex h-[42px] w-full items-center justify-center rounded-2xl bg-[#1D4ED8] text-[14px] font-medium text-white shadow-[0_0_15px_rgba(29,78,216,0.3)] transition hover:brightness-110 hover:shadow-[0_0_30px_rgba(29,78,216,0.55)]">Confirm Purchase</button>
        </form>
        <p class="mt-3 text-center text-[12px] text-white/60">Secure payment · Added to your library after purchase</p>
    </div>
</div>
<script>
    (function () {
        var modal = document.getElementById('buy-modal');
        if (!modal) return;
        var open = function () { modal.classList.remove('hidden'); modal.classList.add('flex'); };
        var close = function () { modal.classList.add('hidden'); modal.classList.remove('flex'); };
        document.querySelectorAll('[data-open-buy]').forEach(function (btn) {
            btn.addEventListener('click', open);
        });
        modal.querySelectorAll('[data-close-buy]').forEach(function (btn) {
            btn.addEventListener('click', close);
        });
        modal.addEventListener('click', function (event) {
            if (event.target === modal) close();
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') close();
        });
    })();
</script>
