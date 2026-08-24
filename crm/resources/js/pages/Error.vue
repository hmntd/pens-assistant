<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';
import AppLogo from '@/components/AppLogo.vue';
import LangSelect from '@/components/landing/atoms/LangSelect.vue';
import ThemeToggleBtn from '@/components/landing/atoms/ThemeToggleBtn.vue';
import { home } from '@/routes';
import { ArrowRight } from '@lucide/vue';
import { AsciiScreen } from '@/lib/AsciiScreen';
import gridData from '@/data/asciiFramesGrid404.json';

const props = defineProps<{
    status?: number;
}>();

const { t } = useI18n();

const screenEl = ref<HTMLElement | null>(null);
let asciiScreen: AsciiScreen | null = null;
let currentFrameIndex = 0;
let frameInterval: ReturnType<typeof setInterval> | null = null;

const statusCode = computed(() => props.status || 404);

onMounted(() => {
    if (!screenEl.value) return;

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
    if (frames.length === 0) return;

    const drawFrame = (frameIdx: number) => {
        if (!asciiScreen) return;
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

    <Head :title="`${statusCode} - ${t('error.pageNotFound')}`" />

    <div
        class="relative min-h-screen w-full overflow-hidden bg-white text-black dark:bg-black dark:text-white flex flex-col justify-between selection:bg-main selection:text-white font-sans transition-colors duration-300">

        <header class="relative z-30 flex items-center justify-between px-6 py-5 max-w-7xl w-full mx-auto">
            <div class="flex items-center gap-3">
                <AppLogo class="h-9 w-9 text-main" />
            </div>

            <div class="flex items-center gap-3">
                <LangSelect />
                <ThemeToggleBtn />
            </div>
        </header>

        <div class="hero-art pointer-events-none absolute inset-0 z-10 overflow-hidden select-none" aria-hidden="true">
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 flex items-center justify-center">
                <pre ref="screenEl"
                    class="text-center font-mono whitespace-pre leading-none drop-shadow-[0_0_14px_rgba(49,222,151,0.5)] text-[6px] sm:text-[9px] md:text-[11px] lg:text-[13px] xl:text-[15px]"></pre>
            </div>
        </div>

        <main class="pointer-events-none absolute inset-0 z-20 flex items-center justify-center p-4">
            <Link :href="home()"
                class="pointer-events-auto inline-flex items-center gap-3.5 font-mono text-lg sm:text-2xl md:text-3xl font-extrabold text-main whitespace-nowrap cursor-pointer bg-white/80 dark:bg-black/30 px-6 py-3 rounded-2xl backdrop-blur-md border border-gray-100 dark:border-white/5 transition-all duration-300">
                <span>404 {{ t('error.pageNotFound') }}</span>
                <ArrowRight class="h-6 w-6 sm:h-7 sm:w-7 shrink-0" />
            </Link>
        </main>

        <div class="flex-1"></div>

        <footer
            class="relative z-30 py-4 text-center text-xs text-black/50 dark:text-white/50 font-medium select-none transition-colors duration-300">
            &copy; {{ new Date().getFullYear() }} PensAssistant. {{ t('footer.rights') }}
        </footer>
    </div>
</template>