<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';
import DashboardHeader from '@/components/dashboard/organisms/DashboardHeader.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import { User, ShieldCheck, Sun, ArrowLeft } from '@lucide/vue';

const { t } = useI18n();

const navItems = computed(() => [
    {
        title: t('settings.nav.profile'),
        href: editProfile(),
        icon: User,
    },
    {
        title: t('settings.nav.security'),
        href: editSecurity(),
        icon: ShieldCheck,
    },
    {
        title: t('settings.nav.appearance'),
        href: editAppearance(),
        icon: Sun,
    },
]);

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div class="min-h-screen bg-slate-50 font-sans text-slate-900 selection:bg-main selection:text-slate-950 dark:bg-black dark:text-slate-100 flex flex-col">
        
        <!-- Standalone Dashboard Header -->
        <DashboardHeader />

        <!-- Navigation Tab Line Section -->
        <div class="border-b border-slate-200/80 bg-white/50 backdrop-blur-md dark:border-zinc-800/80 dark:bg-zinc-950/50">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <nav class="relative flex space-x-2 sm:space-x-8 overflow-x-auto no-scrollbar py-2" aria-label="Settings sections">
                        <Link
                            v-for="item in navItems"
                            :key="item.title"
                            :href="item.href"
                            class="relative flex items-center gap-2 px-3.5 py-2.5 text-xs sm:text-sm font-bold transition-all duration-200 cursor-pointer rounded-xl shrink-0"
                            :class="[
                                isCurrentOrParentUrl(item.href)
                                    ? 'text-slate-900 dark:text-white bg-slate-100/90 dark:bg-zinc-900/90 shadow-sm'
                                    : 'text-slate-500 hover:text-slate-900 dark:text-zinc-400 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-zinc-900/40'
                            ]"
                        >
                            <component
                                :is="item.icon"
                                class="h-4 w-4 transition-colors"
                                :class="isCurrentOrParentUrl(item.href) ? 'text-main' : 'text-slate-400 dark:text-zinc-500'"
                            />
                            <span>{{ item.title }}</span>

                            <div
                                v-if="isCurrentOrParentUrl(item.href)"
                                class="absolute bottom-0 left-2 right-2 h-0.5 rounded-full bg-main shadow-[0_0_8px_rgba(49,222,151,0.6)]"
                            ></div>
                        </Link>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Main Body -->
        <main class="flex-1 mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-slate-200/80 bg-white/70 p-6 sm:p-8 shadow-sm backdrop-blur-md dark:border-zinc-800/80 dark:bg-zinc-950/80">
                <slot />
            </div>
        </main>

    </div>
</template>
