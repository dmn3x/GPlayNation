@extends('layouts.app')
@section('title', 'Payment Pending — GPlayNation')

@section('content')
<div class="grid min-h-[calc(100dvh-48px)] place-items-center px-6 py-16">
    <div class="gpn-panel w-full max-w-[460px] p-8 text-center">
        <span class="gpn-stat-icon mx-auto !h-14 !w-14"><x-icon name="clock" class="h-7 w-7" /></span>

        <h1 class="mt-5 text-3xl font-semibold">Menunggu Pembayaran</h1>
        <p class="mt-2 text-gpn-fg-muted">{{ $nama }}</p>
        <p class="mt-1 text-2xl font-semibold">Rp {{ number_format($transaksi->transaksi_total_harga, 0, ',', '.') }}</p>

        {{-- Midtrans Snap token — in production the checkout popup opens here --}}
        <p class="mt-6 text-sm text-gpn-fg-faint">
            Order ID <span class="font-mono">{{ $transaksi->id }}</span> ·
            Snap token <span class="font-mono">{{ $snapToken }}</span>
        </p>
        <p class="mt-3 text-sm text-gpn-fg-faint">
            Status diperbarui otomatis lewat notifikasi Midtrans (SRS 3.1.3.3).
        </p>

        <div class="mt-7 flex gap-3">
            <a href="{{ route('transactions.index') }}" class="gpn-btn gpn-btn-ghost flex-1">Riwayat Transaksi</a>
            <a href="{{ route('store.index') }}" class="gpn-btn gpn-btn-primary flex-1">Kembali ke Store</a>
        </div>
    </div>
</div>
@endsection