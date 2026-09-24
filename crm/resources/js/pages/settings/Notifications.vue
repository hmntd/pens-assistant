<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/common/atoms/Heading.vue';
import type { UserNotificationChannelData } from '@/components/settings/organisms/NotificationChannelsForm.vue';
import NotificationChannelsForm from '@/components/settings/organisms/NotificationChannelsForm.vue';
import { useI18n } from '@/composables/useI18n';

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
        <Heading
            variant="small"
            :title="t('settings.notificationChannels.title')"
            :description="t('settings.notificationChannels.description')"
        />

        <div
            v-if="status"
            class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-sm font-medium text-emerald-600 dark:text-emerald-400"
        >
            {{ status }}
        </div>

        <NotificationChannelsForm
            :channels="channels"
            :user-email="userEmail"
            :telegram-bot-username="telegramBotUsername"
        />
    </div>
</template>
