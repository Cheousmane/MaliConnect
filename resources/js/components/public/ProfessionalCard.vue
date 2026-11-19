<script setup lang="ts">
import StatusBadge from '@/components/StatusBadge.vue';
import { Link } from '@inertiajs/vue3';
import { Clock, MapPin, Star, Store } from 'lucide-vue-next';

type Category = { id: number; name: string; slug: string } | null;
type Subcategory = { id: number; name: string; slug: string } | null;

type Professional = {
    id: number;
    business_name: string;
    slug: string;
    description?: string | null;
    city: string;
    commune: string | null;
    quartier: string | null;
    is_available?: boolean;
    rating_average: number;
    rating_count: number;
    category: Category;
    subcategory: Subcategory;
    services_count?: number;
};

defineProps<{
    professional: Professional;
}>();
</script>

<template>
    <article
        class="group flex h-full flex-col rounded-2xl border border-border/70 bg-card p-5 shadow-soft transition-colors hover:border-brand-200 dark:hover:border-brand-800"
    >
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h3 class="truncate font-display text-base font-semibold text-foreground">
                    <Link :href="route('professionals.show', professional.slug)" class="hover:text-brand-700 dark:hover:text-brand-300">
                        {{ professional.business_name }}
                    </Link>
                </h3>
                <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs text-muted-foreground">
                    <span v-if="professional.category" class="inline-flex items-center gap-1">
                        <Store class="size-3.5" aria-hidden="true" />
                        {{ professional.category.name }}
                    </span>
                    <span v-if="professional.subcategory">· {{ professional.subcategory.name }}</span>
                </p>
            </div>

            <StatusBadge v-if="professional.is_available === false" tone="neutral" label="Indisponible" class="shrink-0" />
        </div>

        <p v-if="professional.description" class="mt-3 line-clamp-2 text-sm leading-relaxed text-muted-foreground">
            {{ professional.description }}
        </p>

        <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-muted-foreground">
            <span class="inline-flex items-center gap-1.5">
                <MapPin class="size-3.5" aria-hidden="true" />
                {{ professional.city }}<span v-if="professional.commune"> · {{ professional.commune }}</span>
            </span>

            <span v-if="professional.rating_count > 0" class="inline-flex items-center gap-1.5">
                <Star class="size-3.5 fill-gold-400 text-gold-400" aria-hidden="true" />
                {{ professional.rating_average.toFixed(1) }}
                <span class="text-muted-foreground/80">({{ professional.rating_count }})</span>
            </span>
            <span v-else class="inline-flex items-center gap-1.5">
                <Clock class="size-3.5" aria-hidden="true" />
                Nouvel arrivant
            </span>
        </div>

        <div class="mt-5 flex items-center justify-between gap-3 pt-1">
            <span v-if="professional.services_count" class="text-xs text-muted-foreground">
                {{ professional.services_count }} service{{ professional.services_count > 1 ? 's' : '' }} proposé{{
                    professional.services_count > 1 ? 's' : ''
                }}
            </span>
            <span v-else />

            <Link
                :href="route('professionals.show', professional.slug)"
                class="inline-flex items-center gap-1 text-sm font-medium text-brand-700 underline-offset-4 hover:underline dark:text-brand-300"
            >
                Voir le profil
            </Link>
        </div>
    </article>
</template>
