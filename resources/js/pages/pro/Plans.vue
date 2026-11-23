<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowRight, CheckCircle2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

type Owner = { key: string; type: string; id: number; name: string; subtitle: string };

type SubscriptionData = {
    id: number;
    plan: { id: number; code: string; name: string } | null;
    status: string;
    status_label: string;
    status_tone: string;
    billing_cycle: string | null;
    amount: number;
    currency: string;
    formatted_amount: string;
    starts_at: string | null;
    ends_at: string | null;
    days_remaining: number | null;
    cancel_at_period_end: boolean;
    has_scheduled_change: boolean;
    pending_plan: { id: number; code: string; name: string } | null;
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
    trial_eligible: boolean;
    is_free: boolean;
    is_current: boolean;
    is_upgrade: boolean;
    is_downgrade: boolean;
    features: Array<{ key: string; label: string; enabled: boolean }>;
};

type Props = {
    owners: Owner[];
    selectedOwner: Owner | null;
    subscription: SubscriptionData | null;
    currentPlan: PlanPayload;
    plans: PlanPayload[];
    paymentInstructions: string;
    paymentMethods: Array<{ value: string; label: string }>;
    billingCycles: Array<{ value: string; label: string }>;
    proofMaxKb: number;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Mon abonnement', href: '/pro/abonnement' },
    { title: 'Formules', href: '/pro/abonnement/offres' },
];

const selectedOwnerKey = ref(props.selectedOwner?.key ?? props.owners[0]?.key ?? '');
const selectedOwner = computed(() => props.owners.find((o) => o.key === selectedOwnerKey.value) ?? props.owners[0] ?? null);

const changeOwner = () => {
    if (!selectedOwner.value) return;
    router.get(
        route('pro.subscription.plans'),
        {
            owner_type: selectedOwner.value.type,
            owner_id: selectedOwner.value.id,
        },
        { preserveState: true, preserveScroll: true },
    );
};

const subscribeOpen = ref(false);
const selectedPlan = ref<PlanPayload | null>(null);
const trialMode = computed(() => selectedPlan.value?.trial_eligible === true);

const subscribeForm = useForm({
    owner_type: props.selectedOwner?.type ?? 'profile',
    owner_id: props.selectedOwner?.id ?? 0,
    plan_id: 0,
    billing_cycle: 'monthly',
    method: 'mobile_money',
    external_reference: '',
    notes: '',
    proof: null as File | null,
});

const openSubscribe = (plan: PlanPayload) => {
    selectedPlan.value = plan;
    subscribeForm.plan_id = plan.id;
    subscribeForm.owner_type = selectedOwner?.value?.type ?? 'profile';
    subscribeForm.owner_id = selectedOwner?.value?.id ?? 0;
    subscribeForm.external_reference = '';
    subscribeForm.proof = null;
    subscribeOpen.value = true;
};

const submitSubscribe = () => {
    subscribeForm.post(route('pro.subscription.subscribe'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            subscribeOpen.value = false;
        },
    });
};

const requestFree = () => {
    if (!props.subscription) return;
    router.post(route('pro.subscription.free', props.subscription.id), {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="Formules" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader
                eyebrow="Espace professionnel"
                title="Formules disponibles"
                description="Comparez les formules et choisissez celle qui correspond à votre activité."
            />

            <Card v-if="props.owners.length > 1">
                <CardHeader>
                    <CardTitle class="text-base">Propriétaire</CardTitle>
                    <CardDescription>Sélectionnez le profil ou l'entreprise à abonner.</CardDescription>
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

            <div class="grid gap-6 md:grid-cols-3">
                <article
                    v-for="plan in props.plans"
                    :key="plan.id"
                    class="rounded-2xl border p-6 shadow-soft transition-all duration-300 hover:-translate-y-1 hover:shadow-card"
                    :class="[
                        plan.is_current ? 'border-brand-300 bg-brand-50/50 dark:border-brand-700 dark:bg-brand-950/30' : 'border-border/70 bg-card',
                    ]"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="font-display text-xl font-semibold text-foreground">{{ plan.name }}</h3>
                            <p class="text-xs text-muted-foreground">
                                {{ plan.is_free ? 'Gratuit' : `${plan.formatted_monthly} /mois` }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-semibold" :class="plan.is_current ? 'text-brand-700' : 'text-foreground'">
                                {{ plan.is_free ? '0' : plan.formatted_monthly.replace(' FCFA', '') }}
                            </p>
                            <p class="text-xs text-muted-foreground">par mois</p>
                            <p v-if="plan.trial_eligible" class="mt-1 text-xs font-medium text-success">
                                {{ plan.trial_days }} jours d'essai gratuit
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 space-y-3">
                        <div class="flex items-center gap-2 text-sm">
                            <CheckCircle2 class="size-4 text-success" aria-hidden="true" />
                            <span>{{ plan.max_profiles }} profil{{ plan.max_profiles > 1 ? 's' : '' }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm">
                            <CheckCircle2 class="size-4 text-success" aria-hidden="true" />
                            <span>{{ plan.unlimited_services ? 'Services illimités' : `${plan.max_services} services` }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm">
                            <CheckCircle2 class="size-4 text-success" aria-hidden="true" />
                            <span>{{
                                plan.unlimited_pricing_sheets
                                    ? 'Fiches illimitées'
                                    : `${plan.max_pricing_sheets} fiche${plan.max_pricing_sheets > 1 ? 's' : ''}`
                            }}</span>
                        </div>
                        <div
                            v-for="feature in plan.features"
                            :key="feature.key"
                            class="flex items-center gap-2 text-sm"
                            :class="feature.enabled ? 'text-foreground' : 'text-muted-foreground/60'"
                        >
                            <CheckCircle2 v-if="feature.enabled" class="size-4 text-success" aria-hidden="true" />
                            <span v-else class="size-4 text-muted-foreground/30">✕</span>
                            <span>{{ feature.label }}</span>
                        </div>
                    </div>

                    <div class="mt-6">
                        <div
                            v-if="plan.is_current"
                            class="rounded-lg bg-brand-50 px-3 py-2 text-center text-sm font-medium text-brand-700 dark:bg-brand-950/40 dark:text-brand-300"
                        >
                            Formule actuelle
                        </div>
                        <Button v-else-if="!plan.is_free" variant="brand" size="sm" class="h-11 w-full" @click="openSubscribe(plan)">
                            {{ plan.trial_eligible ? "Démarrer l'essai gratuit" : plan.is_upgrade ? 'Passer à cette formule' : 'Choisir cette formule' }}
                            <ArrowRight class="size-4" aria-hidden="true" />
                        </Button>
                        <Button v-else variant="outline" size="sm" class="h-11 w-full" @click="requestFree"> Retour à FREE </Button>
                    </div>
                </article>
            </div>

            <p class="text-center text-sm text-muted-foreground">
                Aucun engagement. Vous pouvez changer de formule à tout moment. Les formules payantes incluent un essai gratuit de 14 jours.
            </p>

            <Dialog v-model:open="subscribeOpen">
                <DialogScrollContent class="max-w-lg">
                    <DialogTitle>
                        {{ trialMode ? `Démarrer l'essai gratuit ${selectedPlan?.name}` : `Souscrire à ${selectedPlan?.name}` }}
                    </DialogTitle>
                    <DialogDescription>
                        {{
                            trialMode
                                ? `Essai gratuit de ${selectedPlan?.trial_days} jours, sans paiement. Vous pourrez régler votre abonnement avant la fin de l'essai.`
                                : props.paymentInstructions
                        }}
                    </DialogDescription>

                    <form class="grid gap-4" @submit.prevent="submitSubscribe">
                        <div
                            v-if="trialMode"
                            class="rounded-xl border border-success/30 bg-success/5 p-3 text-sm text-foreground"
                        >
                            <p class="font-medium">Aucun paiement requis aujourd'hui.</p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Votre formule {{ selectedPlan?.name }} est active pendant {{ selectedPlan?.trial_days }} jours. Vous serez invité à déposer une preuve de paiement avant la fin de l'essai pour continuer.
                            </p>
                        </div>

                        <div class="grid gap-1.5">
                            <Label for="billing_cycle">Périodicité</Label>
                            <select
                                id="billing_cycle"
                                v-model="subscribeForm.billing_cycle"
                                class="h-11 w-full rounded-lg border border-input bg-background px-3.5 text-sm text-foreground"
                            >
                                <option v-for="cycle in props.billingCycles" :key="cycle.value" :value="cycle.value">{{ cycle.label }}</option>
                            </select>
                        </div>

                        <template v-if="!trialMode">
                            <div class="grid gap-1.5">
                                <Label for="method">Moyen de paiement</Label>
                                <select
                                    id="method"
                                    v-model="subscribeForm.method"
                                    class="h-11 w-full rounded-lg border border-input bg-background px-3.5 text-sm text-foreground"
                                >
                                    <option v-for="m in props.paymentMethods" :key="m.value" :value="m.value">{{ m.label }}</option>
                                </select>
                            </div>

                            <div class="grid gap-1.5">
                                <Label for="external_reference">Référence de transaction</Label>
                                <Input id="external_reference" v-model="subscribeForm.external_reference" placeholder="Ex: OM-123456" />
                                <p v-if="subscribeForm.errors.external_reference" class="text-xs text-destructive">
                                    {{ subscribeForm.errors.external_reference }}
                                </p>
                            </div>

                            <div class="grid gap-1.5">
                                <Label for="proof">Preuve de paiement</Label>
                                <p class="text-xs text-muted-foreground">
                                    Joignez une capture ou un reçu lisible pour vérification par l'administration.
                                </p>
                                <Input id="proof" type="file" @change="subscribeForm.proof = ($event.target as HTMLInputElement).files?.[0] ?? null" />
                                <p v-if="subscribeForm.errors.proof" class="text-xs text-destructive">{{ subscribeForm.errors.proof }}</p>
                            </div>
                        </template>

                        <div class="grid gap-1.5">
                            <Label for="notes">Notes (facultatif)</Label>
                            <Textarea id="notes" v-model="subscribeForm.notes" rows="2" />
                        </div>

                        <DialogFooter class="gap-2">
                            <Button type="button" variant="outline" @click="subscribeOpen = false">Annuler</Button>
                            <Button type="submit" variant="brand" :disabled="subscribeForm.processing">
                                {{ subscribeForm.processing ? 'Envoi…' : trialMode ? "Démarrer l'essai" : 'Souscrire' }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogScrollContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
