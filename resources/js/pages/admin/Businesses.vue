<script setup lang="ts">
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import type { BreadcrumbItem, StatusTone, TableColumn } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Building2, Filter, LoaderCircle, Search } from 'lucide-vue-next';
import { ref } from 'vue';

type BusinessRow = {
    id: number;
    name: string;
    slug: string;
    status: string;
    status_label: string;
    status_tone: string;
    city: string;
    commune: string | null;
    owner: string | null;
    members_count: number;
    profiles_count: number;
    created_at: string | null;
};

type Props = {
    businesses: {
        data: BusinessRow[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: { search: string; status: string };
    statuses: Array<{ value: string; label: string }>;
    pendingCount: number;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: '/admin' },
    { title: 'Entreprises', href: '/admin/businesses' },
];

const search = ref(props.filters.search);
const status = ref(props.filters.status);
const filtering = ref(false);

const columns: TableColumn[] = [
    { key: 'name', label: 'Entreprise' },
    { key: 'owner', label: 'Propriétaire', hideOnMobile: true },
    { key: 'profiles_count', label: 'Profils', align: 'center' },
    { key: 'members_count', label: 'Équipe', align: 'center', hideOnMobile: true },
    { key: 'status_label', label: 'Statut' },
    { key: 'created_at', label: 'Créée le', hideOnMobile: true },
    { key: 'actions', label: '', align: 'right' },
];

const tone = (value: string): StatusTone => {
    return ['neutral', 'brand', 'success', 'warning', 'danger', 'info'].includes(value) ? (value as StatusTone) : 'neutral';
};

const formatDate = (value: string | null): string => {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium' }).format(new Date(value));
};

const applyFilters = () => {
    filtering.value = true;

    router.get(
        route('admin.businesses.index'),
        {
            search: search.value || undefined,
            status: status.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => {
                filtering.value = false;
            },
        },
    );
};

const resetFilters = () => {
    search.value = '';
    status.value = '';
    applyFilters();
};

const goToPage = (page: number) => {
    if (page < 1 || page > props.businesses.last_page) {
        return;
    }

    router.get(
        route('admin.businesses.index'),
        {
            page,
            search: search.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
};
</script>

<template>
    <Head title="Entreprises" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader
                eyebrow="Administration"
                title="Entreprises"
                description="Consultez les structures enregistrées, leurs équipes et les profils professionnels qu’elles portent."
            >
                <template #meta>
                    <p class="mt-2 flex items-center gap-2 text-xs text-muted-foreground">
                        <Building2 class="size-3.5" aria-hidden="true" />
                        {{ props.businesses.total }} entreprise{{ props.businesses.total > 1 ? 's' : '' }}
                        <span v-if="props.pendingCount" class="font-medium text-amber-700 dark:text-amber-300">
                            · {{ props.pendingCount }} en attente de vérification
                        </span>
                    </p>
                </template>
            </PageHeader>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Filtrer les entreprises</CardTitle>
                    <CardDescription>Recherchez par nom, raison sociale, e-mail, téléphone ou ville.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_220px_auto]" @submit.prevent="applyFilters">
                        <div class="relative">
                            <Search
                                class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                                aria-hidden="true"
                            />
                            <Input
                                v-model="search"
                                type="search"
                                placeholder="Nom, e-mail, téléphone, ville"
                                class="pl-10"
                                aria-label="Rechercher une entreprise"
                            />
                        </div>

                        <select
                            v-model="status"
                            class="h-11 w-full rounded-lg border border-input bg-background px-3.5 text-sm text-foreground"
                            aria-label="Filtrer par statut"
                        >
                            <option value="">Tous les statuts</option>
                            <option v-for="item in props.statuses" :key="item.value" :value="item.value">
                                {{ item.label }}
                            </option>
                        </select>

                        <div class="flex items-center gap-2">
                            <Button type="submit" variant="brand" :disabled="filtering">
                                <LoaderCircle v-if="filtering" class="size-4 animate-spin" aria-hidden="true" />
                                <Filter v-else class="size-4" aria-hidden="true" />
                                Filtrer
                            </Button>
                            <Button type="button" variant="ghost" @click="resetFilters">Réinitialiser</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Toutes les entreprises</CardTitle>
                    <CardDescription>Les entreprises suspendues ne peuvent plus publier de nouveaux profils.</CardDescription>
                </CardHeader>
                <CardContent>
                    <DataTable
                        :columns="columns"
                        :rows="props.businesses.data"
                        row-key="id"
                        empty-title="Aucune entreprise"
                        empty-description="Les entreprises créées par les professionnels apparaîtront ici."
                        :empty-icon="Building2"
                    >
                        <template #cell-name="{ row }">
                            <div class="flex min-w-0 items-center gap-3">
                                <span
                                    class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-100 dark:bg-brand-950/60 dark:text-brand-300 dark:ring-brand-900"
                                >
                                    <Building2 class="size-4" aria-hidden="true" />
                                </span>
                                <div class="min-w-0">
                                    <Link
                                        :href="route('admin.businesses.show', row.id)"
                                        class="block truncate text-sm font-medium text-foreground hover:text-brand-700 dark:hover:text-brand-300"
                                    >
                                        {{ row.name }}
                                    </Link>
                                    <p class="truncate text-xs text-muted-foreground">
                                        {{ row.city }}<span v-if="row.commune"> · {{ row.commune }}</span>
                                    </p>
                                </div>
                            </div>
                        </template>

                        <template #cell-status_label="{ row }">
                            <StatusBadge :tone="tone(row.status_tone)" :label="row.status_label" />
                        </template>

                        <template #cell-created_at="{ row }">
                            <span class="text-xs text-muted-foreground">{{ formatDate(row.created_at) }}</span>
                        </template>

                        <template #cell-actions="{ row }">
                            <Button as-child variant="ghost" size="sm">
                                <Link :href="route('admin.businesses.show', row.id)">Gérer</Link>
                            </Button>
                        </template>
                    </DataTable>

                    <div
                        v-if="props.businesses.last_page > 1"
                        class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-border/60 pt-4"
                    >
                        <p class="text-xs text-muted-foreground">
                            Page {{ props.businesses.current_page }} sur {{ props.businesses.last_page }} · {{ props.businesses.total }} entreprise{{
                                props.businesses.total > 1 ? 's' : ''
                            }}
                        </p>

                        <div class="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="props.businesses.current_page <= 1"
                                @click="goToPage(props.businesses.current_page - 1)"
                            >
                                Précédent
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="props.businesses.current_page >= props.businesses.last_page"
                                @click="goToPage(props.businesses.current_page + 1)"
                            >
                                Suivant
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
