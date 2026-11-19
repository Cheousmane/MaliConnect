<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, StatusTone } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowRight, CalendarClock, ShieldOff } from 'lucide-vue-next';
import { computed, ref } from 'vue';

type Owner = { key: string; type: string; id: number; name: string; subtitle: string };

type SubscriptionData = {
    id: number;
    plan: { id: number; code: string; name: string } | null;
    status: string;
    status_label: string;
    status_tone: string;
    billing_cycle: string | null;
    billing_cycle_label: string;
    amount: number;
    currency: string;
    formatted_amount: string;
    starts_at: string | null;
    ends_at: string | null;
    activated_at: string | null;
    grace_ends_at: string | null;
    trial_ends_at: string | null;
    cancelled_at: string | null;
    days_remaining: number | null;
    cancel_at_period_end: boolean;
    cancellation_reason: string | null;
    is_active: boolean;
    grants_access: boolean;
    has_scheduled_change: boolean;
    pending_plan: { id: number; code: string; name: string } | null;
    pending_plan_effective_at: string | null;
    created_at: string | null;
};

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
    plan: string | null;
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
    rejection_reason: string | null;
    declared_at: string | null;
    paid_at: string | null;
    created_at: string | null;
    proofs: Array<{ id: number; original_name: string; formatted_size: string; download_url: string }>;
};

type HistoryRow = {
    id: number;
    event: string;
    event_label: string;
    from_status: string | null;
    to_status: string | null;
    from_plan: string | null;
    to_plan: string | null;
    amount: number;
    currency: string | null;
    note: string | null;
    actor: string | null;
    created_at: string | null;
};

type PlanPayload = {
    id: number;
    code: string;
    name: string;
    description: string | null;
    price_monthly: number;
    price_yearly: number;
    currency: string;
    formatted_monthly: string;
    formatted_yearly: string;
    max_services: number;
    max_media: number;
    max_profiles: number;
    max_pricing_sheets: number;
    unlimited_services: boolean;
    unlimited_pricing_sheets: boolean;
    trial_days: number;
    is_free: boolean;
    features: FeatureRow[];
};

type Props = {
    owners: Owner[];
    selectedOwner: Owner | null;
    subscription: SubscriptionData | null;
    currentPlan: PlanPayload;
    limits: LimitRow[];
    features: FeatureRow[];
    history: HistoryRow[];
    payments: PaymentRow[];
    pendingPayment: PaymentRow | null;
    paymentInstructions: string;
    paymentMethods: Array<{ value: string; label: string }>;
    billingCycles: Array<{ value: string; label: string }>;
    proofMaxKb: number;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Mon abonnement', href: '/pro/abonnement' }];

const tone = (value: string): StatusTone =>
    ['neutral', 'brand', 'success', 'warning', 'danger', 'info'].includes(value) ? (value as StatusTone) : 'neutral';

const formatDate = (value: string | null): string => {
    if (!value) return '—';
    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
};

const selectedOwnerKey = ref(props.selectedOwner?.key ?? props.owners[0]?.key ?? '');
const selectedOwner = computed(() => props.owners.find((o) => o.key === selectedOwnerKey.value) ?? props.owners[0] ?? null);

const changeOwner = () => {
    if (!selectedOwner.value) return;
    router.get(
        route('pro.subscription.index'),
        {
            owner_type: selectedOwner.value.type,
            owner_id: selectedOwner.value.id,
        },
        { preserveState: true, preserveScroll: true },
    );
};

const cancelOpen = ref(false);
const cancelForm = useForm({ reason: '' });

const cancelSubscription = () => {
    if (!props.subscription) return;
    cancelForm.patch(route('pro.subscription.cancel', props.subscription.id), {
        preserveScroll: true,
        onSuccess: () => {
            cancelOpen.value = false;
        },
    });
};

const requestFree = () => {
    if (!props.subscription) return;
    router.post(route('pro.subscription.free', props.subscription.id), {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="Mon abonnement" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader eyebrow="Espace professionnel" title="Mon abonnement" description="Gérez votre formule, vos limites et vos paiements." />

            <Card v-if="props.owners.length > 1">
                <CardHeader>
                    <CardTitle class="text-base">Propriétaire</CardTitle>
                    <CardDescription>Sélectionnez le profil ou l'entreprise à gérer.</CardDescription>
                </CardHeader>
                <CardContent>
                    <select
                        v-model="selectedOwnerKey"
                        @change="changeOwner"
                        class="h-11 w-full rounded-lg border border-input bg-background px-3.5 text-sm text-foreground"
                    >
                        <option v-for="owner in props.owners" :key="owner.key" :value="owner.key">{{ owner.name }} — {{ owner.subtitle }}</option>
                    </select>
                </CardContent>
            </Card>

            <div class="grid gap-4 lg:grid-cols-3">
                <Card class="lg:col-span-2">
                    <CardHeader class="flex flex-row items-start justify-between gap-3">
                        <div>
                            <CardTitle class="text-base">Formule actuelle</CardTitle>
                            <CardDescription>{{ props.currentPlan.name }}</CardDescription>
                        </div>
                        <StatusBadge
                            v-if="props.subscription"
                            :tone="tone(props.subscription.status_tone)"
                            :label="props.subscription.status_label"
                            size="md"
                        />
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Formule</p>
                                <p class="mt-1 text-sm font-semibold text-foreground">{{ props.currentPlan.name }}</p>
                                <p class="text-xs text-muted-foreground">{{ props.currentPlan.description }}</p>
                            </div>
                            <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Montant</p>
                                <p class="mt-1 text-sm font-semibold text-foreground">
                                    {{ props.subscription ? props.subscription.formatted_amount : '0 FCFA' }}
                                </p>
                                <p class="text-xs text-muted-foreground">{{ props.subscription?.billing_cycle_label ?? '—' }}</p>
                            </div>
                            <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Jours restants</p>
                                <p class="mt-1 text-sm font-semibold text-foreground">
                                    {{ props.subscription?.days_remaining === null ? 'Illimité' : (props.subscription?.days_remaining ?? '—') }}
                                </p>
                            </div>
                            <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Début</p>
                                <p class="mt-1 text-sm font-semibold text-foreground">{{ formatDate(props.subscription?.starts_at ?? null) }}</p>
                            </div>
                            <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Échéance</p>
                                <p class="mt-1 text-sm font-semibold text-foreground">{{ formatDate(props.subscription?.ends_at ?? null) }}</p>
                            </div>
                            <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Essai jusqu'au</p>
                                <p class="mt-1 text-sm font-semibold text-foreground">{{ formatDate(props.subscription?.trial_ends_at ?? null) }}</p>
                            </div>
                        </div>

                        <div
                            v-if="props.subscription?.has_scheduled_change && props.subscription?.pending_plan"
                            class="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200"
                        >
                            <CalendarClock class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                            <span
                                >Passage à la formule <strong>{{ props.subscription.pending_plan.name }}</strong> programmé.</span
                            >
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <Button as-child variant="brand" size="sm">
                                <Link :href="route('pro.subscription.plans', { owner_type: selectedOwner?.type, owner_id: selectedOwner?.id })">
                                    <ArrowRight class="size-4" aria-hidden="true" />
                                    Changer de formule
                                </Link>
                            </Button>
                            <Button
                                v-if="props.subscription && !props.subscription.cancel_at_period_end"
                                variant="outline"
                                size="sm"
                                @click="requestFree"
                            >
                                Retour à FREE
                            </Button>
                            <Button
                                v-if="props.subscription && !['cancelled', 'expired'].includes(props.subscription.status)"
                                variant="ghost"
                                size="sm"
                                @click="cancelOpen = true"
                            >
                                <ShieldOff class="size-4" aria-hidden="true" />
                                Résilier
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Limites</CardTitle>
                        <CardDescription>Utilisation actuelle.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div v-for="limit in props.limits" :key="limit.key" class="space-y-1.5">
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
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Fonctionnalités</CardTitle>
                    <CardDescription>Options ouvertes par votre formule.</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="feature in props.features"
                            :key="feature.key"
                            class="flex items-center gap-2 rounded-lg border border-border/60 px-2.5 py-2 text-sm"
                            :class="feature.enabled ? 'text-foreground' : 'text-muted-foreground/60'"
                        >
                            <span class="size-2 rounded-full" :class="feature.enabled ? 'bg-success' : 'bg-muted-foreground/30'" aria-hidden="true" />
                            {{ feature.label }}
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Paiements</CardTitle>
                    <CardDescription>Historique de vos paiements.</CardDescription>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="props.payments.length === 0"
                        class="rounded-xl border border-dashed border-border px-4 py-8 text-center text-sm text-muted-foreground"
                    >
                        Aucun paiement enregistré.
                    </div>
                    <div v-else class="divide-y divide-border/60">
                        <div v-for="payment in props.payments" :key="payment.id" class="flex flex-wrap items-center justify-between gap-3 py-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-foreground">{{ payment.reference }}</p>
                                <p class="truncate text-xs text-muted-foreground">
                                    {{ payment.method_label }} · {{ formatDate(payment.declared_at) }} · {{ payment.proofs.length }} preuve{{
                                        payment.proofs.length > 1 ? 's' : ''
                                    }}
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-3">
                                <span class="text-sm font-semibold text-foreground">{{ payment.formatted_amount }}</span>
                                <StatusBadge :tone="tone(payment.status_tone)" :label="payment.status_label" />
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Historique</CardTitle>
                    <CardDescription>Événements de votre abonnement.</CardDescription>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="props.history.length === 0"
                        class="rounded-xl border border-dashed border-border px-4 py-8 text-center text-sm text-muted-foreground"
                    >
                        Aucun événement.
                    </div>
                    <ol v-else class="relative space-y-4 border-l border-border/70 pl-5">
                        <li v-for="history in props.history" :key="history.id" class="relative">
                            <span
                                class="absolute -left-[26px] top-1 flex size-3 items-center justify-center rounded-full border-2 border-background bg-brand-500"
                            />
                            <div class="flex flex-wrap items-baseline justify-between gap-2">
                                <p class="text-sm font-medium text-foreground">{{ history.event_label }}</p>
                                <p class="text-xs text-muted-foreground">{{ formatDate(history.created_at) }}</p>
                            </div>
                            <p class="text-xs text-muted-foreground">{{ history.from_plan ?? '—' }} → {{ history.to_plan ?? '—' }}</p>
                            <p v-if="history.note" class="mt-1 text-sm text-foreground">{{ history.note }}</p>
                        </li>
                    </ol>
                </CardContent>
            </Card>

            <Dialog v-model:open="cancelOpen">
                <DialogScrollContent class="max-w-lg">
                    <DialogTitle>Résilier l'abonnement</DialogTitle>
                    <DialogDescription> La période déjà réglée reste acquise jusqu'à son terme. </DialogDescription>
                    <form class="grid gap-4" @submit.prevent="cancelSubscription">
                        <div class="grid gap-1.5">
                            <Label for="cancel-reason">Motif (facultatif)</Label>
                            <Textarea id="cancel-reason" v-model="cancelForm.reason" rows="3" />
                        </div>
                        <DialogFooter class="gap-2">
                            <Button type="button" variant="outline" @click="cancelOpen = false">Annuler</Button>
                            <Button type="submit" variant="destructive" :disabled="cancelForm.processing">Résilier</Button>
                        </DialogFooter>
                    </form>
                </DialogScrollContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
