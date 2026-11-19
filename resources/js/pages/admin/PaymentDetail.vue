<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogDescription, DialogFooter, DialogScrollContent, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import type { BreadcrumbItem, StatusTone } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle, CreditCard, ShieldCheck, XCircle } from 'lucide-vue-next';
import { ref } from 'vue';

type PaymentDetail = {
    id: number;
    reference: string;
    external_reference: string | null;
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
    notes: string | null;
    rejection_reason: string | null;
    declared_at: string | null;
    paid_at: string | null;
    verified_at: string | null;
    verified_by: string | null;
    user: { id: number; name: string; email: string } | null;
    owner: { type: string; id: number | null; name: string; slug: string | null };
    owner_label: string;
    plan: { id: number; code: string; name: string } | null;
    subscription: {
        id: number;
        plan: { id: number; code: string; name: string } | null;
        status: string;
        status_label: string;
        starts_at: string | null;
        ends_at: string | null;
        url: string;
    } | null;
    proofs: Array<{
        id: number;
        original_name: string;
        mime_type: string;
        size: number;
        formatted_size: string;
        created_at: string | null;
        download_url: string;
    }>;
    can_review: boolean;
};

type Props = {
    payment: PaymentDetail;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: '/admin' },
    { title: 'Paiements', href: '/admin/paiements' },
    { title: props.payment.reference, href: `/admin/paiements/${props.payment.id}` },
];

const tone = (value: string): StatusTone =>
    ['neutral', 'brand', 'success', 'warning', 'danger', 'info'].includes(value) ? (value as StatusTone) : 'neutral';

const formatDate = (value: string | null): string => {
    if (!value) return '—';
    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
};

const approveOpen = ref(false);
const rejectOpen = ref(false);
const rejectReason = ref('');

const approveForm = useForm({ note: '' });
const rejectForm = useForm({ rejection_reason: '' });

const submitApprove = () => {
    approveForm.post(route('admin.payments.approve', props.payment.id), {
        preserveScroll: true,
        onSuccess: () => { approveOpen.value = false; },
    });
};

const submitReject = () => {
    rejectForm.post(route('admin.payments.reject', props.payment.id), {
        preserveScroll: true,
        onSuccess: () => { rejectOpen.value = false; },
    });
};
</script>

<template>
    <Head :title="`Paiement · ${props.payment.reference}`" />
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader
                eyebrow="Paiement"
                :title="props.payment.reference"
                :description="`${props.payment.owner_label} · ${props.payment.formatted_amount}`"
            >
                <template #actions>
                    <Button as-child variant="outline">
                        <Link :href="route('admin.payments.index')">
                            <ArrowLeft class="size-4" aria-hidden="true" />
                            Retour
                        </Link>
                    </Button>
                    <Button v-if="props.payment.can_review" variant="brand" @click="approveOpen = true">
                        <CheckCircle class="size-4" aria-hidden="true" />
                        Valider
                    </Button>
                    <Button v-if="props.payment.can_review" variant="destructive" @click="rejectOpen = true">
                        <XCircle class="size-4" aria-hidden="true" />
                        Refuser
                    </Button>
                </template>
            </PageHeader>

            <div class="grid gap-4 lg:grid-cols-3">
                <Card class="lg:col-span-2">
                    <CardHeader class="flex flex-row items-start justify-between gap-3">
                        <div>
                            <CardTitle class="text-base">Détails du paiement</CardTitle>
                            <CardDescription>Informations de la transaction.</CardDescription>
                        </div>
                        <StatusBadge :tone="tone(props.payment.status_tone)" :label="props.payment.status_label" size="md" />
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Montant</p>
                                <p class="mt-1 text-sm font-semibold text-foreground">{{ props.payment.formatted_amount }}</p>
                            </div>
                            <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Moyen</p>
                                <p class="mt-1 text-sm font-semibold text-foreground">{{ props.payment.method_label }}</p>
                            </div>
                            <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Périodicité</p>
                                <p class="mt-1 text-sm font-semibold text-foreground">{{ props.payment.billing_cycle_label ?? '—' }}</p>
                            </div>
                            <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Déclaré le</p>
                                <p class="mt-1 text-sm font-semibold text-foreground">{{ formatDate(props.payment.declared_at) }}</p>
                            </div>
                            <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Payé le</p>
                                <p class="mt-1 text-sm font-semibold text-foreground">{{ formatDate(props.payment.paid_at) }}</p>
                            </div>
                            <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Vérifié le</p>
                                <p class="mt-1 text-sm font-semibold text-foreground">{{ formatDate(props.payment.verified_at) }}</p>
                            </div>
                        </div>

                        <div v-if="props.payment.external_reference" class="rounded-xl border border-border/70 p-3 text-sm">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Référence externe</p>
                            <p class="mt-1 font-mono text-foreground">{{ props.payment.external_reference }}</p>
                        </div>

                        <div v-if="props.payment.notes" class="rounded-xl border border-border/70 p-3 text-sm">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Notes</p>
                            <p class="mt-1 whitespace-pre-line text-foreground">{{ props.payment.notes }}</p>
                        </div>

                        <div v-if="props.payment.rejection_reason" class="rounded-xl border border-destructive/30 bg-destructive/5 p-3 text-sm">
                            <p class="text-xs font-semibold uppercase tracking-wide text-destructive">Motif de refus</p>
                            <p class="mt-1 text-foreground">{{ props.payment.rejection_reason }}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Propriétaire</CardTitle>
                        <CardDescription>Compte concerné par le paiement.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm">
                        <div>
                            <p class="text-xs text-muted-foreground">Nom</p>
                            <p class="font-medium text-foreground">{{ props.payment.owner.name }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Type</p>
                            <p class="font-medium text-foreground">
                                {{
                                    props.payment.owner.type === 'business'
                                        ? 'Entreprise'
                                        : props.payment.owner.type === 'profile'
                                          ? 'Profil indépendant'
                                          : 'Compte'
                                }}
                            </p>
                        </div>
                        <div v-if="props.payment.plan">
                            <p class="text-xs text-muted-foreground">Formule</p>
                            <p class="font-medium text-foreground">{{ props.payment.plan.name }}</p>
                        </div>
                        <div v-if="props.payment.subscription">
                            <p class="text-xs text-muted-foreground">Abonnement</p>
                            <Link :href="props.payment.subscription.url" class="font-medium text-brand-700 hover:underline dark:text-brand-300">
                                Voir l'abonnement
                            </Link>
                        </div>
                        <div v-if="props.payment.user">
                            <p class="text-xs text-muted-foreground">Déclaré par</p>
                            <p class="font-medium text-foreground">{{ props.payment.user.name }}</p>
                            <p class="text-xs text-muted-foreground">{{ props.payment.user.email }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Preuves de paiement</CardTitle>
                    <CardDescription>Documents joints pour vérification.</CardDescription>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="props.payment.proofs.length === 0"
                        class="rounded-xl border border-dashed border-border px-4 py-8 text-center text-sm text-muted-foreground"
                    >
                        Aucune preuve jointe.
                    </div>
                    <div v-else class="divide-y divide-border/60">
                        <div v-for="proof in props.payment.proofs" :key="proof.id" class="flex items-center justify-between gap-3 py-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-foreground">{{ proof.original_name }}</p>
                                <p class="text-xs text-muted-foreground">{{ proof.mime_type }} · {{ proof.formatted_size }}</p>
                            </div>
                            <Button as-child variant="outline" size="sm">
                                <a :href="proof.download_url" target="_blank">Télécharger</a>
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Dialog v-model:open="approveOpen">
            <DialogScrollContent class="max-w-lg">
                <DialogTitle>Valider le paiement</DialogTitle>
                <DialogDescription>
                    Le paiement sera marqué comme payé et l'abonnement sera activé.
                </DialogDescription>
                <form class="grid gap-4" @submit.prevent="submitApprove">
                    <div class="grid gap-1.5">
                        <Label for="approve-note">Note (facultative)</Label>
                        <Textarea id="approve-note" v-model="approveForm.note" rows="2" placeholder="Vérifié, conforme…" />
                    </div>
                    <DialogFooter class="gap-2">
                        <Button type="button" variant="outline" @click="approveOpen = false">Annuler</Button>
                        <Button type="submit" variant="brand" :disabled="approveForm.processing">Valider</Button>
                    </DialogFooter>
                </form>
            </DialogScrollContent>
        </Dialog>

        <Dialog v-model:open="rejectOpen">
            <DialogScrollContent class="max-w-lg">
                <DialogTitle>Refuser le paiement</DialogTitle>
                <DialogDescription>
                    Le paiement sera refusé. Un motif est obligatoire.
                </DialogDescription>
                <form class="grid gap-4" @submit.prevent="submitReject">
                    <div class="grid gap-1.5">
                        <Label for="reject-reason">Motif du refus</Label>
                        <Textarea id="reject-reason" v-model="rejectForm.rejection_reason" rows="3" placeholder="Preuve illisible, montant incorrect…" />
                        <p v-if="rejectForm.errors.rejection_reason" class="text-xs text-destructive">{{ rejectForm.errors.rejection_reason }}</p>
                    </div>
                    <DialogFooter class="gap-2">
                        <Button type="button" variant="outline" @click="rejectOpen = false">Annuler</Button>
                        <Button type="submit" variant="destructive" :disabled="!rejectForm.rejection_reason.trim() || rejectForm.processing">Refuser</Button>
                    </DialogFooter>
                </form>
            </DialogScrollContent>
        </Dialog>
    </AdminLayout>
</template>
