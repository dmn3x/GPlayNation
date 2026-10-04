@extends('layouts.app')
@section('title', 'Order Summary — GPlayNation')

@section('content')
<div class="grid min-h-[calc(100dvh-48px)] place-items-center px-6 py-16">
    <div class="gpn-panel w-full max-w-[460px] p-8">
        <h1 class="text-3xl font-semibold">Order Summary</h1>
        <p class="mt-1 text-gpn-fg-faint">Periksa pesanan sebelum membayar (SRS 3.1.3.1).</p>

        @if ($type === 'game')
            <img src="{{ $item->cover }}" alt="" class="mt-6 aspect-video w-full rounded-xl object-cover">
        @endif

        <div class="mt-6 space-y-2 text-sm">
            <div class="flex justify-between gap-4">
                <span class="text-gpn-fg-muted">Produk</span>
                <span class="text-right font-medium">{{ $nama }}</span>
            </div>
            <div class="flex justify-between gap-4">
                <span class="text-gpn-fg-muted">Metode pembayaran</span>
                <span class="text-right font-medium">Midtrans — E-Wallet / VA / Kartu Kredit</span>
            </div>
        </div>

        <div class="mt-6 flex items-baseline justify-between border-t border-gpn-border pt-5">
            <span class="text-gpn-fg-muted">Total</span>
            <span class="text-3xl font-semibold">Rp {{ number_format($harga, 0, ',', '.') }}</span>
        </div>

        <form method="POST" action="{{ route('checkout.store', ['type' => $type, 'id' => $item->id]) }}" class="mt-7">
            @csrf
            @if ($type === 'game')
                <label class="mb-4 flex items-start gap-2 text-sm text-gpn-fg-muted">
                    <input type="checkbox" name="setuju" value="1" class="mt-0.5 rounded border-gpn-border-strong bg-gpn-bg">
                    Saya menyetujui ketentuan pembelian di GPlayNation.
                </label>
                @error('setuju')<p class="mb-3 text-sm text-red-400">{{ $message }}</p>@enderror
            @endif

            <button class="gpn-btn gpn-btn-primary w-full">Bayar</button>
            <a href="{{ url()->previous() }}" class="gpn-btn gpn-btn-ghost mt-3 w-full">Batal</a>
        </form>
    </div>
</div>
@endsection