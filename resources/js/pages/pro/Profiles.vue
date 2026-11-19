<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, StatusTone } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Building2, MapPin, Plus, Store } from 'lucide-vue-next';

type ProfileSummary = {
    id: number;
    business_name: string;
    slug: string;
    status: string;
    status_label: string;
    status_tone: string;
    is_active: boolean;
    is_publicly_visible: boolean;
    city: string;
    commune: string | null;
    category: string | null;
    business: string | null;
    rating_average: number;
    rating_count: number;
    created_at: string | null;
};

type Props = {
    profiles: {
        data: ProfileSummary[];
        current_page: number;
        last_page: number;
        total: number;
    };
    statuses: Array<{ value: string; label: string; tone: string }>;
};

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Mes profils',
        href: '/pro/profiles',
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
    <Head title="Mes profils professionnels" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader
                eyebrow="Espace professionnel"
                title="Mes profils"
                description="Créez et pilotez vos profils professionnels. Un profil doit être vérifié par l’administration avant d’être présenté au public comme vérifié."
            >
                <template #actions>
                    <Button as-child variant="brand">
                        <Link :href="route('pro.profiles.create')">
                            <Plus class="size-4" aria-hidden="true" />
                            Créer un profil
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <Card v-if="profiles.total === 0">
                <CardHeader>
                    <CardTitle class="text-base">Aucun profil pour le moment</CardTitle>
                    <CardDescription>
                        Votre profil présente votre nom commercial, vos coordonnées, vos zones d’intervention et vos horaires.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="rounded-xl border border-dashed border-border bg-muted/30 p-6 text-center">
                        <span
                            class="mx-auto flex size-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-950/60 dark:text-brand-300"
                        >
                            <Store class="size-5" aria-hidden="true" />
                        </span>
                        <p class="mt-3 text-sm font-medium text-foreground">Créez votre premier profil professionnel</p>
                        <p class="mx-auto mt-1 max-w-md text-sm text-muted-foreground">
                            Il sera soumis à la vérification administrative avant toute mise en avant.
                        </p>
                        <Button as-child variant="brand" class="mt-4">
                            <Link :href="route('pro.profiles.create')">Créer mon profil</Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <Card v-for="profile in profiles.data" :key="profile.id" class="flex flex-col">
                    <CardHeader>
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <CardTitle class="truncate text-base">{{ profile.business_name }}</CardTitle>
                                <CardDescription class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <span v-if="profile.category" class="inline-flex items-center gap-1">
                                        <Store class="size-3.5" aria-hidden="true" />
                                        {{ profile.category }}
                                    </span>
                                    <span v-if="profile.business" class="inline-flex items-center gap-1">
                                        <Building2 class="size-3.5" aria-hidden="true" />
                                        {{ profile.business }}
                                    </span>
                                </CardDescription>
                            </div>
                            <StatusBadge :tone="tone(profile.status_tone)" :label="profile.status_label" />
                        </div>
                    </CardHeader>

                    <CardContent class="flex flex-1 flex-col justify-between gap-4">
                        <div>
                            <p class="flex items-center gap-1.5 text-sm text-muted-foreground">
                                <MapPin class="size-3.5" aria-hidden="true" />
                                {{ profile.city }}<span v-if="profile.commune"> · {{ profile.commune }}</span>
                            </p>
                            <p v-if="!profile.is_active" class="mt-2 text-xs font-medium text-destructive">
                                Profil désactivé : il n’est plus visible par les clients.
                            </p>
                            <p v-else-if="!profile.is_publicly_visible" class="mt-2 text-xs text-muted-foreground">
                                Ce profil n’est pas encore présenté comme vérifié.
                            </p>
                        </div>

                        <Button as-child variant="outline" class="w-full">
                            <Link :href="route('pro.profiles.show', profile.id)">Gérer le profil</Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
