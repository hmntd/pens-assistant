<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Mail,
    Send,
    Loader2,
    BellRing,
    Info
} from '@lucide/vue';

import NotificationChannelCard from '../molecules/NotificationChannelCard.vue';
import NotificationPreferenceToggle from '../molecules/NotificationPreferenceToggle.vue';
import TelegramSetupGuide from '../molecules/TelegramSetupGuide.vue';
import ChannelTestFeedback from '../atoms/ChannelTestFeedback.vue';

export interface UserNotificationChannelData {
    id?: number;
    user_id?: number;
    email_enabled: boolean;
    telegram_enabled: boolean;
    telegram_chat_id: string | null;
    sms_enabled?: boolean;
    phone_number?: string | null;
    notify_calc_completed: boolean;
    notify_document_processed: boolean;
    notify_system_alerts: boolean;
    notify_pension_updates: boolean;
}

const props = defineProps<{
    channels: UserNotificationChannelData;
    userEmail: string;
    telegramBotUsername?: string;
}>();

const { t } = useI18n();

const form = useForm({
    email_enabled: props.channels.email_enabled ?? false,
    telegram_enabled: props.channels.telegram_enabled ?? false,
    telegram_chat_id: props.channels.telegram_chat_id ?? '',
    notify_calc_completed: props.channels.notify_calc_completed ?? true,
    notify_document_processed: props.channels.notify_document_processed ?? true,
    notify_system_alerts: props.channels.notify_system_alerts ?? true,
    notify_pension_updates: props.channels.notify_pension_updates ?? false,
});

const showTelegramGuide = ref(false);
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
        const payload: Record<string, any> = { channel };
        if (channel === 'telegram') {
            payload.telegram_chat_id = form.telegram_chat_id;
        }

        const response = await fetch('/settings/notifications/test', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify(payload),
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
    <form @submit.prevent="submit" class="space-y-8">
        <!-- CHANNELS SECTION -->
        <div class="space-y-6">
            <h3 class="text-base font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                <BellRing class="h-5 w-5 text-[#31DE97]" />
                <span>{{ t('settings.notificationChannels.title') }}</span>
            </h3>

            <div class="grid gap-6">
                <!-- 1. EMAIL CHANNEL -->
                <NotificationChannelCard :title="t('settings.notificationChannels.emailTitle')"
                    :description="t('settings.notificationChannels.emailDesc')" :icon="Mail"
                    v-model:enabled="form.email_enabled" icon-bg-class="bg-blue-500/10 dark:bg-blue-500/20"
                    icon-color-class="text-blue-600 dark:text-blue-400">
                    <div
                        class="mt-4 pt-4 border-t border-slate-200/60 dark:border-zinc-800/60 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-2 text-xs text-slate-600 dark:text-zinc-400">
                            <span>Email recipient:</span>
                            <code
                                class="px-2 py-1 rounded bg-slate-200/70 dark:bg-zinc-800 text-slate-900 dark:text-slate-100 font-mono text-xs">{{ userEmail }}</code>
                        </div>
                        <Button type="button" variant="outline" size="sm" class="shrink-0"
                            :disabled="testingChannel === 'email'" @click="sendTest('email')">
                            <Loader2 v-if="testingChannel === 'email'" class="h-4 w-4 animate-spin mr-2" />
                            <span>{{ t('settings.notificationChannels.sendTestBtn') }}</span>
                        </Button>
                    </div>
                </NotificationChannelCard>

                <!-- 2. TELEGRAM CHANNEL -->
                <NotificationChannelCard :title="t('settings.notificationChannels.telegramTitle')"
                    :description="t('settings.notificationChannels.telegramDesc')" :icon="Send"
                    v-model:enabled="form.telegram_enabled" icon-bg-class="bg-sky-500/10 dark:bg-sky-500/20"
                    icon-color-class="text-sky-600 dark:text-sky-400">
                    <div class="mt-4 pt-4 border-t border-slate-200/60 dark:border-zinc-800/60 space-y-3">
                        <div class="flex items-center justify-between">
                            <Label for="telegram_chat_id" class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                {{ t('settings.notificationChannels.telegramChatId') }}
                            </Label>

                            <button type="button" @click="showTelegramGuide = !showTelegramGuide"
                                class="flex items-center gap-1.5 text-xs text-sky-600 dark:text-sky-400 hover:text-sky-700 dark:hover:text-sky-300 font-medium transition-colors focus:outline-none cursor-pointer">
                                <Info class="h-4 w-4" />
                                <span>{{ t('settings.notificationChannels.telegramInfoTooltip') }}</span>
                            </button>
                        </div>

                        <!-- Telegram Setup Guide Molecule -->
                        <TelegramSetupGuide v-if="showTelegramGuide" :telegram-bot-username="telegramBotUsername"
                            @close="showTelegramGuide = false" />

                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                            <div class="flex-1">
                                <Input id="telegram_chat_id" v-model="form.telegram_chat_id"
                                    :placeholder="t('settings.notificationChannels.telegramPlaceholder')"
                                    class="w-full font-mono" />
                            </div>
                            <Button type="button" variant="outline" size="sm" class="shrink-0"
                                :disabled="testingChannel === 'telegram'" @click="sendTest('telegram')">
                                <Loader2 v-if="testingChannel === 'telegram'" class="h-4 w-4 animate-spin mr-2" />
                                <span>{{ t('settings.notificationChannels.sendTestBtn') }}</span>
                            </Button>
                        </div>
                        <InputError :message="form.errors.telegram_chat_id" />
                    </div>
                </NotificationChannelCard>
            </div>
        </div>

        <!-- TEST FEEDBACK FEED ATOM -->
        <ChannelTestFeedback v-if="testFeedback" :channel="testFeedback.channel" :success="testFeedback.success"
            :message="testFeedback.message" />

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
                <NotificationPreferenceToggle v-model="form.notify_calc_completed"
                    :label="t('settings.notificationChannels.notifyCalcCompleted')" />
                <NotificationPreferenceToggle v-model="form.notify_document_processed"
                    :label="t('settings.notificationChannels.notifyDocumentProcessed')" />
                <NotificationPreferenceToggle v-model="form.notify_system_alerts"
                    :label="t('settings.notificationChannels.notifySystemAlerts')" />
                <NotificationPreferenceToggle v-model="form.notify_pension_updates"
                    :label="t('settings.notificationChannels.notifyPensionUpdates')" />
            </div>
        </div>

        <!-- SUBMIT BUTTON -->
        <div class="flex items-center gap-4 pt-4">
            <Button type="submit" :disabled="form.processing"
                class="bg-[#31DE97] text-slate-950 font-bold hover:bg-[#28C586] transition-colors cursor-pointer">
                <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
                <span>{{ t('settings.notificationChannels.saveBtn') }}</span>
            </Button>
        </div>
    </form>
</template>
