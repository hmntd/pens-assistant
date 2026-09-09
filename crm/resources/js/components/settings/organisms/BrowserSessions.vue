<script setup lang="ts">
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';
import { toast } from 'vue-sonner';
import {
    Laptop,
    Smartphone,
    Tablet,
    Globe,
    ShieldAlert,
    Trash2,
    Lock,
    CheckCircle2,
} from '@lucide/vue';
import Heading from '@/components/common/atoms/Heading.vue';
import InputError from '@/components/common/atoms/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';

export interface UserSession {
    id: string;
    ip_address: string;
    is_current_device: boolean;
    device_type: 'Desktop' | 'Mobile' | 'Tablet' | string;
    platform: string;
    browser: string;
    browser_version?: string | null;
    user_agent?: string | null;
    last_active: string;
    last_activity_timestamp?: number;
}

const props = defineProps<{
    sessions?: UserSession[];
}>();

const { t } = useI18n();

const confirmingLogout = ref(false);
const passwordInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    password: '',
});

const getDeviceIcon = (type: string) => {
    switch (type) {
        case 'Desktop':
            return Laptop;
        case 'Mobile':
            return Smartphone;
        case 'Tablet':
            return Tablet;
        default:
            return Globe;
    }
};

const confirmLogout = () => {
    confirmingLogout.value = true;
    form.clearErrors();
    form.password = '';
};

const closeModal = () => {
    confirmingLogout.value = false;
    form.clearErrors();
    form.password = '';
};

const logoutOtherBrowserSessions = () => {
    form.post('/settings/sessions/logout-other', {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            toast.success(t('settings.sessions.successToast'));
        },
        onError: () => {
            passwordInput.value?.focus();
        },
    });
};

const revokeSession = (sessionId: string) => {
    if (!confirm(t('settings.sessions.revokeConfirm'))) {
        return;
    }

    router.delete(`/settings/sessions/${sessionId}`, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(t('settings.sessions.revokeSuccessToast'));
        },
        onError: (errors) => {
            const msg = errors.session || t('settings.sessions.errorExecute');
            toast.error(msg);
        },
    });
};
</script>

<template>
    <div class="space-y-6">
        <Heading variant="small" :title="t('settings.sessions.title')"
            :description="t('settings.sessions.description')" />

        <div class="space-y-4">
            <p class="text-sm text-slate-600 dark:text-zinc-400">
                {{ t('settings.sessions.info') }}
            </p>

            <!-- Sessions List -->
            <div v-if="props.sessions && props.sessions.length > 0"
                class="divide-y divide-slate-100 rounded-2xl border border-slate-200/80 bg-white dark:divide-zinc-800/80 dark:border-zinc-800/80 dark:bg-zinc-950/50 shadow-sm overflow-hidden">
                <div v-for="session in props.sessions" :key="session.id"
                    class="flex items-center justify-between p-4 sm:p-5 transition-colors hover:bg-slate-50/50 dark:hover:bg-zinc-900/50">
                    <div class="flex items-center gap-4">
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 dark:bg-zinc-900 text-slate-700 dark:text-zinc-300">
                            <component :is="getDeviceIcon(session.device_type)" class="h-6 w-6 text-main" />
                        </div>

                        <div class="space-y-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-sm font-bold text-slate-900 dark:text-white">
                                    {{ session.platform }} - {{ session.browser }}
                                    <template v-if="session.browser_version">
                                        {{ session.browser_version }}
                                    </template>
                                </span>

                                <span v-if="session.is_current_device"
                                    class="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-xs font-extrabold text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                    <CheckCircle2 class="h-3 w-3" />
                                    {{ t('settings.sessions.thisDevice') }}
                                </span>
                            </div>

                            <p class="text-xs text-slate-500 dark:text-zinc-400">
                                <span>{{ session.ip_address }}</span>
                                <span class="mx-1.5">•</span>
                                <span v-if="session.is_current_device"
                                    class="font-medium text-emerald-600 dark:text-emerald-400">
                                    {{ t('settings.sessions.thisDevice') }}
                                </span>
                                <span v-else>
                                    {{ t('settings.sessions.lastActive') }} {{ session.last_active }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <!-- Individual Revoke Button (only for non-current sessions) -->
                    <div v-if="!session.is_current_device" class="ml-4 shrink-0">
                        <Button variant="ghost" size="sm"
                            class="text-slate-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/30 dark:hover:text-red-400 transition-colors"
                            :title="t('settings.sessions.revokeBtn')" @click="revokeSession(session.id)">
                            <Trash2 class="h-4 w-4" />
                            <span class="sr-only">{{ t('settings.sessions.revokeBtn') }}</span>
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="rounded-2xl border border-dashed border-slate-200 p-8 text-center dark:border-zinc-800">
                <Globe class="mx-auto h-8 w-8 text-slate-400 dark:text-zinc-600" />
                <p class="mt-2 text-sm text-slate-500 dark:text-zinc-400">
                    No active sessions logged.
                </p>
            </div>

            <!-- Logout Other Browser Sessions Button -->
            <div class="pt-2">
                <Button variant="outline"
                    class="font-bold border-slate-300 dark:border-zinc-700 hover:bg-slate-100 dark:hover:bg-zinc-800 text-slate-900 dark:text-white"
                    @click="confirmLogout">
                    <Lock class="mr-2 h-4 w-4 text-main" />
                    {{ t('settings.sessions.logoutOtherBtn') }}
                </Button>
            </div>

            <!-- Password Confirmation Modal -->
            <Dialog :open="confirmingLogout" @update:open="closeModal">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader class="space-y-2">
                        <div
                            class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-main/10 text-main">
                            <ShieldAlert class="h-6 w-6" />
                        </div>
                        <DialogTitle class="text-center font-extrabold text-lg">
                            {{ t('settings.sessions.modalTitle') }}
                        </DialogTitle>
                        <DialogDescription class="text-center text-xs text-slate-500 dark:text-zinc-400">
                            {{ t('settings.sessions.modalDesc') }}
                        </DialogDescription>
                    </DialogHeader>

                    <form @submit.prevent="logoutOtherBrowserSessions" class="space-y-4 pt-2">
                        <div class="space-y-2">
                            <Label for="session_password" class="text-xs font-bold">
                                {{ t('settings.sessions.passwordLabel') }}
                            </Label>
                            <Input id="session_password" ref="passwordInput" v-model="form.password" type="password"
                                required autocomplete="current-password"
                                :placeholder="t('settings.sessions.passwordPlaceholder')"
                                @keyup.enter="logoutOtherBrowserSessions" />
                            <InputError :message="form.errors.password" />
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-2">
                            <Button type="button" variant="outline" :disabled="form.processing" @click="closeModal">
                                {{ t('settings.sessions.cancelBtn') }}
                            </Button>
                            <Button type="submit" class="bg-main text-slate-950 hover:bg-main-hover font-bold"
                                :disabled="form.processing || !form.password">
                                <Spinner v-if="form.processing" class="mr-2" />
                                {{ form.processing ? t('settings.sessions.loggingOut') :
                                    t('settings.sessions.confirmBtn') }}
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
