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
        class="group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white/70 p-6 shadow-sm backdrop-blur-md transition-all duration-300 hover:-translate-y-1 hover:border-[#31DE97]/50 hover:shadow-lg hover:shadow-[#31DE97]/10 dark:border-slate-800/80 dark:bg-slate-900/60 dark:hover:border-[#31DE97]/50 dark:hover:shadow-[#31DE97]/5"
    >
        <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-[#31DE97]/10 text-[#1cb777] transition-colors duration-300 group-hover:bg-[#31DE97] group-hover:text-slate-950 dark:text-[#31DE97] dark:group-hover:text-slate-950">
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
