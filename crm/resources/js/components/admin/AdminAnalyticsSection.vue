<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useI18n } from '@/composables/useI18n';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    LineElement,
    BarElement,
    PointElement,
    CategoryScale,
    LinearScale,
    ArcElement,
    Filler
} from 'chart.js';
import { Line, Doughnut, Bar } from 'vue-chartjs';
import {
    Users,
    Calculator,
    FileText,
    TrendingUp,
    Globe,
    Cpu,
    Activity,
    PieChart,
    BarChart2,
    RefreshCw
} from '@lucide/vue';

ChartJS.register(
    Title,
    Tooltip,
    Legend,
    LineElement,
    BarElement,
    PointElement,
    CategoryScale,
    LinearScale,
    ArcElement,
    Filler
);

const { t } = useI18n();

const loading = ref(true);
const analyticsData = ref<any>(null);

const fetchAnalytics = async () => {
    loading.value = true;
    try {
        const response = await fetch('/admin/analytics', {
            headers: { Accept: 'application/json' },
        });
        if (response.ok) {
            const json = await response.json();
            analyticsData.value = json.data;
        }
    } catch (e) {
        console.error('Failed to fetch admin analytics:', e);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchAnalytics();
});

// Chart 1: 30-Day Activity Timeline Line Chart Data
const timelineChartData = computed(() => {
    if (!analyticsData.value?.timeline) return null;
    const labels = analyticsData.value.timeline.map((item: any) => item.date);
    const calculations = analyticsData.value.timeline.map((item: any) => item.calculations);
    const registrations = analyticsData.value.timeline.map((item: any) => item.registrations);

    return {
        labels,
        datasets: [
            {
                label: t('analytics.pensionCalculations'),
                data: calculations,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.15)',
                fill: true,
                tension: 0.4,
                pointRadius: 3,
            },
            {
                label: t('analytics.newRegistrations'),
                data: registrations,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.15)',
                fill: true,
                tension: 0.4,
                pointRadius: 3,
            },
        ],
    };
});

// Chart 2: Entry Method Popularity Doughnut Chart Data (OCR vs Manual)
const entryMethodChartData = computed(() => {
    if (!analyticsData.value?.entry_methods) return null;
    const em = analyticsData.value.entry_methods;

    return {
        labels: [t('analytics.ocrDocuments'), t('analytics.manualEntry')],
        datasets: [
            {
                data: [em.ocr_count, em.manual_count],
                backgroundColor: ['#6366f1', '#06b6d4'],
                hoverBackgroundColor: ['#4f46e5', '#0891b2'],
                borderWidth: 2,
                borderColor: '#18181b',
            },
        ],
    };
});

// Chart 3: Browser Distribution Bar Chart Data
const browserChartData = computed(() => {
    if (!analyticsData.value?.browsers) return null;
    const b = analyticsData.value.browsers;
    const labels = Object.keys(b);
    const counts = Object.values(b);

    return {
        labels,
        datasets: [
            {
                label: t('analytics.usersCard'),
                data: counts,
                backgroundColor: ['#3b82f6', '#f97316', '#14b8a6', '#06b6d4', '#ef4444', '#a855f7'],
                borderRadius: 8,
            },
        ],
    };
});

// Chart 4: OS & Device Distribution Chart Data
const osChartData = computed(() => {
    if (!analyticsData.value?.operating_systems) return null;
    const os = analyticsData.value.operating_systems;
    const labels = Object.keys(os);
    const counts = Object.values(os);

    return {
        labels,
        datasets: [
            {
                label: t('analytics.operatingSystems'),
                data: counts,
                backgroundColor: ['#8b5cf6', '#ec4899', '#10b981', '#f59e0b', '#64748b'],
                borderRadius: 8,
            },
        ],
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            labels: {
                color: '#9ca3af',
                font: { family: 'Instrument Sans, sans-serif', size: 12 },
            },
        },
    },
    scales: {
        x: {
            ticks: { color: '#9ca3af' },
            grid: { color: 'rgba(255, 255, 255, 0.05)' },
        },
        y: {
            ticks: { color: '#9ca3af' },
            grid: { color: 'rgba(255, 255, 255, 0.05)' },
        },
    },
};

const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom' as const,
            labels: {
                color: '#9ca3af',
                font: { family: 'Instrument Sans, sans-serif', size: 12 },
            },
        },
    },
};
</script>

<template>
    <div class="space-y-8">
        <!-- Section Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-zinc-900 p-6 rounded-3xl border border-slate-200 dark:border-zinc-800 shadow-xs">
            <div>
                <div class="flex items-center gap-2">
                    <Activity class="h-6 w-6 text-main" />
                    <h2 class="text-xl font-black text-slate-900 dark:text-white">{{ t('analytics.title') }}</h2>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    {{ t('analytics.subtitle') }}
                </p>
            </div>
            <button
                @click="fetchAnalytics"
                class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-xs font-bold text-slate-700 dark:text-slate-200 transition-all"
            >
                <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': loading }" />
                {{ t('analytics.refreshData') }}
            </button>
        </div>

        <!-- Loading Spinner -->
        <div v-if="loading" class="flex justify-center items-center py-20">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-main"></div>
        </div>

        <template v-else-if="analyticsData">
            <!-- 1. Top Summary Key Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Users -->
                <div class="bg-white dark:bg-zinc-900/80 p-5 rounded-3xl border border-slate-200 dark:border-zinc-800 shadow-xs relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-slate-400">{{ t('analytics.usersCard') }}</span>
                        <div class="p-2 bg-blue-500/10 rounded-xl text-blue-500">
                            <Users class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-3xl font-black text-slate-900 dark:text-white">{{ analyticsData.summary.total_users }}</span>
                        <span class="text-xs text-slate-400 ml-2">({{ analyticsData.summary.active_users_30d }} {{ t('analytics.activeLabel') }})</span>
                    </div>
                </div>

                <!-- Total Pension Calculations -->
                <div class="bg-white dark:bg-zinc-900/80 p-5 rounded-3xl border border-slate-200 dark:border-zinc-800 shadow-xs relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-slate-400">{{ t('analytics.calculatedPensionsCard') }}</span>
                        <div class="p-2 bg-emerald-500/10 rounded-xl text-emerald-500">
                            <Calculator class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-3xl font-black text-slate-900 dark:text-white">{{ analyticsData.summary.total_calculations }}</span>
                        <span class="text-xs text-emerald-500 font-semibold ml-2">{{ t('analytics.avgLabel') }}: {{ analyticsData.summary.avg_pension_amount }} ₴</span>
                    </div>
                </div>

                <!-- OCR vs Manual Ratio -->
                <div class="bg-white dark:bg-zinc-900/80 p-5 rounded-3xl border border-slate-200 dark:border-zinc-800 shadow-xs relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-slate-400">{{ t('analytics.entryPopularityCard') }}</span>
                        <div class="p-2 bg-indigo-500/10 rounded-xl text-indigo-500">
                            <FileText class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-black text-slate-900 dark:text-white">{{ analyticsData.entry_methods.ocr_percentage }}%</span>
                        <span class="text-xs text-slate-400">{{ t('analytics.ocrUploadLabel') }}</span>
                    </div>
                </div>

                <!-- Avg Wage Coefficient Kz -->
                <div class="bg-white dark:bg-zinc-900/80 p-5 rounded-3xl border border-slate-200 dark:border-zinc-800 shadow-xs relative overflow-hidden">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase text-slate-400">{{ t('analytics.avgKzCard') }}</span>
                        <div class="p-2 bg-purple-500/10 rounded-xl text-purple-500">
                            <TrendingUp class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-3xl font-black text-slate-900 dark:text-white">{{ analyticsData.summary.avg_wage_coefficient }}</span>
                        <span class="text-xs text-purple-400 font-semibold ml-2">{{ t('analytics.wageCoeffLabel') }}</span>
                    </div>
                </div>
            </div>

            <!-- 2. Charts Row 1: Activity Timeline & Entry Method Popularity -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Timeline Line Chart (Span 2) -->
                <div class="lg:col-span-2 bg-white dark:bg-zinc-900 p-6 rounded-3xl border border-slate-200 dark:border-zinc-800 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <BarChart2 class="h-5 w-5 text-main" />
                            <h3 class="font-extrabold text-sm text-slate-900 dark:text-white">{{ t('analytics.activityTimeline') }}</h3>
                        </div>
                        <span class="text-[11px] font-semibold px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-zinc-800 text-slate-500">{{ t('analytics.last30Days') }}</span>
                    </div>
                    <div class="h-64 relative w-full">
                        <Line v-if="timelineChartData" :data="timelineChartData" :options="chartOptions" />
                    </div>
                </div>

                <!-- Entry Method Popularity Doughnut Chart (Span 1) -->
                <div class="bg-white dark:bg-zinc-900 p-6 rounded-3xl border border-slate-200 dark:border-zinc-800 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <PieChart class="h-5 w-5 text-indigo-500" />
                            <h3 class="font-extrabold text-sm text-slate-900 dark:text-white">{{ t('analytics.entryMethodPopularity') }}</h3>
                        </div>
                    </div>
                    <div class="h-64 relative w-full flex items-center justify-center">
                        <Doughnut v-if="entryMethodChartData" :data="entryMethodChartData" :options="doughnutOptions" />
                    </div>
                </div>
            </div>

            <!-- 3. Charts Row 2: Browsers & Operating Systems -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Browser Distribution -->
                <div class="bg-white dark:bg-zinc-900 p-6 rounded-3xl border border-slate-200 dark:border-zinc-800 shadow-xs">
                    <div class="flex items-center gap-2 mb-4">
                        <Globe class="h-5 w-5 text-blue-500" />
                        <h3 class="font-extrabold text-sm text-slate-900 dark:text-white">{{ t('analytics.userBrowsers') }}</h3>
                    </div>
                    <div class="h-60 relative w-full">
                        <Bar v-if="browserChartData" :data="browserChartData" :options="chartOptions" />
                    </div>
                </div>

                <!-- Operating System Breakdown -->
                <div class="bg-white dark:bg-zinc-900 p-6 rounded-3xl border border-slate-200 dark:border-zinc-800 shadow-xs">
                    <div class="flex items-center gap-2 mb-4">
                        <Cpu class="h-5 w-5 text-purple-500" />
                        <h3 class="font-extrabold text-sm text-slate-900 dark:text-white">{{ t('analytics.userOS') }}</h3>
                    </div>
                    <div class="h-60 relative w-full">
                        <Bar v-if="osChartData" :data="osChartData" :options="chartOptions" />
                    </div>
                </div>
            </div>

            <!-- 4. Live Audit Log Activity Feed Table -->
            <div class="bg-white dark:bg-zinc-900 rounded-3xl border border-slate-200 dark:border-zinc-800 shadow-xs overflow-hidden">
                <div class="p-6 border-b border-slate-100 dark:border-zinc-800 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <Activity class="h-5 w-5 text-main" />
                        <h3 class="font-extrabold text-sm text-slate-900 dark:text-white">{{ t('analytics.recentActivity') }}</h3>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-400">{{ t('analytics.last15Actions') }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-zinc-900/60 text-slate-400 uppercase font-bold text-[10px]">
                            <tr>
                                <th class="px-6 py-3.5">{{ t('analytics.user') }}</th>
                                <th class="px-6 py-3.5">{{ t('analytics.action') }}</th>
                                <th class="px-6 py-3.5">{{ t('analytics.browser') }}</th>
                                <th class="px-6 py-3.5">{{ t('analytics.ip') }}</th>
                                <th class="px-6 py-3.5 text-right">{{ t('analytics.time') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-zinc-800">
                            <tr v-for="log in analyticsData.recent_logs" :key="log.id" class="hover:bg-slate-50/50 dark:hover:bg-zinc-850/50 transition-all">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-900 dark:text-white">{{ log.user_name }}</div>
                                    <div class="text-[11px] text-slate-400" v-if="log.user_email">{{ log.user_email }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-zinc-700">
                                        {{ log.action }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-700 dark:text-slate-300">{{ log.browser }} ({{ log.os }})</div>
                                    <div class="text-[10px] text-slate-400">{{ log.device }}</div>
                                </td>
                                <td class="px-6 py-4 font-mono text-[11px] text-slate-500 dark:text-slate-400">
                                    {{ log.ip_address }}
                                </td>
                                <td class="px-6 py-4 text-right font-medium text-slate-400">
                                    {{ log.created_at }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    </div>
</template>
