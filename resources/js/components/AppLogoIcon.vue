<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" fill="none" :class="className" v-bind="$attrs" aria-hidden="true">
        <!-- Le « M » évoque à la fois MaliConnect et un point de rendez-vous. -->
        <path
            d="M7 24V12.5C7 10.6 9.2 9.6 10.6 10.9L16 16l5.4-5.1C22.8 9.6 25 10.6 25 12.5V24"
            stroke="currentColor"
            stroke-width="2.75"
            stroke-linecap="round"
            stroke-linejoin="round"
        />
        <circle cx="25.25" cy="7.25" r="2.4" fill="#fbbf24" />
    </svg>
</template>
