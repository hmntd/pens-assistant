<script setup lang="ts">
import { Component } from 'vue';

defineProps<{
    title: string;
    description: string;
    icon: Component;
    iconBgClass?: string;
    iconColorClass?: string;
}>();
</script>

<template>
    <div class="flex items-center gap-3">
        <div 
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl transition-colors"
            :class="[
                iconBgClass || 'bg-slate-500/10 dark:bg-slate-500/20',
                iconColorClass || 'text-slate-600 dark:text-slate-400'
            ]"
        >
            <component :is="icon" class="h-5 w-5" />
        </div>
        <div>
            <h4 class="text-sm font-bold text-slate-900 dark:text-slate-100">
                {{ title }}
            </h4>
            <p class="text-xs text-slate-500 dark:text-zinc-400">
                {{ description }}
            </p>
        </div>
    </div>
</template>
