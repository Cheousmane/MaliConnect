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
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CalendarClock, LoaderCircle, MapPin, Paperclip, Send, Wallet, XCircle } from 'lucide-vue-next';
import { ref } from 'vue';

type Quote = {
    id: number;
    reference: string;
    message: string;
    amount: string | null;
    duration_minutes: number | null;
    status: string;
    status_label: string;
    status_tone: string;
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
    professional: { id: number; business_name: string; slug: string; category: string | null; phone: string | null } | null;
    service: { id: number; name: string; formatted_price: string } | null;
    quotes: Quote[];
    messages: Message[];
    attachments: Array<{ id: number; name: string; type: string; url: string }>;
    booking: { id: number; reference: string; starts_at: string | null; status: string; status_label: string } | null;
};

type Props = {
    request: RequestDetail;
    canCancel: boolean;
    canMessage: boolean;
    canRejectQuote: boolean;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Mes demandes',
        href: '/demandes',
    },
    {
        title: props.request.title,
        href: `/demandes/${props.request.id}`,
    },
];

const messageForm = useForm({ body: '' });

const bookingForm = useForm({
    quote_id: '',
    starts_at: '',
    duration_minutes: '',
});

const startsAtInput = ref('');

const rejectingQuoteId = ref<number | null>(null);
const rejectionReason = ref('');

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

const sendMessage = () => {
    messageForm.post(route('requests.messages.store', props.request.id), {
        preserveScroll: true,
        onSuccess: () => messageForm.reset(),
    });
};

const acceptQuote = (quote: Quote) => {
    if (!startsAtInput.value) {
        alert('Veuillez sélectionner une date et une heure pour le rendez-vous.');
        return;
    }

    // S'assurer que la date est dans le futur
    const selectedDate = new Date(startsAtInput.value);
    if (selectedDate <= new Date()) {
        alert('La date et l\'heure doivent être dans le futur.');
        return;
    }

    // Nettoyer la durée
    const duration = bookingForm.duration_minutes ? parseInt(String(bookingForm.duration_minutes)) : null;
    if (duration !== null && (duration < 15 || duration > 1440)) {
        alert('La durée doit être entre 15 et 1440 minutes.');
        return;
    }

    bookingForm.quote_id = String(quote.id);
    bookingForm.starts_at = startsAtInput.value;
    bookingForm.duration_minutes = duration ? String(duration) : '';
    router.post(route('requests.quotes.accept', [props.request.id, quote.id]), {
        quote_id: String(quote.id),
        starts_at: startsAtInput.value,
        duration_minutes: duration ? String(duration) : '',
    }, {
        preserveScroll: true,
        onError: (errors) => {
            console.error('Accept quote errors:', errors);
            const messages = Object.values(errors).join(', ');
            alert('Erreur: ' + messages);
        },
        onSuccess: () => {
            console.log('Quote accepted successfully');
        },
    });
};

const cancelRequest = () => {
    useForm({}).post(route('requests.cancel', props.request.id), { preserveScroll: true });
};

const startRejection = (quote: Quote) => {
    rejectingQuoteId.value = quote.id;
    rejectionReason.value = '';
};

const cancelRejection = () => {
    rejectingQuoteId.value = null;
    rejectionReason.value = '';
};

const rejectQuote = (quote: Quote) => {
    router.post(
        route('requests.quotes.reject', [props.request.id, quote.id]),
        { reason: rejectionReason.value || undefined },
        {
            preserveScroll: true,
            onSuccess: () => {
                rejectingQuoteId.value = null;
                rejectionReason.value = '';
            },
        },
    );
};
</script>

<template>
    <Head :title="request.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <Link
                :href="route('requests.index')"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft class="size-4" aria-hidden="true" />
                Retour à mes demandes
            </Link>

            <PageHeader :eyebrow="request.reference" :title="request.title">
                <template #meta>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <StatusBadge :tone="tone(request.status_tone)" :label="request.status_label" />
                        <span v-if="request.professional" class="text-xs text-muted-foreground">
                            {{ request.professional.business_name }}
                            <span v-if="request.professional.category">· {{ request.professional.category }}</span>
                        </span>
                    </div>
                </template>

                <template #actions>
                    <Button v-if="canCancel" type="button" variant="outline" class="text-destructive" @click="cancelRequest">
                        Annuler la demande
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

                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">Devis reçus</CardTitle>
                            <CardDescription>Acceptez un devis pour réserver le créneau correspondant.</CardDescription>
                        </CardHeader>

                        <CardContent class="space-y-4">
                            <ul v-if="request.quotes.length" class="space-y-3">
                                <li v-for="quote in request.quotes" :key="quote.id" class="rounded-xl border border-border/70 bg-background p-4">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-foreground">{{ quote.reference }}</p>
                                            <p class="mt-1 text-xs leading-relaxed text-muted-foreground">{{ quote.message }}</p>
                                        </div>
                                        <div class="shrink-0 text-right">
                                            <p class="text-sm font-semibold text-foreground">{{ quote.amount }} FCFA</p>
                                            <p class="mt-0.5 text-xs text-muted-foreground">
                                                {{ quote.duration_minutes ? `${quote.duration_minutes} min` : 'Durée non précisée' }}
                                            </p>
                                            <p v-if="quote.valid_until" class="mt-0.5 text-xs text-muted-foreground">
                                                Valide jusqu’au {{ formatDay(quote.valid_until) }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                        <StatusBadge :tone="tone(quote.status_tone)" :label="quote.status_label" />

                                        <template v-if="quote.status === 'pending' && !request.booking">
                                            <Input
                                                v-model="startsAtInput"
                                                type="datetime-local"
                                                class="h-9 w-auto text-xs"
                                                aria-label="Date et heure du créneau souhaité"
                                            />
                                            <Input
                                                v-model="bookingForm.duration_minutes"
                                                type="number"
                                                min="15"
                                                step="15"
                                                placeholder="Durée (min)"
                                                class="h-9 w-32 text-xs"
                                                aria-label="Durée en minutes"
                                            />
                                            <Button type="button" variant="brand" size="sm" :disabled="!startsAtInput" @click="acceptQuote(quote)">
                                                Accepter et réserver
                                            </Button>
                                            <Button
                                                v-if="canRejectQuote"
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                class="text-destructive"
                                                @click="startRejection(quote)"
                                            >
                                                <XCircle class="size-4" aria-hidden="true" />
                                                Refuser
                                            </Button>
                                        </template>
                                    </div>

                                    <form
                                        v-if="canRejectQuote && rejectingQuoteId === quote.id && quote.status === 'pending'"
                                        class="mt-3 space-y-2 rounded-xl border border-destructive/20 bg-destructive/5 p-3"
                                        @submit.prevent="rejectQuote(quote)"
                                    >
                                        <label :for="`reason-${quote.id}`" class="text-xs font-medium text-foreground">
                                            Motif du refus (facultatif)
                                        </label>
                                        <Textarea
                                            :id="`reason-${quote.id}`"
                                            v-model="rejectionReason"
                                            rows="2"
                                            maxlength="500"
                                            placeholder="Précisez pourquoi ce devis ne vous convient pas…"
                                        />
                                        <div class="flex flex-wrap items-center gap-2">
                                            <Button type="submit" variant="destructive" size="sm">Confirmer le refus</Button>
                                            <Button type="button" variant="ghost" size="sm" @click="cancelRejection">Annuler</Button>
                                        </div>
                                    </form>
                                </li>
                            </ul>

                            <p v-else class="text-sm text-muted-foreground">Aucun devis reçu pour le moment.</p>

                            <div v-if="request.booking" class="rounded-xl border border-brand-200 bg-brand-50/60 p-4">
                                <p class="text-sm font-medium text-brand-900 dark:text-brand-100">Réservation {{ request.booking.reference }}</p>
                                <p class="mt-1 text-xs text-brand-800/80 dark:text-brand-200/80">
                                    {{ formatDate(request.booking.starts_at) }} · {{ request.booking.status_label }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">Échanges</CardTitle>
                            <CardDescription>Posez vos questions au professionnel.</CardDescription>
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

                            <form v-if="canMessage" class="space-y-3" @submit.prevent="sendMessage">
                                <FormField label="Votre message" for="body" :error="messageForm.errors.body">
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
                            <CardTitle class="text-base">Résumé</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3 text-sm text-muted-foreground">
                            <p class="flex items-start gap-2">
                                <MapPin class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                                <span>
                                    {{ request.address ?? 'Adresse non précisée' }}<br />
                                    {{ request.city }}<span v-if="request.commune"> · {{ request.commune }}</span
                                    ><span v-if="request.quartier"> · {{ request.quartier }}</span>
                                </span>
                            </p>
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

                    <Card v-if="request.professional">
                        <CardHeader>
                            <CardTitle class="text-base">Professionnel</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3 text-sm">
                            <p class="font-medium text-foreground">
                                <Link
                                    :href="route('professionals.show', request.professional.slug)"
                                    class="hover:text-brand-700 dark:hover:text-brand-300"
                                >
                                    {{ request.professional.business_name }}
                                </Link>
                            </p>
                            <p v-if="request.professional.phone" class="text-muted-foreground">{{ request.professional.phone }}</p>
                            <Button as-child variant="outline" size="sm" class="w-full">
                                <Link :href="route('professionals.show', request.professional.slug)">Voir le profil</Link>
                            </Button>
                        </CardContent>
                    </Card>
                </aside>
            </div>
        </div>
    </AppLayout>
</template>
