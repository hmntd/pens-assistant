<script setup lang="ts">
import { computed, watchEffect } from 'vue';
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

const { t } = useI18n();

const title = computed(() => t('auth.verifyTitle'));
const description = computed(() => t('auth.verifyDesc'));

watchEffect(() => {
    setLayoutProps({
        title: title.value,
        description: description.value,
    });
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head :title="t('auth.verifyTitle')" />

    <div
        v-if="status === 'verification-link-sent'"
        class="mb-4 text-center text-sm font-medium text-green-600 dark:text-green-400"
    >
        {{ t('auth.verifySentSuccess') }}
    </div>

    <Form
        v-bind="send.form()"
        class="space-y-6 text-center"
        v-slot="{ processing }"
    >
        <Button :disabled="processing" variant="secondary" class="w-full font-bold">
            <Spinner v-if="processing" />
            {{ t('auth.resendVerifyBtn') }}
        </Button>

        <TextLink :href="logout()" as="button" class="mx-auto block text-sm">
            {{ t('auth.logoutLink') }}
        </TextLink>
    </Form>
</template>
