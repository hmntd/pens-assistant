<script setup lang="ts">
import { CheckCircle2, AlertCircle } from '@lucide/vue';

defineProps<{
    channel: string;
    success: boolean;
    message: string;
}>();
</script>

<template>
    <div
        class="flex animate-in items-start gap-3 rounded-xl border p-4 text-sm transition-all duration-200 fade-in slide-in-from-top-2"
        :class="
            success
                ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                : 'border-rose-500/30 bg-rose-500/10 text-rose-700 dark:text-rose-300'
        "
    >
        <CheckCircle2
            v-if="success"
            class="mt-0.5 h-5 w-5 shrink-0 text-emerald-500"
        />
        <AlertCircle v-else class="mt-0.5 h-5 w-5 shrink-0 text-rose-500" />
        <div class="flex-1">
            <p class="font-bold capitalize">{{ channel }} Test Result</p>
            <p class="mt-0.5 text-xs opacity-90">{{ message }}</p>
        </div>
    </div>
</template>
