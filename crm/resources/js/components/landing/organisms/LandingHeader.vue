<script setup lang="ts">
import BrandLogo from '../atoms/BrandLogo.vue';
import ThemeToggleBtn from '../atoms/ThemeToggleBtn.vue';
import LangSelect from '../atoms/LangSelect.vue';
import GithubBtn from '../atoms/GithubBtn.vue';
import HeaderAuthGroup from '../molecules/HeaderAuthGroup.vue';
import { onMounted } from 'vue';

defineProps<{
    showAuthButtons: boolean;
}>();

onMounted(() => {
    console.log('LandingHeader mounted');
})
</script>

<template>
    <header
        class="sticky top-0 z-50 w-full border-b border-slate-200/50 bg-white/75 backdrop-blur-md transition-all duration-300 dark:border-slate-800/50 dark:bg-black/75"
    >
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <!-- Brand Logo -->
            <BrandLogo />

            <!-- Actions Right -->
            <div class="flex items-center gap-3">
                <LangSelect />
                <ThemeToggleBtn />
                <GithubBtn />

                <!-- Floating Sticky Auth Group (visible when scrolled past Hero buttons) -->
                <transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div v-if="showAuthButtons">
                        <HeaderAuthGroup />
                    </div>
                </transition>
            </div>
        </div>
    </header>
</template>
