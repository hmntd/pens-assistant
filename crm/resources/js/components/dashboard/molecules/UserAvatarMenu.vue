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
                class="group flex items-center gap-3 rounded-xl border border-slate-200/80 bg-white/70 py-1.5 px-2.5 shadow-sm backdrop-blur-md transition-all duration-200 hover:border-main/50 hover:bg-slate-100 dark:border-zinc-800/80 dark:bg-zinc-900/70 dark:hover:border-main/40 dark:hover:bg-zinc-800 cursor-pointer"
            >
                <Avatar class="h-8 w-8 overflow-hidden rounded-full ring-2 ring-main/30">
                    <AvatarImage v-if="user?.avatar" :src="user.avatar" :alt="user.name" />
                    <AvatarFallback class="bg-main/20 font-bold text-main-dark dark:text-main">
                        {{ getInitials(user?.name) }}
                    </AvatarFallback>
                </Avatar>

                <div class="hidden flex-col items-start sm:flex text-left">
                    <span class="text-xs font-bold text-slate-900 dark:text-white group-hover:text-main-dark dark:group-hover:text-main transition-colors">
                        {{ user?.first_name ? `${user.first_name} ${user.last_name || ''}` : (user?.name || 'Користувач') }}
                    </span>
                    <span class="text-[10px] text-slate-500 dark:text-zinc-400 truncate max-w-[140px]">
                        {{ user?.email }}
                    </span>
                </div>
            </button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" class="w-56 rounded-2xl p-1 shadow-xl bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 text-slate-900 dark:text-white">
            <UserMenuContent v-if="user" :user="user" />
        </DropdownMenuContent>
    </DropdownMenu>
</template>
