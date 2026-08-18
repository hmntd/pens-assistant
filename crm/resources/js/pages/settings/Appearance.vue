<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';

const { t } = useI18n();
</script>

<template>
    <Head :title="t('settings.appearance.title')" />

    <h1 class="sr-only">{{ t('settings.appearance.title') }}</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            :title="t('settings.appearance.title')"
            :description="t('settings.appearance.description')"
        />
        <AppearanceTabs />
    </div>
</template>
