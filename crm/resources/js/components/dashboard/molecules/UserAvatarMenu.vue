<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { getInitials } from '@/composables/useInitials';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

const page = usePage();
const user = computed(() => page.props.auth?.user);
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <button
                type="button"
                class="group inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-2 text-slate-700 shadow-sm transition-colors hover:bg-slate-100 hover:text-slate-900 dark:border-zinc-800 dark:bg-black dark:text-slate-300 dark:hover:bg-zinc-900 dark:hover:text-slate-100 cursor-pointer"
            >
                <Avatar class="h-6 w-6 overflow-hidden rounded-full ring-1 ring-main/40">
                    <AvatarImage v-if="user?.avatar" :src="user.avatar" :alt="user.name" />
                    <AvatarFallback class="bg-main/20 text-[10px] font-bold text-main-dark dark:text-main">
                        {{ getInitials(user?.name) }}
                    </AvatarFallback>
                </Avatar>

                <div class="hidden flex-col items-start sm:flex text-left">
                    <span class="text-xs font-bold text-slate-900 dark:text-white group-hover:text-main-dark dark:group-hover:text-main transition-colors leading-none">
                        {{ user?.first_name ? `${user.first_name} ${user.last_name || ''}` : (user?.name || 'Користувач') }}
                    </span>
                </div>
            </button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" class="w-56 rounded-2xl p-1 shadow-xl bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 text-slate-900 dark:text-white">
            <UserMenuContent v-if="user" :user="user" />
        </DropdownMenuContent>
    </DropdownMenu>
</template>
