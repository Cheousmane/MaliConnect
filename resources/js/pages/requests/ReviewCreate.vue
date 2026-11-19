<script setup lang="ts">
import FormField from '@/components/FormField.vue';
import PageHeader from '@/components/PageHeader.vue';
import StarRating from '@/components/StarRating.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CalendarClock, LoaderCircle, Star, Wallet } from 'lucide-vue-next';

type Booking = {
    id: number;
    reference: string;
    starts_at: string | null;
    amount: string | null;
    professional: string | null;
    professional_slug: string | null;
    service: string | null;
};

const props = defineProps<{
    booking: Booking;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Mes réservations',
        href: '/reservations',
    },
    {
        title: 'Laisser un avis',
        href: `/reservations/${props.booking.id}/avis`,
    },
];

const form = useForm({
    rating: 0,
    comment: '',
});

const formatDate = (value: string | null): string => {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'long', timeStyle: 'short' }).format(new Date(value));
};

const submit = () => {
    form.post(route('reviews.store', props.booking.id), { preserveScroll: true });
};
</script>

<template>
    <Head title="Laisser un avis" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <Link
                :href="route('bookings.index')"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft class="size-4" aria-hidden="true" />
                Retour à mes réservations
            </Link>

            <PageHeader
                eyebrow="Avis vérifié"
                title="Laisser un avis"
                description="Votre retour aide les autres clients à choisir le bon professionnel. Seules les prestations terminées peuvent être évaluées."
            />

            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_320px]">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Votre évaluation</CardTitle>
                        <CardDescription>Attribuez une note de 1 à 5 et partagez votre expérience.</CardDescription>
                    </CardHeader>

                    <CardContent>
                        <form class="space-y-5" @submit.prevent="submit">
                            <FormField label="Note" for="rating" required :error="form.errors.rating">
                                <div class="flex flex-wrap items-center gap-3">
                                    <StarRating v-model="form.rating" size="lg" show-value />
                                    <span v-if="form.rating > 0" class="text-sm text-muted-foreground"
                                        >{{ form.rating }} étoile{{ form.rating > 1 ? 's' : '' }} sur 5</span
                                    >
                                </div>
                            </FormField>

                            <FormField
                                label="Votre commentaire"
                                for="comment"
                                required
                                :error="form.errors.comment"
                                hint="Décrivez la qualité de la prestation, le respect du rendez-vous et votre ressenti général."
                            >
                                <Textarea
                                    id="comment"
                                    v-model="form.comment"
                                    rows="6"
                                    minlength="10"
                                    maxlength="2000"
                                    required
                                    placeholder="Partagez les points forts et les éventuels points à améliorer…"
                                />
                            </FormField>

                            <div class="flex flex-wrap items-center gap-3">
                                <Button type="submit" variant="brand" :disabled="form.processing || form.rating === 0">
                                    <LoaderCircle v-if="form.processing" class="size-4 animate-spin" aria-hidden="true" />
                                    <Star v-else class="size-4" aria-hidden="true" />
                                    Publier mon avis
                                </Button>
                                <Button as-child variant="ghost">
                                    <Link :href="route('bookings.index')">Annuler</Link>
                                </Button>
                            </div>

                            <p class="text-xs leading-relaxed text-muted-foreground">
                                Votre avis sera examiné par notre équipe avant publication. Le professionnel pourra y répondre publiquement.
                            </p>
                        </form>
                    </CardContent>
                </Card>

                <aside class="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">Prestation concernée</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2.5 text-sm text-muted-foreground">
                            <p class="font-medium text-foreground">{{ booking.professional ?? 'Professionnel' }}</p>
                            <p v-if="booking.service" class="flex items-center gap-2">
                                <Star class="size-4 shrink-0" aria-hidden="true" />
                                {{ booking.service }}
                            </p>
                            <p class="flex items-center gap-2">
                                <CalendarClock class="size-4 shrink-0" aria-hidden="true" />
                                {{ formatDate(booking.starts_at) }}
                            </p>
                            <p class="flex items-center gap-2">
                                <Wallet class="size-4 shrink-0" aria-hidden="true" />
                                {{ booking.amount ? `${booking.amount} FCFA` : 'Montant non précisé' }}
                            </p>
                            <p class="pt-1 text-xs">Référence {{ booking.reference }}</p>
                        </CardContent>
                    </Card>
                </aside>
            </div>
        </div>
    </AppLayout>
</template>
