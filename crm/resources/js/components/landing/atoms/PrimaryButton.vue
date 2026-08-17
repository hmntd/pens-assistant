<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    href?: string;
}>();
</script>

<template>
    <component
        :is="href ? Link : 'button'"
        :href="href"
        class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#31DE97] px-6 py-3 text-sm font-semibold text-slate-950 shadow-lg shadow-[#31DE97]/25 transition-all duration-200 hover:-translate-y-0.5 hover:bg-[#28C586] hover:shadow-xl hover:shadow-[#31DE97]/35 active:translate-y-0 active:scale-95"
    >
        <slot />
    </component>
</template>
