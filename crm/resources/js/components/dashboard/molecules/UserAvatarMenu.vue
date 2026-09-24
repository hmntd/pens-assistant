<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import UserMenuContent from '@/components/navigation/molecules/UserMenuContent.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { getInitials } from '@/composables/useInitials';

const page = usePage();
const user = computed(() => page.props.auth?.user);
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <button
                type="button"
                class="group inline-flex h-9 cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-2 text-slate-700 shadow-sm transition-colors hover:bg-slate-100 hover:text-slate-900 dark:border-zinc-800 dark:bg-black dark:text-slate-300 dark:hover:bg-zinc-900 dark:hover:text-slate-100"
            >
                <Avatar
                    class="h-6 w-6 overflow-hidden rounded-full ring-1 ring-main/40"
                >
                    <AvatarImage
                        v-if="user?.avatar"
                        :src="user.avatar"
                        :alt="user.name"
                    />
                    <AvatarFallback
                        class="bg-main/20 text-[10px] font-bold text-main-dark dark:text-main"
                    >
                        {{ getInitials(user?.name) }}
                    </AvatarFallback>
                </Avatar>

                <div class="hidden flex-col items-start text-left sm:flex">
                    <span
                        class="text-xs leading-none font-bold text-slate-900 transition-colors group-hover:text-main-dark dark:text-white dark:group-hover:text-main"
                    >
                        {{
                            user?.first_name
                                ? `${user.first_name} ${user.last_name || ''}`
                                : user?.name || 'Користувач'
                        }}
                    </span>
                </div>
            </button>
        </DropdownMenuTrigger>

        <DropdownMenuContent
            align="end"
            class="w-56 rounded-2xl border border-slate-200 bg-white p-1 text-slate-900 shadow-xl dark:border-zinc-800 dark:bg-zinc-950 dark:text-white"
        >
            <UserMenuContent v-if="user" :user="user" />
        </DropdownMenuContent>
    </DropdownMenu>
</template>
