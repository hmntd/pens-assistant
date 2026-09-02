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
    <div class="p-4 rounded-xl bg-sky-500/10 border border-sky-500/20 text-xs space-y-2 text-slate-800 dark:text-zinc-200 transition-all animate-in fade-in slide-in-from-top-2 duration-200">
        <div class="flex items-center justify-between font-bold text-sky-700 dark:text-sky-300 text-sm border-b border-sky-500/20 pb-1.5 mb-2">
            <span class="flex items-center gap-1.5">
                <Send class="h-4 w-4 text-sky-500" />
                {{ t('settings.notificationChannels.telegramGuideTitle') }}
            </span>
            <button type="button" @click="$emit('close')" class="text-xs opacity-70 hover:opacity-100 font-mono cursor-pointer">✕</button>
        </div>
        <ol class="list-decimal list-inside space-y-2 leading-relaxed text-slate-700 dark:text-zinc-300 font-medium">
            <li>
                <span>{{ t('settings.notificationChannels.telegramGuideStep1') }}</span>
                <a 
                    :href="'https://t.me/' + (telegramBotUsername || 'PensAssistantBot')" 
                    target="_blank" 
                    rel="noopener noreferrer" 
                    class="inline-flex items-center gap-1 font-bold text-sky-600 dark:text-sky-400 hover:underline bg-sky-500/10 px-2 py-0.5 rounded-md ml-1"
                >
                    @{{ telegramBotUsername || 'PensAssistantBot' }} ↗
                </a>
            </li>
            <li>{{ t('settings.notificationChannels.telegramGuideStep2') }}</li>
            <li>{{ t('settings.notificationChannels.telegramGuideStep3') }}</li>
            <li>{{ t('settings.notificationChannels.telegramGuideStep4') }}</li>
        </ol>
    </div>
</template>
