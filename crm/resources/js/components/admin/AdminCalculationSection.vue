<script setup lang="ts">
import {
    Search,
    Calculator,
    Eye,
    Trash2,
    ChevronUp,
    ChevronDown,
    X,
    FileText,
    Loader2,
    Download,
} from '@lucide/vue';
import { ref, onMounted, watch } from 'vue';
import { toast } from 'vue-sonner';
import { useI18n } from '@/composables/useI18n';
import type {
    AdminCalculationItem as CalculationItem,
    AdminCalculationDetail as CalculationDetail,
} from '@/types';

const { t, locale } = useI18n();

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
    if (typeof document === 'undefined') {
        return '';
    }

    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);

    if (match) {
        return decodeURIComponent(match[1]);
    }

    const meta = document.querySelector('meta[name="csrf-token"]');

    return meta ? meta.getAttribute('content') || '' : '';
}

async function apiFetch(url: string, options: RequestInit = {}) {
    const headers: Record<string, string> = {
        Accept: 'application/json',
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
        const res = await apiFetch(
            `/admin/pension-calculations?${queryParams.toString()}`,
        );
        const data = await res.json();

        if (res.ok && data.status === 'success') {
            const paginated = data.data;
            calculations.value = paginated.data || [];
            currentPage.value = paginated.current_page || 1;
            lastPage.value = paginated.last_page || 1;
            totalRecords.value = paginated.total || 0;
        } else {
            toast.error(data.message || t('adminCalculations.errorFetch'));
        }
    } catch {
        toast.error(t('adminCalculations.networkErrorFetch'));
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
            return (selectedCalc.value = data.data);
        }

        toast.error(t('adminCalculations.errorDetailFetch'));
        showDetailModal.value = false;
    } catch {
        toast.error(t('adminCalculations.errorDetailFetch'));
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
    if (!targetCalc.value) {
        return;
    }

    isDeleting.value = true;

    try {
        const res = await apiFetch(
            `/admin/pension-calculations/${targetCalc.value.id}`,
            { method: 'DELETE' },
        );
        const data = await res.json();

        if (res.ok && data.status === 'success') {
            toast.success(data.message || t('adminCalculations.successDelete'));
            fetchCalculations();
        } else {
            toast.error(data.message || t('adminCalculations.errorDelete'));
        }
    } catch {
        toast.error(t('adminCalculations.networkErrorDelete'));
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
        <div
            class="flex flex-col items-stretch justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-xs md:flex-row md:items-center dark:border-zinc-800 dark:bg-zinc-900"
        >
            <div class="relative max-w-md flex-1">
                <Search
                    class="absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-zinc-500"
                />
                <input
                    v-model="searchQuery"
                    type="text"
                    :placeholder="t('adminCalculations.searchPlaceholder')"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pr-4 pl-10 text-xs focus:ring-2 focus:ring-main focus:outline-none sm:text-sm dark:border-zinc-800 dark:bg-zinc-950"
                />
            </div>

            <!-- Date Range Filters -->
            <div
                class="flex flex-wrap items-center gap-2 text-xs sm:flex-nowrap"
            >
                <div
                    class="flex items-center gap-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 dark:border-zinc-800 dark:bg-zinc-950"
                >
                    <span class="text-slate-400">From:</span>
                    <input
                        v-model="fromDate"
                        type="date"
                        class="bg-transparent focus:outline-none"
                    />
                </div>
                <div
                    class="flex items-center gap-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 dark:border-zinc-800 dark:bg-zinc-950"
                >
                    <span class="text-slate-400">To:</span>
                    <input
                        v-model="toDate"
                        type="date"
                        class="bg-transparent focus:outline-none"
                    />
                </div>
                <button
                    v-if="fromDate || toDate"
                    @click="
                        fromDate = '';
                        toDate = '';
                    "
                    class="cursor-pointer rounded-xl bg-slate-100 px-2.5 py-1.5 text-slate-600 hover:bg-slate-200 dark:bg-zinc-800 dark:text-zinc-300"
                >
                    Clear
                </button>
            </div>
        </div>

        <!-- Data Table Container -->
        <div
            class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900"
        >
            <div
                class="overflow-x-auto [&::-webkit-scrollbar]:h-1.5 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-slate-300 dark:[&::-webkit-scrollbar-thumb]:bg-zinc-800 [&::-webkit-scrollbar-track]:bg-transparent"
            >
                <table
                    class="w-full min-w-[750px] text-left text-xs sm:text-sm"
                >
                    <thead
                        class="border-b border-slate-200 bg-slate-50 text-[10px] font-extrabold tracking-wider text-slate-500 uppercase sm:text-[11px] dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-400"
                    >
                        <tr>
                            <th
                                @click="handleSort('id')"
                                class="cursor-pointer p-3.5 transition-colors hover:text-slate-900 dark:hover:text-white"
                            >
                                <div class="flex items-center gap-1">
                                    <span>{{
                                        t('adminCalculations.columnId')
                                    }}</span>
                                    <ChevronUp
                                        v-if="
                                            sortBy === 'id' && sortDir === 'asc'
                                        "
                                        class="h-3.5 w-3.5"
                                    />
                                    <ChevronDown
                                        v-if="
                                            sortBy === 'id' &&
                                            sortDir === 'desc'
                                        "
                                        class="h-3.5 w-3.5"
                                    />
                                </div>
                            </th>
                            <th class="p-3.5">
                                {{ t('adminCalculations.columnUser') }}
                            </th>
                            <th
                                @click="handleSort('target_retirement_year')"
                                class="cursor-pointer p-3.5 transition-colors hover:text-slate-900 dark:hover:text-white"
                            >
                                <div class="flex items-center gap-1">
                                    <span>{{
                                        t('adminUsers.retirementYearLabel')
                                    }}</span>
                                    <ChevronUp
                                        v-if="
                                            sortBy ===
                                                'target_retirement_year' &&
                                            sortDir === 'asc'
                                        "
                                        class="h-3.5 w-3.5"
                                    />
                                    <ChevronDown
                                        v-if="
                                            sortBy ===
                                                'target_retirement_year' &&
                                            sortDir === 'desc'
                                        "
                                        class="h-3.5 w-3.5"
                                    />
                                </div>
                            </th>
                            <th
                                @click="handleSort('base_pension_amount')"
                                class="cursor-pointer p-3.5 transition-colors hover:text-slate-900 dark:hover:text-white"
                            >
                                <div class="flex items-center gap-1">
                                    <span>{{
                                        t('adminCalculations.columnBasePension')
                                    }}</span>
                                    <ChevronUp
                                        v-if="
                                            sortBy === 'base_pension_amount' &&
                                            sortDir === 'asc'
                                        "
                                        class="h-3.5 w-3.5"
                                    />
                                    <ChevronDown
                                        v-if="
                                            sortBy === 'base_pension_amount' &&
                                            sortDir === 'desc'
                                        "
                                        class="h-3.5 w-3.5"
                                    />
                                </div>
                            </th>
                            <th
                                @click="handleSort('final_pension_amount')"
                                class="cursor-pointer p-3.5 transition-colors hover:text-slate-900 dark:hover:text-white"
                            >
                                <div class="flex items-center gap-1">
                                    <span>{{
                                        t(
                                            'adminCalculations.columnFinalPension',
                                        )
                                    }}</span>
                                    <ChevronUp
                                        v-if="
                                            sortBy === 'final_pension_amount' &&
                                            sortDir === 'asc'
                                        "
                                        class="h-3.5 w-3.5"
                                    />
                                    <ChevronDown
                                        v-if="
                                            sortBy === 'final_pension_amount' &&
                                            sortDir === 'desc'
                                        "
                                        class="h-3.5 w-3.5"
                                    />
                                </div>
                            </th>
                            <th
                                @click="handleSort('created_at')"
                                class="cursor-pointer p-3.5 transition-colors hover:text-slate-900 dark:hover:text-white"
                            >
                                <div class="flex items-center gap-1">
                                    <span>{{
                                        t('adminCalculations.columnDate')
                                    }}</span>
                                    <ChevronUp
                                        v-if="
                                            sortBy === 'created_at' &&
                                            sortDir === 'asc'
                                        "
                                        class="h-3.5 w-3.5"
                                    />
                                    <ChevronDown
                                        v-if="
                                            sortBy === 'created_at' &&
                                            sortDir === 'desc'
                                        "
                                        class="h-3.5 w-3.5"
                                    />
                                </div>
                            </th>
                            <th class="p-3.5 text-right">
                                {{ t('adminCalculations.columnActions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-slate-100 font-medium dark:divide-zinc-800/80"
                    >
                        <tr v-if="isLoading">
                            <td
                                colspan="7"
                                class="p-8 text-center text-slate-400"
                            >
                                <div
                                    class="flex items-center justify-center gap-2"
                                >
                                    <Loader2
                                        class="h-5 w-5 animate-spin text-main"
                                    />
                                    <span>{{
                                        t(
                                            'adminCalculations.loadingCalculations',
                                        )
                                    }}</span>
                                </div>
                            </td>
                        </tr>
                        <tr v-else-if="calculations.length === 0">
                            <td
                                colspan="7"
                                class="p-8 text-center text-slate-400"
                            >
                                {{ t('adminCalculations.noCalculationsFound') }}
                            </td>
                        </tr>
                        <tr
                            v-for="calc in calculations"
                            :key="calc.id"
                            class="transition-colors hover:bg-slate-50/80 dark:hover:bg-zinc-950/60"
                        >
                            <td
                                class="p-3.5 font-mono font-bold text-slate-500"
                            >
                                #{{ calc.id }}
                            </td>
                            <td class="p-3.5">
                                <div>
                                    <div
                                        class="font-bold text-slate-900 dark:text-white"
                                    >
                                        {{ calc.user_name }}
                                    </div>
                                    <div
                                        class="font-mono text-[11px] text-slate-400"
                                    >
                                        {{ calc.user_email }}
                                    </div>
                                </div>
                            </td>
                            <td
                                class="p-3.5 font-bold text-slate-700 dark:text-zinc-300"
                            >
                                {{ calc.target_retirement_year }}
                            </td>
                            <td
                                class="p-3.5 font-mono text-slate-600 dark:text-zinc-300"
                            >
                                {{
                                    calc.base_pension_amount.toLocaleString(
                                        'uk-UA',
                                        { minimumFractionDigits: 2 },
                                    )
                                }}
                                ₴
                            </td>
                            <td
                                class="p-3.5 font-mono text-sm font-extrabold text-main-dark sm:text-base dark:text-main"
                            >
                                {{
                                    calc.final_pension_amount.toLocaleString(
                                        'uk-UA',
                                        { minimumFractionDigits: 2 },
                                    )
                                }}
                                ₴
                            </td>
                            <td class="p-3.5 font-mono text-xs text-slate-500">
                                {{ calc.created_at }}
                            </td>
                            <td class="p-3.5 text-right">
                                <div
                                    class="flex items-center justify-end gap-1.5"
                                >
                                    <button
                                        @click="viewCalculationDetails(calc)"
                                        :title="
                                            t(
                                                'adminCalculations.btnViewBreakdown',
                                            )
                                        "
                                        class="cursor-pointer rounded-lg p-1.5 text-slate-600 hover:bg-slate-100 dark:text-zinc-400 dark:hover:bg-zinc-800"
                                    >
                                        <Eye class="h-4 w-4" />
                                    </button>
                                    <button
                                        @click="confirmDelete(calc)"
                                        :title="
                                            t('adminCalculations.btnDelete')
                                        "
                                        class="cursor-pointer rounded-lg p-1.5 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/50"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            <div
                class="flex items-center justify-between border-t border-slate-200 p-4 text-xs text-slate-500 dark:border-zinc-800"
            >
                <div>
                    {{ t('adminCalculations.totalRecords') }}
                    <span class="font-bold text-slate-900 dark:text-white">{{
                        totalRecords
                    }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        :disabled="currentPage <= 1"
                        @click="
                            currentPage--;
                            fetchCalculations();
                        "
                        class="cursor-pointer rounded-lg border border-slate-200 px-3 py-1.5 disabled:opacity-40 dark:border-zinc-800"
                    >
                        {{ t('adminUsers.pagePrev') }}
                    </button>
                    <span
                        >{{ t('adminUsers.pageWord') }} {{ currentPage }}
                        {{ t('adminUsers.pageOf') }} {{ lastPage }}</span
                    >
                    <button
                        :disabled="currentPage >= lastPage"
                        @click="
                            currentPage++;
                            fetchCalculations();
                        "
                        class="cursor-pointer rounded-lg border border-slate-200 px-3 py-1.5 disabled:opacity-40 dark:border-zinc-800"
                    >
                        {{ t('adminUsers.pageNext') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Detailed Breakdown & C++ Audit Logs Modal -->
        <div
            v-if="showDetailModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs"
        >
            <div
                class="relative flex max-h-[85vh] w-full max-w-2xl flex-col space-y-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-zinc-800 dark:bg-zinc-950"
            >
                <button
                    @click="showDetailModal = false"
                    class="absolute top-5 right-5 cursor-pointer text-slate-400 hover:text-slate-900 dark:hover:text-white"
                >
                    <X class="h-5 w-5" />
                </button>

                <div
                    class="flex shrink-0 items-center gap-2 text-main-dark dark:text-main"
                >
                    <Calculator class="h-6 w-6 shrink-0 text-main" />
                    <h3
                        class="text-lg font-extrabold text-slate-900 dark:text-white"
                    >
                        {{ t('adminCalculations.modalTitle') }} #{{
                            selectedCalc?.id
                        }}
                    </h3>
                </div>

                <div
                    v-if="isLoadingDetail"
                    class="flex-1 py-12 text-center text-slate-400"
                >
                    <Loader2
                        class="mx-auto mb-2 h-6 w-6 animate-spin text-main"
                    />
                    <span>{{ t('adminUsers.loadingData') }}</span>
                </div>

                <div
                    v-else-if="selectedCalc"
                    class="flex-1 space-y-4 overflow-y-auto pr-1 text-xs [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-slate-300 dark:[&::-webkit-scrollbar-thumb]:bg-zinc-800 [&::-webkit-scrollbar-track]:bg-transparent"
                >
                    <!-- Key Formula Indicators -->
                    <div
                        class="grid grid-cols-2 gap-3 rounded-2xl border border-slate-100 bg-slate-50 p-4 sm:grid-cols-4 dark:border-zinc-800 dark:bg-zinc-900"
                    >
                        <div>
                            <span class="block text-slate-400"
                                >Kz (Wage Coeff):</span
                            >
                            <span
                                class="text-base font-black text-slate-900 dark:text-white"
                                >{{
                                    Number(
                                        selectedCalc.kz_wage_coefficient,
                                    ).toFixed(4)
                                }}</span
                            >
                        </div>
                        <div>
                            <span class="block text-slate-400"
                                >Zp (Average):</span
                            >
                            <span
                                class="text-base font-black text-slate-900 dark:text-white"
                                >{{
                                    Number(
                                        selectedCalc.zp_macroeconomic_average,
                                    ).toLocaleString('uk-UA')
                                }}
                                ₴</span
                            >
                        </div>
                        <div>
                            <span class="block text-slate-400"
                                >Ks (Service Coeff):</span
                            >
                            <span
                                class="text-base font-black text-slate-900 dark:text-white"
                                >{{
                                    Number(
                                        selectedCalc.ks_service_coefficient,
                                    ).toFixed(4)
                                }}</span
                            >
                        </div>
                        <div>
                            <span class="block text-slate-400"
                                >Final Pension:</span
                            >
                            <span
                                class="text-base font-black text-main-dark dark:text-main"
                                >{{
                                    Number(
                                        selectedCalc.final_pension_amount,
                                    ).toLocaleString('uk-UA')
                                }}
                                ₴</span
                            >
                        </div>
                    </div>

                    <!-- 5-Stage Execution Audit Logs -->
                    <div class="space-y-2">
                        <h4
                            class="flex items-center gap-1.5 font-bold text-slate-900 dark:text-white"
                        >
                            <FileText class="h-4 w-4 text-main" />
                            <span>{{ t('adminCalculations.modalSub') }}</span>
                        </h4>
                        <div
                            v-if="
                                selectedCalc.calculation_logs &&
                                selectedCalc.calculation_logs.length > 0
                            "
                            class="max-h-64 space-y-1 overflow-y-auto rounded-2xl bg-zinc-950 p-4 font-mono text-[11px] leading-relaxed text-emerald-400 [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-slate-300 dark:[&::-webkit-scrollbar-thumb]:bg-zinc-800 [&::-webkit-scrollbar-track]:bg-transparent"
                        >
                            <div
                                v-for="(
                                    line, idx
                                ) in selectedCalc.calculation_logs"
                                :key="idx"
                            >
                                &gt; {{ line }}
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-if="selectedCalc"
                    class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 pt-4 dark:border-zinc-800"
                >
                    <a
                        :href="`/admin/pension-calculations/${selectedCalc.id}/pdf?lang=${locale}`"
                        download
                        class="flex cursor-pointer items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-xs font-bold text-white transition-colors hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-100"
                    >
                        <Download class="h-4 w-4" />
                        <span>{{ t('dashboard.details.downloadPdf') }}</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Confirm Delete Modal -->
        <div
            v-if="showDeleteModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs"
        >
            <div
                class="w-full max-w-md space-y-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-zinc-800 dark:bg-zinc-950"
            >
                <h3
                    class="text-base font-extrabold text-slate-900 dark:text-white"
                >
                    {{ t('adminCalculations.btnDelete') }}?
                </h3>
                <p class="text-xs leading-relaxed text-slate-500">
                    {{ t('adminUsers.modalConfirmText') }} #<span
                        class="font-bold text-slate-900 dark:text-white"
                        >{{ targetCalc?.id }}</span
                    >?
                </p>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button
                        @click="showDeleteModal = false"
                        type="button"
                        class="cursor-pointer rounded-xl px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100"
                    >
                        {{ t('adminUsers.btnCancel') }}
                    </button>
                    <button
                        @click="executeDelete()"
                        :disabled="isDeleting"
                        type="button"
                        class="cursor-pointer rounded-xl bg-red-600 px-4 py-2 text-xs font-bold text-white hover:bg-red-700 disabled:opacity-50"
                    >
                        {{
                            isDeleting
                                ? t('adminUsers.btnExecuting')
                                : t('adminUsers.btnConfirm')
                        }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
