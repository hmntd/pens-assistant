<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { LogOut, Settings, Shield } from '@lucide/vue';
import UserInfo from '@/components/navigation/molecules/UserInfo.vue';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { useI18n } from '@/composables/useI18n';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import type { User } from '@/types';

type Props = {
    user: User;
};

const { t } = useI18n();

const handleLogout = () => {
    router.flushAll();
};

defineProps<Props>();
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-2 py-2 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator class="bg-slate-100 dark:bg-zinc-800" />
    <DropdownMenuGroup>
        <DropdownMenuItem
            v-if="user.is_admin"
            :as-child="true"
            class="cursor-pointer font-bold text-amber-600 text-slate-800 hover:bg-slate-100 focus:bg-slate-100 focus:text-slate-900 dark:text-amber-400 dark:text-slate-100 dark:hover:bg-zinc-800 dark:focus:bg-zinc-800 dark:focus:text-white"
        >
            <Link class="flex w-full items-center px-2 py-1.5" href="/admin">
                <Shield class="mr-2 h-4 w-4 text-amber-500" />
                {{ t('header.adminPanel') }}
            </Link>
        </DropdownMenuItem>
        <DropdownMenuItem
            :as-child="true"
            class="cursor-pointer font-medium text-slate-800 hover:bg-slate-100 focus:bg-slate-100 focus:text-slate-900 dark:text-slate-100 dark:hover:bg-zinc-800 dark:focus:bg-zinc-800 dark:focus:text-white"
        >
            <Link
                class="flex w-full items-center px-2 py-1.5"
                :href="edit.url()"
                prefetch
            >
                <Settings class="mr-2 h-4 w-4 text-main" />
                {{ t('settings.nav.profile') }}
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator class="bg-slate-100 dark:bg-zinc-800" />
    <DropdownMenuItem
        :as-child="true"
        class="cursor-pointer font-medium text-slate-800 hover:bg-slate-100 focus:bg-slate-100 focus:text-slate-900 dark:text-slate-100 dark:hover:bg-zinc-800 dark:focus:bg-zinc-800 dark:focus:text-white"
    >
        <Link
            class="flex w-full items-center px-2 py-1.5 text-red-600 dark:text-red-400"
            :href="logout.url()"
            method="post"
            @click="handleLogout"
            as="button"
            data-test="logout-button"
        >
            <LogOut class="mr-2 h-4 w-4" />
            {{ t('header.logout') }}
        </Link>
    </DropdownMenuItem>
</template>
