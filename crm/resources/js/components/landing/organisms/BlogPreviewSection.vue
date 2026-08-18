<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';
import BadgeTag from '../atoms/BadgeTag.vue';
import BlogCard from '../molecules/BlogCard.vue';
import { ExternalLink } from '@lucide/vue';

export interface PfuNewsItem {
    id?: number;
    title: string;
    url: string;
    published_at: string;
    preview_text?: string | null;
}

const props = withDefaults(
    defineProps<{
        news?: PfuNewsItem[];
    }>(),
    {
        news: () => [],
    }
);

const { t } = useI18n();

const PFU_NEWS_PAGE_URL = 'https://www.pfu.gov.ua/kr/category/prestsentr/novini/';
</script>

<template>
    <section id="blog" class="py-16 lg:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            
            <!-- Section Header with "Читати всі новини ПФУ" button -->
            <div class="flex flex-col items-start justify-between gap-6 md:flex-row md:items-end">
                <div class="max-w-2xl text-left">
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

                <div class="shrink-0">
                    <a
                        :href="PFU_NEWS_PAGE_URL"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 rounded-xl bg-main px-5 py-3 text-sm font-semibold text-slate-950 shadow-md transition-all duration-300 hover:bg-main-dark hover:shadow-main/20 active:scale-95"
                    >
                        <span>Читати всі новини ПФУ</span>
                        <ExternalLink class="h-4 w-4" />
                    </a>
                </div>
            </div>

            <!-- Blog Grid (Dynamic PFU News or Fallbacks) -->
            <div class="mt-12 grid grid-cols-1 gap-8 md:grid-cols-3">
                <template v-if="news && news.length > 0">
                    <BlogCard
                        v-for="(item, index) in news.slice(0, 3)"
                        :key="item.id || index"
                        tag="ПФУ Новини"
                        :title="item.title"
                        :date="item.published_at"
                        :description="item.preview_text || item.title"
                        :url="item.url"
                    />
                </template>
                <template v-else>
                    <BlogCard
                        tag="ПФУ Новини"
                        :title="t('blog.post1Title')"
                        :date="t('blog.post1Date')"
                        :description="t('blog.post1Desc')"
                        :url="PFU_NEWS_PAGE_URL"
                    />
                    <BlogCard
                        tag="ПФУ Новини"
                        :title="t('blog.post2Title')"
                        :date="t('blog.post2Date')"
                        :description="t('blog.post2Desc')"
                        :url="PFU_NEWS_PAGE_URL"
                    />
                    <BlogCard
                        tag="ПФУ Новини"
                        :title="t('blog.post3Title')"
                        :date="t('blog.post3Date')"
                        :description="t('blog.post3Desc')"
                        :url="PFU_NEWS_PAGE_URL"
                    />
                </template>
            </div>

        </div>
    </section>
</template>
