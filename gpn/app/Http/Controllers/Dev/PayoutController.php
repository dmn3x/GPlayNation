<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Services\PayoutService;
use Illuminate\View\View;

class PayoutController extends Controller
{
    public function index(PayoutService $payouts): View
    {
        $developer = auth()->user()->developer;

        return view('developer.payouts', [
            'balance' => $payouts->unpaidBalance($developer),
            'rows' => $payouts->unpaidRows($developer),
            'payouts' => $developer->payouts()->latest()->get(),
        ]);
    }
}
