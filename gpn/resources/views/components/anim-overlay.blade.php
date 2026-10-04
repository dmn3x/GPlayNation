{{-- Full-screen transition overlays traced from the Figma "Home Screen",
     "Loading Animation Open App", "Login Animation", and
     "Account Created Animation" sections (nodes 209:5, 174:4541, 194:384, 178:4843).
     Shown once per flash: @session('gpn_anim') or ?anim= for preview. --}}
@php
    $anim = session('gpn_anim') ?? request()->query('anim');
@endphp
@if (in_array($anim, ['loading', 'login', 'account-created'], true))
<div id="gpn-anim-overlay" class="gpn-anim-overlay" aria-live="polite">
    <div class="gpn-anim-stage">
        @if ($anim === 'account-created')
            <div class="gpn-anim-check">
                <svg viewBox="0 0 52 52" class="h-24 w-24">
                    <circle class="gpn-anim-check-circle" cx="26" cy="26" r="24" fill="none" stroke="#30D158" stroke-width="3"/>
                    <path class="gpn-anim-check-mark" fill="none" stroke="#30D158" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" d="M14 27l8 8 16-17"/>
                </svg>
            </div>
            <p class="mt-8 text-2xl font-semibold text-white">Account Created</p>
            <p class="mt-2 text-sm text-gpn-fg-subtle">Selamat datang di GPlayNation</p>
        @else
            <div class="gpn-anim-logo {{ $anim === 'login' ? 'gpn-anim-logo--spin-ring' : '' }}">
                <x-logo class="h-20 w-auto text-white" />
            </div>
            @if ($anim === 'login')
                <p class="mt-8 text-2xl font-semibold text-white">Welcome back</p>
                <div class="gpn-anim-bar mt-6"><span></span></div>
            @else
                <p class="mt-8 text-2xl font-semibold text-white">GPlayNation</p>
                <div class="gpn-anim-bar mt-6"><span></span></div>
            @endif
        @endif
    </div>
</div>
@endif
