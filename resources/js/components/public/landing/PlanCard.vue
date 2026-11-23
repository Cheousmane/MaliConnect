<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { formatMoney } from '@/lib/format';
import type { LandingPlan, BillingCycle } from '@/types/landing';
import type { SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Check, Sparkles } from 'lucide-vue-next';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        plan: LandingPlan;
        cycle?: BillingCycle;
    }>(),
    { cycle: 'monthly' },
);

const page = usePage<SharedData>();
const authUser = computed(() => page.props.auth?.user ?? null);

const featured = computed(() => props.plan.code === 'pro');

const price = computed(() =>
    formatMoney(props.cycle === 'monthly' ? props.plan.price_monthly : props.plan.price_yearly, props.plan.currency),
);
const period = computed(() => (props.cycle === 'monthly' ? '/ mois' : '/ an'));

const yearlySaving = computed(() => {
    if (props.plan.price_monthly <= 0 || props.plan.price_yearly <= 0) {
        return 0;
    }

    return props.plan.price_monthly * 12 - props.plan.price_yearly;
});

const limits = computed(() => {
    const profileLabel = `${props.plan.max_profiles} profil${props.plan.max_profiles > 1 ? 's' : ''}`;
    const servicesLabel = props.plan.max_services === 0 ? 'Services illimités' : `${props.plan.max_services} services`;
    const sheetsLabel =
        props.plan.max_pricing_sheets === 0
            ? 'Fiches tarifaires illimitées'
            : `${props.plan.max_pricing_sheets} fiche${props.plan.max_pricing_sheets > 1 ? 's' : ''} tarifaire${props.plan.max_pricing_sheets > 1 ? 's' : ''}`;

    return [profileLabel, servicesLabel, sheetsLabel];
});

const highlights = computed(() => {
    const items: string[] = [];

    if (props.plan.features.advanced_quotes) items.push('Devis avancés');
    if (props.plan.features.bookings) items.push('Réservations en ligne');
    if (props.plan.features.advanced_stats) items.push('Statistiques avancées');
    if (props.plan.features.gallery) items.push('Galerie photos');
    if (props.plan.features.profile_badge) items.push('Badge de confiance');
    if (props.plan.features.highlight_options) items.push('Mise en avant');
    if (props.plan.features.collaborators) items.push('Collaborateurs');
    if (props.plan.features.priority_support) items.push('Support prioritaire');

    return items.slice(0, 4);
});

const ctaHref = computed(() => {
    if (authUser.value) {
        return route('pro.subscription.plans');
    }

    return route('register', props.plan.is_free ? {} : { plan: props.plan.code });
});

const ctaLabel = computed(() => {
    if (authUser.value) {
        return 'Gérer mon abonnement';
    }

    return props.plan.is_free ? 'Commencer gratuitement' : 'Créer mon espace pro';
});
</script>

<template>
    <article
        class="lift relative flex h-full flex-col rounded-2xl border p-6 shadow-soft"
        :class="
            featured
                ? 'border-brand-300/70 bg-brand-50/80 dark:border-brand-800 dark:bg-brand-950/40 plan-featured'
                : 'border-border/70 bg-card'
        "
    >
        <div v-if="featured" class="absolute -top-3 left-6">
            <span
                class="inline-flex items-center gap-1.5 rounded-full bg-brand-600 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.1em] text-white shadow-soft"
            >
                <Sparkles class="size-3" aria-hidden="true" />
                Pour démarrer
            </span>
        </div>

        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h3 class="font-display text-lg font-semibold text-foreground">{{ plan.name }}</h3>
                <p v-if="plan.description" class="mt-1 line-clamp-2 text-xs leading-relaxed text-muted-foreground">
                    {{ plan.description }}
                </p>
            </div>

            <span
                v-if="plan.trial_days > 0"
                class="shrink-0 rounded-full border border-gold-200 bg-gold-50 px-2 py-0.5 text-[10px] font-medium text-gold-700 dark:border-gold-900 dark:bg-gold-950/50 dark:text-gold-300"
            >
                {{ plan.trial_days }} j offerts
            </span>
        </div>

        <div class="mt-5 flex items-baseline gap-1.5">
            <span class="font-display text-3xl font-semibold tracking-tight text-foreground">{{ price }}</span>
            <span class="ml-1 text-xs text-muted-foreground">{{ period }}</span>
        </div>

        <p v-if="cycle === 'yearly' && yearlySaving > 0" class="mt-1 text-[11px] font-medium text-brand-700 dark:text-brand-300">
            Économisez {{ formatMoney(yearlySaving, plan.currency) }} sur l’année
        </p>
        <p v-else class="mt-1 text-[11px] text-muted-foreground">
            {{ plan.is_free ? 'Sans engagement' : 'Sans engagement, résiliable à tout moment' }}
        </p>

        <ul class="mt-5 space-y-2.5 border-t border-border/60 pt-5">
            <li v-for="item in limits" :key="item" class="flex items-start gap-2 text-sm text-foreground/90">
                <Check class="mt-0.5 size-4 shrink-0 text-brand-600 dark:text-brand-400" aria-hidden="true" />
                <span>{{ item }}</span>
            </li>

            <li v-for="item in highlights" :key="item" class="flex items-start gap-2 text-sm text-muted-foreground">
                <Check class="mt-0.5 size-4 shrink-0 text-brand-600/70 dark:text-brand-400/70" aria-hidden="true" />
                <span>{{ item }}</span>
            </li>
        </ul>

        <div class="mt-auto pt-6">
            <Button as-child :variant="featured ? 'brand' : 'outline'" size="lg" class="h-11 w-full">
                <Link :href="ctaHref">
                    {{ ctaLabel }}
                </Link>
            </Button>
        </div>
    </article>
</template>
