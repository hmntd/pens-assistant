<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import UpdatePasswordController from '@/actions/App/Http/Controllers/Settings/UpdatePasswordController';
import PasswordInput from '@/components/auth/atoms/PasswordInput.vue';
import InputError from '@/components/common/atoms/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { useI18n } from '@/composables/useI18n';

defineProps<{
    passwordRules: string;
}>();

const { t } = useI18n();
</script>

<template>
    <Form
        v-bind="UpdatePasswordController.form()"
        :options="{
            preserveScroll: true,
        }"
        reset-on-success
        :reset-on-error="[
            'password',
            'password_confirmation',
            'current_password',
        ]"
        class="space-y-6"
        v-slot="{ errors, processing }"
    >
        <div class="grid gap-2">
            <Label for="current_password">{{
                t('settings.security.currentPassword')
            }}</Label>
            <PasswordInput
                id="current_password"
                name="current_password"
                class="mt-1 block w-full"
                autocomplete="current-password"
            />
            <InputError :message="errors.current_password" />
        </div>

        <div class="grid gap-2">
            <Label for="password">{{
                t('settings.security.newPassword')
            }}</Label>
            <PasswordInput
                id="password"
                name="password"
                class="mt-1 block w-full"
                autocomplete="new-password"
                :passwordrules="passwordRules"
            />
            <InputError :message="errors.password" />
        </div>

        <div class="grid gap-2">
            <Label for="password_confirmation">{{
                t('settings.security.confirmPassword')
            }}</Label>
            <PasswordInput
                id="password_confirmation"
                name="password_confirmation"
                class="mt-1 block w-full"
                autocomplete="new-password"
                :passwordrules="passwordRules"
            />
            <InputError :message="errors.password_confirmation" />
        </div>

        <div class="flex items-center gap-4">
            <Button
                :disabled="processing"
                data-test="update-password-button"
                class="cursor-pointer bg-main font-bold text-slate-950 hover:bg-main-dark"
            >
                {{ t('settings.security.updatePasswordBtn') }}
            </Button>
        </div>
    </Form>
</template>
