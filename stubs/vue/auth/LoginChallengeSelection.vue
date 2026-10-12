<script setup lang="ts">
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Passkeys, PasskeyError } from '@laravel/passkeys';
import { Fingerprint, Mail, Shield } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

const props = defineProps<{
    availableMethods?: string[];
}>();

const page = usePage();
const form = useForm({
    method: '',
});

const isPasskeySupported = ref<boolean>(false);
const isAuthenticatingPasskey = ref<boolean>(false);
const passkeyError = ref<string | null>(null);

const isEmailAvailable = computed(() => {
    return props.availableMethods ? props.availableMethods.includes('email') : true;
});

const isAuthenticatorAvailable = computed(() => {
    return props.availableMethods ? props.availableMethods.includes('authenticator') : true;
});

onMounted(() => {
    const isPasskeyEnabledGlobally = (page.props.security_settings as any)?.passkey_enabled ?? true;
    const userHasPasskey = props.availableMethods ? props.availableMethods.includes('passkey') : true;
    isPasskeySupported.value = Passkeys.isSupported() && isPasskeyEnabledGlobally && userHasPasskey;
});

const selectMethod = (method: 'email' | 'authenticator') => {
    form.method = method;
    form.post('/login-challenge-selection');
};

const loginWithPasskey = async () => {
    passkeyError.value = null;
    isAuthenticatingPasskey.value = true;
    try {
        await Passkeys.verify();
        window.location.href = '/dashboard';
    } catch (err: any) {
        if (err instanceof PasskeyError) {
            if (err.name !== 'UserCancelledError') {
                passkeyError.value = err.message || 'Passkey verification failed.';
            }
        } else {
            passkeyError.value = err?.message || 'Could not verify passkey.';
        }
    } finally {
        isAuthenticatingPasskey.value = false;
    }
};
</script>

<template>
    <AuthLayout
        title="Verification Required"
        description="Choose a verification method to complete your login."
    >
        <Head title="Select Verification Method" />

        <div class="space-y-4">
            <!-- Passkey Option -->
            <button
                v-if="isPasskeySupported"
                type="button"
                @click="loginWithPasskey"
                :disabled="form.processing || isAuthenticatingPasskey"
                class="flex w-full cursor-pointer items-start gap-4 rounded-xl border border-slate-200 bg-slate-50/50 p-4 text-left transition-all duration-200 hover:border-emerald-600 hover:bg-emerald-50/30 active:scale-98 disabled:opacity-50"
            >
                <div
                    class="rounded-lg bg-emerald-100 p-2.5 text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-400"
                >
                    <Fingerprint class="h-6 w-6" />
                </div>
                <div>
                    <h3
                        class="text-sm font-bold text-slate-800 dark:text-slate-200"
                    >
                        Passkey / Biometrics
                    </h3>
                    <p
                        class="mt-1 text-xs leading-normal text-slate-500 dark:text-slate-400"
                    >
                        Authenticate using Touch ID, Face ID, or your hardware security key.
                    </p>
                </div>
            </button>

            <!-- Email Option -->
            <button
                v-if="isEmailAvailable"
                type="button"
                @click="selectMethod('email')"
                :disabled="form.processing || isAuthenticatingPasskey"
                class="flex w-full cursor-pointer items-start gap-4 rounded-xl border border-slate-200 bg-slate-50/50 p-4 text-left transition-all duration-200 hover:border-emerald-600 hover:bg-emerald-50/30 active:scale-98 disabled:opacity-50"
            >
                <div
                    class="rounded-lg bg-emerald-100 p-2.5 text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-400"
                >
                    <Mail class="h-6 w-6" />
                </div>
                <div>
                    <h3
                        class="text-sm font-bold text-slate-800 dark:text-slate-200"
                    >
                        Email Verification Code
                    </h3>
                    <p
                        class="mt-1 text-xs leading-normal text-slate-500 dark:text-slate-400"
                    >
                        Receive a 6-digit one-time passcode sent directly to
                        your registered email address.
                    </p>
                </div>
            </button>

            <!-- Authenticator Option -->
            <button
                v-if="isAuthenticatorAvailable"
                type="button"
                @click="selectMethod('authenticator')"
                :disabled="form.processing || isAuthenticatingPasskey"
                class="flex w-full cursor-pointer items-start gap-4 rounded-xl border border-slate-200 bg-slate-50/50 p-4 text-left transition-all duration-200 hover:border-emerald-600 hover:bg-emerald-50/30 active:scale-98 disabled:opacity-50"
            >
                <div
                    class="rounded-lg bg-emerald-100 p-2.5 text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-400"
                >
                    <Shield class="h-6 w-6" />
                </div>
                <div>
                    <h3
                        class="text-sm font-bold text-slate-800 dark:text-slate-200"
                    >
                        Authenticator App (2FA)
                    </h3>
                    <p
                        class="mt-1 text-xs leading-normal text-slate-500 dark:text-slate-400"
                    >
                        Use the 6-digit verification code generated by your TOTP
                        authenticator application (e.g. Google Authenticator).
                    </p>
                </div>
            </button>

            <div v-if="passkeyError" class="text-xs text-rose-500 text-center font-medium">
                {{ passkeyError }}
            </div>
        </div>
    </AuthLayout>
</template>
