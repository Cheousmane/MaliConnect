<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, StatusTone } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarClock, Filter, LoaderCircle, MapPin, UserRound } from 'lucide-vue-next';
import { ref } from 'vue';

type Booking = {
    id: number;
    reference: string;
    starts_at: string | null;
    ends_at: string | null;
    duration_minutes: number | null;
    amount: string | null;
    address: string | null;
    status: string;
    status_label: string;
    status_tone: string;
    customer: string | null;
    customer_phone: string | null;
    service: string | null;
};

type Props = {
    bookings: {
        data: Booking[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: { status: string };
    statuses: Array<{ value: string; label: string }>;
    transitions: Record<string, string[]>;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Demandes reçues',
        href: '/pro/demandes',
    },
    {
        title: 'Réservations',
        href: '/pro/reservations',
    },
];

const status = ref(props.filters.status);
const filtering = ref(false);
const updating = ref<number | null>(null);

const tone = (value: string): StatusTone => {
    return ['neutral', 'brand', 'success', 'warning', 'danger', 'info'].includes(value) ? (value as StatusTone) : 'neutral';
};

const formatDate = (value: string | null): string => {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
};

const applyFilter = () => {
    filtering.value = true;

    router.get(
        route('pro.bookings.index'),
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

const nextStatuses = (booking: Booking): string[] => {
    return Object.entries(props.transitions)
        .filter(([, sources]) => sources.includes(booking.status))
        .map(([target]) => target);
};

const statusLabel = (value: string): string => {
    return props.statuses.find((item) => item.value === value)?.label ?? value;
};

const updateStatus = (booking: Booking, target: string) => {
    updating.value = booking.id;

    router.post(
        route('pro.bookings.status.update', booking.id),
        { status: target },
        {
            preserveScroll: true,
            onFinish: () => {
                updating.value = null;
            },
        },
    );
};
</script>

<template>
    <Head title="Réservations" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader
                eyebrow="Espace professionnel"
                title="Réservations"
                description="Confirmez les créneaux, clôturez les prestations terminées et annulez si nécessaire."
            >
                <template #actions>
                    <Button as-child variant="outline">
                        <Link :href="route('pro.requests.index')">Demandes reçues</Link>
                    </Button>
                </template>
            </PageHeader>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Filtrer par statut</CardTitle>
                    <CardDescription>{{ bookings.total }} réservation{{ bookings.total > 1 ? 's' : '' }} au total.</CardDescription>
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
                    <CardTitle class="text-base">Créneaux</CardTitle>
                </CardHeader>

                <CardContent>
                    <ul v-if="bookings.data.length" class="space-y-3">
                        <li
                            v-for="booking in bookings.data"
                            :key="booking.id"
                            class="flex flex-wrap items-start justify-between gap-3 rounded-xl border border-border/70 bg-background p-4"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-foreground">{{ booking.customer }}</p>
                                <p class="mt-0.5 text-xs text-muted-foreground">{{ booking.reference }}</p>
                                <p class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                                    <span class="inline-flex items-center gap-1.5">
                                        <CalendarClock class="size-3.5" aria-hidden="true" />
                                        {{ formatDate(booking.starts_at) }}
                                    </span>
                                    <span class="inline-flex items-center gap-1.5">
                                        <UserRound class="size-3.5" aria-hidden="true" />
                                        {{ booking.customer_phone ?? 'Téléphone non renseigné' }}
                                    </span>
                                    <span v-if="booking.address" class="inline-flex items-center gap-1.5">
                                        <MapPin class="size-3.5" aria-hidden="true" />
                                        {{ booking.address }}
                                    </span>
                                </p>
                            </div>

                            <div class="flex shrink-0 flex-wrap items-center gap-2">
                                <StatusBadge :tone="tone(booking.status_tone)" :label="booking.status_label" />

                                <Button
                                    v-for="target in nextStatuses(booking)"
                                    :key="target"
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    :disabled="updating === booking.id"
                                    @click="updateStatus(booking, target)"
                                >
                                    {{ statusLabel(target) }}
                                </Button>
                            </div>
                        </li>
                    </ul>

                    <p v-else class="text-sm text-muted-foreground">Aucune réservation pour le moment.</p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
