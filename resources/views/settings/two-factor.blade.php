<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication - Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen p-6 flex justify-center">
    <div class="max-w-2xl w-full bg-white rounded-2xl shadow border border-slate-100 p-8 space-y-6">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Two-Factor Authentication</h2>
            <p class="text-xs text-slate-500 mt-1">Add additional security to your account using TOTP two-factor authentication.</p>
        </div>

        <div class="pt-4 border-t border-slate-100">
            @if (!empty($twoFactorEnabled))
                <div class="flex items-center gap-2 mb-4">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">Enabled</span>
                </div>
                <p class="text-sm text-slate-600 mb-6">
                    Two-factor authentication is currently active on your account. When signing in, you will be required to provide a 6-digit authentication token.
                </p>

                <form action="/user/two-factor-authentication" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="py-2.5 px-4 bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs rounded-xl shadow transition">
                        Disable Two-Factor Authentication
                    </button>
                </form>
            @else
                <div class="flex items-center gap-2 mb-4">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-200 text-slate-700">Disabled</span>
                </div>
                <p class="text-sm text-slate-600 mb-6">
                    When you enable two-factor authentication, you will be prompted for a secure passcode during login retrieved from an authenticator app (Google Authenticator, Authy, etc.).
                </p>

                <form action="/user/two-factor-authentication" method="POST">
                    @csrf
                    <button type="submit" class="py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs rounded-xl shadow transition">
                        Enable Two-Factor Authentication
                    </button>
                </form>
            @endif
        </div>
    </div>
</body>
</html>
