<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';
import { FileText, UploadCloud, Trash2, Pencil, CheckCircle2, AlertCircle, Eye, Calendar, Plus, Layers, X, Download, RefreshCw } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Skeleton } from '@/components/ui/skeleton';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import YearPicker from '@/components/ui/YearPicker.vue';

export interface RecognizedDoc {
    id?: number;
    document_id?: number;
    status?: string;
    raw_text?: string | null;
    extracted_data?: Record<string, any> | null;
    confidence_score?: number | null;
}

export interface DocumentItem {
    id: number;
    original_filename?: string;
    document_type?: string;
    status?: string;
    ocr_status?: string;
    recognized_document?: RecognizedDoc | null;
    extracted_data?: Record<string, any> | null;
    raw_ocr_text?: string | null;
    created_at?: string;
}

export interface TaxHistoryItem {
    id: number;
    year: number;
    annual_income: number;
    months_worked: number;
    tax_paid?: number;
}

const props = defineProps<{
    initialDocuments?: DocumentItem[];
    initialTaxHistories?: TaxHistoryItem[];
}>();

const { t } = useI18n();

const isSectionLoading = ref(true);
const documentsList = ref<DocumentItem[]>(props.initialDocuments || []);
const taxHistoriesList = ref<TaxHistoryItem[]>(props.initialTaxHistories || []);
const selectedDoc = ref<DocumentItem | null>(null);
const isReviewModalOpen = ref(false);

watch(() => props.initialDocuments, (newDocs) => {
    if (newDocs) documentsList.value = newDocs;
}, { deep: true });

watch(() => props.initialTaxHistories, (newTaxes) => {
    if (newTaxes) taxHistoriesList.value = newTaxes;
}, { deep: true });

// Status helper utilities
function getDocStatus(doc: DocumentItem): string {
    return (doc.status || doc.ocr_status || doc.recognized_document?.status || 'pending').toLowerCase();
}

function isDocCompleted(doc: DocumentItem): boolean {
    const s = getDocStatus(doc);
    return ['completed', 'processed', 'success'].includes(s);
}

function isDocFailed(doc: DocumentItem): boolean {
    return getDocStatus(doc) === 'failed';
}

function isDocPending(doc: DocumentItem): boolean {
    const s = getDocStatus(doc);
    return ['pending', 'processing'].includes(s);
}

// Upload form
const uploadForm = useForm<{
    file: File | null;
    document_type: string;
}>({
    file: null,
    document_type: 'trudova_auto',
});

// Manual tax history form
const isRangeMode = ref(true);
const currentYear = new Date().getFullYear();

const manualTaxForm = useForm({
    is_range: true,
    year: currentYear,
    from_year: 2020,
    to_year: currentYear,
    monthly_salary: 15000,
    months_worked: 12,
});

const totalWorkedYearsCount = computed(() => taxHistoriesList.value.length);
const totalAccumulatedMonthsCount = computed(() => {
    return taxHistoriesList.value.reduce((acc, curr) => acc + (curr.months_worked || 12), 0);
});

// Polling for pending / processing documents update
let pollInterval: ReturnType<typeof setInterval> | null = null;

function hasPendingDocuments(): boolean {
    return documentsList.value.some((d) => isDocPending(d));
}

async function pollDocumentUpdates() {
    try {
        const res = await fetch('/documents', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        if (res.ok) {
            const json = await res.json();
            if (json.data && Array.isArray(json.data)) {
                documentsList.value = json.data as DocumentItem[];
            }
            if (json.tax_histories && Array.isArray(json.tax_histories)) {
                taxHistoriesList.value = json.tax_histories as TaxHistoryItem[];
            }
            if (selectedDoc.value) {
                const updated = documentsList.value.find((d) => d.id === selectedDoc.value?.id);
                if (updated) {
                    selectedDoc.value = updated;
                }
            }
        }
    } catch (err) {
        console.error('Error polling document status:', err);
    }

    if (!hasPendingDocuments() && pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
}

function startPollingIfNeeded() {
    if (hasPendingDocuments() && !pollInterval) {
        pollInterval = setInterval(pollDocumentUpdates, 2000);
    }
}

async function initializeSection() {
    isSectionLoading.value = true;

    // Populate props if present
    if (props.initialDocuments && props.initialDocuments.length > 0) {
        documentsList.value = props.initialDocuments;
    }
    if (props.initialTaxHistories && props.initialTaxHistories.length > 0) {
        taxHistoriesList.value = props.initialTaxHistories;
    }

    // Fetch fresh data from backend
    await pollDocumentUpdates();

    // Hide skeleton once data is ready
    isSectionLoading.value = false;

    startPollingIfNeeded();
}

onMounted(() => {
    initializeSection();
    window.addEventListener('documents-updated', pollDocumentUpdates);
});

onUnmounted(() => {
    window.removeEventListener('documents-updated', pollDocumentUpdates);
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
});

function handleFileSelect(e: Event) {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        uploadForm.file = target.files[0];
        uploadDocument();
    }
}

function uploadDocument() {
    if (!uploadForm.file) return;
    uploadForm.post('/documents/upload', {
        preserveScroll: true,
        onSuccess: (pageRes) => {
            if (pageRes.props.initialDocuments) {
                documentsList.value = pageRes.props.initialDocuments as DocumentItem[];
            }
            if (pageRes.props.initialTaxHistories) {
                taxHistoriesList.value = pageRes.props.initialTaxHistories as TaxHistoryItem[];
            }
            window.dispatchEvent(new CustomEvent('notification-created'));
            uploadForm.reset();
            startPollingIfNeeded();
            pollDocumentUpdates();
        },
    });
}

function submitManualTaxHistory() {
    manualTaxForm.is_range = isRangeMode.value;
    manualTaxForm.post('/documents/tax-histories', {
        preserveScroll: true,
        onSuccess: (res: any) => {
            window.dispatchEvent(new CustomEvent('notification-created'));
            if (res.props?.initialTaxHistories) {
                taxHistoriesList.value = res.props.initialTaxHistories as TaxHistoryItem[];
            } else {
                pollDocumentUpdates();
            }
        },
    });
}

function deleteDocument(id: number) {
    if (!confirm(t('documents.deleteConfirmDoc'))) return;
    useForm({}).delete(`/documents/${id}`, {
        preserveScroll: true,
        onSuccess: () => {
            documentsList.value = documentsList.value.filter((d) => d.id !== id);
            if (selectedDoc.value?.id === id) {
                selectedDoc.value = null;
                isReviewModalOpen.value = false;
            }
        },
    });
}

function deleteTaxHistory(id: number) {
    if (!confirm(t('documents.deleteConfirmTax'))) return;
    useForm({}).delete(`/documents/tax-histories/${id}`, {
        preserveScroll: true,
        onSuccess: () => {
            taxHistoriesList.value = taxHistoriesList.value.filter((tItem) => tItem.id !== id);
        },
    });
}

const { locale } = useI18n();

const ukMonthNames = [
    'Січень', 'Лютий', 'Березень', 'Квітень', 'Травень', 'Червень',
    'Липень', 'Серпень', 'Вересень', 'Жовтень', 'Листопад', 'Грудень'
];

const enMonthNames = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
];

const monthNames = computed(() => locale.value === 'uk' ? ukMonthNames : enMonthNames);

const isEditTaxModalOpen = ref(false);
const editingTaxHistory = ref<any | null>(null);
const monthlyBreakdownForm = ref<{ [key: number]: number }>({
    1: 0, 2: 0, 3: 0, 4: 0, 5: 0, 6: 0, 7: 0, 8: 0, 9: 0, 10: 0, 11: 0, 12: 0
});
const isSubmittingEditTax = ref(false);

function openEditTaxModal(item: any) {
    editingTaxHistory.value = item;
    const monthsWorked = item.months_worked || 12;
    const avgMonthly = item.annual_income / monthsWorked;
    const breakdown = item.monthly_breakdown || {};

    const initial: { [key: number]: number } = {};
    for (let m = 1; m <= 12; m++) {
        const rawVal = breakdown[m] ?? breakdown[String(m)] ?? (m <= monthsWorked ? avgMonthly : 0);
        initial[m] = Math.round(Number(rawVal) * 100) / 100;
    }
    monthlyBreakdownForm.value = initial;
    isEditTaxModalOpen.value = true;
}

const calculatedAnnualSum = computed(() => {
    return Object.values(monthlyBreakdownForm.value).reduce((sum, val) => sum + (Number(val) || 0), 0);
});

function submitEditTaxHistory() {
    if (!editingTaxHistory.value) return;
    isSubmittingEditTax.value = true;

    useForm({
        monthly_breakdown: monthlyBreakdownForm.value,
    }).put(`/documents/tax-histories/${editingTaxHistory.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isEditTaxModalOpen.value = false;
            isSubmittingEditTax.value = false;
            pollDocumentUpdates();
        },
        onError: () => {
            isSubmittingEditTax.value = false;
        },
    });
}

function openReviewModal(doc: DocumentItem) {
    selectedDoc.value = doc;
    isReviewModalOpen.value = true;
}

function closeReviewModal() {
    isReviewModalOpen.value = false;
    selectedDoc.value = null;
}

function getFileStreamUrl(docId: number): string {
    return `/documents/${docId}/file`;
}

function isPdfFile(filename?: string): boolean {
    return filename?.toLowerCase().endsWith('.pdf') ?? false;
}

function getRawText(doc: DocumentItem): string {
    return doc.raw_ocr_text || doc.recognized_document?.raw_text || '';
}

function getExtractedData(doc: DocumentItem): any {
    return doc.extracted_data || doc.recognized_document?.extracted_data || null;
}
</script>

<template>
    <div>
        <!-- Skeleton Loading View -->
        <div v-if="isSectionLoading" class="space-y-8 animate-pulse">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div class="space-y-2">
                    <Skeleton class="h-8 w-64 rounded-xl" />
                    <Skeleton class="h-4 w-96 rounded-lg" />
                </div>
                <Skeleton class="h-8 w-48 rounded-full" />
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
                <div class="lg:col-span-5 space-y-6">
                    <div class="rounded-2xl border border-slate-200/80 bg-white/70 p-6 dark:border-zinc-800/80 dark:bg-zinc-950/80 space-y-4">
                        <Skeleton class="h-5 w-40 rounded-lg" />
                        <Skeleton class="h-36 w-full rounded-2xl" />
                    </div>

                    <div class="rounded-2xl border border-slate-200/80 bg-white/70 p-6 dark:border-zinc-800/80 dark:bg-zinc-950/80 space-y-4">
                        <Skeleton class="h-5 w-48 rounded-lg" />
                        <div class="grid grid-cols-2 gap-3">
                            <Skeleton class="h-10 w-full rounded-xl" />
                            <Skeleton class="h-10 w-full rounded-xl" />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <Skeleton class="h-10 w-full rounded-xl" />
                            <Skeleton class="h-10 w-full rounded-xl" />
                        </div>
                        <Skeleton class="h-10 w-full rounded-xl" />
                    </div>
                </div>

                <div class="lg:col-span-7 space-y-6">
                    <div class="rounded-2xl border border-slate-200/80 bg-white/70 p-6 dark:border-zinc-800/80 dark:bg-zinc-950/80 space-y-4">
                        <div class="flex justify-between items-center border-b border-slate-100 dark:border-zinc-800/60 pb-3">
                            <Skeleton class="h-5 w-44 rounded-lg" />
                            <Skeleton class="h-4 w-24 rounded-lg" />
                        </div>
                        <div class="space-y-3">
                            <Skeleton v-for="i in 4" :key="i" class="h-10 w-full rounded-xl" />
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200/80 bg-white/70 p-6 dark:border-zinc-800/80 dark:bg-zinc-950/80 space-y-4">
                        <div class="flex justify-between items-center border-b border-slate-100 dark:border-zinc-800/60 pb-3">
                            <Skeleton class="h-5 w-40 rounded-lg" />
                            <Skeleton class="h-4 w-16 rounded-lg" />
                        </div>
                        <div class="space-y-3">
                            <Skeleton v-for="i in 3" :key="i" class="h-14 w-full rounded-xl" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Real Content View -->
        <div v-else class="space-y-8">
            <!-- Header -->
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                        <FileText class="h-6 w-6 text-main" />
                        {{ t('documents.title') }}
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-zinc-400">
                        {{ t('documents.subtitle') }}
                    </p>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-main/15 px-3 py-1 text-xs font-bold text-main-dark dark:text-main shrink-0">
                    <Calendar class="h-3.5 w-3.5" />
                    {{ t('documents.totalService') }} {{ totalWorkedYearsCount }} {{ t('documents.yrs') }} ({{ totalAccumulatedMonthsCount }} {{ t('documents.months') }})
                </span>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
                <!-- Left: Document Upload Dropzone & Manual Service Entry -->
                <div class="lg:col-span-5 space-y-6">
                    <!-- Dropzone Section -->
                    <div class="rounded-2xl border border-slate-200/80 bg-white/70 p-6 shadow-sm backdrop-blur-md dark:border-zinc-800/80 dark:bg-zinc-950/80 space-y-4">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-zinc-800/60 pb-3 flex items-center justify-between">
                            <span class="flex items-center gap-2">
                                <UploadCloud class="h-4 w-4 text-main" />
                                {{ t('documents.uploadTitle') }}
                            </span>
                            <span v-if="uploadForm.processing" class="text-xs font-bold text-main flex items-center gap-1">
                                <RefreshCw class="h-3 w-3 animate-spin" />
                                {{ uploadForm.progress?.percentage ?? 0 }}%
                            </span>
                        </h3>

                        <label
                            class="relative flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 p-6 text-center transition-colors hover:border-main cursor-pointer dark:border-zinc-800 dark:hover:border-main/60 bg-slate-50/50 dark:bg-zinc-900/50 overflow-hidden"
                        >
                            <UploadCloud class="h-8 w-8 text-main mb-2" />
                            <span class="text-xs font-bold text-slate-900 dark:text-white">
                                {{ t('documents.dropzoneText') }}
                            </span>
                            <span class="mt-0.5 text-[10px] text-slate-400">
                                {{ t('documents.supportedFormats') }}
                            </span>

                            <!-- Browser upload progress line under the link/dropzone -->
                            <div v-if="uploadForm.processing" class="absolute bottom-0 left-0 right-0 h-1.5 bg-slate-200 dark:bg-zinc-800">
                                <div
                                    class="h-full bg-main transition-all duration-300 ease-out"
                                    :style="{ width: `${uploadForm.progress?.percentage ?? 0}%` }"
                                ></div>
                            </div>

                            <input
                                type="file"
                                accept=".pdf,.png,.jpg,.jpeg"
                                class="sr-only"
                                @change="handleFileSelect"
                            />
                        </label>

                        <!-- Visual Upload Progress Bar -->
                        <div v-if="uploadForm.processing" class="space-y-1.5 pt-1">
                            <div class="flex justify-between text-xs font-bold">
                                <span class="text-slate-700 dark:text-zinc-300 flex items-center gap-1">
                                    <RefreshCw class="h-3 w-3 animate-spin text-main" />
                                    {{ t('documents.uploadingText') }}
                                </span>
                                <span class="text-main font-mono">{{ uploadForm.progress?.percentage ?? 0 }}%</span>
                            </div>
                            <div class="w-full bg-slate-200 dark:bg-zinc-800 h-2.5 rounded-full overflow-hidden p-0.5 border border-slate-300/50 dark:border-zinc-700/50">
                                <div
                                    class="bg-gradient-to-r from-main-dark to-main h-full rounded-full transition-all duration-300 shadow-sm"
                                    :style="{ width: `${uploadForm.progress?.percentage ?? 0}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <!-- Dashed Line Separator with OR / АБО Tag -->
                    <div class="relative flex items-center justify-center py-2">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t-2 border-dashed border-slate-300/80 dark:border-zinc-800/80"></div>
                        </div>
                        <div class="relative bg-slate-100 dark:bg-zinc-900 px-4 py-1 text-xs font-black uppercase tracking-wider text-slate-500 dark:text-zinc-400 rounded-full border border-slate-200 dark:border-zinc-800 shadow-sm">
                            {{ t('documents.orSeparator') }}
                        </div>
                    </div>

                    <!-- Manual Salary & Insurance Service Entry Section -->
                    <div class="rounded-2xl border border-slate-200/80 bg-white/70 p-6 shadow-sm backdrop-blur-md dark:border-zinc-800/80 dark:bg-zinc-950/80 space-y-4">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-zinc-800/60 pb-3 flex items-center justify-between">
                            <span class="flex items-center gap-2">
                                <Plus class="h-4 w-4 text-main" />
                                {{ t('documents.manualTitle') }}
                            </span>
                            <div class="flex items-center gap-1 bg-slate-100 dark:bg-zinc-900 p-1 rounded-xl text-[10px] font-bold">
                                <button
                                    type="button"
                                    @click="isRangeMode = false"
                                    class="px-2 py-1 rounded-lg transition-colors cursor-pointer"
                                    :class="!isRangeMode ? 'bg-main text-slate-950' : 'text-slate-500 dark:text-zinc-400'"
                                >
                                    {{ t('documents.singleYearTab') }}
                                </button>
                                <button
                                    type="button"
                                    @click="isRangeMode = true"
                                    class="px-2 py-1 rounded-lg transition-colors cursor-pointer"
                                    :class="isRangeMode ? 'bg-main text-slate-950' : 'text-slate-500 dark:text-zinc-400'"
                                >
                                    {{ t('documents.rangeTab') }}
                                </button>
                            </div>
                        </h3>

                        <form @submit.prevent="submitManualTaxHistory" class="space-y-4">
                            <!-- Range Mode -->
                            <template v-if="isRangeMode">
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="grid gap-1.5">
                                        <Label for="from_year" class="text-xs">{{ t('documents.fromYear') }}</Label>
                                        <YearPicker id="from_year" v-model="manualTaxForm.from_year" :min-year="1950" :max-year="2099" />
                                    </div>
                                    <div class="grid gap-1.5">
                                        <Label for="to_year" class="text-xs">{{ t('documents.toYear') }}</Label>
                                        <YearPicker id="to_year" v-model="manualTaxForm.to_year" :min-year="1950" :max-year="2099" />
                                    </div>
                                </div>
                                <p class="text-[10px] text-slate-400">
                                    {{ t('documents.rangeNotice') }}
                                </p>
                            </template>

                            <!-- Single Year Mode -->
                            <template v-else>
                                <div class="grid gap-1.5">
                                    <Label for="single_year" class="text-xs">{{ t('documents.singleYear') }}</Label>
                                    <YearPicker id="single_year" v-model="manualTaxForm.year" :min-year="1950" :max-year="2099" />
                                </div>
                            </template>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="grid gap-1.5">
                                    <Label for="monthly_salary" class="text-xs">{{ t('documents.monthlySalary') }}</Label>
                                    <Input id="monthly_salary" type="number" step="100" v-model="manualTaxForm.monthly_salary" required />
                                </div>
                                <div class="grid gap-1.5">
                                    <Label for="months_worked" class="text-xs">{{ t('documents.monthsInYear') }}</Label>
                                    <Input id="months_worked" type="number" min="1" max="12" v-model="manualTaxForm.months_worked" required />
                                </div>
                            </div>

                            <Button
                                type="submit"
                                class="w-full bg-main text-slate-950 hover:bg-main-dark font-bold shadow-sm h-10 cursor-pointer"
                                :disabled="manualTaxForm.processing"
                            >
                                <Plus class="mr-2 h-4 w-4" />
                                {{ manualTaxForm.processing ? t('documents.savingBtn') : t('documents.addRecordBtn') }}
                            </Button>
                        </form>
                    </div>
                </div>

                <!-- Right: Service History & Uploaded Document Tables -->
                <div class="lg:col-span-7 space-y-6">
                    <!-- Insurance Service / Tax History Table -->
                    <div class="rounded-2xl border border-slate-200/80 bg-white/70 p-6 shadow-sm backdrop-blur-md dark:border-zinc-800/80 dark:bg-zinc-950/80 space-y-4">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center justify-between border-b border-slate-100 dark:border-zinc-800/60 pb-3">
                            <span class="flex items-center gap-2">
                                <Layers class="h-4 w-4 text-main" />
                                {{ t('documents.historyTitle') }}
                            </span>
                            <span class="text-xs font-bold text-main-dark dark:text-main">
                                {{ t('documents.totalYearsCount') }} {{ taxHistoriesList.length }}
                            </span>
                        </h3>

                        <template v-if="taxHistoriesList.length > 0">
                            <div class="overflow-x-auto max-h-64 overflow-y-auto">
                                <table class="w-full text-left text-xs">
                                    <thead>
                                        <tr class="border-b border-slate-200 dark:border-zinc-800 text-slate-400 font-semibold">
                                            <th class="pb-2">{{ t('documents.colYear') }}</th>
                                            <th class="pb-2 text-right">{{ t('documents.colMonthlySalary') }}</th>
                                            <th class="pb-2 text-right">{{ t('documents.colMonths') }}</th>
                                            <th class="pb-2 text-right">{{ t('documents.colAnnualIncome') }}</th>
                                            <th class="pb-2 text-right">{{ t('documents.colAction') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-zinc-900">
                                        <tr v-for="item in taxHistoriesList" :key="item.id" class="hover:bg-slate-50/50 dark:hover:bg-zinc-900/50">
                                            <td class="py-2.5 font-bold text-slate-900 dark:text-white">{{ item.year }}</td>
                                            <td class="py-2.5 text-right font-mono text-slate-700 dark:text-zinc-300">
                                                {{ Number(item.annual_income / (item.months_worked || 12)).toLocaleString('uk-UA', { minimumFractionDigits: 0 }) }} ₴
                                            </td>
                                            <td class="py-2.5 text-right font-bold text-slate-600 dark:text-zinc-400">
                                                {{ item.months_worked || 12 }}
                                            </td>
                                            <td class="py-2.5 text-right font-bold text-main-dark dark:text-main">
                                                {{ Number(item.annual_income).toLocaleString('uk-UA') }} ₴
                                            </td>
                                            <td class="py-2.5 text-right">
                                                <div class="flex items-center justify-end gap-1">
                                                    <button
                                                        @click="openEditTaxModal(item)"
                                                        type="button"
                                                        class="text-slate-400 hover:text-main cursor-pointer p-1"
                                                        :title="t('documents.editMonthlySalary')"
                                                    >
                                                        <Pencil class="h-3.5 w-3.5" />
                                                    </button>
                                                    <button
                                                        @click="deleteTaxHistory(item.id)"
                                                        type="button"
                                                        class="text-slate-400 hover:text-red-500 cursor-pointer p-1"
                                                    >
                                                        <Trash2 class="h-3.5 w-3.5" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </template>
                        <template v-else>
                            <div class="p-6 text-center text-xs text-slate-400">
                                {{ t('documents.emptyHistory') }}
                            </div>
                        </template>
                    </div>

                    <!-- Document Files List -->
                    <div class="rounded-2xl border border-slate-200/80 bg-white/70 p-6 shadow-sm backdrop-blur-md dark:border-zinc-800/80 dark:bg-zinc-950/80 space-y-4">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center justify-between border-b border-slate-100 dark:border-zinc-800/60 pb-3">
                            <span class="flex items-center gap-2">
                                <FileText class="h-4 w-4 text-main" />
                                {{ t('documents.scansTitle') }}
                            </span>
                            <span class="text-xs text-slate-400 font-normal">{{ t('documents.filesCount') }} {{ documentsList.length }}</span>
                        </h3>

                        <template v-if="documentsList.length > 0">
                            <div class="space-y-3 max-h-56 overflow-y-auto pr-1">
                                <div
                                    v-for="doc in documentsList"
                                    :key="doc.id"
                                    class="flex items-center justify-between p-3.5 rounded-xl border border-slate-100 hover:border-main/40 dark:border-zinc-900 dark:hover:border-main/30 bg-slate-50/50 dark:bg-zinc-900/50 transition-colors"
                                >
                                    <div class="flex items-center gap-3 min-w-0">
                                        <FileText class="h-7 w-7 text-main shrink-0" />
                                        <div class="min-w-0">
                                            <h4 class="text-xs font-bold text-slate-900 dark:text-white truncate">
                                                {{ doc.original_filename || `Doc #${doc.id}` }}
                                            </h4>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold"
                                                    :class="[
                                                        isDocCompleted(doc)
                                                            ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400'
                                                            : isDocFailed(doc)
                                                            ? 'bg-red-500/15 text-red-600 dark:text-red-400'
                                                            : 'bg-amber-500/15 text-amber-600 dark:text-amber-400 animate-pulse'
                                                    ]"
                                                >
                                                    <CheckCircle2 v-if="isDocCompleted(doc)" class="h-3 w-3" />
                                                    <AlertCircle v-else-if="isDocFailed(doc)" class="h-3 w-3" />
                                                    <RefreshCw v-else class="h-3 w-3 animate-spin" />
                                                    {{ isDocCompleted(doc) ? t('documents.processed') : isDocFailed(doc) ? t('documents.failed') : t('documents.pending') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-1 shrink-0">
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            class="h-7 w-7 hover:bg-main/20 cursor-pointer"
                                            @click="openReviewModal(doc)"
                                            title="Review Document"
                                        >
                                            <Eye class="h-3.5 w-3.5 text-slate-500 hover:text-main" />
                                        </Button>
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            class="h-7 w-7 hover:text-red-500 cursor-pointer"
                                            @click="deleteDocument(doc.id)"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template v-else>
                            <div class="p-4 text-center text-xs text-slate-400">
                                {{ t('documents.emptyFiles') }}
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Document Review Modal -->
            <div v-if="isReviewModalOpen && selectedDoc" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/75 p-4 backdrop-blur-sm">
                <div class="relative w-full max-w-4xl max-h-[90vh] flex flex-col rounded-3xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 shadow-2xl overflow-hidden">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-zinc-900 px-6 py-4 bg-slate-50/50 dark:bg-zinc-900/50">
                        <div class="flex items-center gap-3">
                            <FileText class="h-6 w-6 text-main" />
                            <div>
                                <h3 class="text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                                    {{ selectedDoc.original_filename || `Document #${selectedDoc.id}` }}
                                    <span
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold"
                                        :class="[
                                            isDocCompleted(selectedDoc)
                                                ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400'
                                                : isDocFailed(selectedDoc)
                                                ? 'bg-red-500/15 text-red-600 dark:text-red-400'
                                                : 'bg-amber-500/15 text-amber-600 dark:text-amber-400 animate-pulse'
                                        ]"
                                    >
                                        {{ isDocCompleted(selectedDoc) ? t('documents.processed') : isDocFailed(selectedDoc) ? t('documents.failed') : t('documents.pending') }}
                                    </span>
                                </h3>
                                <p class="text-[11px] text-slate-400">
                                    Type: {{ selectedDoc.document_type || 'auto' }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <a
                                :href="getFileStreamUrl(selectedDoc.id)"
                                target="_blank"
                                download
                                class="inline-flex items-center gap-1.5 rounded-xl bg-slate-100 dark:bg-zinc-800 px-3 py-1.5 text-xs font-bold text-slate-700 dark:text-zinc-300 hover:bg-main hover:text-slate-950 transition-colors"
                            >
                                <Download class="h-3.5 w-3.5" />
                                Download
                            </a>
                            <button
                                @click="closeReviewModal"
                                type="button"
                                class="rounded-full p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-zinc-800 dark:hover:text-white cursor-pointer"
                            >
                                <X class="h-5 w-5" />
                            </button>
                        </div>
                    </div>

                    <!-- Modal Content -->
                    <div class="flex-1 overflow-y-auto p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Embedded File Preview Panel -->
                            <div class="rounded-2xl border border-slate-200 dark:border-zinc-800 bg-slate-50 dark:bg-zinc-900 p-2 flex flex-col items-center justify-center min-h-[350px]">
                                <template v-if="isPdfFile(selectedDoc.original_filename)">
                                    <iframe
                                        :src="getFileStreamUrl(selectedDoc.id)"
                                        class="w-full h-[400px] rounded-xl border-0"
                                    ></iframe>
                                </template>
                                <template v-else>
                                    <img
                                        :src="getFileStreamUrl(selectedDoc.id)"
                                        :alt="selectedDoc.original_filename"
                                        class="max-h-[400px] w-auto max-w-full object-contain rounded-xl shadow-sm"
                                    />
                                </template>
                            </div>

                            <!-- Extracted OCR & Document Meta Panel -->
                            <div class="space-y-4 flex flex-col justify-between">
                                <div class="space-y-4">
                                    <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-400 border-b border-slate-100 dark:border-zinc-800 pb-2">
                                        OCR Recognition Result
                                    </h4>

                                    <div v-if="getRawText(selectedDoc)" class="rounded-xl bg-slate-900 p-3 text-[11px] font-mono text-emerald-400 max-h-48 overflow-y-auto border border-zinc-800 whitespace-pre-wrap">
                                        {{ getRawText(selectedDoc) }}
                                    </div>
                                    <div v-else class="p-4 text-center text-xs text-slate-400 rounded-xl bg-slate-50 dark:bg-zinc-900">
                                        {{ isDocPending(selectedDoc) ? 'OCR Processing in background...' : 'No raw text extracted.' }}
                                    </div>

                                    <div v-if="getExtractedData(selectedDoc)" class="space-y-2">
                                        <h5 class="text-xs font-bold text-slate-900 dark:text-white">Extracted Metadata:</h5>
                                        <pre class="rounded-xl bg-slate-100 dark:bg-zinc-900 p-3 text-[10px] font-mono text-slate-700 dark:text-zinc-300 max-h-36 overflow-y-auto border border-slate-200 dark:border-zinc-800">{{ JSON.stringify(getExtractedData(selectedDoc), null, 2) }}</pre>
                                    </div>
                                </div>

                                <div class="pt-4 border-t border-slate-100 dark:border-zinc-900 flex justify-end gap-2">
                                    <Button variant="outline" size="sm" @click="closeReviewModal" class="cursor-pointer">
                                        Close
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Monthly Salary Breakdown Modal -->
        <Dialog :open="isEditTaxModalOpen" @update:open="isEditTaxModalOpen = $event">
            <DialogContent class="sm:max-w-2xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-lg font-bold">
                        <Pencil class="h-5 w-5 text-main" />
                        {{ t('documents.editSalaryTitle').replace(':year', String(editingTaxHistory?.year || '')) }}
                    </DialogTitle>
                    <DialogDescription class="text-xs text-slate-500">
                        {{ t('documents.editSalarySubtitle') }}
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitEditTaxHistory" class="space-y-6 pt-4">
                    <!-- 12 Months Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-h-[50vh] overflow-y-auto pr-2">
                        <div v-for="m in 12" :key="m" class="flex items-center justify-between gap-3 p-2.5 rounded-xl border border-slate-200/80 dark:border-zinc-800/80 bg-slate-50/50 dark:bg-zinc-900/50">
                            <span class="text-xs font-semibold text-slate-700 dark:text-zinc-300 w-28 shrink-0 flex items-center gap-1.5">
                                <span class="w-5 h-5 rounded-full bg-main/20 text-main font-bold text-[10px] flex items-center justify-center">{{ m }}</span>
                                {{ monthNames[m - 1] }}
                            </span>
                            <div class="relative flex-1">
                                <Input
                                    v-model.number="monthlyBreakdownForm[m]"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="text-right text-xs font-mono pr-7 h-9"
                                    placeholder="0.00"
                                />
                                <span class="absolute right-2.5 top-2.5 text-xs text-slate-400 font-bold pointer-events-none">₴</span>
                            </div>
                        </div>
                    </div>

                    <!-- Live Total Indicator -->
                    <div class="flex items-center justify-between p-4 rounded-xl bg-main/10 border border-main/30 dark:bg-main/15">
                        <span class="text-xs font-bold text-slate-900 dark:text-white">
                            {{ t('documents.totalAnnualIncome') }}
                        </span>
                        <span class="text-lg font-extrabold text-main-dark dark:text-main font-mono">
                            {{ Number(calculatedAnnualSum).toLocaleString('uk-UA', { minimumFractionDigits: 2 }) }} ₴
                        </span>
                    </div>

                    <!-- Action Buttons -->
                    <DialogFooter class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100 dark:border-zinc-800">
                        <Button
                            type="button"
                            variant="outline"
                            @click="isEditTaxModalOpen = false"
                            :disabled="isSubmittingEditTax"
                            class="h-10 text-xs font-bold cursor-pointer"
                        >
                            {{ t('documents.cancelBtn') }}
                        </Button>

                        <Button
                            type="submit"
                            :disabled="isSubmittingEditTax"
                            class="h-10 text-xs font-bold bg-main text-slate-950 hover:bg-main-dark cursor-pointer"
                        >
                            {{ isSubmittingEditTax ? t('documents.savingBtn') : t('documents.saveBtn') }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
