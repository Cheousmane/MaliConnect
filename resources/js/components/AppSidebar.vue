<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Button } from '@/components/ui/button';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarRail, useSidebar } from '@/components/ui/sidebar';
import type { NavGroup, NavItem, SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    Bell,
    BriefcaseBusiness,
    Building2,
    CalendarClock,
    ClipboardCheck,
    ClipboardList,
    Heart,
    LayoutDashboard,
    LifeBuoy,
    MessageSquare,
    ReceiptText,
    ScrollText,
    ShieldCheck,
    Star,
    Store,
    Tags,
    Users,
    Wallet,
    X,
} from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    variant?: 'professional' | 'admin';
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'professional',
});

const adminGroups: NavGroup[] = [
    {
        title: 'Administration',
        items: [
            { title: 'Tableau de bord', href: '/admin', icon: LayoutDashboard },
            { title: 'Profils à vérifier', href: '/admin/provider-profiles', icon: ClipboardCheck },
            { title: 'Utilisateurs', href: '/admin/users', icon: Users },
            { title: 'Entreprises', href: '/admin/businesses', icon: Building2 },
            { title: 'Professionnels', href: '/admin/professionals', icon: BriefcaseBusiness },
            { title: 'Catégories', href: '/admin/categories', icon: ScrollText },
            { title: 'Sous-catégories', href: '/admin/sous-categories', icon: ScrollText },
            { title: 'Services', href: '/admin/services', icon: Store },
            { title: 'Demandes', href: '/admin/demandes', icon: ClipboardList },
            { title: 'Réservations', href: '/admin/reservations', icon: CalendarClock },
            { title: 'Modération des avis', href: '/admin/avis', icon: Star },
            { title: 'Formules', href: '/admin/formules', icon: Tags },
            { title: 'Abonnements', href: '/admin/abonnements', icon: Wallet },
            { title: 'Paiements', href: '/admin/paiements', icon: Wallet },
            { title: 'Signalements', href: '/admin/signalements', icon: LifeBuoy },
            { title: 'Journal d’audit', href: '/admin/journal', icon: ShieldCheck },
        ],
    },
];

const page = usePage<SharedData>();

const groups = computed<NavGroup[]>(() => {
    if (props.variant === 'admin') {
        return adminGroups;
    }

    const permissions = page.props.auth.permissions;
    const isProfessional = permissions.includes('provider_profiles.create') || permissions.includes('service_requests.respond');
    const isCustomer = permissions.includes('service_requests.create');

    const items: NavItem[] = [{ title: 'Tableau de bord', href: '/dashboard', icon: LayoutDashboard }];

    if (isProfessional) {
        items.push(
            { title: 'Mes profils', href: '/pro/profiles', icon: Store },
            { title: 'Fiches tarifaires', href: '/pro/tarifs', icon: ReceiptText },
            { title: 'Mon entreprise', href: '/pro/businesses', icon: Building2 },
            { title: 'Demandes', href: '/pro/demandes', icon: ClipboardList },
            { title: 'Réservations', href: '/pro/reservations', icon: CalendarClock },
            { title: 'Avis reçus', href: '/pro/avis', icon: Star },
            { title: 'Mon abonnement', href: '/pro/abonnement', icon: Wallet },
            { title: 'Formules', href: '/pro/abonnement/offres', icon: Tags },
            { title: 'Messages', href: '#', icon: MessageSquare, disabled: true, badge: 'Bientôt' },
            { title: 'Notifications', href: '/notifications', icon: Bell },
        );
    }

    if (isCustomer) {
        items.push(
            { title: 'Mes demandes', href: '/demandes', icon: ClipboardList },
            { title: 'Mes réservations', href: '/reservations', icon: CalendarClock },
            { title: 'Mes favoris', href: '/favoris', icon: Heart },
            { title: 'Mes avis', href: '/avis', icon: Star },
            { title: 'Notifications', href: '/notifications', icon: Bell },
        );
    }

    // Cas général : au moins l'accès aux notifications.
    if (!isProfessional && !isCustomer) {
        items.push({ title: 'Notifications', href: '/notifications', icon: Bell });
    }

    return [
        {
            title: isProfessional ? 'Espace professionnel' : 'Espace client',
            items,
        },
    ];
});

const workspaceLabel = computed(() => {
    if (props.variant === 'admin') {
        return 'Administration';
    }

    const permissions = page.props.auth.permissions;
    const isProfessional = permissions.includes('provider_profiles.create') || permissions.includes('service_requests.respond');

    return isProfessional ? 'Espace professionnel' : 'Espace client';
});

const { setOpenMobile } = useSidebar();
</script>

<template>
    <Sidebar collapsible="icon" variant="inset" class="border-r border-sidebar-border/60">
        <SidebarHeader class="border-b border-sidebar-border/60 px-3 py-4 group-data-[collapsible=icon]:px-1.5">
            <div class="flex items-center justify-between gap-2">
                <Link :href="route('dashboard')" class="flex items-center" aria-label="MaliConnect — Tableau de bord">
                    <AppLogo />
                </Link>

                <Button
                    variant="ghost"
                    size="icon"
                    class="size-8 text-muted-foreground lg:hidden"
                    aria-label="Fermer la navigation"
                    @click="setOpenMobile(false)"
                >
                    <X class="size-4" aria-hidden="true" />
                </Button>
            </div>

            <div
                class="mt-3 flex items-center justify-between gap-2 rounded-lg border border-sidebar-border/70 bg-sidebar-accent/50 px-2.5 py-2 group-data-[collapsible=icon]:hidden"
            >
                <div class="min-w-0">
                    <p class="text-[9px] font-semibold uppercase tracking-[0.14em] text-sidebar-foreground/50">Vous êtes dans</p>
                    <p class="mt-0.5 truncate text-[11px] font-semibold text-sidebar-foreground">{{ workspaceLabel }}</p>
                </div>
                <span class="size-1.5 shrink-0 rounded-full" :class="variant === 'admin' ? 'bg-gold-500' : 'bg-brand-500'" aria-hidden="true" />
            </div>
        </SidebarHeader>

        <SidebarContent class="gap-0 py-2">
            <NavMain :groups="groups" />
        </SidebarContent>

        <SidebarFooter class="gap-2 border-t border-sidebar-border/60 p-2">
            <div class="flex items-center justify-between rounded-lg px-2 py-1.5 lg:hidden">
                <span class="text-[11px] font-medium text-sidebar-foreground/70">Apparence</span>
                <ThemeToggle />
            </div>

            <div
                class="rounded-xl border border-brand-100/80 bg-brand-50/70 p-3.5 group-data-[collapsible=icon]:hidden dark:border-brand-900/70 dark:bg-brand-950/30"
            >
                <div class="flex items-center gap-2 text-[11px] font-semibold text-brand-800 dark:text-brand-200">
                    <LifeBuoy class="size-4 text-brand-600 dark:text-brand-400" aria-hidden="true" />
                    Besoin d’aide ?
                </div>
                <p class="mt-1 text-[11px] leading-relaxed text-muted-foreground">Notre équipe vous accompagne dans vos premiers pas.</p>
                <Button as-child variant="soft" size="sm" class="mt-2.5 w-full justify-between text-[11px]">
                    <a href="mailto:support@maliconnect.ml">
                        Contacter le support
                        <ArrowUpRight class="size-3.5" aria-hidden="true" />
                    </a>
                </Button>
            </div>

            <NavUser />
        </SidebarFooter>

        <SidebarRail />
    </Sidebar>
    <slot />
</template>
