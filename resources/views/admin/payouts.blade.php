<x-portal-layout title="Payouts" section="admin">
    <div class="pb-8">
        <h1 class="text-2xl font-bold tracking-tight text-white">Payout Komisi</h1>
        <p class="mt-1 text-sm text-white/50">Kelola pembayaran komisi untuk developer.</p>
    </div>

    <h2 class="section-heading mt-6">Unpaid balances</h2>
    <div class="card mt-3 overflow-hidden">
        @if ($balances->isEmpty())
            <p class="p-5 text-subtle">No unpaid balances.</p>
        @else
            <table class="w-full text-left text-[15px]">
                <thead>
                    <tr class="border-b border-border text-sm text-faint">
                        <th class="px-4 py-3 font-medium">Studio</th>
                        <th class="px-4 py-3 font-medium">Bank</th>
                        <th class="px-4 py-3 font-medium">Unpaid</th>
                        <th class="px-4 py-3 font-medium"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($balances as $entry)
                        <tr class="border-b border-border last:border-0">
                            <td class="px-4 py-3 font-medium">{{ $entry['developer']->studio_name }}</td>
                            <td class="px-4 py-3 text-subtle">{{ $entry['developer']->payout_bank ?? '—' }} {{ $entry['developer']->payout_account ?? '' }}</td>
                            <td class="px-4 py-3">{{ \App\Support\Money::format($entry['balance']) }}</td>
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('admin.payouts.store', $entry['developer']) }}" onsubmit="return confirm('Process payout of {{ \App\Support\Money::format($entry['balance']) }} to {{ $entry['developer']->studio_name }}?')">
                                    @csrf
                                    <button class="btn-primary px-3 py-1.5 text-sm">Process</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <h2 class="section-heading mt-6">Payout history</h2>
    <div class="card mt-3 overflow-hidden">
        @if ($payouts->isEmpty())
            <p class="p-5 text-subtle">No payouts processed yet.</p>
        @else
            <table class="w-full text-left text-[15px]">
                <thead>
                    <tr class="border-b border-border text-sm text-faint">
                        <th class="px-4 py-3 font-medium">Studio</th>
                        <th class="px-4 py-3 font-medium">Period</th>
                        <th class="px-4 py-3 font-medium">Gross</th>
                        <th class="px-4 py-3 font-medium">Commission</th>
                        <th class="px-4 py-3 font-medium">Net</th>
                        <th class="px-4 py-3 font-medium">Processed</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payouts as $payout)
                        <tr class="border-b border-border last:border-0">
                            <td class="px-4 py-3">{{ $payout->developer?->studio_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-subtle">{{ $payout->period }}</td>
                            <td class="px-4 py-3">{{ \App\Support\Money::format($payout->gross) }}</td>
                            <td class="px-4 py-3">{{ \App\Support\Money::format($payout->commission) }}</td>
                            <td class="px-4 py-3">{{ \App\Support\Money::format($payout->net) }}</td>
                            <td class="px-4 py-3 text-subtle">{{ $payout->created_at->format('d M Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</x-portal-layout>
