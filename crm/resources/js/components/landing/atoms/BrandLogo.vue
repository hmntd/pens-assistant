<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import { Link } from '@inertiajs/vue3';
import { home } from '@/routes';
</script>

<template>
    <Link :href="home()" class="flex items-center gap-2 transition-transform duration-200 cursor-pointer">
        <AppLogo />
    </Link>
</template>
