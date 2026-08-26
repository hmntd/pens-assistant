<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { LogOut, Settings, Shield } from '@lucide/vue';
import { useI18n } from '@/composables/useI18n';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import UserInfo from '@/components/UserInfo.vue';
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
        <DropdownMenuItem v-if="user.is_admin" :as-child="true" class="cursor-pointer text-slate-800 dark:text-slate-100 hover:bg-slate-100 dark:hover:bg-zinc-800 focus:bg-slate-100 dark:focus:bg-zinc-800 focus:text-slate-900 dark:focus:text-white font-bold text-amber-600 dark:text-amber-400">
            <Link class="flex items-center w-full px-2 py-1.5" href="/admin">
                <Shield class="mr-2 h-4 w-4 text-amber-500" />
                Панель адміна
            </Link>
        </DropdownMenuItem>
        <DropdownMenuItem :as-child="true" class="cursor-pointer text-slate-800 dark:text-slate-100 hover:bg-slate-100 dark:hover:bg-zinc-800 focus:bg-slate-100 dark:focus:bg-zinc-800 focus:text-slate-900 dark:focus:text-white font-medium">
            <Link class="flex items-center w-full px-2 py-1.5" :href="edit()" prefetch>
                <Settings class="mr-2 h-4 w-4 text-main" />
                {{ t('settings.nav.profile') }}
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator class="bg-slate-100 dark:bg-zinc-800" />
    <DropdownMenuItem :as-child="true" class="cursor-pointer text-slate-800 dark:text-slate-100 hover:bg-slate-100 dark:hover:bg-zinc-800 focus:bg-slate-100 dark:focus:bg-zinc-800 focus:text-slate-900 dark:focus:text-white font-medium">
        <Link
            class="flex items-center w-full px-2 py-1.5 text-red-600 dark:text-red-400"
            :href="logout()"
            @click="handleLogout"
            as="button"
            data-test="logout-button"
        >
            <LogOut class="mr-2 h-4 w-4" />
            {{ t('header.logout') }}
        </Link>
    </DropdownMenuItem>
</template>
