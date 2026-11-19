<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import type { BreadcrumbItem, StatusTone } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

type BookingDetail = {
    id: number;
    reference: string;
    customer: { id: number; name: string; email: string; phone: string | null } | string | null;
    professional: { id: number; business_name: string; slug: string } | string | null;
    starts_at: string | null;
    amount: number;
    status: string;
    status_label: string;
    status_tone: string;
    created_at: string | null;
};

type Props = { booking: BookingDetail };

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: '/admin' },
    { title: 'Réservations', href: '/admin/reservations' },
    { title: props.booking.reference, href: `/admin/reservations/${props.booking.id}` },
];

const tone = (value: string): StatusTone =>
    ['neutral', 'brand', 'success', 'warning', 'danger', 'info'].includes(value) ? (value as StatusTone) : 'neutral';

const formatDate = (value: string | null): string => {
    if (!value) return '—';
    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
};

const formatMoney = (value: number): string => `${new Intl.NumberFormat('fr-FR').format(value)} FCFA`;
</script>

<template>
    <Head :title="`Réservation · ${props.booking.reference}`" />
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader
                eyebrow="Réservation"
                :title="props.booking.reference"
                :description="`${typeof props.booking.customer === 'object' && props.booking.customer !== null ? props.booking.customer.name : (props.booking.customer ?? '—')} · ${typeof props.booking.professional === 'object' && props.booking.professional !== null ? props.booking.professional.business_name : (props.booking.professional ?? '—')}`"
            >
                <template #actions>
                    <Button as-child variant="outline">
                        <Link :href="route('admin.bookings.index')">
                            <ArrowLeft class="size-4" aria-hidden="true" />
                            Retour
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Détails de la réservation</CardTitle>
                        <CardDescription>Informations du rendez-vous.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm">
                        <div>
                            <p class="text-xs text-muted-foreground">Référence</p>
                            <p class="font-mono font-medium text-foreground">{{ props.booking.reference }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Date et heure</p>
                            <p class="font-medium text-foreground">{{ formatDate(props.booking.starts_at) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Montant</p>
                            <p class="font-medium text-foreground">{{ formatMoney(props.booking.amount) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Statut</p>
                            <div class="mt-1">
                                <StatusBadge :tone="tone(props.booking.status_tone)" :label="props.booking.status_label" />
                            </div>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Créée le</p>
                            <p class="font-medium text-foreground">{{ formatDate(props.booking.created_at) }}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Parties prenantes</CardTitle>
                        <CardDescription>Client et professionnel concernés.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm">
                        <div>
                            <p class="text-xs text-muted-foreground">Client</p>
                            <p class="font-medium text-foreground">
                                {{
                                    typeof props.booking.customer === 'object' && props.booking.customer !== null
                                        ? props.booking.customer.name
                                        : (props.booking.customer ?? '—')
                                }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Professionnel</p>
                            <p class="font-medium text-foreground">
                                {{
                                    typeof props.booking.professional === 'object' && props.booking.professional !== null
                                        ? props.booking.professional.business_name
                                        : (props.booking.professional ?? '—')
                                }}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AdminLayout>
</template>
