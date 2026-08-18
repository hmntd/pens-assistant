<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Calendar as CalendarIcon, ChevronLeft, ChevronRight } from '@lucide/vue';

const props = defineProps<{
    modelValue?: number | string | null;
    placeholder?: string;
    minYear?: number;
    maxYear?: number;
    disabled?: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: number | null): void;
}>();

const isOpen = ref(false);
const containerRef = ref<HTMLDivElement | null>(null);

const min = props.minYear || 1950;
const max = props.maxYear || 2099;

const selectedYear = computed(() => {
    if (!props.modelValue) return null;
    return typeof props.modelValue === 'number' ? props.modelValue : parseInt(props.modelValue, 10);
});

// Decades view (shows 12 years per grid page)
const currentDecadeStart = ref(Math.floor((selectedYear.value || new Date().getFullYear()) / 10) * 10 - 1);

watch(
    () => props.modelValue,
    (val) => {
        if (val) {
            const yr = typeof val === 'number' ? val : parseInt(val, 10);
            currentDecadeStart.value = Math.floor(yr / 10) * 10 - 1;
        }
    }
);

const yearsGrid = computed(() => {
    const list = [];
    for (let i = 0; i < 12; i++) {
        const yr = currentDecadeStart.value + i;
        if (yr >= min && yr <= max) {
            list.push(yr);
        }
    }
    return list;
});

function prevDecade() {
    currentDecadeStart.value -= 10;
}

function nextDecade() {
    currentDecadeStart.value += 10;
}

function selectYear(yr: number) {
    emit('update:modelValue', yr);
    isOpen.value = false;
}

function handleClickOutside(e: MouseEvent) {
    if (containerRef.value && !containerRef.value.contains(e.target as Node)) {
        isOpen.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div ref="containerRef" class="relative inline-block w-full">
        <!-- Trigger Button -->
        <button
            type="button"
            @click="isOpen = !isOpen"
            :disabled="disabled"
            class="flex h-11 w-full items-center justify-between rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-900 shadow-sm transition-all hover:border-main focus:border-main focus:outline-none focus:ring-1 focus:ring-main dark:border-zinc-800 dark:bg-zinc-900 dark:text-white dark:hover:border-zinc-700 disabled:opacity-50 cursor-pointer"
        >
            <span :class="!selectedYear ? 'text-slate-400 dark:text-zinc-500 font-normal' : 'font-bold'">
                {{ selectedYear ? `${selectedYear} рік` : (placeholder || 'Оберіть рік') }}
            </span>
            <CalendarIcon class="h-4 w-4 text-main shrink-0" />
        </button>

        <!-- Year Picker Popover Grid -->
        <transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="isOpen"
                class="absolute left-0 z-50 mt-2 w-64 rounded-2xl border border-slate-200 bg-white p-4 shadow-xl backdrop-blur-xl dark:border-zinc-800 dark:bg-zinc-950"
            >
                <!-- Decade Navigation Header -->
                <div class="flex items-center justify-between mb-3 border-b border-slate-100 dark:border-zinc-800 pb-2">
                    <button
                        type="button"
                        @click="prevDecade"
                        class="p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-zinc-800 text-slate-600 dark:text-zinc-300 transition-colors cursor-pointer"
                    >
                        <ChevronLeft class="h-4 w-4" />
                    </button>
                    <span class="text-xs font-bold text-slate-900 dark:text-white">
                        {{ currentDecadeStart + 1 }} - {{ currentDecadeStart + 10 }}
                    </span>
                    <button
                        type="button"
                        @click="nextDecade"
                        class="p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-zinc-800 text-slate-600 dark:text-zinc-300 transition-colors cursor-pointer"
                    >
                        <ChevronRight class="h-4 w-4" />
                    </button>
                </div>

                <!-- 3x4 Grid of Years -->
                <div class="grid grid-cols-3 gap-2">
                    <button
                        v-for="yr in yearsGrid"
                        :key="yr"
                        type="button"
                        @click="selectYear(yr)"
                        class="py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer text-center"
                        :class="[
                            selectedYear === yr
                                ? 'bg-main text-slate-950 shadow-sm'
                                : 'hover:bg-slate-100 dark:hover:bg-zinc-800 text-slate-700 dark:text-zinc-300'
                        ]"
                    >
                        {{ yr }}
                    </button>
                </div>
            </div>
        </transition>
    </div>
</template>
