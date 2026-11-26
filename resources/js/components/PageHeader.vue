<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    title: string;
    description?: string;
    eyebrow?: string;
    class?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <div v-bind="$attrs" :class="cn('flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between', $props.class)">
        <div class="min-w-0">
            <p
                v-if="eyebrow"
                class="mb-2 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-brand-600 dark:text-brand-400"
            >
                <span class="h-px w-5 bg-gold-400" aria-hidden="true" />
                {{ eyebrow }}
            </p>
            <h1 class="font-display text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">{{ title }}</h1>
            <p v-if="description" class="mt-1.5 max-w-2xl text-sm leading-relaxed text-muted-foreground">{{ description }}</p>
            <slot name="meta" />
        </div>

        <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
