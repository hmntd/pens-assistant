<script setup lang="ts">
import type { Component } from 'vue';
import ChannelHeader from '../atoms/ChannelHeader.vue';

defineProps<{
    title: string;
    description: string;
    icon: Component;
    enabled: boolean;
    iconBgClass?: string;
    iconColorClass?: string;
}>();

defineEmits<{
    (e: 'update:enabled', value: boolean): void;
}>();
</script>

<template>
    <div
        class="rounded-2xl border border-slate-200/80 bg-slate-50/50 p-5 transition-all hover:border-main dark:border-zinc-800/80 dark:bg-zinc-900/30 dark:hover:border-main"
    >
        <div class="flex items-start justify-between gap-4">
            <ChannelHeader
                :title="title"
                :description="description"
                :icon="icon"
                :icon-bg-class="iconBgClass"
                :icon-color-class="iconColorClass"
            />
            <label class="relative inline-flex cursor-pointer items-center">
                <input
                    type="checkbox"
                    :checked="enabled"
                    @change="
                        $emit(
                            'update:enabled',
                            ($event.target as HTMLInputElement).checked,
                        )
                    "
                    class="peer sr-only"
                />
                <div
                    class="peer h-6 w-11 rounded-full bg-slate-300 peer-checked:bg-[#31DE97]! peer-focus:outline-none after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full dark:bg-zinc-700 peer-checked:dark:bg-[#31DE97]!"
                ></div>
            </label>
        </div>

        <slot v-if="enabled" />
    </div>
</template>
