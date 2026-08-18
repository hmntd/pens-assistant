<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Calendar as CalendarIcon, ChevronLeft, ChevronRight } from '@lucide/vue';

const props = defineProps<{
    modelValue?: string | null; // Format YYYY-MM-DD
    placeholder?: string;
    disabled?: boolean;
    id?: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | null): void;
}>();

type ViewMode = 'days' | 'months' | 'years';

const isOpen = ref(false);
const viewMode = ref<ViewMode>('days');
const containerRef = ref<HTMLDivElement | null>(null);

const currentYear = ref(new Date().getFullYear());
const currentMonth = ref(new Date().getMonth()); // 0 - 11
const decadeStart = ref(Math.floor(new Date().getFullYear() / 10) * 10 - 1);

const monthNames = [
    'Січень', 'Лютий', 'Березень', 'Квітень', 'Травень', 'Червень',
    'Липень', 'Серпень', 'Вересень', 'Жовтень', 'Листопад', 'Грудень'
];

const dayNames = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Нд'];

const inputText = ref('');

// Format YYYY-MM-DD into DD.MM.YYYY for input display
function toDisplayFormat(val?: string | null): string {
    if (!val) return '';
    const parts = val.split('-');
    if (parts.length !== 3) return val;
    return `${parts[2]}.${parts[1]}.${parts[0]}`;
}

// Parse typed input text (DD.MM.YYYY or YYYY-MM-DD) into YYYY-MM-DD
function parseInputText(text: string): string | null {
    const trimmed = text.trim();
    if (!trimmed) return null;

    // Matches DD.MM.YYYY
    const dotsMatch = trimmed.match(/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/);
    if (dotsMatch) {
        const d = parseInt(dotsMatch[1], 10);
        const m = parseInt(dotsMatch[2], 10);
        const y = parseInt(dotsMatch[3], 10);
        if (m >= 1 && m <= 12 && d >= 1 && d <= 31 && y >= 1900 && y <= 2100) {
            return `${y}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        }
    }

    // Matches YYYY-MM-DD
    const isoMatch = trimmed.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
    if (isoMatch) {
        const y = parseInt(isoMatch[1], 10);
        const m = parseInt(isoMatch[2], 10);
        const d = parseInt(isoMatch[3], 10);
        if (m >= 1 && m <= 12 && d >= 1 && d <= 31 && y >= 1900 && y <= 2100) {
            return `${y}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        }
    }

    return null;
}

watch(
    () => props.modelValue,
    (val) => {
        inputText.value = toDisplayFormat(val);
        if (val) {
            const parts = val.split('-');
            if (parts.length === 3) {
                const y = parseInt(parts[0], 10);
                const m = parseInt(parts[1], 10) - 1;
                currentYear.value = y;
                currentMonth.value = m;
                decadeStart.value = Math.floor(y / 10) * 10 - 1;
            }
        }
    },
    { immediate: true }
);

function onTextInput(e: Event) {
    const val = (e.target as HTMLInputElement).value;
    inputText.value = val;
    const parsed = parseInputText(val);
    if (parsed) {
        emit('update:modelValue', parsed);
    }
}

function onInputBlur() {
    const parsed = parseInputText(inputText.value);
    if (parsed) {
        emit('update:modelValue', parsed);
    } else if (inputText.value.trim() === '') {
        emit('update:modelValue', null);
    } else {
        // Reset to valid modelValue formatting if typing was incomplete
        inputText.value = toDisplayFormat(props.modelValue);
    }
}

const daysInMonth = computed(() => {
    return new Date(currentYear.value, currentMonth.value + 1, 0).getDate();
});

const firstDayOffset = computed(() => {
    const day = new Date(currentYear.value, currentMonth.value, 1).getDay();
    return day === 0 ? 6 : day - 1;
});

const calendarDays = computed(() => {
    const days = [];
    for (let i = 0; i < firstDayOffset.value; i++) {
        days.push(null);
    }
    for (let d = 1; d <= daysInMonth.value; d++) {
        days.push(d);
    }
    return days;
});

const yearsGrid = computed(() => {
    const list = [];
    for (let i = 0; i < 12; i++) {
        list.push(decadeStart.value + i);
    }
    return list;
});

function prevHeader() {
    if (viewMode.value === 'days') {
        if (currentMonth.value === 0) {
            currentMonth.value = 11;
            currentYear.value--;
        } else {
            currentMonth.value--;
        }
    } else if (viewMode.value === 'years') {
        decadeStart.value -= 10;
    }
}

function nextHeader() {
    if (viewMode.value === 'days') {
        if (currentMonth.value === 11) {
            currentMonth.value = 0;
            currentYear.value++;
        } else {
            currentMonth.value++;
        }
    } else if (viewMode.value === 'years') {
        decadeStart.value += 10;
    }
}

function selectDay(day: number | null) {
    if (!day) return;
    const m = String(currentMonth.value + 1).padStart(2, '0');
    const d = String(day).padStart(2, '0');
    const selected = `${currentYear.value}-${m}-${d}`;
    emit('update:modelValue', selected);
    isOpen.value = false;
}

function selectMonth(mIdx: number) {
    currentMonth.value = mIdx;
    viewMode.value = 'days';
    isOpen.value = true;
}

function selectYear(y: number) {
    currentYear.value = y;
    decadeStart.value = Math.floor(y / 10) * 10 - 1;
    viewMode.value = 'months';
    isOpen.value = true;
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
        <!-- Direct Typed Input with Calendar Trigger Icon -->
        <div class="relative flex items-center">
            <input
                :id="id"
                type="text"
                :value="inputText"
                @input="onTextInput"
                @blur="onInputBlur"
                @focus="isOpen = true"
                :disabled="disabled"
                :placeholder="placeholder || 'ДД.ММ.РРРР'"
                class="flex h-11 w-full rounded-xl border border-slate-200 bg-white pl-3.5 pr-10 py-2 text-sm font-bold text-slate-900 shadow-sm transition-all hover:border-main focus:border-main focus:outline-none focus:ring-1 focus:ring-main dark:border-zinc-800 dark:bg-zinc-900 dark:text-white dark:hover:border-zinc-700 disabled:opacity-50"
            />
            <button
                type="button"
                @click="isOpen = !isOpen"
                :disabled="disabled"
                class="absolute right-2.5 p-1 text-main hover:text-main-dark cursor-pointer"
            >
                <CalendarIcon class="h-4 w-4" />
            </button>
        </div>

        <!-- Popover Calendar with Month & Year View Switchers -->
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
                @mousedown.prevent
                class="absolute left-0 z-50 mt-2 w-72 rounded-2xl border border-slate-200 bg-white p-4 shadow-xl backdrop-blur-xl dark:border-zinc-800 dark:bg-zinc-950"
            >
                <!-- Popover Header -->
                <div class="flex items-center justify-between mb-3 border-b border-slate-100 dark:border-zinc-800 pb-2">
                    <button
                        type="button"
                        @click="prevHeader"
                        :disabled="viewMode === 'months'"
                        class="p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-zinc-800 text-slate-600 dark:text-zinc-300 transition-colors cursor-pointer disabled:opacity-30"
                    >
                        <ChevronLeft class="h-4 w-4" />
                    </button>

                    <!-- Clickable Month & Year Header Switchers -->
                    <div class="flex items-center gap-1 text-xs font-bold text-slate-900 dark:text-white">
                        <button
                            type="button"
                            @click="viewMode = viewMode === 'months' ? 'days' : 'months'"
                            class="px-1.5 py-0.5 rounded-md hover:bg-slate-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer text-main-dark dark:text-main"
                        >
                            {{ monthNames[currentMonth] }}
                        </button>
                        <button
                            type="button"
                            @click="viewMode = viewMode === 'years' ? 'days' : 'years'"
                            class="px-1.5 py-0.5 rounded-md hover:bg-slate-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer hover:underline"
                        >
                            {{ viewMode === 'years' ? `${decadeStart + 1} - ${decadeStart + 10}` : currentYear }}
                        </button>
                    </div>

                    <button
                        type="button"
                        @click="nextHeader"
                        :disabled="viewMode === 'months'"
                        class="p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-zinc-800 text-slate-600 dark:text-zinc-300 transition-colors cursor-pointer disabled:opacity-30"
                    >
                        <ChevronRight class="h-4 w-4" />
                    </button>
                </div>

                <!-- VIEW 1: Days Grid -->
                <template v-if="viewMode === 'days'">
                    <div class="grid grid-cols-7 gap-1 text-center mb-1">
                        <span
                            v-for="day in dayNames"
                            :key="day"
                            class="text-[10px] font-bold text-slate-400 dark:text-zinc-500 uppercase"
                        >
                            {{ day }}
                        </span>
                    </div>

                    <div class="grid grid-cols-7 gap-1">
                        <div
                            v-for="(day, idx) in calendarDays"
                            :key="idx"
                            class="aspect-square flex items-center justify-center text-xs font-semibold"
                        >
                            <button
                                v-if="day !== null"
                                type="button"
                                @click="selectDay(day)"
                                class="h-7 w-7 rounded-lg flex items-center justify-center transition-all cursor-pointer"
                                :class="[
                                    modelValue === `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`
                                        ? 'bg-main text-slate-950 font-extrabold shadow-sm'
                                        : 'hover:bg-slate-100 dark:hover:bg-zinc-800 text-slate-700 dark:text-zinc-300'
                                ]"
                            >
                                {{ day }}
                            </button>
                        </div>
                    </div>
                </template>

                <!-- VIEW 2: 12-Month Grid Selector -->
                <template v-else-if="viewMode === 'months'">
                    <div class="grid grid-cols-3 gap-2">
                        <button
                            v-for="(mName, idx) in monthNames"
                            :key="mName"
                            type="button"
                            @click="selectMonth(idx)"
                            class="py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer text-center"
                            :class="[
                                currentMonth === idx
                                    ? 'bg-main text-slate-950 shadow-sm font-extrabold'
                                    : 'hover:bg-slate-100 dark:hover:bg-zinc-800 text-slate-700 dark:text-zinc-300'
                            ]"
                        >
                            {{ mName }}
                        </button>
                    </div>
                </template>

                <!-- VIEW 3: 12-Year Grid Selector -->
                <template v-else-if="viewMode === 'years'">
                    <div class="grid grid-cols-3 gap-2">
                        <button
                            v-for="yr in yearsGrid"
                            :key="yr"
                            type="button"
                            @click="selectYear(yr)"
                            class="py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer text-center"
                            :class="[
                                currentYear === yr
                                    ? 'bg-main text-slate-950 shadow-sm font-extrabold'
                                    : 'hover:bg-slate-100 dark:hover:bg-zinc-800 text-slate-700 dark:text-zinc-300'
                            ]"
                        >
                            {{ yr }}
                        </button>
                    </div>
                </template>
            </div>
        </transition>
    </div>
</template>
