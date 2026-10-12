<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Verification Method - Two-Factor Authentication</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl border border-slate-100 p-8">
        <div class="text-center mb-6">
            <div class="w-12 h-12 bg-emerald-100 text-emerald-700 rounded-xl flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-slate-900">Verification Required</h2>
            <p class="text-xs text-slate-500 mt-1">Choose a verification method to complete your sign-in.</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-lg">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div id="passkey-error" class="hidden mb-4 p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-lg text-center"></div>

        <div class="space-y-3">
            {{-- Passkey Option --}}
            @if (in_array('passkey', $availableMethods ?? ['passkey', 'email', 'authenticator']))
                <button
                    type="button"
                    id="btn-passkey"
                    class="w-full flex items-start gap-4 p-4 rounded-xl border border-slate-200 hover:border-emerald-600 hover:bg-emerald-50/40 text-left transition duration-200 group"
                >
                    <div class="p-2.5 bg-emerald-100 text-emerald-800 rounded-lg group-hover:scale-105 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 004.07 9.873"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">Passkey / Biometrics</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Use Touch ID, Face ID, or your security key.</p>
                    </div>
                </button>
            @endif

            {{-- Email OTP Option --}}
            @if (in_array('email', $availableMethods ?? ['passkey', 'email', 'authenticator']))
                <form action="{{ route('multi-2fa.challenge.select') }}" method="POST">
                    @csrf
                    <input type="hidden" name="method" value="email">
                    <button
                        type="submit"
                        class="w-full flex items-start gap-4 p-4 rounded-xl border border-slate-200 hover:border-emerald-600 hover:bg-emerald-50/40 text-left transition duration-200 group"
                    >
                        <div class="p-2.5 bg-emerald-100 text-emerald-800 rounded-lg group-hover:scale-105 transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-800">Email Verification Code</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Receive a 6-digit one-time passcode by email.</p>
                        </div>
                    </button>
                </form>
            @endif

            {{-- Authenticator TOTP Option --}}
            @if (in_array('authenticator', $availableMethods ?? ['passkey', 'email', 'authenticator']))
                <form action="{{ route('multi-2fa.challenge.select') }}" method="POST">
                    @csrf
                    <input type="hidden" name="method" value="authenticator">
                    <button
                        type="submit"
                        class="w-full flex items-start gap-4 p-4 rounded-xl border border-slate-200 hover:border-emerald-600 hover:bg-emerald-50/40 text-left transition duration-200 group"
                    >
                        <div class="p-2.5 bg-emerald-100 text-emerald-800 rounded-lg group-hover:scale-105 transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-800">Authenticator App (2FA)</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Enter code from Google Authenticator or similar app.</p>
                        </div>
                    </button>
                </form>
            @endif
        </div>
    </div>

    <script>
        const passkeyBtn = document.getElementById('btn-passkey');
        if (passkeyBtn && window.PublicKeyCredential) {
            passkeyBtn.addEventListener('click', async () => {
                const errBox = document.getElementById('passkey-error');
                errBox.classList.add('hidden');
                try {
                    // Laravel Passkeys WebAuthn options endpoint
                    const res = await fetch('/passkeys/authenticate-options', {
                        headers: { 'Accept': 'application/json' }
                    });
                    if (!res.ok) throw new Error('Could not initiate passkey authentication.');
                    const options = await res.json();
                    
                    // Simple base64 decoding helper
                    const decodeBase64 = (s) => Uint8Array.from(atob(s.replace(/-/g, '+').replace(/_/g, '/')), c => c.charCodeAt(0));
                    options.challenge = decodeBase64(options.challenge);
                    if (options.allowCredentials) {
                        options.allowCredentials = options.allowCredentials.map(c => ({ ...c, id: decodeBase64(c.id) }));
                    }

                    const credential = await navigator.credentials.get({ publicKey: options });
                    if (!credential) return;

                    const encodeBase64 = (buf) => btoa(String.fromCharCode(...new Uint8Array(buf))).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
                    
                    const verifyRes = await fetch('/passkeys/authenticate', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            id: credential.id,
                            rawId: encodeBase64(credential.rawId),
                            type: credential.type,
                            response: {
                                clientDataJSON: encodeBase64(credential.response.clientDataJSON),
                                authenticatorData: encodeBase64(credential.response.authenticatorData),
                                signature: encodeBase64(credential.response.signature),
                                userHandle: credential.response.userHandle ? encodeBase64(credential.response.userHandle) : null
                            }
                        })
                    });

                    if (verifyRes.ok) {
                        window.location.href = '{{ config("multi-2fa.home") ?? config("fortify.home", "/dashboard") }}';
                    } else {
                        throw new Error('Passkey verification failed.');
                    }
                } catch (e) {
                    if (e.name !== 'NotAllowedError') {
                        errBox.textContent = e.message || 'Passkey authentication failed.';
                        errBox.classList.remove('hidden');
                    }
                }
            });
        }
    </script>
</body>
</html>
