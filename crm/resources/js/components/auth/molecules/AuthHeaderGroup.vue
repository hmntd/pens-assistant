<script setup lang="ts">
import AuthHeaderTitle from '../atoms/AuthHeaderTitle.vue';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex flex-col items-center gap-4">
        <AuthHeaderTitle :title="title" :description="description" />
    </div>
</template>
