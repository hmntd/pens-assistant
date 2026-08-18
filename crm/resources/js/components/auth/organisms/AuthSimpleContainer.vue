<script setup lang="ts">
import ParticalSphere from '../atoms/ParticalSphere.vue';
import AuthHeaderGroup from '../molecules/AuthHeaderGroup.vue';
import AuthFormCard from '../molecules/AuthFormCard.vue';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="relative flex min-h-svh flex-col items-center justify-center gap-6 overflow-hidden bg-slate-50 p-6 font-sans text-slate-900 selection:bg-[#31DE97] selection:text-slate-950 dark:bg-black dark:text-slate-100 md:p-10">
        <!-- 3D Particle Sphere Background Animation -->
        <div class="pointer-events-none absolute inset-0 z-0 h-full w-full overflow-hidden opacity-80 dark:opacity-95">
            <ParticalSphere />
        </div>

        <!-- Auth Content Card Container -->
        <div class="relative z-10 w-full max-w-md">
            <div class="flex flex-col gap-6">
                <AuthFormCard>
                    <div class="flex flex-col gap-6">
                        <AuthHeaderGroup :title="title" :description="description" />
                        <slot />
                    </div>
                </AuthFormCard>
            </div>
        </div>
    </div>
</template>
