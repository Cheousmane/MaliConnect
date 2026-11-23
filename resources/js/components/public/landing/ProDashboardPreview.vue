<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import type { LandingPlan } from '@/types/landing';
import { BarChart3, CalendarCheck, FileText, Inbox, LayoutDashboard, Settings2, Star, Users } from 'lucide-vue-next';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        plan?: LandingPlan | null;
        professionals: number;
    }>(),
    { plan: null, professionals: 0 },
);

const servicesLabel = computed(() => {
    if (!props.plan) {
        return 'Vos prestations';
    }

    return props.plan.max_services === 0 ? 'Services illimités' : `${props.plan.max_services} services inclus`;
});

const sheetsLabel = computed(() => {
    if (!props.plan) {
        return 'Vos tarifs';
    }

    return props.plan.max_pricing_sheets === 0 ? 'Fiches illimitées' : `${props.plan.max_pricing_sheets} fiche${props.plan.max_pricing_sheets > 1 ? 's' : ''} tarifaire${props.plan.max_pricing_sheets > 1 ? 's' : ''}`;
});

const navItems = [
    { label: 'Tableau de bord', icon: LayoutDashboard, active: true },
    { label: 'Profil', icon: Users },
    { label: 'Services', icon: Settings2 },
    { label: 'Demandes', icon: Inbox },
    { label: 'Réservations', icon: CalendarCheck },
];
</script>

<template>
    <div class="overflow-hidden rounded-2xl border border-white/15 bg-white/[0.06] shadow-elevated backdrop-blur-xl">
        <!-- Barre de fenêtre -->
        <div class="flex items-center gap-2 border-b border-white/10 bg-white/[0.03] px-4 py-2.5">
            <span class="size-2 rounded-full bg-white/25" aria-hidden="true" />
            <span class="size-2 rounded-full bg-white/25" aria-hidden="true" />
            <span class="size-2 rounded-full bg-white/25" aria-hidden="true" />
            <span class="ml-auto rounded-md border border-white/10 bg-white/5 px-2 py-0.5 text-[10px] font-medium text-brand-100/80">
                Aperçu de l’interface
            </span>
        </div>

        <div class="grid gap-3 p-3 sm:grid-cols-[132px_minmax(0,1fr)] sm:p-4">
            <!-- Navigation latérale -->
            <nav class="hidden flex-col gap-1 sm:flex" aria-hidden="true">
                <div class="mb-2 flex items-center gap-2 px-2 py-1">
                    <span class="flex size-6 items-center justify-center rounded-md bg-white/10 text-gold-200">
                        <AppLogoIcon class="size-3.5" />
                    </span>
                    <span class="truncate font-display text-[11px] font-semibold text-white">Espace pro</span>
                </div>

                <span
                    v-for="item in navItems"
                    :key="item.label"
                    class="flex items-center gap-2 rounded-md px-2 py-1.5 text-[11px]"
                    :class="item.active ? 'bg-white/10 text-white' : 'text-brand-100/70'"
                >
                    <component :is="item.icon" class="size-3.5 shrink-0" />
                    <span class="truncate">{{ item.label }}</span>
                </span>
            </nav>

            <!-- Contenu -->
            <div class="min-w-0 space-y-3">
                <div class="flex items-center justify-between gap-3 rounded-xl border border-white/10 bg-white/[0.04] px-3 py-2.5">
                    <div class="min-w-0">
                        <p class="truncate text-xs font-semibold text-white">Votre activité, en un coup d’œil</p>
                        <p class="truncate text-[10px] text-brand-100/70">Profil, prestations et demandes réunies</p>
                    </div>
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-white/10 text-gold-200">
                        <BarChart3 class="size-4" />
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div class="rounded-lg border border-white/10 bg-white/[0.04] p-2.5">
                        <p class="text-[10px] text-brand-100/70">Notoriété</p>
                        <p class="mt-1 flex items-center gap-1.5 text-[11px] font-medium text-white">
                            <Star class="size-3.5 text-gold-300" />
                            {{ professionals > 0 ? 'Dans l’annuaire' : 'À construire' }}
                        </p>
                    </div>

                    <div class="rounded-lg border border-white/10 bg-white/[0.04] p-2.5">
                        <p class="text-[10px] text-brand-100/70">Prestations</p>
                        <p class="mt-1 truncate text-[11px] font-medium text-white">{{ servicesLabel }}</p>
                    </div>

                    <div class="rounded-lg border border-white/10 bg-white/[0.04] p-2.5">
                        <p class="text-[10px] text-brand-100/70">Tarifs</p>
                        <p class="mt-1 truncate text-[11px] font-medium text-white">{{ sheetsLabel }}</p>
                    </div>

                    <div class="rounded-lg border border-white/10 bg-white/[0.04] p-2.5">
                        <p class="text-[10px] text-brand-100/70">Demandes</p>
                        <p class="mt-1 flex items-center gap-1.5 text-[11px] font-medium text-white">
                            <FileText class="size-3.5 text-gold-300" />
                            Centralisées
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-between rounded-lg border border-white/10 bg-white/[0.04] px-3 py-2">
                    <span class="flex items-center gap-2 text-[10px] text-brand-100/80">
                        <CalendarCheck class="size-3.5 text-gold-300" />
                        Réservations
                    </span>
                    <span class="rounded-full border border-gold-300/30 bg-gold-300/10 px-2 py-0.5 text-[9px] font-medium text-gold-200">
                        {{ plan?.features.bookings ? 'Activées' : 'À activer' }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
