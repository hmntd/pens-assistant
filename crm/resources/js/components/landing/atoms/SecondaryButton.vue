<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    href?: string;
}>();
</script>

<template>
    <component
        :is="href ? Link : 'button'"
        :href="href"
        class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white/80 px-6 py-3 text-sm font-semibold text-slate-800 shadow-sm backdrop-blur-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-slate-400 hover:bg-slate-50 dark:border-zinc-800 dark:bg-black/80 dark:text-slate-200 dark:hover:border-zinc-700 dark:hover:bg-zinc-900"
    >
        <slot />
    </component>
</template>
