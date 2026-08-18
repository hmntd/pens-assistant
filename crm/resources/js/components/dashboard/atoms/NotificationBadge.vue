<script setup lang="ts">
defineProps<{
    count: number;
}>();
</script>

<template>
    <span
        v-if="count > 0"
        class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-main text-[10px] font-extrabold text-slate-950 ring-2 ring-white dark:ring-black animate-pulse"
    >
        {{ count }}
    </span>
</template>
