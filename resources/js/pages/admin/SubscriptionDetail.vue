<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogDescription, DialogFooter, DialogScrollContent, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import type { BreadcrumbItem, StatusTone } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CalendarClock, CalendarPlus, CircleCheck, CircleSlash, CreditCard, History, ShieldOff } from 'lucide-vue-next';
import { computed, ref } from 'vue';

type LimitRow = {
    key: string;
    label: string;
    used: number;
    limit: number | null;
    remaining: number | null;
    unlimited: boolean;
    reached: boolean;
    percentage: number | null;
};

type FeatureRow = { key: string; label: string; enabled: boolean };

type PaymentRow = {
    id: number;
    reference: string;
    external_reference: string | null;
    amount: number;
    currency: string;
    formatted_amount: string;
    method: string;
    method_label: string;
    status: string;
    status_label: string;
    status_tone: string;
    proofs_count: number;
    declared_at: string | null;
    paid_at: string | null;
    url: string;
};

type HistoryRow = {
    id: number;
    event: string;
    event_label: string;
    from_status: string | null;
    from_status_label: string | null;
    to_status: string | null;
    to_status_label: string | null;
    from_plan: string | null;
    to_plan: string | null;
    billing_cycle: string | null;
    amount: number;
    currency: string | null;
    note: string | null;
    actor: string | null;
    created_at: string | null;
};

type SubscriptionDetail = {
    id: number;
    plan: { id: number | null; code: string; name: string; price_monthly?: number; price_yearly?: number; currency?: string };
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
    owner: {
        type: string;
        id: number | null;
        name: string;
        slug: string | null;
        manager: string | null;
        email: string | null;
    };
    owner_label: string;
    owner_type: string;
    starts_at: string | null;
    ends_at: string | null;
    activated_at: string | null;
    cancelled_at: string | null;
    suspended_at: string | null;
    grace_ends_at: string | null;
    trial_ends_at: string | null;
    renewed_at: string | null;
    last_payment_at: string | null;
    cancel_at_period_end: boolean;
    cancellation_reason: string | null;
    notes: string | null;
    days_remaining: number | null;
    features: FeatureRow[];
    limits: LimitRow[];
    payments: PaymentRow[];
    histories: HistoryRow[];
    created_at: string | null;
};

type Props = { subscription: SubscriptionDetail };

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: '/admin' },
    { title: 'Abonnements', href: '/admin/abonnements' },
    { title: props.subscription.owner_label, href: `/admin/abonnements/${props.subscription.id}` },
];

const tone = (value: string): StatusTone =>
    ['neutral', 'brand', 'success', 'warning', 'danger', 'info'].includes(value) ? (value as StatusTone) : 'neutral';

const formatDate = (value: string | null): string => {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium' }).format(new Date(value));
};

const formatDateTime = (value: string | null): string => {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
};

const formatMoney = (value: number, currency: string | null): string => `${new Intl.NumberFormat('fr-FR').format(value)} ${currency ?? 'XOF'}`;

const toLocalInput = (value: string | null): string => {
    const date = value ? new Date(value) : new Date(Date.now() + 30 * 24 * 60 * 60 * 1000);
    const pad = (part: number) => String(part).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
};

const suspendOpen = ref(false);
const cancelOpen = ref(false);
const extendOpen = ref(false);
const reactivating = ref(false);

const suspendForm = useForm({ reason: '' });
const cancelForm = useForm({ reason: '' });
const extendForm = useForm({
    ends_at: toLocalInput(props.subscription.ends_at),
    note: '',
});

const canSuspend = computed(() => props.subscription.status !== 'suspended' && props.subscription.status !== 'cancelled');
const canReactivate = computed(() => ['suspended', 'expired', 'cancelled'].includes(props.subscription.status));
const canCancel = computed(() => !['cancelled', 'expired'].includes(props.subscription.status));

const openSuspend = () => {
    suspendForm.reset();
    suspendForm.clearErrors();
    suspendOpen.value = true;
};

const submitSuspend = () => {
    suspendForm.patch(route('admin.subscriptions.suspend', props.subscription.id), {
        preserveScroll: true,
        onSuccess: () => {
            suspendOpen.value = false;
        },
    });
};

const openCancel = () => {
    cancelForm.reset();
    cancelForm.clearErrors();
    cancelOpen.value = true;
};

const submitCancel = () => {
    cancelForm.patch(route('admin.subscriptions.cancel', props.subscription.id), {
        preserveScroll: true,
        onSuccess: () => {
            cancelOpen.value = false;
        },
    });
};

const openExtend = () => {
    extendForm.ends_at = toLocalInput(props.subscription.ends_at);
    extendForm.note = '';
    extendForm.clearErrors();
    extendOpen.value = true;
};

const submitExtend = () => {
    extendForm.patch(route('admin.subscriptions.extend', props.subscription.id), {
        preserveScroll: true,
        onSuccess: () => {
            extendOpen.value = false;
        },
    });
};

const reactivate = () => {
    if (reactivating.value) {
        return;
    }

    reactivating.value = true;

    router.patch(
        route('admin.subscriptions.reactivate', props.subscription.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                reactivating.value = false;
            },
        },
    );
};
</script>

<template>
    <Head :title="`Abonnement · ${props.subscription.owner_label}`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader
                eyebrow="Abonnement"
                :title="props.subscription.owner_label"
                :description="`${props.subscription.owner.type === 'business' ? 'Entreprise' : 'Profil indépendant'} · ${props.subscription.plan_name} · ${props.subscription.billing_cycle_label}`"
            >
                <template #actions>
                    <Button as-child variant="outline">
                        <Link :href="route('admin.subscriptions.index')">
                            <ArrowLeft class="size-4" aria-hidden="true" />
                            Retour
                        </Link>
                    </Button>
                    <Button v-if="canSuspend" variant="outline" @click="openSuspend">
                        <ShieldOff class="size-4" aria-hidden="true" />
                        Suspendre
                    </Button>
                    <Button v-if="canReactivate" variant="brand" :disabled="reactivating" @click="reactivate">
                        <CircleCheck class="size-4" aria-hidden="true" />
                        Réactiver
                    </Button>
                    <Button v-if="canCancel" variant="ghost" @click="openCancel">
                        <CircleSlash class="size-4" aria-hidden="true" />
                        Résilier
                    </Button>
                    <Button variant="soft" @click="openExtend">
                        <CalendarPlus class="size-4" aria-hidden="true" />
                        Prolonger
                    </Button>
                </template>
            </PageHeader>

            <div class="grid gap-4 lg:grid-cols-3">
                <Card class="lg:col-span-2">
                    <CardHeader class="flex flex-row items-start justify-between gap-3">
                        <div>
                            <CardTitle class="text-base">Situation de l’abonnement</CardTitle>
                            <CardDescription>Statut réel, échéances et droits effectifs.</CardDescription>
                        </div>
                        <StatusBadge :tone="tone(props.subscription.status_tone)" :label="props.subscription.status_label" size="md" />
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Formule</p>
                                <p class="mt-1 text-sm font-semibold text-foreground">{{ props.subscription.plan_name }}</p>
                                <p class="text-xs text-muted-foreground">{{ props.subscription.billing_cycle_label }}</p>
                            </div>
                            <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Montant contractuel</p>
                                <p class="mt-1 text-sm font-semibold text-foreground">{{ props.subscription.formatted_amount }}</p>
                                <p class="text-xs text-muted-foreground">{{ props.subscription.currency }}</p>
                            </div>
                            <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Jours restants</p>
                                <p class="mt-1 text-sm font-semibold text-foreground">
                                    {{ props.subscription.days_remaining === null ? 'Illimité' : props.subscription.days_remaining }}
                                </p>
                                <p class="text-xs text-muted-foreground">Avant échéance</p>
                            </div>
                            <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Début</p>
                                <p class="mt-1 text-sm font-semibold text-foreground">{{ formatDate(props.subscription.starts_at) }}</p>
                                <p class="text-xs text-muted-foreground">Activé le {{ formatDate(props.subscription.activated_at) }}</p>
                            </div>
                            <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Échéance</p>
                                <p class="mt-1 text-sm font-semibold text-foreground">{{ formatDate(props.subscription.ends_at) }}</p>
                                <p class="text-xs text-muted-foreground">Grâce jusqu’au {{ formatDate(props.subscription.grace_ends_at) }}</p>
                            </div>
                            <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Dernier paiement</p>
                                <p class="mt-1 text-sm font-semibold text-foreground">{{ formatDate(props.subscription.last_payment_at) }}</p>
                                <p class="text-xs text-muted-foreground">Renouvelé le {{ formatDate(props.subscription.renewed_at) }}</p>
                            </div>
                        </div>

                        <div
                            v-if="props.subscription.has_scheduled_change && props.subscription.pending_plan"
                            class="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200"
                        >
                            <CalendarClock class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                            <span>
                                Passage à la formule <strong>{{ props.subscription.pending_plan.name }}</strong> programmé
                                <template v-if="props.subscription.pending_plan_effective_at">
                                    pour le {{ formatDateTime(props.subscription.pending_plan_effective_at) }}
                                </template>
                                .
                            </span>
                        </div>

                        <div
                            v-if="props.subscription.cancel_at_period_end"
                            class="rounded-xl border border-border bg-muted/30 px-3 py-2.5 text-sm text-muted-foreground"
                        >
                            La résiliation prendra effet à la fin de la période payée. La période en cours reste acquise.
                        </div>

                        <div v-if="props.subscription.cancellation_reason" class="rounded-xl border border-border/70 p-3 text-sm">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Motif de résiliation</p>
                            <p class="mt-1 text-foreground">{{ props.subscription.cancellation_reason }}</p>
                        </div>

                        <div v-if="props.subscription.notes" class="rounded-xl border border-border/70 p-3 text-sm">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Notes internes</p>
                            <p class="mt-1 whitespace-pre-line text-foreground">{{ props.subscription.notes }}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Propriétaire</CardTitle>
                        <CardDescription>Responsable de la relation commerciale.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm">
                        <div>
                            <p class="text-xs text-muted-foreground">Nom</p>
                            <p class="font-medium text-foreground">{{ props.subscription.owner.name }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Type</p>
                            <p class="font-medium text-foreground">
                                {{ props.subscription.owner.type === 'business' ? 'Entreprise' : 'Profil indépendant' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Gestionnaire</p>
                            <p class="font-medium text-foreground">{{ props.subscription.owner.manager ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">E-mail</p>
                            <p class="break-all font-medium text-foreground">{{ props.subscription.owner.email ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Créé le</p>
                            <p class="font-medium text-foreground">{{ formatDateTime(props.subscription.created_at) }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Limites utilisées</CardTitle>
                        <CardDescription>Les données existantes sont conservées, même au-delà de la limite.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div v-for="limit in props.subscription.limits" :key="limit.key" class="space-y-1.5">
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="font-medium text-foreground">{{ limit.label }}</span>
                                <span class="text-muted-foreground"> {{ limit.used }} / {{ limit.unlimited ? '∞' : limit.limit }} </span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-muted">
                                <div
                                    class="h-full rounded-full transition-all duration-500"
                                    :class="limit.reached ? 'bg-destructive' : 'bg-brand-500'"
                                    :style="{ width: `${limit.unlimited ? 100 : (limit.percentage ?? 0)}%` }"
                                />
                            </div>
                            <p class="text-[11px] text-muted-foreground">
                                <template v-if="limit.unlimited">Illimité sur cette formule.</template>
                                <template v-else-if="limit.reached">Limite atteinte : les nouvelles créations sont bloquées.</template>
                                <template v-else>{{ limit.remaining }} emplacement(s) encore disponible(s).</template>
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Fonctionnalités</CardTitle>
                        <CardDescription>Options ouvertes par la formule effective.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <div
                                v-for="feature in props.subscription.features"
                                :key="feature.key"
                                class="flex items-center gap-2 rounded-lg border border-border/60 px-2.5 py-2 text-sm"
                                :class="feature.enabled ? 'text-foreground' : 'text-muted-foreground/60'"
                            >
                                <span
                                    class="size-2 rounded-full"
                                    :class="feature.enabled ? 'bg-success' : 'bg-muted-foreground/30'"
                                    aria-hidden="true"
                                />
                                {{ feature.label }}
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between gap-3">
                    <div>
                        <CardTitle class="text-base">Paiements</CardTitle>
                        <CardDescription>Seuls les paiements validés comptent dans les revenus.</CardDescription>
                    </div>
                    <CreditCard class="size-5 text-muted-foreground" aria-hidden="true" />
                </CardHeader>
                <CardContent>
                    <div
                        v-if="props.subscription.payments.length === 0"
                        class="rounded-xl border border-dashed border-border px-4 py-8 text-center text-sm text-muted-foreground"
                    >
                        Aucun paiement rattaché.
                    </div>
                    <div v-else class="divide-y divide-border/60">
                        <Link
                            v-for="payment in props.subscription.payments"
                            :key="payment.id"
                            :href="payment.url"
                            class="flex flex-wrap items-center justify-between gap-3 py-3 transition hover:bg-muted/30"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-foreground">{{ payment.reference }}</p>
                                <p class="truncate text-xs text-muted-foreground">
                                    {{ payment.method_label }} · {{ formatDateTime(payment.declared_at) }} · {{ payment.proofs_count }} preuve{{
                                        payment.proofs_count > 1 ? 's' : ''
                                    }}
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-3">
                                <span class="text-sm font-semibold text-foreground">{{ payment.formatted_amount }}</span>
                                <StatusBadge :tone="tone(payment.status_tone)" :label="payment.status_label" />
                            </div>
                        </Link>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between gap-3">
                    <div>
                        <CardTitle class="text-base">Historique</CardTitle>
                        <CardDescription>Chaque événement est immuable et horodaté.</CardDescription>
                    </div>
                    <History class="size-5 text-muted-foreground" aria-hidden="true" />
                </CardHeader>
                <CardContent>
                    <div
                        v-if="props.subscription.histories.length === 0"
                        class="rounded-xl border border-dashed border-border px-4 py-8 text-center text-sm text-muted-foreground"
                    >
                        Aucun événement enregistré.
                    </div>
                    <ol v-else class="relative space-y-4 border-l border-border/70 pl-5">
                        <li v-for="history in props.subscription.histories" :key="history.id" class="relative">
                            <span
                                class="absolute -left-[26px] top-1 flex size-3 items-center justify-center rounded-full border-2 border-background bg-brand-500"
                            />
                            <div class="flex flex-wrap items-baseline justify-between gap-2">
                                <p class="text-sm font-medium text-foreground">{{ history.event_label }}</p>
                                <p class="text-xs text-muted-foreground">{{ formatDateTime(history.created_at) }}</p>
                            </div>
                            <p class="text-xs text-muted-foreground">
                                <template v-if="history.from_plan || history.to_plan">
                                    {{ history.from_plan ?? '—' }} → {{ history.to_plan ?? '—' }}
                                </template>
                                <template v-else-if="history.from_status_label || history.to_status_label">
                                    {{ history.from_status_label ?? '—' }} → {{ history.to_status_label ?? '—' }}
                                </template>
                            </p>
                            <p v-if="history.note" class="mt-1 text-sm text-foreground">{{ history.note }}</p>
                            <p class="mt-0.5 text-[11px] text-muted-foreground">
                                {{ history.actor ?? 'Système' }}
                                <template v-if="history.amount > 0"> · {{ formatMoney(history.amount, history.currency) }}</template>
                            </p>
                        </li>
                    </ol>
                </CardContent>
            </Card>
        </div>

        <Dialog v-model:open="suspendOpen">
            <DialogScrollContent class="max-w-lg">
                <DialogTitle>Suspendre l’abonnement</DialogTitle>
                <DialogDescription>
                    L’accès aux fonctionnalités payantes est immédiatement fermé. Les données du professionnel restent intactes.
                </DialogDescription>

                <form class="grid gap-4" @submit.prevent="submitSuspend">
                    <div class="grid gap-1.5">
                        <Label for="suspend-reason">Motif (facultatif)</Label>
                        <Textarea
                            id="suspend-reason"
                            v-model="suspendForm.reason"
                            rows="3"
                            placeholder="Impayé, fraude suspectée, demande du professionnel…"
                        />
                        <p v-if="suspendForm.errors.reason" class="text-xs text-destructive">{{ suspendForm.errors.reason }}</p>
                    </div>
                    <DialogFooter class="gap-2">
                        <Button type="button" variant="outline" @click="suspendOpen = false">Annuler</Button>
                        <Button type="submit" variant="destructive" :disabled="suspendForm.processing">Suspendre</Button>
                    </DialogFooter>
                </form>
            </DialogScrollContent>
        </Dialog>

        <Dialog v-model:open="cancelOpen">
            <DialogScrollContent class="max-w-lg">
                <DialogTitle>Résilier l’abonnement</DialogTitle>
                <DialogDescription>
                    La période déjà réglée reste acquise jusqu’à son terme. Aucun remboursement n’est effectué automatiquement.
                </DialogDescription>

                <form class="grid gap-4" @submit.prevent="submitCancel">
                    <div class="grid gap-1.5">
                        <Label for="cancel-reason">Motif (facultatif)</Label>
                        <Textarea
                            id="cancel-reason"
                            v-model="cancelForm.reason"
                            rows="3"
                            placeholder="Demande du professionnel, cessation d’activité…"
                        />
                        <p v-if="cancelForm.errors.reason" class="text-xs text-destructive">{{ cancelForm.errors.reason }}</p>
                    </div>
                    <DialogFooter class="gap-2">
                        <Button type="button" variant="outline" @click="cancelOpen = false">Annuler</Button>
                        <Button type="submit" variant="destructive" :disabled="cancelForm.processing">Résilier</Button>
                    </DialogFooter>
                </form>
            </DialogScrollContent>
        </Dialog>

        <Dialog v-model:open="extendOpen">
            <DialogScrollContent class="max-w-lg">
                <DialogTitle>Prolonger l’échéance</DialogTitle>
                <DialogDescription>
                    La nouvelle date remplace l’échéance actuelle. L’abonnement reste ou est remis en accès si nécessaire.
                </DialogDescription>

                <form class="grid gap-4" @submit.prevent="submitExtend">
                    <div class="grid gap-1.5">
                        <Label for="extend-ends-at">Nouvelle échéance</Label>
                        <Input id="extend-ends-at" v-model="extendForm.ends_at" type="datetime-local" />
                        <p v-if="extendForm.errors.ends_at" class="text-xs text-destructive">{{ extendForm.errors.ends_at }}</p>
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="extend-note">Note (facultative)</Label>
                        <Textarea id="extend-note" v-model="extendForm.note" rows="2" placeholder="Geste commercial, régularisation…" />
                        <p v-if="extendForm.errors.note" class="text-xs text-destructive">{{ extendForm.errors.note }}</p>
                    </div>
                    <DialogFooter class="gap-2">
                        <Button type="button" variant="outline" @click="extendOpen = false">Annuler</Button>
                        <Button type="submit" variant="brand" :disabled="extendForm.processing">Prolonger</Button>
                    </DialogFooter>
                </form>
            </DialogScrollContent>
        </Dialog>
    </AdminLayout>
</template>
