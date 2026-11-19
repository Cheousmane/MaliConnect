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
import { CreditCard, Filter, LoaderCircle, Search } from 'lucide-vue-next';
import { ref } from 'vue';

type PaymentRow = {
    id: number;
    reference: string;
    external_reference: string | null;
    amount: number;
    currency: string;
    formatted_amount: string;
    method: string;
    method_label: string;
    billing_cycle: string | null;
    billing_cycle_label: string | null;
    status: string;
    status_label: string;
    status_tone: string;
    plan: { id: number; code: string; name: string } | null;
    owner_label: string;
    professional: string | null;
    business: string | null;
    proofs_count: number;
    can_review: boolean;
    declared_at: string | null;
    paid_at: string | null;
    created_at: string | null;
};

type Props = {
    payments: {
        data: PaymentRow[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: { search: string; status: string; method: string; plan: string };
    statuses: Array<{ value: string; label: string }>;
    methods: Array<{ value: string; label: string }>;
    plans: Array<{ value: string; label: string }>;
    stats: {
        paid_total: number;
        pending_count: number;
        pending_amount: number;
    };
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: '/admin' },
    { title: 'Paiements', href: '/admin/paiements' },
];

const search = ref(props.filters.search);
const status = ref(props.filters.status);
const method = ref(props.filters.method);
const plan = ref(props.filters.plan);
const filtering = ref(false);

const columns: TableColumn[] = [
    { key: 'reference', label: 'Référence' },
    { key: 'owner_label', label: 'Propriétaire' },
    { key: 'plan', label: 'Formule', hideOnMobile: true },
    { key: 'formatted_amount', label: 'Montant' },
    { key: 'method_label', label: 'Moyen', hideOnMobile: true },
    { key: 'status_label', label: 'Statut' },
    { key: 'declared_at', label: 'Déclaré le', hideOnMobile: true },
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
        route('admin.payments.index'),
        {
            search: search.value || undefined,
            status: status.value || undefined,
            method: method.value || undefined,
            plan: plan.value || undefined,
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
    method.value = '';
    plan.value = '';
    applyFilters();
};

const goToPage = (page: number) => {
    if (page < 1 || page > props.payments.last_page) return;
    router.get(
        route('admin.payments.index'),
        {
            page,
            search: search.value || undefined,
            status: status.value || undefined,
            method: method.value || undefined,
            plan: plan.value || undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
};
</script>

<template>
    <Head title="Paiements" />
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader eyebrow="Administration" title="Paiements" description="Supervision de tous les paiements de la plateforme.">
                <template #meta>
                    <p class="mt-2 flex items-center gap-2 text-xs text-muted-foreground">
                        <CreditCard class="size-3.5" aria-hidden="true" />
                        {{ props.payments.total }} paiement{{ props.payments.total > 1 ? 's' : '' }}
                    </p>
                </template>
            </PageHeader>

            <div class="grid gap-4 sm:grid-cols-3">
                <Card>
                    <CardContent class="p-4">
                        <p class="text-xs text-muted-foreground">Total encaissé</p>
                        <p class="mt-1 text-lg font-semibold text-foreground">{{ formatMoney(props.stats.paid_total) }}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-4">
                        <p class="text-xs text-muted-foreground">En attente</p>
                        <p class="mt-1 text-lg font-semibold text-foreground">{{ props.stats.pending_count }}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-4">
                        <p class="text-xs text-muted-foreground">Montant en attente</p>
                        <p class="mt-1 text-lg font-semibold text-foreground">{{ formatMoney(props.stats.pending_amount) }}</p>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Filtrer les paiements</CardTitle>
                    <CardDescription>Recherchez par référence, propriétaire, formule ou statut.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_190px_190px_190px_auto]" @submit.prevent="applyFilters">
                        <div class="relative">
                            <Search
                                class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                                aria-hidden="true"
                            />
                            <Input v-model="search" type="search" placeholder="Référence, propriétaire" class="pl-10" aria-label="Rechercher" />
                        </div>
                        <select
                            v-model="status"
                            class="h-11 w-full rounded-lg border border-input bg-background px-3.5 text-sm text-foreground"
                            aria-label="Statut"
                        >
                            <option value="">Tous les statuts</option>
                            <option v-for="item in props.statuses" :key="item.value" :value="item.value">{{ item.label }}</option>
                        </select>
                        <select
                            v-model="method"
                            class="h-11 w-full rounded-lg border border-input bg-background px-3.5 text-sm text-foreground"
                            aria-label="Moyen"
                        >
                            <option value="">Tous les moyens</option>
                            <option v-for="item in props.methods" :key="item.value" :value="item.value">{{ item.label }}</option>
                        </select>
                        <select
                            v-model="plan"
                            class="h-11 w-full rounded-lg border border-input bg-background px-3.5 text-sm text-foreground"
                            aria-label="Formule"
                        >
                            <option value="">Toutes les formules</option>
                            <option v-for="item in props.plans" :key="item.value" :value="item.value">{{ item.label }}</option>
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
                    <CardTitle class="text-base">Tous les paiements</CardTitle>
                    <CardDescription>Les paiements en attente ne sont jamais comptés comme revenus.</CardDescription>
                </CardHeader>
                <CardContent>
                    <DataTable
                        :columns="columns"
                        :rows="props.payments.data"
                        row-key="id"
                        empty-title="Aucun paiement"
                        empty-description="Les paiements apparaîtront ici."
                        :empty-icon="CreditCard"
                    >
                        <template #cell-reference="{ row }">
                            <Link
                                :href="route('admin.payments.show', row.id)"
                                class="block truncate text-sm font-medium text-foreground hover:text-brand-700 dark:hover:text-brand-300"
                            >
                                {{ row.reference }}
                            </Link>
                        </template>

                        <template #cell-owner_label="{ row }">
                            <span class="truncate text-sm text-foreground">{{ row.owner_label }}</span>
                        </template>

                        <template #cell-plan="{ row }">
                            <span class="text-xs text-muted-foreground">{{ row.plan?.name ?? '—' }}</span>
                        </template>

                        <template #cell-formatted_amount="{ row }">
                            <span class="whitespace-nowrap text-sm font-medium tabular-nums text-foreground">{{ row.formatted_amount }}</span>
                        </template>

                        <template #cell-method_label="{ row }">
                            <span class="text-xs text-muted-foreground">{{ row.method_label }}</span>
                        </template>

                        <template #cell-status_label="{ row }">
                            <StatusBadge :tone="tone(row.status_tone)" :label="row.status_label" />
                        </template>

                        <template #cell-declared_at="{ row }">
                            <span class="text-xs text-muted-foreground">{{ formatDate(row.declared_at) }}</span>
                        </template>

                        <template #cell-actions="{ row }">
                            <Button as-child variant="ghost" size="sm">
                                <Link :href="route('admin.payments.show', row.id)">Gérer</Link>
                            </Button>
                        </template>
                    </DataTable>

                    <div
                        v-if="props.payments.last_page > 1"
                        class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-border/60 pt-4"
                    >
                        <p class="text-xs text-muted-foreground">
                            Page {{ props.payments.current_page }} sur {{ props.payments.last_page }} · {{ props.payments.total }} paiement{{
                                props.payments.total > 1 ? 's' : ''
                            }}
                        </p>
                        <div class="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="props.payments.current_page <= 1"
                                @click="goToPage(props.payments.current_page - 1)"
                                >Précédent</Button
                            >
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="props.payments.current_page >= props.payments.last_page"
                                @click="goToPage(props.payments.current_page + 1)"
                                >Suivant</Button
                            >
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
