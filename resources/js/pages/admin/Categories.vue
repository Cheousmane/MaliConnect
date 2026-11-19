<script setup lang="ts">
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import type { BreadcrumbItem, TableColumn } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Filter, FolderTree, LoaderCircle, Search } from 'lucide-vue-next';
import { ref } from 'vue';

type CategoryRow = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    icon: string | null;
    color: string | null;
    is_active: boolean;
    sort_order: number;
    subcategories_count: number;
    created_at: string | null;
};

type Props = {
    categories: {
        data: CategoryRow[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: { search: string; status: string };
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: '/admin' },
    { title: 'Catégories', href: '/admin/categories' },
];

const search = ref(props.filters.search);
const status = ref(props.filters.status);
const filtering = ref(false);

const columns: TableColumn[] = [
    { key: 'name', label: 'Catégorie' },
    { key: 'slug', label: 'Slug', hideOnMobile: true },
    { key: 'subcategories_count', label: 'Sous-catégories', align: 'center', hideOnMobile: true },
    { key: 'is_active', label: 'Actif' },
    { key: 'sort_order', label: 'Ordre', hideOnMobile: true },
    { key: 'actions', label: '', align: 'right' },
];

const applyFilters = () => {
    filtering.value = true;
    router.get(
        route('admin.categories.index'),
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
    if (page < 1 || page > props.categories.last_page) return;
    router.get(
        route('admin.categories.index'),
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
    <Head title="Catégories" />
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader eyebrow="Administration" title="Catégories" description="Gestion du catalogue de catégories et sous-catégories.">
                <template #meta>
                    <p class="mt-2 flex items-center gap-2 text-xs text-muted-foreground">
                        <FolderTree class="size-3.5" aria-hidden="true" />
                        {{ props.categories.total }} catégorie{{ props.categories.total > 1 ? 's' : '' }}
                    </p>
                </template>
            </PageHeader>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Filtrer les catégories</CardTitle>
                    <CardDescription>Recherchez par nom.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_190px_auto]" @submit.prevent="applyFilters">
                        <div class="relative">
                            <Search
                                class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                                aria-hidden="true"
                            />
                            <Input v-model="search" type="search" placeholder="Nom de la catégorie" class="pl-10" aria-label="Rechercher" />
                        </div>
                        <select
                            v-model="status"
                            class="h-11 w-full rounded-lg border border-input bg-background px-3.5 text-sm text-foreground"
                            aria-label="Statut"
                        >
                            <option value="">Tous</option>
                            <option value="1">Actifs</option>
                            <option value="0">Inactifs</option>
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
                    <CardTitle class="text-base">Toutes les catégories</CardTitle>
                    <CardDescription>Les catégories inactives ne sont plus visibles publiquement.</CardDescription>
                </CardHeader>
                <CardContent>
                    <DataTable
                        :columns="columns"
                        :rows="props.categories.data"
                        row-key="id"
                        empty-title="Aucune catégorie"
                        empty-description="Les catégories créées apparaîtront ici."
                        :empty-icon="FolderTree"
                    >
                        <template #cell-name="{ row }">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-foreground">{{ row.name }}</p>
                                <p class="truncate text-xs text-muted-foreground">{{ row.description ?? '—' }}</p>
                            </div>
                        </template>

                        <template #cell-slug="{ row }">
                            <span class="font-mono text-xs text-muted-foreground">{{ row.slug }}</span>
                        </template>

                        <template #cell-subcategories_count="{ row }">
                            <span class="text-sm text-foreground">{{ row.subcategories_count }}</span>
                        </template>

                        <template #cell-is_active="{ row }">
                            <StatusBadge :tone="row.is_active ? 'success' : 'neutral'" :label="row.is_active ? 'Actif' : 'Inactif'" />
                        </template>

                        <template #cell-sort_order="{ row }">
                            <span class="text-xs text-muted-foreground">{{ row.sort_order }}</span>
                        </template>

                        <template #cell-actions="{ row }">
                            <Button
                                variant="ghost"
                                size="sm"
                                @click="
                                    router.patch(
                                        route('admin.categories.activation.update', row.id),
                                        { is_active: !row.is_active },
                                        { preserveScroll: true },
                                    )
                                "
                            >
                                {{ row.is_active ? 'Désactiver' : 'Activer' }}
                            </Button>
                        </template>
                    </DataTable>

                    <div
                        v-if="props.categories.last_page > 1"
                        class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-border/60 pt-4"
                    >
                        <p class="text-xs text-muted-foreground">
                            Page {{ props.categories.current_page }} sur {{ props.categories.last_page }} · {{ props.categories.total }} catégorie{{
                                props.categories.total > 1 ? 's' : ''
                            }}
                        </p>
                        <div class="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="props.categories.current_page <= 1"
                                @click="goToPage(props.categories.current_page - 1)"
                                >Précédent</Button
                            >
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="props.categories.current_page >= props.categories.last_page"
                                @click="goToPage(props.categories.current_page + 1)"
                                >Suivant</Button
                            >
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
