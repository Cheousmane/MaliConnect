<script setup lang="ts">
import FavoriteButton from '@/components/FavoriteButton.vue';
import StarRating from '@/components/StarRating.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import PublicLayout from '@/layouts/public/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    BadgeCheck,
    CalendarClock,
    CalendarDays,
    Check,
    Clock,
    Copy,
    Download,
    MapPin,
    MessageSquare,
    Phone,
    ReceiptText,
    Share2,
    Star,
    Store,
    Wallet,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

type Service = {
    id: number;
    name: string;
    description: string | null;
    price_type: string;
    price_min: string | null;
    price_max: string | null;
    duration_minutes: number | null;
    formatted_price: string;
};

type PricingItem = {
    id: number;
    name: string;
    description: string | null;
    category: { id: number; name: string } | null;
    service_name: string | null;
    pricing_type_label: string;
    formatted_price: string;
    billing_unit: string | null;
    conditions: string | null;
    formatted_duration: string | null;
    requires_diagnostic: boolean;
    is_quote: boolean;
    has_price: boolean;
    quote_url: string | null;
};

type PricingSheet = {
    id: number;
    name: string;
    description: string | null;
    currency_label: string;
    general_conditions: string | null;
    valid_until: string | null;
    updated_at: string | null;
    category: { id: number; name: string } | null;
    public_url: string;
    pdf_url: string;
    items: PricingItem[];
};

type Profile = {
    id: number;
    business_name: string;
    slug: string;
    description: string | null;
    phone: string | null;
    whatsapp: string | null;
    address: string | null;
    city: string;
    commune: string | null;
    quartier: string | null;
    is_available: boolean;
    rating_average: number;
    rating_count: number;
    years_experience: number | null;
    response_time_hours: number | null;
    verified_at: string | null;
    category: { id: number; name: string; slug: string } | null;
    subcategory: { id: number; name: string; slug: string } | null;
    business: { id: number; name: string } | null;
    services: Service[];
    areas: Array<{ city: string; commune: string | null; quartier: string | null; is_primary: boolean }>;
    hours: Array<{ day_of_week: number; opens_at: string | null; closes_at: string | null; is_closed: boolean }>;
    media: Array<{ id: number; type_label: string; url: string; caption: string | null }>;
};

const props = defineProps<{
    profile: Profile;
    pricingSheets: PricingSheet[];
    reviews: Array<{
        id: number;
        rating: number;
        comment: string | null;
        customer: string | null;
        published_at: string | null;
        professional_reply: string | null;
        replied_at: string | null;
    }>;
    isFavorited: boolean;
    canFavorite: boolean;
}>();

const activeTab = ref<'presentation' | 'tarifs'>('presentation');
const copiedSheet = ref<number | null>(null);

const dayLabels = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];

const gallery = computed(() => props.profile.media.filter((media) => media.type_label !== 'Couverture'));
const cover = computed(() => props.profile.media.find((media) => media.type_label === 'Couverture') ?? null);

const formatDuration = (minutes: number | null): string => {
    if (!minutes) {
        return 'Durée non précisée';
    }

    if (minutes < 60) {
        return `${minutes} min`;
    }

    const hours = Math.floor(minutes / 60);
    const rest = minutes % 60;

    return rest ? `${hours} h ${rest}` : `${hours} h`;
};

const formatDate = (value: string | null): string => {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium' }).format(new Date(value));
};

const whatsappShareUrl = (sheet: PricingSheet): string => {
    const text = `${sheet.name} — ${props.profile.business_name}\n${sheet.public_url}`;

    return `https://wa.me/?text=${encodeURIComponent(text)}`;
};

const copyPricingLink = async (sheet: PricingSheet) => {
    try {
        await navigator.clipboard.writeText(sheet.public_url);
        copiedSheet.value = sheet.id;
        window.setTimeout(() => {
            copiedSheet.value = null;
        }, 2200);
    } catch {
        window.prompt('Copiez le lien de cette fiche tarifaire :', sheet.public_url);
    }
};
</script>

<template>
    <Head :title="profile.business_name" />

    <PublicLayout>
        <div class="page-container py-12 sm:py-16">
            <nav class="mb-6 flex flex-wrap items-center gap-1.5 text-sm text-muted-foreground" aria-label="Fil d'Ariane">
                <Link :href="route('categories.index')" class="hover:text-foreground">Catégories</Link>
                <span aria-hidden="true">/</span>
                <Link v-if="profile.category" :href="route('categories.show', profile.category.slug)" class="hover:text-foreground">
                    {{ profile.category.name }}
                </Link>
                <span aria-hidden="true">/</span>
                <span class="text-foreground">{{ profile.business_name }}</span>
            </nav>

            <header class="flex flex-col gap-6 border-b border-border/70 pb-8 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <StatusBadge tone="success" label="Profil vérifié" />
                        <StatusBadge v-if="!profile.is_available" tone="neutral" label="Indisponible" />
                    </div>

                    <h1 class="mt-3 font-display text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">
                        {{ profile.business_name }}
                    </h1>

                    <p class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted-foreground">
                        <span v-if="profile.category" class="inline-flex items-center gap-1.5">
                            <Store class="size-4" aria-hidden="true" />
                            {{ profile.category.name }}
                            <span v-if="profile.subcategory">· {{ profile.subcategory.name }}</span>
                        </span>
                        <span v-if="profile.business" class="inline-flex items-center gap-1.5"> · {{ profile.business.name }} </span>
                    </p>

                    <p class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-sm text-muted-foreground">
                        <span class="inline-flex items-center gap-1.5">
                            <MapPin class="size-4" aria-hidden="true" />
                            {{ profile.city }}<span v-if="profile.commune"> · {{ profile.commune }}</span
                            ><span v-if="profile.quartier"> · {{ profile.quartier }}</span>
                        </span>
                        <span v-if="profile.rating_count > 0" class="inline-flex items-center gap-1.5">
                            {{ profile.rating_average.toFixed(1) }} / 5
                            <span class="text-xs text-muted-foreground/80">({{ profile.rating_count }} avis)</span>
                        </span>
                        <span v-else class="inline-flex items-center gap-1.5">
                            <BadgeCheck class="size-4" aria-hidden="true" />
                            Vérifié le {{ formatDate(profile.verified_at) }}
                        </span>
                    </p>
                </div>

                <div class="flex shrink-0 flex-wrap gap-2">
                    <Button as-child variant="brand">
                        <Link :href="route('requests.create', { profil: profile.slug })">
                            <MessageSquare class="size-4" aria-hidden="true" />
                            Demander un devis
                        </Link>
                    </Button>
                    <FavoriteButton :profile-id="profile.id" :favorited="isFavorited" :authenticated="canFavorite" />
                    <Button v-if="profile.phone" as-child variant="outline">
                        <a :href="`tel:${profile.phone}`">
                            <Phone class="size-4" aria-hidden="true" />
                            {{ profile.phone }}
                        </a>
                    </Button>
                </div>
            </header>

            <div class="mt-8 flex gap-1 border-b border-border/70" role="tablist" aria-label="Sections du profil">
                <button
                    type="button"
                    role="tab"
                    :aria-selected="activeTab === 'presentation'"
                    class="-mb-px inline-flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-medium transition"
                    :class="
                        activeTab === 'presentation'
                            ? 'border-brand-600 text-brand-700 dark:text-brand-300'
                            : 'border-transparent text-muted-foreground hover:text-foreground'
                    "
                    @click="activeTab = 'presentation'"
                >
                    Présentation
                </button>
                <button
                    type="button"
                    role="tab"
                    :aria-selected="activeTab === 'tarifs'"
                    class="-mb-px inline-flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-medium transition"
                    :class="
                        activeTab === 'tarifs'
                            ? 'border-brand-600 text-brand-700 dark:text-brand-300'
                            : 'border-transparent text-muted-foreground hover:text-foreground'
                    "
                    @click="activeTab = 'tarifs'"
                >
                    Tarifs
                    <span
                        v-if="pricingSheets.length"
                        class="inline-flex min-w-5 items-center justify-center rounded-full bg-muted px-1.5 py-0.5 text-[10px] font-semibold text-muted-foreground"
                    >
                        {{ pricingSheets.length }}
                    </span>
                </button>
            </div>

            <div class="mt-8 grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                <div class="min-w-0 space-y-6">
                    <template v-if="activeTab === 'presentation'">
                        <Card v-if="profile.description">
                            <CardHeader>
                                <CardTitle class="text-base">Présentation</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p class="whitespace-pre-line text-sm leading-relaxed text-muted-foreground">
                                    {{ profile.description }}
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <CardTitle class="text-base">Services proposés</CardTitle>
                                        <CardDescription class="mt-1">
                                            {{ profile.services.length }} service{{ profile.services.length > 1 ? 's' : '' }} au catalogue.
                                        </CardDescription>
                                    </div>
                                </div>
                            </CardHeader>

                            <CardContent>
                                <ul v-if="profile.services.length" class="space-y-3">
                                    <li
                                        v-for="service in profile.services"
                                        :key="service.id"
                                        class="flex flex-wrap items-start justify-between gap-3 rounded-xl border border-border/70 bg-background p-4"
                                    >
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-foreground">
                                                <Link
                                                    :href="route('services.show', service.id)"
                                                    class="hover:text-brand-700 dark:hover:text-brand-300"
                                                >
                                                    {{ service.name }}
                                                </Link>
                                            </p>
                                            <p v-if="service.description" class="mt-1 text-xs leading-relaxed text-muted-foreground">
                                                {{ service.description }}
                                            </p>
                                            <p class="mt-1.5 inline-flex items-center gap-1.5 text-xs text-muted-foreground">
                                                <Clock class="size-3.5" aria-hidden="true" />
                                                {{ formatDuration(service.duration_minutes) }}
                                            </p>
                                        </div>

                                        <div class="shrink-0 text-right">
                                            <p class="inline-flex items-center gap-1.5 text-sm font-medium text-foreground">
                                                <Wallet class="size-3.5" aria-hidden="true" />
                                                {{ service.formatted_price }}
                                            </p>
                                        </div>
                                    </li>
                                </ul>
                                <p v-else class="text-sm text-muted-foreground">Aucun service publié pour le moment.</p>
                            </CardContent>
                        </Card>

                        <Card v-if="gallery.length || cover">
                            <CardHeader>
                                <CardTitle class="text-base">Réalisations</CardTitle>
                                <CardDescription>Quelques images de l’activité du professionnel.</CardDescription>
                            </CardHeader>

                            <CardContent>
                                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                                    <figure
                                        v-for="media in cover ? [cover, ...gallery] : gallery"
                                        :key="media.id"
                                        class="overflow-hidden rounded-xl border border-border/70"
                                    >
                                        <img :src="media.url" :alt="media.caption ?? media.type_label" class="h-32 w-full object-cover" />
                                        <figcaption class="px-2.5 py-1.5 text-[11px] text-muted-foreground">
                                            {{ media.caption ?? media.type_label }}
                                        </figcaption>
                                    </figure>
                                </div>
                            </CardContent>
                        </Card>

                        <Card id="avis">
                            <CardHeader>
                                <CardTitle class="text-base">Avis clients</CardTitle>
                                <CardDescription>
                                    {{
                                        profile.rating_count > 0
                                            ? `${profile.rating_count} avis publié${profile.rating_count > 1 ? 's' : ''} · note moyenne ${profile.rating_average.toFixed(1)} / 5`
                                            : 'Aucun avis publié pour le moment.'
                                    }}
                                </CardDescription>
                            </CardHeader>

                            <CardContent>
                                <ul v-if="reviews.length" class="space-y-4">
                                    <li v-for="review in reviews" :key="review.id" class="rounded-xl border border-border/70 bg-background p-4">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <StarRating :model-value="review.rating" readonly size="sm" />
                                                <span class="text-xs font-medium text-foreground">{{ review.customer ?? 'Client vérifié' }}</span>
                                            </div>
                                            <span class="text-[11px] text-muted-foreground">{{ formatDate(review.published_at) }}</span>
                                        </div>

                                        <p v-if="review.comment" class="mt-2 whitespace-pre-line text-sm leading-relaxed text-muted-foreground">
                                            {{ review.comment }}
                                        </p>

                                        <div
                                            v-if="review.professional_reply"
                                            class="mt-3 rounded-lg border border-brand-100 bg-brand-50/50 p-3 dark:border-brand-900/70 dark:bg-brand-950/20"
                                        >
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.1em] text-brand-700 dark:text-brand-300">
                                                Réponse du professionnel
                                            </p>
                                            <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-muted-foreground">
                                                {{ review.professional_reply }}
                                            </p>
                                        </div>
                                    </li>
                                </ul>

                                <div v-else class="flex flex-col items-center gap-2 py-6 text-center">
                                    <Star class="size-6 text-border" aria-hidden="true" />
                                    <p class="text-sm text-muted-foreground">
                                        Les avis apparaîtront ici après une prestation terminée et validée par notre équipe.
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </template>

                    <template v-else>
                        <Card v-if="pricingSheets.length === 0">
                            <CardHeader>
                                <CardTitle class="text-base">Aucun tarif publié</CardTitle>
                                <CardDescription> Le professionnel n’a pas encore publié de fiche tarifaire. </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div
                                    class="flex flex-col items-center gap-2 rounded-xl border border-dashed border-border bg-muted/30 p-8 text-center"
                                >
                                    <ReceiptText class="size-6 text-border" aria-hidden="true" />
                                    <p class="text-sm text-muted-foreground">Les tarifs apparaîtront ici dès leur publication.</p>
                                    <Button as-child variant="brand" class="mt-2">
                                        <Link :href="route('requests.create', { profil: profile.slug })">
                                            <MessageSquare class="size-4" aria-hidden="true" />
                                            Demander un devis
                                        </Link>
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>

                        <article
                            v-for="sheet in pricingSheets"
                            :key="sheet.id"
                            class="overflow-hidden rounded-xl border border-border/80 bg-card shadow-soft"
                        >
                            <div class="border-b border-border/70 p-5 sm:p-6">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <h2 class="font-display text-xl font-semibold tracking-tight text-foreground">
                                            {{ sheet.name }}
                                        </h2>
                                        <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                                            <span v-if="sheet.category" class="inline-flex items-center gap-1.5">
                                                <Store class="size-3.5" aria-hidden="true" />
                                                {{ sheet.category.name }}
                                            </span>
                                            <span class="inline-flex items-center gap-1.5">
                                                <CalendarDays class="size-3.5" aria-hidden="true" />
                                                Mise à jour : {{ formatDate(sheet.updated_at) }}
                                            </span>
                                            <span v-if="sheet.valid_until" class="inline-flex items-center gap-1.5">
                                                <CalendarDays class="size-3.5" aria-hidden="true" />
                                                Valable jusqu’au {{ formatDate(sheet.valid_until) }}
                                            </span>
                                        </p>
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        <Button as-child variant="outline" size="sm">
                                            <a :href="sheet.pdf_url">
                                                <Download class="size-3.5" aria-hidden="true" />
                                                PDF
                                            </a>
                                        </Button>
                                        <Button as-child variant="outline" size="sm">
                                            <a :href="whatsappShareUrl(sheet)" target="_blank" rel="noopener noreferrer">
                                                <Share2 class="size-3.5" aria-hidden="true" />
                                                WhatsApp
                                            </a>
                                        </Button>
                                        <Button variant="outline" size="sm" @click="copyPricingLink(sheet)">
                                            <Check v-if="copiedSheet === sheet.id" class="size-3.5 text-success" aria-hidden="true" />
                                            <Copy v-else class="size-3.5" aria-hidden="true" />
                                            {{ copiedSheet === sheet.id ? 'Lien copié' : 'Copier le lien' }}
                                        </Button>
                                    </div>
                                </div>

                                <p v-if="sheet.description" class="mt-3 text-sm leading-relaxed text-muted-foreground">
                                    {{ sheet.description }}
                                </p>
                            </div>

                            <div class="divide-y divide-border/70">
                                <div
                                    v-for="(item, index) in sheet.items"
                                    :key="item.id"
                                    class="flex flex-col gap-3 p-5 sm:flex-row sm:items-start sm:justify-between sm:p-6"
                                >
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span
                                                class="flex size-6 items-center justify-center rounded-full bg-brand-50 text-[11px] font-semibold text-brand-700 dark:bg-brand-950/60 dark:text-brand-300"
                                            >
                                                {{ index + 1 }}
                                            </span>
                                            <p class="text-sm font-semibold text-foreground">{{ item.name }}</p>
                                            <span
                                                class="inline-flex items-center rounded-full border border-border bg-muted px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-muted-foreground"
                                            >
                                                {{ item.pricing_type_label }}
                                            </span>
                                            <span
                                                v-if="item.requires_diagnostic"
                                                class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-medium text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-200"
                                            >
                                                Sur diagnostic
                                            </span>
                                            <span
                                                v-else-if="item.is_quote"
                                                class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-medium text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-200"
                                            >
                                                Sur devis
                                            </span>
                                        </div>

                                        <p v-if="item.description" class="mt-2 text-sm leading-relaxed text-muted-foreground">
                                            {{ item.description }}
                                        </p>

                                        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground">
                                            <span v-if="item.category" class="inline-flex items-center gap-1.5">
                                                <Store class="size-3.5" aria-hidden="true" />
                                                {{ item.category.name }}
                                            </span>
                                            <span v-if="item.service_name" class="inline-flex items-center gap-1.5">
                                                <ReceiptText class="size-3.5" aria-hidden="true" />
                                                {{ item.service_name }}
                                            </span>
                                            <span v-if="item.formatted_duration" class="inline-flex items-center gap-1.5">
                                                <Clock class="size-3.5" aria-hidden="true" />
                                                {{ item.formatted_duration }}
                                            </span>
                                            <span v-if="item.billing_unit" class="inline-flex items-center gap-1.5">
                                                <ReceiptText class="size-3.5" aria-hidden="true" />
                                                {{ item.billing_unit }}
                                            </span>
                                        </div>

                                        <p v-if="item.conditions" class="mt-2 text-xs leading-relaxed text-muted-foreground">
                                            <strong class="font-medium text-foreground">Conditions :</strong> {{ item.conditions }}
                                        </p>
                                    </div>

                                    <div class="flex shrink-0 flex-col items-start gap-2 sm:items-end">
                                        <p class="text-base font-semibold text-brand-700 dark:text-brand-300">
                                            {{ item.formatted_price }}
                                        </p>
                                        <p v-if="!item.has_price" class="max-w-[200px] text-right text-[11px] leading-relaxed text-muted-foreground">
                                            Aucun montant fictif : le tarif sera précisé après échange.
                                        </p>
                                        <Button v-if="item.quote_url" as-child variant="outline" size="sm">
                                            <Link :href="item.quote_url">
                                                <MessageSquare class="size-3.5" aria-hidden="true" />
                                                Demander un devis
                                            </Link>
                                        </Button>
                                    </div>
                                </div>
                            </div>

                            <div v-if="sheet.general_conditions" class="border-t border-border/70 bg-muted/30 p-5 sm:p-6">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">Conditions générales</p>
                                <p class="mt-1.5 whitespace-pre-line text-sm leading-relaxed text-muted-foreground">
                                    {{ sheet.general_conditions }}
                                </p>
                            </div>
                        </article>

                        <p v-if="pricingSheets.length" class="flex items-start gap-2 text-xs leading-relaxed text-muted-foreground">
                            <ReceiptText class="mt-0.5 size-3.5 shrink-0" aria-hidden="true" />
                            Les tarifs sont fournis par le professionnel. Demandez un devis pour confirmer le montant final.
                        </p>
                    </template>
                </div>

                <aside class="space-y-4">
                    <Card v-if="profile.areas.length">
                        <CardHeader>
                            <CardTitle class="text-base">Zones d’intervention</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ul class="space-y-1.5 text-sm text-muted-foreground">
                                <li v-for="area in profile.areas" :key="`${area.city}-${area.commune}`">
                                    {{ area.city }}<span v-if="area.commune"> · {{ area.commune }}</span
                                    ><span v-if="area.quartier"> · {{ area.quartier }}</span>
                                    <span v-if="area.is_primary" class="ml-1 text-xs">(principale)</span>
                                </li>
                            </ul>
                        </CardContent>
                    </Card>

                    <Card v-if="profile.hours.length">
                        <CardHeader>
                            <CardTitle class="text-base">Horaires</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ul class="space-y-1.5 text-sm text-muted-foreground">
                                <li v-for="hour in profile.hours" :key="hour.day_of_week" class="flex justify-between gap-3">
                                    <span>{{ dayLabels[hour.day_of_week] }}</span>
                                    <span v-if="hour.is_closed">Fermé</span>
                                    <span v-else>{{ hour.opens_at }} – {{ hour.closes_at }}</span>
                                </li>
                            </ul>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">Informations</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2.5 text-sm text-muted-foreground">
                            <p v-if="profile.years_experience" class="flex items-center gap-2">
                                <CalendarClock class="size-4" aria-hidden="true" />
                                {{ profile.years_experience }} an{{ profile.years_experience > 1 ? 's' : '' }} d’expérience
                            </p>
                            <p v-if="profile.response_time_hours" class="flex items-center gap-2">
                                <Clock class="size-4" aria-hidden="true" />
                                Répond en environ {{ profile.response_time_hours }} h
                            </p>
                            <p v-if="profile.whatsapp" class="flex items-center gap-2">
                                <MessageSquare class="size-4" aria-hidden="true" />
                                WhatsApp : {{ profile.whatsapp }}
                            </p>
                            <p v-if="profile.address" class="flex items-start gap-2">
                                <MapPin class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                                {{ profile.address }}
                            </p>
                        </CardContent>
                    </Card>
                </aside>
            </div>
        </div>
    </PublicLayout>
</template>
