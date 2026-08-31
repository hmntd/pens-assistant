import { onMounted, onUnmounted } from 'vue';
import { toast } from 'vue-sonner';
import { useI18n } from '@/composables/useI18n';

const NOTIFIED_KEY = 'notified_doc_status_ids';
const NOTIFIED_CALC_KEY = 'notified_calc_notification_ids';

function getNotifiedSet(): Set<string> {
    if (typeof window === 'undefined') return new Set();
    try {
        const raw = sessionStorage.getItem(NOTIFIED_KEY);
        return raw ? new Set(JSON.parse(raw)) : new Set();
    } catch (e) {
        return new Set();
    }
}

function saveNotifiedSet(set: Set<string>): void {
    if (typeof window === 'undefined') return;
    try {
        sessionStorage.setItem(NOTIFIED_KEY, JSON.stringify(Array.from(set)));
    } catch (e) {}
}

function getNotifiedCalcSet(): Set<number> {
    if (typeof window === 'undefined') return new Set();
    try {
        const raw = sessionStorage.getItem(NOTIFIED_CALC_KEY);
        return raw ? new Set(JSON.parse(raw)) : new Set();
    } catch (e) {
        return new Set();
    }
}

function saveNotifiedCalcSet(set: Set<number>): void {
    if (typeof window === 'undefined') return;
    try {
        sessionStorage.setItem(NOTIFIED_CALC_KEY, JSON.stringify(Array.from(set)));
    } catch (e) {}
}

function isRecentDoc(created_at?: string): boolean {
    if (!created_at) return true;
    try {
        const createdAt = new Date(created_at).getTime();
        const now = Date.now();
        return (now - createdAt) < 180000; // created within last 3 minutes
    } catch (e) {
        return true;
    }
}

let isBootstrapped = false;
let isCalcBootstrapped = false;
let globalPollInterval: ReturnType<typeof setInterval> | null = null;
const knownStatuses = new Map<number, string>();

export function useDocumentNotifier() {
    const { t } = useI18n();

    async function checkNotifications() {
        try {
            const res = await fetch('/notifications', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (!res.ok) return;

            const json = await res.json();
            const items = json.notifications;
            if (!Array.isArray(items)) return;

            const notifiedCalcIds = getNotifiedCalcSet();

            // On initial bootstrap of a fresh session, mark existing unread calculation notifications
            // so ancient notifications don't toast on initial page load
            if (!isCalcBootstrapped) {
                for (const n of items) {
                    if (n.type === 'calculation') {
                        notifiedCalcIds.add(n.id);
                    }
                }
                saveNotifiedCalcSet(notifiedCalcIds);
                isCalcBootstrapped = true;
                return;
            }

            let hasNewCalculations = false;
            for (const n of items) {
                if (n.type === 'calculation' && !n.is_seen && !notifiedCalcIds.has(n.id)) {
                    notifiedCalcIds.add(n.id);
                    saveNotifiedCalcSet(notifiedCalcIds);
                    toast.success(t('notifications.pensionCalculationSuccessToast'));
                    hasNewCalculations = true;
                }
            }

            if (hasNewCalculations) {
                window.dispatchEvent(new CustomEvent('notification-created'));
                window.dispatchEvent(new CustomEvent('calculations-updated'));
            }
        } catch (e) {
            // silent fallback
        }
    }

    async function checkDocuments() {
        try {
            const res = await fetch('/documents', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (!res.ok) return;

            const json = await res.json();
            const docs = json.data;
            if (!Array.isArray(docs)) return;

            const notified = getNotifiedSet();

            // On initial bootstrap of a fresh session:
            // Mark older historical documents (> 3 mins old) as notified so ancient docs don't toast on page load
            if (!isBootstrapped) {
                for (const d of docs) {
                    const status = (d.status || d.ocr_status || d.recognized_document?.status || '').toLowerCase();
                    knownStatuses.set(d.id, status);
                    if (['completed', 'processed', 'success', 'failed'].includes(status) && !isRecentDoc(d.created_at)) {
                        notified.add(`${d.id}:${status}`);
                    }
                }
                saveNotifiedSet(notified);
                isBootstrapped = true;
            }

            let hasChanges = false;

            for (const d of docs) {
                const status = (d.status || d.ocr_status || d.recognized_document?.status || '').toLowerCase();
                const filename = d.original_filename || `Doc #${d.id}`;
                const key = `${d.id}:${status}`;
                const prevStatus = knownStatuses.get(d.id);

                knownStatuses.set(d.id, status);

                if (['completed', 'processed', 'success'].includes(status)) {
                    if (!notified.has(key) || prevStatus === 'pending' || prevStatus === 'processing') {
                        notified.add(key);
                        saveNotifiedSet(notified);
                        toast.success(t('notifications.ocrSuccessToast').replace(':file', filename));
                        hasChanges = true;
                    }
                } else if (status === 'failed') {
                    if (!notified.has(key) || prevStatus === 'pending' || prevStatus === 'processing') {
                        notified.add(key);
                        saveNotifiedSet(notified);
                        toast.error(t('notifications.ocrFailedToast').replace(':file', filename));
                        hasChanges = true;
                    }
                }
            }

            if (hasChanges) {
                window.dispatchEvent(new CustomEvent('notification-created'));
                window.dispatchEvent(new CustomEvent('documents-updated'));
            }

            await checkNotifications();
        } catch (err) {
            // silent fallback
        }
    }

    onMounted(() => {
        checkDocuments();
        if (!globalPollInterval) {
            globalPollInterval = setInterval(checkDocuments, 2000);
        }
    });

    onUnmounted(() => {
        // Keep active across layout lifetime
    });
}
