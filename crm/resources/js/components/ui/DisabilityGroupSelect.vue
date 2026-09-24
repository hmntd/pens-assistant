<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { ShieldCheck, ShieldAlert, HeartPulse, UserCheck } from '@lucide/vue';

const props = defineProps<{
    modelValue: string;
    id?: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const { t } = useI18n();

const value = computed({
    get: () => props.modelValue || 'none',
    set: (val: string) => emit('update:modelValue', val),
});

const options = [
    {
        value: 'none',
        labelKey: 'profileDetails.optNone',
        icon: UserCheck,
        badge: '100%',
    },
    {
        value: 'group_1',
        labelKey: 'profileDetails.optGroup1',
        icon: ShieldAlert,
        badge: '100%',
    },
    {
        value: 'group_2',
        labelKey: 'profileDetails.optGroup2',
        icon: HeartPulse,
        badge: '90%',
    },
    {
        value: 'group_3',
        labelKey: 'profileDetails.optGroup3',
        icon: ShieldCheck,
        badge: '50%',
    },
];

function getIcon(val: string) {
    const opt = options.find((o) => o.value === val);
    return opt ? opt.icon : UserCheck;
}
</script>

<template>
    <Select v-model="value">
        <SelectTrigger
            :id="id"
            class="h-12 w-full rounded-xl border border-slate-200/90 bg-white/90 px-3.5 py-2 text-sm font-semibold text-slate-900 shadow-xs backdrop-blur-md transition-all hover:border-main focus:border-main focus:ring-2 focus:ring-main/20 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-100 cursor-pointer"
        >
            <div class="flex items-center gap-2.5 min-w-0 text-slate-900 dark:text-zinc-100 font-semibold">
                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-main/20 text-main-dark dark:text-main">
                    <component :is="getIcon(value)" class="h-4 w-4" />
                </div>
                <SelectValue class="text-slate-900 dark:text-zinc-100" />
            </div>
        </SelectTrigger>

        <SelectContent class="w-[var(--reka-select-trigger-width)] rounded-2xl border border-slate-200/80 bg-white/98 p-1.5 shadow-xl backdrop-blur-xl dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100 text-slate-900 z-50">
            <SelectItem
                v-for="opt in options"
                :key="opt.value"
                :value="opt.value"
                class="rounded-xl px-3 py-2.5 text-xs font-semibold text-slate-900 dark:text-zinc-100 cursor-pointer transition-colors hover:bg-slate-100 dark:hover:bg-zinc-900 focus:bg-main/15 dark:focus:bg-main/20 focus:text-slate-900 dark:focus:text-white data-[state=checked]:bg-main/20 data-[state=checked]:text-slate-950 dark:data-[state=checked]:text-white"
            >
                <div class="flex items-center justify-between w-full gap-2">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-main/20 text-main-dark dark:text-main">
                            <component :is="opt.icon" class="h-3.5 w-3.5" />
                        </div>
                        <span class="truncate text-slate-900 dark:text-zinc-100 font-semibold">{{ t(opt.labelKey) }}</span>
                    </div>
                    <span class="shrink-0 rounded-md bg-main/20 px-2 py-0.5 text-[10px] font-bold text-main-dark dark:text-main">
                        {{ opt.badge }}
                    </span>
                </div>
            </SelectItem>
        </SelectContent>
    </Select>
</template>
