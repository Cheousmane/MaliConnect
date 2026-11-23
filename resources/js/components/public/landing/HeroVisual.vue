<script setup lang="ts">
import StatusBadge from '@/components/StatusBadge.vue';
import { getInitials } from '@/composables/useInitials';
import { categoryIcon, monogramTone } from '@/lib/catalog';
import type { LandingCategory, LandingProfessional, LandingStats } from '@/types/landing';
import { Link } from '@inertiajs/vue3';
import LocationMap from '@/components/LocationMap.vue';
import { ArrowRight, BadgeCheck, MapPin, Sparkles, Star } from 'lucide-vue-next';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        professional?: LandingProfessional | null;
        categories: LandingCategory[];
        stats: LandingStats;
    }>(),
    { professional: null, categories: () => [] },
);

const initials = computed(() => getInitials(props.professional?.business_name));

const location = computed(() => {
    const professional = props.professional;

    if (!professional) {
        return '';
    }

    return [professional.commune, professional.quartier].filter(Boolean).join(' · ');
});

const hasRating = computed(() => (props.professional?.rating_count ?? 0) > 0);
</script>

<template>
    <div class="relative mx-auto w-full max-w-[440px]">
        <!-- Décor : halo, arche sahélienne et trame de points -->
        <div class="pointer-events-none absolute -inset-x-8 -top-8 bottom-4" aria-hidden="true">
            <div class="hero-mesh absolute inset-0 rounded-[3rem] opacity-90 blur-2xl" />
            <div
                class="arch-frame absolute inset-x-12 top-3 bottom-14 border border-brand-200/70 bg-brand-50/70 dark:border-brand-900/60 dark:bg-brand-950/40"
            />
            <div class="dot-grid absolute inset-0 opacity-40" />
        </div>

        <!-- Carte principale : un profil réel du catalogue -->
        <article
            v-if="professional"
            class="relative z-10 rounded-3xl border border-border/70 bg-card/95 p-5 shadow-elevated backdrop-blur-xl sm:p-6"
        >
            <div class="flex items-center justify-between gap-3">
                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-muted-foreground">Dans l’annuaire</p>
                <StatusBadge tone="success" label="Vérifié" />
            </div>

            <div class="mt-4 flex items-start gap-3">
                <div
                    v-if="professional.logo_url"
                    class="size-12 shrink-0 overflow-hidden rounded-xl border border-border/70 bg-background"
                >
                    <img
                        :src="professional.logo_url"
                        :alt="`Logo de ${professional.business_name}`"
                        width="48"
                        height="48"
                        loading="lazy"
                        decoding="async"
                        class="size-full object-cover"
                    />
                </div>
                <div
                    v-else
                    class="flex size-12 shrink-0 items-center justify-center rounded-xl font-display text-sm font-semibold ring-1 ring-inset"
                    :class="monogramTone(professional.id)"
                    aria-hidden="true"
                >
                    {{ initials }}
                </div>

                <div class="min-w-0">
                    <h3 class="truncate font-display text-base font-semibold text-foreground">{{ professional.business_name }}</h3>
                    <p class="mt-0.5 truncate text-xs text-muted-foreground">
                        {{ professional.category?.name ?? 'Professionnel' }}
                        <span v-if="professional.subcategory"> · {{ professional.subcategory.name }}</span>
                    </p>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-muted-foreground">
                <span class="inline-flex items-center gap-1.5">
                    <MapPin class="size-3.5" aria-hidden="true" />
                    {{ location || professional.city }}
                </span>

                <span v-if="hasRating" class="inline-flex items-center gap-1.5">
                    <Star class="size-3.5 fill-gold-400 text-gold-400" aria-hidden="true" />
                    <span class="font-medium text-foreground">{{ professional.rating_average.toFixed(1) }}</span>
                    <span class="text-muted-foreground/80">({{ professional.rating_count }})</span>
                </span>
                <span v-else class="inline-flex items-center gap-1.5">
                    <Sparkles class="size-3.5 text-gold-500" aria-hidden="true" />
                    Nouveau profil
                </span>
            </div>

            <div v-if="professional.services.length" class="mt-5 space-y-2 border-t border-border/60 pt-4">
                <div
                    v-for="service in professional.services.slice(0, 3)"
                    :key="service.name"
                    class="flex items-center justify-between gap-3 text-xs"
                >
                    <span class="min-w-0 truncate text-foreground/90">{{ service.name }}</span>
                    <span class="shrink-0 font-medium text-foreground">{{ service.formatted_price }}</span>
                </div>
            </div>

            <LocationMap
                v-if="professional.latitude != null && professional.longitude != null"
                :latitude="professional.latitude"
                :longitude="professional.longitude"
                height="160px"
                class="mt-4"
            />

            <Link
                class="mt-5 inline-flex items-center gap-1.5 text-sm font-medium text-brand-700 underline-offset-4 hover:underline dark:text-brand-300"
            >
                Voir le profil
                <ArrowRight class="size-4" aria-hidden="true" />
            </Link>
        </article>

        <!-- Repli : aperçu du catalogue quand aucun profil n'est publié -->
        <article v-else class="relative z-10 rounded-3xl border border-border/70 bg-card/95 p-5 shadow-elevated backdrop-blur-xl sm:p-6">
            <div class="flex items-center justify-between gap-3">
                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-muted-foreground">Le catalogue</p>
                <BadgeCheck class="size-4 text-brand-600 dark:text-brand-400" aria-hidden="true" />
            </div>

            <p class="mt-4 font-display text-base font-semibold text-foreground">Des savoir-faire classés par métier</p>
            <p class="mt-1 text-xs leading-relaxed text-muted-foreground">
                {{ stats.categories }} catégories et {{ stats.subcategories }} spécialités pour trouver le bon professionnel.
            </p>

            <ul class="mt-4 space-y-2">
                <li
                    v-for="category in categories.slice(0, 4)"
                    :key="category.id"
                    class="flex items-center gap-2.5 rounded-xl border border-border/60 bg-muted/30 px-3 py-2.5 text-xs text-foreground"
                >
                    <component :is="categoryIcon(category.icon)" class="size-4 text-brand-600 dark:text-brand-400" aria-hidden="true" />
                    <span class="truncate">{{ category.name }}</span>
                </li>
            </ul>
        </article>
    </div>
</template>
