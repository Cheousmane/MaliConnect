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
import { ClipboardList, Filter, LoaderCircle, Search } from 'lucide-vue-next';
import { ref } from 'vue';

type RequestRow = {
    id: number;
    reference: string;
    title: string;
    customer: string | null;
    professional: string | null;
    status: string;
    status_label: string;
    status_tone: string;
    created_at: string | null;
};

type Props = {
    requests: {
        data: RequestRow[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: { search: string; status: string };
    statuses: Array<{ value: string; label: string }>;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: '/admin' },
    { title: 'Demandes', href: '/admin/demandes' },
];

const search = ref(props.filters.search);
const status = ref(props.filters.status);
const filtering = ref(false);

const columns: TableColumn[] = [
    { key: 'reference', label: 'Référence' },
    { key: 'title', label: 'Titre' },
    { key: 'customer', label: 'Client', hideOnMobile: true },
    { key: 'professional', label: 'Professionnel', hideOnMobile: true },
    { key: 'status_label', label: 'Statut' },
    { key: 'created_at', label: 'Créée le', hideOnMobile: true },
    { key: 'actions', label: '', align: 'right' },
];

const tone = (value: string): StatusTone =>
    ['neutral', 'brand', 'success', 'warning', 'danger', 'info'].includes(value) ? (value as StatusTone) : 'neutral';

const formatDate = (value: string | null): string => {
    if (!value) return '—';
    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium' }).format(new Date(value));
};

const applyFilters = () => {
    filtering.value = true;
    router.get(
        route('admin.service-requests.index'),
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
    if (page < 1 || page > props.requests.last_page) return;
    router.get(
        route('admin.service-requests.index'),
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
    <Head title="Demandes de devis" />
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader
                eyebrow="Administration"
                title="Demandes de devis"
                description="Supervision de toutes les demandes de devis de la plateforme."
            >
                <template #meta>
                    <p class="mt-2 flex items-center gap-2 text-xs text-muted-foreground">
                        <ClipboardList class="size-3.5" aria-hidden="true" />
                        {{ props.requests.total }} demande{{ props.requests.total > 1 ? 's' : '' }}
                    </p>
                </template>
            </PageHeader>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Filtrer les demandes</CardTitle>
                    <CardDescription>Recherchez par référence, titre, client ou professionnel.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_190px_auto]" @submit.prevent="applyFilters">
                        <div class="relative">
                            <Search
                                class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                                aria-hidden="true"
                            />
                            <Input
                                v-model="search"
                                type="search"
                                placeholder="Référence, titre, client, professionnel"
                                class="pl-10"
                                aria-label="Rechercher"
                            />
                        </div>
                        <select
                            v-model="status"
                            class="h-11 w-full rounded-lg border border-input bg-background px-3.5 text-sm text-foreground"
                            aria-label="Statut"
                        >
                            <option value="">Tous les statuts</option>
                            <option v-for="item in props.statuses" :key="item.value" :value="item.value">{{ item.label }}</option>
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
                    <CardTitle class="text-base">Toutes les demandes</CardTitle>
                    <CardDescription>Les demandes sont adressées par les clients aux professionnels.</CardDescription>
                </CardHeader>
                <CardContent>
                    <DataTable
                        :columns="columns"
                        :rows="props.requests.data"
                        row-key="id"
                        empty-title="Aucune demande"
                        empty-description="Les demandes de devis apparaîtront ici."
                        :empty-icon="ClipboardList"
                    >
                        <template #cell-reference="{ row }">
                            <Link
                                :href="route('admin.service-requests.show', row.id)"
                                class="block truncate text-sm font-medium text-foreground hover:text-brand-700 dark:hover:text-brand-300"
                            >
                                {{ row.reference }}
                            </Link>
                        </template>

                        <template #cell-title="{ row }">
                            <span class="truncate text-sm text-foreground">{{ row.title }}</span>
                        </template>

                        <template #cell-customer="{ row }">
                            <span class="text-xs text-muted-foreground">{{ row.customer ?? '—' }}</span>
                        </template>

                        <template #cell-professional="{ row }">
                            <span class="text-xs text-muted-foreground">{{ row.professional ?? '—' }}</span>
                        </template>

                        <template #cell-status_label="{ row }">
                            <StatusBadge :tone="tone(row.status_tone)" :label="row.status_label" />
                        </template>

                        <template #cell-created_at="{ row }">
                            <span class="text-xs text-muted-foreground">{{ formatDate(row.created_at) }}</span>
                        </template>

                        <template #cell-actions="{ row }">
                            <Button as-child variant="ghost" size="sm">
                                <Link :href="route('admin.service-requests.show', row.id)">Gérer</Link>
                            </Button>
                        </template>
                    </DataTable>

                    <div
                        v-if="props.requests.last_page > 1"
                        class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-border/60 pt-4"
                    >
                        <p class="text-xs text-muted-foreground">
                            Page {{ props.requests.current_page }} sur {{ props.requests.last_page }} · {{ props.requests.total }} demande{{
                                props.requests.total > 1 ? 's' : ''
                            }}
                        </p>
                        <div class="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="props.requests.current_page <= 1"
                                @click="goToPage(props.requests.current_page - 1)"
                                >Précédent</Button
                            >
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="props.requests.current_page >= props.requests.last_page"
                                @click="goToPage(props.requests.current_page + 1)"
                                >Suivant</Button
                            >
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
