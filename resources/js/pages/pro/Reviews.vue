<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import FormField from '@/components/FormField.vue';
import PageHeader from '@/components/PageHeader.vue';
import StarRating from '@/components/StarRating.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, StatusTone } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { CalendarClock, Filter, LoaderCircle, MessageSquare, Star, UserRound } from 'lucide-vue-next';
import { ref } from 'vue';

type Review = {
    id: number;
    rating: number;
    comment: string | null;
    status: string;
    status_label: string;
    status_tone: string;
    created_at: string | null;
    published_at: string | null;
    customer: string | null;
    professional: string | null;
    booking_reference: string | null;
    professional_reply: string | null;
    replied_at: string | null;
    can_respond: boolean;
};

type Props = {
    reviews: {
        data: Review[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: { status: string };
    statuses: Array<{ value: string; label: string }>;
    stats: {
        average: number;
        count: number;
        pending: number;
    };
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Espace professionnel',
        href: '/pro/demandes',
    },
    {
        title: 'Avis reçus',
        href: '/pro/avis',
    },
];

const status = ref(props.filters.status);
const filtering = ref(false);
const replyingTo = ref<number | null>(null);

const replyForm = useForm({ reply: '' });

const tone = (value: string): StatusTone => {
    return ['neutral', 'brand', 'success', 'warning', 'danger', 'info'].includes(value) ? (value as StatusTone) : 'neutral';
};

const formatDate = (value: string | null): string => {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium' }).format(new Date(value));
};

const applyFilter = () => {
    filtering.value = true;

    router.get(
        route('pro.reviews.index'),
        { status: status.value || undefined },
        {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => {
                filtering.value = false;
            },
        },
    );
};

const startReply = (review: Review) => {
    replyingTo.value = review.id;
    replyForm.reset();
    replyForm.clearErrors();
};

const cancelReply = () => {
    replyingTo.value = null;
    replyForm.reset();
    replyForm.clearErrors();
};

const submitReply = (review: Review) => {
    replyForm.post(route('pro.reviews.respond', review.id), {
        preserveScroll: true,
        onSuccess: () => {
            replyingTo.value = null;
            replyForm.reset();
        },
    });
};

const pageUrl = (page: number): string => route('pro.reviews.index', { page, status: status.value || undefined });
</script>

<template>
    <Head title="Avis reçus" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader
                eyebrow="Espace professionnel"
                title="Avis reçus"
                description="Suivez la satisfaction de vos clients, répondez publiquement aux avis publiés et consultez les avis en modération."
            >
                <template #actions>
                    <Button as-child variant="outline">
                        <Link :href="route('pro.bookings.index')">Réservations</Link>
                    </Button>
                </template>
            </PageHeader>

            <div class="grid gap-4 sm:grid-cols-3">
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Note moyenne publiée</CardDescription>
                        <CardTitle class="flex items-baseline gap-2 text-3xl">
                            {{ props.stats.average.toFixed(1) }}
                            <span class="text-sm font-normal text-muted-foreground">/ 5</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <StarRating :model-value="Math.round(props.stats.average)" readonly size="sm" />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Avis publiés</CardDescription>
                        <CardTitle class="flex items-baseline gap-2 text-3xl">
                            {{ props.stats.count }}
                            <Star class="size-5 text-gold-500" aria-hidden="true" />
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-muted-foreground">Seuls les avis approuvés comptent dans la note.</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>En attente de modération</CardDescription>
                        <CardTitle class="flex items-baseline gap-2 text-3xl">
                            {{ props.stats.pending }}
                            <MessageSquare class="size-5 text-amber-500" aria-hidden="true" />
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-xs text-muted-foreground">Les avis en attente ne sont pas encore publics.</p>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Filtrer les avis</CardTitle>
                    <CardDescription>{{ props.reviews.total }} avis au total.</CardDescription>
                </CardHeader>

                <CardContent>
                    <form class="flex flex-wrap items-end gap-3" @submit.prevent="applyFilter">
                        <div class="min-w-[200px]">
                            <label for="status" class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.1em] text-muted-foreground">
                                Statut
                            </label>
                            <select
                                id="status"
                                v-model="status"
                                class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm text-foreground"
                            >
                                <option value="">Tous les statuts</option>
                                <option v-for="item in statuses" :key="item.value" :value="item.value">{{ item.label }}</option>
                            </select>
                        </div>

                        <Button type="submit" variant="brand" :disabled="filtering">
                            <LoaderCircle v-if="filtering" class="size-4 animate-spin" aria-hidden="true" />
                            <Filter v-else class="size-4" aria-hidden="true" />
                            Filtrer
                        </Button>
                    </form>
                </CardContent>
            </Card>

            <div v-if="props.reviews.data.length" class="space-y-4">
                <Card v-for="review in props.reviews.data" :key="review.id">
                    <CardHeader class="flex-row items-start justify-between gap-3 space-y-0">
                        <div class="min-w-0">
                            <CardTitle class="flex flex-wrap items-center gap-2 text-base">
                                <UserRound class="size-4 text-muted-foreground" aria-hidden="true" />
                                {{ review.customer ?? 'Client' }}
                            </CardTitle>
                            <CardDescription class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1">
                                <span v-if="review.professional">{{ review.professional }}</span>
                                <span v-if="review.booking_reference" class="inline-flex items-center gap-1.5">
                                    <CalendarClock class="size-3.5" aria-hidden="true" />
                                    {{ review.booking_reference }}
                                </span>
                                <span>Reçu le {{ formatDate(review.created_at) }}</span>
                            </CardDescription>
                        </div>

                        <StatusBadge :tone="tone(review.status_tone)" :label="review.status_label" />
                    </CardHeader>

                    <CardContent class="space-y-4">
                        <div class="flex flex-wrap items-center gap-3">
                            <StarRating :model-value="review.rating" readonly show-value size="sm" />
                            <span v-if="review.published_at" class="text-xs text-muted-foreground"
                                >Publié le {{ formatDate(review.published_at) }}</span
                            >
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
                                Votre réponse
                                <span v-if="review.replied_at" class="font-normal normal-case tracking-normal text-muted-foreground">
                                    · {{ formatDate(review.replied_at) }}
                                </span>
                            </p>
                            <p class="mt-1.5 whitespace-pre-line text-sm leading-relaxed text-muted-foreground">{{ review.professional_reply }}</p>
                        </div>

                        <div v-else-if="review.can_respond" class="space-y-3">
                            <Button v-if="replyingTo !== review.id" type="button" variant="outline" size="sm" @click="startReply(review)">
                                <MessageSquare class="size-4" aria-hidden="true" />
                                Répondre publiquement
                            </Button>

                            <form v-else class="space-y-3" @submit.prevent="submitReply(review)">
                                <FormField
                                    label="Votre réponse"
                                    :for="`reply-${review.id}`"
                                    required
                                    :error="replyForm.errors.reply"
                                    hint="Cette réponse sera visible sur votre fiche publique."
                                >
                                    <Textarea
                                        :id="`reply-${review.id}`"
                                        v-model="replyForm.reply"
                                        rows="4"
                                        minlength="10"
                                        maxlength="2000"
                                        required
                                        placeholder="Remerciez le client ou apportez une précision utile…"
                                    />
                                </FormField>

                                <div class="flex flex-wrap items-center gap-2">
                                    <Button type="submit" variant="brand" size="sm" :disabled="replyForm.processing">
                                        <LoaderCircle v-if="replyForm.processing" class="size-4 animate-spin" aria-hidden="true" />
                                        Publier la réponse
                                    </Button>
                                    <Button type="button" variant="ghost" size="sm" @click="cancelReply">Annuler</Button>
                                </div>
                            </form>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <EmptyState
                v-else
                title="Aucun avis pour le moment"
                description="Les avis de vos clients apparaîtront ici dès qu’une prestation terminée aura été évaluée."
                :icon="Star"
            />

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
