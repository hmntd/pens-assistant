<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" v-bind="$attrs">
        <path d="M 40 30
         a 10 10 0 0 1 10 -10
         h 50
         a 10 10 0 0 1 10 10
         v 15
         a 10 10 0 0 0 10 10
         h 15
         a 10 10 0 0 1 10 10
         v 90
         a 10 10 0 0 1 -10 10
         h -85
         a 10 10 0 0 1 -10 -10
         Z" class="fill-white dark:fill-slate-800 text-slate-900 dark:text-slate-100" stroke="currentColor"
            stroke-width="12" stroke-linejoin="round" />

        <g class="text-slate-900 dark:text-slate-100" stroke="currentColor" stroke-width="12" stroke-linecap="round">
            <path d="M 65 55 h 35" />
            <path d="M 65 75 h 55" />
            <path d="M 65 95 h 55" />
            <path d="M 65 115 h 55" />
            <path d="M 65 135 h 35" />
        </g>

        <g class="text-slate-900 dark:text-slate-100" stroke="currentColor" stroke-width="12" stroke-linejoin="round">
            <path d="M 70 145 v 20 a 50 20 0 0 0 100 0 v -20 Z" fill="var(--main)" />
            <path d="M 70 120 v 20 a 50 20 0 0 0 100 0 v -20 Z" fill="var(--main)" />
            <path d="M 70 95 v 20 a 50 20 0 0 0 100 0 v -20 Z" fill="var(--main)" />
            <ellipse cx="120" cy="95" rx="50" ry="20" fill="var(--main)" />
        </g>
    </svg>
</template>
