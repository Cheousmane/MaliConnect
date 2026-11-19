<script setup lang="ts">
import FormField from '@/components/FormField.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, StatusTone } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, LoaderCircle, Plus, Store, Trash2, UserPlus, Users } from 'lucide-vue-next';
import { ref } from 'vue';

type Member = {
    id: number;
    user_id: number;
    name: string | null;
    email: string | null;
    role: string;
    role_label: string;
    status: string;
    joined_at: string | null;
};

type BusinessProfile = {
    id: number;
    business_name: string;
    status: string;
    status_label: string;
    status_tone: string;
    category: string | null;
    is_active: boolean;
};

type Business = {
    id: number;
    name: string;
    slug: string;
    status: string;
    status_label: string;
    status_tone: string;
    legal_name: string | null;
    description: string | null;
    phone: string | null;
    email: string | null;
    address: string | null;
    city: string;
    commune: string | null;
    logo_path: string | null;
    refusal_reason: string | null;
    owner: { id: number; name: string; email: string } | null;
    members: Member[];
    profiles: BusinessProfile[];
};

type Role = {
    value: string;
    label: string;
};

type Props = {
    business: Business;
    roles: Role[];
    myRole: string | null;
    canManageMembers: boolean;
    canManageProfiles: boolean;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Mon entreprise',
        href: '/pro/businesses',
    },
    {
        title: props.business.name,
        href: `/pro/businesses/${props.business.id}`,
    },
];

const tone = (value: string): StatusTone => {
    if (value === 'success' || value === 'warning' || value === 'danger') {
        return value;
    }

    return 'neutral';
};

// Informations de l'entreprise.
const form = useForm({
    name: props.business.name,
    legal_name: props.business.legal_name ?? '',
    description: props.business.description ?? '',
    phone: props.business.phone ?? '',
    email: props.business.email ?? '',
    address: props.business.address ?? '',
    city: props.business.city,
    commune: props.business.commune ?? '',
});

// Collaborateurs.
const memberForm = useForm({
    user_id: '',
    role: 'agent',
});

// Recherche de comptes à inviter.
const search = ref('');
const results = ref<Array<{ id: number; name: string; email: string }>>([]);
const searching = ref(false);

const runSearch = async () => {
    searching.value = true;

    try {
        const url = new URL(route('pro.businesses.members.search', props.business.id), window.location.origin);
        url.searchParams.set('q', search.value);

        const response = await fetch(url.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });

        const payload = await response.json();

        results.value = payload.data ?? [];
    } finally {
        searching.value = false;
    }
};

const inviteMember = (userId: number) => {
    memberForm.user_id = String(userId);
    memberForm.role = 'agent';

    memberForm.post(route('pro.businesses.members.store', props.business.id), {
        preserveScroll: true,
        onSuccess: () => {
            memberForm.reset('user_id', 'role');
            results.value = [];
            search.value = '';
        },
    });
};

const updateRole = (member: Member, role: string) => {
    memberForm.user_id = String(member.user_id);
    memberForm.role = role;

    memberForm.patch(route('pro.businesses.members.update', [props.business.id, member.id]), {
        preserveScroll: true,
    });
};

const removeMember = (member: Member) => {
    memberForm.delete(route('pro.businesses.members.destroy', [props.business.id, member.id]), {
        preserveScroll: true,
    });
};

const saveBusiness = () => {
    form.patch(route('pro.businesses.update', props.business.id), { preserveScroll: true });
};
</script>

<template>
    <Head :title="business.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <Link
                :href="route('pro.businesses.index')"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft class="size-4" aria-hidden="true" />
                Retour aux entreprises
            </Link>

            <PageHeader eyebrow="Mon entreprise" :title="business.name">
                <template #meta>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <StatusBadge :tone="tone(business.status_tone)" :label="business.status_label" />
                        <span class="text-xs text-muted-foreground"
                            >{{ business.city }}<span v-if="business.commune"> · {{ business.commune }}</span></span
                        >
                    </div>
                </template>

                <template #actions>
                    <Button v-if="canManageProfiles" as-child variant="brand">
                        <Link :href="`${route('pro.profiles.create')}?business=${business.id}`">
                            <Plus class="size-4" aria-hidden="true" />
                            Ajouter un profil
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <div v-if="business.refusal_reason" class="rounded-xl border border-destructive/25 bg-destructive/5 p-4 text-sm text-muted-foreground">
                <span class="font-medium text-destructive">Dossier en attente de régularisation :</span>
                {{ business.refusal_reason }}
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Informations de l’entreprise</CardTitle>
                        <CardDescription>Ces informations apparaissent sur vos profils professionnels.</CardDescription>
                    </CardHeader>

                    <CardContent class="space-y-4">
                        <FormField label="Nom de l’entreprise" for="name" :error="form.errors.name" required>
                            <Input id="name" v-model="form.name" required maxlength="120" :aria-invalid="!!form.errors.name" />
                        </FormField>

                        <FormField label="Raison sociale" for="legal_name" :error="form.errors.legal_name">
                            <Input id="legal_name" v-model="form.legal_name" maxlength="180" />
                        </FormField>

                        <FormField label="Description" for="description" :error="form.errors.description">
                            <Textarea id="description" v-model="form.description" rows="4" maxlength="2000" />
                        </FormField>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <FormField label="Téléphone" for="phone" :error="form.errors.phone">
                                <Input id="phone" v-model="form.phone" maxlength="20" />
                            </FormField>
                            <FormField label="Adresse e-mail" for="email" :error="form.errors.email">
                                <Input id="email" v-model="form.email" type="email" maxlength="255" :aria-invalid="!!form.errors.email" />
                            </FormField>
                        </div>

                        <FormField label="Adresse" for="address" :error="form.errors.address">
                            <Input id="address" v-model="form.address" maxlength="255" />
                        </FormField>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <FormField label="Ville" for="city" :error="form.errors.city" required>
                                <Input id="city" v-model="form.city" required maxlength="100" />
                            </FormField>
                            <FormField label="Commune" for="commune" :error="form.errors.commune">
                                <Input id="commune" v-model="form.commune" maxlength="100" />
                            </FormField>
                        </div>

                        <Button type="button" variant="brand" :disabled="form.processing" @click="saveBusiness">
                            <LoaderCircle v-if="form.processing" class="size-4 animate-spin" aria-hidden="true" />
                            Enregistrer
                        </Button>
                    </CardContent>
                </Card>

                <div class="space-y-4">
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <CardTitle class="text-base">Collaborateurs</CardTitle>
                                    <CardDescription>Répartissez les rôles : responsable, gestionnaire, collaborateur.</CardDescription>
                                </div>
                                <span class="inline-flex items-center gap-1.5 text-xs text-muted-foreground">
                                    <Users class="size-3.5" aria-hidden="true" />
                                    {{ business.members.length }}
                                </span>
                            </div>
                        </CardHeader>

                        <CardContent class="space-y-4">
                            <ul class="space-y-2">
                                <li
                                    v-for="member in business.members"
                                    :key="member.id"
                                    class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-border/80 bg-background px-3.5 py-3"
                                >
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-foreground">{{ member.name }}</p>
                                        <p class="truncate text-xs text-muted-foreground">{{ member.email }}</p>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <select
                                            v-if="canManageMembers"
                                            :value="member.role"
                                            class="h-9 rounded-lg border border-input bg-background px-2.5 text-xs text-foreground"
                                            :aria-label="`Rôle de ${member.name}`"
                                            @change="updateRole(member, ($event.target as HTMLSelectElement).value)"
                                        >
                                            <option v-for="role in roles" :key="role.value" :value="role.value">
                                                {{ role.label }}
                                            </option>
                                        </select>
                                        <span v-else class="text-xs text-muted-foreground">{{ member.role_label }}</span>

                                        <button
                                            v-if="canManageMembers"
                                            type="button"
                                            class="text-muted-foreground transition-colors hover:text-destructive"
                                            :aria-label="`Retirer ${member.name}`"
                                            @click="removeMember(member)"
                                        >
                                            <Trash2 class="size-4" aria-hidden="true" />
                                        </button>
                                    </div>
                                </li>
                            </ul>

                            <div v-if="canManageMembers" class="space-y-3 border-t border-border/70 pt-4">
                                <div class="flex flex-wrap items-end gap-2">
                                    <div class="min-w-[200px] flex-1">
                                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.1em] text-muted-foreground">
                                            Rechercher un compte
                                        </label>
                                        <Input v-model="search" placeholder="Nom ou adresse e-mail" @keyup.enter="runSearch" />
                                    </div>
                                    <Button type="button" variant="outline" :disabled="searching" @click="runSearch">
                                        <LoaderCircle v-if="searching" class="size-4 animate-spin" aria-hidden="true" />
                                        <UserPlus v-else class="size-4" aria-hidden="true" />
                                        Rechercher
                                    </Button>
                                </div>

                                <ul v-if="results.length" class="space-y-2">
                                    <li
                                        v-for="result in results"
                                        :key="result.id"
                                        class="flex items-center justify-between gap-3 rounded-lg border border-border/80 bg-background px-3.5 py-2.5"
                                    >
                                        <div class="min-w-0">
                                            <p class="truncate text-sm text-foreground">{{ result.name }}</p>
                                            <p class="truncate text-xs text-muted-foreground">{{ result.email }}</p>
                                        </div>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            :disabled="memberForm.processing"
                                            @click="inviteMember(result.id)"
                                        >
                                            <Plus class="size-3.5" aria-hidden="true" />
                                            Ajouter
                                        </Button>
                                    </li>
                                </ul>

                                <p v-if="memberForm.errors.user_id" class="text-xs text-destructive">{{ memberForm.errors.user_id }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <CardTitle class="text-base">Profils professionnels</CardTitle>
                                    <CardDescription>Les profils portés par l’entreprise.</CardDescription>
                                </div>
                                <span class="inline-flex items-center gap-1.5 text-xs text-muted-foreground">
                                    <Store class="size-3.5" aria-hidden="true" />
                                    {{ business.profiles.length }}
                                </span>
                            </div>
                        </CardHeader>

                        <CardContent class="space-y-3">
                            <p v-if="business.profiles.length === 0" class="text-sm text-muted-foreground">
                                Aucun profil n’est encore rattaché à cette entreprise.
                            </p>

                            <ul v-else class="space-y-2">
                                <li
                                    v-for="profile in business.profiles"
                                    :key="profile.id"
                                    class="flex items-center justify-between gap-3 rounded-lg border border-border/80 bg-background px-3.5 py-3"
                                >
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-foreground">{{ profile.business_name }}</p>
                                        <p v-if="profile.category" class="truncate text-xs text-muted-foreground">{{ profile.category }}</p>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-2">
                                        <StatusBadge :tone="tone(profile.status_tone)" :label="profile.status_label" />
                                        <Button as-child variant="ghost" size="sm">
                                            <Link :href="route('pro.profiles.show', profile.id)">Gérer</Link>
                                        </Button>
                                    </div>
                                </li>
                            </ul>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
