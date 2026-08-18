<template>
    <div class="wrap pointer-events-none">
        <div v-for="n in total" :key="n" class="c"></div>
    </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue';
import { mountParticleStyles, unmountParticleStyles } from '@/lib/particleSphereCache';

const total = 300;

onMounted(() => {
    mountParticleStyles();
});

onUnmounted(() => {
    unmountParticleStyles();
});
</script>

<style scoped>
.wrap {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    transform-style: preserve-3d;
    perspective: 1000px;
    animation: rotate 14s infinite linear;
}

@keyframes rotate {
    100% {
        transform: rotateY(360deg) rotateX(360deg);
    }
}

.c {
    position: absolute;
    width: 4px;
    height: 4px;
    border-radius: 50%;
    opacity: 0;
}
</style>
