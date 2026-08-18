<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const { t } = useI18n();
const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <Head :title="t('settings.profile.title')" />

    <h1 class="sr-only">{{ t('settings.profile.title') }}</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            :title="t('settings.profile.title')"
            :description="t('settings.profile.description')"
        />

        <Form
            v-bind="ProfileController.update.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="first_name">{{ t('profileDetails.firstName') }}</Label>
                    <Input
                        id="first_name"
                        class="mt-1 block w-full"
                        name="first_name"
                        :default-value="user.first_name"
                        required
                        autocomplete="given-name"
                    />
                    <InputError class="mt-2" :message="errors.first_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="last_name">{{ t('profileDetails.lastName') }}</Label>
                    <Input
                        id="last_name"
                        class="mt-1 block w-full"
                        name="last_name"
                        :default-value="user.last_name"
                        required
                        autocomplete="family-name"
                    />
                    <InputError class="mt-2" :message="errors.last_name" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="email">{{ t('profileDetails.email') }}</Label>
                <Input
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    name="email"
                    :default-value="user.email"
                    required
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="errors.email" />
            </div>

            <div v-if="page.props.mustVerifyEmail && !user.email_verified_at">
                <p class="-mt-4 text-sm text-muted-foreground">
                    {{ t('settings.profile.unverifiedEmail') }}
                    <Link
                        href="/email/verification-notification"
                        as="button"
                        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                    >
                        {{ t('settings.profile.resendVerification') }}
                    </Link>
                </p>
            </div>

            <div class="flex items-center gap-4">
                <Button :disabled="processing" data-test="update-profile-button" class="bg-main text-slate-950 font-bold hover:bg-main-dark">
                    {{ t('settings.profile.saveBtn') }}
                </Button>
            </div>
        </Form>
    </div>

    <DeleteUser />
</template>
