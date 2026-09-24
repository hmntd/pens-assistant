<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogo from '@/components/navigation/atoms/AppLogo.vue';
import { home } from '@/routes';
</script>

<template>
    <Link
        :href="home.url()"
        class="flex cursor-pointer items-center gap-2 transition-transform duration-200"
    >
        <AppLogo />
    </Link>
</template>
