<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';
import Heading from '@/components/Heading.vue';
import NotificationChannelsForm, { UserNotificationChannelData } from '@/components/settings/organisms/NotificationChannelsForm.vue';

defineProps<{
    channels: UserNotificationChannelData;
    userEmail: string;
    telegramBotUsername?: string;
    status?: string;
}>();

const { t } = useI18n();
</script>

<template>

    <Head :title="t('settings.notificationChannels.title')" />

    <div class="flex flex-col space-y-8">
        <Heading variant="small" :title="t('settings.notificationChannels.title')"
            :description="t('settings.notificationChannels.description')" />

        <div v-if="status"
            class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-sm text-emerald-600 dark:text-emerald-400 font-medium">
            {{ status }}
        </div>

        <NotificationChannelsForm :channels="channels" :user-email="userEmail"
            :telegram-bot-username="telegramBotUsername" />
    </div>
</template>
