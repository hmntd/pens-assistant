<script setup lang="ts">
import { Globe } from '@lucide/vue';
import { useI18n } from '@/composables/useI18n';
import type { Locale } from '@/composables/useI18n';

const { locale, setLocale } = useI18n();

function onChange(event: Event) {
    const target = event.target as HTMLSelectElement;
    setLocale(target.value as Locale);
}
</script>

<template>
    <div class="relative inline-flex items-center">
        <Globe
            class="pointer-events-none absolute left-2.5 h-3.5 w-3.5 text-slate-400"
        />
        <select
            :value="locale"
            @change="onChange"
            class="h-9 appearance-none rounded-lg border border-slate-200 bg-white pr-7 pl-8 text-xs font-medium text-slate-700 shadow-sm transition-colors hover:border-slate-300 focus:border-main focus:ring-1 focus:ring-main focus:outline-none dark:border-zinc-800 dark:bg-black dark:text-slate-200 dark:hover:border-zinc-700"
        >
            <option value="uk">🇺🇦 UK</option>
            <option value="en">🇬🇧 EN</option>
        </select>
        <span
            class="pointer-events-none absolute right-2 text-[10px] text-slate-400"
            >▼</span
        >
    </div>
</template>
