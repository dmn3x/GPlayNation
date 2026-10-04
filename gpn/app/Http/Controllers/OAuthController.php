<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * SRS 2.6.3 — Google / Apple OAuth via REST API.
 *
 * Real credential exchange is stubbed until OAuth clients are registered; the
 * route contract (redirect → callback → session) is complete so wiring the
 * provider SDK later requires no view or route changes.
 */
class OAuthController extends Controller
{
    public function redirect(string $provider)
    {
        abort_unless(in_array($provider, ['google', 'apple'], true), 404);

        return view('auth.oauth', [
            'provider' => $provider,
            'authorizeUrl' => route('oauth.callback', $provider),
        ]);
    }

    public function callback(Request $request, string $provider)
    {
        abort_unless(in_array($provider, ['google', 'apple'], true), 404);

        // Placeholder identity — replace with the provider's verified token payload.
        $email = $request->string('email')->toString()
            ?: $provider.'-'.substr(sha1($request->ip()), 0, 12).'@oauth.local';

        $user = \App\Models\User::firstOrCreate(
            ['email' => $email],
            ['name' => $request->string('name')->toString() ?: 'Player', 'pengguna_nama' => 'Player', 'role' => 'user'],
        );

        Auth::login($user, true);

        return redirect()->route('home')->with('status', "Berhasil masuk lewat {$provider}.");
    }
}