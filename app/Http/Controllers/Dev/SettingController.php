<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dev\SettingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('developer.settings', ['developer' => auth()->user()->developer]);
    }

    public function update(SettingRequest $request): RedirectResponse
    {
        auth()->user()->developer->update($request->validated());

        return back()->with('success', 'Studio settings updated.');
    }
}
