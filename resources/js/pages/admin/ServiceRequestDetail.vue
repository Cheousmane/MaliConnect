<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import type { BreadcrumbItem, StatusTone } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

type RequestDetail = {
    id: number;
    reference: string;
    title: string;
    description: string | null;
    status: string;
    status_label: string;
    status_tone: string;
    customer: { id: number; name: string; email: string; phone: string | null } | string | null;
    professional: { id: number; business_name: string; slug: string } | string | null;
    city: string | null;
    commune: string | null;
    budget_min: number | null;
    budget_max: number | null;
    created_at: string | null;
};

type Props = { request: RequestDetail };

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: '/admin' },
    { title: 'Demandes', href: '/admin/demandes' },
    { title: props.request.reference, href: `/admin/demandes/${props.request.id}` },
];

const tone = (value: string): StatusTone =>
    ['neutral', 'brand', 'success', 'warning', 'danger', 'info'].includes(value) ? (value as StatusTone) : 'neutral';

const formatDate = (value: string | null): string => {
    if (!value) return '—';
    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
};

const formatMoney = (value: number | null): string => {
    if (value === null) return '—';
    return `${new Intl.NumberFormat('fr-FR').format(value)} FCFA`;
};
</script>

<template>
    <Head :title="`Demande · ${props.request.reference}`" />
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader eyebrow="Demande de devis" :title="props.request.reference" :description="props.request.title">
                <template #actions>
                    <Button as-child variant="outline">
                        <Link :href="route('admin.service-requests.index')">
                            <ArrowLeft class="size-4" aria-hidden="true" />
                            Retour
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Détails de la demande</CardTitle>
                        <CardDescription>Informations soumises par le client.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm">
                        <div>
                            <p class="text-xs text-muted-foreground">Titre</p>
                            <p class="font-medium text-foreground">{{ props.request.title }}</p>
                        </div>
                        <div v-if="props.request.description">
                            <p class="text-xs text-muted-foreground">Description</p>
                            <p class="whitespace-pre-line text-foreground">{{ props.request.description }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Statut</p>
                            <div class="mt-1">
                                <StatusBadge :tone="tone(props.request.status_tone)" :label="props.request.status_label" />
                            </div>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Ville</p>
                            <p class="font-medium text-foreground">
                                {{ props.request.city ?? '—' }}<span v-if="props.request.commune"> · {{ props.request.commune }}</span>
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Budget</p>
                            <p class="font-medium text-foreground">
                                {{ formatMoney(props.request.budget_min) }} — {{ formatMoney(props.request.budget_max) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Créée le</p>
                            <p class="font-medium text-foreground">{{ formatDate(props.request.created_at) }}</p>
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
                                    typeof props.request.customer === 'object' && props.request.customer !== null
                                        ? props.request.customer.name
                                        : (props.request.customer ?? '—')
                                }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Professionnel</p>
                            <p class="font-medium text-foreground">
                                {{
                                    typeof props.request.professional === 'object' && props.request.professional !== null
                                        ? props.request.professional.business_name
                                        : (props.request.professional ?? '—')
                                }}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AdminLayout>
</template>
