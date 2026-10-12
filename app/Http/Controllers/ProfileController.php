<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Library;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View // SRS 4.1
    {
        $user = auth()->user()->load(['libraries.game', 'wishlists.game', 'iapOwnerships.iap']);

        return view('storefront.profile', [
            'user' => $user,
            'showcased' => $user->libraries->where('is_showcased', true),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse // SRS 3.1.1.3
    {
        $user = $request->user();
        $data = $request->safe()->only(['name']);

        if ($request->hasFile('avatar')) {
            $data['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
        }

        if ($request->filled('password')) {
            $data['password'] = $request->input('password');
        }

        $user->update($data);

        return redirect()->route('profile.show')->with('success', 'Profile updated.');
    }

    public function showcase(Library $library): RedirectResponse // SRS 4.1
    {
        abort_unless($library->user_id === auth()->id(), 403);
        $library->update(['is_showcased' => ! $library->is_showcased]);

        return back()->with('success', 'Showcase updated.');
    }
}
