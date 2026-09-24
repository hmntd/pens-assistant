<script setup lang="ts">
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
    RefreshCw,
} from '@lucide/vue';
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
    Filler,
} from 'chart.js';
import { ref, computed, onMounted } from 'vue';
import { Line, Doughnut, Bar } from 'vue-chartjs';
import { useI18n } from '@/composables/useI18n';

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
    Filler,
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
    if (!analyticsData.value?.timeline) {
        return null;
    }

    const labels = analyticsData.value.timeline.map((item: any) => item.date);
    const calculations = analyticsData.value.timeline.map(
        (item: any) => item.calculations,
    );
    const registrations = analyticsData.value.timeline.map(
        (item: any) => item.registrations,
    );

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
    if (!analyticsData.value?.entry_methods) {
        return null;
    }

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
    if (!analyticsData.value?.browsers) {
        return null;
    }

    const b = analyticsData.value.browsers;
    const labels = Object.keys(b);
    const counts = Object.values(b) as number[];

    return {
        labels,
        datasets: [
            {
                label: t('analytics.usersCard'),
                data: counts,
                backgroundColor: [
                    '#3b82f6',
                    '#f97316',
                    '#14b8a6',
                    '#06b6d4',
                    '#ef4444',
                    '#a855f7',
                ],
                borderRadius: 8,
            },
        ],
    };
});

// Chart 4: OS & Device Distribution Chart Data
const osChartData = computed(() => {
    if (!analyticsData.value?.operating_systems) {
        return null;
    }

    const os = analyticsData.value.operating_systems;
    const labels = Object.keys(os);
    const counts = Object.values(os) as number[];

    return {
        labels,
        datasets: [
            {
                label: t('analytics.operatingSystems'),
                data: counts,
                backgroundColor: [
                    '#8b5cf6',
                    '#ec4899',
                    '#10b981',
                    '#f59e0b',
                    '#64748b',
                ],
                borderRadius: 8,
            },
        ],
    };
});

const chartOptions: any = {
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
        <div
            class="flex flex-col justify-between gap-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-xs sm:flex-row sm:items-center dark:border-zinc-800 dark:bg-zinc-900"
        >
            <div>
                <div class="flex items-center gap-2">
                    <Activity class="h-6 w-6 text-main" />
                    <h2
                        class="text-xl font-black text-slate-900 dark:text-white"
                    >
                        {{ t('analytics.title') }}
                    </h2>
                </div>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    {{ t('analytics.subtitle') }}
                </p>
            </div>
            <button
                @click="fetchAnalytics"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-100 px-4 py-2 text-xs font-bold text-slate-700 transition-all hover:bg-slate-200 dark:bg-zinc-800 dark:text-slate-200 dark:hover:bg-zinc-700"
            >
                <RefreshCw
                    class="h-4 w-4"
                    :class="{ 'animate-spin': loading }"
                />
                {{ t('analytics.refreshData') }}
            </button>
        </div>

        <!-- Loading Spinner -->
        <div v-if="loading" class="flex items-center justify-center py-20">
            <div
                class="h-12 w-12 animate-spin rounded-full border-b-2 border-main"
            ></div>
        </div>

        <template v-else-if="analyticsData">
            <!-- 1. Top Summary Key Metric Cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Total Users -->
                <div
                    class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900/80"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-bold text-slate-400 uppercase"
                            >{{ t('analytics.usersCard') }}</span
                        >
                        <div
                            class="rounded-xl bg-blue-500/10 p-2 text-blue-500"
                        >
                            <Users class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <span
                            class="text-3xl font-black text-slate-900 dark:text-white"
                            >{{ analyticsData.summary.total_users }}</span
                        >
                        <span class="ml-2 text-xs text-slate-400"
                            >({{ analyticsData.summary.active_users_30d }}
                            {{ t('analytics.activeLabel') }})</span
                        >
                    </div>
                </div>

                <!-- Total Pension Calculations -->
                <div
                    class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900/80"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-bold text-slate-400 uppercase"
                            >{{ t('analytics.calculatedPensionsCard') }}</span
                        >
                        <div
                            class="rounded-xl bg-emerald-500/10 p-2 text-emerald-500"
                        >
                            <Calculator class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <span
                            class="text-3xl font-black text-slate-900 dark:text-white"
                            >{{
                                analyticsData.summary.total_calculations
                            }}</span
                        >
                        <span
                            class="ml-2 text-xs font-semibold text-emerald-500"
                            >{{ t('analytics.avgLabel') }}:
                            {{ analyticsData.summary.avg_pension_amount }}
                            ₴</span
                        >
                    </div>
                </div>

                <!-- OCR vs Manual Ratio -->
                <div
                    class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900/80"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-bold text-slate-400 uppercase"
                            >{{ t('analytics.entryPopularityCard') }}</span
                        >
                        <div
                            class="rounded-xl bg-indigo-500/10 p-2 text-indigo-500"
                        >
                            <FileText class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span
                            class="text-3xl font-black text-slate-900 dark:text-white"
                            >{{
                                analyticsData.entry_methods.ocr_percentage
                            }}%</span
                        >
                        <span class="text-xs text-slate-400">{{
                            t('analytics.ocrUploadLabel')
                        }}</span>
                    </div>
                </div>

                <!-- Avg Wage Coefficient Kz -->
                <div
                    class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900/80"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-bold text-slate-400 uppercase"
                            >{{ t('analytics.avgKzCard') }}</span
                        >
                        <div
                            class="rounded-xl bg-purple-500/10 p-2 text-purple-500"
                        >
                            <TrendingUp class="h-5 w-5" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <span
                            class="text-3xl font-black text-slate-900 dark:text-white"
                            >{{
                                analyticsData.summary.avg_wage_coefficient
                            }}</span
                        >
                        <span
                            class="ml-2 text-xs font-semibold text-purple-400"
                            >{{ t('analytics.wageCoeffLabel') }}</span
                        >
                    </div>
                </div>
            </div>

            <!-- Monitoring & Infrastructure Section -->
            <div
                class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div class="mb-4 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <Cpu class="h-5 w-5 text-amber-500" />
                        <h3
                            class="text-sm font-extrabold text-slate-900 dark:text-white"
                        >
                            {{ t('analytics.monitoringTitle') }}
                        </h3>
                    </div>
                    <span
                        class="rounded-lg bg-amber-500/10 px-2.5 py-1 text-[10px] font-bold tracking-wider text-amber-500 uppercase"
                        >{{ t('analytics.adminProtected') }}</span
                    >
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <a
                        href="/grafana/"
                        target="_blank"
                        class="group flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-100/70 p-4 transition-all hover:border-amber-500/50 hover:bg-amber-500/5 dark:border-zinc-800 dark:bg-zinc-950/60"
                    >
                        <div>
                            <div
                                class="flex items-center gap-2 text-sm font-bold text-slate-900 transition-colors group-hover:text-amber-500 dark:text-white"
                            >
                                <span>{{ t('analytics.grafanaTitle') }}</span>
                            </div>
                            <p
                                class="mt-1 text-xs font-medium text-slate-600 dark:text-zinc-300"
                            >
                                {{ t('analytics.grafanaDesc') }}
                            </p>
                        </div>
                        <span
                            class="rounded-xl bg-amber-500 px-3 py-1.5 text-xs font-bold text-slate-950 shadow-xs transition-transform group-hover:scale-105"
                            >{{ t('analytics.openLink') }}</span
                        >
                    </a>

                    <a
                        href="/prometheus/"
                        target="_blank"
                        class="group flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-100/70 p-4 transition-all hover:border-orange-500/50 hover:bg-orange-500/5 dark:border-zinc-800 dark:bg-zinc-950/60"
                    >
                        <div>
                            <div
                                class="flex items-center gap-2 text-sm font-bold text-slate-900 transition-colors group-hover:text-orange-500 dark:text-white"
                            >
                                <span>{{
                                    t('analytics.prometheusTitle')
                                }}</span>
                            </div>
                            <p
                                class="mt-1 text-xs font-medium text-slate-600 dark:text-zinc-300"
                            >
                                {{ t('analytics.prometheusDesc') }}
                            </p>
                        </div>
                        <span
                            class="rounded-xl bg-orange-500 px-3 py-1.5 text-xs font-bold text-slate-950 shadow-xs transition-transform group-hover:scale-105"
                            >{{ t('analytics.openLink') }}</span
                        >
                    </a>

                    <a
                        href="/cadvisor/"
                        target="_blank"
                        class="group flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-100/70 p-4 transition-all hover:border-blue-500/50 hover:bg-blue-500/5 dark:border-zinc-800 dark:bg-zinc-950/60"
                    >
                        <div>
                            <div
                                class="flex items-center gap-2 text-sm font-bold text-slate-900 transition-colors group-hover:text-blue-500 dark:text-white"
                            >
                                <span>{{ t('analytics.cadvisorTitle') }}</span>
                            </div>
                            <p
                                class="mt-1 text-xs font-medium text-slate-600 dark:text-zinc-300"
                            >
                                {{ t('analytics.cadvisorDesc') }}
                            </p>
                        </div>
                        <span
                            class="rounded-xl bg-blue-500 px-3 py-1.5 text-xs font-bold text-white shadow-xs transition-transform group-hover:scale-105"
                            >{{ t('analytics.openLink') }}</span
                        >
                    </a>
                </div>
            </div>

            <!-- 2. Charts Row 1: Activity Timeline & Entry Method Popularity -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Timeline Line Chart (Span 2) -->
                <div
                    class="flex flex-col justify-between rounded-3xl border border-slate-200 bg-white p-6 shadow-xs lg:col-span-2 dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <div class="mb-4 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <BarChart2 class="h-5 w-5 text-main" />
                            <h3
                                class="text-sm font-extrabold text-slate-900 dark:text-white"
                            >
                                {{ t('analytics.activityTimeline') }}
                            </h3>
                        </div>
                        <span
                            class="rounded-lg bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-500 dark:bg-zinc-800"
                            >{{ t('analytics.last30Days') }}</span
                        >
                    </div>
                    <div class="relative h-64 w-full">
                        <Line
                            v-if="timelineChartData"
                            :data="timelineChartData"
                            :options="chartOptions"
                        />
                    </div>
                </div>

                <!-- Entry Method Popularity Doughnut Chart (Span 1) -->
                <div
                    class="flex flex-col justify-between rounded-3xl border border-slate-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <div class="mb-4 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <PieChart class="h-5 w-5 text-indigo-500" />
                            <h3
                                class="text-sm font-extrabold text-slate-900 dark:text-white"
                            >
                                {{ t('analytics.entryMethodPopularity') }}
                            </h3>
                        </div>
                    </div>
                    <div
                        class="relative flex h-64 w-full items-center justify-center"
                    >
                        <Doughnut
                            v-if="entryMethodChartData"
                            :data="entryMethodChartData"
                            :options="doughnutOptions"
                        />
                    </div>
                </div>
            </div>

            <!-- 3. Charts Row 2: Browsers & Operating Systems -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Browser Distribution -->
                <div
                    class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <div class="mb-4 flex items-center gap-2">
                        <Globe class="h-5 w-5 text-blue-500" />
                        <h3
                            class="text-sm font-extrabold text-slate-900 dark:text-white"
                        >
                            {{ t('analytics.userBrowsers') }}
                        </h3>
                    </div>
                    <div class="relative h-60 w-full">
                        <Bar
                            v-if="browserChartData"
                            :data="browserChartData"
                            :options="chartOptions"
                        />
                    </div>
                </div>

                <!-- Operating System Breakdown -->
                <div
                    class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <div class="mb-4 flex items-center gap-2">
                        <Cpu class="h-5 w-5 text-purple-500" />
                        <h3
                            class="text-sm font-extrabold text-slate-900 dark:text-white"
                        >
                            {{ t('analytics.userOS') }}
                        </h3>
                    </div>
                    <div class="relative h-60 w-full">
                        <Bar
                            v-if="osChartData"
                            :data="osChartData"
                            :options="chartOptions"
                        />
                    </div>
                </div>
            </div>

            <!-- 4. Live Audit Log Activity Feed Table -->
            <div
                class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div
                    class="flex items-center justify-between border-b border-slate-100 p-6 dark:border-zinc-800"
                >
                    <div class="flex items-center gap-2">
                        <Activity class="h-5 w-5 text-main" />
                        <h3
                            class="text-sm font-extrabold text-slate-900 dark:text-white"
                        >
                            {{ t('analytics.recentActivity') }}
                        </h3>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-400">{{
                        t('analytics.last15Actions')
                    }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead
                            class="bg-slate-50 text-[10px] font-bold text-slate-400 uppercase dark:bg-zinc-900/60"
                        >
                            <tr>
                                <th class="px-6 py-3.5">
                                    {{ t('analytics.user') }}
                                </th>
                                <th class="px-6 py-3.5">
                                    {{ t('analytics.action') }}
                                </th>
                                <th class="px-6 py-3.5">
                                    {{ t('analytics.browser') }}
                                </th>
                                <th class="px-6 py-3.5">
                                    {{ t('analytics.ip') }}
                                </th>
                                <th class="px-6 py-3.5 text-right">
                                    {{ t('analytics.time') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-slate-100 dark:divide-zinc-800"
                        >
                            <tr
                                v-for="log in analyticsData.recent_logs"
                                :key="log.id"
                                class="transition-all hover:bg-slate-50/50 dark:hover:bg-zinc-800/50"
                            >
                                <td class="px-6 py-4">
                                    <div
                                        class="font-bold text-slate-900 dark:text-white"
                                    >
                                        {{ log.user_name }}
                                    </div>
                                    <div
                                        class="text-[11px] text-slate-400"
                                        v-if="log.user_email"
                                    >
                                        {{ log.user_email }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="rounded-lg border border-slate-200 bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-slate-200"
                                    >
                                        {{ log.action }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div
                                        class="font-semibold text-slate-700 dark:text-slate-300"
                                    >
                                        {{ log.browser }} ({{ log.os }})
                                    </div>
                                    <div class="text-[10px] text-slate-400">
                                        {{ log.device }}
                                    </div>
                                </td>
                                <td
                                    class="px-6 py-4 font-mono text-[11px] text-slate-500 dark:text-slate-400"
                                >
                                    {{ log.ip_address }}
                                </td>
                                <td
                                    class="px-6 py-4 text-right font-medium text-slate-400"
                                >
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
