<script setup lang="ts">
defineProps<{
    modelValue: boolean;
    label: string;
}>();

defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
}>();
</script>

<template>
    <label class="flex items-center justify-between p-4 rounded-xl border border-slate-200/80 bg-slate-50/50 dark:border-zinc-800/80 dark:bg-zinc-900/30 cursor-pointer hover:border-slate-300 dark:hover:border-zinc-700 transition-all">
        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
            {{ label }}
        </span>
        <input 
            type="checkbox" 
            :checked="modelValue" 
            @change="$emit('update:modelValue', ($event.target as HTMLInputElement).checked)"
            class="h-4 w-4 rounded border-slate-300 text-[#31DE97] accent-[#31DE97] focus:ring-[#31DE97]" 
        />
    </label>
</template>
