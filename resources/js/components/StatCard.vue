<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { StatTrend, StatusTone } from '@/types';
import { Link } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';
import { ArrowDownRight, ArrowUpRight, Minus } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    label: string;
    value: string | number;
    icon?: LucideIcon;
    trend?: StatTrend;
    description?: string;
    sparkline?: number[];
    tone?: StatusTone;
    href?: string;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    tone: 'brand',
});

const toneClasses: Record<StatusTone, string> = {
    neutral: 'bg-muted text-muted-foreground',
    brand: 'bg-brand-50 text-brand-600 dark:bg-brand-950/60 dark:text-brand-300',
    success: 'bg-success/10 text-success',
    warning: 'bg-warning/10 text-amber-600 dark:text-amber-300',
    danger: 'bg-destructive/10 text-destructive',
    info: 'bg-info/10 text-info',
};

const trendClasses: Record<StatTrend['direction'], string> = {
    up: 'bg-success/10 text-success',
    down: 'bg-destructive/10 text-destructive',
    neutral: 'bg-muted text-muted-foreground',
};

const trendIcons = {
    up: ArrowUpRight,
    down: ArrowDownRight,
    neutral: Minus,
};

// Construit une mini-courbe normalisée à partir des valeurs fournies.
const sparklinePaths = computed(() => {
    const values = props.sparkline ?? [];

    if (values.length < 2) {
        return null;
    }

    const min = Math.min(...values);
    const max = Math.max(...values);
    const range = max - min || 1;
    const step = 100 / (values.length - 1);

    const coordinates = values.map((value, index) => [index * step, 30 - ((value - min) / range) * 26] as const);
    const line = coordinates.map(([x, y], index) => `${index === 0 ? 'M' : 'L'}${x.toFixed(1)} ${y.toFixed(1)}`).join(' ');

    return {
        line,
        area: `${line} L100 32 L0 32 Z`,
    };
});
</script>

<template>
    <component
        :is="href ? Link : 'div'"
        :href="href"
        :class="
            cn(
                'group relative block overflow-hidden rounded-xl border border-border/80 bg-card p-5 shadow-soft transition-all duration-300 hover:-translate-y-0.5 hover:shadow-card',
                href ? 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring' : '',
                props.class,
            )
        "
    >
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="truncate text-sm font-medium text-muted-foreground">{{ label }}</p>
                <p class="mt-2 font-display text-2xl font-semibold tracking-tight text-foreground sm:text-[28px]">{{ value }}</p>
            </div>

            <div v-if="icon" :class="cn('flex size-10 shrink-0 items-center justify-center rounded-xl', toneClasses[props.tone])">
                <component :is="icon" class="size-5" aria-hidden="true" />
            </div>
        </div>

        <div class="mt-4 flex items-end justify-between gap-3">
            <div class="min-w-0">
                <span
                    v-if="trend"
                    :class="cn('inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold', trendClasses[trend.direction])"
                >
                    <component :is="trendIcons[trend.direction]" class="size-3" aria-hidden="true" />
                    {{ trend.value }}
                </span>
                <p v-if="description" class="mt-1.5 truncate text-xs text-muted-foreground">{{ description }}</p>
            </div>

            <svg
                v-if="sparklinePaths"
                viewBox="0 0 100 32"
                fill="none"
                class="h-8 w-20 shrink-0 text-brand-500 dark:text-brand-400"
                preserveAspectRatio="none"
                aria-hidden="true"
            >
                <path :d="sparklinePaths.area" fill="currentColor" class="opacity-[0.08]" />
                <path :d="sparklinePaths.line" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </div>
    </component>
</template>
