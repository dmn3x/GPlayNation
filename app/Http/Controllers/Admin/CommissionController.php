<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CommissionRequest;
use App\Models\Komisi;
use App\Services\CommissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CommissionController extends Controller
{
    public function index(CommissionService $commissions): View // SRS 3.1.4.2
    {
        return view('admin.commission', [
            'currentRate' => $commissions->percentageFor(now()),
            'schedule' => Komisi::latest('effective_from')->latest('id')->paginate(20)->withQueryString(),
        ]);
    }

    public function store(CommissionRequest $request): RedirectResponse
    {
        Komisi::create([
            'admin_id' => $request->user()->id,
            'percentage' => $request->validated('percentage'),
            'effective_from' => $request->validated('effective_from'),
        ]);

        return back()->with('success', 'Commission rate scheduled.');
    }
}
