<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';
import AppLogo from '@/components/AppLogo.vue';
import { GraduationCap, ShieldAlert } from '@lucide/vue';

const { t } = useI18n();
const currentYear = new Date().getFullYear();
</script>

<template>
    <footer class="border-t border-slate-200/80 bg-white py-12 text-slate-600 dark:border-slate-800/80 dark:bg-black dark:text-slate-400">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            
            <!-- Single Footer Group -->
            <div class="flex flex-col items-center text-center max-w-3xl mx-auto space-y-6">
                
                <!-- Logo -->
                <div class="flex items-center gap-2">
                    <AppLogo class="h-8 w-8" />
                </div>

                <!-- Disclaimer Box -->
                <div class="rounded-2xl border border-amber-500/30 bg-amber-500/10 p-4 text-xs text-amber-800 dark:text-amber-300">
                    <div class="mb-1.5 flex items-center justify-center gap-2 font-bold">
                        <GraduationCap class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                        <span>{{ t('footer.disclaimerTitle') }}</span>
                    </div>
                    <p class="leading-relaxed">
                        {{ t('footer.disclaimerText') }}
                    </p>
                </div>

                <!-- Copyright & Year -->
                <div class="pt-4 border-t border-slate-200/50 w-full dark:border-slate-800/50 text-xs text-slate-400">
                    <p>© {{ currentYear }} Ukrainian Pension Assistant. {{ t('footer.rights') }}</p>
                </div>

            </div>

        </div>
    </footer>
</template>
