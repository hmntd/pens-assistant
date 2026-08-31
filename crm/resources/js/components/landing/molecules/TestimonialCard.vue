<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    title: string;
    description: string;
    icon?: Component;
    tag?: string;
}>();
</script>

<template>
    <div
        class="group relative flex flex-col justify-between h-full rounded-2xl border border-slate-200/80 bg-white/80 p-6 shadow-sm backdrop-blur-md transition-all duration-300 hover:-translate-y-1 hover:border-main/50 hover:shadow-lg hover:shadow-main/5 dark:border-zinc-800/80 dark:bg-zinc-950/80 dark:hover:border-main/50 dark:hover:shadow-main/10"
    >
        <div>
            <div class="mb-4 flex items-center justify-between">
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-main/15 to-emerald-500/20 text-main transition-colors duration-300 group-hover:bg-main group-hover:text-slate-950 dark:from-main/20 dark:to-emerald-500/30"
                >
                    <component :is="icon" class="h-6 w-6 stroke-[2.2]" v-if="icon" />
                    <span v-else class="text-lg font-bold">⚡</span>
                </div>
                <span
                    v-if="tag"
                    class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-600 dark:bg-zinc-800 dark:text-slate-300"
                >
                    {{ tag }}
                </span>
            </div>

            <h3 class="mb-2 text-base font-bold tracking-tight text-slate-900 dark:text-white group-hover:text-main transition-colors">
                {{ title }}
            </h3>

            <p class="text-xs leading-relaxed text-slate-600 dark:text-slate-400">
                {{ description }}
            </p>
        </div>
    </div>
</template>
