<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';
import {
    setPendingCalculationState,
    clearPendingCalculationState,
    isPendingCalculationActive,
} from '@/composables/useDocumentNotifier';
import {
    Calculator,
    ArrowUpRight,
    CheckCircle2,
    History,
    TrendingUp,
    UserCheck,
    AlertCircle,
    AlertTriangle,
    Info,
    Sparkles,
    Layers,
    Table,
    Clock,
    FileText,
    ChevronDown,
    ChevronRight,
    Download,
    Loader2,
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import MissingDataHighlight from '../atoms/MissingDataHighlight.vue';

import { toast } from 'vue-sonner';

export interface CalculationItem {
    id: number;
    status?: 'pending' | 'completed' | 'failed';
    error_message?: string | null;
    final_pension: number;
    base_pension: number;
    zp_macroeconomic_average?: number;
    kz_wage_coefficient?: number;
    ks_service_coefficient?: number;
    total_service_months?: number;
    recalculate_delta?: number;
    coefficient_multiplier?: number;
    disability_group?: string | null;
    created_at?: string;
    calculation_breakdown?: {
        is_hypothetical?: boolean;
        criteria_met?: boolean;
        hypothetical_disclaimer?: string;
        logs?: string[];
        pre_clamped?: number;
        is_min_clamped?: boolean;
        is_max_clamped?: boolean;
    };
    calculation_logs?: string[];
    applied_benefits?: { benefit: string; name: string; amount: number }[];
    input_parameters?: Record<string, any>;
}

export interface TaxHistoryItem {
    id: number;
    year: number;
    annual_income: number;
    months_worked: number;
}

const props = defineProps<{
    initialCalculations?: CalculationItem[];
    initialTaxHistories?: TaxHistoryItem[];
}>();

const emit = defineEmits<{
    (e: 'go-to-section', sectionId: string): void;
}>();

const { t, locale } = useI18n();
const page = usePage();
const user = computed(() => page.props.auth?.user);

const isSectionLoading = ref(true);
const showHypotheticalModal = ref(false);
const showDetailsModal = ref(false);
const activeDetailTab = ref<'kz' | 'zp' | 'ks' | 'logs'>('kz');

const calculationsList = ref<CalculationItem[]>(props.initialCalculations || []);
const activeResult = ref<CalculationItem | null>(calculationsList.value[0] || null);

const taxHistoriesList = ref<TaxHistoryItem[]>(props.initialTaxHistories || []);

watch(
    () => props.initialTaxHistories,
    (newVal) => {
        if (newVal) {
            taxHistoriesList.value = newVal;
        }
    },
    { deep: true }
);

watch(
    () => props.initialCalculations,
    (newVal) => {
        if (newVal && Array.isArray(newVal)) {
            calculationsList.value = newVal;
            const completed = calculationsList.value.filter((c) => (c.status || 'completed') === 'completed');
            if (completed.length > 0 && (!activeResult.value || activeResult.value.status === 'pending')) {
                activeResult.value = completed[0];
            }
        }
    },
    { deep: true, immediate: true }
);

const form = useForm({
    target_retirement_year: user.value?.target_retirement_year || null,
    disability_group: user.value?.disability_group || 'none',
    service_years: 35,
    enable_hypothetical_projection: false,
});

const isGenderMissing = computed(() => !user.value?.gender);
const isRetirementYearMissing = computed(() => !user.value?.target_retirement_year);
const isInsuranceServiceMissing = computed(() => !taxHistoriesList.value || taxHistoriesList.value.length === 0);

const totalYearsWorked = computed(() => {
    if (!taxHistoriesList.value) return 0;
    return taxHistoriesList.value.length;
});

const currentYear = new Date().getFullYear();

const userAge = computed(() => {
    if (!user.value?.date_of_birth) return 0;
    const dob = new Date(user.value.date_of_birth);
    const today = new Date();
    let age = today.getFullYear() - dob.getFullYear();
    const m = today.getMonth() - dob.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
        age--;
    }
    return age;
});

const hasReachedCriteria = computed(() => {
    if (activeResult.value?.calculation_breakdown?.criteria_met !== undefined) {
        return Boolean(activeResult.value.calculation_breakdown.criteria_met);
    }
    const targetYr = user.value?.target_retirement_year || currentYear;
    const isFutureYear = targetYr > currentYear;
    const isUnderAge = userAge.value < 60;
    const isUnderService = totalYearsWorked.value < 35;
    return !isFutureYear && !isUnderAge && !isUnderService;
});

const isHypothetical = computed(() => {
    if (activeResult.value?.calculation_breakdown?.is_hypothetical !== undefined) {
        return Boolean(activeResult.value.calculation_breakdown.is_hypothetical);
    }
    return Boolean(form.enable_hypothetical_projection);
});

const isCalculationBlocked = computed(() => isGenderMissing.value || isRetirementYearMissing.value || isInsuranceServiceMissing.value);

const isAdmin = computed(() => {
    return Boolean(
        user.value?.is_admin ||
        user.value?.role === 'admin' ||
        (Array.isArray(user.value?.roles) && user.value.roles.some((r: any) => (typeof r === 'string' ? r === 'admin' : r.name === 'admin')))
    );
});

const isLoadingBreakdown = ref(false);
const detailedBreakdown = ref<any[] | null>(null);
const expandedYears = ref<{ [key: number]: boolean }>({});

function toggleYearAccordion(year: number) {
    expandedYears.value[year] = !expandedYears.value[year];
}

async function fetchDetailedBreakdown(calcId: number) {
    if (!calcId) return;
    isLoadingBreakdown.value = true;
    try {
        const response = await fetch(`/pension-calculations/${calcId}/breakdown`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        if (response.ok) {
            const json = await response.json();
            if (json?.success) {
                detailedBreakdown.value = json.data || [];
            }
        }
    } catch (e) {
        console.error('Failed to fetch calculation breakdown:', e);
    } finally {
        isLoadingBreakdown.value = false;
    }
}

function openCalculationDetails(tab: 'kz' | 'zp' | 'ks' | 'logs' = 'kz', item?: CalculationItem) {
    if (item) {
        activeResult.value = item;
    }
    if (tab === 'logs' && !isAdmin.value) {
        activeDetailTab.value = 'kz';
    } else {
        activeDetailTab.value = tab;
    }
    showDetailsModal.value = true;

    if (activeResult.value?.id) {
        fetchDetailedBreakdown(activeResult.value.id);
    }
}

watch(activeResult, (newRes) => {
    if (newRes?.id && showDetailsModal.value) {
        fetchDetailedBreakdown(newRes.id);
    }
});

async function refreshTaxHistories() {
    try {
        const res = await fetch('/documents', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        if (!res.ok) return;

        const json = await res.json();
        if (Array.isArray(json.tax_histories)) {
            taxHistoriesList.value = json.tax_histories;
        }
    } catch (e) {
        // silent fallback
    }
}

const isCalculating = computed(() => {
    const hasPendingInList = calculationsList.value.some((c) => c.status === 'pending');
    if (!hasPendingInList) {
        clearPendingCalculationState();
        return false;
    }
    return true;
});

async function refreshCalculations() {
    try {
        const res = await fetch('/pension-calculations', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        if (!res.ok) return;

        const json = await res.json();
        if (Array.isArray(json.data)) {
            const previousLatestCompletedId = calculationsList.value.find((c) => (c.status || 'completed') === 'completed')?.id;

            calculationsList.value = json.data as CalculationItem[];
            const hasPending = calculationsList.value.some((c) => c.status === 'pending');
            if (!hasPending) {
                clearPendingCalculationState();
            }

            const completed = calculationsList.value.filter((c) => (c.status || 'completed') === 'completed');
            if (completed.length > 0) {
                const latestCompleted = completed[0];
                if (!activeResult.value || activeResult.value.status === 'pending' || latestCompleted.id !== previousLatestCompletedId) {
                    activeResult.value = latestCompleted;
                }
            }
        }
    } catch (e) {
        // silent fallback
    }
}

async function initializeSection() {
    isSectionLoading.value = true;
    if (props.initialTaxHistories && props.initialTaxHistories.length > 0) {
        taxHistoriesList.value = props.initialTaxHistories;
    }
    await Promise.all([
        refreshTaxHistories(),
        refreshCalculations(),
    ]);
    isSectionLoading.value = false;
}

onMounted(() => {
    initializeSection();
    window.addEventListener('documents-updated', refreshTaxHistories);
    window.addEventListener('calculations-updated', refreshCalculations);
});

onUnmounted(() => {
    window.removeEventListener('documents-updated', refreshTaxHistories);
    window.removeEventListener('calculations-updated', refreshCalculations);
});

function getDisabilityLabel(group?: string | null) {
    switch (group) {
        case 'group_1':
            return t('dashboard.disabilityLabels.group1');
        case 'group_2':
            return t('dashboard.disabilityLabels.group2');
        case 'group_3':
            return t('dashboard.disabilityLabels.group3');
        default:
            return t('dashboard.disabilityLabels.none');
    }
}

function runCalculation() {
    if (isCalculationBlocked.value) return;
    setPendingCalculationState();

    // Optimistically insert a pending calculation item immediately into history list
    const tempPendingId = -Date.now();
    const tempPendingItem: CalculationItem = {
        id: tempPendingId,
        status: 'pending',
        final_pension: 0,
        base_pension: 0,
        created_at: new Date().toISOString(),
    };
    if (!calculationsList.value.some((c) => c.status === 'pending')) {
        calculationsList.value.unshift(tempPendingItem);
    }
    activeResult.value = tempPendingItem;

    toast.info(t('notifications.pensionCalculationStartedToast'));
    form.post('/pension-calculations', {
        preserveScroll: true,
        onSuccess: () => {
            window.dispatchEvent(new CustomEvent('notification-created'));
            refreshCalculations();
        },
        onError: () => {
            clearPendingCalculationState();
            refreshCalculations();
            toast.error(t('notifications.ocrFailedToast') || 'Помилка при виконанні розрахунку.');
        },
    });
}
</script>

<template>
    <div class="space-y-8">
        <!-- Section Header -->
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <h2
                    class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                    <Calculator class="h-6 w-6 text-main" />
                    {{ t('dashboard.overview.title') }}
                </h2>
                <p class="text-xs text-slate-500 dark:text-zinc-400">
                    {{ t('dashboard.overview.subtitle') }}
                </p>
            </div>
        </div>

        <!-- Skeleton Loading View -->
        <div v-if="isSectionLoading" class="space-y-8 animate-fade-in">
            <div class="space-y-3">
                <Skeleton class="h-14 w-full rounded-2xl" />
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-12 items-start">
                <!-- Left Card Skeleton -->
                <div
                    class="lg:col-span-5 rounded-2xl border border-slate-200/80 bg-white/70 p-6 backdrop-blur-md dark:border-zinc-800/80 dark:bg-zinc-950/80 space-y-6 self-start h-fit">
                    <div
                        class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-zinc-800/60">
                        <Skeleton class="h-5 w-48 rounded-lg" />
                        <Skeleton class="h-4 w-12 rounded-lg" />
                    </div>
                    <div class="space-y-4">
                        <div v-for="i in 4" :key="i"
                            class="flex justify-between items-center py-2 border-b border-slate-100 dark:border-zinc-900">
                            <Skeleton class="h-4 w-28 rounded-lg" />
                            <Skeleton class="h-4 w-24 rounded-lg" />
                        </div>
                    </div>
                    <Skeleton class="h-11 w-full rounded-xl" />
                </div>

                <!-- Right Card Skeleton -->
                <div class="lg:col-span-7 space-y-6">
                    <div
                        class="rounded-2xl border border-slate-200/80 bg-white/70 p-6 dark:border-zinc-800/80 dark:bg-zinc-950/80 space-y-4">
                        <Skeleton class="h-4 w-36 rounded-lg" />
                        <Skeleton class="h-12 w-64 rounded-xl" />
                        <div class="grid grid-cols-2 gap-4 border-t border-slate-100 pt-4 dark:border-zinc-800">
                            <Skeleton class="h-10 w-full rounded-xl" />
                            <Skeleton class="h-10 w-full rounded-xl" />
                        </div>
                    </div>
                    <div
                        class="rounded-2xl border border-slate-200/80 bg-white/70 p-6 dark:border-zinc-800/80 dark:bg-zinc-950/80 space-y-3">
                        <Skeleton class="h-5 w-40 rounded-lg" />
                        <Skeleton v-for="i in 3" :key="i" class="h-12 w-full rounded-xl" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Real Data View -->
        <div v-else class="space-y-8">
            <!-- Highlighted Missing Data Alerts -->
            <div class="space-y-3">
                <MissingDataHighlight v-if="isGenderMissing"
                    :title="t('gender.requiredTitle')"
                    :description="t('gender.requiredNotice')"
                    @click="emit('go-to-section', 'profile_details')" />

                <MissingDataHighlight v-if="isRetirementYearMissing"
                    :title="t('dashboard.alerts.missingRetirementYearTitle')"
                    :description="t('dashboard.alerts.missingRetirementYearDesc')"
                    @click="emit('go-to-section', 'profile_details')" />

                <MissingDataHighlight v-if="isInsuranceServiceMissing"
                    :title="t('dashboard.alerts.missingServiceTitle')"
                    :description="t('dashboard.alerts.missingServiceDesc')"
                    @click="emit('go-to-section', 'documents')" />
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-12 items-start">
                <!-- Left: Read-Only User Data Summary & Calculation Trigger -->
                <div
                    class="lg:col-span-5 rounded-2xl border border-slate-200/80 bg-white/70 p-6 shadow-sm backdrop-blur-md dark:border-zinc-800/80 dark:bg-zinc-950/80 space-y-6 self-start h-fit">
                    <h3
                        class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-zinc-800/60 pb-3 flex items-center justify-between">
                        <span class="flex items-center gap-2">
                            <UserCheck class="h-4 w-4 text-main" />
                            {{ t('dashboard.overview.userCardTitle') }}
                        </span>
                        <button @click="emit('go-to-section', 'profile_details')" type="button"
                            class="text-xs font-bold text-main-dark dark:text-main hover:underline cursor-pointer">
                            {{ t('dashboard.overview.edit') }}
                        </button>
                    </h3>

                    <!-- Read-Only Profile Parameters -->
                    <div class="space-y-4 text-xs">
                        <div
                            class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-zinc-900">
                            <span class="text-slate-500 dark:text-zinc-400">{{ t('dashboard.overview.fullName')
                                }}</span>
                            <span class="font-bold text-slate-900 dark:text-white">
                                {{ user?.first_name ? `${user.first_name} ${user.last_name || ''}` : (user?.name ||
                                '---') }}
                            </span>
                        </div>

                        <div
                            class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-zinc-900">
                            <span class="text-slate-500 dark:text-zinc-400">{{ t('gender.label') }}</span>
                            <span v-if="user?.gender" class="font-bold text-slate-900 dark:text-white">
                                {{ user.gender === 'MALE' || user.gender === 'male' ? t('gender.male') : t('gender.female') }}
                            </span>
                            <button v-else @click="emit('go-to-section', 'profile_details')" type="button"
                                class="px-2 py-0.5 rounded bg-amber-500/20 text-amber-600 dark:text-amber-400 font-bold hover:underline cursor-pointer">
                                {{ t('gender.notSpecified') }}
                            </button>
                        </div>

                        <div
                            class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-zinc-900">
                            <span class="text-slate-500 dark:text-zinc-400">{{ t('dashboard.overview.disabilityGroup')
                                }}</span>
                            <span class="font-bold text-slate-900 dark:text-white">
                                {{ getDisabilityLabel(user?.disability_group) }}
                            </span>
                        </div>

                        <div
                            class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-zinc-900">
                            <span class="text-slate-500 dark:text-zinc-400">{{ t('dashboard.overview.retirementYear')
                                }}</span>
                            <span v-if="user?.target_retirement_year" class="font-bold text-slate-900 dark:text-white">
                                {{ user.target_retirement_year }} {{ t('dashboard.overview.yearUnit') }}
                            </span>
                            <button v-else @click="emit('go-to-section', 'profile_details')" type="button"
                                class="px-2 py-0.5 rounded bg-amber-500/20 text-amber-600 dark:text-amber-400 font-bold hover:underline cursor-pointer">
                                {{ t('dashboard.overview.notSpecified') }}
                            </button>
                        </div>

                        <div
                            class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-zinc-900">
                            <span class="text-slate-500 dark:text-zinc-400">{{ t('dashboard.overview.confirmedService')
                                }}</span>
                            <span v-if="totalYearsWorked > 0" class="font-bold text-slate-900 dark:text-white">
                                {{ totalYearsWorked }} {{ t('dashboard.overview.yearsWorkedUnit') }}
                            </span>
                            <button v-else @click="emit('go-to-section', 'documents')" type="button"
                                class="px-2 py-0.5 rounded bg-amber-500/20 text-amber-600 dark:text-amber-400 font-bold hover:underline cursor-pointer">
                                {{ t('dashboard.overview.notFilled') }}
                            </button>
                        </div>
                    </div>

                    <!-- Hypothetical Calculation Flag Toggle -->
                    <div class="pt-3 border-t border-slate-100 dark:border-zinc-800/80">
                        <label class="flex items-start justify-between gap-3 p-3 rounded-xl border border-slate-200/80 bg-slate-50/70 hover:bg-slate-100/80 dark:border-zinc-800 dark:bg-zinc-900/60 dark:hover:bg-zinc-900 transition-colors cursor-pointer group">
                            <div class="space-y-0.5 min-w-0">
                                <span class="text-xs font-bold text-slate-900 dark:text-zinc-200 group-hover:text-main-dark dark:group-hover:text-main transition-colors flex items-center gap-1.5">
                                    <Sparkles class="h-3.5 w-3.5 text-amber-500 shrink-0" />
                                    {{ t('dashboard.overview.enableHypotheticalLabel') }}
                                </span>
                                <p class="text-[11px] text-slate-500 dark:text-zinc-400 leading-snug">
                                    {{ t('dashboard.overview.enableHypotheticalDesc') }}
                                </p>
                            </div>
                            <input
                                v-model="form.enable_hypothetical_projection"
                                type="checkbox"
                                class="mt-0.5 h-4 w-4 rounded border-slate-300 text-main focus:ring-main dark:border-zinc-700 dark:bg-zinc-800 cursor-pointer shrink-0"
                            />
                        </label>
                    </div>

                    <!-- Run Calculation Button -->
                    <div class="pt-2 space-y-2">
                        <Button @click="runCalculation" type="button"
                            class="w-full bg-main text-slate-950 hover:bg-main-dark font-bold shadow-md h-11 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                            :disabled="form.processing || isCalculationBlocked">
                            <Calculator class="mr-2 h-4 w-4" />
                            {{ form.processing ? t('dashboard.overview.calculatingBtn') :
                                t('dashboard.overview.calculateBtn') }}
                        </Button>

                        <p v-if="isCalculationBlocked"
                            class="text-[11px] text-amber-600 dark:text-amber-400 font-semibold text-center flex items-center justify-center gap-1">
                            <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                            {{ t('dashboard.overview.blockedNotice') }}
                        </p>
                    </div>
                </div>

                <!-- Right: Calculation Results & Detailed Formula Breakdown -->
                <div class="lg:col-span-7 space-y-6">
                    <!-- Calculation History Log Section (Placed TOP) -->
                    <div v-if="calculationsList.length > 0 || isCalculating"
                        class="rounded-2xl border border-slate-200/80 bg-white/70 p-6 shadow-sm backdrop-blur-md dark:border-zinc-800/80 dark:bg-zinc-950/80 space-y-4">
                        <h4
                            class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-zinc-400 flex items-center gap-1.5">
                            <History class="h-4 w-4" />
                            {{ t('dashboard.overview.historyTitle') }}
                        </h4>
                        <div class="space-y-2 max-h-64 overflow-y-auto pr-1">
                            <!-- Calculation Items List -->
                            <div v-for="item in calculationsList" :key="item.id" @click="activeResult = item"
                                class="flex items-center justify-between p-3.5 rounded-xl border transition-all cursor-pointer"
                                :class="[
                                    activeResult?.id === item.id
                                        ? 'bg-main/10 dark:bg-main/15 border-main/50 ring-1 ring-main/30'
                                        : item.status === 'pending'
                                        ? 'border-amber-500/40 bg-amber-500/10 dark:border-amber-500/30 dark:bg-amber-950/20 animate-pulse'
                                        : item.status === 'failed'
                                        ? 'border-red-500/40 bg-red-500/10 dark:border-red-500/30 dark:bg-red-950/20'
                                        : 'border-slate-100 hover:border-main/40 dark:border-zinc-900 dark:hover:border-main/30 bg-slate-50/50 dark:bg-zinc-900/50'
                                ]">
                                <!-- Item Pending -->
                                <template v-if="item.status === 'pending'">
                                    <div class="space-y-0.5 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <Loader2 class="h-4 w-4 animate-spin text-amber-500 shrink-0" />
                                            <span class="text-sm font-extrabold text-slate-900 dark:text-white">
                                                {{ t('dashboard.overview.pendingHistoryTitle') }}
                                            </span>
                                        </div>
                                        <div class="text-xs text-slate-500 dark:text-zinc-400 font-medium">
                                            {{ t('dashboard.overview.pendingHistoryDesc') }}
                                        </div>
                                    </div>
                                    <span class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-amber-500/20 text-amber-700 dark:text-amber-300 shrink-0 ml-3">
                                        {{ t('dashboard.overview.pendingBadge') }}
                                    </span>
                                </template>

                                <!-- Item Failed -->
                                <template v-else-if="item.status === 'failed'">
                                    <div class="space-y-0.5 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <AlertCircle class="h-4 w-4 text-red-500 shrink-0" />
                                            <span class="text-sm font-extrabold text-red-600 dark:text-red-400">
                                                {{ t('dashboard.overview.failedHistoryTitle') }}
                                            </span>
                                        </div>
                                        <div class="text-xs text-red-500/80 dark:text-red-400/80 font-medium">
                                            {{ item.error_message || t('dashboard.overview.failedHistoryDesc') }}
                                        </div>
                                    </div>
                                    <span class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-red-500/20 text-red-700 dark:text-red-300 shrink-0 ml-3">
                                        {{ t('dashboard.overview.failedBadge') }}
                                    </span>
                                </template>

                                <!-- Item Completed -->
                                <template v-else>
                                    <div class="space-y-0.5 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-extrabold text-slate-900 dark:text-white">
                                                {{ Number(item.final_pension).toLocaleString('uk-UA', {
                                                    minimumFractionDigits: 2
                                                }) }} ₴
                                            </span>
                                            <span v-if="item.created_at" class="text-[10px] font-medium text-slate-400 dark:text-zinc-500">
                                                {{ new Date(item.created_at).toLocaleDateString('uk-UA') }}
                                            </span>
                                        </div>
                                        <div class="text-xs text-slate-500 dark:text-zinc-400 flex flex-wrap items-center gap-2">
                                            <span>Base Pension: {{ Number(item.base_pension).toLocaleString('uk-UA', { minimumFractionDigits: 2 }) }} ₴</span>
                                            <span v-if="item.kz_wage_coefficient" class="text-[11px] text-main-dark dark:text-main font-semibold">
                                                (Кз: {{ Number(item.kz_wage_coefficient).toFixed(4) }}, Кс: {{ Number(item.ks_service_coefficient || 1.35).toFixed(4) }})
                                            </span>
                                        </div>
                                    </div>

                                    <button
                                        v-if="activeResult?.id === item.id"
                                        @click.stop="openCalculationDetails('kz', item)"
                                        type="button"
                                        class="shrink-0 flex items-center gap-1 text-xs font-bold text-main-dark dark:text-main bg-main/15 hover:bg-main/25 dark:bg-main/20 dark:hover:bg-main/30 px-3 py-1.5 rounded-lg transition-colors cursor-pointer ml-3"
                                    >
                                        <span>{{ t('dashboard.details.viewDetailsBtn') }}</span>
                                        <ArrowUpRight class="h-3.5 w-3.5" />
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Calculation Status Banner (Shown when active selected item is pending or calculation is in progress) -->
                    <div
                        v-if="activeResult?.status === 'pending' || (isCalculating && !activeResult)"
                        class="rounded-2xl border border-amber-500/40 bg-gradient-to-r from-amber-500/15 via-amber-500/10 to-transparent p-6 shadow-sm backdrop-blur-md dark:border-amber-500/30 dark:bg-amber-950/40 text-amber-950 dark:text-amber-100 flex items-center gap-4 animate-pulse transition-all"
                    >
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-amber-500/20 text-amber-600 dark:text-amber-400">
                            <Loader2 class="h-6 w-6 animate-spin" />
                        </div>
                        <div class="space-y-1">
                            <h4 class="text-sm font-black uppercase tracking-wider text-amber-900 dark:text-amber-200">
                                {{ t('dashboard.overview.pendingTitle') }}
                            </h4>
                            <p class="text-xs text-amber-800/80 dark:text-amber-300/80 font-medium">
                                {{ t('dashboard.overview.pendingDesc') }}
                            </p>
                        </div>
                    </div>

                    <!-- Failed Calculation Notice Banner (Shown when active selected item failed) -->
                    <div
                        v-if="activeResult?.status === 'failed'"
                        class="rounded-2xl border border-red-500/40 bg-gradient-to-r from-red-500/15 via-red-500/10 to-transparent p-6 shadow-sm backdrop-blur-md dark:border-red-500/30 dark:bg-red-950/40 text-red-950 dark:text-red-100 flex items-center gap-4 transition-all"
                    >
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-red-500/20 text-red-600 dark:text-red-400">
                            <AlertCircle class="h-6 w-6" />
                        </div>
                        <div class="space-y-1">
                            <h4 class="text-sm font-black uppercase tracking-wider text-red-900 dark:text-red-200">
                                {{ t('dashboard.overview.failedTitle') || 'Розрахунок не вдався' }}
                            </h4>
                            <p class="text-xs text-red-800/80 dark:text-red-300/80 font-medium">
                                {{ activeResult.error_message || t('dashboard.overview.failedDesc') || 'Помилка під час виконання розрахунку.' }}
                            </p>
                        </div>
                    </div>

                    <!-- Calculation Results & Detailed Formula Breakdown (Shown when active selected item is completed) -->
                    <template v-if="activeResult && (activeResult.status || 'completed') === 'completed'">
                        <!-- Criteria Not Yet Reached Notice Banner -->
                        <div
                            v-if="!hasReachedCriteria && !isHypothetical"
                            class="rounded-2xl border border-blue-500/40 bg-gradient-to-r from-blue-500/15 via-blue-500/10 to-transparent p-5 shadow-sm backdrop-blur-md dark:border-blue-500/30 dark:bg-blue-950/40 text-blue-950 dark:text-blue-100 relative overflow-hidden transition-all duration-300"
                        >
                            <div class="flex items-start gap-3.5 min-w-0">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-500/20 text-blue-600 dark:text-blue-400 mt-0.5 shadow-inner">
                                    <Info class="h-5 w-5" />
                                </div>
                                <div class="space-y-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-xs font-black uppercase tracking-wider text-blue-800 dark:text-blue-300">
                                            {{ t('dashboard.overview.criteriaNotMetTitle') }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-blue-900/90 dark:text-blue-200/90 leading-relaxed font-medium">
                                        {{ t('dashboard.overview.criteriaNotMetDesc').replace(':amount', Number(activeResult.final_pension).toLocaleString('uk-UA', { minimumFractionDigits: 2 })) }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Theoretical (Projected) Calculation Warning Banner -->
                        <div
                            v-if="isHypothetical"
                            class="rounded-2xl border border-amber-500/40 bg-gradient-to-r from-amber-500/15 via-amber-500/10 to-transparent p-5 shadow-sm backdrop-blur-md dark:border-amber-500/30 dark:bg-amber-950/40 text-amber-950 dark:text-amber-100 relative overflow-hidden transition-all duration-300"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-start gap-3.5 min-w-0">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-500/20 text-amber-600 dark:text-amber-400 mt-0.5 shadow-inner">
                                        <AlertTriangle class="h-5 w-5" />
                                    </div>
                                    <div class="space-y-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-xs font-black uppercase tracking-wider text-amber-800 dark:text-amber-300">
                                                {{ t('dashboard.overview.hypotheticalTitle') }}
                                            </span>
                                            <span v-if="user?.target_retirement_year" class="px-2 py-0.5 rounded-md bg-amber-500/25 text-[10px] font-extrabold text-amber-900 dark:text-amber-200">
                                                {{ user.target_retirement_year }} {{ t('dashboard.overview.yearUnit') }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-amber-900/90 dark:text-amber-200/90 leading-relaxed font-medium">
                                            {{ t('dashboard.overview.hypotheticalDesc').replace(':year', String(user?.target_retirement_year || currentYear)) }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Interactive Info Icon Button (i) -->
                                <button
                                    @click="showHypotheticalModal = true"
                                    type="button"
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-500/20 text-amber-700 dark:text-amber-300 hover:bg-amber-500/30 dark:hover:bg-amber-500/40 transition-colors cursor-pointer"
                                    :title="t('dashboard.overview.hypotheticalInfoTooltip')"
                                >
                                    <Info class="h-5 w-5" />
                                </button>
                            </div>
                        </div>

                        <!-- Result Card with Primary Formula Header -->
                        <div
                            class="rounded-2xl border border-main/30 bg-gradient-to-br from-main/10 via-emerald-500/5 to-transparent p-6 shadow-md backdrop-blur-md dark:border-main/20 dark:bg-zinc-950/90 relative overflow-hidden transition-all duration-300">
                            <div class="absolute -right-6 -bottom-6 opacity-10 pointer-events-none">
                                <TrendingUp class="h-48 w-48 text-main" />
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold uppercase tracking-wider text-main-dark dark:text-main">
                                    {{ t('dashboard.overview.resultTitle') }}
                                </span>
                                <button
                                    @click="openCalculationDetails('kz')"
                                    type="button"
                                    class="flex items-center gap-1 text-xs font-bold text-main-dark dark:text-main bg-main/20 hover:bg-main/30 px-3 py-1 rounded-lg transition-colors cursor-pointer"
                                >
                                    <span>{{ t('dashboard.details.viewDetailsBtn') }}</span>
                                    <ArrowUpRight class="h-3.5 w-3.5" />
                                </button>
                            </div>

                            <div class="mt-4 flex flex-wrap items-baseline gap-4">
                                <span class="text-4xl font-extrabold text-slate-900 sm:text-5xl dark:text-white">
                                    {{ Number(activeResult.final_pension).toLocaleString('uk-UA', {
                                    minimumFractionDigits: 2 }) }} ₴
                                </span>
                                <span class="text-xs font-bold text-emerald-600 dark:text-main flex items-center gap-1">
                                    <CheckCircle2 class="h-4 w-4" />
                                    {{ t('dashboard.overview.calculatedPfu') }}
                                </span>
                            </div>

                            <div
                                class="mt-6 grid grid-cols-2 gap-4 border-t border-slate-200/60 pt-4 dark:border-zinc-800/60">
                                <div>
                                    <span class="text-xs text-slate-500 dark:text-zinc-400">{{
                                        t('dashboard.overview.basePension') }}</span>
                                    <p class="text-base font-bold text-slate-900 dark:text-white">
                                        {{ Number(activeResult.base_pension).toLocaleString('uk-UA', {
                                        minimumFractionDigits: 2 }) }} ₴
                                    </p>
                                </div>
                                <div>
                                    <span class="text-xs text-slate-500 dark:text-zinc-400">{{
                                        t('dashboard.overview.serviceMultiplier') }}</span>
                                    <p class="text-base font-bold text-main-dark dark:text-main">
                                        {{ activeResult.coefficient_multiplier || activeResult.ks_service_coefficient || '1.35' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Interactive Formula Coefficient Cards -->
                            <div class="mt-6 pt-4 border-t border-slate-200/60 dark:border-zinc-800/60">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-zinc-400 mb-3 block">
                                    {{ t('dashboard.details.interactiveFormulaElements') }}
                                </span>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                    <!-- Zp Card -->
                                    <button
                                        @click="openCalculationDetails('zp')"
                                        type="button"
                                        class="group flex flex-col justify-between rounded-xl border border-slate-200/80 bg-white/60 p-3.5 text-left transition-all hover:border-main/50 hover:bg-main/10 dark:border-zinc-800 dark:bg-zinc-900/60 dark:hover:border-main/40 dark:hover:bg-main/10 cursor-pointer"
                                    >
                                        <div class="flex items-center justify-between">
                                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-zinc-400">
                                                {{ t('dashboard.details.zpCardLabel') }}
                                            </span>
                                            <Layers class="h-3.5 w-3.5 text-main shrink-0" />
                                        </div>
                                        <p class="mt-2 text-sm font-extrabold text-slate-900 dark:text-white">
                                            {{ Number(activeResult.zp_macroeconomic_average || 16500).toLocaleString('uk-UA', { minimumFractionDigits: 2 }) }} ₴
                                        </p>
                                        <span class="mt-1 text-[10px] text-main-dark dark:text-main group-hover:underline flex items-center gap-0.5 font-semibold">
                                            {{ t('dashboard.details.clickToViewTable') }} &rarr;
                                        </span>
                                    </button>

                                    <!-- Kz Card -->
                                    <button
                                        @click="openCalculationDetails('kz')"
                                        type="button"
                                        class="group flex flex-col justify-between rounded-xl border border-slate-200/80 bg-white/60 p-3.5 text-left transition-all hover:border-main/50 hover:bg-main/10 dark:border-zinc-800 dark:bg-zinc-900/60 dark:hover:border-main/40 dark:hover:bg-main/10 cursor-pointer"
                                    >
                                        <div class="flex items-center justify-between">
                                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-zinc-400">
                                                {{ t('dashboard.details.kzCardLabel') }}
                                            </span>
                                            <Table class="h-3.5 w-3.5 text-main shrink-0" />
                                        </div>
                                        <p class="mt-2 text-sm font-extrabold text-slate-900 dark:text-white">
                                            {{ Number(activeResult.kz_wage_coefficient || 1.0).toFixed(4) }}
                                        </p>
                                        <span class="mt-1 text-[10px] text-main-dark dark:text-main group-hover:underline flex items-center gap-0.5 font-semibold">
                                            {{ t('dashboard.details.inspectCoeffBtn') }} &rarr;
                                        </span>
                                    </button>

                                    <!-- Ks Card -->
                                    <button
                                        @click="openCalculationDetails('ks')"
                                        type="button"
                                        class="group flex flex-col justify-between rounded-xl border border-slate-200/80 bg-white/60 p-3.5 text-left transition-all hover:border-main/50 hover:bg-main/10 dark:border-zinc-800 dark:bg-zinc-900/60 dark:hover:border-main/40 dark:hover:bg-main/10 cursor-pointer"
                                    >
                                        <div class="flex items-center justify-between">
                                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-zinc-400">
                                                {{ t('dashboard.details.ksCardLabel') }}
                                            </span>
                                            <Clock class="h-3.5 w-3.5 text-main shrink-0" />
                                        </div>
                                        <p class="mt-2 text-sm font-extrabold text-slate-900 dark:text-white">
                                            {{ Number(activeResult.ks_service_coefficient || activeResult.coefficient_multiplier || 1.35).toFixed(4) }}
                                        </p>
                                        <span class="mt-1 text-[10px] text-main-dark dark:text-main group-hover:underline flex items-center gap-0.5 font-semibold">
                                            {{ activeResult.total_service_months || (totalYearsWorked * 12) }} {{ t('documents.months') }} &rarr;
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template v-else-if="!isCalculating">
                        <div
                            class="flex h-48 flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 dark:border-zinc-800 text-slate-400 dark:text-zinc-500 text-xs p-6 text-center gap-2">
                            <Calculator class="h-8 w-8 text-slate-400" />
                            <span>{{ t('dashboard.overview.emptyHistory') }}</span>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Hypothetical Projection Explanation Modal Dialog -->
        <Dialog :open="showHypotheticalModal" @update:open="showHypotheticalModal = $event">
            <DialogContent class="sm:max-w-lg rounded-3xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 p-6 shadow-2xl">
                <DialogHeader class="space-y-2">
                    <div class="flex items-center gap-2 text-amber-600 dark:text-amber-400">
                        <Info class="h-6 w-6" />
                        <DialogTitle class="text-lg font-extrabold text-slate-900 dark:text-white">
                            {{ t('dashboard.overview.hypotheticalModalTitle') }}
                        </DialogTitle>
                    </div>
                    <DialogDescription class="text-xs text-slate-500 dark:text-zinc-400">
                        Детальний алгоритм прогностичного розрахунку для майбутніх років пенсії.
                    </DialogDescription>
                </DialogHeader>

                <div class="mt-4 space-y-3.5 text-xs">
                    <div class="rounded-2xl bg-slate-50 dark:bg-zinc-900/60 p-4 border border-slate-100 dark:border-zinc-800 space-y-1">
                        <h5 class="font-bold text-slate-900 dark:text-amber-300">
                            {{ t('dashboard.overview.hypotheticalStep1Title') }}
                        </h5>
                        <p class="text-slate-600 dark:text-zinc-300 leading-relaxed">
                            {{ t('dashboard.overview.hypotheticalStep1Desc').replace(':year', String(user?.target_retirement_year || currentYear)) }}
                        </p>
                    </div>

                    <div class="rounded-2xl bg-slate-50 dark:bg-zinc-900/60 p-4 border border-slate-100 dark:border-zinc-800 space-y-1">
                        <h5 class="font-bold text-slate-900 dark:text-amber-300">
                            {{ t('dashboard.overview.hypotheticalStep2Title') }}
                        </h5>
                        <p class="text-slate-600 dark:text-zinc-300 leading-relaxed">
                            {{ t('dashboard.overview.hypotheticalStep2Desc') }}
                        </p>
                    </div>

                    <div class="rounded-2xl bg-slate-50 dark:bg-zinc-900/60 p-4 border border-slate-100 dark:border-zinc-800 space-y-1">
                        <h5 class="font-bold text-slate-900 dark:text-amber-300">
                            {{ t('dashboard.overview.hypotheticalStep3Title') }}
                        </h5>
                        <p class="text-slate-600 dark:text-zinc-300 leading-relaxed">
                            {{ t('dashboard.overview.hypotheticalStep3Desc') }}
                        </p>
                    </div>

                    <div class="rounded-2xl bg-slate-50 dark:bg-zinc-900/60 p-4 border border-slate-100 dark:border-zinc-800 space-y-1">
                        <h5 class="font-bold text-slate-900 dark:text-amber-300">
                            {{ t('dashboard.overview.hypotheticalStep4Title') }}
                        </h5>
                        <p class="text-slate-600 dark:text-zinc-300 leading-relaxed">
                            {{ t('dashboard.overview.hypotheticalStep4Desc') }}
                        </p>
                    </div>
                </div>

                <DialogFooter class="mt-6">
                    <Button @click="showHypotheticalModal = false" type="button" class="w-full bg-main text-slate-950 font-bold hover:bg-main-dark rounded-xl h-10 cursor-pointer">
                        {{ t('dashboard.overview.closeModal') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Calculation Details & Coefficient Formula Breakdown Modal Dialog -->
        <Dialog :open="showDetailsModal" @update:open="showDetailsModal = $event">
            <DialogContent class="sm:max-w-3xl h-[85vh] max-h-[85vh] w-[95vw] sm:w-full flex flex-col rounded-2xl sm:rounded-3xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 p-4 sm:p-6 shadow-2xl overflow-hidden">
                <DialogHeader class="space-y-1.5 sm:space-y-2 shrink-0">
                    <div class="flex items-center gap-2 text-main-dark dark:text-main">
                        <Layers class="h-5 w-5 sm:h-6 sm:w-6 text-main shrink-0" />
                        <DialogTitle class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-white">
                            {{ t('dashboard.details.modalTitle') }}
                        </DialogTitle>
                    </div>
                    <DialogDescription class="text-[11px] sm:text-xs text-slate-500 dark:text-zinc-400">
                        {{ t('dashboard.details.baseFormulaTitle') }}: <span class="font-bold text-slate-900 dark:text-white">{{ t('dashboard.details.formulaExpression') }}</span>
                    </DialogDescription>
                </DialogHeader>

                <!-- Dynamic Tab Navigation Bar (Scrollable on mobile) -->
                <div class="mt-3 sm:mt-4 flex items-center gap-1.5 sm:gap-2 overflow-x-auto pb-2 sm:pb-3 border-b border-slate-100 dark:border-zinc-800 scrollbar-none shrink-0">
                    <button
                        @click="activeDetailTab = 'kz'"
                        type="button"
                        class="px-3 sm:px-3.5 py-1.5 sm:py-2 rounded-xl text-[11px] sm:text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5 shrink-0"
                        :class="activeDetailTab === 'kz' ? 'bg-main text-slate-950 shadow-sm' : 'bg-slate-100 text-slate-600 dark:bg-zinc-900 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white'"
                    >
                        <Table class="h-3.5 w-3.5 shrink-0" />
                        <span>{{ t('dashboard.details.tabKzTitle') }}</span>
                    </button>

                    <button
                        @click="activeDetailTab = 'zp'"
                        type="button"
                        class="px-3 sm:px-3.5 py-1.5 sm:py-2 rounded-xl text-[11px] sm:text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5 shrink-0"
                        :class="activeDetailTab === 'zp' ? 'bg-main text-slate-950 shadow-sm' : 'bg-slate-100 text-slate-600 dark:bg-zinc-900 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white'"
                    >
                        <Layers class="h-3.5 w-3.5 shrink-0" />
                        <span>{{ t('dashboard.details.tabZpTitle') }}</span>
                    </button>

                    <button
                        @click="activeDetailTab = 'ks'"
                        type="button"
                        class="px-3 sm:px-3.5 py-1.5 sm:py-2 rounded-xl text-[11px] sm:text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5 shrink-0"
                        :class="activeDetailTab === 'ks' ? 'bg-main text-slate-950 shadow-sm' : 'bg-slate-100 text-slate-600 dark:bg-zinc-900 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white'"
                    >
                        <Clock class="h-3.5 w-3.5 shrink-0" />
                        <span>{{ t('dashboard.details.tabKsTitle') }}</span>
                    </button>

                    <button
                        v-if="isAdmin"
                        @click="activeDetailTab = 'logs'"
                        type="button"
                        class="px-3 sm:px-3.5 py-1.5 sm:py-2 rounded-xl text-[11px] sm:text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5 shrink-0"
                        :class="activeDetailTab === 'logs' ? 'bg-main text-slate-950 shadow-sm' : 'bg-slate-100 text-slate-600 dark:bg-zinc-900 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white'"
                    >
                        <FileText class="h-3.5 w-3.5 shrink-0" />
                        <span>{{ t('dashboard.details.tabLogsTitle') }}</span>
                    </button>
                </div>

                <!-- Scrollable Tab Content Container -->
                <div class="mt-3 sm:mt-4 flex-1 overflow-y-auto min-h-0 pr-1 space-y-3 sm:space-y-4 [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar-thumb]:bg-slate-300 dark:[&::-webkit-scrollbar-thumb]:bg-zinc-800 [&::-webkit-scrollbar-thumb]:rounded-full">
                    <!-- Tab 1: Kz Wage Coefficient Table -->
                    <div v-if="activeDetailTab === 'kz'" class="space-y-3 sm:space-y-4">
                        <div class="rounded-2xl border border-slate-100 dark:border-zinc-800 bg-slate-50/60 dark:bg-zinc-900/50 p-3.5 sm:p-4 space-y-1.5 sm:space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-extrabold text-slate-900 dark:text-white">
                                    {{ t('dashboard.details.avgKzTitle') }}
                                </span>
                                <span class="text-base sm:text-lg font-black text-main-dark dark:text-main">
                                    {{ Number(activeResult?.kz_wage_coefficient || 1.0).toFixed(4) }}
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-zinc-400 leading-relaxed">
                                {{ t('dashboard.details.kzNoticeText') }}
                            </p>
                        </div>

                        <!-- Skeleton Loading State -->
                        <div v-if="isLoadingBreakdown" class="space-y-3 p-3.5 sm:p-4 rounded-2xl border border-slate-200 dark:border-zinc-800">
                            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-zinc-800">
                                <Skeleton class="h-4 w-32" />
                                <Skeleton class="h-4 w-24" />
                            </div>
                            <div v-for="i in 4" :key="i" class="space-y-2 py-2">
                                <div class="flex items-center justify-between">
                                    <Skeleton class="h-4 w-20" />
                                    <Skeleton class="h-4 w-28" />
                                    <Skeleton class="h-4 w-28" />
                                    <Skeleton class="h-4 w-16" />
                                </div>
                            </div>
                        </div>

                        <!-- Detailed Accordion Breakdown Table -->
                        <div v-else-if="detailedBreakdown && detailedBreakdown.length > 0" class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-zinc-800 [&::-webkit-scrollbar]:h-1.5 [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar-thumb]:bg-slate-300 dark:[&::-webkit-scrollbar-thumb]:bg-zinc-800 [&::-webkit-scrollbar-thumb]:rounded-full">
                            <table class="w-full text-left text-[11px] sm:text-xs min-w-[520px] sm:min-w-full">
                                <thead class="sticky top-0 z-10 bg-slate-100 dark:bg-zinc-900 text-slate-600 dark:text-zinc-300 font-bold uppercase tracking-wider text-[9px] sm:text-[10px] shadow-xs">
                                    <tr>
                                        <th class="p-2 sm:p-3">{{ t('dashboard.details.tableYear') }}</th>
                                        <th class="p-2 sm:p-3">{{ t('dashboard.details.tableUserSalary') }}</th>
                                        <th class="p-2 sm:p-3">{{ t('dashboard.details.tableNationalSalary') }}</th>
                                        <th class="p-2 sm:p-3 text-right">{{ t('dashboard.details.tableMonthlyCoeff') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/80 font-medium">
                                    <template v-for="yItem in detailedBreakdown" :key="yItem.year">
                                        <!-- Year Summary Row (Accordion Trigger) -->
                                        <tr
                                            @click="toggleYearAccordion(yItem.year)"
                                            class="hover:bg-slate-100/80 dark:hover:bg-zinc-900/80 cursor-pointer transition-colors bg-slate-50/50 dark:bg-zinc-900/30"
                                        >
                                            <td class="p-2 sm:p-3 font-bold text-slate-900 dark:text-white flex items-center gap-1.5 sm:gap-2">
                                                <component :is="expandedYears[yItem.year] ? ChevronDown : ChevronRight" class="h-3.5 w-3.5 sm:h-4 sm:w-4 text-main shrink-0 transition-transform" />
                                                <span>{{ yItem.year }} р.</span>
                                            </td>
                                            <td class="p-2 sm:p-3 text-slate-700 dark:text-zinc-300">
                                                <template v-if="yItem.user_annual_income > 0">
                                                    <div>{{ Number(yItem.user_avg_monthly_salary).toLocaleString('uk-UA', { minimumFractionDigits: 2 }) }} ₴ /міс</div>
                                                    <span class="text-[9px] sm:text-[10px] text-slate-400 block">({{ Number(yItem.user_annual_income).toLocaleString('uk-UA') }} ₴ /рік, {{ yItem.months_worked }} міс.)</span>
                                                </template>
                                                <template v-else>
                                                    <span class="text-[9px] sm:text-[10px] italic text-amber-700 dark:text-amber-400 font-semibold bg-amber-50 dark:bg-amber-950/40 px-1.5 py-0.5 rounded-md border border-amber-200/60 dark:border-amber-900/40">
                                                        {{ t('documents.noSalaryBadge') }}
                                                    </span>
                                                </template>
                                            </td>
                                            <td class="p-2 sm:p-3 text-slate-700 dark:text-zinc-300 font-semibold">
                                                {{ Number(yItem.national_avg_salary).toLocaleString('uk-UA', { minimumFractionDigits: 2 }) }} ₴
                                            </td>
                                            <td class="p-2 sm:p-3 text-right font-extrabold text-main-dark dark:text-main">
                                                {{ Number(yItem.yearly_coefficient).toFixed(4) }}
                                            </td>
                                        </tr>

                                        <!-- Expanded Monthly Sub-Rows -->
                                        <template v-if="expandedYears[yItem.year]">
                                            <tr
                                                v-for="mRec in yItem.months"
                                                :key="mRec.month"
                                                class="bg-slate-100/40 dark:bg-zinc-950/60 hover:bg-slate-100 dark:hover:bg-zinc-900/60 text-[10px] sm:text-[11px]"
                                            >
                                                <td class="py-1.5 sm:py-2 px-2 sm:px-3 pl-5 sm:pl-8 text-slate-600 dark:text-zinc-400 font-medium flex items-center gap-1 sm:gap-1.5">
                                                    <span class="w-1 h-1 sm:w-1.5 sm:h-1.5 rounded-full bg-main/60 shrink-0"></span>
                                                    <span>{{ t('monthNames.' + mRec.month) }}</span>
                                                </td>
                                                <td class="py-1.5 sm:py-2 px-2 sm:px-3 font-mono text-slate-700 dark:text-zinc-300">
                                                    {{ Number(mRec.user_salary).toLocaleString('uk-UA', { minimumFractionDigits: 2 }) }} ₴
                                                </td>
                                                <td class="py-1.5 sm:py-2 px-2 sm:px-3 font-mono text-slate-600 dark:text-zinc-400">
                                                    {{ Number(mRec.national_avg_salary).toLocaleString('uk-UA', { minimumFractionDigits: 2 }) }} ₴
                                                </td>
                                                <td class="py-1.5 sm:py-2 px-2 sm:px-3 text-right font-mono font-bold text-main-dark dark:text-main">
                                                    {{ Number(mRec.monthly_coefficient).toFixed(4) }}
                                                </td>
                                            </tr>
                                        </template>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="p-4 sm:p-6 text-center text-xs text-slate-400 dark:text-zinc-500 rounded-2xl border border-dashed border-slate-200 dark:border-zinc-800">
                            {{ t('dashboard.details.noTaxRecordsNotice') }}
                        </div>
                    </div>

                    <!-- Tab 2: Zp Macroeconomic Salary -->
                    <div v-if="activeDetailTab === 'zp'" class="space-y-3 sm:space-y-4">
                        <div class="rounded-2xl border border-slate-100 dark:border-zinc-800 bg-slate-50/60 dark:bg-zinc-900/50 p-4 sm:p-5 space-y-2 sm:space-y-3">
                            <span class="text-[10px] sm:text-xs font-extrabold uppercase tracking-wider text-slate-500 dark:text-zinc-400">
                                {{ t('dashboard.details.zpFormulaTitle') }}
                            </span>
                            <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white">
                                {{ Number(activeResult?.zp_macroeconomic_average || 16500).toLocaleString('uk-UA', { minimumFractionDigits: 2 }) }} ₴
                            </div>
                            <p class="text-xs text-slate-600 dark:text-zinc-300 leading-relaxed">
                                {{ t('dashboard.details.zpDescription') }}
                            </p>
                        </div>
                    </div>

                    <!-- Tab 3: Ks Service Multiplier -->
                    <div v-if="activeDetailTab === 'ks'" class="space-y-3 sm:space-y-4">
                        <div class="rounded-2xl border border-slate-100 dark:border-zinc-800 bg-slate-50/60 dark:bg-zinc-900/50 p-4 sm:p-5 space-y-3">
                            <span class="text-[10px] sm:text-xs font-extrabold uppercase tracking-wider text-slate-500 dark:text-zinc-400">
                                {{ t('dashboard.details.ksFormulaTitle') }}
                            </span>
                            <div class="flex flex-col sm:flex-row sm:items-baseline justify-between gap-1">
                                <span class="text-xs text-slate-500 dark:text-zinc-400">{{ t('dashboard.details.ksMonthsLabel') }}:</span>
                                <span class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">
                                    {{ activeResult?.total_service_months || (totalYearsWorked * 12) }} {{ t('documents.months') }} ({{ Math.floor((activeResult?.total_service_months || (totalYearsWorked * 12)) / 12) }} {{ t('documents.yrs') }})
                                </span>
                            </div>
                            <div class="flex flex-col sm:flex-row sm:items-baseline justify-between border-t border-slate-200/60 dark:border-zinc-800 pt-3 gap-1">
                                <span class="text-xs text-slate-500 dark:text-zinc-400">Підсумковий коефіцієнт Ks:</span>
                                <span class="text-xl sm:text-2xl font-black text-main-dark dark:text-main">
                                    {{ Number(activeResult?.ks_service_coefficient || activeResult?.coefficient_multiplier || 1.35).toFixed(4) }}
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-zinc-400 leading-relaxed">
                                За кожен рік страхового стажу коефіцієнт оцінки стажу становить 1% (0.01). Формула: Ks = Місяці / 1200.
                            </p>
                        </div>
                    </div>

                    <!-- Tab 4: 5-Stage Execution Audit Logs (Admin Only) -->
                    <div v-if="isAdmin && activeDetailTab === 'logs'" class="space-y-3 sm:space-y-4">
                        <div class="rounded-2xl border border-slate-100 dark:border-zinc-800 bg-slate-50/60 dark:bg-zinc-900/50 p-3.5 sm:p-4 space-y-1.5 sm:space-y-2">
                            <h5 class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <FileText class="h-4 w-4 text-main shrink-0" />
                                {{ t('dashboard.details.logsTitle') }}
                            </h5>
                            <p class="text-[11px] text-slate-500 dark:text-zinc-400">
                                Аудит 5 етапів обчислення пенсійним математичним движком C++ (Закон України № 1058-IV).
                            </p>
                        </div>

                        <div v-if="(activeResult?.calculation_logs && activeResult.calculation_logs.length > 0) || (activeResult?.calculation_breakdown?.logs && activeResult.calculation_breakdown.logs.length > 0)"
                            class="rounded-2xl bg-zinc-950 p-3 sm:p-4 font-mono text-[10px] sm:text-[11px] text-emerald-400 leading-relaxed max-h-60 overflow-y-auto space-y-1 [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar-thumb]:bg-slate-300 dark:[&::-webkit-scrollbar-thumb]:bg-zinc-800 [&::-webkit-scrollbar-thumb]:rounded-full">
                            <div v-for="(logLine, lIdx) in (activeResult?.calculation_logs || activeResult?.calculation_breakdown?.logs || [])" :key="lIdx">
                                &gt; {{ logLine }}
                            </div>
                        </div>
                        <div v-else class="p-4 sm:p-6 text-center text-xs text-slate-400 dark:text-zinc-500 rounded-2xl border border-dashed border-slate-200 dark:border-zinc-800">
                            {{ t('dashboard.details.noLogsNotice') }}
                        </div>
                    </div>
                </div>

                <DialogFooter class="mt-4 sm:mt-6 shrink-0 flex flex-col sm:flex-row items-center gap-2">
                    <a
                        v-if="activeResult?.id"
                        :href="`/pension-calculations/${activeResult.id}/pdf?lang=${locale}`"
                        download
                        class="w-full sm:flex-1 bg-slate-900 dark:bg-white text-white dark:text-slate-950 font-bold hover:bg-slate-800 dark:hover:bg-slate-100 rounded-xl h-9 sm:h-10 cursor-pointer text-xs sm:text-sm flex items-center justify-center gap-2 transition-colors"
                    >
                        <Download class="h-4 w-4" />
                        <span>{{ t('dashboard.details.downloadPdf') }}</span>
                    </a>
                    <Button @click="showDetailsModal = false" type="button" class="w-full sm:flex-1 bg-main text-slate-950 font-bold hover:bg-main-dark rounded-xl h-9 sm:h-10 cursor-pointer text-xs sm:text-sm">
                        {{ t('dashboard.overview.closeModal') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
