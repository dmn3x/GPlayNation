<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * SRS 3.1.1 — Registrasi Akun, Login, Kelola Profil.
 */
class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        // 3.1.1.2 alternate flow: unverified developer accounts cannot log in.
        if (! $user || ! Auth::validate($credentials)) {
            return back()->withErrors(['email' => __('auth.failed')]);
        }

        if ($user->status === 'pending') {
            return back()->withErrors(['email' => 'Akun Anda masih menunggu verifikasi Admin.']);
        }

        if ($user->status === 'rejected') {
            return back()->withErrors(['email' => 'Akun Anda ditolak.']);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended($this->homeFor($user))
            ->with('gpn_anim', 'login');
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', 'in:user,developer'],
            'nama_studio' => ['nullable', 'required_if:role,developer', 'string', 'max:120'],
        ]);

        $user = DB::transaction(function () use ($data) {
            // SRS 3.1.1.1 post-condition: user accounts activate immediately,
            // developer accounts wait for admin verification.
            $user = User::create([
                'name' => $data['name'],
                'pengguna_nama' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => $data['role'],
                'status' => $data['role'] === 'developer' ? 'pending' : 'active',
            ]);

            if ($data['role'] === 'developer') {
                $user->developer()->create([
                    'nama_developer' => $data['name'],
                    'nama_studio' => $data['nama_studio'],
                ]);
            }

            return $user;
        });

        if ($user->status === 'pending') {
            return redirect()->route('login')->with(
                'status',
                'Pendaftaran berhasil. Akun Developer menunggu verifikasi Admin.'
            );
        }

        Auth::login($user);

        return redirect()->route('home')->with('gpn_anim', 'account-created');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /** SRS 3.1.1.3 Kelola Profil */
    public function showProfile(): View
    {
        $user = Auth::user();

        return view('profile.show', [
            'user' => $user,
            'library' => $user->library()->with('game')->orderByDesc('jam_main')->get(),
            'purchased' => $user->transaksis()
                ->where('transaksi_status', 'settlement')
                ->with('game')
                ->latest('transaksi_tanggal')
                ->get(),
            'wishlist' => $user->wishlist()->with('game')->latest()->get(),
            'stats' => [
                'assets' => $user->library()->count(),
                'slots' => $user->library()->where('jam_main', '>', 0)->count(),
                'totalHours' => (float) $user->library()->sum('jam_main'),
            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->update([
            'name' => $data['name'],
            'pengguna_nama' => $data['name'],
            'email' => $data['email'],
        ]);

        $user->developer?->update(['nama_developer' => $data['name']]);

        return back()->with('status', 'Profil berhasil diperbarui.');
    }

    private function homeFor(User $user): string
    {
        return match ($user->role) {
            'admin' => route('admin.dashboard'),
            'developer' => route('developer.dashboard'),
            default => route('home'),
        };
    }
}