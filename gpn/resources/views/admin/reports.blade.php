@extends('admin.layout')
@section('title', 'Laporan — Admin')

@section('admin')
<h1 class="text-3xl font-semibold">Pelaporan Platform</h1>
<p class="mt-1 text-gpn-fg-muted">SRS 3.1.5.3–3.1.5.5 / F-10.</p>

<div class="mt-8 grid gap-4 md:grid-cols-3">
    <x-stat-tile icon="cart" label="Total Transaksi" :value="number_format($totalTransaksi)" />
    <x-stat-tile icon="tag" label="Total Pendapatan" :value="'Rp '.number_format($totalPendapatan, 0, ',', '.')" />
    <x-stat-tile icon="chart" label="Total Komisi" :value="'Rp '.number_format($totalKomisi, 0, ',', '.')" />
</div>

<form method="GET" class="mt-8 flex items-end gap-3">
    <div>
        <label for="status" class="gpn-label">Status</label>
        <select id="status" name="status" class="gpn-input">
            @foreach (['semua' => 'Semua', 'settlement' => 'Settlement', 'pending' => 'Pending', 'failed' => 'Failed'] as $value => $label)
                <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <button class="gpn-btn gpn-btn-primary">Tampilkan</button>
</form>

<div class="gpn-panel mt-6 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="border-b border-gpn-border text-left text-gpn-fg-faint">
            <tr>
                <th class="px-5 py-3">Tanggal</th>
                <th class="px-5 py-3">Pengguna</th>
                <th class="px-5 py-3">Produk</th>
                <th class="px-5 py-3">Nominal</th>
                <th class="px-5 py-3">Komisi</th>
                <th class="px-5 py-3">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gpn-border">
            @forelse ($trx as $t)
                <tr>
                    <td class="whitespace-nowrap px-5 py-3">{{ $t->transaksi_tanggal->format('j M Y H:i') }}</td>
                    <td class="px-5 py-3">{{ $t->pengguna->pengguna_nama }}</td>
                    <td class="px-5 py-3">{{ $t->game?->game_nama ?? $t->iap?->iap_nama ?? '—' }}</td>
                    <td class="px-5 py-3">Rp {{ number_format($t->transaksi_total_harga, 0, ',', '.') }}</td>
                    <td class="px-5 py-3">Rp {{ number_format($t->komisi?->nominal ?? 0, 0, ',', '.') }}</td>
                    <td class="px-5 py-3"><span class="gpn-tag">{{ $t->transaksi_status }}</span></td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-5 py-8 text-center text-gpn-fg-faint">Data transaksi tidak ditemukan.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="p-5">{{ $trx->links() }}</div>
</div>
@endsection
