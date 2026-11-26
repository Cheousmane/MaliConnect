<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, StatusTone } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Building2, ExternalLink, Filter, MapPin, Pencil, Plus, Star, Store } from 'lucide-vue-next';
import { ref } from 'vue';

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
    filters?: { status: string };
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Mes profils', href: '/pro/profiles' }];
const status = ref(props.filters?.status ?? '');

const tone = (value: string): StatusTone => (['success', 'warning', 'danger', 'info', 'brand', 'neutral'].includes(value) ? (value as StatusTone) : 'neutral');

const applyFilter = () => {
    router.get(route('pro.profiles.index'), { status: status.value || undefined }, { preserveState: true, preserveScroll: true });
};

const pageUrl = (page: number) => route('pro.profiles.index', { status: status.value || undefined, page });
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

            <Card v-if="profiles.total > 0 || status">
                <CardContent class="flex flex-col gap-3 p-4 sm:flex-row sm:items-end">
                    <div class="min-w-[220px]">
                        <label for="profile-status" class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.1em] text-muted-foreground">Statut</label>
                        <select id="profile-status" v-model="status" class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm text-foreground">
                            <option value="">Tous les statuts</option>
                            <option v-for="item in statuses" :key="item.value" :value="item.value">{{ item.label }}</option>
                        </select>
                    </div>
                    <Button variant="outline" @click="applyFilter">
                        <Filter class="size-4" aria-hidden="true" />
                        Filtrer
                    </Button>
                    <p class="text-sm text-muted-foreground sm:ml-auto">{{ profiles.total }} profil{{ profiles.total > 1 ? 's' : '' }}</p>
                </CardContent>
            </Card>

            <Card v-if="profiles.total === 0">
                <CardHeader>
                    <CardTitle class="text-base">{{ status ? 'Aucun profil pour ce filtre' : 'Aucun profil pour le moment' }}</CardTitle>
                    <CardDescription>
                        {{ status ? 'Essayez un autre statut ou réinitialisez le filtre.' : 'Votre profil présente votre nom commercial, vos coordonnées, vos zones d’intervention et vos horaires.' }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="rounded-xl border border-dashed border-border bg-muted/30 p-6 text-center">
                        <span class="mx-auto flex size-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-950/60 dark:text-brand-300">
                            <Store class="size-5" aria-hidden="true" />
                        </span>
                        <p class="mt-3 text-sm font-medium text-foreground">{{ status ? 'Aucun résultat' : 'Créez votre premier profil professionnel' }}</p>
                        <p class="mx-auto mt-1 max-w-md text-sm text-muted-foreground">
                            {{ status ? 'Modifiez le filtre pour afficher vos profils.' : 'Il sera soumis à la vérification administrative avant toute mise en avant.' }}
                        </p>
                        <Button as-child variant="brand" class="mt-4">
                            <Link :href="status ? route('pro.profiles.index') : route('pro.profiles.create')">{{ status ? 'Réinitialiser' : 'Créer mon profil' }}</Link>
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
                                    <span v-if="profile.category" class="inline-flex items-center gap-1"><Store class="size-3.5" aria-hidden="true" />{{ profile.category }}</span>
                                    <span v-if="profile.business" class="inline-flex items-center gap-1"><Building2 class="size-3.5" aria-hidden="true" />{{ profile.business }}</span>
                                </CardDescription>
                            </div>
                            <StatusBadge :tone="tone(profile.status_tone)" :label="profile.status_label" />
                        </div>
                    </CardHeader>

                    <CardContent class="flex flex-1 flex-col justify-between gap-4">
                        <div class="space-y-2">
                            <p class="flex items-center gap-1.5 text-sm text-muted-foreground">
                                <MapPin class="size-3.5" aria-hidden="true" />
                                {{ profile.city }}<span v-if="profile.commune"> · {{ profile.commune }}</span>
                            </p>
                            <p v-if="profile.rating_count > 0" class="flex items-center gap-1.5 text-sm text-muted-foreground">
                                <Star class="size-3.5 fill-gold-400 text-gold-500" aria-hidden="true" />
                                {{ profile.rating_average.toFixed(1) }} ({{ profile.rating_count }} avis)
                            </p>
                            <p v-if="!profile.is_active" class="text-xs font-medium text-destructive">Profil désactivé : il n’est plus visible par les clients.</p>
                            <p v-else-if="!profile.is_publicly_visible" class="text-xs text-muted-foreground">Ce profil n’est pas encore présenté comme vérifié.</p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <Button as-child variant="outline" size="sm" class="flex-1">
                                <Link :href="route('pro.profiles.show', profile.id)">Gérer</Link>
                            </Button>
                            <Button as-child variant="ghost" size="sm">
                                <Link :href="route('pro.profiles.edit', profile.id)"><Pencil class="size-4" aria-hidden="true" /><span class="sr-only">Modifier</span></Link>
                            </Button>
                            <Button v-if="profile.is_publicly_visible" as-child variant="ghost" size="sm">
                                <a :href="route('professionals.show', profile.slug)" target="_blank" rel="noopener noreferrer"><ExternalLink class="size-4" aria-hidden="true" /><span class="sr-only">Voir public</span></a>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <nav v-if="profiles.last_page > 1" class="flex items-center justify-between gap-3" aria-label="Pagination des profils">
                <p class="text-xs text-muted-foreground">Page {{ profiles.current_page }} sur {{ profiles.last_page }}</p>
                <div class="flex gap-2">
                    <Button as-child variant="outline" size="sm" :class="profiles.current_page <= 1 ? 'pointer-events-none opacity-50' : ''">
                        <Link :href="pageUrl(profiles.current_page - 1)">Précédent</Link>
                    </Button>
                    <Button as-child variant="outline" size="sm" :class="profiles.current_page >= profiles.last_page ? 'pointer-events-none opacity-50' : ''">
                        <Link :href="pageUrl(profiles.current_page + 1)">Suivant</Link>
                    </Button>
                </div>
            </nav>
        </div>
    </AppLayout>
</template>
