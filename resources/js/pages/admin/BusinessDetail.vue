<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import type { BreadcrumbItem, StatusTone } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

type BusinessDetail = {
    id: number;
    name: string;
    slug: string;
    status: string;
    status_label: string;
    status_tone: string;
    city: string;
    commune: string | null;
    owner: { id: number; name: string; email: string; phone: string | null } | string | null;
    members_count: number;
    profiles_count: number;
    created_at: string | null;
};

type Props = { business: BusinessDetail };

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: '/admin' },
    { title: 'Entreprises', href: '/admin/businesses' },
    { title: props.business.name, href: `/admin/businesses/${props.business.id}` },
];

const tone = (value: string): StatusTone =>
    ['neutral', 'brand', 'success', 'warning', 'danger', 'info'].includes(value) ? (value as StatusTone) : 'neutral';

const formatDate = (value: string | null): string => {
    if (!value) return '—';
    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium' }).format(new Date(value));
};
</script>

<template>
    <Head :title="`Entreprise · ${props.business.name}`" />
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader
                :title="props.business.name"
                :description="`${props.business.city}${props.business.commune ? ' · ' + props.business.commune : ''}`"
            >
                <template #actions>
                    <Button as-child variant="outline">
                        <Link :href="route('admin.businesses.index')">
                            <ArrowLeft class="size-4" aria-hidden="true" />
                            Retour
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardContent class="p-4">
                        <p class="text-xs text-muted-foreground">Statut</p>
                        <div class="mt-1">
                            <StatusBadge :tone="tone(props.business.status_tone)" :label="props.business.status_label" />
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-4">
                        <p class="text-xs text-muted-foreground">Profils</p>
                        <p class="mt-1 text-lg font-semibold text-foreground">{{ props.business.profiles_count }}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-4">
                        <p class="text-xs text-muted-foreground">Équipe</p>
                        <p class="mt-1 text-lg font-semibold text-foreground">{{ props.business.members_count }}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-4">
                        <p class="text-xs text-muted-foreground">Créée le</p>
                        <p class="mt-1 text-sm font-semibold text-foreground">{{ formatDate(props.business.created_at) }}</p>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Informations</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3 text-sm">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <p class="text-xs text-muted-foreground">Nom</p>
                            <p class="font-medium text-foreground">{{ props.business.name }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Slug</p>
                            <p class="font-mono text-foreground">{{ props.business.slug }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Propriétaire</p>
                            <p class="font-medium text-foreground">
                                {{
                                    typeof props.business.owner === 'object' && props.business.owner !== null
                                        ? props.business.owner.name
                                        : (props.business.owner ?? '—')
                                }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Ville</p>
                            <p class="font-medium text-foreground">{{ props.business.city }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
