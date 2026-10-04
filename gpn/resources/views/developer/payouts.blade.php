@extends('developer.layout')
@section('title', 'Payouts — Developer Tools')

@section('dev')
<h1 class="text-3xl font-semibold">Payouts</h1>
<p class="mt-1 text-gpn-fg-muted">Commission deductions and disbursement history (SRS 3.1.4.3).</p>

<div class="gpn-panel mt-6 p-6">
    <h2 class="font-medium">Unpaid balance</h2>
    <p class="mt-2 text-4xl font-semibold">Rp {{ number_format($unpaid, 0, ',', '.') }}</p>
    <p class="mt-1 text-sm text-gpn-fg-faint">Pendapatan bersih setelah komisi platform, belum diproses payout.</p>
</div>

<h2 class="mt-8 text-lg font-medium">Payout history</h2>

@if ($payouts->isEmpty())
    <x-empty-state class="mt-4" icon="download" title="Belum ada payout"
                   message="Payout akan muncul setelah Admin memprosesnya." />
@else
    <div class="gpn-panel mt-4 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="border-b border-gpn-border text-left text-gpn-fg-faint">
                <tr>
                    <th class="px-5 py-3">Periode</th>
                    <th class="px-5 py-3">Komisi</th>
                    <th class="px-5 py-3">Bersih</th>
                    <th class="px-5 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gpn-border">
                @foreach ($payouts as $payout)
                    <tr>
                        <td class="px-5 py-3">{{ $payout->periode }}</td>
                        <td class="px-5 py-3">Rp {{ number_format($payout->total_komisi, 0, ',', '.') }}</td>
                        <td class="px-5 py-3">Rp {{ number_format($payout->total_bersih, 0, ',', '.') }}</td>
                        <td class="px-5 py-3">
                            <span class="gpn-tag">{{ $payout->status }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
