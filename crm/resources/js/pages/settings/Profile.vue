<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import ProfileInfoSection from '@/components/settings/organisms/ProfileInfoSection.vue';

const { t } = useI18n();
</script>

<template>

    <Head :title="t('settings.profile.title')" />

    <h1 class="sr-only">{{ t('settings.profile.title') }}</h1>

    <div class="space-y-10">
        <div class="flex flex-col space-y-6">
            <Heading variant="small" :title="t('settings.profile.title')"
                :description="t('settings.profile.description')" />

            <ProfileInfoSection />
        </div>

        <div class="pt-8 border-t border-slate-200/80 dark:border-zinc-800/80">
            <DeleteUser />
        </div>
    </div>
</template>
