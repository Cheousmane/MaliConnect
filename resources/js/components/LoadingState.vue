<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

interface Props {
    label?: string;
    class?: HTMLAttributes['class'];
}

withDefaults(defineProps<Props>(), {
    label: 'Chargement en cours…',
});
</script>

<template>
    <div :class="cn('flex flex-col items-center justify-center gap-3 py-12', $props.class)" role="status" aria-live="polite">
        <div class="relative size-10">
            <div class="absolute inset-0 rounded-full border-2 border-brand-100 dark:border-brand-900" aria-hidden="true" />
            <div
                class="absolute inset-0 animate-spin rounded-full border-2 border-transparent border-t-brand-600 dark:border-t-brand-400"
                aria-hidden="true"
            />
        </div>
        <p class="text-sm text-muted-foreground">{{ label }}</p>
    </div>
</template>
