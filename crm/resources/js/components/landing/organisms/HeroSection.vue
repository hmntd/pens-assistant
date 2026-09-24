<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ShieldCheck, Calculator, FileSearch, ArrowRight } from '@lucide/vue';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import AppLogo from '@/components/navigation/atoms/AppLogo.vue';
import { useI18n } from '@/composables/useI18n';
import { getHeroFrames } from '@/lib/heroFramesCache';
import { dashboard, login, register } from '@/routes';
import PrimaryButton from '../atoms/PrimaryButton.vue';
import SecondaryButton from '../atoms/SecondaryButton.vue';

const page = usePage();
const { t } = useI18n();

const actionsRef = ref<HTMLElement | null>(null);
const isAuthenticated = computed(() => !!page.props.auth?.user);

const frames = ref<string[]>([]);
const currentFrameIndex = ref(0);
let frameInterval: ReturnType<typeof setInterval> | null = null;

onMounted(async () => {
    frames.value = await getHeroFrames();

    // 100ms high-speed frame animation timer
    frameInterval = setInterval(() => {
        if (frames.value.length > 0) {
            currentFrameIndex.value =
                (currentFrameIndex.value + 1) % frames.value.length;
        }
    }, 100);
});

onUnmounted(() => {
    if (frameInterval) {
        clearInterval(frameInterval);
    }
});

defineExpose({
    actionsRef,
});
</script>

<template>
    <section
        class="relative flex min-h-[540px] flex-col justify-center overflow-hidden pt-24 pb-24 lg:min-h-[620px] lg:pt-32 lg:pb-32"
    >
        <!-- Glowing Background Accents -->
        <div
            class="pointer-events-none absolute -top-24 left-1/2 z-20 h-[500px] w-[800px] -translate-x-1/2 rounded-full bg-gradient-to-tr from-main/20 via-emerald-500/10 to-transparent opacity-70 blur-3xl"
        ></div>

        <!-- Hero ASCII Art Section (Re-renders dynamically from raw_frames.js in background) -->
        <div
            class="hero-art pointer-events-none absolute inset-0 z-10 flex items-start justify-center overflow-hidden opacity-60 select-none dark:opacity-35"
            aria-hidden="true"
        >
            <pre
                v-if="frames.length > 0"
                class="h-full px-[120px] pt-4 pb-[120px] text-left font-mono text-[6px] leading-[1.1] font-semibold whitespace-pre text-emerald-600 drop-shadow-[0_1px_2px_rgba(16,185,129,0.2)] transition-all duration-75 sm:text-[9px] md:text-[11px] lg:pt-6 lg:text-[13px] dark:font-normal dark:text-main dark:drop-shadow-[0_0_12px_rgba(49,222,151,0.35)]"
                >{{ frames[currentFrameIndex] }}</pre>
        </div>

        <!-- Main Hero Content Grid (positioned above background ASCII art) -->
        <div
            class="relative z-10 mx-auto max-w-4xl px-4 pt-6 sm:px-6 lg:px-8 lg:pt-10"
        >
            <div class="flex flex-col items-center text-center">
                <!-- Front side Logo + Title -->
                <div class="mb-4 flex items-center justify-center gap-3">
                    <AppLogo class="h-10 w-10" />
                    <span
                        class="text-xs font-semibold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                    >
                        Pension Recalculation Platform
                    </span>
                </div>

                <h1
                    class="mb-8 text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl lg:text-6xl dark:text-white"
                >
                    {{ t('hero.title') }}
                </h1>

                <!-- 2 Hero Buttons (Sign In filled in bg-main & Sign Up, or Dashboard) -->
                <div
                    ref="actionsRef"
                    class="flex flex-wrap items-center justify-center gap-4"
                >
                    <template v-if="isAuthenticated">
                        <PrimaryButton :href="dashboard.url()">
                            <span>{{ t('hero.dashboard') }}</span>
                            <ArrowRight class="h-4 w-4" />
                        </PrimaryButton>
                    </template>
                    <template v-else>
                        <PrimaryButton :href="login.url()">
                            <span>{{ t('hero.signIn') }}</span>
                            <ArrowRight class="h-4 w-4" />
                        </PrimaryButton>
                        <SecondaryButton :href="register.url()">
                            <span>{{ t('hero.signUp') }}</span>
                        </SecondaryButton>
                    </template>
                </div>

                <!-- Statistics / Quick Badges -->
                <div
                    class="mt-12 grid w-full grid-cols-1 gap-6 border-t border-slate-200/60 pt-8 sm:grid-cols-3 dark:border-slate-800/60"
                >
                    <div class="flex flex-col items-center text-center">
                        <div
                            class="flex items-center gap-1.5 text-base font-bold text-slate-900 dark:text-white"
                        >
                            <ShieldCheck class="h-4 w-4 text-main" />
                            <span>{{ t('hero.stat1') }}</span>
                        </div>
                        <p
                            class="mt-0.5 text-xs text-slate-500 dark:text-slate-400"
                        >
                            {{ t('hero.stat1Sub') }}
                        </p>
                    </div>
                    <div class="flex flex-col items-center text-center">
                        <div
                            class="flex items-center gap-1.5 text-base font-bold text-slate-900 dark:text-white"
                        >
                            <Calculator class="h-4 w-4 text-main" />
                            <span>{{ t('hero.stat2') }}</span>
                        </div>
                        <p
                            class="mt-0.5 text-xs text-slate-500 dark:text-slate-400"
                        >
                            {{ t('hero.stat2Sub') }}
                        </p>
                    </div>
                    <div class="flex flex-col items-center text-center">
                        <div
                            class="flex items-center gap-1.5 text-base font-bold text-slate-900 dark:text-white"
                        >
                            <FileSearch class="h-4 w-4 text-main" />
                            <span>{{ t('hero.stat3') }}</span>
                        </div>
                        <p
                            class="mt-0.5 text-xs text-slate-500 dark:text-slate-400"
                        >
                            {{ t('hero.stat3Sub') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
