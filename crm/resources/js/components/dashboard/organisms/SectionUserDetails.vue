<script setup lang="ts">
import { ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';
import { User, ShieldAlert, Award, Check, Save } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import DatePicker from '@/components/ui/DatePicker.vue';
import YearPicker from '@/components/ui/YearPicker.vue';
import DisabilityGroupSelect from '@/components/ui/DisabilityGroupSelect.vue';
import GenderSelectButton from '@/components/ui/GenderSelectButton.vue';

const { t } = useI18n();
const page = usePage();
const user = page.props.auth?.user;

const savedSuccess = ref(false);

const form = useForm({
    first_name: user?.first_name || '',
    last_name: user?.last_name || '',
    email: user?.email || '',
    gender: user?.gender || null,
    date_of_birth: user?.date_of_birth || '',
    disability_group: user?.disability_group || 'none',
    pension_type: 'age',
    target_retirement_year: user?.target_retirement_year ?? '',
    benefits: user?.benefits || [],
});

function toggleBenefit(benefitKey: string) {
    const list = [...(form.benefits || [])];
    const index = list.indexOf(benefitKey);
    if (index >= 0) {
        list.splice(index, 1);
    } else {
        list.push(benefitKey);
    }
    form.benefits = list;
}

function saveDetails() {
    if (form.target_retirement_year === '' as any) {
        form.target_retirement_year = null as any;
    }
    form.patch('/settings/profile', {
        preserveScroll: true,
        onSuccess: () => {
            savedSuccess.value = true;
            window.dispatchEvent(new CustomEvent('notification-created'));
            setTimeout(() => (savedSuccess.value = false), 3000);
        },
    });
}
</script>

<template>
    <div class="space-y-8">
        <!-- Header -->
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                    <User class="h-6 w-6 text-main" />
                    {{ t('profileDetails.title') }}
                </h2>
                <p class="text-xs text-slate-500 dark:text-zinc-400">
                    {{ t('profileDetails.subtitle') }}
                </p>
            </div>
            <div v-if="savedSuccess" class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/20 px-3 py-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                <Check class="h-3.5 w-3.5" />
                {{ t('profileDetails.savedSuccess') }}
            </div>
        </div>

        <form @submit.prevent="saveDetails" class="grid grid-cols-1 gap-8 lg:grid-cols-12 items-start">
            <!-- Left Column: Name & Contact Info -->
            <div class="lg:col-span-6 rounded-2xl border border-slate-200/80 bg-white/70 p-6 shadow-sm backdrop-blur-md dark:border-zinc-800/80 dark:bg-zinc-950/80 space-y-6 self-start h-fit">
                <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-zinc-800/60 pb-3 flex items-center gap-2">
                    <User class="h-4 w-4 text-main" />
                    {{ t('profileDetails.mainInfoTitle') }}
                </h3>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="first_name">{{ t('profileDetails.firstName') }}</Label>
                        <Input id="first_name" v-model="form.first_name" required />
                    </div>
                    <div class="grid gap-2">
                        <Label for="last_name">{{ t('profileDetails.lastName') }}</Label>
                        <Input id="last_name" v-model="form.last_name" required />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="email">{{ t('profileDetails.email') }}</Label>
                    <Input id="email" type="email" v-model="form.email" required />
                </div>

                <!-- Gender Selection (2 Sectors: Male Left, Female Right) -->
                <div class="grid gap-2">
                    <Label for="gender" class="flex items-center justify-between">
                        <span>{{ t('gender.label') }}</span>
                        <span v-if="!form.gender" class="text-[10px] text-amber-500 font-bold">{{ t('gender.notSpecified') }}</span>
                    </Label>
                    <GenderSelectButton
                        id="gender"
                        v-model="form.gender"
                    />
                </div>

                <!-- Date of Birth Picker -->
                <div class="grid gap-2">
                    <Label for="date_of_birth">{{ t('profileDetails.dateOfBirth') }}</Label>
                    <DatePicker
                        id="date_of_birth"
                        v-model="form.date_of_birth"
                        :placeholder="t('profileDetails.selectDob')"
                    />
                </div>

                <!-- Target Retirement YearPicker -->
                <div class="grid gap-2">
                    <Label for="target_year" class="flex items-center justify-between">
                        <span>{{ t('profileDetails.targetYear') }}</span>
                        <span v-if="!form.target_retirement_year" class="text-[10px] text-amber-500 font-bold">{{ t('profileDetails.notSpecifiedWarning') }}</span>
                    </Label>
                    <YearPicker
                        id="target_year"
                        v-model="form.target_retirement_year"
                        :min-year="1990"
                        :max-year="2070"
                        placeholder="Оберіть рік виходу на пенсію"
                    />
                </div>
            </div>

            <!-- Right Column: Disability Group & Special Categories -->
            <div class="lg:col-span-6 rounded-2xl border border-slate-200/80 bg-white/70 p-6 shadow-sm backdrop-blur-md dark:border-zinc-800/80 dark:bg-zinc-950/80 space-y-6 self-start h-fit">
                <h3 class="text-base font-bold text-slate-900 dark:text-white border-b border-slate-100 dark:border-zinc-800/60 pb-3 flex items-center gap-2">
                    <ShieldAlert class="h-4 w-4 text-main" />
                    {{ t('profileDetails.disabilityTitle') }}
                </h3>

                <!-- Disability Group Selection -->
                <div class="grid gap-2">
                    <Label for="disability_group" class="font-bold">{{ t('profileDetails.disabilityGroupLabel') }}</Label>
                    <DisabilityGroupSelect
                        id="disability_group"
                        v-model="form.disability_group"
                    />
                </div>

                <!-- Benefits Checkboxes -->
                <div class="space-y-3 pt-2">
                    <Label class="font-bold">{{ t('profileDetails.benefitStatusLabel') }}</Label>
                    
                    <div
                        @click="toggleBenefit('chornobyl')"
                        class="flex items-center justify-between p-3 rounded-xl border transition-colors cursor-pointer"
                        :class="form.benefits?.includes('chornobyl') ? 'border-main/50 bg-main/10 dark:bg-main/15' : 'border-slate-200 dark:border-zinc-800 bg-slate-50/50 dark:bg-zinc-900/50'"
                    >
                        <div class="flex items-center gap-3">
                            <Award class="h-5 w-5 text-main" />
                            <div>
                                <h4 class="text-xs font-bold text-slate-900 dark:text-white">{{ t('profileDetails.benefitChornobylTitle') }}</h4>
                                <p class="text-[10px] text-slate-500 dark:text-zinc-400">{{ t('profileDetails.benefitChornobylDesc') }}</p>
                            </div>
                        </div>
                        <input type="checkbox" :checked="form.benefits?.includes('chornobyl')" class="h-4 w-4 rounded accent-main" />
                    </div>

                    <div
                        @click="toggleBenefit('combat_participant')"
                        class="flex items-center justify-between p-3 rounded-xl border transition-colors cursor-pointer"
                        :class="form.benefits?.includes('combat_participant') ? 'border-main/50 bg-main/10 dark:bg-main/15' : 'border-slate-200 dark:border-zinc-800 bg-slate-50/50 dark:bg-zinc-900/50'"
                    >
                        <div class="flex items-center gap-3">
                            <Award class="h-5 w-5 text-main" />
                            <div>
                                <h4 class="text-xs font-bold text-slate-900 dark:text-white">{{ t('profileDetails.benefitUbdTitle') }}</h4>
                                <p class="text-[10px] text-slate-500 dark:text-zinc-400">{{ t('profileDetails.benefitUbdDesc') }}</p>
                            </div>
                        </div>
                        <input type="checkbox" :checked="form.benefits?.includes('combat_participant')" class="h-4 w-4 rounded accent-main" />
                    </div>
                </div>

                <div class="pt-4">
                    <Button
                        type="submit"
                        class="w-full bg-main text-slate-950 hover:bg-main-dark font-bold shadow-md"
                        :disabled="form.processing"
                    >
                        <Save class="mr-2 h-4 w-4" />
                        {{ form.processing ? t('profileDetails.savingBtn') : t('profileDetails.saveBtn') }}
                    </Button>
                </div>
            </div>
        </form>
    </div>
</template>
