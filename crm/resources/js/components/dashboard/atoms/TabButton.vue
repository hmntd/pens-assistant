<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    label: string;
    icon: Component;
    active: boolean;
}>();

defineEmits<{
    (e: 'click'): void;
}>();
</script>

<template>
    <button
        @click="$emit('click')"
        type="button"
        class="relative flex items-center gap-2 px-3.5 py-2.5 text-xs sm:text-sm font-bold transition-all duration-200 cursor-pointer rounded-xl shrink-0"
        :class="[
            active
                ? 'text-slate-900 dark:text-white bg-slate-100/90 dark:bg-zinc-900/90 shadow-sm'
                : 'text-slate-500 hover:text-slate-900 dark:text-zinc-400 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-zinc-900/40'
        ]"
    >
        <component
            :is="icon"
            class="h-4 w-4 transition-colors"
            :class="active ? 'text-main' : 'text-slate-400 dark:text-zinc-500'"
        />
        <span>{{ label }}</span>

        <!-- Animated Active Underline Bar -->
        <div
            v-if="active"
            class="absolute bottom-0 left-2 right-2 h-0.5 rounded-full bg-main shadow-[0_0_8px_rgba(49,222,151,0.6)]"
        ></div>
    </button>
</template>
