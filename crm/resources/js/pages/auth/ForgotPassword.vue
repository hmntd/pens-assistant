<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import { computed, watchEffect } from 'vue';
import InputError from '@/components/common/atoms/InputError.vue';
import TextLink from '@/components/common/atoms/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useI18n } from '@/composables/useI18n';
import { login } from '@/routes';
import { email } from '@/routes/password';

const { t } = useI18n();

const title = computed(() => t('auth.forgotTitle'));
const description = computed(() => t('auth.forgotDesc'));

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
    <Head :title="t('auth.forgotTitle')" />

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600 dark:text-green-400"
    >
        {{ status }}
    </div>

    <div class="space-y-6">
        <Form v-bind="email.form()" v-slot="{ errors, processing }">
            <div class="grid gap-2">
                <Label for="email">{{ t('auth.emailLabel') }}</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    autocomplete="off"
                    autofocus
                    placeholder="email@example.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="my-6 flex items-center justify-start">
                <Button
                    class="w-full bg-main font-bold text-slate-950 hover:bg-main-dark"
                    :disabled="processing"
                    data-test="email-password-reset-link-button"
                >
                    <Spinner v-if="processing" />
                    {{ t('auth.sendResetLinkBtn') }}
                </Button>
            </div>
        </Form>

        <div class="space-x-1 text-center text-sm text-muted-foreground">
            <span>{{ t('auth.orReturnTo') }}</span>
            <TextLink :href="login.url()">{{ t('auth.loginLink') }}</TextLink>
        </div>
    </div>
</template>
