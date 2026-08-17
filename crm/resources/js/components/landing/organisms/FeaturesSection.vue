<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';
import BadgeTag from '../atoms/BadgeTag.vue';
import FeatureCard from '../molecules/FeatureCard.vue';
import { Cpu, ScanLine, Clock, ShieldAlert } from '@lucide/vue';

const { t } = useI18n();

const featureIcons = [Cpu, ScanLine, Clock, ShieldAlert];
</script>

<template>
    <section id="features" class="py-16 lg:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            
            <!-- Section Header -->
            <div class="mx-auto max-w-3xl text-center">
                <BadgeTag class="mb-4">
                    {{ t('features.badge') }}
                </BadgeTag>
                <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl dark:text-white">
                    {{ t('features.title') }}
                </h2>
                <p class="mt-4 text-base text-slate-600 dark:text-slate-400">
                    {{ t('features.subtitle') }}
                </p>
            </div>

            <!-- Features Grid -->
            <div class="mt-16 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                <FeatureCard
                    :title="t('features.item1Title')"
                    :description="t('features.item1Desc')"
                    :icon="featureIcons[0]"
                    :index="1"
                />
                <FeatureCard
                    :title="t('features.item2Title')"
                    :description="t('features.item2Desc')"
                    :icon="featureIcons[1]"
                    :index="2"
                />
                <FeatureCard
                    :title="t('features.item3Title')"
                    :description="t('features.item3Desc')"
                    :icon="featureIcons[2]"
                    :index="3"
                />
                <FeatureCard
                    :title="t('features.item4Title')"
                    :description="t('features.item4Desc')"
                    :icon="featureIcons[3]"
                    :index="4"
                />
            </div>

        </div>
    </section>
</template>
