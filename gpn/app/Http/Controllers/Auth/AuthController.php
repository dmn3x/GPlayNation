<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Developer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'These credentials do not match our records.'])->onlyInput('email');
        }

        $user = $request->user();

        if ($user->isDeveloper() && $user->status === UserStatus::Pending) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->with('error', 'Your developer account is still awaiting admin verification.');
        }

        if ($user->isDeveloper() && $user->status === UserStatus::Rejected) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->with('error', 'Your developer account was rejected: ' . ($user->rejection_reason ?? 'no reason given'));
        }

        $request->session()->regenerate();

        return redirect()->intended(match (true) {
            $user->isAdmin() => '/admin',
            $user->isDeveloper() => '/dev',
            default => route('home'),
        });
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request): RedirectResponse // SRS 3.1.1.1 + 3-2.5
    {
        $data = $request->validated();
        $isDev = $data['account_type'] === 'developer';

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $isDev ? Role::Developer : Role::User,
            'status' => $isDev ? UserStatus::Pending : UserStatus::Active,
        ]);

        if ($isDev) {
            Developer::create([
                'user_id' => $user->id,
                'studio_name' => $data['studio_name'],
                'portfolio' => $data['portfolio'] ?? null,
            ]);

            return redirect()->route('login')->with('account_created', 'developer');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')->with('account_created', 'user');
    }

    public function logout(): RedirectResponse
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('home');
    }
}
