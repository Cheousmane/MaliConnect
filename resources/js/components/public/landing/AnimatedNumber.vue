<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        value: number;
        duration?: number;
    }>(),
    { duration: 1600 },
);

const display = ref(0);
const el = ref<HTMLElement | null>(null);
let raf = 0;
let started = false;

const animate = () => {
    if (started) {
        return;
    }

    started = true;

    const start = performance.now();
    const from = 0;
    const to = props.value;

    const tick = (now: number) => {
        const progress = Math.min((now - start) / props.duration, 1);
        // ease-out cubic
        const eased = 1 - Math.pow(1 - progress, 3);
        display.value = Math.round(from + (to - from) * eased);

        if (progress < 1) {
            raf = requestAnimationFrame(tick);
        } else {
            display.value = to;
        }
    };

    raf = requestAnimationFrame(tick);
};

onMounted(() => {
    if (!el.value || typeof IntersectionObserver === 'undefined') {
        display.value = props.value;

        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    animate();
                    observer.disconnect();
                }
            });
        },
        { threshold: 0.1 },
    );

    observer.observe(el.value);

    // Filet de sécurité : si l'observateur ne se déclenche pas, on affiche la valeur finale.
    const fallback = window.setTimeout(() => {
        if (!started) {
            display.value = props.value;
            started = true;
        }
    }, 100);

    onUnmounted(() => {
        observer.disconnect();
        window.clearTimeout(fallback);
        if (raf) {
            cancelAnimationFrame(raf);
        }
    });
});

watch(
    () => props.value,
    (next) => {
        if (started) {
            display.value = next;
        }
    },
);
</script>

<template>
    <span ref="el">{{ display }}</span>
</template>
