<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import LangSelect from '@/components/landing/atoms/LangSelect.vue';
import ThemeToggleBtn from '@/components/landing/atoms/ThemeToggleBtn.vue';
import AppLogo from '@/components/navigation/atoms/AppLogo.vue';
import { useI18n } from '@/composables/useI18n';
import gridData from '@/data/asciiFramesGrid404.json';
import { AsciiScreen } from '@/lib/AsciiScreen';
import { home } from '@/routes';

const props = defineProps<{
    status?: number;
    message?: string;
}>();

const { t } = useI18n();

const screenEl = ref<HTMLElement | null>(null);
let asciiScreen: AsciiScreen | null = null;
let currentFrameIndex = 0;
let frameInterval: ReturnType<typeof setInterval> | null = null;

const statusCode = computed(() => props.status || 404);

const goBack = () => {
    if (typeof window !== 'undefined' && window.history.length > 1) {
        window.history.back();
    } else {
        window.location.href = home.url();
    }
};

const errorDetails = computed(() => {
    const code = statusCode.value;

    switch (code) {
        case 400:
            return {
                title: t('error.code400'),
                description: props.message || t('error.desc400'),
            };
        case 401:
            return {
                title: t('error.code401'),
                description: props.message || t('error.desc401'),
            };
        case 403:
            return {
                title: t('error.code403'),
                description: props.message || t('error.desc403'),
            };
        case 404:
            return {
                title: t('error.pageNotFound'),
                description: props.message || t('error.desc404'),
            };
        case 419:
            return {
                title: t('error.code419'),
                description: props.message || t('error.desc419'),
            };
        case 429:
            return {
                title: t('error.code429'),
                description: props.message || t('error.desc429'),
            };
        case 500:
            return {
                title: t('error.code500'),
                description: props.message || t('error.desc500'),
            };
        case 502:
            return {
                title: t('error.code502'),
                description: props.message || t('error.desc502'),
            };
        case 503:
            return {
                title: t('error.code503'),
                description: props.message || t('error.desc503'),
            };
        case 504:
            return {
                title: t('error.code504'),
                description: props.message || t('error.desc504'),
            };
        default:
            return {
                title: t('error.genericTitle'),
                description: props.message || t('error.genericDesc'),
            };
    }
});

onMounted(() => {
    if (!screenEl.value) {
        return;
    }

    asciiScreen = new AsciiScreen(screenEl.value, {
        mode: 'palette',
        renderer: 'canvas',
        palette: gridData.palette,
        autoCardBodyHeight: false,
        defaultRows: 40,
        minCols: 151,
        maxCols: 300,
    });

    const frames = gridData.frames;

    if (frames.length === 0) {
        return;
    }

    const drawFrame = (frameIdx: number) => {
        if (!asciiScreen) {
            return;
        }

        asciiScreen.clear();
        const rows = frames[frameIdx];

        for (let r = 0; r < rows.length; r++) {
            const cells = rows[r];

            for (let c = 0; c < cells.length; c++) {
                const cell = cells[c];
                asciiScreen.put(c, r, cell[0] as string, cell[1] as number);
            }
        }

        asciiScreen.renderToElement();
    };

    drawFrame(0);

    frameInterval = setInterval(() => {
        currentFrameIndex = (currentFrameIndex + 1) % frames.length;
        drawFrame(currentFrameIndex);
    }, 100);
});

onUnmounted(() => {
    if (frameInterval) {
        clearInterval(frameInterval);
    }

    if (asciiScreen) {
        asciiScreen.destroy();
    }
});
</script>

<template>
    <Head :title="`${statusCode} - ${errorDetails.title}`" />

    <div
        class="relative flex min-h-screen w-full flex-col justify-between overflow-hidden bg-white font-sans text-black transition-colors duration-300 selection:bg-main selection:text-white dark:bg-black dark:text-white"
    >
        <header
            class="relative z-30 mx-auto flex w-full max-w-7xl items-center justify-between px-6 py-5"
        >
            <div class="flex items-center gap-3">
                <AppLogo class="h-9 w-9 text-main" />
            </div>

            <div class="flex items-center gap-3">
                <LangSelect />
                <ThemeToggleBtn />
            </div>
        </header>

        <div
            class="hero-art pointer-events-none absolute inset-0 z-10 overflow-hidden select-none"
            aria-hidden="true"
        >
            <div
                class="absolute top-1/2 left-1/2 flex -translate-x-1/2 -translate-y-1/2 items-center justify-center"
            >
                <pre
                    ref="screenEl"
                    class="text-center font-mono text-[6px] leading-none whitespace-pre drop-shadow-[0_0_14px_rgba(49,222,151,0.5)] sm:text-[9px] md:text-[11px] lg:text-[13px] xl:text-[15px]"
                ></pre>
            </div>
        </div>

        <main
            class="pointer-events-none absolute inset-0 z-20 flex items-center justify-center p-4"
        >
            <Link
                @click="goBack()"
                class="pointer-events-auto inline-flex cursor-pointer items-center gap-3.5 rounded-2xl border border-gray-100 bg-white/80 px-6 py-3 font-mono text-lg font-extrabold whitespace-nowrap text-main backdrop-blur-md transition-all duration-300 sm:text-2xl md:text-3xl dark:border-white/5 dark:bg-black/30"
            >
                <span>{{ statusCode }} {{ errorDetails.title }}</span>
                <ArrowRight class="h-6 w-6 shrink-0 sm:h-7 sm:w-7" />
            </Link>
        </main>

        <div class="flex-1"></div>

        <footer
            class="relative z-30 py-4 text-center text-xs font-medium text-black/50 transition-colors duration-300 select-none dark:text-white/50"
        >
            &copy; {{ new Date().getFullYear() }} PensAssistant.
            {{ t('footer.rights') }}
        </footer>
    </div>
</template>
