<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { 
    Mail, 
    Send, 
    Phone, 
    CheckCircle2, 
    AlertCircle, 
    Loader2, 
    BellRing 
} from '@lucide/vue';

interface UserNotificationChannelData {
    id?: number;
    user_id?: number;
    email_enabled: boolean;
    telegram_enabled: boolean;
    telegram_chat_id: string | null;
    sms_enabled: boolean;
    phone_number: string | null;
    notify_calc_completed: boolean;
    notify_document_processed: boolean;
    notify_system_alerts: boolean;
    notify_pension_updates: boolean;
}

const props = defineProps<{
    channels: UserNotificationChannelData;
    userEmail: string;
    status?: string;
}>();

const { t } = useI18n();

const form = useForm({
    email_enabled: props.channels.email_enabled ?? false,
    telegram_enabled: props.channels.telegram_enabled ?? false,
    telegram_chat_id: props.channels.telegram_chat_id ?? '',
    // SMS channel commented out for future integration
    // sms_enabled: props.channels.sms_enabled ?? false,
    // phone_number: props.channels.phone_number ?? '',
    notify_calc_completed: props.channels.notify_calc_completed ?? true,
    notify_document_processed: props.channels.notify_document_processed ?? true,
    notify_system_alerts: props.channels.notify_system_alerts ?? true,
    notify_pension_updates: props.channels.notify_pension_updates ?? false,
});

const testingChannel = ref<string | null>(null);
const testFeedback = ref<{ channel: string; success: boolean; message: string } | null>(null);

function getCsrfToken(): string {
    const meta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null;
    return meta?.content || '';
}

const submit = () => {
    form.put('/settings/notifications', {
        preserveScroll: true,
    });
};

const sendTest = async (channel: string) => {
    testingChannel.value = channel;
    testFeedback.value = null;

    try {
        const response = await fetch('/settings/notifications/test', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({ channel }),
        });

        const data = await response.json();
        testFeedback.value = {
            channel,
            success: Boolean(data.success),
            message: data.message || (data.success ? 'Test notification sent successfully.' : 'Failed to send test notification.'),
        };
    } catch (err: any) {
        testFeedback.value = {
            channel,
            success: false,
            message: err.message || 'Error executing test notification request.',
        };
    } finally {
        testingChannel.value = null;
    }
};
</script>

<template>
    <Head :title="t('settings.notificationChannels.title')" />

    <div class="flex flex-col space-y-8">
        <Heading
            variant="small"
            :title="t('settings.notificationChannels.title')"
            :description="t('settings.notificationChannels.description')"
        />

        <div v-if="status" class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-sm text-emerald-600 dark:text-emerald-400 font-medium">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-8">

            <!-- CHANNELS SECTION -->
            <div class="space-y-6">
                <h3 class="text-base font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                    <BellRing class="h-5 w-5 text-[#31DE97]" />
                    <span>{{ t('settings.notificationChannels.title') }}</span>
                </h3>

                <div class="grid gap-6">

                    <!-- 1. EMAIL CHANNEL -->
                    <div class="rounded-2xl border border-slate-200/80 bg-slate-50/50 p-5 dark:border-zinc-800/80 dark:bg-zinc-900/30 transition-all hover:border-slate-300 dark:hover:border-zinc-700">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-500/10 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400">
                                    <Mail class="h-5 w-5" />
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-slate-100">
                                        {{ t('settings.notificationChannels.emailTitle') }}
                                    </h4>
                                    <p class="text-xs text-slate-500 dark:text-zinc-400">
                                        {{ t('settings.notificationChannels.emailDesc') }}
                                    </p>
                                </div>
                            </div>
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input type="checkbox" v-model="form.email_enabled" class="peer sr-only" />
                                <div class="peer h-6 w-11 rounded-full bg-slate-300 dark:bg-zinc-700 after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:bg-[#31DE97]! peer-checked:dark:bg-[#31DE97]! peer-checked:after:translate-x-full peer-focus:outline-none"></div>
                            </label>
                        </div>

                        <div v-if="form.email_enabled" class="mt-4 pt-4 border-t border-slate-200/60 dark:border-zinc-800/60 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-2 text-xs text-slate-600 dark:text-zinc-400">
                                <span>Email recipient:</span>
                                <code class="px-2 py-1 rounded bg-slate-200/70 dark:bg-zinc-800 text-slate-900 dark:text-slate-100 font-mono text-xs">{{ userEmail }}</code>
                            </div>
                            <Button 
                                type="button" 
                                variant="outline" 
                                size="sm" 
                                class="shrink-0"
                                :disabled="testingChannel === 'email'" 
                                @click="sendTest('email')"
                            >
                                <Loader2 v-if="testingChannel === 'email'" class="h-4 w-4 animate-spin mr-2" />
                                <span>{{ t('settings.notificationChannels.sendTestBtn') }}</span>
                            </Button>
                        </div>
                    </div>

                    <!-- 2. TELEGRAM BOT CHANNEL -->
                    <div class="rounded-2xl border border-slate-200/80 bg-slate-50/50 p-5 dark:border-zinc-800/80 dark:bg-zinc-900/30 transition-all hover:border-slate-300 dark:hover:border-zinc-700">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-500/10 text-sky-600 dark:bg-sky-500/20 dark:text-sky-400">
                                    <Send class="h-5 w-5" />
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-slate-100">
                                        {{ t('settings.notificationChannels.telegramTitle') }}
                                    </h4>
                                    <p class="text-xs text-slate-500 dark:text-zinc-400">
                                        {{ t('settings.notificationChannels.telegramDesc') }}
                                    </p>
                                </div>
                            </div>
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input type="checkbox" v-model="form.telegram_enabled" class="peer sr-only" />
                                <div class="peer h-6 w-11 rounded-full bg-slate-300 dark:bg-zinc-700 after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:bg-[#31DE97]! peer-checked:dark:bg-[#31DE97]! peer-checked:after:translate-x-full peer-focus:outline-none"></div>
                            </label>
                        </div>

                        <div v-if="form.telegram_enabled" class="mt-4 pt-4 border-t border-slate-200/60 dark:border-zinc-800/60 space-y-3">
                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                                <div class="flex-1">
                                    <Label for="telegram_chat_id" class="sr-only">{{ t('settings.notificationChannels.telegramChatId') }}</Label>
                                    <Input
                                        id="telegram_chat_id"
                                        v-model="form.telegram_chat_id"
                                        :placeholder="t('settings.notificationChannels.telegramPlaceholder')"
                                        class="w-full font-mono"
                                    />
                                </div>
                                <Button 
                                    type="button" 
                                    variant="outline" 
                                    size="sm" 
                                    class="shrink-0"
                                    :disabled="testingChannel === 'telegram' || !form.telegram_chat_id" 
                                    @click="sendTest('telegram')"
                                >
                                    <Loader2 v-if="testingChannel === 'telegram'" class="h-4 w-4 animate-spin mr-2" />
                                    <span>{{ t('settings.notificationChannels.sendTestBtn') }}</span>
                                </Button>
                            </div>
                            <InputError :message="form.errors.telegram_chat_id" />
                        </div>
                    </div>

                    <!-- 3. SMS CHANNEL (Commented for future integration)
                    <div class="rounded-2xl border border-slate-200/80 bg-slate-50/50 p-5 dark:border-zinc-800/80 dark:bg-zinc-900/30 transition-all hover:border-slate-300 dark:hover:border-zinc-700">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400">
                                    <Phone class="h-5 w-5" />
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-slate-100">
                                        {{ t('settings.notificationChannels.smsTitle') }}
                                    </h4>
                                    <p class="text-xs text-slate-500 dark:text-zinc-400">
                                        {{ t('settings.notificationChannels.smsDesc') }}
                                    </p>
                                </div>
                            </div>
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input type="checkbox" v-model="form.sms_enabled" class="peer sr-only" />
                                <div class="peer h-6 w-11 rounded-full bg-slate-300 dark:bg-zinc-700 after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:bg-[#31DE97]! peer-checked:dark:bg-[#31DE97]! peer-checked:after:translate-x-full peer-focus:outline-none"></div>
                            </label>
                        </div>

                        <div v-if="form.sms_enabled" class="mt-4 pt-4 border-t border-slate-200/60 dark:border-zinc-800/60 space-y-3">
                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                                <div class="flex-1">
                                    <Label for="phone_number" class="sr-only">{{ t('settings.notificationChannels.phoneNumber') }}</Label>
                                    <Input
                                        id="phone_number"
                                        v-model="form.phone_number"
                                        :placeholder="t('settings.notificationChannels.phonePlaceholder')"
                                        class="w-full font-mono"
                                    />
                                </div>
                                <Button 
                                    type="button" 
                                    variant="outline" 
                                    size="sm" 
                                    class="shrink-0"
                                    :disabled="testingChannel === 'sms' || !form.phone_number" 
                                    @click="sendTest('sms')"
                                >
                                    <Loader2 v-if="testingChannel === 'sms'" class="h-4 w-4 animate-spin mr-2" />
                                    <span>{{ t('settings.notificationChannels.sendTestBtn') }}</span>
                                </Button>
                            </div>
                            <InputError :message="form.errors.phone_number" />
                        </div>
                    </div>
                    -->

                </div>
            </div>

            <!-- TEST FEEDBACK FEED -->
            <div v-if="testFeedback" class="rounded-xl border p-4 text-sm flex items-start gap-3 transition-all"
                :class="testFeedback.success 
                    ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300' 
                    : 'border-rose-500/30 bg-rose-500/10 text-rose-700 dark:text-rose-300'"
            >
                <CheckCircle2 v-if="testFeedback.success" class="h-5 w-5 shrink-0 text-emerald-500 mt-0.5" />
                <AlertCircle v-else class="h-5 w-5 shrink-0 text-rose-500 mt-0.5" />
                <div class="flex-1">
                    <p class="font-bold capitalize">{{ testFeedback.channel }} Test Result</p>
                    <p class="text-xs opacity-90 mt-0.5">{{ testFeedback.message }}</p>
                </div>
            </div>

            <!-- NOTIFICATION PREFERENCES SECTION -->
            <div class="space-y-6 pt-4 border-t border-slate-200 dark:border-zinc-800">
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">
                        {{ t('settings.notificationChannels.preferencesTitle') }}
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-zinc-400">
                        {{ t('settings.notificationChannels.preferencesDesc') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <label class="flex items-center justify-between p-4 rounded-xl border border-slate-200/80 bg-slate-50/50 dark:border-zinc-800/80 dark:bg-zinc-900/30 cursor-pointer hover:border-slate-300 dark:hover:border-zinc-700">
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                            {{ t('settings.notificationChannels.notifyCalcCompleted') }}
                        </span>
                        <input type="checkbox" v-model="form.notify_calc_completed" class="h-4 w-4 rounded border-slate-300 text-[#31DE97] accent-[#31DE97] focus:ring-[#31DE97]" />
                    </label>

                    <label class="flex items-center justify-between p-4 rounded-xl border border-slate-200/80 bg-slate-50/50 dark:border-zinc-800/80 dark:bg-zinc-900/30 cursor-pointer hover:border-slate-300 dark:hover:border-zinc-700">
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                            {{ t('settings.notificationChannels.notifyDocumentProcessed') }}
                        </span>
                        <input type="checkbox" v-model="form.notify_document_processed" class="h-4 w-4 rounded border-slate-300 text-[#31DE97] accent-[#31DE97] focus:ring-[#31DE97]" />
                    </label>

                    <label class="flex items-center justify-between p-4 rounded-xl border border-slate-200/80 bg-slate-50/50 dark:border-zinc-800/80 dark:bg-zinc-900/30 cursor-pointer hover:border-slate-300 dark:hover:border-zinc-700">
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                            {{ t('settings.notificationChannels.notifySystemAlerts') }}
                        </span>
                        <input type="checkbox" v-model="form.notify_system_alerts" class="h-4 w-4 rounded border-slate-300 text-[#31DE97] accent-[#31DE97] focus:ring-[#31DE97]" />
                    </label>

                    <label class="flex items-center justify-between p-4 rounded-xl border border-slate-200/80 bg-slate-50/50 dark:border-zinc-800/80 dark:bg-zinc-900/30 cursor-pointer hover:border-slate-300 dark:hover:border-zinc-700">
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                            {{ t('settings.notificationChannels.notifyPensionUpdates') }}
                        </span>
                        <input type="checkbox" v-model="form.notify_pension_updates" class="h-4 w-4 rounded border-slate-300 text-[#31DE97] accent-[#31DE97] focus:ring-[#31DE97]" />
                    </label>
                </div>
            </div>

            <!-- SUBMIT BUTTON -->
            <div class="flex items-center gap-4 pt-4">
                <Button 
                    type="submit" 
                    :disabled="form.processing" 
                    class="bg-[#31DE97] text-slate-950 font-bold hover:bg-[#28C586] transition-colors"
                >
                    <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
                    <span>{{ t('settings.notificationChannels.saveBtn') }}</span>
                </Button>
            </div>

        </form>
    </div>
</template>
