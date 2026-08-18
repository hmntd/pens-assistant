<script setup lang="ts">
import { CheckCircle2, Clock, AlertCircle } from '@lucide/vue';

defineProps<{
    status: 'processed' | 'failed' | 'pending' | string;
    label?: string;
}>();
</script>

<template>
    <span
        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold"
        :class="[
            status === 'processed'
                ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400'
                : status === 'failed'
                ? 'bg-red-500/15 text-red-600 dark:text-red-400'
                : 'bg-amber-500/15 text-amber-600 dark:text-amber-400'
        ]"
    >
        <CheckCircle2 v-if="status === 'processed'" class="h-3 w-3" />
        <AlertCircle v-else-if="status === 'failed'" class="h-3 w-3" />
        <Clock v-else class="h-3 w-3" />
        {{ label || (status === 'processed' ? 'Розпізнано' : status === 'failed' ? 'Помилка' : 'Обробка') }}
    </span>
</template>
