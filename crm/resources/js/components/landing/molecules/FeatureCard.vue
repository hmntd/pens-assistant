<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    title: string;
    description: string;
    icon: Component;
    index: number;
}>();
</script>

<template>
    <div
        class="group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white/70 p-6 shadow-sm backdrop-blur-md transition-all duration-300 hover:-translate-y-1 hover:border-main/50 hover:shadow-lg hover:shadow-main/10 dark:border-zinc-800/80 dark:bg-zinc-950/80 dark:hover:border-main/50 dark:hover:shadow-main/5"
    >
        <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-main/10 text-main-dark transition-colors duration-300 group-hover:bg-main group-hover:text-slate-950 dark:text-main dark:group-hover:text-slate-950">
            <component :is="icon" class="h-6 w-6" />
        </div>
        <h3 class="mb-2 text-lg font-bold text-slate-900 dark:text-white">
            {{ title }}
        </h3>
        <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-400">
            {{ description }}
        </p>
    </div>
</template>
