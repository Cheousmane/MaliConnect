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
import { CalendarClock, Filter, LoaderCircle, Search } from 'lucide-vue-next';
import { ref } from 'vue';

type BookingRow = {
    id: number;
    reference: string;
    customer: string | null;
    professional: string | null;
    starts_at: string | null;
    amount: number;
    status: string;
    status_label: string;
    status_tone: string;
    created_at: string | null;
};

type Props = {
    bookings: {
        data: BookingRow[];
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
    { title: 'Réservations', href: '/admin/reservations' },
];

const search = ref(props.filters.search);
const status = ref(props.filters.status);
const filtering = ref(false);

const columns: TableColumn[] = [
    { key: 'reference', label: 'Référence' },
    { key: 'customer', label: 'Client' },
    { key: 'professional', label: 'Professionnel', hideOnMobile: true },
    { key: 'starts_at', label: 'Date', hideOnMobile: true },
    { key: 'amount', label: 'Montant', align: 'right' },
    { key: 'status_label', label: 'Statut' },
    { key: 'actions', label: '', align: 'right' },
];

const tone = (value: string): StatusTone =>
    ['neutral', 'brand', 'success', 'warning', 'danger', 'info'].includes(value) ? (value as StatusTone) : 'neutral';

const formatDate = (value: string | null): string => {
    if (!value) return '—';
    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
};

const formatMoney = (value: number): string => `${new Intl.NumberFormat('fr-FR').format(value)} FCFA`;

const applyFilters = () => {
    filtering.value = true;
    router.get(
        route('admin.bookings.index'),
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
    if (page < 1 || page > props.bookings.last_page) return;
    router.get(
        route('admin.bookings.index'),
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
    <Head title="Réservations" />
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader eyebrow="Administration" title="Réservations" description="Supervision de toutes les réservations de la plateforme.">
                <template #meta>
                    <p class="mt-2 flex items-center gap-2 text-xs text-muted-foreground">
                        <CalendarClock class="size-3.5" aria-hidden="true" />
                        {{ props.bookings.total }} réservation{{ props.bookings.total > 1 ? 's' : '' }}
                    </p>
                </template>
            </PageHeader>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Filtrer les réservations</CardTitle>
                    <CardDescription>Recherchez par référence, client ou professionnel.</CardDescription>
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
                                placeholder="Référence, client, professionnel"
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
                    <CardTitle class="text-base">Toutes les réservations</CardTitle>
                    <CardDescription>Les réservations sont créées par les clients auprès des professionnels.</CardDescription>
                </CardHeader>
                <CardContent>
                    <DataTable
                        :columns="columns"
                        :rows="props.bookings.data"
                        row-key="id"
                        empty-title="Aucune réservation"
                        empty-description="Les réservations apparaîtront ici."
                        :empty-icon="CalendarClock"
                    >
                        <template #cell-reference="{ row }">
                            <Link
                                :href="route('admin.bookings.show', row.id)"
                                class="block truncate text-sm font-medium text-foreground hover:text-brand-700 dark:hover:text-brand-300"
                            >
                                {{ row.reference }}
                            </Link>
                        </template>

                        <template #cell-customer="{ row }">
                            <span class="text-sm text-foreground">{{ row.customer ?? '—' }}</span>
                        </template>

                        <template #cell-professional="{ row }">
                            <span class="text-xs text-muted-foreground">{{ row.professional ?? '—' }}</span>
                        </template>

                        <template #cell-starts_at="{ row }">
                            <span class="text-xs text-muted-foreground">{{ formatDate(row.starts_at) }}</span>
                        </template>

                        <template #cell-amount="{ row }">
                            <span class="whitespace-nowrap text-sm font-medium tabular-nums text-foreground">{{ formatMoney(row.amount) }}</span>
                        </template>

                        <template #cell-status_label="{ row }">
                            <StatusBadge :tone="tone(row.status_tone)" :label="row.status_label" />
                        </template>

                        <template #cell-actions="{ row }">
                            <Button as-child variant="ghost" size="sm">
                                <Link :href="route('admin.bookings.show', row.id)">Gérer</Link>
                            </Button>
                        </template>
                    </DataTable>

                    <div
                        v-if="props.bookings.last_page > 1"
                        class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-border/60 pt-4"
                    >
                        <p class="text-xs text-muted-foreground">
                            Page {{ props.bookings.current_page }} sur {{ props.bookings.last_page }} · {{ props.bookings.total }} réservation{{
                                props.bookings.total > 1 ? 's' : ''
                            }}
                        </p>
                        <div class="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="props.bookings.current_page <= 1"
                                @click="goToPage(props.bookings.current_page - 1)"
                                >Précédent</Button
                            >
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="props.bookings.current_page >= props.bookings.last_page"
                                @click="goToPage(props.bookings.current_page + 1)"
                                >Suivant</Button
                            >
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
