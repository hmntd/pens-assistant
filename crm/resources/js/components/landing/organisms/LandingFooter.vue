<script setup lang="ts">
import { GraduationCap } from '@lucide/vue';
import AppLogo from '@/components/navigation/atoms/AppLogo.vue';
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();
const currentYear = new Date().getFullYear();
</script>

<template>
    <footer
        class="border-t border-slate-200/80 bg-white py-12 text-slate-600 dark:border-slate-800/80 dark:bg-black dark:text-slate-400"
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Single Footer Group -->
            <div
                class="mx-auto flex max-w-3xl flex-col items-center space-y-6 text-center"
            >
                <!-- Logo -->
                <div class="flex items-center gap-2">
                    <AppLogo class="h-8 w-8" />
                </div>

                <!-- Disclaimer Box -->
                <div
                    class="rounded-2xl border border-amber-500/30 bg-amber-500/10 p-4 text-xs text-amber-800 dark:text-amber-300"
                >
                    <div
                        class="mb-1.5 flex items-center justify-center gap-2 font-bold"
                    >
                        <GraduationCap
                            class="h-4 w-4 text-amber-600 dark:text-amber-400"
                        />
                        <span>{{ t('footer.disclaimerTitle') }}</span>
                    </div>
                    <p class="leading-relaxed">
                        {{ t('footer.disclaimerText') }}
                    </p>
                </div>

                <!-- Copyright & Year -->
                <div
                    class="w-full border-t border-slate-200/50 pt-4 text-xs text-slate-400 dark:border-slate-800/50"
                >
                    <p>
                        © {{ currentYear }} Ukrainian Pension Assistant.
                        {{ t('footer.rights') }}
                    </p>
                </div>
            </div>
        </div>
    </footer>
</template>
