<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import LangSelect from '@/components/landing/atoms/LangSelect.vue';
import ThemeToggleBtn from '@/components/landing/atoms/ThemeToggleBtn.vue';
import AppLogo from '@/components/navigation/atoms/AppLogo.vue';
import { Toaster } from '@/components/ui/sonner';
import { useDocumentNotifier } from '@/composables/useDocumentNotifier';
import { useI18n } from '@/composables/useI18n';
import { dashboard, home } from '@/routes';
import NotificationDropdown from '../molecules/NotificationDropdown.vue';
import UserAvatarMenu from '../molecules/UserAvatarMenu.vue';

const { t } = useI18n();
const page = usePage();

// Initialize document notifier on DashboardHeader so every dashboard page gets real-time toasts and notification updates
useDocumentNotifier();

const isHomeActive = computed(() => page.url === '/' || page.url === '');
const isDashboardActive = computed(() => page.url.startsWith('/dashboard'));
</script>

<template>
    <header
        class="sticky top-0 z-40 w-full border-b border-slate-200/80 bg-white/80 backdrop-blur-xl dark:border-zinc-800/80 dark:bg-black/80"
    >
        <Toaster position="top-right" richColors />

        <div
            class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8"
        >
            <div class="flex items-center gap-6">
                <!-- Brand Logo & Title -->
                <div class="group flex items-center gap-3">
                    <AppLogo
                        class="h-9 w-9 transition-transform group-hover:scale-105"
                    />
                </div>

                <!-- Navigation Links -->
                <nav
                    class="hidden items-center gap-4 border-l border-slate-200/80 pl-6 sm:flex dark:border-zinc-800/80"
                >
                    <Link
                        :href="home.url()"
                        class="text-xs font-extrabold transition-all"
                        :class="
                            isHomeActive
                                ? 'font-black text-main underline decoration-2 underline-offset-4 dark:text-main'
                                : 'text-slate-600 hover:text-slate-900 dark:text-zinc-400 dark:hover:text-white'
                        "
                    >
                        {{ t('header.home') }}
                    </Link>
                    <Link
                        :href="dashboard.url()"
                        class="text-xs font-extrabold transition-all"
                        :class="
                            isDashboardActive
                                ? 'font-black text-main underline decoration-2 underline-offset-4 dark:text-main'
                                : 'text-slate-600 hover:text-slate-900 dark:text-zinc-400 dark:hover:text-white'
                        "
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
