<script setup lang="ts">
withDefaults(
    defineProps<{
        eyebrow?: string;
        title?: string;
        description?: string;
        align?: 'left' | 'center';
    }>(),
    { align: 'left' },
);
</script>

<template>
    <div :class="['max-w-2xl', align === 'center' ? 'mx-auto text-center' : '']">
        <p v-if="eyebrow" class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-700 dark:text-brand-300">
            {{ eyebrow }}
        </p>

        <h2 class="mt-3 text-balance font-display text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">
            <slot>{{ title }}</slot>
        </h2>

        <p v-if="description" class="mt-3 text-pretty text-sm leading-relaxed text-muted-foreground sm:text-base">
            {{ description }}
        </p>
    </div>
</template>
