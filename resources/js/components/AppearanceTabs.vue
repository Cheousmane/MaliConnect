<script setup lang="ts">
import { useAppearance } from '@/composables/useAppearance';
import { cn } from '@/lib/utils';
import { Check, Monitor, Moon, Sun } from 'lucide-vue-next';

interface Props {
    class?: string;
}

const { class: containerClass = '' } = defineProps<Props>();

const { appearance, updateAppearance } = useAppearance();

const tabs = [
    {
        value: 'light',
        icon: Sun,
        label: 'Clair',
        description: 'Lumineux et net',
    },
    {
        value: 'dark',
        icon: Moon,
        label: 'Sombre',
        description: 'Doux pour les yeux',
    },
    {
        value: 'system',
        icon: Monitor,
        label: 'Système',
        description: 'Selon votre appareil',
    },
] as const;
</script>

<template>
    <div :class="cn('grid gap-3 sm:grid-cols-3', containerClass)">
        <button
            v-for="{ value, icon, label, description } in tabs"
            :key="value"
            type="button"
            :aria-pressed="appearance === value"
            :class="
                cn(
                    'group relative flex flex-col items-start gap-3 rounded-xl border p-4 text-left transition-all duration-200',
                    appearance === value
                        ? 'border-brand-300 bg-brand-50/70 ring-1 ring-brand-200 dark:border-brand-800 dark:bg-brand-950/40 dark:ring-brand-900'
                        : 'border-border/80 bg-background hover:border-brand-200 hover:bg-muted/40 dark:hover:border-brand-800',
                )
            "
            @click="updateAppearance(value)"
        >
            <div class="flex w-full items-center justify-between gap-2">
                <span
                    :class="
                        cn(
                            'flex size-9 items-center justify-center rounded-lg',
                            appearance === value ? 'bg-brand-600 text-white' : 'bg-muted text-muted-foreground group-hover:text-foreground',
                        )
                    "
                >
                    <component :is="icon" class="size-4" aria-hidden="true" />
                </span>

                <span
                    v-if="appearance === value"
                    class="flex size-5 items-center justify-center rounded-full bg-brand-600 text-white"
                    aria-hidden="true"
                >
                    <Check class="size-3" />
                </span>
            </div>

            <span class="min-w-0">
                <span class="block text-sm font-semibold text-foreground">{{ label }}</span>
                <span class="mt-0.5 block text-xs text-muted-foreground">{{ description }}</span>
            </span>
        </button>
    </div>
</template>
