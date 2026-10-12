<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VerifyRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class VerificationController extends Controller
{
    public function index(): View
    {
        $tab = request('tab', 'pending');
        $status = $tab === 'rejected' ? UserStatus::Rejected : UserStatus::Pending;

        return view('admin.verification.index', [
            'tab' => $tab,
            'developers' => User::where('role', 'developer')
                ->where('status', $status)
                ->with('developer')
                ->latest()
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function update(VerifyRequest $request, User $user): RedirectResponse // SRS 3.1.1.4
    {
        abort_unless($user->role->value === 'developer', 404);
        $data = $request->validated();

        if ($data['action'] === 'approve') {
            $user->update(['status' => UserStatus::Active, 'rejection_reason' => null]);

            return back()->with('success', 'Developer approved.');
        }

        $user->update(['status' => UserStatus::Rejected, 'rejection_reason' => $data['reason']]);

        return back()->with('success', 'Developer rejected.');
    }
}
