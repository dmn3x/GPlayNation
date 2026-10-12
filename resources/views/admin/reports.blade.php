<x-portal-layout title="Reports" section="admin">
    <div class="pb-8">
        <h1 class="text-2xl font-bold tracking-tight text-white">Laporan Transaksi Admin</h1>
        <p class="mt-1 text-sm text-white/50">Pantau dan kelola seluruh transaksi platform.</p>
    </div>

    <div class="mt-6 flex gap-2">
        <a href="{{ route('admin.reports.index', array_filter(['tab' => 'transactions', 'month' => $month, 'status' => $status])) }}" @class(['btn-ghost px-4 py-2 text-sm', 'btn-primary' => $tab !== 'commission'])>Transactions</a>
        <a href="{{ route('admin.reports.index', array_filter(['tab' => 'commission', 'month' => $month])) }}" @class(['btn-ghost px-4 py-2 text-sm', 'btn-primary' => $tab === 'commission'])>Commission</a>
    </div>

    @if ($tab === 'commission')
        <form method="GET" action="{{ route('admin.reports.index') }}" class="mt-4 flex flex-wrap items-end gap-3">
            <input type="hidden" name="tab" value="commission">
            <label class="flex flex-col gap-1 text-sm text-subtle">Month
                <input type="month" name="month" value="{{ $month }}" class="input w-auto">
            </label>
            <button class="btn-primary">Apply</button>
        </form>
        @error('month')<p class="mt-2 text-sm text-danger">{{ $message }}</p>@enderror

        <div class="card mt-4 overflow-hidden">
            @if ($commission->isEmpty())
                <p class="p-5 text-subtle">No commission data</p>
            @else
                <table class="w-full text-left text-[15px]">
                    <thead>
                        <tr class="h-[33px] border-b border-[#222224] text-[11px] font-medium uppercase text-white/50">
                            <th class="px-4 font-medium">Studio</th>
                            <th class="px-4 font-medium">Transactions</th>
                            <th class="px-4 font-medium">Gross</th>
                            <th class="px-4 font-medium">Commission</th>
                            <th class="px-4 font-medium">Net</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($commission as $studio => $row)
                            <tr class="h-[59px] border-b border-[#222224] last:border-0">
                                <td class="px-4 font-medium">{{ $studio }}</td>
                                <td class="px-4">{{ number_format($row['count']) }}</td>
                                <td class="px-4">{{ \App\Support\Money::format($row['gross']) }}</td>
                                <td class="px-4">{{ \App\Support\Money::format($row['commission']) }}</td>
                                <td class="px-4">{{ \App\Support\Money::format($row['net']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @else
        <form method="GET" action="{{ route('admin.reports.index') }}" class="mt-4 flex flex-wrap items-end gap-3">
            <input type="hidden" name="tab" value="transactions">
            <label class="flex flex-col gap-1 text-sm text-subtle">Month
                <input type="month" name="month" value="{{ $month }}" class="input w-auto">
            </label>
            <label class="flex flex-col gap-1 text-sm text-subtle">Status
                <select name="status" class="input w-auto">
                    <option value="">All statuses</option>
                    @foreach (['pending', 'success', 'failed', 'expired'] as $s)
                        <option value="{{ $s }}" @selected($status === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </label>
            <button class="btn-primary">Apply</button>
        </form>
        @error('month')<p class="mt-2 text-sm text-danger">{{ $message }}</p>@enderror
        @error('status')<p class="mt-2 text-sm text-danger">{{ $message }}</p>@enderror

        <div class="card mt-4 overflow-hidden">
            @if ($transactions->isEmpty())
                <p class="p-5 text-subtle">No transactions found</p>
            @else
                <table class="w-full text-left text-[15px]">
                    <thead>
                        <tr class="h-[33px] border-b border-[#222224] text-[11px] font-medium uppercase text-white/50">
                            <th class="px-4 font-medium">Order</th>
                            <th class="px-4 font-medium">Buyer</th>
                            <th class="px-4 font-medium">Game</th>
                            <th class="px-4 font-medium">Status</th>
                            <th class="px-4 font-medium">Settled</th>
                            <th class="px-4 font-medium">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transactions as $trx)
                            <tr class="h-[59px] border-b border-[#222224] last:border-0">
                                <td class="px-4">{{ $trx->order_id }}</td>
                                <td class="px-4">{{ $trx->user?->name ?? '—' }}</td>
                                <td class="px-4">{{ $trx->game?->name ?? '—' }}</td>
                                <td class="px-4"><x-status-pill :status="$trx->status" /></td>
                                <td class="px-4 text-subtle">{{ $trx->pembayaran?->paid_at?->format('d M Y H:i') ?? '—' }}</td>
                                <td class="px-4">{{ \App\Support\Money::format($trx->total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-border font-medium">
                            <td colspan="5" class="px-4 py-3 text-right text-subtle">Total</td>
                            <td class="px-4">{{ \App\Support\Money::format($total) }}</td>
                        </tr>
                    </tfoot>
                </table>
            @endif
        </div>
        @if ($transactions)
            <div class="mt-4">{{ $transactions->links() }}</div>
        @endif
    @endif
</x-portal-layout>
