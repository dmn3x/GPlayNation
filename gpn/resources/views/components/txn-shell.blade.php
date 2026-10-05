@props(['active' => 'history', 'trx' => null, 'pending' => null, 'latest' => null, 'title' => 'Transaksi · GPlayNation'])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#0F0F10] font-sans text-white antialiased">
    <div class="flex min-h-screen">
        <aside class="sticky top-0 hidden h-screen w-[240px] shrink-0 flex-col border-r border-[#222224] bg-[#0F0F10] p-4 md:flex">
            <a href="{{ route('home') }}" class="mb-6 flex items-center p-2" aria-label="GPlayNation home">
                <x-logo class="h-7 w-auto text-white" />
            </a>
            <nav class="flex flex-col gap-1">
                <button type="button" data-cart-soon class="flex h-10 items-center gap-3 rounded-xl px-3 text-sm font-medium text-white/60 transition hover:bg-white/5 hover:text-white">
                    <x-icon name="cart" class="h-4 w-4" /> Keranjang
                </button>
                @if ($trx)
                    <a href="{{ route('checkout.summary', $trx->order_id) }}" @class(['flex h-10 items-center gap-3 rounded-xl px-3 text-sm font-medium transition', 'bg-[#1D4ED8]/20 text-white' => $active === 'pay', 'text-white/60 hover:bg-white/5 hover:text-white' => $active !== 'pay'])>
                        <x-icon name="wallet" class="h-4 w-4" /> Proses Pembayaran
                    </a>
                @elseif ($pending)
                    <a href="{{ route('checkout.summary', $pending->order_id) }}" class="flex h-10 items-center gap-3 rounded-xl px-3 text-sm font-medium text-white/60 transition hover:bg-white/5 hover:text-white">
                        <x-icon name="wallet" class="h-4 w-4" /> Proses Pembayaran
                    </a>
                @else
                    <span class="flex h-10 cursor-default items-center gap-3 rounded-xl px-3 text-sm font-medium text-white/30">
                        <x-icon name="wallet" class="h-4 w-4" /> Proses Pembayaran
                    </span>
                @endif
                @if ($trx)
                    <a href="{{ route('transactions.show', $trx->order_id) }}" @class(['flex h-10 items-center gap-3 rounded-xl px-3 text-sm font-medium transition', 'bg-[#1D4ED8]/20 text-white' => $active === 'status', 'text-white/60 hover:bg-white/5 hover:text-white' => $active !== 'status'])>
                        <x-icon name="document" class="h-4 w-4" /> Status Pembayaran
                    </a>
                @elseif ($latest)
                    <a href="{{ route('transactions.show', $latest->order_id) }}" class="flex h-10 items-center gap-3 rounded-xl px-3 text-sm font-medium text-white/60 transition hover:bg-white/5 hover:text-white">
                        <x-icon name="document" class="h-4 w-4" /> Status Pembayaran
                    </a>
                @else
                    <span class="flex h-10 cursor-default items-center gap-3 rounded-xl px-3 text-sm font-medium text-white/30">
                        <x-icon name="document" class="h-4 w-4" /> Status Pembayaran
                    </span>
                @endif
                <a href="{{ route('transactions.index') }}" @class(['flex h-10 items-center gap-3 rounded-xl px-3 text-sm font-medium transition', 'bg-[#1D4ED8]/20 text-white' => $active === 'history', 'text-white/60 hover:bg-white/5 hover:text-white' => $active !== 'history'])>
                    <x-icon name="banknote" class="h-4 w-4" /> Riwayat Transaksi
                </a>
            </nav>
        </aside>

        <main class="min-w-0 flex-1">
            {{ $slot }}
        </main>
    </div>

    <div id="cart-soon" class="pointer-events-none fixed inset-x-0 bottom-6 z-50 hidden justify-center" role="status">
        <p class="rounded-xl border border-[#27282A] bg-[#161618] px-5 py-2.5 text-[14px] text-white shadow-lg">Keranjang belanja segera hadir.</p>
    </div>
    <script>
        (function () {
            var note = document.getElementById('cart-soon');
            var timer = null;
            document.querySelectorAll('[data-cart-soon]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    note.classList.remove('hidden');
                    note.classList.add('flex');
                    clearTimeout(timer);
                    timer = setTimeout(function () {
                        note.classList.add('hidden');
                        note.classList.remove('flex');
                    }, 2500);
                });
            });
        })();
    </script>
    <x-toasts />
</body>
</html>
