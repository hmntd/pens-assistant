import { onMounted, onUnmounted } from 'vue';
import { toast } from 'vue-sonner';
import { useI18n } from '@/composables/useI18n';
import { usePage } from '@inertiajs/vue3';
import { initializeEcho } from '@/echo';

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

const PENDING_CALC_KEY = 'pending_pension_calc_active';
const PENDING_CALC_TIME_KEY = 'pending_pension_calc_started_at';

let isFastPollingActive = false;

export function triggerFastPolling() {
    if (isFastPollingActive) return;
    isFastPollingActive = true;
    if (globalPollInterval) clearInterval(globalPollInterval);

    const runPoll = async () => {
        if (!isPendingCalculationActive()) {
            isFastPollingActive = false;
            if (globalPollInterval) clearInterval(globalPollInterval);
            globalPollInterval = setInterval(checkDocumentsGlobal, 10000);
            return;
        }
        await checkDocumentsGlobal();
    };

    runPoll();
    globalPollInterval = setInterval(runPoll, 1500);
}

export function setPendingCalculationState() {
    if (typeof window === 'undefined') return;
    try {
        sessionStorage.setItem(PENDING_CALC_KEY, 'true');
        sessionStorage.setItem(PENDING_CALC_TIME_KEY, Date.now().toString());
    } catch (e) {}
    triggerFastPolling();
}

export function clearPendingCalculationState() {
    if (typeof window === 'undefined') return;
    try {
        sessionStorage.removeItem(PENDING_CALC_KEY);
        sessionStorage.removeItem(PENDING_CALC_TIME_KEY);
    } catch (e) {}
}

export function isPendingCalculationActive(): boolean {
    if (typeof window === 'undefined') return false;
    try {
        const active = sessionStorage.getItem(PENDING_CALC_KEY);
        const startedAtStr = sessionStorage.getItem(PENDING_CALC_TIME_KEY);
        if (active !== 'true' || !startedAtStr) return false;

        const startedAt = parseInt(startedAtStr, 10);
        // Timeout after 2 minutes (120,000ms) in case background job failed
        if (Date.now() - startedAt > 120000) {
            clearPendingCalculationState();
            return false;
        }
        return true;
    } catch (e) {
        return false;
    }
}

let isBootstrapped = false;
let isCalcBootstrapped = false;
let isCalculationsBootstrapped = false;
let globalPollInterval: ReturnType<typeof setInterval> | null = null;
let checkDocumentsGlobal: () => Promise<void> = async () => {};
const knownCalcStatuses = new Map<number, string>();
const knownStatuses = new Map<number, string>();

export function useDocumentNotifier() {
    const { t } = useI18n();

    async function checkCalculations() {
        try {
            const res = await fetch('/pension-calculations', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (!res.ok) return;

            const json = await res.json();
            const calcs = json.data;
            if (!Array.isArray(calcs)) return;

            let hasStatusChanges = false;
            let hasPendingActive = false;

            for (const c of calcs) {
                if (!c.id) continue;
                const status = (c.status || 'completed').toLowerCase();
                const prevStatus = knownCalcStatuses.get(c.id);

                if (status === 'pending') {
                    hasPendingActive = true;
                }

                if (prevStatus === 'pending' && status === 'completed') {
                    clearPendingCalculationState();
                    toast.success(t('notifications.pensionCalculationSuccessToast'));
                    hasStatusChanges = true;
                } else if (prevStatus === 'pending' && status === 'failed') {
                    clearPendingCalculationState();
                    toast.error(c.error_message || t('notifications.ocrFailedToast') || 'Помилка при виконанні розрахунку.');
                    hasStatusChanges = true;
                } else if (isCalculationsBootstrapped && !knownCalcStatuses.has(c.id)) {
                    hasStatusChanges = true;
                }

                knownCalcStatuses.set(c.id, status);
            }

            if (!hasPendingActive && isPendingCalculationActive()) {
                clearPendingCalculationState();
                hasStatusChanges = true;
            }

            if (!isCalculationsBootstrapped) {
                isCalculationsBootstrapped = true;
            }
            if (hasStatusChanges) {
                window.dispatchEvent(new CustomEvent('calculations-updated'));
                window.dispatchEvent(new CustomEvent('notification-created'));
            }
        } catch (e) {
            // silent fallback
        }
    }

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

            // On initial bootstrap of a fresh session, only mark older historical calculation notifications (>3 mins old)
            // so ancient notifications don't toast on initial page load
            if (!isCalcBootstrapped) {
                for (const n of items) {
                    const isCalcType = n.type === 'calculation' || n.type === 'calc_completed';
                    if (isCalcType && !isRecentDoc(n.created_at)) {
                        notifiedCalcIds.add(n.id);
                    }
                }
                saveNotifiedCalcSet(notifiedCalcIds);
                isCalcBootstrapped = true;
            }

            let hasNewCalculations = false;
            for (const n of items) {
                const isCalcType = n.type === 'calculation' || n.type === 'calc_completed';
                if (isCalcType && !n.is_seen && !notifiedCalcIds.has(n.id)) {
                    notifiedCalcIds.add(n.id);
                    saveNotifiedCalcSet(notifiedCalcIds);
                    toast.success(t('notifications.pensionCalculationSuccessToast'));
                    hasNewCalculations = true;
                } else if (n.type === 'error' && !n.is_seen && !notifiedCalcIds.has(n.id)) {
                    notifiedCalcIds.add(n.id);
                    saveNotifiedCalcSet(notifiedCalcIds);
                    const msg = n.translations?.uk || n.translations?.en || 'Помилка виконання розрахунку.';
                    toast.error(msg);
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
            await checkCalculations();
        } catch (err) {
            // silent fallback
        }
    }

    onMounted(() => {
        checkDocumentsGlobal = checkDocuments;
        checkDocuments();
        if (isPendingCalculationActive()) {
            triggerFastPolling();
        } else if (!globalPollInterval) {
            globalPollInterval = setInterval(checkDocuments, 10000);
        }

        try {
            const page = usePage();
            const userId = page.props.auth?.user?.id;
            if (userId) {
                const echo = initializeEcho();
                if (echo) {
                    const handleCalcEvent = async () => {
                        clearPendingCalculationState();
                        await checkCalculations();
                        await checkNotifications();
                        window.dispatchEvent(new CustomEvent('calculations-updated'));
                        window.dispatchEvent(new CustomEvent('notification-created'));
                    };

                    echo.private(`users.${userId}`)
                        .listen('.pension.calculated', handleCalcEvent)
                        .listen('PensionCalculated', handleCalcEvent)
                        .listen('.document.status.updated', () => {
                            checkDocuments();
                        });
                }
            }
        } catch (e) {
            // silent fallback
        }
    });

    onUnmounted(() => {
        // Keep active across layout lifetime
    });
}
