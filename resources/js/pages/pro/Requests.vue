<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, StatusTone } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, Filter, Inbox, LoaderCircle } from 'lucide-vue-next';
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
    customer: string | null;
    quotes_count: number;
    created_at: string | null;
};

type Props = {
    requests: {
        data: RequestSummary[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: { status: string };
    statuses: Array<{ value: string; label: string }>;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Demandes reçues',
        href: '/pro/demandes',
    },
];

const status = ref(props.filters.status);
const filtering = ref(false);

const tone = (value: string): StatusTone => {
    return ['neutral', 'brand', 'success', 'warning', 'danger', 'info'].includes(value) ? (value as StatusTone) : 'neutral';
};

const formatDate = (value: string | null): string => {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium' }).format(new Date(value));
};

const applyFilter = () => {
    filtering.value = true;

    router.get(
        route('pro.requests.index'),
        { status: status.value || undefined },
        {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => {
                filtering.value = false;
            },
        },
    );
};

const pageUrl = (page: number) => route('pro.requests.index', { status: status.value || undefined, page });
</script>

<template>
    <Head title="Demandes reçues" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader
                eyebrow="Espace professionnel"
                title="Demandes reçues"
                description="Répondez aux clients, posez vos questions et envoyez vos devis."
            >
                <template #actions>
                    <Button as-child variant="outline">
                        <Link :href="route('pro.bookings.index')">Réservations</Link>
                    </Button>
                </template>
            </PageHeader>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Filtrer par statut</CardTitle>
                    <CardDescription>{{ requests.total }} demande{{ requests.total > 1 ? 's' : '' }} au total.</CardDescription>
                </CardHeader>

                <CardContent>
                    <form class="flex flex-wrap items-end gap-3" @submit.prevent="applyFilter">
                        <div class="min-w-[200px]">
                            <label for="status" class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.1em] text-muted-foreground">
                                Statut
                            </label>
                            <select
                                id="status"
                                v-model="status"
                                class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm text-foreground"
                            >
                                <option value="">Tous les statuts</option>
                                <option v-for="item in statuses" :key="item.value" :value="item.value">{{ item.label }}</option>
                            </select>
                        </div>

                        <Button type="submit" variant="brand" :disabled="filtering">
                            <LoaderCircle v-if="filtering" class="size-4 animate-spin" aria-hidden="true" />
                            <Filter v-else class="size-4" aria-hidden="true" />
                            Filtrer
                        </Button>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">File des demandes</CardTitle>
                </CardHeader>

                <CardContent>
                    <ul v-if="requests.data.length" class="space-y-3">
                        <li
                            v-for="request in requests.data"
                            :key="request.id"
                            class="flex flex-wrap items-start justify-between gap-3 rounded-xl border border-border/70 bg-background p-4"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-foreground">{{ request.title }}</p>
                                <p class="mt-0.5 text-xs text-muted-foreground">
                                    {{ request.reference }} · {{ request.customer }}
                                    <span v-if="request.city">· {{ request.city }}</span>
                                    <span v-if="request.desired_date">· pour le {{ formatDate(request.desired_date) }}</span>
                                </p>
                                <p v-if="request.quotes_count" class="mt-1 text-xs text-muted-foreground">
                                    {{ request.quotes_count }} devis envoyé{{ request.quotes_count > 1 ? 's' : '' }}
                                </p>
                            </div>

                            <div class="flex shrink-0 items-center gap-3">
                                <StatusBadge :tone="tone(request.status_tone)" :label="request.status_label" />
                                <Button as-child variant="ghost" size="sm">
                                    <Link :href="route('pro.requests.show', request.id)">
                                        Traiter
                                        <ArrowRight class="size-3.5" aria-hidden="true" />
                                    </Link>
                                </Button>
                            </div>
                        </li>
                    </ul>

                    <div v-else class="rounded-xl border border-dashed border-border p-8 text-center">
                        <span class="mx-auto flex size-10 items-center justify-center rounded-xl bg-muted text-muted-foreground">
                            <Inbox class="size-5" aria-hidden="true" />
                        </span>
                        <p class="mt-3 text-sm font-medium text-foreground">{{ status ? 'Aucune demande pour ce filtre' : 'Aucune demande pour le moment' }}</p>
                        <p class="mx-auto mt-1 max-w-sm text-sm text-muted-foreground">
                            {{ status ? 'Essayez un autre statut ou réinitialisez le filtre.' : 'Les demandes adressées à vos profils vérifiés apparaîtront ici.' }}
                        </p>
                        <Button v-if="status" variant="outline" size="sm" class="mt-4" @click="status = ''; applyFilter()">Réinitialiser</Button>
                    </div>

                    <nav v-if="requests.last_page > 1" class="mt-4 flex items-center justify-between gap-3" aria-label="Pagination des demandes">
                        <p class="text-xs text-muted-foreground">Page {{ requests.current_page }} sur {{ requests.last_page }}</p>
                        <div class="flex gap-2">
                            <Button as-child variant="outline" size="sm" :class="requests.current_page <= 1 ? 'pointer-events-none opacity-50' : ''">
                                <Link :href="pageUrl(requests.current_page - 1)">Précédent</Link>
                            </Button>
                            <Button as-child variant="outline" size="sm" :class="requests.current_page >= requests.last_page ? 'pointer-events-none opacity-50' : ''">
                                <Link :href="pageUrl(requests.current_page + 1)">Suivant</Link>
                            </Button>
                        </div>
                    </nav>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
