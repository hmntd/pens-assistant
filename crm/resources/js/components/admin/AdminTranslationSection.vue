<script setup lang="ts">
import { Search, Globe, Plus, Save, X, Loader2 } from '@lucide/vue';
import { ref, onMounted, watch } from 'vue';
import { toast } from 'vue-sonner';
import { useI18n } from '@/composables/useI18n';
import type { TranslationItem } from '@/types';

const { t } = useI18n();

const translations = ref<TranslationItem[]>([]);
const isLoading = ref(false);
const searchQuery = ref('');

// Pagination
const currentPage = ref(1);
const lastPage = ref(1);
const totalRecords = ref(0);
const perPage = ref(25);

// Add New Key Modal
const showAddModal = ref(false);
const newKey = ref('');
const newUkVal = ref('');
const newEnVal = ref('');
const isSubmittingNewKey = ref(false);

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

async function fetchTranslations() {
    isLoading.value = true;

    try {
        const queryParams = new URLSearchParams({
            page: currentPage.value.toString(),
            per_page: perPage.value.toString(),
            search: searchQuery.value,
        });
        const res = await apiFetch(
            `/admin/translations?${queryParams.toString()}`,
        );
        const data = await res.json();

        if (res.ok && data.status === 'success') {
            const paged = data.data;
            translations.value = (paged.items || []).map((item: any) => ({
                ...item,
                is_saving: false,
            }));
            currentPage.value = paged.current_page || 1;
            lastPage.value = paged.last_page || 1;
            totalRecords.value = paged.total || 0;
        } else {
            toast.error(data.message || t('adminTranslations.errorFetch'));
        }
    } catch {
        toast.error(t('adminTranslations.networkErrorFetch'));
    } finally {
        isLoading.value = false;
    }
}

async function saveTranslationRow(item: TranslationItem) {
    item.is_saving = true;

    try {
        const res = await apiFetch('/admin/translations', {
            method: 'PUT',
            body: JSON.stringify({
                key: item.key,
                uk: item.uk,
                en: item.en,
            }),
        });
        const data = await res.json();

        if (res.ok && data.status === 'success') {
            toast.success(
                data.message ||
                    t('adminTranslations.successSave').replace(
                        ':key',
                        item.key,
                    ),
            );
        } else {
            toast.error(data.message || t('adminTranslations.errorSave'));
        }
    } catch {
        toast.error(t('adminTranslations.networkErrorSave'));
    } finally {
        item.is_saving = false;
    }
}

async function handleAddKey() {
    if (!newKey.value.trim()) {
        toast.error(t('adminTranslations.promptEnterKey'));

        return;
    }

    isSubmittingNewKey.value = true;

    try {
        const res = await apiFetch('/admin/translations', {
            method: 'POST',
            body: JSON.stringify({
                key: newKey.value.trim(),
                uk: newUkVal.value,
                en: newEnVal.value,
            }),
        });
        const data = await res.json();

        if (res.ok && data.status === 'success') {
            toast.success(data.message || t('adminTranslations.successAddKey'));
            showAddModal.value = false;
            newKey.value = '';
            newUkVal.value = '';
            newEnVal.value = '';
            fetchTranslations();
        } else {
            toast.error(data.message || t('adminTranslations.errorAddKey'));
        }
    } catch {
        toast.error(t('adminTranslations.networkErrorAddKey'));
    } finally {
        isSubmittingNewKey.value = false;
    }
}

let searchDebounce: any = null;
watch(searchQuery, () => {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => {
        currentPage.value = 1;
        fetchTranslations();
    }, 350);
});

onMounted(() => {
    fetchTranslations();
});
</script>

<template>
    <div class="space-y-6">
        <!-- Control Bar: Search & Add Key Button -->
        <div
            class="flex flex-col items-stretch justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-xs sm:flex-row sm:items-center dark:border-zinc-800 dark:bg-zinc-900"
        >
            <div class="relative max-w-md flex-1">
                <Search
                    class="absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-zinc-500"
                />
                <input
                    v-model="searchQuery"
                    type="text"
                    :placeholder="t('adminTranslations.searchPlaceholder')"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pr-4 pl-10 text-xs focus:ring-2 focus:ring-main focus:outline-none sm:text-sm dark:border-zinc-800 dark:bg-zinc-950"
                />
            </div>

            <button
                @click="showAddModal = true"
                class="flex shrink-0 cursor-pointer items-center justify-center gap-1.5 rounded-xl bg-main px-4 py-2 text-xs font-bold text-slate-950 shadow-xs transition-colors hover:bg-main-dark sm:text-sm"
            >
                <Plus class="h-4 w-4" />
                <span>{{ t('adminTranslations.btnAddKey') }}</span>
            </button>
        </div>

        <!-- Data Table Container -->
        <div
            class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900"
        >
            <div
                class="overflow-x-auto [&::-webkit-scrollbar]:h-1.5 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-slate-300 dark:[&::-webkit-scrollbar-thumb]:bg-zinc-800 [&::-webkit-scrollbar-track]:bg-transparent"
            >
                <table
                    class="w-full min-w-[700px] text-left text-xs sm:text-sm"
                >
                    <thead
                        class="border-b border-slate-200 bg-slate-50 text-[10px] font-extrabold tracking-wider text-slate-500 uppercase sm:text-[11px] dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-400"
                    >
                        <tr>
                            <th class="w-1/4 p-3.5">
                                {{ t('adminTranslations.columnKey') }}
                            </th>
                            <th class="w-1/3 p-3.5">
                                {{ t('adminTranslations.columnUk') }}
                            </th>
                            <th class="w-1/3 p-3.5">
                                {{ t('adminTranslations.columnEn') }}
                            </th>
                            <th class="p-3.5 text-right">
                                {{ t('adminTranslations.columnActions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-slate-100 font-medium dark:divide-zinc-800/80"
                    >
                        <tr v-if="isLoading">
                            <td
                                colspan="4"
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
                                            'adminTranslations.loadingTranslations',
                                        )
                                    }}</span>
                                </div>
                            </td>
                        </tr>
                        <tr v-else-if="translations.length === 0">
                            <td
                                colspan="4"
                                class="p-8 text-center text-slate-400"
                            >
                                {{ t('adminTranslations.noTranslationsFound') }}
                            </td>
                        </tr>
                        <tr
                            v-for="item in translations"
                            :key="item.key"
                            class="transition-colors hover:bg-slate-50/80 dark:hover:bg-zinc-950/60"
                        >
                            <td
                                class="p-3.5 font-mono text-xs font-bold break-all text-slate-900 dark:text-white"
                            >
                                {{ item.key }}
                            </td>
                            <td class="p-3.5">
                                <textarea
                                    v-model="item.uk"
                                    rows="2"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs focus:ring-1 focus:ring-main focus:outline-none dark:border-zinc-800 dark:bg-zinc-950"
                                ></textarea>
                            </td>
                            <td class="p-3.5">
                                <textarea
                                    v-model="item.en"
                                    rows="2"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 p-2 text-xs focus:ring-1 focus:ring-main focus:outline-none dark:border-zinc-800 dark:bg-zinc-950"
                                ></textarea>
                            </td>
                            <td class="p-3.5 text-right">
                                <button
                                    @click="saveTranslationRow(item)"
                                    :disabled="item.is_saving"
                                    :title="t('adminTranslations.btnSave')"
                                    class="ml-auto flex cursor-pointer items-center justify-center gap-1.5 rounded-xl bg-main px-3 py-1.5 text-xs font-bold text-slate-950 hover:bg-main-dark disabled:opacity-50"
                                >
                                    <Loader2
                                        v-if="item.is_saving"
                                        class="h-3.5 w-3.5 animate-spin"
                                    />
                                    <Save v-else class="h-3.5 w-3.5" />
                                    <span>{{
                                        item.is_saving
                                            ? t('adminTranslations.btnSaving')
                                            : t('adminTranslations.btnSave')
                                    }}</span>
                                </button>
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
                    {{ t('adminTranslations.totalKeys') }}
                    <span class="font-bold text-slate-900 dark:text-white">{{
                        totalRecords
                    }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        :disabled="currentPage <= 1"
                        @click="
                            currentPage--;
                            fetchTranslations();
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
                            fetchTranslations();
                        "
                        class="cursor-pointer rounded-lg border border-slate-200 px-3 py-1.5 disabled:opacity-40 dark:border-zinc-800"
                    >
                        {{ t('adminUsers.pageNext') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Add New Key Modal -->
        <div
            v-if="showAddModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs"
        >
            <div
                class="relative w-full max-w-lg space-y-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-zinc-800 dark:bg-zinc-950"
            >
                <button
                    @click="showAddModal = false"
                    class="absolute top-5 right-5 cursor-pointer text-slate-400 hover:text-slate-900 dark:hover:text-white"
                >
                    <X class="h-5 w-5" />
                </button>

                <div
                    class="flex items-center gap-2 text-main-dark dark:text-main"
                >
                    <Globe class="h-6 w-6 shrink-0 text-main" />
                    <h3
                        class="text-base font-extrabold text-slate-900 dark:text-white"
                    >
                        {{ t('adminTranslations.modalTitle') }}
                    </h3>
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <label class="mb-1 block font-bold text-slate-500">{{
                            t('adminTranslations.labelKey')
                        }}</label>
                        <input
                            v-model="newKey"
                            type="text"
                            placeholder="admin.newKey"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 font-mono focus:ring-2 focus:ring-main focus:outline-none dark:border-zinc-800 dark:bg-zinc-900"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block font-bold text-slate-500">{{
                            t('adminTranslations.labelUk')
                        }}</label>
                        <textarea
                            v-model="newUkVal"
                            rows="3"
                            :placeholder="t('adminTranslations.placeholderUk')"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 focus:ring-2 focus:ring-main focus:outline-none dark:border-zinc-800 dark:bg-zinc-900"
                        ></textarea>
                    </div>
                    <div>
                        <label class="mb-1 block font-bold text-slate-500">{{
                            t('adminTranslations.labelEn')
                        }}</label>
                        <textarea
                            v-model="newEnVal"
                            rows="3"
                            :placeholder="t('adminTranslations.placeholderEn')"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 focus:ring-2 focus:ring-main focus:outline-none dark:border-zinc-800 dark:bg-zinc-900"
                        ></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button
                        @click="showAddModal = false"
                        type="button"
                        class="cursor-pointer rounded-xl px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100"
                    >
                        {{ t('adminTranslations.btnCancel') }}
                    </button>
                    <button
                        @click="handleAddKey()"
                        :disabled="isSubmittingNewKey"
                        type="button"
                        class="flex cursor-pointer items-center gap-1.5 rounded-xl bg-main px-4 py-2 text-xs font-bold text-slate-950 hover:bg-main-dark disabled:opacity-50"
                    >
                        <Loader2
                            v-if="isSubmittingNewKey"
                            class="h-3.5 w-3.5 animate-spin"
                        />
                        <span>{{
                            isSubmittingNewKey
                                ? t('adminTranslations.btnSaving')
                                : t('adminTranslations.btnAdd')
                        }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
