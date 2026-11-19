<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, StatusTone } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, CalendarClock, MapPin, Star } from 'lucide-vue-next';

type ReviewSummary = {
    id: number;
    status: string;
    status_label: string;
    status_tone: string;
};

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
    professional: string | null;
    professional_slug: string | null;
    service: string | null;
    review: ReviewSummary | null;
    can_review: boolean;
};

type Props = {
    bookings: {
        data: Booking[];
        current_page: number;
        last_page: number;
        total: number;
    };
};

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Mes demandes',
        href: '/demandes',
    },
    {
        title: 'Réservations',
        href: '/reservations',
    },
];

const tone = (value: string): StatusTone => {
    return ['neutral', 'brand', 'success', 'warning', 'danger', 'info'].includes(value) ? (value as StatusTone) : 'neutral';
};

const formatDate = (value: string | null): string => {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
};
</script>

<template>
    <Head title="Mes réservations" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <Link
                :href="route('requests.index')"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft class="size-4" aria-hidden="true" />
                Retour à mes demandes
            </Link>

            <PageHeader
                eyebrow="Réservations"
                title="Mes réservations"
                description="Chaque prestation réservée est due à un créneau unique : une annulation est nécessaire pour en changer."
            />

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">{{ bookings.total }} réservation{{ bookings.total > 1 ? 's' : '' }}</CardTitle>
                    <CardDescription>Historique complet de vos créneaux.</CardDescription>
                </CardHeader>

                <CardContent>
                    <ul v-if="bookings.data.length" class="space-y-3">
                        <li
                            v-for="booking in bookings.data"
                            :key="booking.id"
                            class="flex flex-wrap items-start justify-between gap-3 rounded-xl border border-border/70 bg-background p-4"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-foreground">{{ booking.professional }}</p>
                                <p class="mt-0.5 text-xs text-muted-foreground">{{ booking.reference }}</p>
                                <p class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                                    <span class="inline-flex items-center gap-1.5">
                                        <CalendarClock class="size-3.5" aria-hidden="true" />
                                        {{ formatDate(booking.starts_at) }}
                                    </span>
                                    <span v-if="booking.address" class="inline-flex items-center gap-1.5">
                                        <MapPin class="size-3.5" aria-hidden="true" />
                                        {{ booking.address }}
                                    </span>
                                    <span v-if="booking.service">{{ booking.service }}</span>
                                </p>
                            </div>

                            <div class="flex shrink-0 flex-wrap items-center gap-2">
                                <StatusBadge :tone="tone(booking.status_tone)" :label="booking.status_label" />

                                <StatusBadge
                                    v-if="booking.review"
                                    :tone="tone(booking.review.status_tone)"
                                    :label="`Avis : ${booking.review.status_label}`"
                                    :icon="Star"
                                />

                                <Button v-if="booking.can_review" as-child variant="brand" size="sm">
                                    <Link :href="route('reviews.create', booking.id)">
                                        <Star class="size-4" aria-hidden="true" />
                                        Laisser un avis
                                    </Link>
                                </Button>

                                <Button v-if="booking.status === 'pending' || booking.status === 'confirmed'" as-child variant="ghost" size="sm">
                                    <Link :href="route('bookings.cancel', booking.id)" method="post" as="button" class="text-destructive">
                                        Annuler
                                    </Link>
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
