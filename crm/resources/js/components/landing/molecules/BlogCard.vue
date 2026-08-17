<script setup lang="ts">
import { ArrowUpRight, Calendar } from '@lucide/vue';

defineProps<{
    tag: string;
    title: string;
    date: string;
    description: string;
}>();
</script>

<template>
    <article
        class="group flex flex-col justify-between overflow-hidden rounded-2xl border border-slate-200/80 bg-white/70 p-6 shadow-sm backdrop-blur-md transition-all duration-300 hover:-translate-y-1 hover:border-[#31DE97]/50 hover:shadow-lg hover:shadow-[#31DE97]/10 dark:border-slate-800/80 dark:bg-slate-900/60"
    >
        <div>
            <div class="mb-3 flex items-center justify-between">
                <span class="rounded-full bg-[#31DE97]/15 px-2.5 py-0.5 text-xs font-medium text-[#1cb777] dark:text-[#31DE97]">
                    {{ tag }}
                </span>
                <span class="inline-flex items-center gap-1 text-xs text-slate-400">
                    <Calendar class="h-3 w-3" />
                    {{ date }}
                </span>
            </div>
            <h3 class="mb-2 text-base font-bold text-slate-900 group-hover:text-[#1cb777] dark:text-white dark:group-hover:text-[#31DE97] transition-colors">
                {{ title }}
            </h3>
            <p class="text-xs leading-relaxed text-slate-600 dark:text-slate-400">
                {{ description }}
            </p>
        </div>
        <div class="mt-4 flex items-center gap-1 text-xs font-semibold text-[#1cb777] dark:text-[#31DE97]">
            <span>Читати далі</span>
            <ArrowUpRight class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
        </div>
    </article>
</template>
