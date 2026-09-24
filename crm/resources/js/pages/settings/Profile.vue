<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/common/atoms/Heading.vue';
import DeleteUser from '@/components/settings/organisms/DeleteUser.vue';
import ProfileInfoSection from '@/components/settings/organisms/ProfileInfoSection.vue';
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();
</script>

<template>
    <Head :title="t('settings.profile.title')" />

    <h1 class="sr-only">{{ t('settings.profile.title') }}</h1>

    <div class="space-y-10">
        <div class="flex flex-col space-y-6">
            <Heading
                variant="small"
                :title="t('settings.profile.title')"
                :description="t('settings.profile.description')"
            />

            <ProfileInfoSection />
        </div>

        <div class="border-t border-slate-200/80 pt-8 dark:border-zinc-800/80">
            <DeleteUser />
        </div>
    </div>
</template>
