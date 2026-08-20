<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';
import DashboardHeader from '@/components/dashboard/organisms/DashboardHeader.vue';
import DashboardTabBar from '@/components/dashboard/organisms/DashboardTabBar.vue';
import SectionPensionCalc from '@/components/dashboard/organisms/SectionPensionCalc.vue';
import SectionDocuments from '@/components/dashboard/organisms/SectionDocuments.vue';
import SectionUserDetails from '@/components/dashboard/organisms/SectionUserDetails.vue';
import { Calculator, FileText, User } from '@lucide/vue';

defineProps<{
    initialCalculations?: any[];
    initialDocuments?: any[];
    initialTaxHistories?: any[];
}>();

const { t } = useI18n();
const page = usePage();

const tabs = computed(() => [
    { id: 'pension_calc', label: t('dashboard.tabs.pensionCalc'), icon: Calculator },
    { id: 'documents', label: t('dashboard.tabs.documents'), icon: FileText },
    { id: 'profile_details', label: t('dashboard.tabs.profileDetails'), icon: User },
]);

const activeTabIndex = ref(0);
const previousTabIndex = ref(0);

const currentTabComponent = computed(() => {
    switch (tabs.value[activeTabIndex.value].id) {
        case 'pension_calc':
            return SectionPensionCalc;
        case 'documents':
            return SectionDocuments;
        case 'profile_details':
            return SectionUserDetails;
        default:
            return SectionPensionCalc;
    }
});

const transitionName = computed(() => {
    return activeTabIndex.value > previousTabIndex.value ? 'slide-left' : 'slide-right';
});

function switchTab(index: number) {
    if (index === activeTabIndex.value) return;
    previousTabIndex.value = activeTabIndex.value;
    activeTabIndex.value = index;
}

function handleGoToSection(sectionId: string) {
    const targetIndex = tabs.value.findIndex((tItem) => tItem.id === sectionId);
    if (targetIndex >= 0) {
        switchTab(targetIndex);
    }
}

function syncSectionFromUrl() {
    if (typeof window === 'undefined') return;
    const params = new URLSearchParams(window.location.search);
    const section = params.get('section');
    if (!section) return;

    let targetId = '';
    if (section === 'documents' || section === 'document') {
        targetId = 'documents';
    } else if (section === 'pension' || section === 'pension_calc' || section === 'pension-calculations') {
        targetId = 'pension_calc';
    } else if (section === 'details' || section === 'profile' || section === 'profile_details') {
        targetId = 'profile_details';
    }

    if (targetId) {
        handleGoToSection(targetId);
    }
}

onMounted(() => {
    syncSectionFromUrl();
});

watch(
    () => page.url,
    () => {
        syncSectionFromUrl();
    }
);
</script>

<template>
    <Head :title="t('dashboard.title')" />

    <div class="min-h-screen bg-slate-50 font-sans text-slate-900 selection:bg-main selection:text-slate-950 dark:bg-black dark:text-slate-100 flex flex-col">
        
        <!-- Standalone Single Header -->
        <DashboardHeader />

        <!-- Horizontal Tab Bar with Active Line Indicator -->
        <DashboardTabBar
            :tabs="tabs"
            :active-tab-index="activeTabIndex"
            @switch-tab="switchTab"
        />

        <!-- Main SPA Content Container with Horizontal Slide Transitions -->
        <main class="flex-1 mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8 overflow-hidden">
            <Transition :name="transitionName" mode="out-in">
                <component
                    :is="currentTabComponent"
                    :key="activeTabIndex"
                    :initial-calculations="initialCalculations"
                    :initial-documents="initialDocuments"
                    :initial-tax-histories="initialTaxHistories"
                    @go-to-section="handleGoToSection"
                />
            </Transition>
        </main>

    </div>
</template>

<style scoped>
/* Horizontal Slide Left */
.slide-left-enter-active,
.slide-left-leave-active,
.slide-right-enter-active,
.slide-right-leave-active {
    transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.25s ease-out;
}

.slide-left-enter-from {
    opacity: 0;
    transform: translateX(40px);
}
.slide-left-leave-to {
    opacity: 0;
    transform: translateX(-40px);
}

/* Horizontal Slide Right */
.slide-right-enter-from {
    opacity: 0;
    transform: translateX(-40px);
}
.slide-right-leave-to {
    opacity: 0;
    transform: translateX(40px);
}
</style>
