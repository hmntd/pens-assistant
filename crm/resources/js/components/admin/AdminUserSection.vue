<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { useI18n } from '@/composables/useI18n';
import {
    Search,
    Shield,
    UserCheck,
    Ban,
    Trash2,
    RotateCcw,
    Eye,
    ChevronUp,
    ChevronDown,
    User as UserIcon,
    Calendar,
    FileText,
    Calculator,
    CheckCircle2,
    XCircle,
    X,
    Loader2
} from '@lucide/vue';

const { t } = useI18n();

interface UserItem {
    id: number;
    first_name: string;
    last_name: string;
    name: string;
    email: string;
    role: string;
    is_admin: boolean;
    is_suspended: boolean;
    is_trashed: boolean;
    created_at: string;
    deleted_at?: string | null;
}

interface UserDetail extends UserItem {
    gender?: string | null;
    date_of_birth?: string | null;
    disability_group?: string | null;
    benefits?: string[];
    target_retirement_year?: number | null;
    calculations_count?: number;
    documents_count?: number;
    tax_histories_count?: number;
    updated_at?: string;
}

const users = ref<UserItem[]>([]);
const isLoading = ref(false);
const searchQuery = ref('');
const roleFilter = ref('');
const statusFilter = ref('');
const sortBy = ref('id');
const sortDir = ref<'asc' | 'desc'>('desc');

// Pagination
const currentPage = ref(1);
const lastPage = ref(1);
const totalRecords = ref(0);
const perPage = ref(15);

// Details Slide-Over / Modal
const showDetailModal = ref(false);
const selectedUser = ref<UserDetail | null>(null);
const isLoadingDetail = ref(false);

// Confirm Action Modal
const showConfirmModal = ref(false);
const confirmActionType = ref<'delete' | 'restore' | 'suspend'>('delete');
const targetUser = ref<UserItem | null>(null);
const isProcessingAction = ref(false);

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

async function fetchUsers() {
    isLoading.value = true;
    try {
        const queryParams = new URLSearchParams({
            page: currentPage.value.toString(),
            per_page: perPage.value.toString(),
            search: searchQuery.value,
            role: roleFilter.value,
            status: statusFilter.value,
            sort_by: sortBy.value,
            sort_dir: sortDir.value,
        });
        const res = await apiFetch(`/admin/users?${queryParams.toString()}`);
        const data = await res.json();
        if (res.ok && data.status === 'success') {
            const paginated = data.data;
            users.value = paginated.data || [];
            currentPage.value = paginated.current_page || 1;
            lastPage.value = paginated.last_page || 1;
            totalRecords.value = paginated.total || 0;
        } else {
            toast.error(data.message || 'Помилка завантаження користувачів.');
        }
    } catch (err: any) {
        toast.error('Помилка мережі при завантаженні користувачів.');
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
    fetchUsers();
}

async function viewUserDetails(user: UserItem) {
    showDetailModal.value = true;
    isLoadingDetail.value = true;
    selectedUser.value = null;
    try {
        const res = await apiFetch(`/admin/users/${user.id}`);
        const data = await res.json();
        if (res.ok && data.status === 'success') {
            selectedUser.value = data.data;
        } else {
            toast.error('Не вдалося завантажити деталі користувача.');
            showDetailModal.value = false;
        }
    } catch (err: any) {
        toast.error('Помилка завантаження деталей користувача.');
        showDetailModal.value = false;
    } finally {
        isLoadingDetail.value = false;
    }
}

async function handleRoleChange(user: UserItem, newRole: string) {
    if (user.id === currentUserId.value) {
        toast.error('Ви не можете змінити власну роль.');
        fetchUsers();
        return;
    }
    try {
        const res = await apiFetch(`/admin/users/${user.id}/role`, {
            method: 'PUT',
            body: JSON.stringify({ role: newRole }),
        });
        const data = await res.json();
        if (res.ok && data.status === 'success') {
            user.role = newRole;
            user.is_admin = newRole === 'admin';
            toast.success(data.message || `Роль користувача оновлено на ${newRole}.`);
        } else {
            toast.error(data.message || 'Помилка оновлення ролі.');
            fetchUsers();
        }
    } catch (err: any) {
        toast.error('Помилка мережі.');
        fetchUsers();
    }
}

function openConfirmModal(user: UserItem, action: 'delete' | 'restore' | 'suspend') {
    targetUser.value = user;
    confirmActionType.value = action;
    showConfirmModal.value = true;
}

async function executeConfirmedAction() {
    if (!targetUser.value) return;
    isProcessingAction.value = true;
    try {
        let res: Response;
        if (confirmActionType.value === 'suspend') {
            res = await apiFetch(`/admin/users/${targetUser.value.id}/toggle-suspend`, { method: 'POST' });
        } else if (confirmActionType.value === 'delete') {
            res = await apiFetch(`/admin/users/${targetUser.value.id}`, { method: 'DELETE' });
        } else {
            res = await apiFetch(`/admin/users/${targetUser.value.id}/restore`, { method: 'POST' });
        }
        const data = await res.json();
        if (res.ok && data.status === 'success') {
            toast.success(data.message);
            fetchUsers();
        } else {
            toast.error(data.message || 'Помилка виконання операції.');
        }
    } catch (err: any) {
        toast.error('Помилка мережі при виконанні операції.');
    } finally {
        isProcessingAction.value = false;
        showConfirmModal.value = false;
        targetUser.value = null;
    }
}

let searchDebounceTimer: any = null;
watch(searchQuery, () => {
    clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(() => {
        currentPage.value = 1;
        fetchUsers();
    }, 350);
});

watch([roleFilter, statusFilter], () => {
    currentPage.value = 1;
    fetchUsers();
});

onMounted(() => {
    fetchUsers();
});
</script>

<template>
    <div class="space-y-6">
        <!-- Control Bar: Search & Filters -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 bg-white dark:bg-zinc-900 p-4 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-xs">
            <div class="relative flex-1 max-w-md">
                <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 dark:text-zinc-500" />
                <input
                    v-model="searchQuery"
                    type="text"
                    :placeholder="t('adminUsers.searchPlaceholder')"
                    class="w-full pl-10 pr-4 py-2 text-xs sm:text-sm bg-slate-50 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl focus:outline-none focus:ring-2 focus:ring-main"
                />
            </div>

            <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                <!-- Role Filter -->
                <select
                    v-model="roleFilter"
                    class="px-3 py-2 text-xs sm:text-sm bg-slate-50 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl font-medium cursor-pointer"
                >
                    <option value="">{{ t('adminUsers.allRoles') }}</option>
                    <option value="admin">{{ t('adminUsers.administrators') }}</option>
                    <option value="user">{{ t('adminUsers.users') }}</option>
                </select>

                <!-- Status Filter -->
                <select
                    v-model="statusFilter"
                    class="px-3 py-2 text-xs sm:text-sm bg-slate-50 dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl font-medium cursor-pointer"
                >
                    <option value="">{{ t('adminUsers.allStatuses') }}</option>
                    <option value="active">{{ t('adminUsers.active') }}</option>
                    <option value="suspended">{{ t('adminUsers.suspended') }}</option>
                    <option value="trashed">{{ t('adminUsers.trashed') }}</option>
                </select>
            </div>
        </div>

        <!-- Data Table Container -->
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-slate-200 dark:border-zinc-800 shadow-xs overflow-hidden">
            <div class="overflow-x-auto [&::-webkit-scrollbar]:h-1.5 [&::-webkit-scrollbar-track]:bg-transparent [&::-webkit-scrollbar-thumb]:bg-slate-300 dark:[&::-webkit-scrollbar-thumb]:bg-zinc-800 [&::-webkit-scrollbar-thumb]:rounded-full">
                <table class="w-full text-left text-xs sm:text-sm min-w-[700px]">
                    <thead class="bg-slate-50 dark:bg-zinc-950 text-slate-500 dark:text-zinc-400 font-extrabold uppercase tracking-wider text-[10px] sm:text-[11px] border-b border-slate-200 dark:border-zinc-800">
                        <tr>
                            <th @click="handleSort('id')" class="p-3.5 cursor-pointer hover:text-slate-900 dark:hover:text-white transition-colors">
                                <div class="flex items-center gap-1">
                                    <span>{{ t('adminUsers.columnId') }}</span>
                                    <ChevronUp v-if="sortBy === 'id' && sortDir === 'asc'" class="h-3.5 w-3.5" />
                                    <ChevronDown v-if="sortBy === 'id' && sortDir === 'desc'" class="h-3.5 w-3.5" />
                                </div>
                            </th>
                            <th class="p-3.5">{{ t('adminUsers.columnUser') }}</th>
                            <th @click="handleSort('email')" class="p-3.5 cursor-pointer hover:text-slate-900 dark:hover:text-white transition-colors">
                                <div class="flex items-center gap-1">
                                    <span>{{ t('adminUsers.columnEmail') }}</span>
                                    <ChevronUp v-if="sortBy === 'email' && sortDir === 'asc'" class="h-3.5 w-3.5" />
                                    <ChevronDown v-if="sortBy === 'email' && sortDir === 'desc'" class="h-3.5 w-3.5" />
                                </div>
                            </th>
                            <th class="p-3.5">{{ t('adminUsers.columnRole') }}</th>
                            <th class="p-3.5">{{ t('adminUsers.columnStatus') }}</th>
                            <th @click="handleSort('created_at')" class="p-3.5 cursor-pointer hover:text-slate-900 dark:hover:text-white transition-colors">
                                <div class="flex items-center gap-1">
                                    <span>{{ t('adminUsers.columnRegistrationDate') }}</span>
                                    <ChevronUp v-if="sortBy === 'created_at' && sortDir === 'asc'" class="h-3.5 w-3.5" />
                                    <ChevronDown v-if="sortBy === 'created_at' && sortDir === 'desc'" class="h-3.5 w-3.5" />
                                </div>
                            </th>
                            <th class="p-3.5 text-right">{{ t('adminUsers.columnActions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-zinc-800/80 font-medium">
                        <tr v-if="isLoading">
                            <td colspan="7" class="p-8 text-center text-slate-400">
                                <div class="flex items-center justify-center gap-2">
                                    <Loader2 class="h-5 w-5 animate-spin text-main" />
                                    <span>{{ t('adminUsers.loadingUsers') }}</span>
                                </div>
                            </td>
                        </tr>
                        <tr v-else-if="users.length === 0">
                            <td colspan="7" class="p-8 text-center text-slate-400">
                                {{ t('adminUsers.noUsersFound') }}
                            </td>
                        </tr>
                        <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50/80 dark:hover:bg-zinc-950/60 transition-colors" :class="{ 'opacity-60 bg-red-50/20 dark:bg-red-950/10': user.is_trashed }">
                            <td class="p-3.5 font-bold font-mono text-slate-500">#{{ user.id }}</td>
                            <td class="p-3.5">
                                <div class="flex items-center gap-3">
                                    <!-- Avatar initials -->
                                    <div class="h-8 w-8 rounded-full bg-main/20 text-main-dark dark:text-main flex items-center justify-center font-bold text-xs shrink-0 border border-main/30">
                                        {{ user.first_name ? user.first_name.charAt(0).toUpperCase() : 'U' }}{{ user.last_name ? user.last_name.charAt(0).toUpperCase() : '' }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 dark:text-white">{{ user.name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3.5 font-mono text-slate-600 dark:text-zinc-300">{{ user.email }}</td>
                            <td class="p-3.5">
                                <select
                                    :value="user.role"
                                    :disabled="user.id === currentUserId"
                                    :title="user.id === currentUserId ? t('adminUsers.cannotBlockSelf') : t('adminUsers.columnRole')"
                                    @change="handleRoleChange(user, ($event.target as HTMLSelectElement).value)"
                                    class="px-2.5 py-1 text-xs font-bold rounded-lg border border-slate-200 dark:border-zinc-800 bg-slate-50 dark:bg-zinc-950 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                                    :class="user.role === 'admin' ? 'text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 border-amber-200 dark:border-amber-900' : 'text-slate-700 dark:text-zinc-300'"
                                >
                                    <option value="user">{{ t('adminUsers.roleUser') }}</option>
                                    <option value="admin">{{ t('adminUsers.roleAdmin') }}</option>
                                </select>
                            </td>
                            <td class="p-3.5">
                                <span v-if="user.is_trashed" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300">
                                    <XCircle class="h-3 w-3" /> {{ t('adminUsers.statusTrashed') }}
                                </span>
                                <span v-else-if="user.is_suspended" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                                    <Ban class="h-3 w-3" /> {{ t('adminUsers.statusSuspended') }}
                                </span>
                                <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                                    <CheckCircle2 class="h-3 w-3" /> {{ t('adminUsers.statusActive') }}
                                </span>
                            </td>
                            <td class="p-3.5 text-slate-500 font-mono text-xs">{{ user.created_at }}</td>
                            <td class="p-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- View Details -->
                                    <button @click="viewUserDetails(user)" :title="t('adminUsers.titleViewDetails')" class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-zinc-800 text-slate-600 dark:text-zinc-400 cursor-pointer">
                                        <Eye class="h-4 w-4" />
                                    </button>

                                    <!-- Suspend / Ban Toggle -->
                                    <button
                                        @click="openConfirmModal(user, 'suspend')"
                                        :disabled="user.id === currentUserId"
                                        :title="user.id === currentUserId ? t('adminUsers.cannotBlockSelf') : (user.is_suspended ? t('adminUsers.titleUnblock') : t('adminUsers.titleBlock'))"
                                        class="p-1.5 rounded-lg hover:bg-amber-50 dark:hover:bg-amber-950/50 text-amber-600 dark:text-amber-400 cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed"
                                    >
                                        <Ban class="h-4 w-4" />
                                    </button>

                                    <!-- Soft Delete or Restore -->
                                    <button v-if="user.is_trashed" @click="openConfirmModal(user, 'restore')" :title="t('adminUsers.titleRestore')" class="p-1.5 rounded-lg hover:bg-emerald-50 dark:hover:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 cursor-pointer">
                                        <RotateCcw class="h-4 w-4" />
                                    </button>
                                    <button
                                        v-else
                                        @click="openConfirmModal(user, 'delete')"
                                        :disabled="user.id === currentUserId"
                                        :title="user.id === currentUserId ? t('adminUsers.cannotDeleteSelf') : t('adminUsers.titleSoftDelete')"
                                        class="p-1.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-950/50 text-red-600 dark:text-red-400 cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed"
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
            <div class="flex items-center justify-between p-4 border-t border-slate-200 dark:border-zinc-800 text-xs text-slate-500">
                <div>{{ t('adminUsers.totalRecords') }} <span class="font-bold text-slate-900 dark:text-white">{{ totalRecords }}</span></div>
                <div class="flex items-center gap-2">
                    <button
                        :disabled="currentPage <= 1"
                        @click="currentPage--; fetchUsers();"
                        class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-zinc-800 disabled:opacity-40 cursor-pointer"
                    >
                        {{ t('adminUsers.pagePrev') }}
                    </button>
                    <span>{{ t('adminUsers.pageWord') }} {{ currentPage }} {{ t('adminUsers.pageOf') }} {{ lastPage }}</span>
                    <button
                        :disabled="currentPage >= lastPage"
                        @click="currentPage++; fetchUsers();"
                        class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-zinc-800 disabled:opacity-40 cursor-pointer"
                    >
                        {{ t('adminUsers.pageNext') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Extended User Details Slide-Over Modal -->
        <div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
            <div class="bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-3xl w-full max-w-xl p-6 shadow-2xl space-y-6 relative max-h-[90vh] overflow-y-auto">
                <button @click="showDetailModal = false" class="absolute right-5 top-5 text-slate-400 hover:text-slate-900 dark:hover:text-white cursor-pointer">
                    <X class="h-5 w-5" />
                </button>

                <div class="flex items-center gap-3">
                    <div class="h-12 w-12 rounded-2xl bg-main/20 text-main-dark dark:text-main flex items-center justify-center font-bold text-lg border border-main/30">
                        {{ selectedUser?.first_name ? selectedUser.first_name.charAt(0).toUpperCase() : 'U' }}
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-slate-900 dark:text-white">{{ selectedUser?.name }}</h3>
                        <p class="text-xs font-mono text-slate-500">{{ selectedUser?.email }}</p>
                    </div>
                </div>

                <div v-if="isLoadingDetail" class="py-8 text-center text-slate-400">
                    <Loader2 class="h-6 w-6 animate-spin text-main mx-auto mb-2" />
                    <span>{{ t('adminUsers.loadingData') }}</span>
                </div>

                <div v-else-if="selectedUser" class="space-y-4 text-xs">
                    <div class="grid grid-cols-2 gap-3 p-4 rounded-2xl bg-slate-50 dark:bg-zinc-900 border border-slate-100 dark:border-zinc-800">
                        <div>
                            <span class="text-slate-400 block">{{ t('adminUsers.genderLabel') }}</span>
                            <span class="font-bold text-slate-900 dark:text-white">{{ selectedUser.gender === 'FEMALE' ? t('adminUsers.genderFemale') : (selectedUser.gender === 'MALE' ? t('adminUsers.genderMale') : t('adminUsers.genderNotSpecified')) }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block">{{ t('adminUsers.dobLabel') }}</span>
                            <span class="font-bold text-slate-900 dark:text-white">{{ selectedUser.date_of_birth || t('adminUsers.genderNotSpecified') }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block">{{ t('adminUsers.disabilityGroupLabel') }}</span>
                            <span class="font-bold text-slate-900 dark:text-white">{{ selectedUser.disability_group || t('adminUsers.disabilityNone') }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block">{{ t('adminUsers.retirementYearLabel') }}</span>
                            <span class="font-bold text-slate-900 dark:text-white">{{ selectedUser.target_retirement_year || t('adminUsers.retirementYearCurrent') }}</span>
                        </div>
                    </div>

                    <!-- Usage Statistics -->
                    <div class="grid grid-cols-3 gap-3">
                        <div class="p-3 rounded-2xl bg-main/10 border border-main/20 text-center">
                            <Calculator class="h-5 w-5 text-main-dark dark:text-main mx-auto mb-1" />
                            <div class="text-base font-black text-slate-900 dark:text-white">{{ selectedUser.calculations_count || 0 }}</div>
                            <span class="text-[10px] text-slate-500">{{ t('adminUsers.statCalculations') }}</span>
                        </div>
                        <div class="p-3 rounded-2xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-900 text-center">
                            <FileText class="h-5 w-5 text-blue-600 dark:text-blue-400 mx-auto mb-1" />
                            <div class="text-base font-black text-slate-900 dark:text-white">{{ selectedUser.documents_count || 0 }}</div>
                            <span class="text-[10px] text-slate-500">{{ t('adminUsers.statDocuments') }}</span>
                        </div>
                        <div class="p-3 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900 text-center">
                            <Calendar class="h-5 w-5 text-amber-600 dark:text-amber-400 mx-auto mb-1" />
                            <div class="text-base font-black text-slate-900 dark:text-white">{{ selectedUser.tax_histories_count || 0 }}</div>
                            <span class="text-[10px] text-slate-500">{{ t('adminUsers.statTaxHistories') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirm Action Modal -->
        <div v-if="showConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
            <div class="bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-3xl w-full max-w-md p-6 shadow-2xl space-y-4">
                <h3 class="text-base font-extrabold text-slate-900 dark:text-white">
                    <template v-if="confirmActionType === 'delete'">{{ t('adminUsers.modalDeleteTitle') }}</template>
                    <template v-else-if="confirmActionType === 'restore'">{{ t('adminUsers.modalRestoreTitle') }}</template>
                    <template v-else-if="confirmActionType === 'suspend'">{{ t('adminUsers.modalSuspendTitle') }}</template>
                </h3>
                <p class="text-xs text-slate-500 leading-relaxed">
                    {{ t('adminUsers.modalConfirmText') }} <span class="font-bold text-slate-900 dark:text-white">{{ targetUser?.name }}</span>?
                </p>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button @click="showConfirmModal = false" type="button" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl cursor-pointer">
                        {{ t('adminUsers.btnCancel') }}
                    </button>
                    <button
                        @click="executeConfirmedAction()"
                        :disabled="isProcessingAction"
                        type="button"
                        class="px-4 py-2 text-xs font-bold bg-main text-slate-950 hover:bg-main-dark rounded-xl cursor-pointer disabled:opacity-50"
                    >
                        {{ isProcessingAction ? t('adminUsers.btnExecuting') : t('adminUsers.btnConfirm') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
