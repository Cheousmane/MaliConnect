<script setup lang="ts">
import { getInitials } from '@/composables/useInitials';
import { monogramTone } from '@/lib/catalog';
import type { LandingProfessional } from '@/types/landing';
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight, Briefcase, MapPin, Star } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    professional: LandingProfessional;
}>();

const initials = computed(() => getInitials(props.professional.business_name));
const location = computed(() => {
    const parts = [props.professional.city];

    if (props.professional.commune && props.professional.commune !== props.professional.city) {
        parts.push(props.professional.commune);
    }

    if (props.professional.quartier) {
        parts.push(props.professional.quartier);
    }

    return parts.filter(Boolean).join(' · ');
});

const hasRating = computed(() => props.professional.rating_count > 0);
</script>

<template>
    <article
        class="lift group flex h-full flex-col rounded-2xl border border-border/70 bg-card p-5 shadow-soft hover:border-brand-200 hover:shadow-card dark:hover:border-brand-800 sm:p-6"
    >
        <div class="flex items-start justify-between gap-3">
            <div class="flex min-w-0 items-center gap-3">
                <div v-if="professional.logo_url" class="size-12 shrink-0 overflow-hidden rounded-xl border border-border/70 bg-background">
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
                    <h3 class="truncate font-display text-base font-semibold text-foreground">
                        {{ professional.business_name }}
                    </h3>
                    <p class="mt-0.5 truncate text-xs text-muted-foreground">
                        {{ professional.category?.name ?? 'Professionnel' }}
                        <span v-if="professional.subcategory"> · {{ professional.subcategory.name }}</span>
                    </p>
                </div>
            </div>

            <span
                class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-brand-200/70 bg-brand-50 px-2.5 py-1 text-[11px] font-medium text-brand-700 dark:border-brand-900 dark:bg-brand-950/60 dark:text-brand-300"
            >
                <span class="size-1.5 rounded-full bg-current" aria-hidden="true" />
                Vérifié
            </span>
        </div>

        <p v-if="professional.description" class="mt-3 line-clamp-2 text-sm leading-relaxed text-muted-foreground">
            {{ professional.description }}
        </p>

        <dl class="mt-4 space-y-2 text-xs text-muted-foreground">
            <div class="flex items-center gap-2">
                <MapPin class="size-3.5 shrink-0" aria-hidden="true" />
                <dt class="sr-only">Localisation</dt>
                <dd class="truncate">{{ location }}</dd>
            </div>

            <div v-if="hasRating" class="flex items-center gap-2">
                <Star class="size-3.5 shrink-0 fill-gold-400 text-gold-400" aria-hidden="true" />
                <dt class="sr-only">Note moyenne</dt>
                <dd>
                    <span class="font-medium text-foreground">{{ professional.rating_average.toFixed(1) }}</span>
                    sur 5 · {{ professional.rating_count }} avis
                </dd>
            </div>

            <div v-else class="flex items-center gap-2">
                <Star class="size-3.5 shrink-0 text-muted-foreground/60" aria-hidden="true" />
                <dt class="sr-only">Note moyenne</dt>
                <dd>Nouveau profil, pas encore d’avis</dd>
            </div>

            <div v-if="professional.years_experience" class="flex items-center gap-2">
                <Briefcase class="size-3.5 shrink-0" aria-hidden="true" />
                <dt class="sr-only">Expérience</dt>
                <dd>{{ professional.years_experience }} an{{ professional.years_experience > 1 ? 's' : '' }} d’expérience</dd>
            </div>
        </dl>

        <div v-if="professional.services.length" class="mt-4 border-t border-border/60 pt-4">
            <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-muted-foreground">Prestations</p>
            <ul class="mt-2 space-y-2">
                <li
                    v-for="service in professional.services.slice(0, 2)"
                    :key="service.name"
                    class="flex items-center justify-between gap-3 text-sm"
                >
                    <span class="min-w-0 truncate text-foreground/90">{{ service.name }}</span>
                    <span class="shrink-0 text-xs font-medium text-foreground">{{ service.formatted_price }}</span>
                </li>
            </ul>
        </div>

        <div class="mt-auto flex items-center justify-between gap-3 pt-5">
            <span class="text-xs text-muted-foreground">
                {{ professional.services_count }} prestation{{ professional.services_count > 1 ? 's' : '' }}
            </span>

            <Link
                :href="route('professionals.show', professional.slug)"
                class="inline-flex items-center gap-1 text-sm font-semibold text-brand-700 underline-offset-4 hover:underline dark:text-brand-300"
            >
                Voir le profil
                <ArrowUpRight class="size-4" aria-hidden="true" />
            </Link>
        </div>
    </article>
</template>
