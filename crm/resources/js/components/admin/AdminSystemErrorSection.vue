<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { useI18n } from '@/composables/useI18n';
import {
    AlertTriangle,
    CheckCircle2,
    Clock,
    Search,
    RefreshCw,
    FileCode,
    Check,
    RotateCcw,
    Copy,
    User,
    Globe,
    Layers,
    ChevronLeft,
    ChevronRight,
    Sparkles,
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { toast } from 'vue-sonner';

export interface SystemErrorItem {
    id: number;
    user_id?: number | null;
    status_code: number;
    url: string;
    method: string;
    exception_class: string;
    message: string;
    stack_trace?: string | null;
    user_agent?: string | null;
    ip_address?: string | null;
    is_resolved: boolean;
    resolved_at?: string | null;
    resolved_by_id?: number | null;
    created_at?: string;
    user?: {
        id: number;
        first_name?: string;
        last_name?: string;
        email: string;
    } | null;
    resolver?: {
        id: number;
        first_name?: string;
        last_name?: string;
        email: string;
    } | null;
}

const { t } = useI18n();

const isLoading = ref(true);
const logs = ref<SystemErrorItem[]>([]);
const stats = ref({
    total: 0,
    unresolved: 0,
    resolved_today: 0,
});

const statusFilter = ref<'all' | 'unresolved' | 'resolved'>('all');
const searchQuery = ref('');
const currentPage = ref(1);
const totalPages = ref(1);
const perPage = ref(15);

const selectedIds = ref<number[]>([]);
const isBatchProcessing = ref(false);

const activeDetailLog = ref<SystemErrorItem | null>(null);
const showDetailModal = ref(false);
const isCopied = ref(false);

async function fetchLogs(page = 1) {
    isLoading.value = true;
    try {
        const params = new URLSearchParams({
            page: String(page),
            per_page: String(perPage.value),
            status: statusFilter.value,
            search: searchQuery.value,
        });

        const res = await fetch(`/admin/system-errors?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (res.ok) {
            const json = await res.json();
            if (json.success && json.data) {
                logs.value = json.data.data || [];
                currentPage.value = json.data.current_page || 1;
                totalPages.value = json.data.last_page || 1;
                stats.value = json.stats || stats.value;
            }
        }
    } catch (e) {
        toast.error('Failed to fetch system error logs.');
    } finally {
        isLoading.value = false;
    }
}

async function toggleResolveStatus(item: SystemErrorItem) {
    try {
        const res = await fetch(`/admin/system-errors/${item.id}/toggle-resolve`, {
            method: 'PATCH',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
            },
        });

        if (res.ok) {
            const json = await res.json();
            if (json.success && json.data) {
                item.is_resolved = json.data.is_resolved;
                item.resolved_at = json.data.resolved_at;
                item.resolver = json.data.resolver;

                if (json.data.is_resolved) {
                    stats.value.unresolved = Math.max(0, stats.value.unresolved - 1);
                    stats.value.resolved_today++;
                    toast.success(t('adminSystemErrors.statusResolved'));
                } else {
                    stats.value.unresolved++;
                    toast.info(t('adminSystemErrors.statusUnresolved'));
                }
            }
        }
    } catch (e) {
        toast.error('Could not update status.');
    }
}

async function handleBatchResolve(targetResolvedStatus: boolean) {
    if (selectedIds.value.length === 0) return;
    isBatchProcessing.value = true;
    try {
        const res = await fetch('/admin/system-errors/batch-resolve', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
            },
            body: JSON.stringify({
                ids: selectedIds.value,
                is_resolved: targetResolvedStatus,
            }),
        });

        if (res.ok) {
            toast.success(targetResolvedStatus ? t('adminSystemErrors.batchResolve') : t('adminSystemErrors.batchUnresolve'));
            selectedIds.value = [];
            await fetchLogs(currentPage.value);
        }
    } catch (e) {
        toast.error('Batch operation failed.');
    } finally {
        isBatchProcessing.value = false;
    }
}

function selectAllOnPage(e: Event) {
    const checked = (e.target as HTMLInputElement).checked;
    if (checked) {
        selectedIds.value = logs.value.map(l => l.id);
    } else {
        selectedIds.value = [];
    }
}

function openDetailModal(item: SystemErrorItem) {
    activeDetailLog.value = item;
    showDetailModal.value = true;
    isCopied.value = false;
}

function copyStackTrace() {
    if (!activeDetailLog.value?.stack_trace) return;
    navigator.clipboard.writeText(activeDetailLog.value.stack_trace);
    isCopied.value = true;
    toast.success(t('adminSystemErrors.traceCopied'));
    setTimeout(() => {
        isCopied.value = false;
    }, 2000);
}

function formatDate(dateStr?: string | null) {
    if (!dateStr) return '---';
    return new Date(dateStr).toLocaleString('uk-UA', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
}

function getStatusCodeClass(code: number) {
    if (code === 502) return 'bg-amber-500/20 text-amber-600 dark:text-amber-400 border-amber-500/30';
    if (code >= 500) return 'bg-rose-500/20 text-rose-600 dark:text-rose-400 border-rose-500/30';
    return 'bg-blue-500/20 text-blue-600 dark:text-blue-400 border-blue-500/30';
}

let searchDebounce: ReturnType<typeof setTimeout> | null = null;

watch(searchQuery, () => {
    if (searchDebounce) clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => {
        fetchLogs(1);
    }, 300);
});

watch(statusFilter, () => {
    fetchLogs(1);
});

onMounted(() => {
    fetchLogs(1);
});
</script>

<template>
    <div class="space-y-6">
        <!-- Top Stats Overview Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <!-- Total Errors Card -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 flex items-center justify-between">
                <div class="space-y-1">
                    <span class="text-xs font-semibold text-slate-500 dark:text-zinc-400">{{ t('adminSystemErrors.totalErrors') }}</span>
                    <p class="text-2xl font-black text-slate-900 dark:text-white">{{ stats.total }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-100 text-slate-700 dark:bg-zinc-800 dark:text-zinc-300">
                    <FileCode class="h-5 w-5" />
                </div>
            </div>

            <!-- Unresolved Errors Card -->
            <div class="rounded-2xl border border-amber-500/30 bg-gradient-to-br from-amber-500/10 to-transparent p-5 shadow-xs dark:border-amber-500/20 dark:bg-zinc-900 flex items-center justify-between">
                <div class="space-y-1">
                    <span class="text-xs font-bold text-amber-700 dark:text-amber-400">{{ t('adminSystemErrors.unresolvedErrors') }}</span>
                    <p class="text-2xl font-black text-amber-600 dark:text-amber-400">{{ stats.unresolved }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-500/20 text-amber-600 dark:text-amber-400">
                    <AlertTriangle class="h-5 w-5" />
                </div>
            </div>

            <!-- Resolved Today Card -->
            <div class="rounded-2xl border border-emerald-500/30 bg-gradient-to-br from-emerald-500/10 to-transparent p-5 shadow-xs dark:border-emerald-500/20 dark:bg-zinc-900 flex items-center justify-between">
                <div class="space-y-1">
                    <span class="text-xs font-bold text-emerald-700 dark:text-emerald-400">{{ t('adminSystemErrors.resolvedToday') }}</span>
                    <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ stats.resolved_today }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-500/20 text-emerald-600 dark:text-emerald-400">
                    <CheckCircle2 class="h-5 w-5" />
                </div>
            </div>
        </div>

        <!-- Filter & Search Controls Bar -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 space-y-4 sm:space-y-0 sm:flex sm:items-center sm:justify-between sm:gap-4">
            <!-- Filter Tabs -->
            <div class="flex items-center gap-1.5 p-1 rounded-xl bg-slate-100 dark:bg-zinc-800 shrink-0">
                <button
                    @click="statusFilter = 'all'"
                    type="button"
                    class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all cursor-pointer"
                    :class="statusFilter === 'all' ? 'bg-white text-slate-900 shadow-xs dark:bg-zinc-950 dark:text-white' : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white'"
                >
                    {{ t('adminSystemErrors.filterAll') }}
                </button>
                <button
                    @click="statusFilter = 'unresolved'"
                    type="button"
                    class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all cursor-pointer flex items-center gap-1.5"
                    :class="statusFilter === 'unresolved' ? 'bg-white text-amber-600 shadow-xs dark:bg-zinc-950 dark:text-amber-400' : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white'"
                >
                    <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                    {{ t('adminSystemErrors.filterUnresolved') }}
                </button>
                <button
                    @click="statusFilter = 'resolved'"
                    type="button"
                    class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all cursor-pointer flex items-center gap-1.5"
                    :class="statusFilter === 'resolved' ? 'bg-white text-emerald-600 shadow-xs dark:bg-zinc-950 dark:text-emerald-400' : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white'"
                >
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    {{ t('adminSystemErrors.filterResolved') }}
                </button>
            </div>

            <!-- Search Bar & Refresh -->
            <div class="flex items-center gap-2 flex-1 max-w-md">
                <div class="relative flex-1">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        :placeholder="t('adminSystemErrors.searchPlaceholder')"
                        class="w-full pl-9 pr-4 py-2 rounded-xl text-xs border border-slate-200 bg-slate-50 text-slate-900 focus:outline-none focus:ring-2 focus:ring-main dark:border-zinc-800 dark:bg-zinc-950 dark:text-white"
                    />
                </div>
                <Button
                    @click="fetchLogs(currentPage)"
                    type="button"
                    variant="outline"
                    class="h-9 px-3 rounded-xl cursor-pointer"
                    :disabled="isLoading"
                >
                    <RefreshCw class="h-4 w-4 text-slate-600 dark:text-zinc-400" :class="{ 'animate-spin': isLoading }" />
                </Button>
            </div>
        </div>

        <!-- Batch Actions Bar -->
        <div v-if="selectedIds.length > 0" class="rounded-xl border border-main/30 bg-main/10 p-3 flex items-center justify-between text-xs font-bold text-slate-900 dark:text-white">
            <span>Вибрано {{ selectedIds.length }} логів</span>
            <div class="flex items-center gap-2">
                <Button
                    @click="handleBatchResolve(true)"
                    type="button"
                    size="sm"
                    class="bg-emerald-600 text-white hover:bg-emerald-700 font-bold h-8 rounded-lg cursor-pointer"
                    :disabled="isBatchProcessing"
                >
                    <Check class="mr-1.5 h-3.5 w-3.5" />
                    {{ t('adminSystemErrors.batchResolve') }}
                </Button>
                <Button
                    @click="handleBatchResolve(false)"
                    type="button"
                    size="sm"
                    variant="outline"
                    class="h-8 rounded-lg cursor-pointer"
                    :disabled="isBatchProcessing"
                >
                    <RotateCcw class="mr-1.5 h-3.5 w-3.5" />
                    {{ t('adminSystemErrors.batchUnresolve') }}
                </Button>
            </div>
        </div>

        <!-- Main Errors Data Table -->
        <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-slate-100 bg-slate-50/70 text-[11px] font-bold text-slate-500 uppercase tracking-wider dark:border-zinc-800 dark:bg-zinc-950/70 dark:text-zinc-400">
                        <tr>
                            <th class="p-4 w-10 text-center">
                                <input
                                    type="checkbox"
                                    :checked="selectedIds.length > 0 && selectedIds.length === logs.length"
                                    @change="selectAllOnPage"
                                    class="rounded border-slate-300 text-main focus:ring-main dark:border-zinc-700 dark:bg-zinc-800 cursor-pointer"
                                />
                            </th>
                            <th class="p-4">{{ t('adminSystemErrors.columnDate') }}</th>
                            <th class="p-4">{{ t('adminSystemErrors.columnUser') }}</th>
                            <th class="p-4">{{ t('adminSystemErrors.columnStatusCode') }}</th>
                            <th class="p-4">{{ t('adminSystemErrors.columnPath') }}</th>
                            <th class="p-4">{{ t('adminSystemErrors.columnException') }}</th>
                            <th class="p-4">{{ t('adminSystemErrors.columnStatus') }}</th>
                            <th class="p-4 text-right">{{ t('adminSystemErrors.columnActions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/60 font-medium">
                        <template v-if="isLoading">
                            <tr v-for="i in 5" :key="i" class="animate-pulse">
                                <td colspan="8" class="p-4">
                                    <div class="h-5 bg-slate-100 dark:bg-zinc-800 rounded-lg w-full"></div>
                                </td>
                            </tr>
                        </template>
                        <template v-else-if="logs.length > 0">
                            <tr
                                v-for="item in logs"
                                :key="item.id"
                                class="hover:bg-slate-50/80 dark:hover:bg-zinc-900/50 transition-colors"
                            >
                                <td class="p-4 text-center">
                                    <input
                                        type="checkbox"
                                        :value="item.id"
                                        v-model="selectedIds"
                                        class="rounded border-slate-300 text-main focus:ring-main dark:border-zinc-700 dark:bg-zinc-800 cursor-pointer"
                                    />
                                </td>
                                <td class="p-4 whitespace-nowrap text-slate-500 dark:text-zinc-400">
                                    {{ formatDate(item.created_at) }}
                                </td>
                                <td class="p-4 whitespace-nowrap">
                                    <span v-if="item.user" class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                        <User class="h-3.5 w-3.5 text-main shrink-0" />
                                        {{ item.user.first_name ? `${item.user.first_name} ${item.user.last_name || ''}` : item.user.email }}
                                    </span>
                                    <span v-else class="text-slate-400 dark:text-zinc-500 italic">Гість / Guest</span>
                                </td>
                                <td class="p-4 whitespace-nowrap">
                                    <span
                                        class="px-2.5 py-1 rounded-md text-[11px] font-black border"
                                        :class="getStatusCodeClass(item.status_code)"
                                    >
                                        {{ item.status_code }}
                                    </span>
                                </td>
                                <td class="p-4 max-w-xs truncate font-mono text-[11px] text-slate-700 dark:text-zinc-300">
                                    <span class="font-bold uppercase text-[10px] px-1.5 py-0.5 rounded bg-slate-100 dark:bg-zinc-800 mr-1">
                                        {{ item.method }}
                                    </span>
                                    {{ item.url }}
                                </td>
                                <td class="p-4 max-w-xs truncate">
                                    <p class="font-bold text-slate-900 dark:text-white truncate">
                                        {{ item.exception_class.split('\\').pop() }}
                                    </p>
                                    <p class="text-[11px] text-slate-500 dark:text-zinc-400 truncate">
                                        {{ item.message }}
                                    </p>
                                </td>
                                <td class="p-4 whitespace-nowrap">
                                    <span
                                        v-if="item.is_resolved"
                                        class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-bold text-[11px]"
                                    >
                                        <CheckCircle2 class="h-3.5 w-3.5" />
                                        {{ t('adminSystemErrors.statusResolved') }}
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 text-amber-600 dark:text-amber-400 font-bold text-[11px]"
                                    >
                                        <AlertTriangle class="h-3.5 w-3.5" />
                                        {{ t('adminSystemErrors.statusUnresolved') }}
                                    </span>
                                </td>
                                <td class="p-4 whitespace-nowrap text-right space-x-2">
                                    <Button
                                        @click="openDetailModal(item)"
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        class="h-8 text-xs font-bold rounded-lg cursor-pointer"
                                    >
                                        <FileCode class="h-3.5 w-3.5 mr-1" />
                                        {{ t('adminSystemErrors.btnViewTrace') }}
                                    </Button>

                                    <Button
                                        @click="toggleResolveStatus(item)"
                                        type="button"
                                        size="sm"
                                        :class="item.is_resolved ? 'bg-slate-200 text-slate-800 hover:bg-slate-300 dark:bg-zinc-800 dark:text-zinc-200' : 'bg-emerald-600 text-white hover:bg-emerald-700'"
                                        class="h-8 text-xs font-bold rounded-lg cursor-pointer"
                                    >
                                        <component :is="item.is_resolved ? RotateCcw : Check" class="h-3.5 w-3.5 mr-1" />
                                        {{ item.is_resolved ? t('adminSystemErrors.btnUnresolve') : t('adminSystemErrors.btnResolve') }}
                                    </Button>
                                </td>
                            </tr>
                        </template>
                        <template v-else>
                            <tr>
                                <td colspan="8" class="p-8 text-center text-slate-500 dark:text-zinc-400">
                                    <CheckCircle2 class="h-8 w-8 mx-auto mb-2 text-emerald-500 opacity-60" />
                                    <p class="font-medium text-xs">{{ t('adminSystemErrors.emptyList') }}</p>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            <div v-if="totalPages > 1" class="border-t border-slate-100 dark:border-zinc-800/80 p-4 flex items-center justify-between text-xs text-slate-500 dark:text-zinc-400">
                <span>Сторінка {{ currentPage }} з {{ totalPages }}</span>
                <div class="flex items-center gap-2">
                    <Button
                        @click="fetchLogs(currentPage - 1)"
                        type="button"
                        variant="outline"
                        size="sm"
                        class="h-8 rounded-lg cursor-pointer"
                        :disabled="currentPage <= 1 || isLoading"
                    >
                        <ChevronLeft class="h-4 w-4" />
                    </Button>
                    <Button
                        @click="fetchLogs(currentPage + 1)"
                        type="button"
                        variant="outline"
                        size="sm"
                        class="h-8 rounded-lg cursor-pointer"
                        :disabled="currentPage >= totalPages || isLoading"
                    >
                        <ChevronRight class="h-4 w-4" />
                    </Button>
                </div>
            </div>
        </div>

        <!-- Detailed Exception & Stack Trace Inspection Modal Dialog -->
        <Dialog :open="showDetailModal" @update:open="showDetailModal = $event">
            <DialogContent class="sm:max-w-3xl h-[85vh] max-h-[85vh] w-[95vw] sm:w-full flex flex-col rounded-3xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 p-6 shadow-2xl overflow-hidden">
                <DialogHeader class="space-y-2 pb-3 border-b border-slate-100 dark:border-zinc-800">
                    <div class="flex items-center justify-between pr-6">
                        <div class="flex items-center gap-2">
                            <span
                                class="px-2.5 py-1 rounded-md text-xs font-black border"
                                :class="getStatusCodeClass(activeDetailLog?.status_code || 500)"
                            >
                                {{ activeDetailLog?.status_code }}
                            </span>
                            <DialogTitle class="text-base font-extrabold text-slate-900 dark:text-white truncate">
                                {{ activeDetailLog?.exception_class }}
                            </DialogTitle>
                        </div>
                        <Button
                            @click="copyStackTrace"
                            type="button"
                            size="sm"
                            variant="outline"
                            class="h-8 text-xs font-bold rounded-lg cursor-pointer"
                        >
                            <Copy class="h-3.5 w-3.5 mr-1.5" />
                            {{ isCopied ? t('adminSystemErrors.traceCopied') : t('adminSystemErrors.copyTrace') }}
                        </Button>
                    </div>
                    <DialogDescription class="text-xs text-slate-500 dark:text-zinc-400">
                        Журнал помилки створено: {{ formatDate(activeDetailLog?.created_at) }}
                    </DialogDescription>
                </DialogHeader>

                <div v-if="activeDetailLog" class="flex-1 overflow-y-auto space-y-4 py-4 pr-1 text-xs">
                    <!-- Exception Message Banner -->
                    <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-950 dark:text-rose-200 font-semibold space-y-1">
                        <span class="text-[10px] uppercase font-black tracking-wider text-rose-600 dark:text-rose-400 block">
                            Повідомлення про помилку / Error Message:
                        </span>
                        <p class="text-sm font-bold leading-relaxed break-words">
                            {{ activeDetailLog.message }}
                        </p>
                    </div>

                    <!-- Metadata Overview Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-zinc-900 border border-slate-100 dark:border-zinc-800 space-y-1">
                            <span class="text-[10px] uppercase font-bold text-slate-400">URL & Method:</span>
                            <p class="font-mono text-xs text-slate-900 dark:text-white font-bold break-all">
                                {{ activeDetailLog.method }} {{ activeDetailLog.url }}
                            </p>
                        </div>

                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-zinc-900 border border-slate-100 dark:border-zinc-800 space-y-1">
                            <span class="text-[10px] uppercase font-bold text-slate-400">IP & User Agent:</span>
                            <p class="font-mono text-[11px] text-slate-700 dark:text-zinc-300 truncate">
                                {{ activeDetailLog.ip_address || '---' }} | {{ activeDetailLog.user_agent || '---' }}
                            </p>
                        </div>

                        <div v-if="activeDetailLog.user" class="p-3.5 rounded-xl bg-slate-50 dark:bg-zinc-900 border border-slate-100 dark:border-zinc-800 space-y-1">
                            <span class="text-[10px] uppercase font-bold text-slate-400">Користувач:</span>
                            <p class="font-bold text-slate-900 dark:text-white">
                                {{ activeDetailLog.user.first_name ? `${activeDetailLog.user.first_name} ${activeDetailLog.user.last_name || ''}` : activeDetailLog.user.email }}
                            </p>
                        </div>

                        <div v-if="activeDetailLog.is_resolved" class="p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 space-y-1">
                            <span class="text-[10px] uppercase font-bold text-emerald-600 dark:text-emerald-400">Статус вирішення:</span>
                            <p class="font-bold text-emerald-700 dark:text-emerald-300">
                                {{ t('adminSystemErrors.resolvedBy') }}: {{ activeDetailLog.resolver?.first_name || activeDetailLog.resolver?.email || 'Admin' }} ({{ formatDate(activeDetailLog.resolved_at) }})
                            </p>
                        </div>
                    </div>

                    <!-- Stack Trace Pre Code Block -->
                    <div class="space-y-1.5">
                        <span class="text-[11px] uppercase font-extrabold tracking-wider text-slate-500 dark:text-zinc-400">
                            Стек викликів / Stack Trace:
                        </span>
                        <pre class="p-4 rounded-2xl bg-slate-950 text-slate-200 font-mono text-[11px] leading-relaxed overflow-x-auto border border-zinc-800 selection:bg-main selection:text-black">{{ activeDetailLog.stack_trace || 'No stack trace available.' }}</pre>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
