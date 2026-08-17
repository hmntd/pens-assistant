<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';
import BadgeTag from '../atoms/BadgeTag.vue';
import PrimaryButton from '../atoms/PrimaryButton.vue';
import SecondaryButton from '../atoms/SecondaryButton.vue';
import AppLogo from '@/components/AppLogo.vue';
import { dashboard, login, register } from '@/routes';
import { ShieldCheck, Calculator, FileSearch, ArrowRight } from '@lucide/vue';

const page = usePage();
const { t } = useI18n();

const actionsRef = ref<HTMLElement | null>(null);
const isAuthenticated = computed(() => !!page.props.auth?.user);

const frames = ref<string[]>([]);
const currentFrameIndex = ref(0);
let frameInterval: ReturnType<typeof setInterval> | null = null;

onMounted(async () => {
    try {
        const res = await fetch('/raw_frames.js');
        const text = await res.text();
        // Parse raw_frames.js by extracting the array content safely
        const codeToEval = text.replace(/^const extractedFrames =\s*/, '');
        // Evaluate array using Function constructor safely
        const loadedFrames = new Function(`return ${codeToEval}`)();
        if (Array.isArray(loadedFrames) && loadedFrames.length > 0) {
            frames.value = loadedFrames;
        }
    } catch (e) {
        console.error('Failed to load raw_frames.js:', e);
    }

    // 100ms high-speed frame animation timer
    frameInterval = setInterval(() => {
        if (frames.value.length > 0) {
            currentFrameIndex.value = (currentFrameIndex.value + 1) % frames.value.length;
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
    <section class="relative overflow-hidden pt-12 pb-20 lg:pt-20 lg:pb-28">
        <!-- Glowing Background Accents -->
        <div class="pointer-events-none absolute -top-24 left-1/2 z-20 h-[500px] w-[800px] -translate-x-1/2 rounded-full bg-gradient-to-tr from-[#31DE97]/20 via-emerald-500/10 to-transparent blur-3xl opacity-70"></div>

        <!-- Hero ASCII Art Section (Re-renders dynamically from raw_frames.js in background -z-10 behind grid z-10) -->
        <div class="hero-art pointer-events-none absolute inset-0 z-10 flex items-center justify-center overflow-hidden opacity-25 dark:opacity-35 select-none" aria-hidden="true">
            <pre v-if="frames.length > 0" class="text-[6px] sm:text-[9px] md:text-[11px] lg:text-[13px] leading-[1.1] font-mono text-[#31DE97] drop-shadow-[0_0_12px_rgba(49,222,151,0.35)] whitespace-pre text-left h-full p-[120px] transition-all duration-75">{{ frames[currentFrameIndex] }}</pre>
        </div>

        <!-- Main Hero Content Grid (positioned above background ASCII art) -->
        <div class="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid items-center gap-12 lg:grid-cols-12 lg:gap-8">
                
                <!-- Left Text Column -->
                <div class="flex flex-col items-start lg:col-span-7">
                    <BadgeTag class="mb-6">
                        {{ t('hero.badge') }}
                    </BadgeTag>

                    <!-- Front side Logo + Title -->
                    <div class="mb-4 flex items-center gap-3">
                        <AppLogo class="h-10 w-10" />
                        <span class="text-xs font-semibold tracking-wider text-slate-500 uppercase dark:text-slate-400">
                            Pension Recalculation Platform
                        </span>
                    </div>

                    <h1 class="mb-6 text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl lg:text-6xl dark:text-white">
                        {{ t('hero.title') }}
                    </h1>

                    <!-- Short description -->
                    <p class="mb-8 text-lg font-medium text-slate-600 dark:text-slate-300">
                        {{ t('hero.subtitle') }}
                    </p>

                    <!-- 2 Hero Buttons (Sign In filled in #31DE97 & Sign Up, or Dashboard) -->
                    <div ref="actionsRef" class="flex flex-wrap items-center gap-4">
                        <template v-if="isAuthenticated">
                            <PrimaryButton :href="dashboard()">
                                <span>{{ t('hero.dashboard') }}</span>
                                <ArrowRight class="h-4 w-4" />
                            </PrimaryButton>
                        </template>
                        <template v-else>
                            <PrimaryButton :href="login()">
                                <span>{{ t('hero.signIn') }}</span>
                                <ArrowRight class="h-4 w-4" />
                            </PrimaryButton>
                            <SecondaryButton :href="register()">
                                <span>{{ t('hero.signUp') }}</span>
                            </SecondaryButton>
                        </template>
                    </div>

                    <!-- Statistics / Quick Badges -->
                    <div class="mt-12 grid w-full grid-cols-1 gap-4 sm:grid-cols-3 border-t border-slate-200/60 pt-8 dark:border-slate-800/60">
                        <div>
                            <div class="flex items-center gap-1.5 font-bold text-slate-900 dark:text-white text-base">
                                <ShieldCheck class="h-4 w-4 text-[#31DE97]" />
                                <span>{{ t('hero.stat1') }}</span>
                            </div>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                {{ t('hero.stat1Sub') }}
                            </p>
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5 font-bold text-slate-900 dark:text-white text-base">
                                <Calculator class="h-4 w-4 text-[#31DE97]" />
                                <span>{{ t('hero.stat2') }}</span>
                            </div>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                {{ t('hero.stat2Sub') }}
                            </p>
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5 font-bold text-slate-900 dark:text-white text-base">
                                <FileSearch class="h-4 w-4 text-[#31DE97]" />
                                <span>{{ t('hero.stat3') }}</span>
                            </div>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                {{ t('hero.stat3Sub') }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Right Hero Art (OpenClaw style SVG layered graphics) -->
                <div class="relative lg:col-span-5">
                    <div class="relative mx-auto w-full max-w-md lg:max-w-none">
                        <!-- Glass Card Outer container -->
                        <div class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-gradient-to-b from-white/90 to-slate-100/90 p-6 shadow-2xl backdrop-blur-xl dark:border-slate-800/80 dark:from-slate-900/90 dark:to-slate-950/90">
                            
                            <!-- OpenClaw layered background grid -->
                            <div class="absolute inset-0 bg-[radial-gradient(#31DE97_1px,transparent_1px)] [background-size:16px_16px] opacity-15"></div>

                            <!-- Header Bar -->
                            <div class="relative mb-6 flex items-center justify-between border-b border-slate-200/60 pb-4 dark:border-slate-800/60">
                                <div class="flex items-center gap-2">
                                    <span class="h-3 w-3 rounded-full bg-rose-500"></span>
                                    <span class="h-3 w-3 rounded-full bg-amber-500"></span>
                                    <span class="h-3 w-3 rounded-full bg-[#31DE97]"></span>
                                </div>
                                <span class="rounded-md bg-[#31DE97]/15 px-2.5 py-0.5 text-[10px] font-bold text-[#1cb777] dark:text-[#31DE97]">
                                    LIVE ENGINE DEMO
                                </span>
                            </div>

                            <!-- Calculator Simulation Graphics -->
                            <div class="relative space-y-4">
                                <div class="rounded-xl border border-slate-200/60 bg-white/80 p-4 shadow-sm dark:border-slate-800/60 dark:bg-slate-900/80">
                                    <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400">
                                        <span>Формула ПФУ: П = Зп × Кз × Кс</span>
                                        <span class="text-[#31DE97]">Закон № 1058-IV</span>
                                    </div>
                                    <div class="mt-2 text-xl font-extrabold text-slate-900 dark:text-white">
                                        8 241.31 UAH <span class="text-xs font-normal text-slate-400">/ місяць</span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div class="rounded-xl border border-slate-200/60 bg-white/80 p-3 text-xs dark:border-slate-800/60 dark:bg-slate-900/80">
                                        <div class="text-slate-400">Коефіцієнт Зп (Кз)</div>
                                        <div class="mt-1 font-bold text-slate-800 dark:text-slate-200">1.8425</div>
                                    </div>
                                    <div class="rounded-xl border border-slate-200/60 bg-white/80 p-3 text-xs dark:border-slate-800/60 dark:bg-slate-900/80">
                                        <div class="text-slate-400">Страховий стаж (Кс)</div>
                                        <div class="mt-1 font-bold text-slate-800 dark:text-slate-200">35 років (0.350)</div>
                                    </div>
                                </div>

                                <!-- Age Supplement Alert Card -->
                                <div class="flex items-center justify-between rounded-xl border border-[#31DE97]/40 bg-[#31DE97]/10 p-3 text-xs dark:bg-[#31DE97]/15">
                                    <div class="flex items-center gap-2">
                                        <span class="h-2 w-2 rounded-full bg-[#31DE97] animate-ping"></span>
                                        <span class="font-medium text-slate-800 dark:text-slate-200">Вікова надбавка (70+ років)</span>
                                    </div>
                                    <span class="font-bold text-[#1cb777] dark:text-[#31DE97]">+300.00 UAH</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </section>
</template>
