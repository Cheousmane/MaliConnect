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
import { Filter, LoaderCircle, Search, Users } from 'lucide-vue-next';
import { ref } from 'vue';

type UserRow = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    status: string;
    status_label: string;
    status_tone: string;
    roles: string[];
    subscription_status: string | null;
    subscription_status_label: string | null;
    subscription_plan: string | null;
    email_verified_at: string | null;
    last_login_at: string | null;
    created_at: string | null;
};

type Props = {
    users: {
        data: UserRow[];
        current_page: number;
        last_page: number;
        total: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    filters: { search: string; status: string; role: string };
    statuses: Array<{ value: string; label: string }>;
    roles: Array<{ value: string; label: string }>;
    canChangeStatus: boolean;
    canAssignRoles: boolean;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: '/admin' },
    { title: 'Utilisateurs', href: '/admin/users' },
];

const search = ref(props.filters.search);
const status = ref(props.filters.status);
const role = ref(props.filters.role);
const filtering = ref(false);

const columns: TableColumn[] = [
    { key: 'name', label: 'Nom' },
    { key: 'email', label: 'E-mail', hideOnMobile: true },
    { key: 'roles', label: 'Rôles' },
    { key: 'subscription_plan', label: 'Abonnement', hideOnMobile: true },
    { key: 'status_label', label: 'Statut' },
    { key: 'created_at', label: 'Créé le', hideOnMobile: true },
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
        route('admin.users.index'),
        {
            search: search.value || undefined,
            status: status.value || undefined,
            role: role.value || undefined,
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
    role.value = '';
    applyFilters();
};

const goToPage = (page: number) => {
    if (page < 1 || page > props.users.last_page) {
        return;
    }

    router.get(
        route('admin.users.index'),
        {
            page,
            search: search.value || undefined,
            status: status.value || undefined,
            role: role.value || undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
};
</script>

<template>
    <Head title="Comptes utilisateurs" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader eyebrow="Administration" title="Comptes utilisateurs" description="Consultez et gérez les comptes de la plateforme.">
                <template #meta>
                    <p class="mt-2 flex items-center gap-2 text-xs text-muted-foreground">
                        <Users class="size-3.5" aria-hidden="true" />
                        {{ props.users.total }} compte{{ props.users.total > 1 ? 's' : '' }}
                    </p>
                </template>
            </PageHeader>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Filtrer les comptes</CardTitle>
                    <CardDescription>Recherchez par nom, e-mail ou téléphone.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_190px_190px_auto]" @submit.prevent="applyFilters">
                        <div class="relative">
                            <Search
                                class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                                aria-hidden="true"
                            />
                            <Input
                                v-model="search"
                                type="search"
                                placeholder="Nom, e-mail, téléphone"
                                class="pl-10"
                                aria-label="Rechercher un compte"
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
                            v-model="role"
                            class="h-11 w-full rounded-lg border border-input bg-background px-3.5 text-sm text-foreground"
                            aria-label="Filtrer par rôle"
                        >
                            <option value="">Tous les rôles</option>
                            <option v-for="item in props.roles" :key="item.value" :value="item.value">{{ item.label }}</option>
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
                    <CardTitle class="text-base">Tous les comptes</CardTitle>
                    <CardDescription>Les comptes sont créés via l'inscription ou la commande artisan.</CardDescription>
                </CardHeader>
                <CardContent>
                    <DataTable
                        :columns="columns"
                        :rows="props.users.data"
                        row-key="id"
                        empty-title="Aucun compte"
                        empty-description="Les comptes créés apparaîtront ici."
                        :empty-icon="Users"
                    >
                        <template #cell-name="{ row }">
                            <div class="min-w-0">
                                <Link
                                    :href="route('admin.users.show', row.id)"
                                    class="block truncate text-sm font-medium text-foreground hover:text-brand-700 dark:hover:text-brand-300"
                                >
                                    {{ row.name }}
                                </Link>
                                <p class="truncate text-xs text-muted-foreground">{{ row.phone ?? '—' }}</p>
                            </div>
                        </template>

                        <template #cell-email="{ row }">
                            <span class="truncate text-xs text-muted-foreground">{{ row.email }}</span>
                        </template>

                        <template #cell-roles="{ row }">
                            <div class="flex flex-wrap gap-1">
                                <StatusBadge
                                    v-for="r in row.roles"
                                    :key="r"
                                    tone="brand"
                                    :label="
                                        r === 'super-admin'
                                            ? 'Super administrateur'
                                            : r === 'admin'
                                              ? 'Administrateur'
                                              : r === 'customer'
                                                ? 'Client'
                                                : r === 'professional'
                                                  ? 'Professionnel'
                                                  : r === 'company-manager'
                                                    ? 'Gestionnaire d’entreprise'
                                                    : r
                                    "
                                    size="sm"
                                />
                                <span v-if="row.roles.length === 0" class="text-xs text-muted-foreground">—</span>
                            </div>
                        </template>

                        <template #cell-subscription_plan="{ row }">
                            <div v-if="row.subscription_plan" class="text-xs">
                                <p class="font-medium text-foreground">{{ row.subscription_plan }}</p>
                                <p class="text-muted-foreground">{{ row.subscription_status_label ?? '—' }}</p>
                            </div>
                            <span v-else class="text-xs text-muted-foreground">Aucun</span>
                        </template>

                        <template #cell-status_label="{ row }">
                            <StatusBadge :tone="tone(row.status_tone)" :label="row.status_label" />
                        </template>

                        <template #cell-created_at="{ row }">
                            <span class="text-xs text-muted-foreground">{{ formatDate(row.created_at) }}</span>
                        </template>

                        <template #cell-actions="{ row }">
                            <Button as-child variant="ghost" size="sm">
                                <Link :href="route('admin.users.show', row.id)">Gérer</Link>
                            </Button>
                        </template>
                    </DataTable>

                    <div
                        v-if="props.users.last_page > 1"
                        class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-border/60 pt-4"
                    >
                        <p class="text-xs text-muted-foreground">
                            Page {{ props.users.current_page }} sur {{ props.users.last_page }} · {{ props.users.total }} compte{{
                                props.users.total > 1 ? 's' : ''
                            }}
                        </p>

                        <div class="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="props.users.current_page <= 1"
                                @click="goToPage(props.users.current_page - 1)"
                            >
                                Précédent
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="props.users.current_page >= props.users.last_page"
                                @click="goToPage(props.users.current_page + 1)"
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
