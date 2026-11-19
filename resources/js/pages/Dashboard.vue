<script setup lang="ts">
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatCard from '@/components/StatCard.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SharedData, StatTrend, StatusTone, TableColumn } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';
import {
    ArrowRight,
    ArrowUpRight,
    CalendarDays,
    CheckCircle2,
    Circle,
    ClipboardList,
    Clock,
    Eye,
    Inbox,
    MessageSquare,
    MoreHorizontal,
    Settings2,
    Sparkles,
    Users,
    Wrench,
} from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage<SharedData>();
const user = computed(() => page.props.auth.user);
const firstName = computed(() => user.value.name.split(' ')[0] ?? user.value.name);

// Statistiques réelles du compte connecté.
const realStats = computed(() => (page.props as any).stats ?? { profiles: 0, services: 0, pricing_sheets: 0 });

// Demandes récentes réelles.
const requests = computed(() => (page.props as any).requests ?? []);

// Profils professionnels réellement rattachés au compte connecté.
const profiles = computed(
    () =>
        (page.props.profiles ?? []) as Array<{
            id: number;
            business_name: string;
            status: string;
            status_label: string;
            status_tone: string;
            is_active: boolean;
            category: string | null;
            city: string;
        }>,
);

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Vue d’ensemble', href: '/dashboard' }];

const today = new Intl.DateTimeFormat('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date());

interface StatItem {
    label: string;
    value: string;
    icon: LucideIcon;
    tone: StatusTone;
    trend: StatTrend;
    description: string;
    sparkline: number[];
}

const stats: StatItem[] = [
    {
        label: 'Mes profils',
        value: String(realStats.value.profiles),
        icon: Users,
        tone: 'brand',
        trend: { value: '', direction: 'neutral' },
        description: 'profils professionnels',
        sparkline: [],
    },
    {
        label: 'Mes services',
        value: String(realStats.value.services),
        icon: Wrench,
        tone: 'info',
        trend: { value: '', direction: 'neutral' },
        description: 'services publiés',
        sparkline: [],
    },
    {
        label: 'Mes fiches tarifaires',
        value: String(realStats.value.pricing_sheets),
        icon: ClipboardList,
        tone: 'success',
        trend: { value: '', direction: 'neutral' },
        description: 'fiches créées',
        sparkline: [],
    },
    {
        label: 'Demandes en attente',
        value: '0',
        icon: Clock,
        tone: 'warning',
        trend: { value: 'À traiter', direction: 'neutral' },
        description: 'sous 48 h',
        sparkline: [],
    },
];

const requestColumns: TableColumn[] = [
    { key: 'client', label: 'Client' },
    { key: 'service', label: 'Service' },
    { key: 'date', label: 'Reçue le', class: 'hidden xl:table-cell', hideOnMobile: true },
    { key: 'status', label: 'Statut' },
    { key: 'amount', label: 'Budget', align: 'right' },
    { key: 'actions', label: '', align: 'right', hideOnMobile: true },
];

// Les demandes sont chargées depuis le serveur via page.props.requests

// L'activité hebdomadaire sera disponible prochainement (fonctionnalité en développement).

// Les messages seront disponibles prochainement (fonctionnalité en développement).

const profileChecklist = [
    { label: 'Photo de profil', complete: true },
    { label: 'Présentation professionnelle', complete: true },
    { label: 'Zone d’intervention', complete: true },
    { label: 'Documents vérifiés', complete: false },
];
</script>

<template>
    <Head title="Vue d’ensemble" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader
                eyebrow="Tableau de bord"
                :title="`Bonjour, ${firstName} 👋`"
                description="Voici un aperçu de votre activité et des opportunités qui vous attendent aujourd’hui."
            >
                <template #meta>
                    <p class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                        <span class="inline-flex items-center gap-1.5">
                            <CalendarDays class="size-3.5" aria-hidden="true" />
                            <span class="capitalize">{{ today }}</span>
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="size-1.5 rounded-full bg-gold-500" aria-hidden="true" />
                            Aperçu de démonstration
                        </span>
                    </p>
                </template>

                <template #actions>
                    <Button as-child variant="outline">
                        <Link :href="route('profile.edit')">
                            <Settings2 class="size-4" aria-hidden="true" />
                            Modifier mon profil
                        </Link>
                    </Button>
                    <span
                        class="inline-flex h-10 items-center gap-2 rounded-lg border border-dashed border-border bg-background px-4 text-sm font-medium text-muted-foreground"
                    >
                        <Sparkles class="size-4" aria-hidden="true" />
                        Nouvelle disponibilité
                        <span class="rounded-full bg-muted px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-wide">Bientôt</span>
                    </span>
                </template>
            </PageHeader>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    v-for="stat in stats"
                    :key="stat.label"
                    :label="stat.label"
                    :value="stat.value"
                    :icon="stat.icon"
                    :tone="stat.tone"
                    :trend="stat.trend"
                    :description="stat.description"
                    :sparkline="stat.sparkline"
                />
            </div>

            <Card v-if="profiles.length > 0">
                <CardHeader class="flex-row items-start justify-between gap-3 space-y-0">
                    <div>
                        <CardTitle class="text-base">Mes profils professionnels</CardTitle>
                        <CardDescription class="mt-1"> Vos profils et leur état de vérification administrative. </CardDescription>
                    </div>
                    <Button as-child variant="outline" size="sm" class="shrink-0">
                        <Link :href="route('pro.profiles.index')">Tout gérer</Link>
                    </Button>
                </CardHeader>

                <CardContent>
                    <ul class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        <li
                            v-for="profile in profiles"
                            :key="profile.id"
                            class="flex items-center justify-between gap-3 rounded-xl border border-border/80 bg-background px-3.5 py-3"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-foreground">{{ profile.business_name }}</p>
                                <p class="truncate text-xs text-muted-foreground">{{ profile.category ?? 'Sans catégorie' }} · {{ profile.city }}</p>
                            </div>
                            <StatusBadge :tone="profile.status_tone as StatusTone" :label="profile.status_label" class="shrink-0" />
                        </li>
                    </ul>
                </CardContent>
            </Card>

            <div class="space-y-4">
                <Card class="min-w-0 overflow-hidden">
                    <CardHeader class="flex-row items-start justify-between gap-3 space-y-0">
                        <div>
                            <CardTitle class="text-base">Demandes récentes</CardTitle>
                            <CardDescription class="mt-1">Les dernières demandes reçues sur votre profil.</CardDescription>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <StatusBadge
                                tone="neutral"
                                :label="`${requests.length} demande${requests.length > 1 ? 's' : ''}`"
                                class="hidden sm:inline-flex"
                            />
                            <Button variant="outline" size="sm" class="hidden sm:inline-flex">
                                Tout voir
                                <ArrowUpRight class="size-3.5" aria-hidden="true" />
                            </Button>
                        </div>
                    </CardHeader>

                    <CardContent class="px-0 pb-2 sm:px-0">
                        <DataTable :columns="requestColumns" :rows="requests" :row-key="'id'" class="px-2 sm:px-3">
                            <template #cell-client="{ row }">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-[11px] font-semibold text-brand-700 dark:bg-brand-950/70 dark:text-brand-300"
                                    >
                                        {{ row.initials }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-foreground">{{ row.client }}</p>
                                        <p class="truncate text-xs text-muted-foreground">{{ row.city }}</p>
                                    </div>
                                </div>
                            </template>

                            <template #cell-service="{ row }">
                                <span class="text-sm text-foreground">{{ row.service }}</span>
                            </template>

                            <template #cell-date="{ row }">
                                <span class="whitespace-nowrap text-xs text-muted-foreground">{{ row.date }}</span>
                            </template>

                            <template #cell-status="{ row }">
                                <StatusBadge :tone="row.statusTone" :label="row.statusLabel" dot />
                            </template>

                            <template #cell-amount="{ row }">
                                <span class="whitespace-nowrap text-sm font-medium tabular-nums text-foreground">{{ row.amount }}</span>
                            </template>

                            <template #cell-actions>
                                <Button variant="ghost" size="icon-sm" class="text-muted-foreground" aria-label="Actions de la demande">
                                    <MoreHorizontal class="size-4" aria-hidden="true" />
                                </Button>
                            </template>
                        </DataTable>
                    </CardContent>
                </Card>

                <div class="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader class="pb-3">
                            <div class="flex items-center justify-between gap-3">
                                <CardTitle class="text-base">Profil professionnel</CardTitle>
                                <StatusBadge tone="warning" label="80 %" />
                            </div>
                            <CardDescription>Un profil complet inspire davantage confiance.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div
                                class="h-2 overflow-hidden rounded-full bg-muted"
                                role="progressbar"
                                aria-valuenow="80"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                aria-label="Profil complété à 80 %"
                            >
                                <div class="h-full w-[80%] rounded-full bg-brand-600" />
                            </div>

                            <ul class="mt-4 space-y-2.5">
                                <li v-for="item in profileChecklist" :key="item.label" class="flex items-center gap-2.5 text-sm">
                                    <CheckCircle2 v-if="item.complete" class="size-4 shrink-0 text-success" aria-hidden="true" />
                                    <Circle v-else class="size-4 shrink-0 text-border" aria-hidden="true" />
                                    <span :class="item.complete ? 'text-muted-foreground' : 'font-medium text-foreground'">{{ item.label }}</span>
                                    <StatusBadge v-if="!item.complete" tone="warning" label="À faire" class="ml-auto" />
                                </li>
                            </ul>

                            <Button as-child variant="soft" class="mt-4 w-full justify-between">
                                <Link :href="route('profile.edit')">
                                    Compléter mon profil
                                    <ArrowRight class="size-4" aria-hidden="true" />
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="pb-3">
                            <CardTitle class="text-base">Activité récente</CardTitle>
                            <CardDescription>Les derniers événements de votre espace.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div
                                class="relative space-y-4 before:absolute before:bottom-2 before:left-[15px] before:top-2 before:w-px before:bg-border"
                            >
                                <div class="relative flex gap-3">
                                    <span
                                        class="relative z-10 flex size-8 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-600 ring-4 ring-card dark:bg-brand-950/70 dark:text-brand-300"
                                    >
                                        <Inbox class="size-3.5" aria-hidden="true" />
                                    </span>
                                    <div class="min-w-0 pt-0.5">
                                        <p class="text-xs font-medium leading-snug text-foreground">Nouvelle demande d’Aïssata Keïta</p>
                                        <p class="mt-0.5 text-[11px] text-muted-foreground">Réparation de fuite · Il y a 12 min</p>
                                    </div>
                                </div>

                                <div class="relative flex gap-3">
                                    <span
                                        class="relative z-10 flex size-8 shrink-0 items-center justify-center rounded-full bg-info/10 text-info ring-4 ring-card"
                                    >
                                        <MessageSquare class="size-3.5" aria-hidden="true" />
                                    </span>
                                    <div class="min-w-0 pt-0.5">
                                        <p class="text-xs font-medium leading-snug text-foreground">Nouveau message d’un client</p>
                                        <p class="mt-0.5 text-[11px] text-muted-foreground">Mamadou Sissoko · Il y a 3 h</p>
                                    </div>
                                </div>

                                <div class="relative flex gap-3">
                                    <span
                                        class="relative z-10 flex size-8 shrink-0 items-center justify-center rounded-full bg-info/10 text-info ring-4 ring-card"
                                    >
                                        <Eye class="size-3.5" aria-hidden="true" />
                                    </span>
                                    <div class="min-w-0 pt-0.5">
                                        <p class="text-xs font-medium leading-snug text-foreground">Votre profil a été consulté 42 fois</p>
                                        <p class="mt-0.5 text-[11px] text-muted-foreground">Aujourd’hui · +18 % vs hier</p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <div class="grid gap-4 xl:grid-cols-[minmax(0,1.35fr)_minmax(0,1fr)]">
                <Card>
                    <CardHeader class="flex-row items-start justify-between gap-3 space-y-0">
                        <div>
                            <CardTitle class="text-base">Performance de la semaine</CardTitle>
                            <CardDescription class="mt-1">Vues de votre profil et intérêt pour vos services.</CardDescription>
                        </div>
                        <div class="flex shrink-0 items-center gap-3 text-[11px] text-muted-foreground">
                            <span class="flex items-center gap-1.5"><span class="size-2 rounded-full bg-brand-500" aria-hidden="true" /> Vues</span>
                            <span class="flex items-center gap-1.5"
                                ><span class="size-2 rounded-full bg-gold-400" aria-hidden="true" /> Aujourd’hui</span
                            >
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="rounded-xl border border-dashed border-border bg-muted/20 px-4 py-8 text-center">
                            <p class="text-sm font-medium text-foreground">Statistiques en préparation</p>
                            <p class="mt-1 text-xs text-muted-foreground">Les statistiques de performance seront disponibles prochainement.</p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex-row items-start justify-between gap-3 space-y-0">
                        <div>
                            <CardTitle class="text-base">Messages récents</CardTitle>
                            <CardDescription class="mt-1">Vos échanges les plus récents.</CardDescription>
                        </div>
                        <StatusBadge tone="brand" label="2 non lus" />
                    </CardHeader>
                    <CardContent class="space-y-1">
                        <div class="rounded-xl border border-dashed border-border bg-muted/20 px-4 py-8 text-center">
                            <p class="text-sm font-medium text-foreground">Messagerie en préparation</p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                La messagerie sera disponible prochainement. Vous pourrez alors échanger directement avec vos clients.
                            </p>
                        </div>

                        <Button variant="ghost" size="sm" class="mt-2 w-full justify-between text-xs text-muted-foreground" disabled>
                            Ouvrir la messagerie
                            <span class="rounded-full bg-muted px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-wide">Bientôt</span>
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
