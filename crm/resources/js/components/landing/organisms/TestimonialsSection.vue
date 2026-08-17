<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import BadgeTag from '../atoms/BadgeTag.vue';
import TestimonialCard from '../molecules/TestimonialCard.vue';

const { t } = useI18n();

const row1 = computed(() => [
    { quote: t('testimonials.quote1'), author: t('testimonials.author1'), role: t('testimonials.role1') },
    { quote: t('testimonials.quote2'), author: t('testimonials.author2'), role: t('testimonials.role2') },
    { quote: t('testimonials.quote3'), author: t('testimonials.author3'), role: t('testimonials.role3') },
]);

const row2 = computed(() => [
    { quote: t('testimonials.quote3'), author: t('testimonials.author3'), role: t('testimonials.role3') },
    { quote: t('testimonials.quote1'), author: t('testimonials.author1'), role: t('testimonials.role1') },
    { quote: t('testimonials.quote2'), author: t('testimonials.author2'), role: t('testimonials.role2') },
]);
</script>

<template>
    <section id="testimonials" class="testimonials relative overflow-hidden py-16 lg:py-24 bg-slate-50/50 dark:bg-slate-900/30">
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
                
                <!-- Row 1 (Running Left - Only pauses when hovering this specific row) -->
                <div class="marquee-row mb-6 flex w-max cursor-pointer">
                    <div class="animate-marquee-left flex gap-6 pr-6">
                        <div
                            v-for="(item, idx) in [...row1, ...row1, ...row1, ...row1]"
                            :key="'r1-' + idx"
                            class="w-[300px] sm:w-[360px] shrink-0"
                        >
                            <TestimonialCard
                                :quote="item.quote"
                                :author="item.author"
                                :role="item.role"
                            />
                        </div>
                    </div>
                </div>

                <!-- Row 2 (Running Right - Only pauses when hovering this specific row) -->
                <div class="marquee-row flex w-max cursor-pointer">
                    <div class="animate-marquee-right flex gap-6 pr-6">
                        <div
                            v-for="(item, idx) in [...row2, ...row2, ...row2, ...row2]"
                            :key="'r2-' + idx"
                            class="w-[300px] sm:w-[360px] shrink-0"
                        >
                            <TestimonialCard
                                :quote="item.quote"
                                :author="item.author"
                                :role="item.role"
                            />
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </section>
</template>
