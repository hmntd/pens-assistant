<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { Toaster } from '@/components/ui/sonner';
import { useI18n } from '@/composables/useI18n';
import DashboardHeader from '@/components/dashboard/organisms/DashboardHeader.vue';
import AdminUserSection from '@/components/admin/AdminUserSection.vue';
import AdminCalculationSection from '@/components/admin/AdminCalculationSection.vue';
import AdminDocumentSection from '@/components/admin/AdminDocumentSection.vue';
import AdminTranslationSection from '@/components/admin/AdminTranslationSection.vue';
import AdminAnalyticsSection from '@/components/admin/AdminAnalyticsSection.vue';
import AdminSystemErrorSection from '@/components/admin/AdminSystemErrorSection.vue';
import {
    Users,
    Calculator,
    FileText,
    Globe,
    LayoutDashboard,
    ArrowLeft,
    ShieldCheck,
    BarChart3,
    AlertTriangle,
} from '@lucide/vue';

const { t } = useI18n();

type AdminSection = 'analytics' | 'users' | 'calculations' | 'documents' | 'translations' | 'system-errors';

const activeSection = ref<AdminSection>('analytics');

const navItems = computed(() => [
    { id: 'analytics', label: t('adminNav.analytics'), icon: BarChart3, desc: t('adminNav.analyticsDesc') },
    { id: 'users', label: t('adminNav.users'), icon: Users, desc: t('adminNav.usersDesc') },
    { id: 'calculations', label: t('adminNav.calculations'), icon: Calculator, desc: t('adminNav.calculationsDesc') },
    { id: 'documents', label: t('adminNav.documents'), icon: FileText, desc: t('adminNav.documentsDesc') },
    { id: 'translations', label: t('adminNav.translations'), icon: Globe, desc: t('adminNav.translationsDesc') },
    { id: 'system-errors', label: t('adminNav.systemErrors'), icon: AlertTriangle, desc: t('adminNav.systemErrorsDesc') },
]);

const currentSectionComponent = computed(() => {
    switch (activeSection.value) {
        case 'analytics':
            return AdminAnalyticsSection;
        case 'users':
            return AdminUserSection;
        case 'calculations':
            return AdminCalculationSection;
        case 'documents':
            return AdminDocumentSection;
        case 'translations':
            return AdminTranslationSection;
        case 'system-errors':
            return AdminSystemErrorSection;
        default:
            return AdminAnalyticsSection;
    }
});

function switchSection(sectionId: AdminSection) {
    activeSection.value = sectionId;
    if (typeof window !== 'undefined') {
        const url = new URL(window.location.href);
        url.searchParams.set('section', sectionId);
        window.history.replaceState({}, '', url.toString());
    }
}

function syncSectionFromUrl() {
    if (typeof window === 'undefined') return;
    const params = new URLSearchParams(window.location.search);
    const sec = params.get('section');
    if (sec && ['analytics', 'users', 'calculations', 'documents', 'translations', 'system-errors'].includes(sec)) {
        activeSection.value = sec as AdminSection;
    }
}

onMounted(() => {
    syncSectionFromUrl();
});
</script>

<template>
    <Head :title="`${t('adminNav.title')} | PensAssistant`" />

    <div class="min-h-screen bg-slate-50 dark:bg-black font-sans text-slate-900 dark:text-slate-100 flex flex-col">
        <!-- Standalone Dashboard Header -->
        <DashboardHeader />

        <!-- Toast Notifications -->
        <Toaster position="top-right" richColors />

        <!-- Main Layout with Admin Sidebar -->
        <div class="flex-1 max-w-7xl w-full mx-auto px-4 py-8 sm:px-6 lg:px-8 flex flex-col lg:flex-row gap-8">
            <!-- Sidebar Navigation Container -->
            <aside class="w-full lg:w-72 shrink-0 space-y-4">
                <div class="bg-white dark:bg-zinc-900 rounded-3xl p-5 border border-slate-200 dark:border-zinc-800 shadow-xs space-y-4">
                    <div class="flex items-center gap-2 pb-3 border-b border-slate-100 dark:border-zinc-800">
                        <ShieldCheck class="h-6 w-6 text-main shrink-0" />
                        <div>
                            <h2 class="font-extrabold text-sm text-slate-900 dark:text-white">{{ t('adminNav.title') }}</h2>
                            <p class="text-[11px] text-slate-400">{{ t('adminNav.subtitle') }}</p>
                        </div>
                    </div>

                    <!-- Navigation Items -->
                    <nav class="space-y-1.5">
                        <button
                            v-for="item in navItems"
                            :key="item.id"
                            @click="switchSection(item.id as AdminSection)"
                            class="w-full text-left p-3 rounded-2xl transition-all cursor-pointer flex items-center gap-3 text-xs font-bold"
                            :class="activeSection === item.id ? 'bg-main text-slate-950 shadow-sm' : 'text-slate-600 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-zinc-800 hover:text-slate-900 dark:hover:text-white'"
                        >
                            <component :is="item.icon" class="h-4 w-4 shrink-0" />
                            <div class="truncate">
                                <div>{{ item.label }}</div>
                            </div>
                        </button>
                    </nav>

                    <div class="pt-3 border-t border-slate-100 dark:border-zinc-800">
                        <Link
                            href="/dashboard"
                            class="w-full py-2.5 px-3 rounded-2xl bg-slate-100 dark:bg-zinc-800 hover:bg-slate-200 dark:hover:bg-zinc-700 text-slate-700 dark:text-zinc-200 text-xs font-bold flex items-center justify-center gap-2 transition-colors cursor-pointer"
                        >
                            <ArrowLeft class="h-4 w-4" />
                            <span>{{ t('adminNav.backToDashboard') }}</span>
                        </Link>
                    </div>
                </div>
            </aside>

            <!-- Main Content Area -->
            <main class="flex-1 min-w-0">
                <!-- Section Header Banner -->
                <div class="mb-6 bg-white dark:bg-zinc-900 rounded-3xl p-6 border border-slate-200 dark:border-zinc-800 shadow-xs flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-2xl bg-main/20 text-main-dark dark:text-main flex items-center justify-center shrink-0 border border-main/30">
                            <component :is="navItems.find(i => i.id === activeSection)?.icon || LayoutDashboard" class="h-5 w-5" />
                        </div>
                        <div>
                            <h1 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white">
                                {{ navItems.find(i => i.id === activeSection)?.label }}
                            </h1>
                            <p class="text-xs text-slate-500 dark:text-zinc-400">
                                {{ navItems.find(i => i.id === activeSection)?.desc }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Dynamic Active Section Component -->
                <component :is="currentSectionComponent" />
            </main>
        </div>
    </div>
</template>
