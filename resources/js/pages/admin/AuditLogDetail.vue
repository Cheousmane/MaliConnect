<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

type LogDetail = {
    id: number;
    action: string;
    action_label: string | null;
    description: string;
    actor: { id: number; name: string; email: string } | null;
    subject_type: string | null;
    subject_id: number | null;
    properties: Record<string, any> | null;
    ip_address: string | null;
    created_at: string | null;
};

type Props = { log: LogDetail };

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: '/admin' },
    { title: 'Journal d’audit', href: '/admin/journal' },
    { title: `Entrée #${props.log.id}`, href: `/admin/journal/${props.log.id}` },
];

const formatDate = (value: string | null): string => {
    if (!value) return '—';
    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
};

const propertyLabel = (key: string): string => {
    const labels: Record<string, string> = {
        is_active: 'Actif',
        previous_status: 'Statut précédent',
        new_status: 'Nouveau statut',
        decision: 'Décision',
        reason: 'Motif',
        amount: 'Montant',
        currency: 'Devise',
        subscription_id: 'ID abonnement',
        subscription_ends_at: 'Échéance abonnement',
        note: 'Note',
        previous: 'Valeurs précédentes',
        current: 'Valeurs actuelles',
        price_monthly: 'Prix mensuel',
        price_yearly: 'Prix annuel',
        max_services: 'Services max.',
        max_profiles: 'Profils max.',
        max_pricing_sheets: 'Fiches max.',
        previous_roles: 'Rôles précédents',
        new_roles: 'Nouveaux rôles',
        previous_ends_at: 'Échéance précédente',
        new_ends_at: 'Nouvelle échéance',
        payment_id: 'ID paiement',
        payment_reference: 'Référence paiement',
        rejection_reason: 'Motif de refus',
    };

    return labels[key] ?? key;
};
</script>

<template>
    <Head :title="`Journal · Entrée #${props.log.id}`" />
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader eyebrow="Journal d’audit" :title="`Entrée #${props.log.id}`" :description="props.log.description">
                <template #actions>
                    <Button as-child variant="outline">
                        <Link :href="route('admin.audit.index')">
                            <ArrowLeft class="size-4" aria-hidden="true" />
                            Retour
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Détails de l'action</CardTitle>
                        <CardDescription>Informations sur l'événement enregistré.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm">
                        <div>
                            <p class="text-xs text-muted-foreground">Action</p>
                            <p class="font-medium text-foreground">{{ props.log.action_label ?? props.log.action }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Description</p>
                            <p class="text-foreground">{{ props.log.description }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Date</p>
                            <p class="font-medium text-foreground">{{ formatDate(props.log.created_at) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Adresse IP</p>
                            <p class="font-mono text-foreground">{{ props.log.ip_address ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Sujet</p>
                            <p class="text-foreground">
                                {{ props.log.subject_type ?? '—' }}<span v-if="props.log.subject_id"> #{{ props.log.subject_id }}</span>
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Acteur</CardTitle>
                        <CardDescription>Compte à l'origine de l'action.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm">
                        <template v-if="props.log.actor">
                            <div>
                                <p class="text-xs text-muted-foreground">Nom</p>
                                <p class="font-medium text-foreground">{{ props.log.actor.name }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">E-mail</p>
                                <p class="text-foreground">{{ props.log.actor.email }}</p>
                            </div>
                        </template>
                        <p v-else class="text-muted-foreground">Action système (aucun acteur humain).</p>
                    </CardContent>
                </Card>
            </div>

            <Card v-if="props.log.properties">
                <CardHeader>
                    <CardTitle class="text-base">Propriétés</CardTitle>
                    <CardDescription>Données associées à l'événement.</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-2">
                        <div
                            v-for="(value, key) in props.log.properties"
                            :key="key"
                            class="flex items-start justify-between gap-3 rounded-xl border border-border/60 bg-muted/20 px-3 py-2 text-sm"
                        >
                            <span class="text-xs font-medium text-muted-foreground">{{ propertyLabel(String(key)) }}</span>
                            <span class="text-right text-foreground">
                                <template v-if="value === null">—</template>
                                <template v-else-if="typeof value === 'boolean'">{{ value ? 'Oui' : 'Non' }}</template>
                                <template v-else-if="Array.isArray(value)">{{ value.join(', ') }}</template>
                                <template v-else-if="typeof value === 'object'">
                                    <div class="space-y-1 text-left">
                                        <div
                                            v-for="(nestedValue, nestedKey) in value"
                                            :key="nestedKey"
                                            class="flex items-center justify-between gap-2 text-xs"
                                        >
                                            <span class="text-muted-foreground">{{ propertyLabel(String(nestedKey)) }}</span>
                                            <span class="text-foreground">
                                                <template v-if="nestedValue === null">—</template>
                                                <template v-else-if="typeof nestedValue === 'boolean'">{{ nestedValue ? 'Oui' : 'Non' }}</template>
                                                <template v-else>{{ nestedValue }}</template>
                                            </span>
                                        </div>
                                    </div>
                                </template>
                                <template v-else>{{ value }}</template>
                            </span>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
