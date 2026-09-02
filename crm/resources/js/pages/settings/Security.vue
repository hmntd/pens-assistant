<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';
import Heading from '@/components/Heading.vue';
import type { Props as ManagePasskeysProps } from '@/components/ManagePasskeys.vue';
import ManagePasskeys from '@/components/ManagePasskeys.vue';
import type { Props as ManageTwoFactorProps } from '@/components/ManageTwoFactor.vue';
import ManageTwoFactor from '@/components/ManageTwoFactor.vue';
import UpdatePasswordForm from '@/components/settings/organisms/UpdatePasswordForm.vue';

type Props = {
    passwordRules: string;
} & ManagePasskeysProps &
    ManageTwoFactorProps;

const props = defineProps<Props>();
const { t } = useI18n();
</script>

<template>

    <Head :title="t('settings.security.title')" />

    <h1 class="sr-only">{{ t('settings.security.title') }}</h1>

    <div class="space-y-6">
        <Heading variant="small" :title="t('settings.security.title')"
            :description="t('settings.security.description')" />

        <UpdatePasswordForm :password-rules="props.passwordRules" />
    </div>

    <ManageTwoFactor :canManageTwoFactor="canManageTwoFactor" :requiresConfirmation="requiresConfirmation"
        :twoFactorEnabled="twoFactorEnabled" />

    <ManagePasskeys :canManagePasskeys="canManagePasskeys" :passkeys="passkeys" />
</template>
