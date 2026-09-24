<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import {
    FileText,
    UploadCloud,
    Trash2,
    Pencil,
    CheckCircle2,
    AlertCircle,
    Eye,
    Calendar,
    Plus,
    Layers,
    X,
    Download,
    RefreshCw,
    FileX,
} from '@lucide/vue';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Skeleton } from '@/components/ui/skeleton';
import YearPicker from '@/components/ui/YearPicker.vue';
import { useI18n } from '@/composables/useI18n';

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

watch(
    () => props.initialDocuments,
    (newDocs) => {
        if (newDocs) {
            documentsList.value = newDocs;
        }
    },
    { deep: true },
);

watch(
    () => props.initialTaxHistories,
    (newTaxes) => {
        if (newTaxes) {
            taxHistoriesList.value = newTaxes;
        }
    },
    { deep: true },
);

// Status helper utilities
function getDocStatus(doc: DocumentItem): string {
    return (
        doc.status ||
        doc.ocr_status ||
        doc.recognized_document?.status ||
        'pending'
    ).toLowerCase();
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

const passSalaryPre2000 = ref(false);

const manualTaxForm = useForm({
    is_range: true,
    year: currentYear,
    from_year: 2020,
    to_year: currentYear,
    monthly_salary: 15000,
    months_worked: 12,
});

const isPre2000Selected = computed(() => {
    if (isRangeMode.value) {
        return Boolean(manualTaxForm.to_year && manualTaxForm.to_year < 2000);
    }

    return Boolean(manualTaxForm.year && manualTaxForm.year < 2000);
});

watch(
    () => manualTaxForm.from_year,
    (newFrom) => {
        if (newFrom >= currentYear) {
            manualTaxForm.from_year = currentYear - 1;
        }

        if (manualTaxForm.to_year <= manualTaxForm.from_year) {
            manualTaxForm.to_year = Math.min(
                currentYear,
                manualTaxForm.from_year + 1,
            );
        }
    },
);

watch(
    () => manualTaxForm.to_year,
    (newTo) => {
        if (newTo > currentYear) {
            manualTaxForm.to_year = currentYear;
        }

        if (manualTaxForm.from_year >= manualTaxForm.to_year) {
            manualTaxForm.from_year = Math.max(1950, manualTaxForm.to_year - 1);
        }
    },
);

watch(
    () => manualTaxForm.year,
    (newYr) => {
        if (newYr > currentYear) {
            manualTaxForm.year = currentYear;
        }
    },
);

const totalWorkedYearsCount = computed(() => taxHistoriesList.value.length);
const totalAccumulatedMonthsCount = computed(() => {
    return taxHistoriesList.value.reduce(
        (acc, curr) => acc + (curr.months_worked || 12),
        0,
    );
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
                Accept: 'application/json',
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
                const updated = documentsList.value.find(
                    (d) => d.id === selectedDoc.value?.id,
                );

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
    if (!uploadForm.file) {
        return;
    }

    uploadForm.post('/documents/upload', {
        preserveScroll: true,
        onSuccess: (pageRes) => {
            if (pageRes.props.initialDocuments) {
                documentsList.value = pageRes.props
                    .initialDocuments as DocumentItem[];
            }

            if (pageRes.props.initialTaxHistories) {
                taxHistoriesList.value = pageRes.props
                    .initialTaxHistories as TaxHistoryItem[];
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

    if (isPre2000Selected.value && !passSalaryPre2000.value) {
        manualTaxForm.monthly_salary = 0;
    }

    manualTaxForm.post('/documents/tax-histories', {
        preserveScroll: true,
        onSuccess: (res: any) => {
            window.dispatchEvent(new CustomEvent('notification-created'));

            if (res.props?.initialTaxHistories) {
                taxHistoriesList.value = res.props
                    .initialTaxHistories as TaxHistoryItem[];
            } else {
                pollDocumentUpdates();
            }
        },
    });
}

const isConfirmDeleteModalOpen = ref(false);
const deleteTarget = ref<{
    type: 'document' | 'taxHistory';
    id: number;
    title?: string;
} | null>(null);
const isDeletingRecord = ref(false);

function confirmDeleteDocument(doc: DocumentItem) {
    deleteTarget.value = {
        type: 'document',
        id: doc.id,
        title: doc.original_filename || `Doc #${doc.id}`,
    };
    isConfirmDeleteModalOpen.value = true;
}

function confirmDeleteTaxHistory(tax: TaxHistoryItem) {
    deleteTarget.value = {
        type: 'taxHistory',
        id: tax.id,
        title: `${tax.year} ${t('documents.colYear')}`,
    };
    isConfirmDeleteModalOpen.value = true;
}

function executeDeleteRecord() {
    if (!deleteTarget.value) {
        return;
    }

    isDeletingRecord.value = true;

    if (deleteTarget.value.type === 'document') {
        const id = deleteTarget.value.id;
        useForm({}).delete(`/documents/${id}`, {
            preserveScroll: true,
            onSuccess: () => {
                documentsList.value = documentsList.value.filter(
                    (d) => d.id !== id,
                );

                if (selectedDoc.value?.id === id) {
                    selectedDoc.value = null;
                    isReviewModalOpen.value = false;
                }

                isConfirmDeleteModalOpen.value = false;
                isDeletingRecord.value = false;
                deleteTarget.value = null;
            },
            onError: () => {
                isDeletingRecord.value = false;
            },
        });
    } else {
        const id = deleteTarget.value.id;
        useForm({}).delete(`/documents/tax-histories/${id}`, {
            preserveScroll: true,
            onSuccess: () => {
                taxHistoriesList.value = taxHistoriesList.value.filter(
                    (tItem) => tItem.id !== id,
                );
                isConfirmDeleteModalOpen.value = false;
                isDeletingRecord.value = false;
                deleteTarget.value = null;
            },
            onError: () => {
                isDeletingRecord.value = false;
            },
        });
    }
}

const { locale } = useI18n();

const ukMonthNames = [
    'Січень',
    'Лютий',
    'Березень',
    'Квітень',
    'Травень',
    'Червень',
    'Липень',
    'Серпень',
    'Вересень',
    'Жовтень',
    'Листопад',
    'Грудень',
];

const enMonthNames = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December',
];

const monthNames = computed(() =>
    locale.value === 'uk' ? ukMonthNames : enMonthNames,
);

const isEditTaxModalOpen = ref(false);
const editingTaxHistory = ref<any | null>(null);
const editPassSalaryPre2000 = ref(false);
const monthlyBreakdownForm = ref<{ [key: number]: number }>({
    1: 0,
    2: 0,
    3: 0,
    4: 0,
    5: 0,
    6: 0,
    7: 0,
    8: 0,
    9: 0,
    10: 0,
    11: 0,
    12: 0,
});
const isSubmittingEditTax = ref(false);

function openEditTaxModal(item: any) {
    editingTaxHistory.value = item;
    const monthsWorked = item.months_worked || 12;
    const avgMonthly = item.annual_income / monthsWorked;
    const breakdown = item.monthly_breakdown || {};

    editPassSalaryPre2000.value =
        item.year < 2000 ? item.annual_income > 0 : true;

    const initial: { [key: number]: number } = {};

    for (let m = 1; m <= 12; m++) {
        const rawVal =
            breakdown[m] ??
            breakdown[String(m)] ??
            (m <= monthsWorked ? avgMonthly : 0);
        initial[m] = Math.round(Number(rawVal) * 100) / 100;
    }

    monthlyBreakdownForm.value = initial;
    isEditTaxModalOpen.value = true;
}

watch(editPassSalaryPre2000, (newVal) => {
    if (
        editingTaxHistory.value &&
        editingTaxHistory.value.year < 2000 &&
        !newVal
    ) {
        for (let m = 1; m <= 12; m++) {
            monthlyBreakdownForm.value[m] = 0;
        }
    }
});

const calculatedAnnualSum = computed(() => {
    return Object.values(monthlyBreakdownForm.value).reduce(
        (sum, val) => sum + (Number(val) || 0),
        0,
    );
});

function submitEditTaxHistory() {
    if (!editingTaxHistory.value) {
        return;
    }

    if (editingTaxHistory.value.year < 2000 && !editPassSalaryPre2000.value) {
        for (let m = 1; m <= 12; m++) {
            monthlyBreakdownForm.value[m] = 0;
        }
    }

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

const isCheckingFile = ref(false);
const isImageError = ref(false);

async function openReviewModal(doc: DocumentItem) {
    selectedDoc.value = doc;
    isImageError.value = false;
    isCheckingFile.value = true;
    isReviewModalOpen.value = true;

    try {
        const res = await fetch(getFileStreamUrl(doc.id), { method: 'HEAD' });

        if (!res.ok) {
            isImageError.value = true;
        }
    } catch {
        isImageError.value = true;
    } finally {
        isCheckingFile.value = false;
    }
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
    return (
        doc.extracted_data || doc.recognized_document?.extracted_data || null
    );
}
</script>

<template>
    <div>
        <!-- Skeleton Loading View -->
        <div v-if="isSectionLoading" class="animate-pulse space-y-8">
            <div
                class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between"
            >
                <div class="space-y-2">
                    <Skeleton class="h-8 w-64 rounded-xl" />
                    <Skeleton class="h-4 w-96 rounded-lg" />
                </div>
                <Skeleton class="h-8 w-48 rounded-full" />
            </div>

            <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-12">
                <div class="h-fit space-y-6 self-start lg:col-span-5">
                    <div
                        class="space-y-4 rounded-2xl border border-slate-200/80 bg-white/70 p-6 dark:border-zinc-800/80 dark:bg-zinc-950/80"
                    >
                        <Skeleton class="h-5 w-40 rounded-lg" />
                        <Skeleton class="h-36 w-full rounded-2xl" />
                    </div>

                    <div
                        class="space-y-4 rounded-2xl border border-slate-200/80 bg-white/70 p-6 dark:border-zinc-800/80 dark:bg-zinc-950/80"
                    >
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

                <div class="space-y-6 lg:col-span-7">
                    <div
                        class="space-y-4 rounded-2xl border border-slate-200/80 bg-white/70 p-6 dark:border-zinc-800/80 dark:bg-zinc-950/80"
                    >
                        <div
                            class="flex items-center justify-between border-b border-slate-100 pb-3 dark:border-zinc-800/60"
                        >
                            <Skeleton class="h-5 w-44 rounded-lg" />
                            <Skeleton class="h-4 w-24 rounded-lg" />
                        </div>
                        <div class="space-y-3">
                            <Skeleton
                                v-for="i in 4"
                                :key="i"
                                class="h-10 w-full rounded-xl"
                            />
                        </div>
                    </div>

                    <div
                        class="space-y-4 rounded-2xl border border-slate-200/80 bg-white/70 p-6 dark:border-zinc-800/80 dark:bg-zinc-950/80"
                    >
                        <div
                            class="flex items-center justify-between border-b border-slate-100 pb-3 dark:border-zinc-800/60"
                        >
                            <Skeleton class="h-5 w-40 rounded-lg" />
                            <Skeleton class="h-4 w-16 rounded-lg" />
                        </div>
                        <div class="space-y-3">
                            <Skeleton
                                v-for="i in 3"
                                :key="i"
                                class="h-14 w-full rounded-xl"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Real Content View -->
        <div v-else class="space-y-8">
            <!-- Header -->
            <div
                class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between"
            >
                <div>
                    <h2
                        class="flex items-center gap-2 text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white"
                    >
                        <FileText class="h-6 w-6 text-main" />
                        {{ t('documents.title') }}
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-zinc-400">
                        {{ t('documents.subtitle') }}
                    </p>
                </div>
                <span
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-main/15 px-3 py-1 text-xs font-bold text-main-dark dark:text-main"
                >
                    <Calendar class="h-3.5 w-3.5" />
                    {{ t('documents.totalService') }}
                    {{ totalWorkedYearsCount }} {{ t('documents.yrs') }} ({{
                        totalAccumulatedMonthsCount
                    }}
                    {{ t('documents.months') }})
                </span>
            </div>

            <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-12">
                <!-- Left: Document Upload Dropzone & Manual Service Entry -->
                <div class="h-fit space-y-6 self-start lg:col-span-5">
                    <!-- Dropzone Section -->
                    <div
                        class="space-y-4 rounded-2xl border border-slate-200/80 bg-white/70 p-6 shadow-sm backdrop-blur-md dark:border-zinc-800/80 dark:bg-zinc-950/80"
                    >
                        <h3
                            class="flex items-center justify-between border-b border-slate-100 pb-3 text-base font-bold text-slate-900 dark:border-zinc-800/60 dark:text-white"
                        >
                            <span class="flex items-center gap-2">
                                <UploadCloud class="h-4 w-4 text-main" />
                                {{ t('documents.uploadTitle') }}
                            </span>
                            <span
                                v-if="uploadForm.processing"
                                class="flex items-center gap-1 text-xs font-bold text-main"
                            >
                                <RefreshCw class="h-3 w-3 animate-spin" />
                                {{ uploadForm.progress?.percentage ?? 0 }}%
                            </span>
                        </h3>

                        <label
                            class="relative flex cursor-pointer flex-col items-center justify-center overflow-hidden rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50/50 p-6 text-center transition-colors hover:border-main dark:border-zinc-800 dark:bg-zinc-900/50 dark:hover:border-main/60"
                        >
                            <UploadCloud class="mb-2 h-8 w-8 text-main" />
                            <span
                                class="text-xs font-bold text-slate-900 dark:text-white"
                            >
                                {{ t('documents.dropzoneText') }}
                            </span>
                            <span class="mt-0.5 text-[10px] text-slate-400">
                                {{ t('documents.supportedFormats') }}
                            </span>

                            <!-- Browser upload progress line under the link/dropzone -->
                            <div
                                v-if="uploadForm.processing"
                                class="absolute right-0 bottom-0 left-0 h-1.5 bg-slate-200 dark:bg-zinc-800"
                            >
                                <div
                                    class="h-full bg-main transition-all duration-300 ease-out"
                                    :style="{
                                        width: `${uploadForm.progress?.percentage ?? 0}%`,
                                    }"
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
                        <div
                            v-if="uploadForm.processing"
                            class="space-y-1.5 pt-1"
                        >
                            <div class="flex justify-between text-xs font-bold">
                                <span
                                    class="flex items-center gap-1 text-slate-700 dark:text-zinc-300"
                                >
                                    <RefreshCw
                                        class="h-3 w-3 animate-spin text-main"
                                    />
                                    {{ t('documents.uploadingText') }}
                                </span>
                                <span class="font-mono text-main"
                                    >{{
                                        uploadForm.progress?.percentage ?? 0
                                    }}%</span
                                >
                            </div>
                            <div
                                class="h-2.5 w-full overflow-hidden rounded-full border border-slate-300/50 bg-slate-200 p-0.5 dark:border-zinc-700/50 dark:bg-zinc-800"
                            >
                                <div
                                    class="h-full rounded-full bg-gradient-to-r from-main-dark to-main shadow-sm transition-all duration-300"
                                    :style="{
                                        width: `${uploadForm.progress?.percentage ?? 0}%`,
                                    }"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <!-- Dashed Line Separator with OR / АБО Tag -->
                    <div class="relative flex items-center justify-center py-2">
                        <div class="absolute inset-0 flex items-center">
                            <div
                                class="w-full border-t-2 border-dashed border-slate-300/80 dark:border-zinc-800/80"
                            ></div>
                        </div>
                        <div
                            class="relative rounded-full border border-slate-200 bg-slate-100 px-4 py-1 text-xs font-black tracking-wider text-slate-500 uppercase shadow-sm dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400"
                        >
                            {{ t('documents.orSeparator') }}
                        </div>
                    </div>

                    <!-- Manual Salary & Insurance Service Entry Section -->
                    <div
                        class="space-y-4 rounded-2xl border border-slate-200/80 bg-white/70 p-6 shadow-sm backdrop-blur-md dark:border-zinc-800/80 dark:bg-zinc-950/80"
                    >
                        <h3
                            class="flex items-center justify-between border-b border-slate-100 pb-3 text-base font-bold text-slate-900 dark:border-zinc-800/60 dark:text-white"
                        >
                            <span class="flex items-center gap-2">
                                <Plus class="h-4 w-4 text-main" />
                                {{ t('documents.manualTitle') }}
                            </span>
                            <div
                                class="flex items-center gap-1 rounded-xl bg-slate-100 p-1 text-[10px] font-bold dark:bg-zinc-900"
                            >
                                <button
                                    type="button"
                                    @click="isRangeMode = false"
                                    class="cursor-pointer rounded-lg px-2 py-1 transition-colors"
                                    :class="
                                        !isRangeMode
                                            ? 'bg-main text-slate-950'
                                            : 'text-slate-500 dark:text-zinc-400'
                                    "
                                >
                                    {{ t('documents.singleYearTab') }}
                                </button>
                                <button
                                    type="button"
                                    @click="isRangeMode = true"
                                    class="cursor-pointer rounded-lg px-2 py-1 transition-colors"
                                    :class="
                                        isRangeMode
                                            ? 'bg-main text-slate-950'
                                            : 'text-slate-500 dark:text-zinc-400'
                                    "
                                >
                                    {{ t('documents.rangeTab') }}
                                </button>
                            </div>
                        </h3>

                        <form
                            @submit.prevent="submitManualTaxHistory"
                            class="space-y-4"
                        >
                            <!-- Range Mode -->
                            <template v-if="isRangeMode">
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="grid gap-1.5">
                                        <Label
                                            for="from_year"
                                            class="text-xs"
                                            >{{
                                                t('documents.fromYear')
                                            }}</Label
                                        >
                                        <YearPicker
                                            id="from_year"
                                            v-model="manualTaxForm.from_year"
                                            :min-year="1950"
                                            :max-year="currentYear - 1"
                                        />
                                    </div>
                                    <div class="grid gap-1.5">
                                        <Label for="to_year" class="text-xs">{{
                                            t('documents.toYear')
                                        }}</Label>
                                        <YearPicker
                                            id="to_year"
                                            v-model="manualTaxForm.to_year"
                                            :min-year="
                                                manualTaxForm.from_year
                                                    ? manualTaxForm.from_year +
                                                      1
                                                    : 1951
                                            "
                                            :max-year="currentYear"
                                        />
                                    </div>
                                </div>
                                <p class="text-[10px] text-slate-400">
                                    {{ t('documents.rangeNotice') }}
                                </p>
                            </template>

                            <!-- Single Year Mode -->
                            <template v-else>
                                <div class="grid gap-1.5">
                                    <Label for="single_year" class="text-xs">{{
                                        t('documents.singleYear')
                                    }}</Label>
                                    <YearPicker
                                        id="single_year"
                                        v-model="manualTaxForm.year"
                                        :min-year="1950"
                                        :max-year="currentYear"
                                    />
                                </div>
                            </template>

                            <!-- Pre-2000 Salary Pass Switch/Toggle -->
                            <div
                                v-if="isPre2000Selected"
                                class="space-y-2 rounded-xl border border-amber-200/80 bg-amber-50/60 p-3.5 dark:border-amber-900/40 dark:bg-amber-950/30"
                            >
                                <div
                                    class="flex items-center justify-between gap-3"
                                >
                                    <Label
                                        for="pass_salary_pre_2000"
                                        class="cursor-pointer text-xs font-bold text-slate-800 dark:text-zinc-200"
                                    >
                                        {{
                                            t(
                                                'documents.includePre2000SalaryLabel',
                                            )
                                        }}
                                    </Label>
                                    <button
                                        type="button"
                                        id="pass_salary_pre_2000"
                                        @click="
                                            passSalaryPre2000 =
                                                !passSalaryPre2000
                                        "
                                        class="relative inline-flex h-5 w-10 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                        :class="
                                            passSalaryPre2000
                                                ? 'bg-main'
                                                : 'bg-slate-300 dark:bg-zinc-700'
                                        "
                                    >
                                        <span
                                            class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
                                            :class="
                                                passSalaryPre2000
                                                    ? 'translate-x-5'
                                                    : 'translate-x-0'
                                            "
                                        ></span>
                                    </button>
                                </div>
                                <p
                                    class="text-[11px] leading-relaxed text-amber-800 dark:text-amber-300/90"
                                >
                                    {{
                                        passSalaryPre2000
                                            ? t(
                                                  'documents.pre2000SalaryNoticeWith',
                                              )
                                            : t(
                                                  'documents.pre2000SalaryNoticeWithout',
                                              )
                                    }}
                                </p>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div
                                    v-if="
                                        !isPre2000Selected || passSalaryPre2000
                                    "
                                    class="grid gap-1.5"
                                >
                                    <Label
                                        for="monthly_salary"
                                        class="text-xs"
                                        >{{
                                            t('documents.monthlySalary')
                                        }}</Label
                                    >
                                    <Input
                                        id="monthly_salary"
                                        type="number"
                                        step="100"
                                        min="0"
                                        v-model="manualTaxForm.monthly_salary"
                                        required
                                    />
                                </div>
                                <div
                                    :class="
                                        !isPre2000Selected || passSalaryPre2000
                                            ? 'grid gap-1.5'
                                            : 'col-span-2 grid gap-1.5'
                                    "
                                >
                                    <Label
                                        for="months_worked"
                                        class="text-xs"
                                        >{{
                                            t('documents.monthsInYear')
                                        }}</Label
                                    >
                                    <Input
                                        id="months_worked"
                                        type="number"
                                        min="1"
                                        max="12"
                                        v-model="manualTaxForm.months_worked"
                                        required
                                    />
                                </div>
                            </div>

                            <Button
                                type="submit"
                                class="h-10 w-full cursor-pointer bg-main font-bold text-slate-950 shadow-sm hover:bg-main-dark"
                                :disabled="manualTaxForm.processing"
                            >
                                <Plus class="mr-2 h-4 w-4" />
                                {{
                                    manualTaxForm.processing
                                        ? t('documents.savingBtn')
                                        : t('documents.addRecordBtn')
                                }}
                            </Button>
                        </form>
                    </div>
                </div>

                <!-- Right: Service History & Uploaded Document Tables -->
                <div class="space-y-6 lg:col-span-7">
                    <!-- Insurance Service / Tax History Table -->
                    <div
                        class="space-y-4 rounded-2xl border border-slate-200/80 bg-white/70 p-6 shadow-sm backdrop-blur-md dark:border-zinc-800/80 dark:bg-zinc-950/80"
                    >
                        <h3
                            class="flex items-center justify-between border-b border-slate-100 pb-3 text-base font-bold text-slate-900 dark:border-zinc-800/60 dark:text-white"
                        >
                            <span class="flex items-center gap-2">
                                <Layers class="h-4 w-4 text-main" />
                                {{ t('documents.historyTitle') }}
                            </span>
                            <span
                                class="text-xs font-bold text-main-dark dark:text-main"
                            >
                                {{ t('documents.totalYearsCount') }}
                                {{ taxHistoriesList.length }}
                            </span>
                        </h3>

                        <template v-if="taxHistoriesList.length > 0">
                            <div
                                class="max-h-64 overflow-x-auto overflow-y-auto [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-slate-300 dark:[&::-webkit-scrollbar-thumb]:bg-zinc-800 [&::-webkit-scrollbar-track]:bg-transparent"
                            >
                                <table class="w-full text-left text-xs">
                                    <thead>
                                        <tr
                                            class="border-b border-slate-200 font-semibold text-slate-400 dark:border-zinc-800"
                                        >
                                            <th class="pb-2">
                                                {{ t('documents.colYear') }}
                                            </th>
                                            <th class="pb-2 text-right">
                                                {{
                                                    t(
                                                        'documents.colMonthlySalary',
                                                    )
                                                }}
                                            </th>
                                            <th class="pb-2 text-right">
                                                {{ t('documents.colMonths') }}
                                            </th>
                                            <th class="pb-2 text-right">
                                                {{
                                                    t(
                                                        'documents.colAnnualIncome',
                                                    )
                                                }}
                                            </th>
                                            <th class="pb-2 text-right">
                                                {{ t('documents.colAction') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody
                                        class="divide-y divide-slate-100 dark:divide-zinc-900"
                                    >
                                        <tr
                                            v-for="item in taxHistoriesList"
                                            :key="item.id"
                                            class="hover:bg-slate-50/50 dark:hover:bg-zinc-900/50"
                                        >
                                            <td
                                                class="py-2.5 font-bold text-slate-900 dark:text-white"
                                            >
                                                {{ item.year }}
                                            </td>
                                            <td
                                                class="py-2.5 text-right font-mono text-slate-700 dark:text-zinc-300"
                                            >
                                                <template
                                                    v-if="
                                                        item.annual_income > 0
                                                    "
                                                >
                                                    {{
                                                        Number(
                                                            item.annual_income /
                                                                (item.months_worked ||
                                                                    12),
                                                        ).toLocaleString(
                                                            'uk-UA',
                                                            {
                                                                minimumFractionDigits: 0,
                                                            },
                                                        )
                                                    }}
                                                    ₴
                                                </template>
                                                <template v-else>
                                                    <span
                                                        class="rounded-md border border-amber-200/60 bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700 italic dark:border-amber-900/40 dark:bg-amber-950/40 dark:text-amber-400"
                                                    >
                                                        {{
                                                            t(
                                                                'documents.noSalaryBadge',
                                                            )
                                                        }}
                                                    </span>
                                                </template>
                                            </td>
                                            <td
                                                class="py-2.5 text-right font-bold text-slate-600 dark:text-zinc-400"
                                            >
                                                {{ item.months_worked || 12 }}
                                            </td>
                                            <td
                                                class="py-2.5 text-right font-bold text-main-dark dark:text-main"
                                            >
                                                <template
                                                    v-if="
                                                        item.annual_income > 0
                                                    "
                                                >
                                                    {{
                                                        Number(
                                                            item.annual_income,
                                                        ).toLocaleString(
                                                            'uk-UA',
                                                        )
                                                    }}
                                                    ₴
                                                </template>
                                                <template v-else>
                                                    <span
                                                        class="font-normal text-slate-400"
                                                        >&mdash;</span
                                                    >
                                                </template>
                                            </td>
                                            <td class="py-2.5 text-right">
                                                <div
                                                    class="flex items-center justify-end gap-1"
                                                >
                                                    <button
                                                        @click="
                                                            openEditTaxModal(
                                                                item,
                                                            )
                                                        "
                                                        type="button"
                                                        class="cursor-pointer p-1 text-slate-400 hover:text-main"
                                                        :title="
                                                            t(
                                                                'documents.editMonthlySalary',
                                                            )
                                                        "
                                                    >
                                                        <Pencil
                                                            class="h-3.5 w-3.5"
                                                        />
                                                    </button>
                                                    <button
                                                        @click="
                                                            confirmDeleteTaxHistory(
                                                                item,
                                                            )
                                                        "
                                                        type="button"
                                                        class="cursor-pointer p-1 text-slate-400 hover:text-red-500"
                                                    >
                                                        <Trash2
                                                            class="h-3.5 w-3.5"
                                                        />
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
                    <div
                        class="space-y-4 rounded-2xl border border-slate-200/80 bg-white/70 p-6 shadow-sm backdrop-blur-md dark:border-zinc-800/80 dark:bg-zinc-950/80"
                    >
                        <h3
                            class="flex items-center justify-between border-b border-slate-100 pb-3 text-base font-bold text-slate-900 dark:border-zinc-800/60 dark:text-white"
                        >
                            <span class="flex items-center gap-2">
                                <FileText class="h-4 w-4 text-main" />
                                {{ t('documents.scansTitle') }}
                            </span>
                            <span class="text-xs font-normal text-slate-400"
                                >{{ t('documents.filesCount') }}
                                {{ documentsList.length }}</span
                            >
                        </h3>

                        <template v-if="documentsList.length > 0">
                            <div
                                class="max-h-56 space-y-3 overflow-y-auto pr-1"
                            >
                                <div
                                    v-for="doc in documentsList"
                                    :key="doc.id"
                                    class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/50 p-3.5 transition-colors hover:border-main/40 dark:border-zinc-900 dark:bg-zinc-900/50 dark:hover:border-main/30"
                                >
                                    <div
                                        class="flex min-w-0 items-center gap-3"
                                    >
                                        <FileText
                                            class="h-7 w-7 shrink-0 text-main"
                                        />
                                        <div class="min-w-0">
                                            <h4
                                                class="truncate text-xs font-bold text-slate-900 dark:text-white"
                                            >
                                                {{
                                                    doc.original_filename ||
                                                    `Doc #${doc.id}`
                                                }}
                                            </h4>
                                            <div
                                                class="mt-0.5 flex items-center gap-2"
                                            >
                                                <span
                                                    class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[10px] font-semibold"
                                                    :class="[
                                                        isDocCompleted(doc)
                                                            ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400'
                                                            : isDocFailed(doc)
                                                              ? 'bg-red-500/15 text-red-600 dark:text-red-400'
                                                              : 'animate-pulse bg-amber-500/15 text-amber-600 dark:text-amber-400',
                                                    ]"
                                                >
                                                    <CheckCircle2
                                                        v-if="
                                                            isDocCompleted(doc)
                                                        "
                                                        class="h-3 w-3"
                                                    />
                                                    <AlertCircle
                                                        v-else-if="
                                                            isDocFailed(doc)
                                                        "
                                                        class="h-3 w-3"
                                                    />
                                                    <RefreshCw
                                                        v-else
                                                        class="h-3 w-3 animate-spin"
                                                    />
                                                    {{
                                                        isDocCompleted(doc)
                                                            ? t(
                                                                  'documents.processed',
                                                              )
                                                            : isDocFailed(doc)
                                                              ? t(
                                                                    'documents.failed',
                                                                )
                                                              : t(
                                                                    'documents.pending',
                                                                )
                                                    }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        class="flex shrink-0 items-center gap-1"
                                    >
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            class="h-7 w-7 cursor-pointer hover:bg-main/20"
                                            @click="openReviewModal(doc)"
                                            title="Review Document"
                                        >
                                            <Eye
                                                class="h-3.5 w-3.5 text-slate-500 hover:text-main"
                                            />
                                        </Button>
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            class="h-7 w-7 cursor-pointer hover:text-red-500"
                                            @click="confirmDeleteDocument(doc)"
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
            <div
                v-if="isReviewModalOpen && selectedDoc"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/75 p-4 backdrop-blur-sm"
            >
                <div
                    class="relative flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl dark:border-zinc-800 dark:bg-zinc-950"
                >
                    <!-- Modal Header -->
                    <div
                        class="flex items-center justify-between border-b border-slate-100 bg-slate-50/50 px-6 py-4 dark:border-zinc-900 dark:bg-zinc-900/50"
                    >
                        <div class="flex items-center gap-3">
                            <FileText class="h-6 w-6 text-main" />
                            <div>
                                <h3
                                    class="flex items-center gap-2 text-base font-extrabold text-slate-900 dark:text-white"
                                >
                                    {{
                                        selectedDoc.original_filename ||
                                        `Document #${selectedDoc.id}`
                                    }}
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold"
                                        :class="[
                                            isDocCompleted(selectedDoc)
                                                ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400'
                                                : isDocFailed(selectedDoc)
                                                  ? 'bg-red-500/15 text-red-600 dark:text-red-400'
                                                  : 'animate-pulse bg-amber-500/15 text-amber-600 dark:text-amber-400',
                                        ]"
                                    >
                                        {{
                                            isDocCompleted(selectedDoc)
                                                ? t('documents.processed')
                                                : isDocFailed(selectedDoc)
                                                  ? t('documents.failed')
                                                  : t('documents.pending')
                                        }}
                                    </span>
                                </h3>
                                <p class="text-[11px] text-slate-400">
                                    {{ t('documents.docTypeLabel') }}:
                                    {{ selectedDoc.document_type || 'auto' }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <a
                                :href="getFileStreamUrl(selectedDoc.id)"
                                target="_blank"
                                download
                                class="inline-flex items-center gap-1.5 rounded-xl bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-700 transition-colors hover:bg-main hover:text-slate-950 dark:bg-zinc-800 dark:text-zinc-300"
                            >
                                <Download class="h-3.5 w-3.5" />
                                {{ t('documents.downloadBtn') }}
                            </a>
                            <button
                                @click="closeReviewModal"
                                type="button"
                                class="cursor-pointer rounded-full p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-zinc-800 dark:hover:text-white"
                            >
                                <X class="h-5 w-5" />
                            </button>
                        </div>
                    </div>

                    <!-- Modal Content -->
                    <div class="flex-1 space-y-6 overflow-y-auto p-6">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <!-- Embedded File Preview Panel -->
                            <div
                                class="flex min-h-[350px] flex-col items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 p-2 dark:border-zinc-800 dark:bg-zinc-900"
                            >
                                <template v-if="isCheckingFile">
                                    <div
                                        class="flex flex-col items-center justify-center space-y-2 p-8 text-center text-slate-400"
                                    >
                                        <RefreshCw
                                            class="mb-2 h-6 w-6 animate-spin text-main"
                                        />
                                    </div>
                                </template>
                                <template v-else-if="isImageError">
                                    <div
                                        class="flex flex-col items-center justify-center space-y-3 p-8 text-center"
                                    >
                                        <div
                                            class="flex h-14 w-14 items-center justify-center rounded-2xl border border-amber-200/60 bg-amber-50 text-amber-500 shadow-xs dark:border-amber-900/40 dark:bg-amber-950/40"
                                        >
                                            <FileX class="h-7 w-7" />
                                        </div>
                                        <div>
                                            <h4
                                                class="text-sm font-extrabold text-slate-900 dark:text-white"
                                            >
                                                {{
                                                    t(
                                                        'documents.fileNotFoundTitle',
                                                    )
                                                }}
                                            </h4>
                                            <p
                                                class="mt-1 max-w-xs text-xs leading-relaxed text-slate-500 dark:text-zinc-400"
                                            >
                                                {{
                                                    t(
                                                        'documents.fileNotFoundDesc',
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </template>
                                <template
                                    v-else-if="
                                        isPdfFile(selectedDoc.original_filename)
                                    "
                                >
                                    <iframe
                                        :src="getFileStreamUrl(selectedDoc.id)"
                                        class="h-[400px] w-full rounded-xl border-0"
                                        @error="isImageError = true"
                                    ></iframe>
                                </template>
                                <template v-else>
                                    <img
                                        :src="getFileStreamUrl(selectedDoc.id)"
                                        :alt="selectedDoc.original_filename"
                                        class="max-h-[400px] w-auto max-w-full rounded-xl object-contain shadow-sm"
                                        @error="isImageError = true"
                                    />
                                </template>
                            </div>

                            <!-- Extracted OCR & Document Meta Panel -->
                            <div
                                class="flex flex-col justify-between space-y-4"
                            >
                                <div class="space-y-4">
                                    <h4
                                        class="border-b border-slate-100 pb-2 text-xs font-extrabold tracking-wider text-slate-400 uppercase dark:border-zinc-800"
                                    >
                                        {{ t('documents.ocrResultTitle') }}
                                    </h4>

                                    <div
                                        v-if="getRawText(selectedDoc)"
                                        class="max-h-48 overflow-y-auto rounded-xl border border-zinc-800 bg-slate-900 p-3 font-mono text-[11px] whitespace-pre-wrap text-emerald-400"
                                    >
                                        {{ getRawText(selectedDoc) }}
                                    </div>
                                    <div
                                        v-else
                                        class="rounded-xl bg-slate-50 p-4 text-center text-xs text-slate-400 dark:bg-zinc-900"
                                    >
                                        {{
                                            isDocPending(selectedDoc)
                                                ? t('documents.ocrProcessing')
                                                : t('documents.noRawText')
                                        }}
                                    </div>

                                    <div
                                        v-if="getExtractedData(selectedDoc)"
                                        class="space-y-2"
                                    >
                                        <h5
                                            class="text-xs font-bold text-slate-900 dark:text-white"
                                        >
                                            {{ t('documents.extractedMeta') }}:
                                        </h5>
                                        <pre
                                            class="max-h-36 overflow-y-auto rounded-xl border border-slate-200 bg-slate-100 p-3 font-mono text-[10px] text-slate-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300"
                                        >
                                {{
                                                JSON.stringify(
                                                    getExtractedData(
                                                        selectedDoc,
                                                    ),
                                                    null,
                                                    2,
                                                )
                                            }}</pre>
                                    </div>
                                </div>

                                <div
                                    class="flex justify-end gap-2 border-t border-slate-100 pt-4 dark:border-zinc-900"
                                >
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        @click="closeReviewModal"
                                        class="cursor-pointer"
                                    >
                                        {{ t('documents.closeModal') }}
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Monthly Salary Breakdown Modal -->
        <Dialog
            :open="isEditTaxModalOpen"
            @update:open="isEditTaxModalOpen = $event"
        >
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle
                        class="flex items-center gap-2 text-lg font-bold"
                    >
                        <Pencil class="h-5 w-5 text-main" />
                        {{
                            t('documents.editSalaryTitle').replace(
                                ':year',
                                String(editingTaxHistory?.year || ''),
                            )
                        }}
                    </DialogTitle>
                    <DialogDescription class="text-xs text-slate-500">
                        {{ t('documents.editSalarySubtitle') }}
                    </DialogDescription>
                </DialogHeader>

                <form
                    @submit.prevent="submitEditTaxHistory"
                    class="space-y-6 pt-4"
                >
                    <!-- Pre-2000 Toggle Switch inside Edit Modal -->
                    <div
                        v-if="
                            editingTaxHistory && editingTaxHistory.year < 2000
                        "
                        class="space-y-2 rounded-xl border border-amber-200/80 bg-amber-50/60 p-3.5 dark:border-amber-900/40 dark:bg-amber-950/30"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <Label
                                for="edit_pass_salary_toggle"
                                class="cursor-pointer text-xs font-bold text-slate-800 dark:text-zinc-200"
                            >
                                {{ t('documents.includePre2000SalaryLabel') }}
                            </Label>
                            <button
                                type="button"
                                id="edit_pass_salary_toggle"
                                @click="
                                    editPassSalaryPre2000 =
                                        !editPassSalaryPre2000
                                "
                                class="relative inline-flex h-5 w-10 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                :class="
                                    editPassSalaryPre2000
                                        ? 'bg-main'
                                        : 'bg-slate-300 dark:bg-zinc-700'
                                "
                            >
                                <span
                                    class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
                                    :class="
                                        editPassSalaryPre2000
                                            ? 'translate-x-5'
                                            : 'translate-x-0'
                                    "
                                ></span>
                            </button>
                        </div>
                        <p
                            class="text-[11px] leading-relaxed text-amber-800 dark:text-amber-300/90"
                        >
                            {{
                                editPassSalaryPre2000
                                    ? t('documents.pre2000SalaryNoticeWith')
                                    : t('documents.pre2000SalaryNoticeWithout')
                            }}
                        </p>
                    </div>

                    <!-- 12 Months Grid -->
                    <div
                        v-if="
                            !editingTaxHistory ||
                            editingTaxHistory.year >= 2000 ||
                            editPassSalaryPre2000
                        "
                        class="grid max-h-[50vh] grid-cols-1 gap-4 overflow-y-auto pr-2 sm:grid-cols-2"
                    >
                        <div
                            v-for="m in 12"
                            :key="m"
                            class="flex items-center justify-between gap-3 rounded-xl border border-slate-200/80 bg-slate-50/50 p-2.5 dark:border-zinc-800/80 dark:bg-zinc-900/50"
                        >
                            <span
                                class="flex w-28 shrink-0 items-center gap-1.5 text-xs font-semibold text-slate-700 dark:text-zinc-300"
                            >
                                <span
                                    class="flex h-5 w-5 items-center justify-center rounded-full bg-main/20 text-[10px] font-bold text-main"
                                    >{{ m }}</span
                                >
                                {{ monthNames[m - 1] }}
                            </span>
                            <div class="relative flex-1">
                                <Input
                                    v-model.number="monthlyBreakdownForm[m]"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="h-9 pr-7 text-right font-mono text-xs"
                                    placeholder="0.00"
                                />
                                <span
                                    class="pointer-events-none absolute top-2.5 right-2.5 text-xs font-bold text-slate-400"
                                    >₴</span
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Live Total Indicator -->
                    <div
                        class="flex items-center justify-between rounded-xl border border-main/30 bg-main/10 p-4 dark:bg-main/15"
                    >
                        <span
                            class="text-xs font-bold text-slate-900 dark:text-white"
                        >
                            {{ t('documents.totalAnnualIncome') }}
                        </span>
                        <span
                            class="font-mono text-lg font-extrabold text-main-dark dark:text-main"
                        >
                            {{
                                Number(calculatedAnnualSum).toLocaleString(
                                    'uk-UA',
                                    { minimumFractionDigits: 2 },
                                )
                            }}
                            ₴
                        </span>
                    </div>

                    <!-- Action Buttons -->
                    <DialogFooter
                        class="flex items-center justify-end gap-3 border-t border-slate-100 pt-2 dark:border-zinc-800"
                    >
                        <Button
                            type="button"
                            variant="outline"
                            @click="isEditTaxModalOpen = false"
                            :disabled="isSubmittingEditTax"
                            class="h-10 cursor-pointer text-xs font-bold"
                        >
                            {{ t('documents.cancelBtn') }}
                        </Button>

                        <Button
                            type="submit"
                            :disabled="isSubmittingEditTax"
                            class="h-10 cursor-pointer bg-main text-xs font-bold text-slate-950 hover:bg-main-dark"
                        >
                            {{
                                isSubmittingEditTax
                                    ? t('documents.savingBtn')
                                    : t('documents.saveBtn')
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Custom Delete Confirmation Modal -->
        <Dialog
            :open="isConfirmDeleteModalOpen"
            @update:open="isConfirmDeleteModalOpen = $event"
        >
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle
                        class="flex items-center gap-2.5 text-base font-extrabold text-slate-900 dark:text-white"
                    >
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-red-500/20 bg-red-500/10 text-red-600 dark:text-red-400"
                        >
                            <Trash2 class="h-4 w-4" />
                        </div>
                        {{
                            deleteTarget?.type === 'document'
                                ? t('documents.deleteConfirmDocTitle')
                                : t('documents.deleteConfirmTaxTitle')
                        }}
                    </DialogTitle>
                    <DialogDescription
                        class="pt-2 text-xs leading-relaxed text-slate-500 dark:text-zinc-400"
                    >
                        {{
                            deleteTarget?.type === 'document'
                                ? t('documents.deleteConfirmDocMsg')
                                : t('documents.deleteConfirmTaxMsg')
                        }}
                        <span
                            v-if="deleteTarget?.title"
                            class="mt-1.5 block rounded-xl border border-slate-200/60 bg-slate-100 p-2 font-bold text-slate-900 dark:border-zinc-800 dark:bg-zinc-900 dark:text-white"
                        >
                            {{ deleteTarget.title }}
                        </span>
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter
                    class="flex items-center justify-end gap-2.5 border-t border-slate-100 pt-4 dark:border-zinc-800"
                >
                    <Button
                        type="button"
                        variant="outline"
                        @click="isConfirmDeleteModalOpen = false"
                        :disabled="isDeletingRecord"
                        class="h-9 cursor-pointer text-xs font-bold"
                    >
                        {{ t('documents.cancelBtn') }}
                    </Button>
                    <Button
                        type="button"
                        @click="executeDeleteRecord"
                        :disabled="isDeletingRecord"
                        class="h-9 cursor-pointer bg-red-600 text-xs font-bold text-white shadow-xs hover:bg-red-700"
                    >
                        <RefreshCw
                            v-if="isDeletingRecord"
                            class="mr-1.5 h-3.5 w-3.5 animate-spin"
                        />
                        {{
                            isDeletingRecord
                                ? t('documents.deletingBtn')
                                : t('documents.confirmDeleteBtn')
                        }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
