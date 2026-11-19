<script setup lang="ts">
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import type { BreadcrumbItem, TableColumn } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Filter, LoaderCircle, Search, ShieldCheck } from 'lucide-vue-next';
import { ref } from 'vue';

type LogRow = {
    id: number;
    action: string;
    action_label: string | null;
    description: string;
    actor: string | null;
    subject_type: string | null;
    subject_id: number | null;
    ip_address: string | null;
    created_at: string | null;
};

type Props = {
    logs: {
        data: LogRow[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: { search: string; action: string };
    actions: string[];
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: '/admin' },
    { title: 'Journal d’audit', href: '/admin/journal' },
];

const search = ref(props.filters.search);
const action = ref(props.filters.action);
const filtering = ref(false);

const columns: TableColumn[] = [
    { key: 'action', label: 'Action' },
    { key: 'description', label: 'Description' },
    { key: 'actor', label: 'Acteur', hideOnMobile: true },
    { key: 'subject_type', label: 'Sujet', hideOnMobile: true },
    { key: 'ip_address', label: 'IP', hideOnMobile: true },
    { key: 'created_at', label: 'Date' },
    { key: 'actions', label: '', align: 'right' },
];

const formatDate = (value: string | null): string => {
    if (!value) return '—';
    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
};

const applyFilters = () => {
    filtering.value = true;
    router.get(
        route('admin.audit.index'),
        {
            search: search.value || undefined,
            action: action.value || undefined,
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
    action.value = '';
    applyFilters();
};

const goToPage = (page: number) => {
    if (page < 1 || page > props.logs.last_page) return;
    router.get(
        route('admin.audit.index'),
        {
            page,
            search: search.value || undefined,
            action: action.value || undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
};
</script>

<template>
    <Head title="Journal d’audit" />
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader
                eyebrow="Administration"
                title="Journal d’audit"
                description="Historique des actions sensibles réalisées dans l’administration."
            >
                <template #meta>
                    <p class="mt-2 flex items-center gap-2 text-xs text-muted-foreground">
                        <ShieldCheck class="size-3.5" aria-hidden="true" />
                        {{ props.logs.total }} entrée{{ props.logs.total > 1 ? 's' : '' }}
                    </p>
                </template>
            </PageHeader>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Filtrer le journal</CardTitle>
                    <CardDescription>Recherchez par action, description ou acteur.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_220px_auto]" @submit.prevent="applyFilters">
                        <div class="relative">
                            <Search
                                class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                                aria-hidden="true"
                            />
                            <Input v-model="search" type="search" placeholder="Action, description, acteur" class="pl-10" aria-label="Rechercher" />
                        </div>
                        <select
                            v-model="action"
                            class="h-11 w-full rounded-lg border border-input bg-background px-3.5 text-sm text-foreground"
                            aria-label="Action"
                        >
                            <option value="">Toutes les actions</option>
                            <option v-for="item in props.actions" :key="item" :value="item">{{ item }}</option>
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
                    <CardTitle class="text-base">Journal des actions</CardTitle>
                    <CardDescription>Chaque action sensible est enregistrée avec son auteur et sa date.</CardDescription>
                </CardHeader>
                <CardContent>
                    <DataTable
                        :columns="columns"
                        :rows="props.logs.data"
                        row-key="id"
                        empty-title="Aucune entrée"
                        empty-description="Les actions administratives apparaîtront ici."
                        :empty-icon="ShieldCheck"
                    >
                        <template #cell-action="{ row }">
                            <span class="text-xs font-medium text-foreground">{{ row.action_label ?? row.action }}</span>
                        </template>

                        <template #cell-description="{ row }">
                            <span class="truncate text-sm text-foreground">{{ row.description }}</span>
                        </template>

                        <template #cell-actor="{ row }">
                            <span class="text-xs text-muted-foreground">{{ row.actor ?? 'Système' }}</span>
                        </template>

                        <template #cell-subject_type="{ row }">
                            <span class="text-xs text-muted-foreground"
                                >{{ row.subject_type ?? '—' }}<span v-if="row.subject_id"> #{{ row.subject_id }}</span></span
                            >
                        </template>

                        <template #cell-ip_address="{ row }">
                            <span class="font-mono text-xs text-muted-foreground">{{ row.ip_address ?? '—' }}</span>
                        </template>

                        <template #cell-created_at="{ row }">
                            <span class="text-xs text-muted-foreground">{{ formatDate(row.created_at) }}</span>
                        </template>

                        <template #cell-actions="{ row }">
                            <Button as-child variant="ghost" size="sm">
                                <Link :href="route('admin.audit.show', row.id)">Détail</Link>
                            </Button>
                        </template>
                    </DataTable>

                    <div
                        v-if="props.logs.last_page > 1"
                        class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-border/60 pt-4"
                    >
                        <p class="text-xs text-muted-foreground">
                            Page {{ props.logs.current_page }} sur {{ props.logs.last_page }} · {{ props.logs.total }} entrée{{
                                props.logs.total > 1 ? 's' : ''
                            }}
                        </p>
                        <div class="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="props.logs.current_page <= 1"
                                @click="goToPage(props.logs.current_page - 1)"
                                >Précédent</Button
                            >
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="props.logs.current_page >= props.logs.last_page"
                                @click="goToPage(props.logs.current_page + 1)"
                                >Suivant</Button
                            >
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
