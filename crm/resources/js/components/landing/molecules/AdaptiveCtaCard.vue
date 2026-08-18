<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';

defineProps<{
    title: string;
    description: string;
    href: string;
}>();
</script>

<template>
    <Link
        :href="href"
        class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-slate-200/80 bg-gradient-to-br from-white to-slate-50 p-6 shadow-sm backdrop-blur-md transition-all duration-300 hover:-translate-y-1 hover:border-main hover:shadow-lg hover:shadow-main/15 dark:border-zinc-800/80 dark:from-zinc-950/90 dark:to-black/90 dark:hover:border-main"
    >
        <div>
            <h3 class="mb-2 text-base font-bold text-slate-900 group-hover:text-main-dark dark:text-white dark:group-hover:text-main transition-colors">
                {{ title }}
            </h3>
            <p class="text-xs leading-relaxed text-slate-600 dark:text-slate-400">
                {{ description }}
            </p>
        </div>
        <div class="mt-4 flex items-center gap-1.5 text-xs font-semibold text-main-dark dark:text-main">
            <span>Перейти</span>
            <ArrowRight class="h-3.5 w-3.5 transition-transform group-hover:translate-x-1" />
        </div>
    </Link>
</template>
