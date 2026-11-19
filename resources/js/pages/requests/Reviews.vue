<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import StarRating from '@/components/StarRating.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, StatusTone } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, CalendarClock, MessageSquare, Star } from 'lucide-vue-next';

type Review = {
    id: number;
    rating: number;
    comment: string | null;
    status: string;
    status_label: string;
    status_tone: string;
    published_at: string | null;
    created_at: string | null;
    professional: string | null;
    professional_slug: string | null;
    booking_reference: string | null;
    professional_reply: string | null;
    replied_at: string | null;
};

type Props = {
    reviews: {
        data: Review[];
        current_page: number;
        last_page: number;
        total: number;
    };
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Mes demandes',
        href: '/demandes',
    },
    {
        title: 'Mes avis',
        href: '/avis',
    },
];

const tone = (value: string): StatusTone => {
    return ['neutral', 'brand', 'success', 'warning', 'danger', 'info'].includes(value) ? (value as StatusTone) : 'neutral';
};

const formatDate = (value: string | null): string => {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium' }).format(new Date(value));
};

const pageUrl = (page: number): string => route('reviews.index', { page });
</script>

<template>
    <Head title="Mes avis" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <Link
                :href="route('requests.index')"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft class="size-4" aria-hidden="true" />
                Retour à mes demandes
            </Link>

            <PageHeader
                eyebrow="Avis"
                title="Mes avis"
                description="Chaque avis est lié à une prestation terminée. Il est publié après modération et le professionnel peut y répondre."
            >
                <template #actions>
                    <Button as-child variant="outline">
                        <Link :href="route('bookings.index')">Mes réservations</Link>
                    </Button>
                </template>
            </PageHeader>

            <div v-if="props.reviews.data.length" class="space-y-4">
                <Card v-for="review in props.reviews.data" :key="review.id">
                    <CardHeader class="flex-row items-start justify-between gap-3 space-y-0">
                        <div class="min-w-0">
                            <CardTitle class="text-base">
                                <Link
                                    v-if="review.professional_slug"
                                    :href="route('professionals.show', review.professional_slug)"
                                    class="hover:text-brand-700 dark:hover:text-brand-300"
                                >
                                    {{ review.professional ?? 'Professionnel' }}
                                </Link>
                                <span v-else>{{ review.professional ?? 'Professionnel' }}</span>
                            </CardTitle>
                            <CardDescription class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1">
                                <span v-if="review.booking_reference" class="inline-flex items-center gap-1.5">
                                    <CalendarClock class="size-3.5" aria-hidden="true" />
                                    {{ review.booking_reference }}
                                </span>
                                <span>Déposé le {{ formatDate(review.created_at) }}</span>
                            </CardDescription>
                        </div>

                        <StatusBadge :tone="tone(review.status_tone)" :label="review.status_label" />
                    </CardHeader>

                    <CardContent class="space-y-4">
                        <div class="flex flex-wrap items-center gap-3">
                            <StarRating :model-value="review.rating" readonly show-value size="sm" />
                            <span v-if="review.published_at" class="text-xs text-muted-foreground">
                                Publié le {{ formatDate(review.published_at) }}
                            </span>
                        </div>

                        <p v-if="review.comment" class="whitespace-pre-line text-sm leading-relaxed text-muted-foreground">
                            {{ review.comment }}
                        </p>

                        <div
                            v-if="review.professional_reply"
                            class="rounded-xl border border-brand-100 bg-brand-50/50 p-3.5 dark:border-brand-900/70 dark:bg-brand-950/20"
                        >
                            <p
                                class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.1em] text-brand-700 dark:text-brand-300"
                            >
                                <MessageSquare class="size-3.5" aria-hidden="true" />
                                Réponse du professionnel
                                <span v-if="review.replied_at" class="font-normal normal-case tracking-normal text-muted-foreground">
                                    · {{ formatDate(review.replied_at) }}
                                </span>
                            </p>
                            <p class="mt-1.5 whitespace-pre-line text-sm leading-relaxed text-muted-foreground">{{ review.professional_reply }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <EmptyState
                v-else
                title="Aucun avis pour le moment"
                description="Vous pourrez évaluer un professionnel dès que l’une de vos réservations sera terminée."
                :icon="Star"
            >
                <template #action>
                    <Button as-child variant="brand">
                        <Link :href="route('bookings.index')">Voir mes réservations</Link>
                    </Button>
                </template>
            </EmptyState>

            <nav v-if="props.reviews.last_page > 1" class="flex items-center justify-between gap-3" aria-label="Pagination des avis">
                <p class="text-xs text-muted-foreground">Page {{ props.reviews.current_page }} sur {{ props.reviews.last_page }}</p>
                <div class="flex items-center gap-2">
                    <Button as-child variant="outline" size="sm" :class="props.reviews.current_page <= 1 ? 'pointer-events-none opacity-50' : ''">
                        <Link :href="pageUrl(props.reviews.current_page - 1)">Précédent</Link>
                    </Button>
                    <Button
                        as-child
                        variant="outline"
                        size="sm"
                        :class="props.reviews.current_page >= props.reviews.last_page ? 'pointer-events-none opacity-50' : ''"
                    >
                        <Link :href="pageUrl(props.reviews.current_page + 1)">Suivant</Link>
                    </Button>
                </div>
            </nav>
        </div>
    </AppLayout>
</template>
