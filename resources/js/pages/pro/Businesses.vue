<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, StatusTone } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Building2, MapPin, Plus, Users } from 'lucide-vue-next';

type BusinessSummary = {
    id: number;
    name: string;
    slug: string;
    status: string;
    status_label: string;
    status_tone: string;
    city: string;
    commune: string | null;
    members_count: number;
    profiles_count: number;
    created_at: string | null;
};

type Props = {
    businesses: BusinessSummary[];
    canCreate: boolean;
};

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Mon entreprise',
        href: '/pro/businesses',
    },
];

const tone = (value: string): StatusTone => {
    if (value === 'success' || value === 'warning' || value === 'danger') {
        return value;
    }

    return 'neutral';
};
</script>

<template>
    <Head title="Mon entreprise" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader
                eyebrow="Espace professionnel"
                title="Mon entreprise"
                description="Regroupez vos profils professionnels, vos collaborateurs et les services que vous proposez."
            >
                <template #actions>
                    <Button v-if="canCreate" as-child variant="brand">
                        <Link :href="route('pro.businesses.create')">
                            <Plus class="size-4" aria-hidden="true" />
                            Créer une entreprise
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <Card v-if="businesses.length === 0">
                <CardHeader>
                    <CardTitle class="text-base">Aucune entreprise</CardTitle>
                    <CardDescription>
                        Une entreprise permet de gérer plusieurs profils professionnels et de répartir les rôles entre vos collaborateurs.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="rounded-xl border border-dashed border-border bg-muted/30 p-6 text-center">
                        <span
                            class="mx-auto flex size-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-950/60 dark:text-brand-300"
                        >
                            <Building2 class="size-5" aria-hidden="true" />
                        </span>
                        <p class="mt-3 text-sm font-medium text-foreground">Créez votre entreprise</p>
                        <p class="mx-auto mt-1 max-w-md text-sm text-muted-foreground">
                            Ajoutez ensuite vos collaborateurs et distribuez les rôles (responsable, gestionnaire, collaborateur).
                        </p>
                        <Button v-if="canCreate" as-child variant="brand" class="mt-4">
                            <Link :href="route('pro.businesses.create')">Créer mon entreprise</Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <Card v-for="business in businesses" :key="business.id">
                    <CardHeader>
                        <div class="flex items-start justify-between gap-3">
                            <CardTitle class="truncate text-base">{{ business.name }}</CardTitle>
                            <StatusBadge :tone="tone(business.status_tone)" :label="business.status_label" />
                        </div>
                    </CardHeader>

                    <CardContent class="space-y-4">
                        <p class="flex items-center gap-1.5 text-sm text-muted-foreground">
                            <MapPin class="size-3.5" aria-hidden="true" />
                            {{ business.city }}<span v-if="business.commune"> · {{ business.commune }}</span>
                        </p>

                        <div class="flex items-center gap-4 text-xs text-muted-foreground">
                            <span class="inline-flex items-center gap-1.5">
                                <Users class="size-3.5" aria-hidden="true" />
                                {{ business.members_count }} collaborateur{{ business.members_count > 1 ? 's' : '' }}
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <Building2 class="size-3.5" aria-hidden="true" />
                                {{ business.profiles_count }} profil{{ business.profiles_count > 1 ? 's' : '' }}
                            </span>
                        </div>

                        <Button as-child variant="outline" class="w-full">
                            <Link :href="route('pro.businesses.show', business.id)">Gérer l’entreprise</Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
