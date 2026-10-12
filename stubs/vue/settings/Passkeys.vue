<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { BreadcrumbItem } from '@/types';
import { Passkeys, PasskeyError } from '@laravel/passkeys';
import { Head, router } from '@inertiajs/vue3';
import { Fingerprint, Key, KeyRound, Laptop, Plus, ShieldCheck, Smartphone, Trash2 } from 'lucide-vue-next';
import { onMounted, ref, watch } from 'vue';

interface PasskeyItem {
    id: number;
    name: string;
    created_at: string;
    created_at_formatted: string;
    last_used_at: string;
    last_used_at_formatted: string;
}

const props = defineProps<{
    passkeys: PasskeyItem[];
    status?: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Passkeys',
        href: '/settings/passkeys',
    },
];

const isSupported = ref<boolean>(true);
const isRegistering = ref<boolean>(false);
const showAddModal = ref<boolean>(false);
const passkeyName = ref<string>('');
const modalErrorMessage = ref<string | null>(null);
const pageErrorMessage = ref<string | null>(null);
const successMessage = ref<string | null>(props.status || null);
const passkeyToDelete = ref<PasskeyItem | null>(null);
const isDeleting = ref<boolean>(false);

onMounted(() => {
    isSupported.value = Passkeys.isSupported();
});

// Reset state when modal closes
watch(showAddModal, (isOpen) => {
    if (!isOpen) {
        passkeyName.value = '';
        modalErrorMessage.value = null;
        isRegistering.value = false;
    }
});

const getDeviceIcon = (name: string) => {
    const lower = name.toLowerCase();
    if (lower.includes('phone') || lower.includes('iphone') || lower.includes('android') || lower.includes('mobile')) {
        return Smartphone;
    }
    if (lower.includes('mac') || lower.includes('pc') || lower.includes('laptop') || lower.includes('desktop') || lower.includes('windows')) {
        return Laptop;
    }
    return Key;
};

const handleRegisterPasskey = async () => {
    if (!passkeyName.value.trim()) {
        modalErrorMessage.value = 'Please provide a name for your passkey (e.g. Work MacBook, My iPhone).';
        return;
    }

    modalErrorMessage.value = null;
    pageErrorMessage.value = null;
    successMessage.value = null;
    isRegistering.value = true;

    try {
        await Passkeys.register({
            name: passkeyName.value.trim(),
        });
        
        successMessage.value = 'Passkey registered successfully!';
        showAddModal.value = false;
        
        // Refresh passkey list from backend
        router.reload({ only: ['passkeys'] });
    } catch (err: any) {
        if (err instanceof PasskeyError) {
            modalErrorMessage.value = err.message || 'Failed to register passkey.';
        } else {
            modalErrorMessage.value = err?.message || 'An unexpected error occurred while registering your passkey.';
        }
    } finally {
        isRegistering.value = false;
    }
};

const confirmDelete = (passkey: PasskeyItem) => {
    passkeyToDelete.value = passkey;
};

const handleDeletePasskey = () => {
    if (!passkeyToDelete.value) return;

    isDeleting.value = true;
    router.delete(`/settings/passkeys/${passkeyToDelete.value.id}`, {
        onSuccess: () => {
            passkeyToDelete.value = null;
            successMessage.value = 'Passkey revoked successfully.';
        },
        onError: (err) => {
            pageErrorMessage.value = Object.values(err).flat().join(', ') || 'Failed to delete passkey.';
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Passkeys" />
        <SettingsLayout>
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <HeadingSmall
                        title="Passkey Authentication"
                        description="Sign in securely using biometrics, Touch ID, Face ID, or hardware security keys."
                    />
                    <Button
                        v-if="isSupported"
                        @click="showAddModal = true"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white dark:bg-emerald-600 dark:hover:bg-emerald-500"
                    >
                        <Plus class="h-4 w-4 mr-2" /> Add Passkey
                    </Button>
                </div>

                <!-- Browser Support Warning -->
                <div
                    v-if="!isSupported"
                    class="rounded-lg border border-amber-500/30 bg-amber-500/10 p-4 text-amber-600 dark:text-amber-400 text-sm flex items-start gap-3"
                >
                    <KeyRound class="h-5 w-5 mt-0.5 shrink-0" />
                    <div>
                        <p class="font-semibold">WebAuthn / Passkeys standard is not supported in this browser environment.</p>
                        <p class="mt-1 text-xs opacity-90">Please use a modern browser (Chrome, Safari, Firefox, Edge) on a device with biometrics or a security key.</p>
                    </div>
                </div>

                <!-- Status / Page Error Messages -->
                <div
                    v-if="successMessage"
                    class="rounded-md bg-emerald-500/10 border border-emerald-500/30 p-3 text-sm text-emerald-600 dark:text-emerald-400 flex items-center gap-2"
                >
                    <ShieldCheck class="h-4 w-4" />
                    <span>{{ successMessage }}</span>
                </div>

                <div
                    v-if="pageErrorMessage"
                    class="rounded-md bg-rose-500/10 border border-rose-500/30 p-3 text-sm text-rose-600 dark:text-rose-400"
                >
                    {{ pageErrorMessage }}
                </div>

                <!-- Passkeys List -->
                <div class="space-y-3">
                    <div
                        v-if="passkeys.length === 0"
                        class="rounded-xl border border-dashed border-neutral-300 dark:border-neutral-700 p-8 text-center"
                    >
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                            <Fingerprint class="h-6 w-6" />
                        </div>
                        <h3 class="mt-4 text-sm font-semibold text-foreground">No Passkeys Registered</h3>
                        <p class="mt-1 text-xs text-muted-foreground max-w-sm mx-auto">
                            Passkeys allow you to sign into your account instantly using Touch ID, Face ID, or your device security key without needing a password.
                        </p>
                        <Button
                            v-if="isSupported"
                            @click="showAddModal = true"
                            variant="outline"
                            class="mt-4 text-xs"
                        >
                            <Plus class="h-3.5 w-3.5 mr-1" /> Add Your First Passkey
                        </Button>
                    </div>

                    <div
                        v-for="passkey in passkeys"
                        :key="passkey.id"
                        class="flex items-center justify-between rounded-xl border bg-card p-4 transition-all hover:border-emerald-500/40 shadow-sm"
                    >
                        <div class="flex items-center space-x-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 shrink-0">
                                <component :is="getDeviceIcon(passkey.name)" class="h-5 w-5" />
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h4 class="font-medium text-sm text-foreground">{{ passkey.name }}</h4>
                                    <Badge variant="outline" class="text-[10px] py-0 px-1.5 bg-emerald-500/5 text-emerald-600 border-emerald-500/20">Active</Badge>
                                </div>
                                <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground">
                                    <span>Added {{ passkey.created_at }}</span>
                                    <span>•</span>
                                    <span>Last used: {{ passkey.last_used_at }}</span>
                                </div>
                            </div>
                        </div>

                        <Button
                            variant="ghost"
                            size="icon"
                            class="text-muted-foreground hover:text-rose-600 hover:bg-rose-500/10 dark:hover:bg-rose-500/20"
                            @click="confirmDelete(passkey)"
                            title="Revoke passkey"
                        >
                            <Trash2 class="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                <!-- Add Passkey Dialog (Error shown inside modal) -->
                <Dialog :open="showAddModal" @update:open="showAddModal = $event">
                    <DialogContent
                        class="sm:max-w-md"
                        @interact-outside.prevent
                        @escape-keydown.prevent
                    >
                        <DialogHeader>
                            <div class="flex items-center space-x-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 shrink-0">
                                    <Fingerprint class="h-6 w-6" />
                                </div>
                                <div>
                                    <DialogTitle>Register New Passkey</DialogTitle>
                                    <DialogDescription class="text-xs">
                                        Assign a recognizable name to this device or security key.
                                    </DialogDescription>
                                </div>
                            </div>
                        </DialogHeader>

                        <div class="space-y-3 py-2">
                            <Label for="passkey-name">Device / Key Name</Label>
                            <Input
                                id="passkey-name"
                                v-model="passkeyName"
                                placeholder="e.g. Work MacBook Air, Personal iPhone 15"
                                :disabled="isRegistering"
                                @keyup.enter="handleRegisterPasskey"
                            />

                            <div
                                v-if="modalErrorMessage"
                                class="rounded-md bg-rose-500/10 border border-rose-500/30 p-3 text-xs text-rose-600 dark:text-rose-400 font-medium"
                            >
                                {{ modalErrorMessage }}
                            </div>
                        </div>

                        <DialogFooter class="sm:justify-end">
                            <Button
                                type="button"
                                variant="outline"
                                @click="showAddModal = false"
                                :disabled="isRegistering"
                            >
                                Cancel
                            </Button>
                            <Button
                                type="button"
                                @click="handleRegisterPasskey"
                                :disabled="isRegistering || !passkeyName.trim()"
                                class="bg-emerald-600 hover:bg-emerald-700 text-white dark:bg-emerald-600 dark:hover:bg-emerald-500"
                            >
                                <Spinner v-if="isRegistering" class="mr-2" />
                                <Fingerprint v-else class="h-4 w-4 mr-2" />
                                Continue with Biometrics
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                <!-- Confirm Revoke Dialog -->
                <Dialog :open="!!passkeyToDelete" @update:open="(val) => { if (!val) passkeyToDelete = null }">
                    <DialogContent
                        class="sm:max-w-md"
                        @interact-outside.prevent
                        @escape-keydown.prevent
                    >
                        <DialogHeader>
                            <DialogTitle>Revoke Passkey?</DialogTitle>
                            <DialogDescription>
                                Are you sure you want to revoke <strong>{{ passkeyToDelete?.name }}</strong>? You will no longer be able to use this passkey to log in.
                            </DialogDescription>
                        </DialogHeader>

                        <DialogFooter class="sm:justify-end pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                @click="passkeyToDelete = null"
                                :disabled="isDeleting"
                            >
                                Cancel
                            </Button>
                            <Button
                                type="button"
                                variant="destructive"
                                @click="handleDeletePasskey"
                                :disabled="isDeleting"
                            >
                                <Spinner v-if="isDeleting" class="mr-2" />
                                Revoke Passkey
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
