@extends('layouts.settings')

@section('settings-title', 'Email Verification (OTP)')
@section('settings-heading', 'Email OTP Verification')
@section('settings-subheading', 'Protect your account using email verification codes sent to your registered inbox during authentication.')

@section('settings-content')
@php
    $isEnabled = $user && method_exists($user, 'hasEnabledEmailOtp') && $user->hasEnabledEmailOtp();
@endphp

@if (session('status'))
    <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-800 rounded-r-md text-sm">
        {{ session('status') }}
    </div>
@endif

<x-settings-card 
    title="Email One-Time Passcode (OTP)" 
    description="Verification codes sent to your primary email address for multi-factor authentication."
    icon="mail"
    :badge="$isEnabled ? 'Active' : 'Disabled'"
    :badgeClass="$isEnabled ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600'"
>
    <x-slot:headerActions>
        <form action="{{ route('multi-2fa.settings.email-otp.update') }}" method="POST">
            @csrf
            <input type="hidden" name="enable" value="{{ $isEnabled ? '0' : '1' }}">
            @if ($isEnabled)
                <button type="submit" 
                        class="px-3.5 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 text-xs font-semibold rounded-lg border border-red-200 transition">
                    Disable Email OTP
                </button>
            @else
                <button type="submit" 
                        class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                    Enable Email OTP
                </button>
            @endif
        </form>
    </x-slot:headerActions>

    <div class="space-y-6">
        <div class="flex items-start gap-4 p-4 rounded-xl border border-gray-200 {{ $isEnabled ? 'bg-emerald-50/40 border-emerald-200' : 'bg-gray-50/50' }}">
            <div class="p-2.5 rounded-lg {{ $isEnabled ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-600' }} flex-shrink-0">
                <i data-feather="{{ $isEnabled ? 'check-circle' : 'info' }}" class="w-5 h-5"></i>
            </div>
            <div class="space-y-1">
                <h3 class="text-sm font-semibold text-gray-900">
                    {{ $isEnabled ? 'Email OTP Authentication is Currently Active' : 'Email OTP Authentication is Currently Disabled' }}
                </h3>
                <p class="text-xs text-gray-600 leading-relaxed">
                    @if ($isEnabled)
                        When you sign in, a fresh 6-digit one-time passcode will be emailed to <strong class="text-gray-900 font-mono">{{ $user->email }}</strong>. You will need to enter this code to complete authentication.
                    @else
                        When disabled, you will not receive one-time passcodes to your email address during sign-in. If other methods (Passkeys or Authenticator) are enabled, they will be used instead.
                    @endif
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-gray-600 pt-2 border-t border-gray-100">
            <div class="p-3.5 bg-gray-50 rounded-lg border border-gray-200 space-y-1">
                <span class="font-semibold text-gray-800 block">Configured Recipient</span>
                <span class="font-mono text-gray-700">{{ $user->email }}</span>
                <p class="text-[11px] text-gray-500 mt-1">To change this email, visit the Profile tab.</p>
            </div>

            <div class="p-3.5 bg-gray-50 rounded-lg border border-gray-200 space-y-1">
                <span class="font-semibold text-gray-800 block">OTP Code Expiration</span>
                <span class="text-gray-700">10 Minutes</span>
                <p class="text-[11px] text-gray-500 mt-1">Codes expire automatically after 10 minutes for security.</p>
            </div>
        </div>
    </div>
</x-settings-card>
@endsection
