<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import BadgeTag from '../atoms/BadgeTag.vue';
import TestimonialCard from '../molecules/TestimonialCard.vue';
import {
    Calendar,
    Calculator,
    TrendingUp,
    Zap,
    Award,
    FileText,
    ScanLine,
    Activity,
} from '@lucide/vue';

const { t } = useI18n();

const row1 = computed(() => [
    {
        title: t('testimonials.feature1Title'),
        description: t('testimonials.feature1Desc'),
        icon: Calendar,
        tag: 'Art. 40',
    },
    {
        title: t('testimonials.feature2Title'),
        description: t('testimonials.feature2Desc'),
        icon: Calculator,
        tag: 'PFU Formula',
    },
    {
        title: t('testimonials.feature3Title'),
        description: t('testimonials.feature3Desc'),
        icon: TrendingUp,
        tag: 'Forecast',
    },
    {
        title: t('testimonials.feature4Title'),
        description: t('testimonials.feature4Desc'),
        icon: Zap,
        tag: 'Kz Rule',
    },
]);

const row2 = computed(() => [
    {
        title: t('testimonials.feature5Title'),
        description: t('testimonials.feature5Desc'),
        icon: Award,
        tag: 'Surcharges',
    },
    {
        title: t('testimonials.feature6Title'),
        description: t('testimonials.feature6Desc'),
        icon: FileText,
        tag: 'PDF Export',
    },
    {
        title: t('testimonials.feature7Title'),
        description: t('testimonials.feature7Desc'),
        icon: ScanLine,
        tag: 'AI OCR',
    },
    {
        title: t('testimonials.feature8Title'),
        description: t('testimonials.feature8Desc'),
        icon: Activity,
        tag: 'Live Sync',
    },
]);
</script>

<template>
    <section id="testimonials" class="testimonials relative overflow-hidden py-16 lg:py-24 bg-slate-50/50 dark:bg-black">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            
            <!-- Section Header -->
            <div class="mx-auto max-w-3xl text-center mb-12">
                <BadgeTag class="mb-4">
                    {{ t('testimonials.badge') }}
                </BadgeTag>
                <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl dark:text-white">
                    {{ t('testimonials.title') }}
                </h2>
                <p class="mt-4 text-base text-slate-600 dark:text-slate-400">
                    {{ t('testimonials.subtitle') }}
                </p>
            </div>

            <!-- OpenClaw Infinite Running Rows Container with side fade gradient mask -->
            <div class="relative w-full overflow-hidden [mask-image:linear-gradient(to_right,transparent,black_5%,black_95%,transparent)]">
                
                <!-- Row 1 (Running Left) -->
                <div class="marquee-row mb-6 flex w-max cursor-pointer">
                    <div class="animate-marquee-left flex gap-6 pr-6">
                        <div
                            v-for="(item, idx) in [...row1, ...row1, ...row1, ...row1]"
                            :key="'r1-' + idx"
                            class="w-[300px] sm:w-[360px] shrink-0"
                        >
                            <TestimonialCard
                                :title="item.title"
                                :description="item.description"
                                :icon="item.icon"
                                :tag="item.tag"
                            />
                        </div>
                    </div>
                </div>

                <!-- Row 2 (Running Right) -->
                <div class="marquee-row flex w-max cursor-pointer">
                    <div class="animate-marquee-right flex gap-6 pr-6">
                        <div
                            v-for="(item, idx) in [...row2, ...row2, ...row2, ...row2]"
                            :key="'r2-' + idx"
                            class="w-[300px] sm:w-[360px] shrink-0"
                        >
                            <TestimonialCard
                                :title="item.title"
                                :description="item.description"
                                :icon="item.icon"
                                :tag="item.tag"
                            />
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </section>
</template>
