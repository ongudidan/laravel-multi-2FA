<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passkeys - Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen p-6 flex justify-center">
    <div class="max-w-2xl w-full bg-white rounded-2xl shadow border border-slate-100 p-8 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-900">Passkey Authentication</h2>
                <p class="text-xs text-slate-500 mt-1">Sign in instantly using Touch ID, Face ID, or hardware security keys.</p>
            </div>
            <button
                type="button"
                id="btn-register-passkey"
                class="py-2 px-3 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs rounded-xl shadow transition"
            >
                + Add Passkey
            </button>
        </div>

        @if (session('status'))
            <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs rounded-lg">
                {{ session('status') }}
            </div>
        @endif

        <div id="passkey-status-msg" class="hidden p-3 rounded-lg text-xs"></div>

        <div class="pt-4 border-t border-slate-100 space-y-3">
            @forelse ($passkeys ?? [] as $passkey)
                <div class="flex items-center justify-between p-4 rounded-xl border border-slate-200 hover:border-slate-300 transition">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-sm text-slate-800">{{ $passkey['name'] }}</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] bg-emerald-100 text-emerald-800">Active</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">
                            Added {{ $passkey['created_at'] }} • Last used: {{ $passkey['last_used_at'] }}
                        </p>
                    </div>

                    <form action="{{ route('multi-2fa.settings.passkeys.destroy', $passkey['id']) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-slate-400 hover:text-rose-600 transition p-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </form>
                </div>
            @empty
                <div class="text-center py-8 border border-dashed border-slate-200 rounded-xl">
                    <p class="text-xs text-slate-500">No passkeys registered yet. Click "+ Add Passkey" above to register your first device.</p>
                </div>
            @endforelse
        </div>
    </div>

    <script>
        const registerBtn = document.getElementById('btn-register-passkey');
        if (registerBtn && window.PublicKeyCredential) {
            registerBtn.addEventListener('click', async () => {
                const name = prompt('Enter a name for this device/passkey (e.g. My MacBook):');
                if (!name || !name.trim()) return;

                const msgBox = document.getElementById('passkey-status-msg');
                msgBox.className = 'hidden';

                try {
                    const res = await fetch('/passkeys/register-options', {
                        headers: { 'Accept': 'application/json' }
                    });
                    if (!res.ok) throw new Error('Could not fetch registration options.');
                    const options = await res.json();

                    const decodeBase64 = (s) => Uint8Array.from(atob(s.replace(/-/g, '+').replace(/_/g, '/')), c => c.charCodeAt(0));
                    options.challenge = decodeBase64(options.challenge);
                    options.user.id = decodeBase64(options.user.id);
                    if (options.excludeCredentials) {
                        options.excludeCredentials = options.excludeCredentials.map(c => ({ ...c, id: decodeBase64(c.id) }));
                    }

                    const credential = await navigator.credentials.create({ publicKey: options });
                    if (!credential) return;

                    const encodeBase64 = (buf) => btoa(String.fromCharCode(...new Uint8Array(buf))).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
                    
                    const saveRes = await fetch('/passkeys', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            name: name.trim(),
                            id: credential.id,
                            rawId: encodeBase64(credential.rawId),
                            type: credential.type,
                            response: {
                                clientDataJSON: encodeBase64(credential.response.clientDataJSON),
                                attestationObject: encodeBase64(credential.response.attestationObject)
                            }
                        })
                    });

                    if (saveRes.ok) {
                        window.location.reload();
                    } else {
                        throw new Error('Failed to save passkey on server.');
                    }
                } catch (e) {
                    if (e.name !== 'NotAllowedError') {
                        msgBox.textContent = e.message || 'Passkey creation failed.';
                        msgBox.className = 'p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-lg';
                    }
                }
            });
        }
    </script>
</body>
</html>
