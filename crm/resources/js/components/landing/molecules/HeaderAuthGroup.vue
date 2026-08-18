<script setup lang="ts">
import { usePage, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import { dashboard, login, register } from '@/routes';

const page = usePage();
const { t } = useI18n();

const isAuthenticated = computed(() => !!page.props.auth?.user);
</script>

<template>
    <div class="flex items-center gap-2">
        <template v-if="isAuthenticated">
            <Link
                :href="dashboard()"
                class="inline-flex h-9 items-center justify-center rounded-lg bg-main px-4 text-xs font-semibold text-slate-950 shadow-sm transition-all hover:bg-main-hover hover:shadow"
            >
                {{ t('header.dashboard') }}
            </Link>
        </template>
        <template v-else>
            <Link
                :href="login()"
                class="inline-flex h-9 items-center justify-center rounded-lg bg-main px-4 text-xs font-semibold text-slate-950 shadow-sm transition-all hover:bg-main-hover"
            >
                {{ t('header.signIn') }}
            </Link>
            <Link
                :href="register()"
                class="hidden h-9 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-700 shadow-sm transition-all hover:bg-slate-50 dark:border-zinc-800 dark:bg-black dark:text-slate-300 dark:hover:bg-zinc-900 sm:inline-flex"
            >
                {{ t('header.signUp') }}
            </Link>
        </template>
    </div>
</template>
