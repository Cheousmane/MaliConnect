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
import { Filter, LoaderCircle, Search, Store } from 'lucide-vue-next';
import { ref } from 'vue';

type ServiceRow = {
    id: number;
    name: string;
    description: string | null;
    price_type: string;
    formatted_price: string;
    duration_minutes: number | null;
    is_active: boolean;
    professional: string | null;
    category: string | null;
    created_at: string | null;
};

type Props = {
    services: {
        data: ServiceRow[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: { search: string; active: string };
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: '/admin' },
    { title: 'Services', href: '/admin/services' },
];

const search = ref(props.filters.search);
const active = ref(props.filters.active);
const filtering = ref(false);

const columns: TableColumn[] = [
    { key: 'name', label: 'Service' },
    { key: 'professional', label: 'Professionnel', hideOnMobile: true },
    { key: 'category', label: 'Catégorie', hideOnMobile: true },
    { key: 'formatted_price', label: 'Prix' },
    { key: 'is_active', label: 'Actif' },
    { key: 'actions', label: '', align: 'right' },
];

const applyFilters = () => {
    filtering.value = true;
    router.get(
        route('admin.services.index'),
        {
            search: search.value || undefined,
            active: active.value || undefined,
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
    active.value = '';
    applyFilters();
};

const goToPage = (page: number) => {
    if (page < 1 || page > props.services.last_page) return;
    router.get(
        route('admin.services.index'),
        {
            page,
            search: search.value || undefined,
            active: active.value || undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
};

const toggleActivation = (service: any) => {
    router.patch(
        route('admin.services.activation.update', service.id),
        {
            is_active: !service.is_active,
        },
        { preserveScroll: true },
    );
};
</script>

<template>
    <Head title="Services" />
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader eyebrow="Administration" title="Services" description="Supervision des services publiés par les professionnels.">
                <template #meta>
                    <p class="mt-2 flex items-center gap-2 text-xs text-muted-foreground">
                        <Store class="size-3.5" aria-hidden="true" />
                        {{ props.services.total }} service{{ props.services.total > 1 ? 's' : '' }}
                    </p>
                </template>
            </PageHeader>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Filtrer les services</CardTitle>
                    <CardDescription>Recherchez par nom ou professionnel.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_190px_auto]" @submit.prevent="applyFilters">
                        <div class="relative">
                            <Search
                                class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                                aria-hidden="true"
                            />
                            <Input v-model="search" type="search" placeholder="Nom du service, professionnel" class="pl-10" aria-label="Rechercher" />
                        </div>
                        <select
                            v-model="active"
                            class="h-11 w-full rounded-lg border border-input bg-background px-3.5 text-sm text-foreground"
                            aria-label="Actif"
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
                    <CardTitle class="text-base">Tous les services</CardTitle>
                    <CardDescription>Les services inactifs ne sont plus visibles publiquement.</CardDescription>
                </CardHeader>
                <CardContent>
                    <DataTable
                        :columns="columns"
                        :rows="props.services.data"
                        row-key="id"
                        empty-title="Aucun service"
                        empty-description="Les services publiés apparaîtront ici."
                        :empty-icon="Store"
                    >
                        <template #cell-name="{ row }">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-foreground">{{ row.name }}</p>
                                <p class="truncate text-xs text-muted-foreground">{{ row.description ?? '—' }}</p>
                            </div>
                        </template>

                        <template #cell-professional="{ row }">
                            <span class="text-xs text-muted-foreground">{{ row.professional ?? '—' }}</span>
                        </template>

                        <template #cell-category="{ row }">
                            <span class="text-xs text-muted-foreground">{{ row.category ?? '—' }}</span>
                        </template>

                        <template #cell-formatted_price="{ row }">
                            <span class="text-sm font-medium text-foreground">{{ row.formatted_price }}</span>
                        </template>

                        <template #cell-is_active="{ row }">
                            <StatusBadge :tone="row.is_active ? 'success' : 'neutral'" :label="row.is_active ? 'Actif' : 'Inactif'" />
                        </template>

                        <template #cell-actions="{ row }">
                            <Button variant="ghost" size="sm" @click="toggleActivation(row)">
                                {{ row.is_active ? 'Désactiver' : 'Activer' }}
                            </Button>
                        </template>
                    </DataTable>

                    <div
                        v-if="props.services.last_page > 1"
                        class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-border/60 pt-4"
                    >
                        <p class="text-xs text-muted-foreground">
                            Page {{ props.services.current_page }} sur {{ props.services.last_page }} · {{ props.services.total }} service{{
                                props.services.total > 1 ? 's' : ''
                            }}
                        </p>
                        <div class="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="props.services.current_page <= 1"
                                @click="goToPage(props.services.current_page - 1)"
                                >Précédent</Button
                            >
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="props.services.current_page >= props.services.last_page"
                                @click="goToPage(props.services.current_page + 1)"
                                >Suivant</Button
                            >
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
