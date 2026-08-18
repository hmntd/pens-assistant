<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';
import AppLogo from '@/components/AppLogo.vue';
import NotificationDropdown from '../molecules/NotificationDropdown.vue';
import UserAvatarMenu from '../molecules/UserAvatarMenu.vue';
import ThemeToggleBtn from '@/components/landing/atoms/ThemeToggleBtn.vue';
import LangSelect from '@/components/landing/atoms/LangSelect.vue';
import { Toaster } from '@/components/ui/sonner';
import { dashboard, home } from '@/routes';

const { t } = useI18n();
</script>

<template>
    <header class="sticky top-0 z-40 w-full border-b border-slate-200/80 bg-white/80 backdrop-blur-xl dark:border-zinc-800/80 dark:bg-black/80">
        <Toaster position="top-right" richColors />
        
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">

            <div class="flex items-center gap-6">
                <!-- Brand Logo & Title -->
                <div class="flex items-center gap-3 group">
                    <AppLogo class="h-9 w-9 transition-transform group-hover:scale-105" />
                </div>

                <!-- Navigation Links -->
                <nav class="hidden sm:flex items-center gap-4 border-l border-slate-200/80 dark:border-zinc-800/80 pl-6">
                    <Link
                        :href="home()"
                        class="text-xs font-extrabold text-slate-600 hover:text-slate-900 dark:text-zinc-400 dark:hover:text-white transition-colors"
                    >
                        {{ t('header.home') }}
                    </Link>
                    <Link
                        :href="dashboard()"
                        class="text-xs font-extrabold text-slate-600 hover:text-slate-900 dark:text-zinc-400 dark:hover:text-white transition-colors"
                    >
                        {{ t('header.dashboard') }}
                    </Link>
                </nav>
            </div>

            <!-- Right Side: LangSelect, ThemeToggleBtn, Notification Dropdown & User Avatar Menu -->
            <div class="flex items-center gap-2 sm:gap-3">
                <LangSelect />
                <ThemeToggleBtn />
                <NotificationDropdown />
                <UserAvatarMenu />
            </div>

        </div>
    </header>
</template>
