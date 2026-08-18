<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';
import { FileText, UploadCloud, Trash2, CheckCircle2, Clock, AlertCircle, Eye, Calendar, Plus, Layers } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import YearPicker from '@/components/ui/YearPicker.vue';

export interface DocumentItem {
    id: number;
    original_filename?: string;
    document_type?: string;
    ocr_status?: 'pending' | 'processed' | 'failed' | string;
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

const documentsList = ref<DocumentItem[]>(props.initialDocuments || []);
const taxHistoriesList = ref<TaxHistoryItem[]>(props.initialTaxHistories || []);
const selectedDoc = ref<DocumentItem | null>(null);

// Upload form
const uploadForm = useForm<{
    document: File | null;
    document_type: string;
}>({
    document: null,
    document_type: 'income_certificate',
});

// Manual tax history form
const isRangeMode = ref(true);
const manualTaxForm = useForm({
    is_range: true,
    year: 2024,
    from_year: 2020,
    to_year: 2024,
    monthly_salary: 15000,
    months_worked: 12,
});

const totalWorkedYearsCount = computed(() => taxHistoriesList.value.length);
const totalAccumulatedMonthsCount = computed(() => {
    return taxHistoriesList.value.reduce((acc, curr) => acc + (curr.months_worked || 12), 0);
});

function handleFileSelect(e: Event) {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        uploadForm.document = target.files[0];
        uploadDocument();
    }
}

function uploadDocument() {
    if (!uploadForm.document) return;
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
                window.location.reload();
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
            if (selectedDoc.value?.id === id) selectedDoc.value = null;
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
</script>

<template>
    <div class="space-y-8">
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
                    <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-zinc-800/60 pb-3 flex items-center gap-2">
                        <UploadCloud class="h-4 w-4 text-main" />
                        {{ t('documents.uploadTitle') }}
                    </h3>

                    <label
                        class="relative flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 p-6 text-center transition-colors hover:border-main cursor-pointer dark:border-zinc-800 dark:hover:border-main/60 bg-slate-50/50 dark:bg-zinc-900/50"
                    >
                        <UploadCloud class="h-8 w-8 text-main mb-2" />
                        <span class="text-xs font-bold text-slate-900 dark:text-white">
                            {{ t('documents.dropzoneText') }}
                        </span>
                        <span class="mt-0.5 text-[10px] text-slate-400">
                            {{ t('documents.supportedFormats') }}
                        </span>
                        <input
                            type="file"
                            accept=".pdf,.png,.jpg,.jpeg"
                            class="sr-only"
                            @change="handleFileSelect"
                        />
                    </label>

                    <div v-if="uploadForm.processing" class="text-center text-xs font-semibold text-main animate-pulse">
                        {{ t('documents.uploadingText') }}
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
                            class="w-full bg-main text-slate-950 hover:bg-main-dark font-bold shadow-sm h-10"
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
                                            <button
                                                @click="deleteTaxHistory(item.id)"
                                                type="button"
                                                class="text-slate-400 hover:text-red-500 cursor-pointer p-1"
                                            >
                                                <Trash2 class="h-3.5 w-3.5" />
                                            </button>
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
                        <div class="space-y-3 max-h-48 overflow-y-auto pr-1">
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
                                                    doc.ocr_status === 'processed'
                                                        ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400'
                                                        : doc.ocr_status === 'failed'
                                                        ? 'bg-red-500/15 text-red-600 dark:text-red-400'
                                                        : 'bg-amber-500/15 text-amber-600 dark:text-amber-400'
                                                ]"
                                            >
                                                <CheckCircle2 v-if="doc.ocr_status === 'processed'" class="h-3 w-3" />
                                                <AlertCircle v-else-if="doc.ocr_status === 'failed'" class="h-3 w-3" />
                                                <Clock v-else class="h-3 w-3" />
                                                {{ doc.ocr_status === 'processed' ? t('documents.processed') : doc.ocr_status === 'failed' ? t('documents.failed') : t('documents.pending') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-1 shrink-0">
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        class="h-7 w-7"
                                        @click="selectedDoc = doc"
                                    >
                                        <Eye class="h-3.5 w-3.5 text-slate-500 hover:text-main" />
                                    </Button>
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        class="h-7 w-7 hover:text-red-500"
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
    </div>
</template>
