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
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Check, Image, Pencil, Plus, Power, ReceiptText, Store, Users, X } from 'lucide-vue-next';
import { ref } from 'vue';

type PlanFeature = { key: string; label: string; enabled: boolean };

type PlanRow = {
    id: number;
    code: string;
    name: string;
    description: string | null;
    price_monthly: number;
    price_yearly: number;
    currency: string;
    max_services: number;
    max_media: number;
    max_profiles: number;
    max_pricing_sheets: number;
    trial_days: number;
    sort_order: number;
    is_active: boolean;
    subscriptions_count: number;
    active_subscriptions_count: number;
    features: PlanFeature[];
    raw: Record<string, boolean>;
};

type Props = {
    plans: PlanRow[];
    featureOptions: Array<{ key: string; label: string }>;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: '/admin' },
    { title: 'Formules', href: '/admin/formules' },
];

type PlanFormData = {
    code: string;
    name: string;
    description: string;
    price_monthly: number;
    price_yearly: number;
    currency: string;
    max_services: number;
    max_media: number;
    max_profiles: number;
    max_pricing_sheets: number;
    trial_days: number;
    sort_order: number;
    is_active: boolean;
    featureFlags: Record<string, boolean>;
};

const featureDefaults = (): Record<string, boolean> => Object.fromEntries(props.featureOptions.map((feature) => [`has_${feature.key}`, false]));

const form = useForm<PlanFormData>({
    code: '',
    name: '',
    description: '',
    price_monthly: 0,
    price_yearly: 0,
    currency: 'XOF',
    max_services: 3,
    max_media: 0,
    max_profiles: 1,
    max_pricing_sheets: 1,
    trial_days: 0,
    sort_order: 0,
    is_active: true,
    featureFlags: featureDefaults(),
});

form.transform((data) => {
    const { featureFlags, ...rest } = data;

    return { ...rest, ...featureFlags };
});

const dialogOpen = ref(false);
const editing = ref<PlanRow | null>(null);

const formatMoney = (value: number, currency: string): string => `${new Intl.NumberFormat('fr-FR').format(value)} ${currency}`;

const limitLabel = (value: number): string => (value === 0 ? 'Illimité' : String(value));

const fillForm = (plan?: PlanRow): void => {
    editing.value = plan ?? null;
    form.clearErrors();
    form.code = plan?.code ?? '';
    form.name = plan?.name ?? '';
    form.description = plan?.description ?? '';
    form.price_monthly = Number(plan?.price_monthly ?? 0);
    form.price_yearly = Number(plan?.price_yearly ?? 0);
    form.currency = plan?.currency ?? 'XOF';
    form.max_services = Number(plan?.max_services ?? 3);
    form.max_media = Number(plan?.max_media ?? 0);
    form.max_profiles = Number(plan?.max_profiles ?? 1);
    form.max_pricing_sheets = Number(plan?.max_pricing_sheets ?? 1);
    form.trial_days = Number(plan?.trial_days ?? 0);
    form.sort_order = Number(plan?.sort_order ?? 0);
    form.is_active = plan?.is_active ?? true;
    form.featureFlags = plan ? { ...featureDefaults(), ...plan.raw } : featureDefaults();
};

const openCreate = (): void => {
    fillForm();
    dialogOpen.value = true;
};

const openEdit = (plan: PlanRow): void => {
    fillForm(plan);
    dialogOpen.value = true;
};

const submit = (): void => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            dialogOpen.value = false;
        },
    };

    if (editing.value !== null) {
        form.patch(route('admin.plans.update', editing.value.id), options);
    } else {
        form.post(route('admin.plans.store'), options);
    }
};

const toggleActivation = (plan: PlanRow): void => {
    router.patch(route('admin.plans.activation.update', plan.id), { is_active: !plan.is_active }, { preserveScroll: true });
};
</script>

<template>
    <Head title="Formules" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader
                eyebrow="Administration"
                title="Formules commerciales"
                description="Prix, limites et fonctionnalités sont modifiables ici sans redéploiement. Les changements s’appliquent immédiatement aux nouveaux abonnements et aux droits effectifs."
            >
                <template #actions>
                    <Button variant="brand" @click="openCreate">
                        <Plus class="size-4" aria-hidden="true" />
                        Nouvelle formule
                    </Button>
                </template>
            </PageHeader>

            <div v-if="plans.length === 0" class="rounded-xl border border-dashed border-border bg-muted/20 px-6 py-12 text-center">
                <p class="text-sm font-medium text-foreground">Aucune formule configurée</p>
                <p class="mt-1 text-sm text-muted-foreground">Créez au moins une formule pour permettre les souscriptions.</p>
                <Button variant="brand" class="mt-4" @click="openCreate">Créer une formule</Button>
            </div>

            <div v-else class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
                <Card v-for="plan in plans" :key="plan.id" class="flex flex-col">
                    <CardHeader class="gap-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <CardTitle class="text-lg">{{ plan.name }}</CardTitle>
                                    <StatusBadge :tone="plan.is_active ? 'success' : 'neutral'" :label="plan.is_active ? 'Proposée' : 'Retirée'" />
                                </div>
                                <p class="mt-1 font-mono text-xs text-muted-foreground">{{ plan.code }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-display text-lg font-semibold text-foreground">{{ formatMoney(plan.price_monthly, plan.currency) }}</p>
                                <p class="text-xs text-muted-foreground">par mois</p>
                                <p class="mt-1 text-xs text-muted-foreground">{{ formatMoney(plan.price_yearly, plan.currency) }} / an</p>
                            </div>
                        </div>
                        <CardDescription>{{ plan.description || 'Aucune description commerciale.' }}</CardDescription>
                    </CardHeader>

                    <CardContent class="flex flex-1 flex-col gap-4">
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div class="flex items-center gap-2 rounded-lg border border-border/60 px-2.5 py-2">
                                <Users class="size-4 text-muted-foreground" aria-hidden="true" />
                                <span class="text-muted-foreground">Profils</span>
                                <span class="ml-auto font-semibold text-foreground">{{ limitLabel(plan.max_profiles) }}</span>
                            </div>
                            <div class="flex items-center gap-2 rounded-lg border border-border/60 px-2.5 py-2">
                                <Store class="size-4 text-muted-foreground" aria-hidden="true" />
                                <span class="text-muted-foreground">Services</span>
                                <span class="ml-auto font-semibold text-foreground">{{ limitLabel(plan.max_services) }}</span>
                            </div>
                            <div class="flex items-center gap-2 rounded-lg border border-border/60 px-2.5 py-2">
                                <Image class="size-4 text-muted-foreground" aria-hidden="true" />
                                <span class="text-muted-foreground">Photos</span>
                                <span class="ml-auto font-semibold text-foreground">{{ limitLabel(plan.max_media) }}</span>
                            </div>
                            <div class="flex items-center gap-2 rounded-lg border border-border/60 px-2.5 py-2">
                                <ReceiptText class="size-4 text-muted-foreground" aria-hidden="true" />
                                <span class="text-muted-foreground">Fiches</span>
                                <span class="ml-auto font-semibold text-foreground">{{ limitLabel(plan.max_pricing_sheets) }}</span>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <div
                                v-for="feature in plan.features"
                                :key="feature.key"
                                class="flex items-center gap-2 text-sm"
                                :class="feature.enabled ? 'text-foreground' : 'text-muted-foreground/60'"
                            >
                                <component
                                    :is="feature.enabled ? Check : X"
                                    class="size-3.5 shrink-0"
                                    :class="feature.enabled ? 'text-success' : 'text-muted-foreground/50'"
                                    aria-hidden="true"
                                />
                                <span>{{ feature.label }}</span>
                            </div>
                        </div>

                        <div class="mt-auto space-y-3 border-t border-border/60 pt-3">
                            <p class="text-xs text-muted-foreground">
                                {{ plan.subscriptions_count }} abonnement(s) · {{ plan.active_subscriptions_count }} actif(s)
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <Button variant="outline" size="sm" @click="openEdit(plan)">
                                    <Pencil class="size-3.5" aria-hidden="true" />
                                    Modifier
                                </Button>
                                <Button :variant="plan.is_active ? 'ghost' : 'soft'" size="sm" @click="toggleActivation(plan)">
                                    <Power class="size-3.5" aria-hidden="true" />
                                    {{ plan.is_active ? 'Retirer' : 'Proposer' }}
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Dialog v-model:open="dialogOpen">
                <DialogScrollContent class="max-w-3xl">
                    <DialogTitle>{{ editing ? 'Modifier la formule' : 'Nouvelle formule' }}</DialogTitle>
                    <DialogDescription>
                        Le code technique est immuable après création. Les limites à 0 signifient « illimité » pour les services et les fiches
                        tarifaires.
                    </DialogDescription>

                    <form class="grid gap-4" @submit.prevent="submit">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="grid gap-1.5">
                                <Label for="plan-code">Code technique</Label>
                                <Input id="plan-code" v-model="form.code" :disabled="editing !== null" placeholder="pro_plus" />
                                <p v-if="form.errors.code" class="text-xs text-destructive">{{ form.errors.code }}</p>
                            </div>
                            <div class="grid gap-1.5">
                                <Label for="plan-name">Nom commercial</Label>
                                <Input id="plan-name" v-model="form.name" placeholder="PRO+" />
                                <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                            </div>
                        </div>

                        <div class="grid gap-1.5">
                            <Label for="plan-description">Description</Label>
                            <Textarea id="plan-description" v-model="form.description" rows="3" />
                            <p v-if="form.errors.description" class="text-xs text-destructive">{{ form.errors.description }}</p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="grid gap-1.5">
                                <Label for="plan-monthly">Prix mensuel</Label>
                                <Input id="plan-monthly" v-model.number="form.price_monthly" type="number" min="0" />
                                <p v-if="form.errors.price_monthly" class="text-xs text-destructive">{{ form.errors.price_monthly }}</p>
                            </div>
                            <div class="grid gap-1.5">
                                <Label for="plan-yearly">Prix annuel</Label>
                                <Input id="plan-yearly" v-model.number="form.price_yearly" type="number" min="0" />
                                <p v-if="form.errors.price_yearly" class="text-xs text-destructive">{{ form.errors.price_yearly }}</p>
                            </div>
                            <div class="grid gap-1.5">
                                <Label for="plan-currency">Devise</Label>
                                <Input id="plan-currency" v-model="form.currency" maxlength="3" />
                                <p v-if="form.errors.currency" class="text-xs text-destructive">{{ form.errors.currency }}</p>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <div class="grid gap-1.5">
                                <Label for="plan-profiles">Profils max.</Label>
                                <Input id="plan-profiles" v-model.number="form.max_profiles" type="number" min="0" />
                            </div>
                            <div class="grid gap-1.5">
                                <Label for="plan-services">Services max.</Label>
                                <Input id="plan-services" v-model.number="form.max_services" type="number" min="0" />
                            </div>
                            <div class="grid gap-1.5">
                                <Label for="plan-media">Photos max.</Label>
                                <Input id="plan-media" v-model.number="form.max_media" type="number" min="0" />
                            </div>
                            <div class="grid gap-1.5">
                                <Label for="plan-sheets">Fiches max.</Label>
                                <Input id="plan-sheets" v-model.number="form.max_pricing_sheets" type="number" min="0" />
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="grid gap-1.5">
                                <Label for="plan-trial">Jours d’essai</Label>
                                <Input id="plan-trial" v-model.number="form.trial_days" type="number" min="0" max="365" />
                            </div>
                            <div class="grid gap-1.5">
                                <Label for="plan-sort">Ordre commercial</Label>
                                <Input id="plan-sort" v-model.number="form.sort_order" type="number" min="0" />
                            </div>
                            <label class="flex items-end gap-2 pb-2 text-sm text-foreground">
                                <input v-model="form.is_active" type="checkbox" class="size-4 rounded border-input" />
                                Formule proposée
                            </label>
                        </div>

                        <div class="rounded-xl border border-border/70 p-3">
                            <p class="mb-2 text-sm font-medium text-foreground">Fonctionnalités incluses</p>
                            <div class="grid gap-2 sm:grid-cols-2">
                                <label
                                    v-for="feature in featureOptions"
                                    :key="feature.key"
                                    class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm text-foreground hover:bg-muted/50"
                                >
                                    <input v-model="form.featureFlags[`has_${feature.key}`]" type="checkbox" class="size-4 rounded border-input" />
                                    {{ feature.label }}
                                </label>
                            </div>
                        </div>

                        <DialogFooter class="gap-2">
                            <Button type="button" variant="outline" @click="dialogOpen = false">Annuler</Button>
                            <Button type="submit" variant="brand" :disabled="form.processing">
                                {{ editing ? 'Enregistrer' : 'Créer la formule' }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogScrollContent>
            </Dialog>
        </div>
    </AdminLayout>
</template>
