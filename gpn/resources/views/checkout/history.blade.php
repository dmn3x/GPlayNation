@extends('layouts.app')
@section('title', 'Riwayat Transaksi — GPlayNation')

@section('content')
<div class="mx-auto max-w-[1000px] px-8 py-10">
    <h1 class="text-3xl font-semibold">Riwayat Transaksi</h1>
    <p class="mt-1 text-gpn-fg-muted">SRS 3.1.3.4 — purchase history for games and IAP.</p>

    @if ($trx->isEmpty())
        <x-empty-state class="mt-8" icon="cart" title="Belum ada riwayat transaksi"
                       message="Pembelian game dan IAP Anda akan muncul di sini." />
    @else
        <div class="gpn-panel mt-8 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="border-b border-gpn-border text-left text-gpn-fg-faint">
                    <tr>
                        <th class="px-5 py-3">Tanggal</th>
                        <th class="px-5 py-3">Item</th>
                        <th class="px-5 py-3">Nominal</th>
                        <th class="px-5 py-3">Metode</th>
                        <th class="px-5 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gpn-border">
                    @foreach ($trx as $t)
                        <tr>
                            <td class="whitespace-nowrap px-5 py-3">{{ $t->transaksi_tanggal->format('j M Y') }}</td>
                            <td class="px-5 py-3">{{ $t->game?->game_nama ?? $t->iap?->iap_nama ?? '—' }}</td>
                            <td class="px-5 py-3">Rp {{ number_format($t->transaksi_total_harga, 0, ',', '.') }}</td>
                            <td class="px-5 py-3 text-gpn-fg-muted">{{ $t->pembayaran?->metode ?? '—' }}</td>
                            <td class="px-5 py-3">
                                <span class="gpn-tag {{ $t->transaksi_status === 'settlement' ? '!border-emerald-500/40 !text-emerald-300' : '' }}">
                                    {{ $t->transaksi_status }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-8">{{ $trx->links() }}</div>
    @endif
</div>
@endsection