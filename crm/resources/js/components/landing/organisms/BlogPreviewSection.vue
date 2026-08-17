<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';
import BadgeTag from '../atoms/BadgeTag.vue';
import BlogCard from '../molecules/BlogCard.vue';

const { t } = useI18n();
</script>

<template>
    <section id="blog" class="py-16 lg:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            
            <!-- Section Header -->
            <div class="mx-auto max-w-3xl text-center">
                <BadgeTag class="mb-4">
                    {{ t('blog.badge') }}
                </BadgeTag>
                <h2 class="text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl dark:text-white">
                    {{ t('blog.title') }}
                </h2>
                <p class="mt-4 text-base text-slate-600 dark:text-slate-400">
                    {{ t('blog.subtitle') }}
                </p>
            </div>

            <!-- Blog Grid -->
            <div class="mt-16 grid grid-cols-1 gap-8 md:grid-cols-3">
                <BlogCard
                    :tag="t('blog.post1Tag')"
                    :title="t('blog.post1Title')"
                    :date="t('blog.post1Date')"
                    :description="t('blog.post1Desc')"
                />
                <BlogCard
                    :tag="t('blog.post2Tag')"
                    :title="t('blog.post2Title')"
                    :date="t('blog.post2Date')"
                    :description="t('blog.post2Desc')"
                />
                <BlogCard
                    :tag="t('blog.post3Tag')"
                    :title="t('blog.post3Title')"
                    :date="t('blog.post3Date')"
                    :description="t('blog.post3Desc')"
                />
            </div>

        </div>
    </section>
</template>
