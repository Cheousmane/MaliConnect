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
import { Filter, LifeBuoy, LoaderCircle, Search } from 'lucide-vue-next';
import { ref } from 'vue';

type ReportRow = {
    id: number;
    reference: string;
    subject: string;
    status: string;
    status_label: string;
    status_tone: string;
    reporter: string | null;
    target: { type: string; label: string; meta: string; url: string } | null;
    created_at: string | null;
};

type Props = {
    reports: {
        data: ReportRow[];
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
    { title: 'Signalements', href: '/admin/signalements' },
];

const search = ref(props.filters.search);
const status = ref(props.filters.status);
const filtering = ref(false);

const columns: TableColumn[] = [
    { key: 'reference', label: 'Référence' },
    { key: 'subject', label: 'Sujet' },
    { key: 'reporter', label: 'Signalant', hideOnMobile: true },
    { key: 'target', label: 'Cible', hideOnMobile: true },
    { key: 'status_label', label: 'Statut' },
    { key: 'created_at', label: 'Date', hideOnMobile: true },
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
        route('admin.reports.index'),
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
    if (page < 1 || page > props.reports.last_page) return;
    router.get(
        route('admin.reports.index'),
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
    <Head title="Signalements" />
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader eyebrow="Administration" title="Signalements" description="Gestion des signalements déposés par les utilisateurs.">
                <template #meta>
                    <p class="mt-2 flex items-center gap-2 text-xs text-muted-foreground">
                        <LifeBuoy class="size-3.5" aria-hidden="true" />
                        {{ props.reports.total }} signalement{{ props.reports.total > 1 ? 's' : '' }}
                    </p>
                </template>
            </PageHeader>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Filtrer les signalements</CardTitle>
                    <CardDescription>Recherchez par référence, sujet, signalant ou professionnel.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_190px_auto]" @submit.prevent="applyFilters">
                        <div class="relative">
                            <Search
                                class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                                aria-hidden="true"
                            />
                            <Input v-model="search" type="search" placeholder="Référence, sujet, signalant" class="pl-10" aria-label="Rechercher" />
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
                    <CardTitle class="text-base">Tous les signalements</CardTitle>
                    <CardDescription>Les signalements sont déposés par les utilisateurs.</CardDescription>
                </CardHeader>
                <CardContent>
                    <DataTable
                        :columns="columns"
                        :rows="props.reports.data"
                        row-key="id"
                        empty-title="Aucun signalement"
                        empty-description="Les signalements déposés apparaîtront ici."
                        :empty-icon="LifeBuoy"
                    >
                        <template #cell-reference="{ row }">
                            <Link
                                :href="route('admin.reports.show', row.id)"
                                class="block truncate text-sm font-medium text-foreground hover:text-brand-700 dark:hover:text-brand-300"
                            >
                                {{ row.reference }}
                            </Link>
                        </template>

                        <template #cell-subject="{ row }">
                            <span class="truncate text-sm text-foreground">{{ row.subject }}</span>
                        </template>

                        <template #cell-reporter="{ row }">
                            <span class="text-xs text-muted-foreground">{{ row.reporter ?? '—' }}</span>
                        </template>

                        <template #cell-target="{ row }">
                            <span class="text-xs text-muted-foreground">{{ row.target?.label ?? '—' }}</span>
                        </template>

                        <template #cell-status_label="{ row }">
                            <StatusBadge :tone="tone(row.status_tone)" :label="row.status_label" />
                        </template>

                        <template #cell-created_at="{ row }">
                            <span class="text-xs text-muted-foreground">{{ formatDate(row.created_at) }}</span>
                        </template>

                        <template #cell-actions="{ row }">
                            <Button as-child variant="ghost" size="sm">
                                <Link :href="route('admin.reports.show', row.id)">Gérer</Link>
                            </Button>
                        </template>
                    </DataTable>

                    <div
                        v-if="props.reports.last_page > 1"
                        class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-border/60 pt-4"
                    >
                        <p class="text-xs text-muted-foreground">
                            Page {{ props.reports.current_page }} sur {{ props.reports.last_page }} · {{ props.reports.total }} signalement{{
                                props.reports.total > 1 ? 's' : ''
                            }}
                        </p>
                        <div class="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="props.reports.current_page <= 1"
                                @click="goToPage(props.reports.current_page - 1)"
                                >Précédent</Button
                            >
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="props.reports.current_page >= props.reports.last_page"
                                @click="goToPage(props.reports.current_page + 1)"
                                >Suivant</Button
                            >
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
