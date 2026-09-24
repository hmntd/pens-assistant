<script setup lang="ts">
import { Send } from '@lucide/vue';
import { useI18n } from '@/composables/useI18n';

defineProps<{
    telegramBotUsername?: string;
}>();

defineEmits<{
    (e: 'close'): void;
}>();

const { t } = useI18n();
</script>

<template>
    <div
        class="animate-in space-y-2 rounded-xl border border-sky-500/20 bg-sky-500/10 p-4 text-xs text-slate-800 transition-all duration-200 fade-in slide-in-from-top-2 dark:text-zinc-200"
    >
        <div
            class="mb-2 flex items-center justify-between border-b border-sky-500/20 pb-1.5 text-sm font-bold text-sky-700 dark:text-sky-300"
        >
            <span class="flex items-center gap-1.5">
                <Send class="h-4 w-4 text-sky-500" />
                {{ t('settings.notificationChannels.telegramGuideTitle') }}
            </span>
            <button
                type="button"
                @click="$emit('close')"
                class="cursor-pointer font-mono text-xs opacity-70 hover:opacity-100"
            >
                ✕
            </button>
        </div>
        <ol
            class="list-inside list-decimal space-y-2 leading-relaxed font-medium text-slate-700 dark:text-zinc-300"
        >
            <li>
                <span>{{
                    t('settings.notificationChannels.telegramGuideStep1')
                }}</span>
                <a
                    :href="
                        'https://t.me/' +
                        (telegramBotUsername || 'PensAssistantBot')
                    "
                    target="_blank"
                    rel="noopener noreferrer"
                    class="ml-1 inline-flex items-center gap-1 rounded-md bg-sky-500/10 px-2 py-0.5 font-bold text-sky-600 hover:underline dark:text-sky-400"
                >
                    @PensAssistantBot
                </a>
            </li>
            <li>{{ t('settings.notificationChannels.telegramGuideStep2') }}</li>
            <li>
                <span>{{
                    t('settings.notificationChannels.telegramGuideStep3Prefix')
                }}</span>
                <a
                    href="https://t.me/userinfobot"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="ml-1 inline-flex items-center gap-1 rounded-md bg-sky-500/10 px-2 py-0.5 font-bold text-sky-600 hover:underline dark:text-sky-400"
                >
                    @userinfobot
                </a>
                <span>{{
                    t('settings.notificationChannels.telegramGuideStep3Or')
                }}</span>
                <a
                    href="https://t.me/myidbot"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="ml-1 inline-flex items-center gap-1 rounded-md bg-sky-500/10 px-2 py-0.5 font-bold text-sky-600 hover:underline dark:text-sky-400"
                >
                    @myidbot
                </a>
                <span>)</span>
            </li>
            <li>{{ t('settings.notificationChannels.telegramGuideStep4') }}</li>
        </ol>
    </div>
</template>
