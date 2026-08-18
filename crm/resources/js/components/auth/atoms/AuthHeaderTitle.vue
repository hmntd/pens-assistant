<script setup lang="ts">
defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="space-y-2 text-center">
        <h1 v-if="title" class="text-xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-2xl">
            {{ title }}
        </h1>
        <p v-if="description" class="text-center text-sm text-slate-600 dark:text-slate-400">
            {{ description }}
        </p>
    </div>
</template>
