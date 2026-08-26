<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';
import { User, UserCheck } from '@lucide/vue';

const props = defineProps<{
    modelValue: string | null;
    disabled?: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | null): void;
}>();

const { t } = useI18n();

function select(gender: 'MALE' | 'FEMALE') {
    if (props.disabled) return;
    emit('update:modelValue', gender);
}
</script>

<template>
    <div class="relative flex items-center p-1 rounded-xl bg-slate-100 dark:bg-zinc-900 border border-slate-200/80 dark:border-zinc-800/80 select-none">
        <!-- Left Sector: Male -->
        <button
            type="button"
            @click="select('MALE')"
            :disabled="disabled"
            class="flex-1 flex items-center justify-center gap-2 py-2 px-3 rounded-lg text-xs font-bold transition-all duration-200 cursor-pointer"
            :class="[
                modelValue === 'MALE' || modelValue === 'male'
                    ? 'bg-main text-slate-950 shadow-md shadow-main/20 ring-1 ring-main/50'
                    : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/50 dark:hover:bg-zinc-800/50'
            ]"
        >
            <User class="h-4 w-4 shrink-0" :class="modelValue === 'MALE' || modelValue === 'male' ? 'text-slate-950' : 'text-slate-400 dark:text-zinc-500'" />
            <span>{{ t('gender.male') }}</span>
        </button>

        <!-- Right Sector: Female -->
        <button
            type="button"
            @click="select('FEMALE')"
            :disabled="disabled"
            class="flex-1 flex items-center justify-center gap-2 py-2 px-3 rounded-lg text-xs font-bold transition-all duration-200 cursor-pointer"
            :class="[
                modelValue === 'FEMALE' || modelValue === 'female'
                    ? 'bg-main text-slate-950 shadow-md shadow-main/20 ring-1 ring-main/50'
                    : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/50 dark:hover:bg-zinc-800/50'
            ]"
        >
            <UserCheck class="h-4 w-4 shrink-0" :class="modelValue === 'FEMALE' || modelValue === 'female' ? 'text-slate-950' : 'text-slate-400 dark:text-zinc-500'" />
            <span>{{ t('gender.female') }}</span>
        </button>
    </div>
</template>
