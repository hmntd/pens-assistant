<script setup lang="ts">
import type { Component } from 'vue';
import TabButton from '../atoms/TabButton.vue';

export interface TabItem {
    id: string;
    label: string;
    icon: Component;
}

defineProps<{
    tabs: TabItem[];
    activeTabIndex: number;
}>();

defineEmits<{
    (e: 'switch-tab', index: number): void;
}>();
</script>

<template>
    <div class="border-b border-slate-200/80 bg-white/50 backdrop-blur-md dark:border-zinc-800/80 dark:bg-zinc-950/50">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <nav class="relative flex space-x-2 sm:space-x-8 overflow-x-auto no-scrollbar py-2" aria-label="Dashboard sections">
                <TabButton
                    v-for="(tab, index) in tabs"
                    :key="tab.id"
                    :label="tab.label"
                    :icon="tab.icon"
                    :active="activeTabIndex === index"
                    @click="$emit('switch-tab', index)"
                />
            </nav>
        </div>
    </div>
</template>
