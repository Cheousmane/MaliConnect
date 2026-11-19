<script setup lang="ts">
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import type { BreadcrumbItem, StatusTone, TableColumn } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Filter, LoaderCircle, Search, Star } from 'lucide-vue-next';
import { ref } from 'vue';

type ReviewRow = {
    id: number;
    rating: number;
    comment: string | null;
    status: string;
    status_label: string;
    status_tone: string;
    created_at: string | null;
    published_at: string | null;
    customer: string | null;
    professional: string | null;
    booking_reference: string | null;
    professional_reply: string | null;
};

type Props = {
    reviews: {
        data: ReviewRow[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: { status: string; search: string };
    statuses: Array<{ value: string; label: string }>;
    pendingCount: number;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: '/admin' },
    { title: 'Modération des avis', href: '/admin/avis' },
];

const search = ref(props.filters.search);
const status = ref(props.filters.status);
const filtering = ref(false);

const columns: TableColumn[] = [
    { key: 'rating', label: 'Note' },
    { key: 'customer', label: 'Client' },
    { key: 'professional', label: 'Professionnel', hideOnMobile: true },
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
        route('admin.reviews.index'),
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
    if (page < 1 || page > props.reviews.last_page) return;
    router.get(
        route('admin.reviews.index'),
        {
            page,
            search: search.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
};

const moderate = (review: any, decision: string) => {
    router.post(
        route('admin.reviews.moderate', review.id),
        {
            decision,
            reason: null,
        },
        { preserveScroll: true },
    );
};
</script>

<template>
    <Head title="Modération des avis" />
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader eyebrow="Administration" title="Modération des avis" description="Approuvez ou refusez les avis clients.">
                <template #meta>
                    <p class="mt-2 flex items-center gap-2 text-xs text-muted-foreground">
                        <Star class="size-3.5" aria-hidden="true" />
                        {{ props.reviews.total }} avis ·
                        <span v-if="props.pendingCount" class="font-medium text-amber-700 dark:text-amber-300">
                            {{ props.pendingCount }} en attente
                        </span>
                    </p>
                </template>
            </PageHeader>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Filtrer les avis</CardTitle>
                    <CardDescription>Recherchez par professionnel ou client.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_190px_auto]" @submit.prevent="applyFilters">
                        <div class="relative">
                            <Search
                                class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                                aria-hidden="true"
                            />
                            <Input v-model="search" type="search" placeholder="Professionnel, client" class="pl-10" aria-label="Rechercher" />
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
                    <CardTitle class="text-base">File de modération</CardTitle>
                    <CardDescription>Les avis en attente nécessitent une décision.</CardDescription>
                </CardHeader>
                <CardContent>
                    <DataTable
                        :columns="columns"
                        :rows="props.reviews.data"
                        row-key="id"
                        empty-title="Aucun avis"
                        empty-description="Les avis clients apparaîtront ici."
                        :empty-icon="Star"
                    >
                        <template #cell-rating="{ row }">
                            <div class="flex items-center gap-1">
                                <Star v-for="i in row.rating" :key="i" class="size-3.5 fill-gold-400 text-gold-400" aria-hidden="true" />
                            </div>
                        </template>

                        <template #cell-customer="{ row }">
                            <span class="text-sm text-foreground">{{ row.customer ?? '—' }}</span>
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
                            <div class="flex gap-1">
                                <Button variant="ghost" size="sm" class="text-success" @click="moderate(row, 'approve')">Publier</Button>
                                <Button variant="ghost" size="sm" class="text-destructive" @click="moderate(row, 'reject')">Refuser</Button>
                            </div>
                        </template>
                    </DataTable>

                    <div
                        v-if="props.reviews.last_page > 1"
                        class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-border/60 pt-4"
                    >
                        <p class="text-xs text-muted-foreground">
                            Page {{ props.reviews.current_page }} sur {{ props.reviews.last_page }} · {{ props.reviews.total }} avis
                        </p>
                        <div class="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="props.reviews.current_page <= 1"
                                @click="goToPage(props.reviews.current_page - 1)"
                                >Précédent</Button
                            >
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="props.reviews.current_page >= props.reviews.last_page"
                                @click="goToPage(props.reviews.current_page + 1)"
                                >Suivant</Button
                            >
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
