<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, StatusTone } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, CalendarClock, Filter, Inbox, Plus } from 'lucide-vue-next';
import { ref } from 'vue';

type RequestSummary = {
    id: number;
    reference: string;
    title: string;
    status: string;
    status_label: string;
    status_tone: string;
    city: string;
    commune: string | null;
    desired_date: string | null;
    professional: string | null;
    quotes_count: number;
    created_at: string | null;
};

type Booking = {
    id: number;
    reference: string;
    starts_at: string | null;
    status: string;
    status_label: string;
    status_tone: string;
    professional: string | null;
    service: string | null;
};

type Props = {
    requests: { data: RequestSummary[]; current_page: number; last_page: number; total: number };
    bookings: Booking[];
    filters?: { status: string };
    statuses?: Array<{ value: string; label: string }>;
};

const props = defineProps<Props>();
const status = ref(props.filters?.status ?? '');
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Mes demandes', href: '/demandes' }];

const tone = (value: string): StatusTone => (['neutral', 'brand', 'success', 'warning', 'danger', 'info'].includes(value) ? (value as StatusTone) : 'neutral');
const formatDate = (value: string | null): string => (value ? new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '—');
const applyFilter = () => router.get(route('requests.index'), { status: status.value || undefined }, { preserveState: true, preserveScroll: true });
const pageUrl = (page: number) => route('requests.index', { status: status.value || undefined, page });
</script>

<template>
    <Head title="Mes demandes de devis" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader
                eyebrow="Mes demandes"
                title="Demandes de devis et réservations"
                description="Suivez vos demandes, vos échanges avec les professionnels et vos réservations."
            >
                <template #actions>
                    <Button as-child variant="brand">
                        <Link :href="route('requests.create')"><Plus class="size-4" aria-hidden="true" />Nouvelle demande</Link>
                    </Button>
                </template>
            </PageHeader>

            <Card>
                <CardHeader>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <CardTitle class="text-base">Demandes envoyées</CardTitle>
                            <CardDescription>{{ props.requests.total }} demande{{ props.requests.total > 1 ? 's' : '' }} au total.</CardDescription>
                        </div>
                        <form class="flex flex-wrap items-end gap-2" @submit.prevent="applyFilter">
                            <div class="min-w-[180px]">
                                <label for="request-status" class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.1em] text-muted-foreground">Statut</label>
                                <select id="request-status" v-model="status" class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm text-foreground">
                                    <option value="">Tous les statuts</option>
                                    <option v-for="item in props.statuses ?? []" :key="item.value" :value="item.value">{{ item.label }}</option>
                                </select>
                            </div>
                            <Button type="submit" variant="outline" size="sm"><Filter class="size-4" aria-hidden="true" />Filtrer</Button>
                        </form>
                    </div>
                </CardHeader>

                <CardContent>
                    <ul v-if="props.requests.data.length" class="space-y-3">
                        <li v-for="request in props.requests.data" :key="request.id" class="flex flex-wrap items-start justify-between gap-3 rounded-xl border border-border/70 bg-background p-4">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-foreground">
                                    <Link :href="route('requests.show', request.id)" class="hover:text-brand-700 dark:hover:text-brand-300">{{ request.title }}</Link>
                                </p>
                                <p class="mt-0.5 text-xs text-muted-foreground">
                                    {{ request.reference }} · {{ request.professional ?? 'Professionnel' }}<span v-if="request.city"> · {{ request.city }}</span>
                                </p>
                                <p class="mt-1 text-[11px] text-muted-foreground">
                                    {{ request.quotes_count }} devis · {{ request.created_at ? `Créée le ${new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium' }).format(new Date(request.created_at))}` : '' }}
                                </p>
                            </div>

                            <div class="flex shrink-0 items-center gap-3">
                                <StatusBadge :tone="tone(request.status_tone)" :label="request.status_label" />
                                <Button as-child variant="ghost" size="sm">
                                    <Link :href="route('requests.show', request.id)">Détail <ArrowRight class="size-3.5" aria-hidden="true" /></Link>
                                </Button>
                            </div>
                        </li>
                    </ul>

                    <div v-else class="rounded-xl border border-dashed border-border p-8 text-center">
                        <span class="mx-auto flex size-10 items-center justify-center rounded-xl bg-muted text-muted-foreground"><Inbox class="size-5" aria-hidden="true" /></span>
                        <p class="mt-3 text-sm font-medium text-foreground">{{ status ? 'Aucune demande pour ce filtre' : 'Aucune demande envoyée' }}</p>
                        <p class="mx-auto mt-1 max-w-sm text-sm text-muted-foreground">
                            {{ status ? 'Essayez un autre statut pour voir vos demandes.' : 'Choisissez un professionnel vérifié et décrivez votre besoin pour recevoir un devis.' }}
                        </p>
                        <Button as-child variant="brand" class="mt-4">
                            <Link :href="status ? route('requests.index') : route('requests.create')">{{ status ? 'Réinitialiser' : 'Créer une demande' }}</Link>
                        </Button>
                    </div>

                    <nav v-if="props.requests.last_page > 1" class="mt-4 flex items-center justify-between gap-3" aria-label="Pagination des demandes">
                        <p class="text-xs text-muted-foreground">Page {{ props.requests.current_page }} sur {{ props.requests.last_page }}</p>
                        <div class="flex gap-2">
                            <Button as-child variant="outline" size="sm" :class="props.requests.current_page <= 1 ? 'pointer-events-none opacity-50' : ''"><Link :href="pageUrl(props.requests.current_page - 1)">Précédent</Link></Button>
                            <Button as-child variant="outline" size="sm" :class="props.requests.current_page >= props.requests.last_page ? 'pointer-events-none opacity-50' : ''"><Link :href="pageUrl(props.requests.current_page + 1)">Suivant</Link></Button>
                        </div>
                    </nav>
                </CardContent>
            </Card>

            <Card v-if="props.bookings.length">
                <CardHeader>
                    <CardTitle class="text-base">Réservations à venir</CardTitle>
                    <CardDescription>Vos prochaines prestations confirmées ou en attente.</CardDescription>
                </CardHeader>
                <CardContent>
                    <ul class="space-y-3">
                        <li v-for="booking in props.bookings" :key="booking.id" class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-border/70 bg-background p-4">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-foreground">{{ booking.professional }}</p>
                                <p class="mt-0.5 flex items-center gap-1.5 text-xs text-muted-foreground">
                                    <CalendarClock class="size-3.5" aria-hidden="true" />
                                    {{ formatDate(booking.starts_at) }}
                                    <span v-if="booking.service">· {{ booking.service }}</span>
                                </p>
                            </div>
                            <StatusBadge :tone="tone(booking.status_tone)" :label="booking.status_label" />
                        </li>
                    </ul>
                    <Button as-child variant="outline" size="sm" class="mt-4"><Link :href="route('bookings.index')">Toutes mes réservations</Link></Button>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
