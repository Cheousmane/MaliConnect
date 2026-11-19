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
import { ArrowLeft, CalendarClock, LoaderCircle, Mail, MapPin, Paperclip, Phone, Send, UserRound, Wallet } from 'lucide-vue-next';
import { computed } from 'vue';

type Quote = {
    id: number;
    reference: string;
    message: string;
    amount: string | null;
    duration_minutes: number | null;
    status: string;
    status_label: string;
    status_tone: string;
    rejection_reason: string | null;
    valid_until: string | null;
    created_at: string | null;
};

type Message = {
    id: number;
    body: string;
    author: string | null;
    is_mine: boolean;
    created_at: string | null;
};

type RequestDetail = {
    id: number;
    reference: string;
    title: string;
    status: string;
    status_label: string;
    status_tone: string;
    description: string;
    address: string | null;
    city: string;
    commune: string | null;
    quartier: string | null;
    budget_min: string | null;
    budget_max: string | null;
    desired_date: string | null;
    preferred_time: string | null;
    created_at: string | null;
    customer: { id: number; name: string; email: string; phone: string | null } | null;
    service: { id: number; name: string; formatted_price: string } | null;
    quotes: Quote[];
    messages: Message[];
    attachments: Array<{ id: number; name: string; type: string; url: string }>;
};

type Props = {
    request: RequestDetail;
    transitions: Record<string, string[]>;
    canQuote: boolean;
    canRespond: boolean;
    canComplete: boolean;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Demandes reçues',
        href: '/pro/demandes',
    },
    {
        title: props.request.title,
        href: `/pro/demandes/${props.request.id}`,
    },
];

const statusLabels: Record<string, string> = {
    pending: 'En attente',
    in_progress: 'En cours',
    quoted: 'Devis envoyé',
    accepted: 'Acceptée',
    refused: 'Refusée',
    cancelled: 'Annulée',
    completed: 'Terminée',
};

const tone = (value: string): StatusTone => {
    return ['neutral', 'brand', 'success', 'warning', 'danger', 'info'].includes(value) ? (value as StatusTone) : 'neutral';
};

const formatDate = (value: string | null): string => {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
};

const formatDay = (value: string | null): string => {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium' }).format(new Date(value));
};

const messageForm = useForm({ body: '' });

const quoteForm = useForm({
    message: '',
    amount: '',
    duration_minutes: '',
    valid_until: '',
});

const statusForm = useForm({ status: '' });

const nextStatuses = computed(() =>
    Object.entries(props.transitions)
        .filter(([, sources]) => sources.includes(props.request.status))
        .map(([target]) => target),
);

const sendMessage = () => {
    messageForm.post(route('pro.requests.messages.store', props.request.id), {
        preserveScroll: true,
        onSuccess: () => messageForm.reset(),
    });
};

const sendQuote = () => {
    quoteForm.post(route('pro.requests.quotes.store', props.request.id), {
        preserveScroll: true,
        onSuccess: () => quoteForm.reset(),
    });
};

const changeStatus = (status: string) => {
    statusForm.status = status;

    statusForm.post(route('pro.requests.status.update', props.request.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="request.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <Link
                :href="route('pro.requests.index')"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft class="size-4" aria-hidden="true" />
                Retour aux demandes
            </Link>

            <PageHeader :eyebrow="request.reference" :title="request.title">
                <template #meta>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <StatusBadge :tone="tone(request.status_tone)" :label="request.status_label" />
                        <span class="text-xs text-muted-foreground">
                            {{ request.customer?.name }} · reçue le {{ formatDate(request.created_at ?? null) }}
                        </span>
                    </div>
                </template>

                <template #actions>
                    <Button v-for="status in nextStatuses" :key="status" type="button" variant="outline" size="sm" @click="changeStatus(status)">
                        {{ statusLabels[status] ?? status }}
                    </Button>
                </template>
            </PageHeader>

            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_320px]">
                <div class="min-w-0 space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">Besoin exprimé</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <p class="whitespace-pre-line text-sm leading-relaxed text-muted-foreground">{{ request.description }}</p>

                            <div v-if="request.attachments.length" class="space-y-2">
                                <p class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.1em] text-muted-foreground">
                                    <Paperclip class="size-3.5" aria-hidden="true" />
                                    Pièces jointes
                                </p>
                                <ul class="space-y-1.5">
                                    <li v-for="attachment in request.attachments" :key="attachment.id">
                                        <a
                                            :href="attachment.url"
                                            target="_blank"
                                            rel="noopener"
                                            class="text-sm text-brand-700 underline-offset-4 hover:underline dark:text-brand-300"
                                        >
                                            {{ attachment.name }}
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </CardContent>
                    </Card>

                    <Card v-if="canQuote">
                        <CardHeader>
                            <CardTitle class="text-base">Envoyer un devis</CardTitle>
                            <CardDescription>Montant, durée et validité : le client pourra accepter et réserver.</CardDescription>
                        </CardHeader>

                        <CardContent>
                            <form class="space-y-3" @submit.prevent="sendQuote">
                                <FormField label="Détail de la prestation" for="quote-message" :error="quoteForm.errors.message" required>
                                    <Textarea id="quote-message" v-model="quoteForm.message" rows="4" minlength="20" maxlength="3000" required />
                                </FormField>

                                <div class="grid gap-3 sm:grid-cols-3">
                                    <FormField label="Montant (FCFA)" for="quote-amount" :error="quoteForm.errors.amount" required>
                                        <Input id="quote-amount" v-model="quoteForm.amount" type="number" min="0" required />
                                    </FormField>
                                    <FormField label="Durée (min)" for="quote-duration" :error="quoteForm.errors.duration_minutes">
                                        <Input id="quote-duration" v-model="quoteForm.duration_minutes" type="number" min="15" step="15" />
                                    </FormField>
                                    <FormField label="Valable jusqu’au" for="quote-validity" :error="quoteForm.errors.valid_until">
                                        <Input id="quote-validity" v-model="quoteForm.valid_until" type="date" />
                                    </FormField>
                                </div>

                                <Button type="submit" variant="brand" :disabled="quoteForm.processing">
                                    <LoaderCircle v-if="quoteForm.processing" class="size-4 animate-spin" aria-hidden="true" />
                                    <Send v-else class="size-4" aria-hidden="true" />
                                    Envoyer le devis
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    <Card v-if="request.quotes.length">
                        <CardHeader>
                            <CardTitle class="text-base">Devis envoyés</CardTitle>
                        </CardHeader>

                        <CardContent>
                            <ul class="space-y-3">
                                <li
                                    v-for="quote in request.quotes"
                                    :key="quote.id"
                                    class="flex flex-wrap items-start justify-between gap-3 rounded-xl border border-border/70 bg-background p-4"
                                >
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-foreground">{{ quote.reference }}</p>
                                        <p class="mt-1 text-xs leading-relaxed text-muted-foreground">{{ quote.message }}</p>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <p class="text-sm font-semibold text-foreground">{{ quote.amount }} FCFA</p>
                                        <StatusBadge :tone="tone(quote.status_tone)" :label="quote.status_label" class="mt-1" />
                                    </div>
                                    <div v-if="quote.rejection_reason" class="mt-2 w-full rounded-lg border border-destructive/20 bg-destructive/5 px-3 py-2 text-xs text-destructive">
                                        <p class="font-medium">Motif du refus :</p>
                                        <p class="mt-0.5">{{ quote.rejection_reason }}</p>
                                    </div>
                                </li>
                            </ul>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">Échanges</CardTitle>
                            <CardDescription>Posez vos questions au client.</CardDescription>
                        </CardHeader>

                        <CardContent class="space-y-4">
                            <ul v-if="request.messages.length" class="space-y-2">
                                <li
                                    v-for="message in request.messages"
                                    :key="message.id"
                                    class="rounded-xl px-3.5 py-2.5"
                                    :class="message.is_mine ? 'bg-brand-50/70 dark:bg-brand-950/40' : 'bg-muted/50'"
                                >
                                    <p class="text-xs font-medium text-foreground">
                                        {{ message.author }}
                                        <span class="ml-1 font-normal text-muted-foreground">{{ formatDate(message.created_at) }}</span>
                                    </p>
                                    <p class="mt-1 text-sm leading-relaxed text-muted-foreground">{{ message.body }}</p>
                                </li>
                            </ul>

                            <p v-else class="text-sm text-muted-foreground">Aucun message pour le moment.</p>

                            <form v-if="canRespond" class="space-y-3" @submit.prevent="sendMessage">
                                <FormField label="Votre message" for="body" :error="messageForm.errors.body" required>
                                    <Textarea id="body" v-model="messageForm.body" rows="3" maxlength="2000" required />
                                </FormField>
                                <Button type="submit" variant="outline" :disabled="messageForm.processing">
                                    <LoaderCircle v-if="messageForm.processing" class="size-4 animate-spin" aria-hidden="true" />
                                    <Send v-else class="size-4" aria-hidden="true" />
                                    Envoyer
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                </div>

                <aside class="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">Client</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2.5 text-sm text-muted-foreground">
                            <p class="flex items-center gap-2 text-foreground">
                                <UserRound class="size-4 shrink-0" aria-hidden="true" />
                                {{ request.customer?.name }}
                            </p>
                            <p class="flex items-center gap-2">
                                <Mail class="size-4 shrink-0" aria-hidden="true" />
                                {{ request.customer?.email }}
                            </p>
                            <p v-if="request.customer?.phone" class="flex items-center gap-2">
                                <Phone class="size-4 shrink-0" aria-hidden="true" />
                                {{ request.customer.phone }}
                            </p>
                            <p class="flex items-center gap-2">
                                <MapPin class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                                <span>
                                    {{ request.address ?? 'Adresse non précisée' }}<br />
                                    {{ request.city }}<span v-if="request.commune"> · {{ request.commune }}</span
                                    ><span v-if="request.quartier"> · {{ request.quartier }}</span>
                                </span>
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">Résumé</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3 text-sm text-muted-foreground">
                            <p class="flex items-center gap-2">
                                <Wallet class="size-4 shrink-0" aria-hidden="true" />
                                <span v-if="request.budget_min">
                                    {{ request.budget_min }}<span v-if="request.budget_max"> – {{ request.budget_max }}</span> FCFA
                                </span>
                                <span v-else>Budget non précisé</span>
                            </p>
                            <p class="flex items-center gap-2">
                                <CalendarClock class="size-4 shrink-0" aria-hidden="true" />
                                <span>
                                    {{ request.desired_date ? formatDay(request.desired_date) : 'Date non précisée' }}
                                    <span v-if="request.preferred_time">· {{ request.preferred_time }}</span>
                                </span>
                            </p>
                        </CardContent>
                    </Card>
                </aside>
            </div>
        </div>
    </AppLayout>
</template>
