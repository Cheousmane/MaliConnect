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
import { ClipboardCheck, Filter, LoaderCircle, Search } from 'lucide-vue-next';
import { ref } from 'vue';

type ProfileRow = {
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
    owner: string | null;
    business: string | null;
    created_at: string | null;
};

type Props = {
    profiles: {
        data: ProfileRow[];
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
    { title: 'Profils à vérifier', href: '/admin/provider-profiles' },
];

const search = ref(props.filters.search);
const status = ref(props.filters.status);
const filtering = ref(false);

const columns: TableColumn[] = [
    { key: 'business_name', label: 'Profil' },
    { key: 'category', label: 'Catégorie', hideOnMobile: true },
    { key: 'city', label: 'Ville', hideOnMobile: true },
    { key: 'owner', label: 'Compte', hideOnMobile: true },
    { key: 'status_label', label: 'Statut' },
    { key: 'actions', label: '', align: 'right' },
];

const tone = (value: string): StatusTone =>
    ['neutral', 'brand', 'success', 'warning', 'danger', 'info'].includes(value) ? (value as StatusTone) : 'neutral';

const applyFilters = () => {
    filtering.value = true;
    router.get(
        route('admin.provider-profiles.index'),
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
    if (page < 1 || page > props.profiles.last_page) return;
    router.get(
        route('admin.provider-profiles.index'),
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
    <Head title="Profils à vérifier" />
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader eyebrow="Administration" title="Profils à vérifier" description="Vérification administrative des profils professionnels.">
                <template #meta>
                    <p class="mt-2 flex items-center gap-2 text-xs text-muted-foreground">
                        <ClipboardCheck class="size-3.5" aria-hidden="true" />
                        {{ props.profiles.total }} profil{{ props.profiles.total > 1 ? 's' : '' }}
                        <span v-if="props.pendingCount" class="font-medium text-amber-700 dark:text-amber-300">
                            · {{ props.pendingCount }} en attente
                        </span>
                    </p>
                </template>
            </PageHeader>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Filtrer les profils</CardTitle>
                    <CardDescription>Recherchez par nom, catégorie, ville ou compte.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_190px_auto]" @submit.prevent="applyFilters">
                        <div class="relative">
                            <Search
                                class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                                aria-hidden="true"
                            />
                            <Input v-model="search" type="search" placeholder="Nom, catégorie, ville, compte" class="pl-10" aria-label="Rechercher" />
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
                    <CardTitle class="text-base">Tous les profils</CardTitle>
                    <CardDescription>Les profils en attente nécessitent une vérification administrative.</CardDescription>
                </CardHeader>
                <CardContent>
                    <DataTable
                        :columns="columns"
                        :rows="props.profiles.data"
                        row-key="id"
                        empty-title="Aucun profil"
                        empty-description="Les profils créés apparaîtront ici."
                        :empty-icon="ClipboardCheck"
                    >
                        <template #cell-business_name="{ row }">
                            <div class="min-w-0">
                                <Link
                                    :href="route('admin.provider-profiles.show', row.id)"
                                    class="block truncate text-sm font-medium text-foreground hover:text-brand-700 dark:hover:text-brand-300"
                                >
                                    {{ row.business_name }}
                                </Link>
                                <p class="truncate text-xs text-muted-foreground">{{ row.owner ?? '—' }}</p>
                            </div>
                        </template>

                        <template #cell-category="{ row }">
                            <span class="text-xs text-muted-foreground">{{ row.category ?? '—' }}</span>
                        </template>

                        <template #cell-city="{ row }">
                            <span class="text-xs text-muted-foreground"
                                >{{ row.city }}<span v-if="row.commune"> · {{ row.commune }}</span></span
                            >
                        </template>

                        <template #cell-owner="{ row }">
                            <span class="text-xs text-muted-foreground">{{ row.owner ?? '—' }}</span>
                        </template>

                        <template #cell-status_label="{ row }">
                            <StatusBadge :tone="tone(row.status_tone)" :label="row.status_label" />
                        </template>

                        <template #cell-actions="{ row }">
                            <Button as-child variant="ghost" size="sm">
                                <Link :href="route('admin.provider-profiles.show', row.id)">Vérifier</Link>
                            </Button>
                        </template>
                    </DataTable>

                    <div
                        v-if="props.profiles.last_page > 1"
                        class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-border/60 pt-4"
                    >
                        <p class="text-xs text-muted-foreground">
                            Page {{ props.profiles.current_page }} sur {{ props.profiles.last_page }} · {{ props.profiles.total }} profil{{
                                props.profiles.total > 1 ? 's' : ''
                            }}
                        </p>
                        <div class="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="props.profiles.current_page <= 1"
                                @click="goToPage(props.profiles.current_page - 1)"
                                >Précédent</Button
                            >
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="props.profiles.current_page >= props.profiles.last_page"
                                @click="goToPage(props.profiles.current_page + 1)"
                                >Suivant</Button
                            >
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
