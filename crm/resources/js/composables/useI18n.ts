import { ref, computed } from 'vue';
import uk from '@/i18n/uk';
import en from '@/i18n/en';

export type Locale = 'uk' | 'en';

const messages = { uk, en };

const currentLocale = ref<Locale>('uk');

export function initializeI18n(): void {
    if (typeof window === 'undefined') return;
    const saved = localStorage.getItem('locale') as Locale | null;
    if (saved && (saved === 'uk' || saved === 'en')) {
        currentLocale.value = saved;
    } else {
        const lang = navigator.language.toLowerCase();
        currentLocale.value = lang.startsWith('uk') ? 'uk' : 'en';
    }
    syncLocaleCookie(currentLocale.value);
}

function syncLocaleCookie(lang: Locale) {
    if (typeof document !== 'undefined') {
        document.cookie = `locale=${lang};path=/;max-age=31536000;SameSite=Lax`;
    }
}

export function useI18n() {
    initializeI18n();

    const locale = computed({
        get: () => currentLocale.value,
        set: (val: Locale) => {
            setLocale(val);
        },
    });

    function setLocale(lang: Locale) {
        currentLocale.value = lang;
        if (typeof window !== 'undefined') {
            localStorage.setItem('locale', lang);
        }
        syncLocaleCookie(lang);
    }

    function t(path: string): string {
        const keys = path.split('.');
        let current: any = messages[currentLocale.value];
        for (const k of keys) {
            if (current && typeof current === 'object' && k in current) {
                current = current[k];
            } else {
                // Fallback to UK if missing in current locale
                let fallback: any = messages['uk'];
                for (const fk of keys) {
                    if (fallback && typeof fallback === 'object' && fk in fallback) {
                        fallback = fallback[fk];
                    } else {
                        return path;
                    }
                }
                return typeof fallback === 'string' ? fallback : path;
            }
        }
        return typeof current === 'string' ? current : path;
    }

    return {
        locale,
        setLocale,
        t,
    };
}
