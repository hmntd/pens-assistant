<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';
import { Calculator, ArrowUpRight, CheckCircle2, History, TrendingUp, UserCheck, AlertCircle, AlertTriangle, Info, Sparkles } from '@lucide/vue';
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
    final_pension: number;
    base_pension: number;
    recalculate_delta?: number;
    coefficient_multiplier?: number;
    disability_group?: string | null;
    created_at?: string;
    calculation_breakdown?: {
        is_hypothetical?: boolean;
        hypothetical_disclaimer?: string;
        logs?: string[];
        pre_clamped?: number;
        is_min_clamped?: boolean;
        is_max_clamped?: boolean;
    };
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

const { t } = useI18n();
const page = usePage();
const user = computed(() => page.props.auth?.user);

const isSectionLoading = ref(true);
const showHypotheticalModal = ref(false);

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

const form = useForm({
    target_retirement_year: user.value?.target_retirement_year || null,
    disability_group: user.value?.disability_group || 'none',
    service_years: 35,
    enable_hypothetical_projection: false,
});

const isRetirementYearMissing = computed(() => !user.value?.target_retirement_year);
const isInsuranceServiceMissing = computed(() => !taxHistoriesList.value || taxHistoriesList.value.length === 0);

const totalYearsWorked = computed(() => {
    if (!taxHistoriesList.value) return 0;
    return taxHistoriesList.value.length;
});

const currentYear = new Date().getFullYear();

const isHypothetical = computed(() => {
    if (activeResult.value?.calculation_breakdown?.is_hypothetical !== undefined) {
        return Boolean(activeResult.value.calculation_breakdown.is_hypothetical);
    }
    const targetYr = user.value?.target_retirement_year;
    return Boolean(targetYr && targetYr > currentYear) || totalYearsWorked.value < 35;
});

const isCalculationBlocked = computed(() => isRetirementYearMissing.value || isInsuranceServiceMissing.value);

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
            calculationsList.value = json.data as CalculationItem[];
            activeResult.value = calculationsList.value[0] || null;
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
    await refreshTaxHistories();
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
    toast.info(t('notifications.pensionCalculationStartedToast'));
    form.post('/pension-calculations', {
        preserveScroll: true,
        onSuccess: (pageRes) => {
            toast.success(t('notifications.pensionCalculationSuccessToast'));
            window.dispatchEvent(new CustomEvent('notification-created'));
            if (pageRes.props.initialCalculations) {
                calculationsList.value = pageRes.props.initialCalculations as CalculationItem[];
                activeResult.value = calculationsList.value[0] || null;
            }
        },
        onError: () => {
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

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
                <!-- Left Card Skeleton -->
                <div
                    class="lg:col-span-5 rounded-2xl border border-slate-200/80 bg-white/70 p-6 backdrop-blur-md dark:border-zinc-800/80 dark:bg-zinc-950/80 space-y-6">
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
                <MissingDataHighlight v-if="isRetirementYearMissing"
                    :title="t('dashboard.alerts.missingRetirementYearTitle')"
                    :description="t('dashboard.alerts.missingRetirementYearDesc')"
                    @click="emit('go-to-section', 'profile_details')" />

                <MissingDataHighlight v-if="isInsuranceServiceMissing"
                    :title="t('dashboard.alerts.missingServiceTitle')"
                    :description="t('dashboard.alerts.missingServiceDesc')"
                    @click="emit('go-to-section', 'documents')" />
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
                <!-- Left: Read-Only User Data Summary & Calculation Trigger -->
                <div
                    class="lg:col-span-5 rounded-2xl border border-slate-200/80 bg-white/70 p-6 shadow-sm backdrop-blur-md dark:border-zinc-800/80 dark:bg-zinc-950/80 space-y-6">
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

                <!-- Right: Calculation Results & Breakdown -->
                <div class="lg:col-span-7 space-y-6">
                    <template v-if="activeResult">
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

                        <div
                            class="rounded-2xl border border-main/30 bg-gradient-to-br from-main/10 via-emerald-500/5 to-transparent p-6 shadow-md backdrop-blur-md dark:border-main/20 dark:bg-zinc-950/90 relative overflow-hidden">
                            <div class="absolute -right-6 -bottom-6 opacity-10 pointer-events-none">
                                <TrendingUp class="h-48 w-48 text-main" />
                            </div>

                            <span class="text-xs font-semibold uppercase tracking-wider text-main-dark dark:text-main">
                                {{ t('dashboard.overview.resultTitle') }}
                            </span>

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
                                        {{ activeResult.coefficient_multiplier || '1.35' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <div
                            class="flex h-48 flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 dark:border-zinc-800 text-slate-400 dark:text-zinc-500 text-xs p-6 text-center gap-2">
                            <Calculator class="h-8 w-8 text-slate-400" />
                            <span>{{ t('dashboard.overview.emptyHistory') }}</span>
                        </div>
                    </template>

                    <!-- History Log Table -->
                    <div v-if="calculationsList.length > 0"
                        class="rounded-2xl border border-slate-200/80 bg-white/70 p-6 shadow-sm backdrop-blur-md dark:border-zinc-800/80 dark:bg-zinc-950/80">
                        <h4
                            class="mb-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-zinc-400 flex items-center gap-1.5">
                            <History class="h-4 w-4" />
                            {{ t('dashboard.overview.historyTitle') }}
                        </h4>
                        <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                            <div v-for="item in calculationsList" :key="item.id" @click="activeResult = item"
                                class="flex items-center justify-between p-3 rounded-xl border border-slate-100 hover:border-main/40 dark:border-zinc-900 dark:hover:border-main/30 cursor-pointer transition-colors"
                                :class="activeResult?.id === item.id ? 'bg-main/10 dark:bg-main/15 border-main/50' : 'bg-slate-50/50 dark:bg-zinc-900/50'">
                                <div>
                                    <span class="text-sm font-bold text-slate-900 dark:text-white">
                                        {{ Number(item.final_pension).toLocaleString('uk-UA', {
                                            minimumFractionDigits: 2
                                        }) }} ₴
                                    </span>
                                    <span class="ml-2 text-xs text-slate-400">
                                        ({{ t('dashboard.overview.basePension') }}: {{
                                            Number(item.base_pension).toLocaleString('uk-UA') }}
                                        ₴)
                                    </span>
                                </div>
                                <ArrowUpRight class="h-4 w-4 text-slate-400" />
                            </div>
                        </div>
                    </div>
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
    </div>
</template>
