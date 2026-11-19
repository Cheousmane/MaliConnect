<script setup lang="ts">
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatCard from '@/components/StatCard.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import type { BreadcrumbItem, StatusTone, TableColumn } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarClock, CircleAlert, CircleCheck, CircleSlash, Filter, LoaderCircle, Search, Wallet } from 'lucide-vue-next';
import { ref } from 'vue';

type SubscriptionOwner = {
    type: string;
    id: number | null;
    name: string;
    slug: string | null;
    manager: string | null;
    email: string | null;
};

type SubscriptionRow = {
    id: number;
    plan: { id: number | null; code: string; name: string };
    plan_code: string;
    plan_name: string;
    pending_plan: { id: number; code: string; name: string } | null;
    pending_plan_effective_at: string | null;
    has_scheduled_change: boolean;
    status: string;
    status_label: string;
    status_tone: string;
    billing_cycle: string | null;
    billing_cycle_label: string;
    amount: number;
    currency: string;
    formatted_amount: string;
    is_active: boolean;
    grants_access: boolean;
    owner: SubscriptionOwner;
    owner_label: string;
    owner_type: string;
    starts_at: string | null;
    ends_at: string | null;
    created_at: string | null;
};

type Props = {
    subscriptions: {
        data: SubscriptionRow[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: { search: string; status: string; plan: string; owner_type: string };
    statuses: Array<{ value: string; label: string }>;
    plans: Array<{ value: string; label: string }>;
    stats: {
        active: number;
        pending: number;
        past_due: number;
        expired: number;
        suspended: number;
    };
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: '/admin' },
    { title: 'Abonnements', href: '/admin/abonnements' },
];

const search = ref(props.filters.search);
const status = ref(props.filters.status);
const plan = ref(props.filters.plan);
const ownerType = ref(props.filters.owner_type);
const filtering = ref(false);

const columns: TableColumn[] = [
    { key: 'owner_label', label: 'Propriétaire' },
    { key: 'plan_name', label: 'Formule' },
    { key: 'status_label', label: 'Statut' },
    { key: 'billing_cycle_label', label: 'Périodicité', hideOnMobile: true },
    { key: 'formatted_amount', label: 'Montant', hideOnMobile: true },
    { key: 'ends_at', label: 'Échéance', hideOnMobile: true },
    { key: 'actions', label: '', align: 'right' },
];

const tone = (value: string): StatusTone =>
    ['neutral', 'brand', 'success', 'warning', 'danger', 'info'].includes(value) ? (value as StatusTone) : 'neutral';

const formatDate = (value: string | null): string => {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium' }).format(new Date(value));
};

const applyFilters = () => {
    filtering.value = true;

    router.get(
        route('admin.subscriptions.index'),
        {
            search: search.value || undefined,
            status: status.value || undefined,
            plan: plan.value || undefined,
            owner_type: ownerType.value || undefined,
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
    plan.value = '';
    ownerType.value = '';
    applyFilters();
};

const goToPage = (page: number) => {
    if (page < 1 || page > props.subscriptions.last_page) {
        return;
    }

    router.get(
        route('admin.subscriptions.index'),
        {
            page,
            search: search.value || undefined,
            status: status.value || undefined,
            plan: plan.value || undefined,
            owner_type: ownerType.value || undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
};
</script>

<template>
    <Head title="Abonnements" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader
                eyebrow="Administration"
                title="Abonnements"
                description="Suivez les abonnements des profils indépendants et des entreprises, leurs échéances et les changements programmés."
            >
                <template #meta>
                    <p class="mt-2 flex items-center gap-2 text-xs text-muted-foreground">
                        <Wallet class="size-3.5" aria-hidden="true" />
                        {{ props.subscriptions.total }} abonnement{{ props.subscriptions.total > 1 ? 's' : '' }} enregistré{{
                            props.subscriptions.total > 1 ? 's' : ''
                        }}
                    </p>
                </template>
            </PageHeader>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <StatCard label="Actifs" :value="props.stats.active" :icon="CircleCheck" tone="success" description="Droits ouverts" />
                <StatCard label="En attente" :value="props.stats.pending" :icon="CalendarClock" tone="warning" description="Paiement à vérifier" />
                <StatCard label="En retard" :value="props.stats.past_due" :icon="CircleAlert" tone="warning" description="Période de grâce" />
                <StatCard label="Expirés" :value="props.stats.expired" :icon="CircleSlash" tone="danger" description="Accès aux options fermé" />
                <StatCard label="Suspendus" :value="props.stats.suspended" :icon="CircleSlash" tone="neutral" description="Décision administrative" />
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Filtrer les abonnements</CardTitle>
                    <CardDescription>Recherchez par professionnel, entreprise, formule ou statut.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_190px_190px_190px_auto]" @submit.prevent="applyFilters">
                        <div class="relative">
                            <Search
                                class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                                aria-hidden="true"
                            />
                            <Input
                                v-model="search"
                                type="search"
                                placeholder="Professionnel, entreprise, gestionnaire"
                                class="pl-10"
                                aria-label="Rechercher un abonnement"
                            />
                        </div>

                        <select
                            v-model="status"
                            class="h-11 w-full rounded-lg border border-input bg-background px-3.5 text-sm text-foreground"
                            aria-label="Filtrer par statut"
                        >
                            <option value="">Tous les statuts</option>
                            <option v-for="item in props.statuses" :key="item.value" :value="item.value">{{ item.label }}</option>
                        </select>

                        <select
                            v-model="plan"
                            class="h-11 w-full rounded-lg border border-input bg-background px-3.5 text-sm text-foreground"
                            aria-label="Filtrer par formule"
                        >
                            <option value="">Toutes les formules</option>
                            <option v-for="item in props.plans" :key="item.value" :value="item.value">{{ item.label }}</option>
                        </select>

                        <select
                            v-model="ownerType"
                            class="h-11 w-full rounded-lg border border-input bg-background px-3.5 text-sm text-foreground"
                            aria-label="Filtrer par type de propriétaire"
                        >
                            <option value="">Tous les propriétaires</option>
                            <option value="profile">Profils indépendants</option>
                            <option value="business">Entreprises</option>
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
                    <CardTitle class="text-base">Tous les abonnements</CardTitle>
                    <CardDescription>
                        Un seul abonnement courant par propriétaire. Les changements de formule passent par l’historique.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <DataTable
                        :columns="columns"
                        :rows="props.subscriptions.data"
                        row-key="id"
                        empty-title="Aucun abonnement"
                        empty-description="Les souscriptions professionnelles apparaîtront ici."
                        :empty-icon="Wallet"
                    >
                        <template #cell-owner_label="{ row }">
                            <div class="min-w-0">
                                <Link
                                    :href="route('admin.subscriptions.show', row.id)"
                                    class="block truncate text-sm font-medium text-foreground hover:text-brand-700 dark:hover:text-brand-300"
                                >
                                    {{ row.owner_label }}
                                </Link>
                                <p class="truncate text-xs text-muted-foreground">
                                    {{ row.owner.type === 'business' ? 'Entreprise' : 'Profil indépendant' }}
                                    <span v-if="row.owner.manager"> · {{ row.owner.manager }}</span>
                                </p>
                            </div>
                        </template>

                        <template #cell-plan_name="{ row }">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-foreground">{{ row.plan_name }}</p>
                                <p v-if="row.has_scheduled_change && row.pending_plan" class="truncate text-xs text-amber-700 dark:text-amber-300">
                                    → {{ row.pending_plan.name }} programmé
                                </p>
                            </div>
                        </template>

                        <template #cell-status_label="{ row }">
                            <StatusBadge :tone="tone(row.status_tone)" :label="row.status_label" />
                        </template>

                        <template #cell-formatted_amount="{ row }">
                            <div class="text-sm">
                                <p class="font-medium text-foreground">{{ row.formatted_amount }}</p>
                                <p class="text-xs text-muted-foreground">{{ row.billing_cycle_label }}</p>
                            </div>
                        </template>

                        <template #cell-ends_at="{ row }">
                            <span class="text-xs text-muted-foreground">{{ formatDate(row.ends_at) }}</span>
                        </template>

                        <template #cell-actions="{ row }">
                            <Button as-child variant="ghost" size="sm">
                                <Link :href="route('admin.subscriptions.show', row.id)">Gérer</Link>
                            </Button>
                        </template>
                    </DataTable>

                    <div
                        v-if="props.subscriptions.last_page > 1"
                        class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-border/60 pt-4"
                    >
                        <p class="text-xs text-muted-foreground">
                            Page {{ props.subscriptions.current_page }} sur {{ props.subscriptions.last_page }} ·
                            {{ props.subscriptions.total }} abonnement{{ props.subscriptions.total > 1 ? 's' : '' }}
                        </p>

                        <div class="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="props.subscriptions.current_page <= 1"
                                @click="goToPage(props.subscriptions.current_page - 1)"
                            >
                                Précédent
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="props.subscriptions.current_page >= props.subscriptions.last_page"
                                @click="goToPage(props.subscriptions.current_page + 1)"
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
