<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import ParticalSphere from '@/components/auth/atoms/ParticalSphere.vue';
import { home } from '@/routes';

const page = usePage();
const name = page.props.name;

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="relative grid h-dvh flex-col items-center justify-center overflow-hidden bg-slate-50 font-sans text-slate-900 selection:bg-[#31DE97] selection:text-slate-950 dark:bg-black dark:text-slate-100 px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
        <!-- 3D Particle Sphere Background Animation -->
        <div class="pointer-events-none absolute inset-0 z-0 h-full w-full overflow-hidden opacity-60 dark:opacity-80">
            <ParticalSphere />
        </div>

        <div class="relative z-10 hidden h-full flex-col p-10 text-white lg:flex border-r border-slate-200/50 dark:border-zinc-800/50">
            <Link
                :href="home()"
                class="relative z-20 flex items-center text-lg font-medium"
            >
                <AppLogoIcon class="mr-2 size-8 fill-current text-[#31DE97]" />
                {{ name }}
            </Link>
        </div>

        <div class="relative z-10 lg:p-8">
            <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
                <div class="flex flex-col space-y-2 text-center">
                    <h1 class="text-xl font-medium tracking-tight" v-if="title">
                        {{ title }}
                    </h1>
                    <p class="text-sm text-slate-600 dark:text-slate-400" v-if="description">
                        {{ description }}
                    </p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
