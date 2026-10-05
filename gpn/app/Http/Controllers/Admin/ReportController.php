<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request, ReportService $reports): View // SRS 3.1.5.3/4/5
    {
        $validated = $request->validate([
            'tab' => ['nullable', Rule::in(['transactions', 'commission'])],
            'month' => ['nullable', 'date_format:Y-m'],
            'status' => ['nullable', Rule::in(['pending', 'success', 'failed', 'expired'])],
        ]);

        $tab = $validated['tab'] ?? 'transactions';
        $monthParam = $validated['month'] ?? null;
        $status = $validated['status'] ?? null;

        if ($tab === 'commission') {
            $month = Carbon::parse(($monthParam ?? now()->format('Y-m')).'-01');

            return view('admin.reports', [
                'tab' => $tab,
                'month' => $month->format('Y-m'),
                'status' => $status,
                'transactions' => null,
                'total' => '0',
                'commission' => $reports->adminCommission($month),
            ]);
        }

        $transactions = $reports->adminTransactions(['status' => $status, 'month' => $monthParam]);

        $total = (string) Transaksi::query()
            ->when($status, fn ($q, $s) => $q->where('status', $s))
            ->when($monthParam, function ($q) use ($monthParam) {
                $m = Carbon::parse($monthParam.'-01');
                $q->where(function ($qq) use ($m) {
                    $qq->whereHas('pembayaran', fn ($p) => $p->whereBetween('paid_at', [$m->copy()->startOfMonth(), $m->copy()->endOfMonth()]))
                        ->orWhere(fn ($n) => $n->where('status', '!=', 'success')->whereBetween('transaksis.created_at', [$m->copy()->startOfMonth(), $m->copy()->endOfMonth()]));
                });
            })
            ->sum('total');

        return view('admin.reports', [
            'tab' => $tab,
            'month' => $monthParam,
            'status' => $status,
            'transactions' => $transactions,
            'total' => $total,
            'commission' => collect(),
        ]);
    }
}
