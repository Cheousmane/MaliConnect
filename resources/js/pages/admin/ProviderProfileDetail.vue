<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogDescription, DialogFooter, DialogScrollContent, DialogTitle } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import type { BreadcrumbItem, StatusTone } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle, ShieldOff, XCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';

type ProfileDetail = {
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
    category: { id: number; name: string } | string | null;
    user: string | null;
    business: { id: number; name: string; status: string } | string | null;
    phone: string | null;
    email: string | null;
    description: string | null;
    created_at: string | null;
};

type Props = { profile: ProfileDetail };

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: '/admin' },
    { title: 'Profils à vérifier', href: '/admin/provider-profiles' },
    { title: props.profile.business_name, href: `/admin/provider-profiles/${props.profile.id}` },
];

const tone = (value: string): StatusTone =>
    ['neutral', 'brand', 'success', 'warning', 'danger', 'info'].includes(value) ? (value as StatusTone) : 'neutral';

const formatDate = (value: string | null): string => {
    if (!value) return '—';
    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium' }).format(new Date(value));
};

const refuseOpen = ref(false);
const suspendOpen = ref(false);
const reason = ref('');

const canReview = computed(() => props.profile.status === 'pending' || props.profile.status === 'submitted');

const approve = () => {
    router.post(
        route('admin.provider-profiles.review', props.profile.id),
        {
            decision: 'approve',
        },
        { preserveScroll: true },
    );
};

const openRefuse = () => {
    reason.value = '';
    refuseOpen.value = true;
};

const submitRefuse = () => {
    router.post(
        route('admin.provider-profiles.review', props.profile.id),
        {
            decision: 'refuse',
            reason: reason.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                refuseOpen.value = false;
            },
        },
    );
};

const openSuspend = () => {
    reason.value = '';
    suspendOpen.value = true;
};

const submitSuspend = () => {
    router.post(
        route('admin.provider-profiles.review', props.profile.id),
        {
            decision: 'suspend',
            reason: reason.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                suspendOpen.value = false;
            },
        },
    );
};
</script>

<template>
    <Head :title="`Profil · ${props.profile.business_name}`" />
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader
                eyebrow="Profil professionnel"
                :title="props.profile.business_name"
                :description="`${typeof props.profile.category === 'object' && props.profile.category !== null ? props.profile.category.name : (props.profile.category ?? 'Sans catégorie')} · ${props.profile.city}`"
            >
                <template #actions>
                    <Button as-child variant="outline">
                        <Link :href="route('admin.provider-profiles.index')">
                            <ArrowLeft class="size-4" aria-hidden="true" />
                            Retour
                        </Link>
                    </Button>
                    <Button v-if="canReview" variant="brand" @click="approve">
                        <CheckCircle class="size-4" aria-hidden="true" />
                        Approuver
                    </Button>
                    <Button v-if="canReview" variant="outline" @click="openRefuse">
                        <XCircle class="size-4" aria-hidden="true" />
                        Refuser
                    </Button>
                    <Button v-if="props.profile.status === 'verified'" variant="ghost" @click="openSuspend">
                        <ShieldOff class="size-4" aria-hidden="true" />
                        Suspendre
                    </Button>
                </template>
            </PageHeader>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Informations du profil</CardTitle>
                        <CardDescription>Identité et localisation.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Nom</p>
                                <p class="mt-1 font-semibold text-foreground">{{ props.profile.business_name }}</p>
                            </div>
                            <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Slug</p>
                                <p class="mt-1 font-mono text-foreground">{{ props.profile.slug }}</p>
                            </div>
                            <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Catégorie</p>
                                <p class="mt-1 text-foreground">
                                    {{
                                        typeof props.profile.category === 'object' && props.profile.category !== null
                                            ? props.profile.category.name
                                            : (props.profile.category ?? '—')
                                    }}
                                </p>
                            </div>
                            <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Ville</p>
                                <p class="mt-1 text-foreground">
                                    {{ props.profile.city }}<span v-if="props.profile.commune"> · {{ props.profile.commune }}</span>
                                </p>
                            </div>
                            <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Compte</p>
                                <p class="mt-1 text-foreground">{{ props.profile.user ?? '—' }}</p>
                            </div>
                            <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Entreprise</p>
                                <p class="mt-1 text-foreground">
                                    {{
                                        typeof props.profile.business === 'object' && props.profile.business !== null
                                            ? props.profile.business.name
                                            : (props.profile.business ?? '—')
                                    }}
                                </p>
                            </div>
                            <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Statut</p>
                                <div class="mt-1">
                                    <StatusBadge :tone="tone(props.profile.status_tone)" :label="props.profile.status_label" />
                                </div>
                            </div>
                            <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Visibilité</p>
                                <div class="mt-1">
                                    <StatusBadge
                                        :tone="props.profile.is_publicly_visible ? 'success' : 'neutral'"
                                        :label="props.profile.is_publicly_visible ? 'Visible' : 'Masqué'"
                                    />
                                </div>
                            </div>
                        </div>
                        <div v-if="props.profile.description" class="rounded-xl border border-border/70 p-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Description</p>
                            <p class="mt-1 whitespace-pre-line text-foreground">{{ props.profile.description }}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Coordonnées</CardTitle>
                        <CardDescription>Informations de contact.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm">
                        <div>
                            <p class="text-xs text-muted-foreground">Téléphone</p>
                            <p class="font-medium text-foreground">{{ props.profile.phone ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">E-mail</p>
                            <p class="font-medium text-foreground">{{ props.profile.email ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Créé le</p>
                            <p class="font-medium text-foreground">{{ formatDate(props.profile.created_at) }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <Dialog v-model:open="refuseOpen">
            <DialogScrollContent class="max-w-lg">
                <DialogTitle>Refuser le profil</DialogTitle>
                <DialogDescription> Le profil ne sera pas visible publiquement. Un motif est obligatoire. </DialogDescription>
                <form class="grid gap-4" @submit.prevent="submitRefuse">
                    <div class="grid gap-1.5">
                        <Label for="reason">Motif du refus</Label>
                        <Textarea id="reason" v-model="reason" rows="3" placeholder="Documents incomplets, informations inexactes…" />
                    </div>
                    <DialogFooter class="gap-2">
                        <Button type="button" variant="outline" @click="refuseOpen = false">Annuler</Button>
                        <Button type="submit" variant="destructive" :disabled="!reason.trim()">Refuser</Button>
                    </DialogFooter>
                </form>
            </DialogScrollContent>
        </Dialog>

        <Dialog v-model:open="suspendOpen">
            <DialogScrollContent class="max-w-lg">
                <DialogTitle>Suspendre le profil</DialogTitle>
                <DialogDescription> Le profil sera masqué publiquement. </DialogDescription>
                <form class="grid gap-4" @submit.prevent="submitSuspend">
                    <div class="grid gap-1.5">
                        <Label for="suspend-reason">Motif (facultatif)</Label>
                        <Textarea id="suspend-reason" v-model="reason" rows="3" placeholder="Non-conformité, plainte reçue…" />
                    </div>
                    <DialogFooter class="gap-2">
                        <Button type="button" variant="outline" @click="suspendOpen = false">Annuler</Button>
                        <Button type="submit" variant="destructive">Suspendre</Button>
                    </DialogFooter>
                </form>
            </DialogScrollContent>
        </Dialog>
    </AdminLayout>
</template>
