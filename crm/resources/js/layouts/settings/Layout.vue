<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { User, ShieldCheck, Bell } from '@lucide/vue';
import { computed } from 'vue';
import DashboardHeader from '@/components/dashboard/organisms/DashboardHeader.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useI18n } from '@/composables/useI18n';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';

const { t } = useI18n();

const navItems = computed(() => [
    {
        title: t('settings.nav.profile'),
        href: editProfile.url(),
        icon: User,
    },
    {
        title: t('settings.nav.security'),
        href: editSecurity.url(),
        icon: ShieldCheck,
    },
    {
        title: t('settings.nav.notifications'),
        href: '/settings/notifications',
        icon: Bell,
    },
]);

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div
        class="flex min-h-screen flex-col bg-slate-50 font-sans text-slate-900 selection:bg-main selection:text-slate-950 dark:bg-black dark:text-slate-100"
    >
        <!-- Standalone Dashboard Header -->
        <DashboardHeader />

        <!-- Navigation Tab Line Section -->
        <div
            class="border-b border-slate-200/80 bg-white/50 backdrop-blur-md dark:border-zinc-800/80 dark:bg-zinc-950/50"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <nav
                        class="no-scrollbar relative flex space-x-2 overflow-x-auto py-2 sm:space-x-8"
                        aria-label="Settings sections"
                    >
                        <Link
                            v-for="item in navItems"
                            :key="item.title"
                            :href="item.href"
                            class="relative flex shrink-0 cursor-pointer items-center gap-2 rounded-xl px-3.5 py-2.5 text-xs font-bold transition-all duration-200 sm:text-sm"
                            :class="[
                                isCurrentOrParentUrl(item.href)
                                    ? 'bg-slate-100/90 text-slate-900 shadow-sm dark:bg-zinc-900/90 dark:text-white'
                                    : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900 dark:text-zinc-400 dark:hover:bg-zinc-900/40 dark:hover:text-white',
                            ]"
                        >
                            <component
                                :is="item.icon"
                                class="h-4 w-4 transition-colors"
                                :class="
                                    isCurrentOrParentUrl(item.href)
                                        ? 'text-main'
                                        : 'text-slate-400 dark:text-zinc-500'
                                "
                            />
                            <span>{{ item.title }}</span>

                            <div
                                v-if="isCurrentOrParentUrl(item.href)"
                                class="absolute right-2 bottom-0 left-2 h-0.5 rounded-full bg-main shadow-[0_0_8px_rgba(49,222,151,0.6)]"
                            ></div>
                        </Link>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Main Body -->
        <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
            <div
                class="rounded-2xl border border-slate-200/80 bg-white/70 p-6 shadow-sm backdrop-blur-md sm:p-8 dark:border-zinc-800/80 dark:bg-zinc-950/80"
            >
                <slot />
            </div>
        </main>
    </div>
</template>
