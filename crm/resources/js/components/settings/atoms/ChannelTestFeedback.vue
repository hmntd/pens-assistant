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
        class="rounded-xl border p-4 text-sm flex items-start gap-3 transition-all animate-in fade-in slide-in-from-top-2 duration-200"
        :class="success 
            ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300' 
            : 'border-rose-500/30 bg-rose-500/10 text-rose-700 dark:text-rose-300'"
    >
        <CheckCircle2 v-if="success" class="h-5 w-5 shrink-0 text-emerald-500 mt-0.5" />
        <AlertCircle v-else class="h-5 w-5 shrink-0 text-rose-500 mt-0.5" />
        <div class="flex-1">
            <p class="font-bold capitalize">{{ channel }} Test Result</p>
            <p class="text-xs opacity-90 mt-0.5">{{ message }}</p>
        </div>
    </div>
</template>
