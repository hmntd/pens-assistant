<script setup lang="ts">
import AppContent from '@/components/navigation/organisms/AppContent.vue';
import AppShell from '@/components/navigation/organisms/AppShell.vue';
import AppSidebar from '@/components/navigation/organisms/AppSidebar.vue';
import AppSidebarHeader from '@/components/navigation/molecules/AppSidebarHeader.vue';
import { Toaster } from '@/components/ui/sonner';
import { useDocumentNotifier } from '@/composables/useDocumentNotifier';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

useDocumentNotifier();
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
        <Toaster />
    </AppShell>
</template>
