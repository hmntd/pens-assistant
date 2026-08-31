<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { toast } from 'vue-sonner';
import { useI18n } from '@/composables/useI18n';
import {
    Search,
    Calendar,
    Calculator,
    Eye,
    Trash2,
    ChevronUp,
    ChevronDown,
    X,
    FileText,
    Layers,
    Clock,
    Loader2,
    Download
} from '@lucide/vue';

const { t, locale } = useI18n();

interface CalculationItem {
    id: number;
    user_id: number;
    user_name: string;
    user_email: string;
    pension_type: string;
    target_retirement_year: number;
    total_service_months: number;
    total_service_years: number;
    kz_wage_coefficient: number;
    zp_macroeconomic_average: number;
    ks_service_coefficient: number;
    base_pension_amount: number;
    final_pension_amount: number;
    created_at: string;
}

interface CalculationDetail extends CalculationItem {
    calculation_breakdown?: any;
    calculation_logs?: string[];
}

const calculations = ref<CalculationItem[]>([]);
const isLoading = ref(false);
const searchQuery = ref('');
const fromDate = ref('');
const toDate = ref('');
const sortBy = ref('created_at');
const sortDir = ref<'asc' | 'desc'>('desc');

// Pagination
const currentPage = ref(1);
const lastPage = ref(1);
const totalRecords = ref(0);
const perPage = ref(15);

// Detail Modal
const showDetailModal = ref(false);
const selectedCalc = ref<CalculationDetail | null>(null);
const isLoadingDetail = ref(false);

// Confirm Delete Modal
const showDeleteModal = ref(false);
const targetCalc = ref<CalculationItem | null>(null);
const isDeleting = ref(false);

function getCsrfToken(): string {
    if (typeof document === 'undefined') return '';
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    if (match) return decodeURIComponent(match[1]);
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') || '' : '';
}

async function apiFetch(url: string, options: RequestInit = {}) {
    const headers: Record<string, string> = {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': getCsrfToken(),
        ...((options.headers as Record<string, string>) || {}),
    };
    return fetch(url, { ...options, headers });
}

async function fetchCalculations() {
    isLoading.value = true;
    try {
        const queryParams = new URLSearchParams({
            page: currentPage.value.toString(),
            per_page: perPage.value.toString(),
            search: searchQuery.value,
            from_date: fromDate.value,
            to_date: toDate.value,
            sort_by: sortBy.value,
            sort_dir: sortDir.value,
        });
        const res = await apiFetch(`/admin/pension-calculations?${queryParams.toString()}`);
        const data = await res.json();
        if (res.ok && data.status === 'success') {
            const paginated = data.data;
            calculations.value = paginated.data || [];
            currentPage.value = paginated.current_page || 1;
            lastPage.value = paginated.last_page || 1;
            totalRecords.value = paginated.total || 0;
        } else {
            toast.error(data.message || 'Помилка завантаження історії розрахунків.');
        }
    } catch (err: any) {
        toast.error('Помилка мережі.');
    } finally {
        isLoading.value = false;
    }
}

function handleSort(column: string) {
    if (sortBy.value === column) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = column;
        sortDir.value = 'desc';
    }
    currentPage.value = 1;
    fetchCalculations();
}

async function viewCalculationDetails(calc: CalculationItem) {
    showDetailModal.value = true;
    isLoadingDetail.value = true;
    selectedCalc.value = null;
    try {
        const res = await apiFetch(`/admin/pension-calculations/${calc.id}`);
        const data = await res.json();
        if (res.ok && data.status === 'success') {
            selectedCalc.value = data.data;
        } else {
            toast.error('Не вдалося завантажити деталі розрахунку.');
            showDetailModal.value = false;
        }
    } catch (err: any) {
        toast.error('Помилка завантаження деталей розрахунку.');
        showDetailModal.value = false;
    } finally {
        isLoadingDetail.value = false;
    }
}

function confirmDelete(calc: CalculationItem) {
    targetCalc.value = calc;
    showDeleteModal.value = true;
}

async function executeDelete() {
    if (!targetCalc.value) return;
    isDeleting.value = true;
    try {
        const res = await apiFetch(`/admin/pension-calculations/${targetCalc.value.id}`, { method: 'DELETE' });
        const data = await res.json();
        if (res.ok && data.status === 'success') {
            toast.success(data.message || 'Запис розрахунку вилучено.');
            fetchCalculations();
        } else {
            toast.error(data.message || 'Помилка вилучення запису.');
        }
    } catch (err: any) {
        toast.error('Помилка мережі при вилученні.');
    } finally {
        isDeleting.value = false;
        showDeleteModal.value = false;
        targetCalc.value = null;
    }
}

let searchDebounce: any = null;
watch(searchQuery, () => {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => {
        currentPage.value = 1;
        fetchCalculations();
    }, 350);
});

watch([fromDate, toDate], () => {
    currentPage.value = 1;
    fetchCalculations();
});

onMounted(() => {
    fetchCalculations();
});
</script>

<template>
    <div class="space-y-6">
        <!-- Control Bar: Search & Date Range Filters -->
        <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4 bg-white dark:bg-zinc-900 p-4 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-xs">
            <div class="relative flex-1 max-w-md">
                <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 dark:text-zinc-500" />
                <input
                    v-model="searchQuery"
                    type="text"
                    :placeholder="t('adminCalculations.searchPlaceholder')"
                    class="w-full pl-10 pr-4 py-2 text-xs sm:text-sm bg-slate-50 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl focus:outline-none focus:ring-2 focus:ring-main"
                />
            </div>

            <!-- Date Range Filters -->
            <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap text-xs">
                <div class="flex items-center gap-1 bg-slate-50 dark:bg-zinc-950 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-zinc-800">
                    <span class="text-slate-400">From:</span>
                    <input v-model="fromDate" type="date" class="bg-transparent focus:outline-none" />
                </div>
                <div class="flex items-center gap-1 bg-slate-50 dark:bg-zinc-950 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-zinc-800">
                    <span class="text-slate-400">To:</span>
                    <input v-model="toDate" type="date" class="bg-transparent focus:outline-none" />
                </div>
                <button
                    v-if="fromDate || toDate"
                    @click="fromDate = ''; toDate = '';"
                    class="px-2.5 py-1.5 rounded-xl bg-slate-100 dark:bg-zinc-800 hover:bg-slate-200 text-slate-600 dark:text-zinc-300 cursor-pointer"
                >
                    Clear
                </button>
            </div>
        </div>

        <!-- Data Table Container -->
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-xs overflow-hidden">
            <div class="overflow-x-auto [&::-webkit-scrollbar]:h-1.5 [&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar-thumb]:bg-slate-300 dark:[&::-webkit-scrollbar-thumb]:bg-zinc-800 [&::-webkit-scrollbar-thumb]:rounded-full">
                <table class="w-full text-left text-xs sm:text-sm min-w-[750px]">
                    <thead class="bg-slate-50 dark:bg-zinc-950 text-slate-500 dark:text-zinc-400 font-extrabold uppercase tracking-wider text-[10px] sm:text-[11px] border-b border-slate-200 dark:border-zinc-800">
                        <tr>
                            <th @click="handleSort('id')" class="p-3.5 cursor-pointer hover:text-slate-900 dark:hover:text-white transition-colors">
                                <div class="flex items-center gap-1">
                                    <span>{{ t('adminCalculations.columnId') }}</span>
                                    <ChevronUp v-if="sortBy === 'id' && sortDir === 'asc'" class="h-3.5 w-3.5" />
                                    <ChevronDown v-if="sortBy === 'id' && sortDir === 'desc'" class="h-3.5 w-3.5" />
                                </div>
                            </th>
                            <th class="p-3.5">{{ t('adminCalculations.columnUser') }}</th>
                            <th @click="handleSort('target_retirement_year')" class="p-3.5 cursor-pointer hover:text-slate-900 dark:hover:text-white transition-colors">
                                <div class="flex items-center gap-1">
                                    <span>{{ t('adminUsers.retirementYearLabel') }}</span>
                                    <ChevronUp v-if="sortBy === 'target_retirement_year' && sortDir === 'asc'" class="h-3.5 w-3.5" />
                                    <ChevronDown v-if="sortBy === 'target_retirement_year' && sortDir === 'desc'" class="h-3.5 w-3.5" />
                                </div>
                            </th>
                            <th @click="handleSort('base_pension_amount')" class="p-3.5 cursor-pointer hover:text-slate-900 dark:hover:text-white transition-colors">
                                <div class="flex items-center gap-1">
                                    <span>{{ t('adminCalculations.columnBasePension') }}</span>
                                    <ChevronUp v-if="sortBy === 'base_pension_amount' && sortDir === 'asc'" class="h-3.5 w-3.5" />
                                    <ChevronDown v-if="sortBy === 'base_pension_amount' && sortDir === 'desc'" class="h-3.5 w-3.5" />
                                </div>
                            </th>
                            <th @click="handleSort('final_pension_amount')" class="p-3.5 cursor-pointer hover:text-slate-900 dark:hover:text-white transition-colors">
                                <div class="flex items-center gap-1">
                                    <span>{{ t('adminCalculations.columnFinalPension') }}</span>
                                    <ChevronUp v-if="sortBy === 'final_pension_amount' && sortDir === 'asc'" class="h-3.5 w-3.5" />
                                    <ChevronDown v-if="sortBy === 'final_pension_amount' && sortDir === 'desc'" class="h-3.5 w-3.5" />
                                </div>
                            </th>
                            <th @click="handleSort('created_at')" class="p-3.5 cursor-pointer hover:text-slate-900 dark:hover:text-white transition-colors">
                                <div class="flex items-center gap-1">
                                    <span>{{ t('adminCalculations.columnDate') }}</span>
                                    <ChevronUp v-if="sortBy === 'created_at' && sortDir === 'asc'" class="h-3.5 w-3.5" />
                                    <ChevronDown v-if="sortBy === 'created_at' && sortDir === 'desc'" class="h-3.5 w-3.5" />
                                </div>
                            </th>
                            <th class="p-3.5 text-right">{{ t('adminCalculations.columnActions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/80 font-medium">
                        <tr v-if="isLoading">
                            <td colspan="7" class="p-8 text-center text-slate-400">
                                <div class="flex items-center justify-center gap-2">
                                    <Loader2 class="h-5 w-5 animate-spin text-main" />
                                    <span>{{ t('adminCalculations.loadingCalculations') }}</span>
                                </div>
                            </td>
                        </tr>
                        <tr v-else-if="calculations.length === 0">
                            <td colspan="7" class="p-8 text-center text-slate-400">
                                {{ t('adminCalculations.noCalculationsFound') }}
                            </td>
                        </tr>
                        <tr v-for="calc in calculations" :key="calc.id" class="hover:bg-slate-50/80 dark:hover:bg-zinc-950/60 transition-colors">
                            <td class="p-3.5 font-bold font-mono text-slate-500">#{{ calc.id }}</td>
                            <td class="p-3.5">
                                <div>
                                    <div class="font-bold text-slate-900 dark:text-white">{{ calc.user_name }}</div>
                                    <div class="text-[11px] font-mono text-slate-400">{{ calc.user_email }}</div>
                                </div>
                            </td>
                            <td class="p-3.5 font-bold text-slate-700 dark:text-zinc-300">{{ calc.target_retirement_year }}</td>
                            <td class="p-3.5 font-mono text-slate-600 dark:text-zinc-300">{{ calc.base_pension_amount.toLocaleString('uk-UA', { minimumFractionDigits: 2 }) }} ₴</td>
                            <td class="p-3.5 font-mono font-extrabold text-main-dark dark:text-main text-sm sm:text-base">
                                {{ calc.final_pension_amount.toLocaleString('uk-UA', { minimumFractionDigits: 2 }) }} ₴
                            </td>
                            <td class="p-3.5 text-slate-500 font-mono text-xs">{{ calc.created_at }}</td>
                            <td class="p-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button @click="viewCalculationDetails(calc)" :title="t('adminCalculations.btnViewBreakdown')" class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-zinc-800 text-slate-600 dark:text-zinc-400 cursor-pointer">
                                        <Eye class="h-4 w-4" />
                                    </button>
                                    <button @click="confirmDelete(calc)" :title="t('adminCalculations.btnDelete')" class="p-1.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-950/50 text-red-600 dark:text-red-400 cursor-pointer">
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            <div class="flex items-center justify-between p-4 border-t border-slate-200 dark:border-zinc-800 text-xs text-slate-500">
                <div>{{ t('adminCalculations.totalRecords') }} <span class="font-bold text-slate-900 dark:text-white">{{ totalRecords }}</span></div>
                <div class="flex items-center gap-2">
                    <button
                        :disabled="currentPage <= 1"
                        @click="currentPage--; fetchCalculations();"
                        class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-zinc-800 disabled:opacity-40 cursor-pointer"
                    >
                        {{ t('adminUsers.pagePrev') }}
                    </button>
                    <span>{{ t('adminUsers.pageWord') }} {{ currentPage }} {{ t('adminUsers.pageOf') }} {{ lastPage }}</span>
                    <button
                        :disabled="currentPage >= lastPage"
                        @click="currentPage++; fetchCalculations();"
                        class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-zinc-800 disabled:opacity-40 cursor-pointer"
                    >
                        {{ t('adminUsers.pageNext') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Detailed Breakdown & C++ Audit Logs Modal -->
        <div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
            <div class="bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-3xl w-full max-w-2xl p-6 shadow-2xl space-y-6 relative max-h-[85vh] flex flex-col">
                <button @click="showDetailModal = false" class="absolute right-5 top-5 text-slate-400 hover:text-slate-900 dark:hover:text-white cursor-pointer">
                    <X class="h-5 w-5" />
                </button>

                <div class="flex items-center gap-2 text-main-dark dark:text-main shrink-0">
                    <Calculator class="h-6 w-6 text-main shrink-0" />
                    <h3 class="text-lg font-extrabold text-slate-900 dark:text-white">
                        {{ t('adminCalculations.modalTitle') }} #{{ selectedCalc?.id }}
                    </h3>
                </div>

                <div v-if="isLoadingDetail" class="py-12 text-center text-slate-400 flex-1">
                    <Loader2 class="h-6 w-6 animate-spin text-main mx-auto mb-2" />
                    <span>{{ t('adminUsers.loadingData') }}</span>
                </div>

                <div v-else-if="selectedCalc" class="flex-1 overflow-y-auto space-y-4 text-xs pr-1 [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar-thumb]:bg-slate-300 dark:[&::-webkit-scrollbar-thumb]:bg-zinc-800 [&::-webkit-scrollbar-thumb]:rounded-full">
                    <!-- Key Formula Indicators -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-4 rounded-2xl bg-slate-50 dark:bg-zinc-900 border border-slate-100 dark:border-zinc-800">
                        <div>
                            <span class="text-slate-400 block">Kz (Wage Coeff):</span>
                            <span class="text-base font-black text-slate-900 dark:text-white">{{ Number(selectedCalc.kz_wage_coefficient).toFixed(4) }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block">Zp (Average):</span>
                            <span class="text-base font-black text-slate-900 dark:text-white">{{ Number(selectedCalc.zp_macroeconomic_average).toLocaleString('uk-UA') }} ₴</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block">Ks (Service Coeff):</span>
                            <span class="text-base font-black text-slate-900 dark:text-white">{{ Number(selectedCalc.ks_service_coefficient).toFixed(4) }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block">Final Pension:</span>
                            <span class="text-base font-black text-main-dark dark:text-main">{{ Number(selectedCalc.final_pension_amount).toLocaleString('uk-UA') }} ₴</span>
                        </div>
                    </div>

                    <!-- 5-Stage Execution Audit Logs -->
                    <div class="space-y-2">
                        <h4 class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                            <FileText class="h-4 w-4 text-main" />
                            <span>{{ t('adminCalculations.modalSub') }}</span>
                        </h4>
                        <div v-if="selectedCalc.calculation_logs && selectedCalc.calculation_logs.length > 0"
                            class="rounded-2xl bg-zinc-950 p-4 font-mono text-[11px] text-emerald-400 leading-relaxed max-h-64 overflow-y-auto space-y-1 [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar-thumb]:bg-slate-300 dark:[&::-webkit-scrollbar-thumb]:bg-zinc-800 [&::-webkit-scrollbar-thumb]:rounded-full">
                            <div v-for="(line, idx) in selectedCalc.calculation_logs" :key="idx">&gt; {{ line }}</div>
                        </div>
                    </div>
                </div>

                <div v-if="selectedCalc" class="pt-4 border-t border-slate-100 dark:border-zinc-800 flex items-center justify-end gap-2 shrink-0">
                    <a
                        :href="`/admin/pension-calculations/${selectedCalc.id}/pdf?lang=${locale}`"
                        download
                        class="px-4 py-2 bg-slate-900 dark:bg-white text-white dark:text-slate-950 text-xs font-bold hover:bg-slate-800 dark:hover:bg-slate-100 rounded-xl flex items-center gap-2 cursor-pointer transition-colors"
                    >
                        <Download class="h-4 w-4" />
                        <span>{{ t('dashboard.details.downloadPdf') }}</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Confirm Delete Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
            <div class="bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-3xl w-full max-w-md p-6 shadow-2xl space-y-4">
                <h3 class="text-base font-extrabold text-slate-900 dark:text-white">{{ t('adminCalculations.btnDelete') }}?</h3>
                <p class="text-xs text-slate-500 leading-relaxed">
                    {{ t('adminUsers.modalConfirmText') }} #<span class="font-bold text-slate-900 dark:text-white">{{ targetCalc?.id }}</span>?
                </p>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button @click="showDeleteModal = false" type="button" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl cursor-pointer">
                        {{ t('adminUsers.btnCancel') }}
                    </button>
                    <button
                        @click="executeDelete()"
                        :disabled="isDeleting"
                        type="button"
                        class="px-4 py-2 text-xs font-bold bg-red-600 text-white hover:bg-red-700 rounded-xl cursor-pointer disabled:opacity-50"
                    >
                        {{ isDeleting ? t('adminUsers.btnExecuting') : t('adminUsers.btnConfirm') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
