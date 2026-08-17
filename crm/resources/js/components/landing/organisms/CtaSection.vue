<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from '@/composables/useI18n';
import BadgeTag from '../atoms/BadgeTag.vue';
import AdaptiveCtaCard from '../molecules/AdaptiveCtaCard.vue';

export interface CtaLinkItem {
    title: string;
    description: string;
    href: string;
}

const props = withDefaults(
    defineProps<{
        items?: CtaLinkItem[];
    }>(),
    {
        items: undefined,
    }
);

const { t } = useI18n();

// Default 6 links if not passed
const defaultItems = computed<CtaLinkItem[]>(() => [
    {
        title: t('cta.link1Title'),
        description: t('cta.link1Desc'),
        href: '/pension-calculations',
    },
    {
        title: t('cta.link2Title'),
        description: t('cta.link2Desc'),
        href: '/documents',
    },
    {
        title: t('cta.link5Title'),
        description: t('cta.link5Desc'),
        href: '/settings/profile',
    },
]);

const activeItems = computed(() => props.items || defaultItems.value);

// Dynamic grid columns adaptation (supports 2, 3, 6, etc.)
const gridColsClass = computed(() => {
    const count = activeItems.value.length;
    if (count === 2) return 'grid-cols-1 md:grid-cols-2 max-w-4xl mx-auto';
    if (count === 3) return 'grid-cols-1 md:grid-cols-3 max-w-6xl mx-auto';
    return 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3 max-w-7xl mx-auto';
});
</script>

<template>
    <section id="cta" class="py-16 lg:py-24 bg-slate-50/50 dark:bg-slate-900/30">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            
            <!-- Section Header -->
            <div class="mx-auto max-w-3xl text-center mb-16">
                <BadgeTag class="mb-4">
                    {{ t('cta.badge') }}
                </BadgeTag>
                <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl dark:text-white">
                    {{ t('cta.title') }}
                </h2>
                <p class="mt-4 text-base text-slate-600 dark:text-slate-400">
                    {{ t('cta.subtitle') }}
                </p>
            </div>

            <!-- Adaptive Grid -->
            <div :class="['grid gap-6', gridColsClass]">
                <AdaptiveCtaCard
                    v-for="(item, idx) in activeItems"
                    :key="idx"
                    :title="item.title"
                    :description="item.description"
                    :href="item.href"
                />
            </div>

        </div>
    </section>
</template>
