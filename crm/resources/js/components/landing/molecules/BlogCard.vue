<script setup lang="ts">
import { ArrowUpRight, Calendar } from '@lucide/vue';

defineProps<{
    tag: string;
    title: string;
    date: string;
    description: string;
    url?: string;
}>();
</script>

<template>
    <component
        :is="url ? 'a' : 'article'"
        :href="url"
        :target="url ? '_blank' : undefined"
        :rel="url ? 'noopener noreferrer' : undefined"
        class="group flex flex-col justify-between overflow-hidden rounded-2xl border border-slate-200/80 bg-white/70 p-6 shadow-sm backdrop-blur-md transition-all duration-300 hover:-translate-y-1 hover:border-main/50 hover:shadow-lg hover:shadow-main/10 dark:border-zinc-800/80 dark:bg-zinc-950/80 cursor-pointer text-left no-underline"
    >
        <div>
            <div class="mb-3 flex items-center justify-between">
                <span class="rounded-full bg-main/15 px-2.5 py-0.5 text-xs font-medium text-main-dark dark:text-main">
                    {{ tag }}
                </span>
                <span class="inline-flex items-center gap-1 text-xs text-slate-400">
                    <Calendar class="h-3 w-3" />
                    {{ date }}
                </span>
            </div>
            <h3 class="mb-2 text-base font-bold text-slate-900 group-hover:text-main-dark dark:text-white dark:group-hover:text-main transition-colors">
                {{ title }}
            </h3>
            <p class="text-xs leading-relaxed text-slate-600 dark:text-slate-400">
                {{ description }}
            </p>
        </div>
        <div class="mt-4 flex items-center gap-1 text-xs font-semibold text-main-dark dark:text-main">
            <span>Читати на сайті ПФУ</span>
            <ArrowUpRight class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
        </div>
    </component>
</template>
