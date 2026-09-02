<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { useTemplateRef } from 'vue';
import { useI18n } from '@/composables/useI18n';
import DestroyProfileController from '@/actions/App/Http/Controllers/Settings/DestroyProfileController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';

const { t } = useI18n();
const passwordInput = useTemplateRef('passwordInput');
</script>

<template>
    <div class="space-y-6">
        <Heading
            variant="small"
            :title="t('settings.deleteUser.title')"
            :description="t('settings.deleteUser.description')"
        />
        <div
            class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10"
        >
            <div class="relative space-y-0.5 text-red-600 dark:text-red-100">
                <p class="font-medium">{{ t('settings.deleteUser.warningTitle') }}</p>
                <p class="text-sm">
                    {{ t('settings.deleteUser.warningText') }}
                </p>
            </div>
            <Dialog>
                <DialogTrigger as-child>
                    <Button variant="destructive" data-test="delete-user-button">
                        {{ t('settings.deleteUser.btnDelete') }}
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <Form
                        v-bind="DestroyProfileController.form()"
                        reset-on-success
                        @error="() => passwordInput?.focus()"
                        :options="{
                            preserveScroll: true,
                        }"
                        class="space-y-6"
                        v-slot="{ errors, processing, reset, clearErrors }"
                    >
                        <DialogHeader class="space-y-3">
                            <DialogTitle>
                                {{ t('settings.deleteUser.modalTitle') }}
                            </DialogTitle>
                            <DialogDescription>
                                {{ t('settings.deleteUser.modalDesc') }}
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="password" class="sr-only">
                                {{ t('settings.deleteUser.passwordLabel') }}
                            </Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                ref="passwordInput"
                                :placeholder="t('settings.deleteUser.passwordLabel')"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button
                                    variant="secondary"
                                    @click="
                                        () => {
                                            clearErrors();
                                            reset();
                                        }
                                    "
                                >
                                    {{ t('settings.deleteUser.cancelBtn') }}
                                </Button>
                            </DialogClose>

                            <Button
                                type="submit"
                                variant="destructive"
                                :disabled="processing"
                                data-test="confirm-delete-user-button"
                            >
                                {{ t('settings.deleteUser.btnDelete') }}
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
