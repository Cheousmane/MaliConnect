<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogDescription, DialogFooter, DialogScrollContent, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import type { BreadcrumbItem, StatusTone } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ShieldCheck, UserRound } from 'lucide-vue-next';
import { ref } from 'vue';

type AccountDetail = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    status: string;
    status_label: string;
    status_tone: string;
    roles: string[];
    role_labels: string[];
    permissions: string[];
    permission_labels: string[];
    email_verified_at: string | null;
    last_login_at: string | null;
    created_at: string | null;
};

type Props = {
    account: AccountDetail;
    statuses: Array<{ value: string; label: string }>;
    roles: Array<{ value: string; label: string }>;
    canChangeStatus: boolean;
    canAssignRoles: boolean;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: '/admin' },
    { title: 'Utilisateurs', href: '/admin/users' },
    { title: props.account.name, href: `/admin/users/${props.account.id}` },
];

const tone = (value: string): StatusTone =>
    ['neutral', 'brand', 'success', 'warning', 'danger', 'info'].includes(value) ? (value as StatusTone) : 'neutral';

const formatDate = (value: string | null): string => {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
};

const statusOpen = ref(false);
const rolesOpen = ref(false);

const statusForm = useForm({ status: props.account.status });
const rolesForm = useForm({ roles: [...props.account.roles] });

const submitStatus = () => {
    statusForm.patch(route('admin.users.status.update', props.account.id), {
        preserveScroll: true,
        onSuccess: () => {
            statusOpen.value = false;
        },
    });
};

const submitRoles = () => {
    rolesForm.patch(route('admin.users.roles.update', props.account.id), {
        preserveScroll: true,
        onSuccess: () => {
            rolesOpen.value = false;
        },
    });
};

const toggleRole = (roleValue: string) => {
    const current = rolesForm.roles;
    const index = current.indexOf(roleValue);

    if (index === -1) {
        current.push(roleValue);
    } else {
        current.splice(index, 1);
    }
};
</script>

<template>
    <Head :title="`Compte · ${props.account.name}`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader eyebrow="Compte utilisateur" :title="props.account.name" :description="props.account.email">
                <template #actions>
                    <Button as-child variant="outline">
                        <Link :href="route('admin.users.index')">
                            <ArrowLeft class="size-4" aria-hidden="true" />
                            Retour
                        </Link>
                    </Button>
                    <Button v-if="props.canChangeStatus" variant="outline" @click="statusOpen = true">
                        <ShieldCheck class="size-4" aria-hidden="true" />
                        Changer le statut
                    </Button>
                    <Button v-if="props.canAssignRoles" variant="brand" @click="rolesOpen = true">
                        <UserRound class="size-4" aria-hidden="true" />
                        Gérer les rôles
                    </Button>
                </template>
            </PageHeader>

            <div class="grid gap-4 lg:grid-cols-3">
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle class="text-base">Informations du compte</CardTitle>
                        <CardDescription>Identité et coordonnées.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Nom</p>
                                <p class="mt-1 text-sm font-semibold text-foreground">{{ props.account.name }}</p>
                            </div>
                            <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">E-mail</p>
                                <p class="mt-1 text-sm font-semibold text-foreground">{{ props.account.email }}</p>
                            </div>
                            <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Téléphone</p>
                                <p class="mt-1 text-sm font-semibold text-foreground">{{ props.account.phone ?? '—' }}</p>
                            </div>
                            <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Statut</p>
                                <div class="mt-1">
                                    <StatusBadge :tone="tone(props.account.status_tone)" :label="props.account.status_label" />
                                </div>
                            </div>
                            <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">E-mail vérifié</p>
                                <p class="mt-1 text-sm font-semibold text-foreground">{{ formatDate(props.account.email_verified_at) }}</p>
                            </div>
                            <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Dernière connexion</p>
                                <p class="mt-1 text-sm font-semibold text-foreground">{{ formatDate(props.account.last_login_at) }}</p>
                            </div>
                            <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Créé le</p>
                                <p class="mt-1 text-sm font-semibold text-foreground">{{ formatDate(props.account.created_at) }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Rôles et permissions</CardTitle>
                        <CardDescription>Droits effectifs du compte.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Rôles</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <StatusBadge
                                    v-for="(label, index) in props.account.role_labels"
                                    :key="props.account.roles[index]"
                                    tone="brand"
                                    :label="label"
                                />
                                <span v-if="props.account.roles.length === 0" class="text-sm text-muted-foreground">Aucun rôle</span>
                            </div>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                Permissions ({{ props.account.permissions.length }})
                            </p>
                            <div class="mt-2 max-h-48 overflow-y-auto rounded-xl border border-border/70 bg-muted/20 p-3">
                                <div v-if="props.account.permissions.length === 0" class="text-sm text-muted-foreground">Aucune permission</div>
                                <ul v-else class="space-y-1 text-xs text-foreground">
                                    <li v-for="label in props.account.permission_labels" :key="label">
                                        {{ label }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Dialog v-model:open="statusOpen">
                <DialogScrollContent class="max-w-lg">
                    <DialogTitle>Changer le statut du compte</DialogTitle>
                    <DialogDescription> Le changement de statut est journalisé et audité. </DialogDescription>

                    <form class="grid gap-4" @submit.prevent="submitStatus">
                        <div class="grid gap-1.5">
                            <Label for="status">Nouveau statut</Label>
                            <select
                                id="status"
                                v-model="statusForm.status"
                                class="h-11 w-full rounded-lg border border-input bg-background px-3.5 text-sm text-foreground"
                            >
                                <option v-for="item in props.statuses" :key="item.value" :value="item.value">
                                    {{ item.label }}
                                </option>
                            </select>
                            <p v-if="statusForm.errors.status" class="text-xs text-destructive">{{ statusForm.errors.status }}</p>
                        </div>
                        <DialogFooter class="gap-2">
                            <Button type="button" variant="outline" @click="statusOpen = false">Annuler</Button>
                            <Button type="submit" variant="brand" :disabled="statusForm.processing">Enregistrer</Button>
                        </DialogFooter>
                    </form>
                </DialogScrollContent>
            </Dialog>

            <Dialog v-model:open="rolesOpen">
                <DialogScrollContent class="max-w-lg">
                    <DialogTitle>Gérer les rôles</DialogTitle>
                    <DialogDescription> Sélectionnez les rôles à attribuer à ce compte. </DialogDescription>

                    <form class="grid gap-4" @submit.prevent="submitRoles">
                        <div class="space-y-2">
                            <label
                                v-for="role in props.roles"
                                :key="role.value"
                                class="flex items-center gap-2 rounded-lg border border-border/60 px-3 py-2.5 text-sm text-foreground hover:bg-muted/50"
                            >
                                <input
                                    type="checkbox"
                                    :checked="rolesForm.roles.includes(role.value)"
                                    class="size-4 rounded border-input"
                                    @change="toggleRole(role.value)"
                                />
                                {{ role.label }}
                            </label>
                        </div>
                        <p v-if="rolesForm.errors.roles" class="text-xs text-destructive">{{ rolesForm.errors.roles }}</p>
                        <DialogFooter class="gap-2">
                            <Button type="button" variant="outline" @click="rolesOpen = false">Annuler</Button>
                            <Button type="submit" variant="brand" :disabled="rolesForm.processing">Enregistrer</Button>
                        </DialogFooter>
                    </form>
                </DialogScrollContent>
            </Dialog>
        </div>
    </AdminLayout>
</template>
