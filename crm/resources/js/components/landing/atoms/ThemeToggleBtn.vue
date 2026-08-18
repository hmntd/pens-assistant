<script setup lang="ts">
import { useAppearance } from '@/composables/useAppearance';
import { Sun, Moon } from '@lucide/vue';

const { appearance, updateAppearance, resolvedAppearance } = useAppearance();

function toggle() {
    const next = resolvedAppearance.value === 'dark' ? 'light' : 'dark';
    updateAppearance(next);
}
</script>

<template>
    <button
        @click="toggle"
        type="button"
        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900 dark:border-zinc-800 dark:bg-black dark:text-slate-400 dark:hover:bg-zinc-900 dark:hover:text-slate-100"
        :aria-label="resolvedAppearance === 'dark' ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
    >
        <Moon v-if="resolvedAppearance === 'dark'" class="h-4 w-4 text-slate-200" />
        <Sun v-else class="h-4 w-4 text-amber-400" />
    </button>
</template>
