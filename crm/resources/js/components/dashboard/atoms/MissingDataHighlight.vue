<script setup lang="ts">
import { AlertTriangle, ArrowRight } from '@lucide/vue';
import { useI18n } from '@/composables/useI18n';

defineProps<{
    title: string;
    description: string;
}>();

defineEmits<{
    (e: 'click'): void;
}>();

const { t } = useI18n();
</script>

<template>
    <div
        @click="$emit('click')"
        class="group relative flex items-center justify-between gap-4 rounded-xl border border-amber-500/40 bg-amber-500/10 p-4 transition-all duration-200 hover:border-amber-500 hover:bg-amber-500/15 cursor-pointer dark:bg-amber-500/15 dark:hover:bg-amber-500/20"
    >
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-500/20 text-amber-600 dark:text-amber-400 font-bold">
                <AlertTriangle class="h-5 w-5" />
            </div>
            <div>
                <h4 class="text-xs font-bold text-slate-900 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">
                    {{ title }}
                </h4>
                <p class="text-[11px] text-slate-600 dark:text-zinc-300">
                    {{ description }}
                </p>
            </div>
        </div>

        <span class="inline-flex items-center gap-1 text-xs font-bold text-amber-600 dark:text-amber-400 shrink-0">
            {{ t('dashboard.alerts.fillBtn') }}
            <ArrowRight class="h-3.5 w-3.5 transition-transform group-hover:translate-x-1" />
        </span>
    </div>
</template>
