<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { toast } from 'vue-sonner';
import { useI18n } from '@/composables/useI18n';
import {
    Search,
    FileText,
    Download,
    Eye,
    Trash2,
    ChevronUp,
    ChevronDown,
    X,
    CheckCircle2,
    Loader2
} from '@lucide/vue';

const { t } = useI18n();

interface DocumentItem {
    id: number;
    user_id: number;
    user_name: string;
    user_email: string;
    title: string;
    document_type: string;
    file_name: string;
    file_size: number;
    formatted_file_size: string;
    mime_type: string;
    status: string;
    created_at: string;
}

interface DocumentDetail extends DocumentItem {
    recognized_data?: any;
}

const documents = ref<DocumentItem[]>([]);
const isLoading = ref(false);
const searchQuery = ref('');
const typeFilter = ref('');
const sortBy = ref('created_at');
const sortDir = ref<'asc' | 'desc'>('desc');

// Pagination
const currentPage = ref(1);
const lastPage = ref(1);
const totalRecords = ref(0);
const perPage = ref(15);

// Preview Modal
const showPreviewModal = ref(false);
const selectedDoc = ref<DocumentDetail | null>(null);
const isLoadingDetail = ref(false);

// Confirm Delete Modal
const showDeleteModal = ref(false);
const targetDoc = ref<DocumentItem | null>(null);
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

async function fetchDocuments() {
    isLoading.value = true;
    try {
        const queryParams = new URLSearchParams({
            page: currentPage.value.toString(),
            per_page: perPage.value.toString(),
            search: searchQuery.value,
            document_type: typeFilter.value,
            sort_by: sortBy.value,
            sort_dir: sortDir.value,
        });
        const res = await apiFetch(`/admin/documents?${queryParams.toString()}`);
        const data = await res.json();
        if (res.ok && data.status === 'success') {
            const paginated = data.data;
            documents.value = paginated.data || [];
            currentPage.value = paginated.current_page || 1;
            lastPage.value = paginated.last_page || 1;
            totalRecords.value = paginated.total || 0;
        } else {
            toast.error(data.message || 'Помилка завантаження документів.');
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
    fetchDocuments();
}

async function previewDocument(doc: DocumentItem) {
    showPreviewModal.value = true;
    isLoadingDetail.value = true;
    selectedDoc.value = null;
    try {
        const res = await apiFetch(`/admin/documents/${doc.id}`);
        const data = await res.json();
        if (res.ok && data.status === 'success') {
            selectedDoc.value = data.data;
        } else {
            toast.error('Не вдалося завантажити деталі документа.');
            showPreviewModal.value = false;
        }
    } catch (err: any) {
        toast.error('Помилка завантаження деталей.');
        showPreviewModal.value = false;
    } finally {
        isLoadingDetail.value = false;
    }
}

function downloadDocument(doc: DocumentItem) {
    window.open(`/admin/documents/${doc.id}/download`, '_blank');
}

function confirmDelete(doc: DocumentItem) {
    targetDoc.value = doc;
    showDeleteModal.value = true;
}

async function executeDelete() {
    if (!targetDoc.value) return;
    isDeleting.value = true;
    try {
        const res = await apiFetch(`/admin/documents/${targetDoc.value.id}`, { method: 'DELETE' });
        const data = await res.json();
        if (res.ok && data.status === 'success') {
            toast.success(data.message || 'Документ успішно вилучено.');
            fetchDocuments();
        } else {
            toast.error(data.message || 'Помилка вилучення документа.');
        }
    } catch (err: any) {
        toast.error('Помилка мережі при вилученні.');
    } finally {
        isDeleting.value = false;
        showDeleteModal.value = false;
        targetDoc.value = null;
    }
}

let searchDebounce: any = null;
watch(searchQuery, () => {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => {
        currentPage.value = 1;
        fetchDocuments();
    }, 350);
});

watch(typeFilter, () => {
    currentPage.value = 1;
    fetchDocuments();
});

onMounted(() => {
    fetchDocuments();
});
</script>

<template>
    <div class="space-y-6">
        <!-- Control Bar: Search & Type Filters -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 bg-white dark:bg-zinc-900 p-4 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-xs">
            <div class="relative flex-1 max-w-md">
                <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 dark:text-zinc-500" />
                <input
                    v-model="searchQuery"
                    type="text"
                    :placeholder="t('adminDocuments.searchPlaceholder')"
                    class="w-full pl-10 pr-4 py-2 text-xs sm:text-sm bg-slate-50 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl focus:outline-none focus:ring-2 focus:ring-main"
                />
            </div>

            <!-- Type Filter -->
            <select
                v-model="typeFilter"
                class="px-3 py-2 text-xs sm:text-sm bg-slate-50 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl font-medium cursor-pointer"
            >
                <option value="">All document types</option>
                <option value="PASSPORT">Passport</option>
                <option value="WORK_BOOK">Work Book</option>
                <option value="TAX_RECORD">Tax Record</option>
                <option value="MILITARY_ID">Military ID</option>
                <option value="DIPLOMA">Diploma</option>
            </select>
        </div>

        <!-- Data Table Container -->
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-xs overflow-hidden">
            <div class="overflow-x-auto [&::-webkit-scrollbar]:h-1.5 [&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar-thumb]:bg-slate-300 dark:[&::-webkit-scrollbar-thumb]:bg-zinc-800 [&::-webkit-scrollbar-thumb]:rounded-full">
                <table class="w-full text-left text-xs sm:text-sm min-w-[700px]">
                    <thead class="bg-slate-50 dark:bg-zinc-950 text-slate-500 dark:text-zinc-400 font-extrabold uppercase tracking-wider text-[10px] sm:text-[11px] border-b border-slate-200 dark:border-zinc-800">
                        <tr>
                            <th @click="handleSort('id')" class="p-3.5 cursor-pointer hover:text-slate-900 dark:hover:text-white transition-colors">
                                <div class="flex items-center gap-1">
                                    <span>{{ t('adminDocuments.columnId') }}</span>
                                    <ChevronUp v-if="sortBy === 'id' && sortDir === 'asc'" class="h-3.5 w-3.5" />
                                    <ChevronDown v-if="sortBy === 'id' && sortDir === 'desc'" class="h-3.5 w-3.5" />
                                </div>
                            </th>
                            <th class="p-3.5">{{ t('adminDocuments.columnUser') }}</th>
                            <th @click="handleSort('title')" class="p-3.5 cursor-pointer hover:text-slate-900 dark:hover:text-white transition-colors">
                                <div class="flex items-center gap-1">
                                    <span>{{ t('adminDocuments.columnFilename') }}</span>
                                    <ChevronUp v-if="sortBy === 'title' && sortDir === 'asc'" class="h-3.5 w-3.5" />
                                    <ChevronDown v-if="sortBy === 'title' && sortDir === 'desc'" class="h-3.5 w-3.5" />
                                </div>
                            </th>
                            <th @click="handleSort('file_size')" class="p-3.5 cursor-pointer hover:text-slate-900 dark:hover:text-white transition-colors">
                                <div class="flex items-center gap-1">
                                    <span>{{ t('adminDocuments.columnSize') }}</span>
                                    <ChevronUp v-if="sortBy === 'file_size' && sortDir === 'asc'" class="h-3.5 w-3.5" />
                                    <ChevronDown v-if="sortBy === 'file_size' && sortDir === 'desc'" class="h-3.5 w-3.5" />
                                </div>
                            </th>
                            <th @click="handleSort('created_at')" class="p-3.5 cursor-pointer hover:text-slate-900 dark:hover:text-white transition-colors">
                                <div class="flex items-center gap-1">
                                    <span>{{ t('adminDocuments.columnUploadedAt') }}</span>
                                    <ChevronUp v-if="sortBy === 'created_at' && sortDir === 'asc'" class="h-3.5 w-3.5" />
                                    <ChevronDown v-if="sortBy === 'created_at' && sortDir === 'desc'" class="h-3.5 w-3.5" />
                                </div>
                            </th>
                            <th class="p-3.5 text-right">{{ t('adminDocuments.columnActions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/80 font-medium">
                        <tr v-if="isLoading">
                            <td colspan="6" class="p-8 text-center text-slate-400">
                                <div class="flex items-center justify-center gap-2">
                                    <Loader2 class="h-5 w-5 animate-spin text-main" />
                                    <span>{{ t('adminDocuments.loadingDocuments') }}</span>
                                </div>
                            </td>
                        </tr>
                        <tr v-else-if="documents.length === 0">
                            <td colspan="6" class="p-8 text-center text-slate-400">
                                {{ t('adminDocuments.noDocumentsFound') }}
                            </td>
                        </tr>
                        <tr v-for="doc in documents" :key="doc.id" class="hover:bg-slate-50/80 dark:hover:bg-zinc-950/60 transition-colors">
                            <td class="p-3.5 font-bold font-mono text-slate-500">#{{ doc.id }}</td>
                            <td class="p-3.5">
                                <div>
                                    <div class="font-bold text-slate-900 dark:text-white">{{ doc.user_name }}</div>
                                    <div class="text-[11px] font-mono text-slate-400">{{ doc.user_email }}</div>
                                </div>
                            </td>
                            <td class="p-3.5">
                                <div class="flex items-center gap-2">
                                    <FileText class="h-4 w-4 text-main shrink-0" />
                                    <div>
                                        <div class="font-bold text-slate-900 dark:text-white">{{ doc.title }}</div>
                                        <span class="text-[10px] font-extrabold uppercase px-1.5 py-0.5 rounded bg-slate-100 dark:bg-zinc-800 text-slate-500">
                                            {{ doc.document_type }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3.5 font-mono text-slate-600 dark:text-zinc-300">{{ doc.formatted_file_size }}</td>
                            <td class="p-3.5 text-slate-500 font-mono text-xs">{{ doc.created_at }}</td>
                            <td class="p-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button @click="previewDocument(doc)" :title="t('adminUsers.titleViewDetails')" class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-zinc-800 text-slate-600 dark:text-zinc-400 cursor-pointer">
                                        <Eye class="h-4 w-4" />
                                    </button>
                                    <button @click="downloadDocument(doc)" :title="t('adminDocuments.btnDownload')" class="p-1.5 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-950/50 text-blue-600 dark:text-blue-400 cursor-pointer">
                                        <Download class="h-4 w-4" />
                                    </button>
                                    <button @click="confirmDelete(doc)" :title="t('adminDocuments.btnDelete')" class="p-1.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-950/50 text-red-600 dark:text-red-400 cursor-pointer">
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
                <div>{{ t('adminDocuments.totalRecords') }} <span class="font-bold text-slate-900 dark:text-white">{{ totalRecords }}</span></div>
                <div class="flex items-center gap-2">
                    <button
                        :disabled="currentPage <= 1"
                        @click="currentPage--; fetchDocuments();"
                        class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-zinc-800 disabled:opacity-40 cursor-pointer"
                    >
                        {{ t('adminUsers.pagePrev') }}
                    </button>
                    <span>{{ t('adminUsers.pageWord') }} {{ currentPage }} {{ t('adminUsers.pageOf') }} {{ lastPage }}</span>
                    <button
                        :disabled="currentPage >= lastPage"
                        @click="currentPage++; fetchDocuments();"
                        class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-zinc-800 disabled:opacity-40 cursor-pointer"
                    >
                        {{ t('adminUsers.pageNext') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Preview Modal -->
        <div v-if="showPreviewModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
            <div class="bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-3xl w-full max-w-xl p-6 shadow-2xl space-y-4 relative">
                <button @click="showPreviewModal = false" class="absolute right-5 top-5 text-slate-400 hover:text-slate-900 dark:hover:text-white cursor-pointer">
                    <X class="h-5 w-5" />
                </button>

                <div class="flex items-center gap-2 text-main-dark dark:text-main">
                    <FileText class="h-6 w-6 text-main shrink-0" />
                    <h3 class="text-base font-black text-slate-900 dark:text-white">{{ t('adminDocuments.columnFilename') }} #{{ selectedDoc?.id }}</h3>
                </div>

                <div v-if="isLoadingDetail" class="py-8 text-center text-slate-400">
                    <Loader2 class="h-6 w-6 animate-spin text-main mx-auto mb-2" />
                    <span>{{ t('adminUsers.loadingData') }}</span>
                </div>

                <div v-else-if="selectedDoc" class="space-y-4 text-xs">
                    <div class="space-y-2 p-4 rounded-2xl bg-slate-50 dark:bg-zinc-900 border border-slate-100 dark:border-zinc-800">
                        <div class="flex justify-between">
                            <span class="text-slate-400">Title:</span>
                            <span class="font-bold text-slate-900 dark:text-white">{{ selectedDoc.title }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">File:</span>
                            <span class="font-mono text-slate-700 dark:text-zinc-300">{{ selectedDoc.file_name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Type:</span>
                            <span class="font-bold text-slate-900 dark:text-white">{{ selectedDoc.document_type }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Size:</span>
                            <span class="font-mono text-slate-700 dark:text-zinc-300">{{ selectedDoc.formatted_file_size }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Owner:</span>
                            <span class="font-bold text-slate-900 dark:text-white">{{ selectedDoc.user?.name }} ({{ selectedDoc.user?.email }})</span>
                        </div>
                    </div>

                    <div v-if="selectedDoc.recognized_data" class="space-y-2">
                        <span class="font-bold text-slate-900 dark:text-white">OCR Data:</span>
                        <pre class="p-3 rounded-xl bg-zinc-950 text-emerald-400 font-mono text-[11px] overflow-x-auto max-h-40">{{ JSON.stringify(selectedDoc.recognized_data, null, 2) }}</pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirm Delete Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
            <div class="bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-3xl w-full max-w-md p-6 shadow-2xl space-y-4">
                <h3 class="text-base font-extrabold text-slate-900 dark:text-white">{{ t('adminDocuments.btnDelete') }}?</h3>
                <p class="text-xs text-slate-500 leading-relaxed">
                    {{ t('adminUsers.modalConfirmText') }} "<span class="font-bold text-slate-900 dark:text-white">{{ targetDoc?.title }}</span>"?
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
