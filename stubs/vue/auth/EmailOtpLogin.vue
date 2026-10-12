<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    PinInput,
    PinInputGroup,
    PinInputSlot,
} from '@/components/ui/pin-input';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    canSwitch?: boolean;
}>();

const code = ref<number[]>([]);
const codeValue = computed<string>(() => code.value.join(''));

const form = useForm({
    code: '',
});

const resending = ref(false);

const submit = () => {
    form.code = codeValue.value;
    form.post('/email-otp-login', {
        onError: () => {
            code.value = [];
        },
    });
};

const resendCode = () => {
    resending.value = true;
    router.post(
        '/email-otp-login/resend',
        {},
        {
            onFinish: () => {
                resending.value = false;
            },
        },
    );
};
</script>

<template>
    <AuthLayout
        title="Email Verification"
        description="We sent a 6-digit verification code to your email address. Please enter it below to proceed."
    >
        <Head title="Email OTP Verification" />

        <div class="space-y-6">
            <form @submit.prevent="submit" class="space-y-4">
                <input type="hidden" name="code" :value="codeValue" />
                <div
                    class="flex flex-col items-center justify-center space-y-3 text-center"
                >
                    <div class="flex w-full items-center justify-center">
                        <PinInput
                            id="otp"
                            placeholder="○"
                            v-model="code"
                            type="number"
                            otp
                        >
                            <PinInputGroup>
                                <PinInputSlot
                                    v-for="(id, index) in 6"
                                    :key="id"
                                    :index="index"
                                    :disabled="form.processing"
                                    autofocus
                                />
                            </PinInputGroup>
                        </PinInput>
                    </div>
                    <InputError :message="form.errors.code" />
                </div>

                <Button
                    type="submit"
                    class="w-full"
                    :disabled="form.processing || codeValue.length < 6"
                >
                    Verify Code
                </Button>

                <div class="text-center text-sm text-muted-foreground">
                    <span>Didn't receive the code? </span>
                    <button
                        type="button"
                        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                        @click="resendCode"
                        :disabled="resending"
                    >
                        {{ resending ? 'Resending...' : 'Resend Code' }}
                    </button>
                </div>

                <div
                    v-if="canSwitch"
                    class="mt-4 border-t border-slate-100 pt-4 text-center text-sm dark:border-slate-800"
                >
                    <button
                        type="button"
                        class="cursor-pointer text-xs font-medium text-slate-600 transition-colors duration-200 hover:text-emerald-600 active:scale-95 dark:text-slate-400 dark:hover:text-emerald-400"
                        @click="router.get('/login-challenge-selection')"
                    >
                        ← Choose a different verification method
                    </button>
                </div>
            </form>
        </div>
    </AuthLayout>
</template>
