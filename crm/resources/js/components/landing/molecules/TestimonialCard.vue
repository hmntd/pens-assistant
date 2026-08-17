<script setup lang="ts">
import { Quote } from '@lucide/vue';

defineProps<{
    quote: string;
    author: string;
    role: string;
    avatarUrl?: string;
}>();
</script>

<template>
    <div
        class="relative flex flex-col justify-between rounded-2xl border border-slate-200/80 bg-white/70 p-6 shadow-sm backdrop-blur-md transition-all duration-300 hover:border-[#31DE97]/40 hover:shadow-md dark:border-slate-800/80 dark:bg-slate-900/60"
    >
        <Quote class="mb-3 h-6 w-6 text-[#31DE97]/60" />
        <p class="mb-6 text-sm italic leading-relaxed text-slate-700 dark:text-slate-300">
            "{{ quote }}"
        </p>
        <div class="flex items-center gap-3">
            <div
                class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-[#31DE97] to-emerald-600 font-bold text-slate-950 text-sm shadow-inner"
            >
                {{ author.charAt(0) }}
            </div>
            <div>
                <h4 class="text-sm font-semibold text-slate-900 dark:text-white">
                    {{ author }}
                </h4>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    {{ role }}
                </p>
            </div>
        </div>
    </div>
</template>
