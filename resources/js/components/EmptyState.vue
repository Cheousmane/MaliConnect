<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { LucideIcon } from 'lucide-vue-next';
import { Inbox } from 'lucide-vue-next';
import type { HTMLAttributes } from 'vue';

interface Props {
    title: string;
    description?: string;
    icon?: LucideIcon;
    class?: HTMLAttributes['class'];
    compact?: boolean;
}

withDefaults(defineProps<Props>(), {
    compact: false,
});
</script>

<template>
    <div
        :class="
            cn(
                'flex flex-col items-center justify-center rounded-xl border border-dashed border-border bg-muted/20 px-6 text-center',
                compact ? 'py-8' : 'py-12 sm:py-16',
                $props.class,
            )
        "
    >
        <div
            class="flex size-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 ring-1 ring-inset ring-brand-100 dark:bg-brand-950/60 dark:text-brand-300 dark:ring-brand-900"
        >
            <component :is="icon ?? Inbox" class="size-6" aria-hidden="true" />
        </div>

        <h3 class="mt-4 font-display text-base font-semibold text-foreground">{{ title }}</h3>
        <p v-if="description" class="mt-1.5 max-w-sm text-sm leading-relaxed text-muted-foreground">{{ description }}</p>

        <div v-if="$slots.action" class="mt-5">
            <slot name="action" />
        </div>
    </div>
</template>
