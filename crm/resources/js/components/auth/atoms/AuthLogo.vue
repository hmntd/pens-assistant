<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';
</script>

<template>
    <Link
        :href="home()"
        class="flex flex-col items-center gap-2 font-medium"
    >
        <div class="mb-1 flex h-10 w-10 items-center justify-center rounded-xl bg-slate-900/60 p-1.5 backdrop-blur-md dark:bg-black/60 border border-slate-800/60">
            <AppLogoIcon class="size-8 fill-current text-[#31DE97]" />
        </div>
    </Link>
</template>
