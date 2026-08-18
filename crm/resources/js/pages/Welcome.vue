<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from '@/composables/useI18n';
import LandingHeader from '@/components/landing/organisms/LandingHeader.vue';
import HeroSection from '@/components/landing/organisms/HeroSection.vue';
import FeaturesSection from '@/components/landing/organisms/FeaturesSection.vue';
import TestimonialsSection from '@/components/landing/organisms/TestimonialsSection.vue';
import BlogPreviewSection, { PfuNewsItem } from '@/components/landing/organisms/BlogPreviewSection.vue';
import CtaSection from '@/components/landing/organisms/CtaSection.vue';
import LandingFooter from '@/components/landing/organisms/LandingFooter.vue';

defineProps<{
    pfuNews?: PfuNewsItem[];
}>();

const { t } = useI18n();

const heroRef = ref<InstanceType<typeof HeroSection> | null>(null);
const showHeaderAuthButtons = ref(false);

let observer: IntersectionObserver | null = null;

onMounted(() => {
    if (typeof window === 'undefined') return;

    // Observe Hero Action Buttons container
    const target = heroRef.value?.actionsRef;
    if (target) {
        observer = new IntersectionObserver(
            ([entry]) => {
                // When Hero actions scroll OUT of view, show auth/dashboard buttons in the header
                showHeaderAuthButtons.value = !entry.isIntersecting;
            },
            {
                threshold: 0.1,
            }
        );
        observer.observe(target);
    }
});

onUnmounted(() => {
    if (observer) {
        observer.disconnect();
    }
});
</script>

<template>
    <Head :title="t('hero.badge')">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div class="min-h-screen bg-slate-50 font-sans text-slate-900 selection:bg-main selection:text-slate-950 dark:bg-black dark:text-slate-100">
        
        <!-- Sticky Header with dynamic floating auth buttons -->
        <LandingHeader :show-auth-buttons="showHeaderAuthButtons" />

        <main>
            <!-- Hero Section -->
            <HeroSection ref="heroRef" />

            <!-- Features Section -->
            <FeaturesSection />

            <!-- Testimonials Section -->
            <TestimonialsSection />

            <!-- Blog Preview Section -->
            <BlogPreviewSection :news="pfuNews" />

            <!-- Adaptive CTA Section -->
            <CtaSection />
        </main>

        <!-- Footer -->
        <LandingFooter />

    </div>
</template>
