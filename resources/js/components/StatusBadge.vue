<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { StatusTone } from '@/types';
import type { LucideIcon } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    tone?: StatusTone;
    label?: string;
    icon?: LucideIcon;
    dot?: boolean;
    size?: 'sm' | 'md';
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    tone: 'neutral',
    dot: false,
    size: 'sm',
});

const toneClasses: Record<StatusTone, string> = {
    neutral: 'border-border bg-muted text-muted-foreground',
    brand: 'border-brand-200 bg-brand-50 text-brand-700 dark:border-brand-900 dark:bg-brand-950/60 dark:text-brand-300',
    success: 'border-success/20 bg-success/10 text-success',
    warning: 'border-warning/25 bg-warning/10 text-amber-700 dark:text-amber-300',
    danger: 'border-destructive/20 bg-destructive/10 text-destructive',
    info: 'border-info/20 bg-info/10 text-info',
};

const badgeClasses = computed(() =>
    cn(
        'inline-flex w-fit items-center gap-1.5 whitespace-nowrap rounded-full border font-medium',
        props.size === 'sm' ? 'px-2.5 py-0.5 text-[11px]' : 'px-3 py-1 text-xs',
        toneClasses[props.tone],
        props.class,
    ),
);
</script>

<template>
    <span :class="badgeClasses">
        <span v-if="dot" class="size-1.5 rounded-full bg-current" aria-hidden="true" />
        <component :is="icon" v-else-if="icon" class="size-3" aria-hidden="true" />
        <slot>{{ label }}</slot>
    </span>
</template>
