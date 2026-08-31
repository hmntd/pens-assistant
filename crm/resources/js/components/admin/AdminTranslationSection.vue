<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { toast } from 'vue-sonner';
import { useI18n } from '@/composables/useI18n';
import {
    Search,
    Globe,
    Plus,
    Save,
    X,
    Loader2
} from '@lucide/vue';

const { t } = useI18n();

interface TranslationItem {
    key: string;
    uk: string;
    en: string;
    is_saving?: boolean;
}

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

async function fetchTranslations() {
    isLoading.value = true;
    try {
        const queryParams = new URLSearchParams({
            page: currentPage.value.toString(),
            per_page: perPage.value.toString(),
            search: searchQuery.value,
        });
        const res = await apiFetch(`/admin/translations?${queryParams.toString()}`);
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
            toast.error(data.message || 'Помилка завантаження перекладів.');
        }
    } catch (err: any) {
        toast.error('Помилка мережі при завантаженні перекладів.');
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
            toast.success(data.message || `Переклад "${item.key}" збережено.`);
        } else {
            toast.error(data.message || 'Помилка збереження перекладу.');
        }
    } catch (err: any) {
        toast.error('Помилка мережі при збереженні.');
    } finally {
        item.is_saving = false;
    }
}

async function handleAddKey() {
    if (!newKey.value.trim()) {
        toast.error('Будь ласка, введіть ключ перекладу.');
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
            toast.success(data.message || 'Новий ключ перекладу додано.');
            showAddModal.value = false;
            newKey.value = '';
            newUkVal.value = '';
            newEnVal.value = '';
            fetchTranslations();
        } else {
            toast.error(data.message || 'Помилка створення ключа перекладу.');
        }
    } catch (err: any) {
        toast.error('Помилка мережі при створенні ключа.');
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
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 bg-white dark:bg-zinc-900 p-4 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-xs">
            <div class="relative flex-1 max-w-md">
                <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 dark:text-zinc-500" />
                <input
                    v-model="searchQuery"
                    type="text"
                    :placeholder="t('adminTranslations.searchPlaceholder')"
                    class="w-full pl-10 pr-4 py-2 text-xs sm:text-sm bg-slate-50 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl focus:outline-none focus:ring-2 focus:ring-main"
                />
            </div>

            <button
                @click="showAddModal = true"
                class="px-4 py-2 text-xs sm:text-sm font-bold bg-main text-slate-950 hover:bg-main-dark rounded-xl flex items-center justify-center gap-1.5 cursor-pointer shadow-xs transition-colors shrink-0"
            >
                <Plus class="h-4 w-4" />
                <span>{{ t('adminTranslations.btnAddKey') }}</span>
            </button>
        </div>

        <!-- Data Table Container -->
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-xs overflow-hidden">
            <div class="overflow-x-auto [&::-webkit-scrollbar]:h-1.5 [&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar-thumb]:bg-slate-300 dark:[&::-webkit-scrollbar-thumb]:bg-zinc-800 [&::-webkit-scrollbar-thumb]:rounded-full">
                <table class="w-full text-left text-xs sm:text-sm min-w-[700px]">
                    <thead class="bg-slate-50 dark:bg-zinc-950 text-slate-500 dark:text-zinc-400 font-extrabold uppercase tracking-wider text-[10px] sm:text-[11px] border-b border-slate-200 dark:border-zinc-800">
                        <tr>
                            <th class="p-3.5 w-1/4">{{ t('adminTranslations.columnKey') }}</th>
                            <th class="p-3.5 w-1/3">{{ t('adminTranslations.columnUk') }}</th>
                            <th class="p-3.5 w-1/3">{{ t('adminTranslations.columnEn') }}</th>
                            <th class="p-3.5 text-right">{{ t('adminTranslations.columnActions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/80 font-medium">
                        <tr v-if="isLoading">
                            <td colspan="4" class="p-8 text-center text-slate-400">
                                <div class="flex items-center justify-center gap-2">
                                    <Loader2 class="h-5 w-5 animate-spin text-main" />
                                    <span>{{ t('adminTranslations.loadingTranslations') }}</span>
                                </div>
                            </td>
                        </tr>
                        <tr v-else-if="translations.length === 0">
                            <td colspan="4" class="p-8 text-center text-slate-400">
                                {{ t('adminTranslations.noTranslationsFound') }}
                            </td>
                        </tr>
                        <tr v-for="item in translations" :key="item.key" class="hover:bg-slate-50/80 dark:hover:bg-zinc-950/60 transition-colors">
                            <td class="p-3.5 font-bold font-mono text-slate-900 dark:text-white text-xs break-all">
                                {{ item.key }}
                            </td>
                            <td class="p-3.5">
                                <textarea
                                    v-model="item.uk"
                                    rows="2"
                                    class="w-full p-2 text-xs bg-slate-50 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl focus:outline-none focus:ring-1 focus:ring-main"
                                ></textarea>
                            </td>
                            <td class="p-3.5">
                                <textarea
                                    v-model="item.en"
                                    rows="2"
                                    class="w-full p-2 text-xs bg-slate-50 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl focus:outline-none focus:ring-1 focus:ring-main"
                                ></textarea>
                            </td>
                            <td class="p-3.5 text-right">
                                <button
                                    @click="saveTranslationRow(item)"
                                    :disabled="item.is_saving"
                                    :title="t('adminTranslations.btnSave')"
                                    class="px-3 py-1.5 rounded-xl bg-main text-slate-950 font-bold hover:bg-main-dark flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50 text-xs ml-auto"
                                >
                                    <Loader2 v-if="item.is_saving" class="h-3.5 w-3.5 animate-spin" />
                                    <Save v-else class="h-3.5 w-3.5" />
                                    <span>{{ item.is_saving ? t('adminTranslations.btnSaving') : t('adminTranslations.btnSave') }}</span>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            <div class="flex items-center justify-between p-4 border-t border-slate-200 dark:border-zinc-800 text-xs text-slate-500">
                <div>{{ t('adminTranslations.totalKeys') }} <span class="font-bold text-slate-900 dark:text-white">{{ totalRecords }}</span></div>
                <div class="flex items-center gap-2">
                    <button
                        :disabled="currentPage <= 1"
                        @click="currentPage--; fetchTranslations();"
                        class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-zinc-800 disabled:opacity-40 cursor-pointer"
                    >
                        {{ t('adminUsers.pagePrev') }}
                    </button>
                    <span>{{ t('adminUsers.pageWord') }} {{ currentPage }} {{ t('adminUsers.pageOf') }} {{ lastPage }}</span>
                    <button
                        :disabled="currentPage >= lastPage"
                        @click="currentPage++; fetchTranslations();"
                        class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-zinc-800 disabled:opacity-40 cursor-pointer"
                    >
                        {{ t('adminUsers.pageNext') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Add New Key Modal -->
        <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
            <div class="bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-3xl w-full max-w-lg p-6 shadow-2xl space-y-4 relative">
                <button @click="showAddModal = false" class="absolute right-5 top-5 text-slate-400 hover:text-slate-900 dark:hover:text-white cursor-pointer">
                    <X class="h-5 w-5" />
                </button>

                <div class="flex items-center gap-2 text-main-dark dark:text-main">
                    <Globe class="h-6 w-6 text-main shrink-0" />
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-white">{{ t('adminTranslations.modalTitle') }}</h3>
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <label class="block text-slate-500 font-bold mb-1">{{ t('adminTranslations.labelKey') }}</label>
                        <input
                            v-model="newKey"
                            type="text"
                            placeholder="admin.newKey"
                            class="w-full p-2.5 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl font-mono focus:outline-none focus:ring-2 focus:ring-main"
                        />
                    </div>
                    <div>
                        <label class="block text-slate-500 font-bold mb-1">{{ t('adminTranslations.labelUk') }}</label>
                        <textarea
                            v-model="newUkVal"
                            rows="3"
                            :placeholder="t('adminTranslations.placeholderUk')"
                            class="w-full p-2.5 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl focus:outline-none focus:ring-2 focus:ring-main"
                        ></textarea>
                    </div>
                    <div>
                        <label class="block text-slate-500 font-bold mb-1">{{ t('adminTranslations.labelEn') }}</label>
                        <textarea
                            v-model="newEnVal"
                            rows="3"
                            :placeholder="t('adminTranslations.placeholderEn')"
                            class="w-full p-2.5 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl focus:outline-none focus:ring-2 focus:ring-main"
                        ></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button @click="showAddModal = false" type="button" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl cursor-pointer">
                        {{ t('adminTranslations.btnCancel') }}
                    </button>
                    <button
                        @click="handleAddKey()"
                        :disabled="isSubmittingNewKey"
                        type="button"
                        class="px-4 py-2 text-xs font-bold bg-main text-slate-950 hover:bg-main-dark rounded-xl cursor-pointer disabled:opacity-50 flex items-center gap-1.5"
                    >
                        <Loader2 v-if="isSubmittingNewKey" class="h-3.5 w-3.5 animate-spin" />
                        <span>{{ isSubmittingNewKey ? t('adminTranslations.btnSaving') : t('adminTranslations.btnAdd') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
