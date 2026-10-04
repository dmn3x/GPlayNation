@extends('admin.layout')
@section('title', 'Payout — Admin')

@section('admin')
<h1 class="text-3xl font-semibold">Kelola Payout</h1>
<p class="mt-1 text-gpn-fg-muted">SRS 3.1.4.3 — disburse developer revenue after commission deduction.</p>

<form method="GET" class="mt-6 flex items-end gap-3">
    <div>
        <label for="periode" class="gpn-label">Periode</label>
        <input id="periode" name="periode" type="month" value="{{ $periode }}" class="gpn-input">
    </div>
    <button class="gpn-btn gpn-btn-primary">Tampilkan</button>
</form>

@if ($rows->isEmpty())
    <x-empty-state class="mt-8" icon="download" title="Payout belum dapat diproses"
                   message="Tidak ada pendapatan developer pada periode ini." />
@else
    <div class="gpn-panel mt-8 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="border-b border-gpn-border text-left text-gpn-fg-faint">
                <tr>
                    <th class="px-5 py-3">Developer</th>
                    <th class="px-5 py-3">Penjualan</th>
                    <th class="px-5 py-3">Komisi</th>
                    <th class="px-5 py-3">Bersih</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gpn-border">
                @foreach ($rows as $row)
                    <tr>
                        <td class="px-5 py-3">{{ $row['developer']->nama_studio }}</td>
                        <td class="px-5 py-3">Rp {{ number_format($row['pembelian'], 0, ',', '.') }}</td>
                        <td class="px-5 py-3">Rp {{ number_format($row['komisi'], 0, ',', '.') }}</td>
                        <td class="px-5 py-3 font-semibold">Rp {{ number_format($row['pembelian'] - $row['komisi'], 0, ',', '.') }}</td>
                        <td class="px-5 py-3">
                            @if ($row['payout'])
                                <span class="gpn-tag">{{ $row['payout']->status }}</span>
                            @else
                                <span class="gpn-tag">pending</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right">
                            @unless ($row['payout'])
                                <form method="POST" action="{{ route('admin.payouts.store', $row['developer']->user_id) }}">
                                    @csrf
                                    <input type="hidden" name="periode" value="{{ $periode }}">
                                    <button class="gpn-btn gpn-btn-primary gpn-btn-sm">Proses Payout</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
