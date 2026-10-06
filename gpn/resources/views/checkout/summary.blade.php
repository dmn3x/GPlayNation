<x-txn-shell active="pay" :title="'Proses Pembayaran · GPlayNation'" :trx="$trx" :pending="$trx">
    @php
        $preferred = old('method', session('gateway_method_'.$trx->order_id, 'e_wallet'));
        $methods = [
            ['value' => 'e_wallet', 'chip' => 'EW', 'label' => 'E-Wallet', 'desc' => 'GoPay, OVO, DANA, dan lainnya'],
            ['value' => 'virtual_account', 'chip' => 'VA', 'label' => 'Virtual Account', 'desc' => 'BCA, BRI, Mandiri, dan lainnya'],
            ['value' => 'credit_card', 'chip' => 'CC', 'label' => 'Kartu Kredit / Debit', 'desc' => 'Visa, Mastercard, JCB'],
        ];
        $itemName = $trx->iap?->name ?? $trx->game?->name ?? 'Unknown item';
    @endphp
    <div class="mx-auto max-w-[1120px] p-4 sm:p-6 md:p-[45px]">
        <div class="flex flex-col gap-4 lg:flex-row lg:flex-wrap lg:items-end lg:justify-between lg:gap-6">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[1.54px] text-[#60A5FA]">Checkout</p>
                <h1 class="mt-1 text-[28px] font-bold leading-[36px] text-white">Proses Pembayaran</h1>
                <p class="mt-1 text-[14px] text-white/60">Selesaikan pembayaran untuk mulai bermain.</p>
            </div>
            <x-checkout-steps :current="2" />
        </div>

        <form method="POST" action="{{ route('checkout.pay', $trx->order_id) }}" class="mt-6 flex flex-col gap-6 lg:flex-row">
            @csrf
            <section class="min-w-0 flex-1 rounded-2xl border border-[#222224] bg-[#161618] p-4 sm:p-7">
                <h2 class="text-[16px] font-semibold text-white">Pilih Metode Pembayaran</h2>
                <p class="mt-1 text-[12px] text-white/60">Semua transaksi dilindungi dengan enkripsi berstandar industri.</p>

                <div class="mt-5 space-y-3">
                    @foreach ($methods as $method)
                        <label class="flex h-[78px] cursor-pointer items-center gap-4 rounded-[14px] border border-[#28282C] bg-[#121214] p-4 transition has-[:checked]:border-[#2563EB] has-[:checked]:bg-[#2563EB]/10">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl border border-[#28282C] bg-[#202024] text-[12px] font-bold text-[#93C5FD]">{{ $method['chip'] }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-[14px] font-semibold text-white">{{ $method['label'] }}</span>
                                <span class="block truncate text-[12px] text-white/60">{{ $method['desc'] }}</span>
                            </span>
                            <input type="radio" name="method" value="{{ $method['value'] }}" @checked($preferred === $method['value']) class="h-5 w-5 shrink-0 accent-[#2563EB]">
                        </label>
                    @endforeach
                </div>
                @error('method')<p class="field-error">{{ $message }}</p>@enderror

                <div class="mt-5 flex gap-3 rounded-[14px] border border-[#30D158]/20 bg-[#30D158]/5 p-4">
                    <span class="rounded-[7px] bg-[#30D158]/10 px-[7px] py-[6px] text-[10px] font-bold leading-none text-[#30D158]">SSL</span>
                    <p class="text-[12px] leading-[18px] text-white/60"><span class="font-semibold text-white">Pembayaran aman.</span> Informasi pembayaran tidak disimpan oleh GPlayNation.</p>
                </div>
            </section>

            <aside class="w-full shrink-0 rounded-2xl border border-[#222224] bg-[#161618] p-6 lg:w-[360px]">
                <h2 class="text-[18px] font-semibold text-white">Ringkasan Pesanan</h2>
                <div class="mt-4 flex items-center gap-3">
                    @if ($trx->game?->cover_path)
                        <img src="{{ asset('storage/'.$trx->game->cover_path) }}" alt="" aria-hidden="true" class="h-[52px] w-[72px] shrink-0 rounded-lg object-cover">
                    @endif
                    <div class="min-w-0">
                        <p class="truncate text-[14px] font-semibold text-white">{{ $itemName }}</p>
                        <p class="text-[12px] text-white/60">Windows · Standard Edition</p>
                    </div>
                </div>

                <dl class="mt-4 space-y-2 border-t border-[#222224] pt-4 text-[13px]">
                    <div class="flex justify-between"><dt class="text-[12px] text-white/60">Harga game</dt><dd class="font-semibold text-white">{{ \App\Support\Money::format($trx->game?->price ?? $trx->total) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-[12px] text-white/60">Biaya layanan</dt><dd class="font-semibold text-white">{{ \App\Support\Money::format(0) }}</dd></div>
                    <div class="flex justify-between border-t border-[#222224] pt-3"><dt class="text-[14px] text-white/60">Total pembayaran</dt><dd class="text-[18px] font-bold text-white">{{ \App\Support\Money::format($trx->total) }}</dd></div>
                </dl>

                <label class="mt-4 flex items-start gap-2 text-[12px] text-white/60">
                    <input type="checkbox" name="agreement" value="1" @checked(old('agreement')) class="mt-0.5 h-4 w-4 shrink-0 rounded border-[#28282C] bg-[#121214] accent-[#1D4ED8]">
                    <span>Saya menyetujui syarat penjualan dan memahami bahwa pembelian bersifat final.</span>
                </label>
                @error('agreement')<p class="field-error">{{ $message }}</p>@enderror

                <button type="submit" class="mt-4 flex h-[42px] w-full items-center justify-center rounded-[14px] bg-[#1D4ED8] px-5 text-[14px] font-medium text-white shadow-[0_0_15px_rgba(29,78,216,0.25)] transition hover:brightness-110">Bayar {{ \App\Support\Money::format($trx->total) }}</button>
                <a href="{{ route('store.show', $trx->game->slug) }}" class="mt-3 flex h-[42px] w-full items-center justify-center rounded-[14px] border border-[#28282C] px-5 text-[14px] font-medium text-white/60 transition hover:bg-white/5 hover:text-white">Kembali</a>
                <p class="mt-3 text-center text-[10px] leading-[14px] text-white/40">Dengan melanjutkan, kamu menyetujui Syarat dan Ketentuan GPlayNation.</p>
            </aside>
        </form>

        <form method="POST" action="{{ route('checkout.cancel', $trx->order_id) }}" class="mt-4">
            @csrf
            <button type="submit" class="text-[12px] text-white/40 transition hover:text-white/70 hover:underline">Batalkan pesanan ini</button>
        </form>
    </div>
</x-txn-shell>
