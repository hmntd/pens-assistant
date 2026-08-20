<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { Bell, CheckCheck, FileText, Calculator, Info, CheckCircle2, AlertCircle } from '@lucide/vue';
import NotificationBadge from '../atoms/NotificationBadge.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

export interface DbNotificationItem {
    id: number;
    user_id: number;
    type: 'success' | 'error' | 'info' | 'warning' | string;
    is_seen: boolean;
    created_at?: string;
    translations?: {
        uk: string;
        en: string;
    };
}

const { t, locale } = useI18n();

const notifications = ref<DbNotificationItem[]>([]);
const unreadCount = ref(0);

async function fetchNotifications() {
    try {
        const response = await fetch('/notifications');
        if (response.ok) {
            const data = await response.json();
            notifications.value = data.notifications || [];
            unreadCount.value = data.unread_count || 0;
        }
    } catch (e) {
        console.error('Failed to fetch notifications', e);
    }
}

async function markAllAsRead() {
    try {
        await fetch('/notifications/mark-all-read', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                'Accept': 'application/json',
            },
        });
    } catch (e) {
        // silent fallback
    }
    notifications.value.forEach((n) => (n.is_seen = true));
    unreadCount.value = 0;
}

async function toggleRead(item: DbNotificationItem) {
    if (item.is_seen) return;
    item.is_seen = true;
    unreadCount.value = Math.max(0, unreadCount.value - 1);
    try {
        await fetch(`/notifications/${item.id}/mark-read`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                'Accept': 'application/json',
            },
        });
    } catch (e) {
        // silent fallback
    }
}

function getIcon(type: string) {
    switch (type) {
        case 'success':
            return CheckCircle2;
        case 'error':
            return AlertCircle;
        case 'document':
            return FileText;
        case 'calculation':
            return Calculator;
        default:
            return Info;
    }
}

function formatTime(dateStr?: string) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

onMounted(() => {
    fetchNotifications();
    window.addEventListener('notification-created', fetchNotifications);
});

onUnmounted(() => {
    window.removeEventListener('notification-created', fetchNotifications);
});
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <button
                type="button"
                class="relative flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200/80 bg-white/70 text-slate-700 shadow-sm backdrop-blur-md transition-all duration-200 hover:bg-slate-100 hover:text-slate-900 dark:border-zinc-800/80 dark:bg-zinc-900/70 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white cursor-pointer"
                :aria-label="t('notifications.title')"
            >
                <Bell class="h-5 w-5" />
                <NotificationBadge :count="unreadCount" />
            </button>
        </DropdownMenuTrigger>

        <DropdownMenuContent
            align="end"
            class="w-80 sm:w-96 rounded-2xl border border-slate-200/80 bg-white/95 p-0 shadow-xl backdrop-blur-xl dark:border-zinc-800/80 dark:bg-zinc-950/95 text-slate-900 dark:text-white z-50"
        >
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 dark:border-zinc-800/60">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-bold text-slate-900 dark:text-white">{{ t('notifications.title') }}</span>
                    <span
                        v-if="unreadCount > 0"
                        class="rounded-full bg-main/20 px-2 py-0.5 text-xs font-semibold text-main-dark dark:text-main"
                    >
                        {{ unreadCount }} {{ t('notifications.newCount') }}
                    </span>
                </div>
                <button
                    v-if="unreadCount > 0"
                    @click="markAllAsRead"
                    type="button"
                    class="inline-flex items-center gap-1 text-xs font-medium text-slate-500 transition-colors hover:text-main-dark dark:text-zinc-400 dark:hover:text-main cursor-pointer"
                >
                    <CheckCheck class="h-3.5 w-3.5" />
                    {{ t('notifications.markAllRead') }}
                </button>
            </div>

            <div class="max-h-80 overflow-y-auto divide-y divide-slate-100 dark:divide-zinc-800/40">
                <template v-if="notifications.length > 0">
                    <div
                        v-for="item in notifications"
                        :key="item.id"
                        @click="toggleRead(item)"
                        class="flex items-start gap-3 p-3.5 transition-colors cursor-pointer"
                        :class="[
                            item.is_seen
                                ? 'bg-transparent hover:bg-slate-50 dark:hover:bg-zinc-900/40'
                                : 'bg-main/5 dark:bg-main/10 hover:bg-main/10 dark:hover:bg-main/15'
                        ]"
                    >
                        <div
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                            :class="[
                                item.is_seen
                                    ? 'bg-slate-100 text-slate-600 dark:bg-zinc-800 dark:text-zinc-400'
                                    : 'bg-main/20 text-main-dark dark:text-main font-bold'
                            ]"
                        >
                            <component :is="getIcon(item.type)" class="h-4 w-4" />
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <h4
                                    class="text-xs font-semibold truncate capitalize"
                                    :class="item.is_seen ? 'text-slate-700 dark:text-zinc-300' : 'text-slate-900 dark:text-white font-bold'"
                                >
                                    {{ item.type === 'success' ? t('notifications.title') : t('notifications.title') }}
                                </h4>
                                <span class="text-[10px] text-slate-400 dark:text-zinc-500 shrink-0">{{ formatTime(item.created_at) }}</span>
                            </div>
                            <p class="mt-0.5 text-xs text-slate-600 dark:text-zinc-400 line-clamp-2 leading-relaxed">
                                {{ item.translations?.[locale] || item.translations?.['uk'] || '' }}
                            </p>
                        </div>

                        <div v-if="!item.is_seen" class="mt-1 h-2 w-2 shrink-0 rounded-full bg-main"></div>
                    </div>
                </template>
                <template v-else>
                    <div class="p-6 text-center text-xs text-slate-500 dark:text-zinc-400 font-medium">
                        {{ t('notifications.empty') }}
                    </div>
                </template>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
