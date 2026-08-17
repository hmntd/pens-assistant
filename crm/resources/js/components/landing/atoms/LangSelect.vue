<script setup lang="ts">
import { useI18n, type Locale } from '@/composables/useI18n';
import { Globe } from '@lucide/vue';

const { locale, setLocale } = useI18n();

function onChange(event: Event) {
    const target = event.target as HTMLSelectElement;
    setLocale(target.value as Locale);
}
</script>

<template>
    <div class="relative inline-flex items-center">
        <Globe class="absolute left-2.5 h-3.5 w-3.5 text-slate-400 pointer-events-none" />
        <select
            :value="locale"
            @change="onChange"
            class="h-9 appearance-none rounded-lg border border-slate-200 bg-white pl-8 pr-7 text-xs font-medium text-slate-700 shadow-sm transition-colors hover:border-slate-300 focus:border-[#31DE97] focus:outline-none focus:ring-1 focus:ring-[#31DE97] dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-slate-700"
        >
            <option value="uk">🇺🇦 UK</option>
            <option value="en">🇬🇧 EN</option>
        </select>
        <span class="pointer-events-none absolute right-2 text-[10px] text-slate-400">▼</span>
    </div>
</template>
