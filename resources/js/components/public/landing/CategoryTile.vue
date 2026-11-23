<script setup lang="ts">
import { categoryIcon, withAlpha } from '@/lib/catalog';
import type { LandingCategory } from '@/types/landing';
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        category: LandingCategory;
        featured?: boolean;
    }>(),
    { featured: false },
);

const accent = computed(() => props.category.color ?? '#0a8a54');

const iconStyle = computed(() => ({
    backgroundColor: withAlpha(accent.value, 0.12),
    color: accent.value,
    boxShadow: `inset 0 0 0 1px ${withAlpha(accent.value, 0.22) ?? 'transparent'}`,
}));
</script>

<template>
    <Link
        :href="route('categories.show', category.slug)"
        class="group lift relative flex h-full flex-col overflow-hidden rounded-2xl border border-border/70 bg-card p-5 shadow-soft hover:border-brand-200 hover:shadow-card dark:hover:border-brand-800"
        :class="featured ? 'sm:p-6' : ''"
    >
        <div
            class="pointer-events-none absolute -right-10 -top-10 size-28 rounded-full opacity-0 blur-2xl transition-opacity duration-500 group-hover:opacity-60"
            :style="{ backgroundColor: withAlpha(accent, 0.35) }"
            aria-hidden="true"
        />

        <div class="flex items-start justify-between gap-3">
            <span class="lift-icon flex size-11 shrink-0 items-center justify-center rounded-xl" :style="iconStyle">
                <component :is="categoryIcon(category.icon)" class="size-5" aria-hidden="true" />
            </span>

            <span class="text-[11px] font-medium text-muted-foreground">
                {{ category.subcategories_count }} métier{{ category.subcategories_count > 1 ? 's' : '' }}
            </span>
        </div>

        <h3 class="mt-4 font-display font-semibold text-foreground" :class="featured ? 'text-lg sm:text-xl' : 'text-base'">
            {{ category.name }}
        </h3>

        <p v-if="category.description" class="mt-1.5 line-clamp-2 text-sm leading-relaxed text-muted-foreground">
            {{ category.description }}
        </p>

        <span class="mt-auto inline-flex items-center gap-1 pt-5 text-xs font-semibold text-brand-700 dark:text-brand-300">
            Explorer
            <ArrowRight class="lift-arrow size-3.5" aria-hidden="true" />
        </span>
    </Link>
</template>
