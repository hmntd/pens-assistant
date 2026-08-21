import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { useI18n } from '@/composables/useI18n';
import type { FlashToast } from '@/types/ui';

export function initializeFlashToast(): void {
    const { t } = useI18n();

    router.on('flash', (event) => {
        const flash = (event as CustomEvent).detail?.flash;
        const data = flash?.toast as FlashToast | undefined;

        if (!data || !data.message) {
            return;
        }

        const translated = t(data.message);
        const displayMsg = translated !== data.message ? translated : data.message;

        toast[data.type](displayMsg);
    });
}
