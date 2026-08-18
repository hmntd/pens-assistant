<script setup lang="ts">
import AuthSimpleContainer from '@/components/auth/organisms/AuthSimpleContainer.vue';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <AuthSimpleContainer :title="title" :description="description">
        <slot />
    </AuthSimpleContainer>
</template>
