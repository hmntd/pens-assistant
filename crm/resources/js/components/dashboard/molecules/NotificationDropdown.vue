<script setup lang="ts">
import {
    Bell,
    CheckCheck,
    FileText,
    Calculator,
    Info,
    CheckCircle2,
    AlertCircle,
} from '@lucide/vue';
import { ref, onMounted, onUnmounted } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useI18n } from '@/composables/useI18n';
import NotificationBadge from '../atoms/NotificationBadge.vue';

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
        await fetch('/notifications/read-all', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN':
                    (
                        document.querySelector(
                            'meta[name="csrf-token"]',
                        ) as HTMLMetaElement
                    )?.content || '',
                Accept: 'application/json',
            },
        });
    } catch {
        // silent fallback
    }

    notifications.value.forEach((n) => (n.is_seen = true));
    unreadCount.value = 0;
}

async function toggleRead(item: DbNotificationItem) {
    if (item.is_seen) {
        return;
    }

    item.is_seen = true;
    unreadCount.value = Math.max(0, unreadCount.value - 1);

    try {
        await fetch(`/notifications/${item.id}/read`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN':
                    (
                        document.querySelector(
                            'meta[name="csrf-token"]',
                        ) as HTMLMetaElement
                    )?.content || '',
                Accept: 'application/json',
            },
        });
    } catch {
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
    if (!dateStr) {
        return '';
    }

    const date = new Date(dateStr);

    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function getNotificationText(item: DbNotificationItem): string {
    const currentLang = (locale.value || 'uk') as 'uk' | 'en';

    return item.translations?.[currentLang] || item.translations?.['uk'] || '';
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
                class="relative inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 shadow-sm transition-colors hover:bg-slate-100 hover:text-slate-900 dark:border-zinc-800 dark:bg-black dark:text-slate-400 dark:hover:bg-zinc-900 dark:hover:text-slate-100"
                :aria-label="t('notifications.title')"
            >
                <Bell class="h-4 w-4" />
                <NotificationBadge :count="unreadCount" />
            </button>
        </DropdownMenuTrigger>

        <DropdownMenuContent
            align="end"
            class="z-50 w-80 rounded-2xl border border-slate-200/80 bg-white/95 p-0 text-slate-900 shadow-xl backdrop-blur-xl sm:w-96 dark:border-zinc-800/80 dark:bg-zinc-950/95 dark:text-white"
        >
            <div
                class="flex items-center justify-between border-b border-slate-100 px-4 py-3 dark:border-zinc-800/60"
            >
                <div class="flex items-center gap-2">
                    <span
                        class="text-sm font-bold text-slate-900 dark:text-white"
                        >{{ t('notifications.title') }}</span
                    >
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
                    class="inline-flex cursor-pointer items-center gap-1 text-xs font-medium text-slate-500 transition-colors hover:text-main-dark dark:text-zinc-400 dark:hover:text-main"
                >
                    <CheckCheck class="h-3.5 w-3.5" />
                    {{ t('notifications.markAllRead') }}
                </button>
            </div>

            <!-- Scrollbar container with transparent background -->
            <div
                class="max-h-80 divide-y divide-slate-100 overflow-y-auto dark:divide-zinc-800/40 [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-slate-300 dark:[&::-webkit-scrollbar-thumb]:bg-zinc-800 [&::-webkit-scrollbar-track]:bg-transparent"
            >
                <template v-if="notifications.length > 0">
                    <div
                        v-for="item in notifications"
                        :key="item.id"
                        @click="toggleRead(item)"
                        class="flex cursor-pointer items-start gap-3 p-3.5 transition-colors"
                        :class="[
                            item.is_seen
                                ? 'bg-transparent hover:bg-slate-50 dark:hover:bg-zinc-900/40'
                                : 'bg-main/5 hover:bg-main/10 dark:bg-main/10 dark:hover:bg-main/15',
                        ]"
                    >
                        <div
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                            :class="[
                                item.is_seen
                                    ? 'bg-slate-100 text-slate-600 dark:bg-zinc-800 dark:text-zinc-400'
                                    : 'bg-main/20 font-bold text-main-dark dark:text-main',
                            ]"
                        >
                            <component
                                :is="getIcon(item.type)"
                                class="h-4 w-4"
                            />
                        </div>

                        <div class="min-w-0 flex-1">
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <h4
                                    class="truncate text-xs font-semibold capitalize"
                                    :class="
                                        item.is_seen
                                            ? 'text-slate-700 dark:text-zinc-300'
                                            : 'font-bold text-slate-900 dark:text-white'
                                    "
                                >
                                    {{ t('notifications.title') }}
                                </h4>
                                <span
                                    class="shrink-0 text-[10px] text-slate-400 dark:text-zinc-500"
                                    >{{ formatTime(item.created_at) }}</span
                                >
                            </div>
                            <p
                                class="mt-0.5 line-clamp-2 text-xs leading-relaxed text-slate-600 dark:text-zinc-400"
                            >
                                {{ getNotificationText(item) }}
                            </p>
                        </div>

                        <div
                            v-if="!item.is_seen"
                            class="mt-1 h-2 w-2 shrink-0 rounded-full bg-main"
                        ></div>
                    </div>
                </template>
                <template v-else>
                    <div
                        class="p-6 text-center text-xs font-medium text-slate-500 dark:text-zinc-400"
                    >
                        {{ t('notifications.empty') }}
                    </div>
                </template>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
