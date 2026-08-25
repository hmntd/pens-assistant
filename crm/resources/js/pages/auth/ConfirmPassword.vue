<script setup lang="ts">
import { computed, watchEffect } from 'vue';
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';
import {
    index as confirmOptions,
    store as confirmStore,
} from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyConfirmationController';
import InputError from '@/components/InputError.vue';
import PasskeyVerify from '@/components/PasskeyVerify.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/password/confirm';

const { t } = useI18n();

const title = computed(() => t('auth.confirmTitle'));
const description = computed(() => t('auth.confirmDesc'));

watchEffect(() => {
    setLayoutProps({
        title: title.value,
        description: description.value,
    });
});
</script>

<template>
    <Head :title="t('auth.confirmTitle')" />

    <PasskeyVerify
        :routes="{
            options: confirmOptions(),
            submit: confirmStore(),
        }"
        label="Confirm with passkey"
        loading-label="Confirming..."
        separator="Or confirm with password"
    />

    <Form
        v-bind="store.form()"
        reset-on-success
        v-slot="{ errors, processing }"
    >
        <div class="space-y-6">
            <div class="grid gap-2">
                <Label htmlFor="password">{{ t('auth.passwordLabel') }}</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="current-password"
                    autofocus
                />

                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center">
                <Button
                    class="w-full bg-main text-slate-950 hover:bg-main-dark font-bold"
                    :disabled="processing"
                    data-test="confirm-password-button"
                >
                    <Spinner v-if="processing" />
                    {{ t('auth.confirmBtn') }}
                </Button>
            </div>
        </div>
    </Form>
</template>
