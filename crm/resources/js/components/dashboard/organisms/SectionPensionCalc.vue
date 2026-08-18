<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';
import { Calculator, ArrowUpRight, CheckCircle2, History, Sparkles, TrendingUp, UserCheck, AlertCircle } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import MissingDataHighlight from '../atoms/MissingDataHighlight.vue';

export interface CalculationItem {
    id: number;
    final_pension: number;
    base_pension: number;
    recalculate_delta?: number;
    coefficient_multiplier?: number;
    disability_group?: string | null;
    created_at?: string;
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

const calculationsList = ref<CalculationItem[]>(props.initialCalculations || []);
const activeResult = ref<CalculationItem | null>(calculationsList.value[0] || null);

const form = useForm({
    target_retirement_year: user.value?.target_retirement_year || null,
    disability_group: user.value?.disability_group || 'none',
    service_years: 35,
});

const isRetirementYearMissing = computed(() => !user.value?.target_retirement_year);
const isInsuranceServiceMissing = computed(() => !props.initialTaxHistories || props.initialTaxHistories.length === 0);

const totalYearsWorked = computed(() => {
    if (!props.initialTaxHistories) return 0;
    return props.initialTaxHistories.length;
});

const isCalculationBlocked = computed(() => isRetirementYearMissing.value || isInsuranceServiceMissing.value);

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
    form.post('/pension-calculations', {
        preserveScroll: true,
        onSuccess: (pageRes) => {
            if (pageRes.props.initialCalculations) {
                calculationsList.value = pageRes.props.initialCalculations as CalculationItem[];
                activeResult.value = calculationsList.value[0] || null;
            }
        },
    });
}
</script>

<template>
    <div class="space-y-8">
        <!-- Section Header -->
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                    <Calculator class="h-6 w-6 text-main" />
                    {{ t('dashboard.overview.title') }}
                </h2>
                <p class="text-xs text-slate-500 dark:text-zinc-400">
                    {{ t('dashboard.overview.subtitle') }}
                </p>
            </div>
        </div>

        <!-- Highlighted Missing Data Alerts -->
        <div class="space-y-3">
            <MissingDataHighlight
                v-if="isRetirementYearMissing"
                :title="t('dashboard.alerts.missingRetirementYearTitle')"
                :description="t('dashboard.alerts.missingRetirementYearDesc')"
                @click="emit('go-to-section', 'profile_details')"
            />

            <MissingDataHighlight
                v-if="isInsuranceServiceMissing"
                :title="t('dashboard.alerts.missingServiceTitle')"
                :description="t('dashboard.alerts.missingServiceDesc')"
                @click="emit('go-to-section', 'documents')"
            />
        </div>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
            <!-- Left: Read-Only User Data Summary & Calculation Trigger -->
            <div class="lg:col-span-5 rounded-2xl border border-slate-200/80 bg-white/70 p-6 shadow-sm backdrop-blur-md dark:border-zinc-800/80 dark:bg-zinc-950/80 space-y-6">
                <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-zinc-800/60 pb-3 flex items-center justify-between">
                    <span class="flex items-center gap-2">
                        <UserCheck class="h-4 w-4 text-main" />
                        {{ t('dashboard.overview.userCardTitle') }}
                    </span>
                    <button
                        @click="emit('go-to-section', 'profile_details')"
                        type="button"
                        class="text-xs font-bold text-main-dark dark:text-main hover:underline cursor-pointer"
                    >
                        {{ t('dashboard.overview.edit') }}
                    </button>
                </h3>

                <!-- Read-Only Profile Parameters -->
                <div class="space-y-4 text-xs">
                    <div class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-zinc-900">
                        <span class="text-slate-500 dark:text-zinc-400">{{ t('dashboard.overview.fullName') }}</span>
                        <span class="font-bold text-slate-900 dark:text-white">
                            {{ user?.first_name ? `${user.first_name} ${user.last_name || ''}` : (user?.name || '---') }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-zinc-900">
                        <span class="text-slate-500 dark:text-zinc-400">{{ t('dashboard.overview.disabilityGroup') }}</span>
                        <span class="font-bold text-slate-900 dark:text-white">
                            {{ getDisabilityLabel(user?.disability_group) }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-zinc-900">
                        <span class="text-slate-500 dark:text-zinc-400">{{ t('dashboard.overview.retirementYear') }}</span>
                        <span
                            v-if="user?.target_retirement_year"
                            class="font-bold text-slate-900 dark:text-white"
                        >
                            {{ user.target_retirement_year }} {{ t('dashboard.overview.yearUnit') }}
                        </span>
                        <button
                            v-else
                            @click="emit('go-to-section', 'profile_details')"
                            type="button"
                            class="px-2 py-0.5 rounded bg-amber-500/20 text-amber-600 dark:text-amber-400 font-bold hover:underline cursor-pointer"
                        >
                            {{ t('dashboard.overview.notSpecified') }}
                        </button>
                    </div>

                    <div class="flex items-center justify-between py-2 border-b border-slate-100 dark:border-zinc-900">
                        <span class="text-slate-500 dark:text-zinc-400">{{ t('dashboard.overview.confirmedService') }}</span>
                        <span
                            v-if="totalYearsWorked > 0"
                            class="font-bold text-slate-900 dark:text-white"
                        >
                            {{ totalYearsWorked }} {{ t('dashboard.overview.yearsWorkedUnit') }}
                        </span>
                        <button
                            v-else
                            @click="emit('go-to-section', 'documents')"
                            type="button"
                            class="px-2 py-0.5 rounded bg-amber-500/20 text-amber-600 dark:text-amber-400 font-bold hover:underline cursor-pointer"
                        >
                            {{ t('dashboard.overview.notFilled') }}
                        </button>
                    </div>
                </div>

                <!-- Run Calculation Button -->
                <div class="pt-2 space-y-2">
                    <Button
                        @click="runCalculation"
                        type="button"
                        class="w-full bg-main text-slate-950 hover:bg-main-dark font-bold shadow-md h-11 disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="form.processing || isCalculationBlocked"
                    >
                        <Calculator class="mr-2 h-4 w-4" />
                        {{ form.processing ? t('dashboard.overview.calculatingBtn') : t('dashboard.overview.calculateBtn') }}
                    </Button>

                    <p v-if="isCalculationBlocked" class="text-[11px] text-amber-600 dark:text-amber-400 font-semibold text-center flex items-center justify-center gap-1">
                        <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                        {{ t('dashboard.overview.blockedNotice') }}
                    </p>
                </div>
            </div>

            <!-- Right: Calculation Results & Breakdown -->
            <div class="lg:col-span-7 space-y-6">
                <template v-if="activeResult">
                    <div class="rounded-2xl border border-main/30 bg-gradient-to-br from-main/10 via-emerald-500/5 to-transparent p-6 shadow-md backdrop-blur-md dark:border-main/20 dark:bg-zinc-950/90 relative overflow-hidden">
                        <div class="absolute -right-6 -bottom-6 opacity-10 pointer-events-none">
                            <TrendingUp class="h-48 w-48 text-main" />
                        </div>

                        <span class="text-xs font-semibold uppercase tracking-wider text-main-dark dark:text-main">
                            {{ t('dashboard.overview.resultTitle') }}
                        </span>

                        <div class="mt-4 flex flex-wrap items-baseline gap-4">
                            <span class="text-4xl font-extrabold text-slate-900 sm:text-5xl dark:text-white">
                                {{ Number(activeResult.final_pension).toLocaleString('uk-UA', { minimumFractionDigits: 2 }) }} ₴
                            </span>
                            <span class="text-xs font-bold text-emerald-600 dark:text-main flex items-center gap-1">
                                <CheckCircle2 class="h-4 w-4" />
                                {{ t('dashboard.overview.calculatedPfu') }}
                            </span>
                        </div>

                        <div class="mt-6 grid grid-cols-2 gap-4 border-t border-slate-200/60 pt-4 dark:border-zinc-800/60">
                            <div>
                                <span class="text-xs text-slate-500 dark:text-zinc-400">{{ t('dashboard.overview.basePension') }}</span>
                                <p class="text-base font-bold text-slate-900 dark:text-white">
                                    {{ Number(activeResult.base_pension).toLocaleString('uk-UA', { minimumFractionDigits: 2 }) }} ₴
                                </p>
                            </div>
                            <div>
                                <span class="text-xs text-slate-500 dark:text-zinc-400">{{ t('dashboard.overview.serviceMultiplier') }}</span>
                                <p class="text-base font-bold text-main-dark dark:text-main">
                                    {{ activeResult.coefficient_multiplier || '1.35' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </template>
                <template v-else>
                    <div class="flex h-48 flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 dark:border-zinc-800 text-slate-400 dark:text-zinc-500 text-xs p-6 text-center gap-2">
                        <Calculator class="h-8 w-8 text-slate-400" />
                        <span>{{ t('dashboard.overview.emptyHistory') }}</span>
                    </div>
                </template>

                <!-- History Log Table -->
                <div v-if="calculationsList.length > 0" class="rounded-2xl border border-slate-200/80 bg-white/70 p-6 shadow-sm backdrop-blur-md dark:border-zinc-800/80 dark:bg-zinc-950/80">
                    <h4 class="mb-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-zinc-400 flex items-center gap-1.5">
                        <History class="h-4 w-4" />
                        {{ t('dashboard.overview.historyTitle') }}
                    </h4>
                    <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                        <div
                            v-for="item in calculationsList"
                            :key="item.id"
                            @click="activeResult = item"
                            class="flex items-center justify-between p-3 rounded-xl border border-slate-100 hover:border-main/40 dark:border-zinc-900 dark:hover:border-main/30 cursor-pointer transition-colors"
                            :class="activeResult?.id === item.id ? 'bg-main/10 dark:bg-main/15 border-main/50' : 'bg-slate-50/50 dark:bg-zinc-900/50'"
                        >
                            <div>
                                <span class="text-sm font-bold text-slate-900 dark:text-white">
                                    {{ Number(item.final_pension).toLocaleString('uk-UA', { minimumFractionDigits: 2 }) }} ₴
                                </span>
                                <span class="ml-2 text-xs text-slate-400">
                                    ({{ t('dashboard.overview.basePension') }}: {{ Number(item.base_pension).toLocaleString('uk-UA') }} ₴)
                                </span>
                            </div>
                            <ArrowUpRight class="h-4 w-4 text-slate-400" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
