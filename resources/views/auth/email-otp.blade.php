<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - Two-Factor Authentication</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl border border-slate-100 p-8">
        <div class="text-center mb-6">
            <div class="w-12 h-12 bg-emerald-100 text-emerald-700 rounded-xl flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-slate-900">Email Verification</h2>
            <p class="text-xs text-slate-500 mt-1">We sent a 6-digit verification code to your email address.</p>
        </div>

        @if (session('success'))
            <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs rounded-lg text-center">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-lg">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('multi-2fa.email-otp.verify') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="code" class="block text-xs font-semibold text-slate-600 mb-1 text-center">Verification Code</label>
                <input
                    type="text"
                    id="code"
                    name="code"
                    maxlength="6"
                    pattern="[0-9]{6}"
                    inputmode="numeric"
                    required
                    autofocus
                    placeholder="••••••"
                    class="w-full text-center text-2xl tracking-widest font-mono py-3 px-4 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition"
                />
            </div>

            <button
                type="submit"
                class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl shadow transition duration-200"
            >
                Verify & Continue
            </button>
        </form>

        <div class="mt-6 flex flex-col items-center gap-3 text-xs text-slate-500">
            <form action="{{ route('multi-2fa.email-otp.resend') }}" method="POST">
                @csrf
                <span>Didn't receive the code? </span>
                <button type="submit" class="text-emerald-600 hover:text-emerald-700 font-semibold underline">
                    Resend Code
                </button>
            </form>

            @if (!empty($canSwitch))
                <div class="pt-3 border-t border-slate-100 w-full text-center">
                    <a href="{{ route('multi-2fa.challenge.show') }}" class="text-slate-500 hover:text-emerald-600 transition">
                        ← Choose a different verification method
                    </a>
                </div>
            @endif
        </div>
    </div>
</body>
</html>
