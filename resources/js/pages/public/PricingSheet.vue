<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import PublicLayout from '@/layouts/public/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    BadgeCheck,
    CalendarDays,
    Check,
    Clock,
    Copy,
    Download,
    Eye,
    FileText,
    Info,
    MapPin,
    MessageSquare,
    Phone,
    ReceiptText,
    Share2,
    Store,
    Wallet,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

type PricingItem = {
    id: number;
    name: string;
    description: string | null;
    category: { id: number; name: string } | null;
    service_id: number | null;
    service_name: string | null;
    pricing_type: string;
    pricing_type_label: string;
    price: string | null;
    price_min: string | null;
    price_max: string | null;
    formatted_price: string;
    billing_unit: string | null;
    conditions: string | null;
    estimated_duration_minutes: number | null;
    formatted_duration: string | null;
    is_available: boolean;
    requires_diagnostic: boolean;
    is_quote: boolean;
    has_price: boolean;
    quote_url: string | null;
};

type Sheet = {
    id: number;
    name: string;
    description: string | null;
    currency: string;
    currency_label: string;
    general_conditions: string | null;
    valid_until: string | null;
    updated_at: string | null;
    published_at: string | null;
    category: { id: number; name: string } | null;
    public_url: string;
    pdf_url: string;
    profile: {
        id: number;
        business_name: string;
        slug: string;
        phone: string | null;
        whatsapp: string | null;
        email: string | null;
        address: string | null;
        city: string | null;
        commune: string | null;
        logo_url: string | null;
    } | null;
    items: PricingItem[];
};

const props = defineProps<{
    sheet: Sheet;
    preview?: boolean;
    manageUrl?: string | null;
}>();

const copied = ref(false);

const profile = computed(() => props.sheet.profile);

const isExpired = computed(() => {
    if (!props.sheet.valid_until) {
        return false;
    }

    const end = new Date(props.sheet.valid_until);
    end.setHours(23, 59, 59, 999);

    return end.getTime() < Date.now();
});

const quoteItems = computed(() => props.sheet.items.filter((item) => item.is_quote || item.requires_diagnostic));

const formattedDate = (value: string | null): string => {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'long' }).format(new Date(value));
};

const whatsappShareUrl = computed(() => {
    const name = profile.value?.business_name ?? 'MaliConnect';
    const text = `${props.sheet.name} — ${name}\n${props.sheet.public_url}`;

    return `https://wa.me/?text=${encodeURIComponent(text)}`;
});

const copyPublicLink = async () => {
    try {
        await navigator.clipboard.writeText(props.sheet.public_url);
        copied.value = true;
        window.setTimeout(() => {
            copied.value = false;
        }, 2200);
    } catch {
        window.prompt('Copiez le lien de cette fiche tarifaire :', props.sheet.public_url);
    }
};

const initials = computed(() => {
    const name = profile.value?.business_name ?? 'MaliConnect';

    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
});
</script>

<template>
    <Head :title="`${sheet.name} — ${profile?.business_name ?? 'MaliConnect'}`" />

    <PublicLayout>
        <div v-if="preview" class="border-b border-amber-200 bg-amber-50 dark:border-amber-900/60 dark:bg-amber-950/30">
            <div class="page-container flex flex-wrap items-center justify-between gap-3 py-3">
                <p class="flex items-center gap-2 text-sm text-amber-900 dark:text-amber-200">
                    <Eye class="size-4 shrink-0" aria-hidden="true" />
                    <span>
                        <strong class="font-semibold">Aperçu professionnel.</strong>
                        Cette fiche n’est visible que par vous tant qu’elle n’est pas publiée.
                    </span>
                </p>
                <div class="flex flex-wrap gap-2">
                    <Button v-if="manageUrl" as-child variant="outline" size="sm">
                        <Link :href="manageUrl">
                            <ArrowLeft class="size-4" aria-hidden="true" />
                            Modifier la fiche
                        </Link>
                    </Button>
                </div>
            </div>
        </div>

        <div class="page-container py-10 sm:py-14">
            <header class="flex flex-col gap-6 border-b border-border/70 pb-8 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex min-w-0 items-start gap-4">
                    <img
                        v-if="profile?.logo_url"
                        :src="profile.logo_url"
                        :alt="`Logo de ${profile.business_name}`"
                        class="size-16 shrink-0 rounded-2xl border border-border/70 object-cover shadow-soft sm:size-20"
                    />
                    <span
                        v-else
                        class="flex size-16 shrink-0 items-center justify-center rounded-2xl bg-brand-600 font-display text-xl font-semibold text-white shadow-soft sm:size-20 sm:text-2xl"
                        aria-hidden="true"
                    >
                        {{ initials }}
                    </span>

                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full border border-brand-200 bg-brand-50 px-2.5 py-0.5 text-[11px] font-medium text-brand-700 dark:border-brand-900 dark:bg-brand-950/60 dark:text-brand-300"
                            >
                                <BadgeCheck class="size-3" aria-hidden="true" />
                                Fiche tarifaire
                            </span>
                            <span
                                v-if="isExpired"
                                class="inline-flex items-center gap-1.5 rounded-full border border-destructive/20 bg-destructive/10 px-2.5 py-0.5 text-[11px] font-medium text-destructive"
                            >
                                Validité dépassée
                            </span>
                        </div>

                        <h1 class="mt-2 font-display text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">
                            {{ sheet.name }}
                        </h1>

                        <p class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted-foreground">
                            <span v-if="profile" class="inline-flex items-center gap-1.5">
                                <Store class="size-4" aria-hidden="true" />
                                {{ profile.business_name }}
                            </span>
                            <span v-if="sheet.category" class="inline-flex items-center gap-1.5"> · {{ sheet.category.name }} </span>
                            <span v-if="profile?.city" class="inline-flex items-center gap-1.5">
                                <MapPin class="size-4" aria-hidden="true" />
                                {{ profile.city }}<span v-if="profile.commune"> · {{ profile.commune }}</span>
                            </span>
                        </p>

                        <p v-if="sheet.description" class="mt-3 max-w-2xl text-sm leading-relaxed text-muted-foreground">
                            {{ sheet.description }}
                        </p>
                    </div>
                </div>

                <div class="flex shrink-0 flex-wrap gap-2">
                    <Button v-if="!preview" as-child variant="brand">
                        <a :href="whatsappShareUrl" target="_blank" rel="noopener noreferrer">
                            <Share2 class="size-4" aria-hidden="true" />
                            Partager sur WhatsApp
                        </a>
                    </Button>
                    <Button v-if="!preview" as-child variant="outline">
                        <a :href="sheet.pdf_url">
                            <Download class="size-4" aria-hidden="true" />
                            Télécharger le PDF
                        </a>
                    </Button>
                    <Button v-if="!preview" variant="outline" @click="copyPublicLink">
                        <Check v-if="copied" class="size-4 text-success" aria-hidden="true" />
                        <Copy v-else class="size-4" aria-hidden="true" />
                        {{ copied ? 'Lien copié' : 'Copier le lien' }}
                    </Button>
                </div>
            </header>

            <div
                class="mt-6 flex flex-wrap items-center gap-x-5 gap-y-2 rounded-xl border border-border/70 bg-muted/30 px-4 py-3 text-xs text-muted-foreground"
            >
                <span class="inline-flex items-center gap-1.5">
                    <CalendarDays class="size-3.5" aria-hidden="true" />
                    Mise à jour : {{ formattedDate(sheet.updated_at) }}
                </span>
                <span v-if="sheet.valid_until" class="inline-flex items-center gap-1.5" :class="isExpired ? 'font-medium text-destructive' : ''">
                    <CalendarDays class="size-3.5" aria-hidden="true" />
                    {{ isExpired ? 'Validité dépassée le' : 'Valable jusqu’au' }} {{ formattedDate(sheet.valid_until) }}
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <Wallet class="size-3.5" aria-hidden="true" />
                    Devise : {{ sheet.currency_label }}
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <ReceiptText class="size-3.5" aria-hidden="true" />
                    {{ sheet.items.length }} prestation{{ sheet.items.length > 1 ? 's' : '' }}
                </span>
            </div>

            <div class="mt-8 grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                <div class="min-w-0 space-y-4">
                    <Card v-if="sheet.items.length === 0">
                        <CardHeader>
                            <CardTitle class="text-base">Aucune prestation disponible</CardTitle>
                            <CardDescription> Le professionnel n’a pas encore communiqué de tarif disponible pour cette fiche. </CardDescription>
                        </CardHeader>
                    </Card>

                    <article
                        v-for="(item, index) in sheet.items"
                        :key="item.id"
                        class="rounded-xl border border-border/80 bg-card p-5 shadow-soft sm:p-6"
                    >
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        class="flex size-6 items-center justify-center rounded-full bg-brand-50 text-[11px] font-semibold text-brand-700 dark:bg-brand-950/60 dark:text-brand-300"
                                    >
                                        {{ index + 1 }}
                                    </span>
                                    <h2 class="text-base font-semibold text-foreground">{{ item.name }}</h2>
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

                                <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-muted-foreground">
                                    <span v-if="item.category" class="inline-flex items-center gap-1.5">
                                        <Store class="size-3.5" aria-hidden="true" />
                                        {{ item.category.name }}
                                    </span>
                                    <span v-if="item.service_name" class="inline-flex items-center gap-1.5">
                                        <FileText class="size-3.5" aria-hidden="true" />
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

                                <p
                                    v-if="item.conditions"
                                    class="mt-3 rounded-lg border border-border/60 bg-muted/30 px-3 py-2 text-xs leading-relaxed text-muted-foreground"
                                >
                                    <strong class="font-medium text-foreground">Conditions :</strong> {{ item.conditions }}
                                </p>
                            </div>

                            <div class="flex shrink-0 flex-col items-start gap-2 sm:items-end">
                                <p class="text-lg font-semibold text-brand-700 dark:text-brand-300">{{ item.formatted_price }}</p>
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
                    </article>

                    <Card v-if="sheet.general_conditions">
                        <CardHeader>
                            <CardTitle class="text-base">Conditions générales</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="whitespace-pre-line text-sm leading-relaxed text-muted-foreground">
                                {{ sheet.general_conditions }}
                            </p>
                        </CardContent>
                    </Card>

                    <p
                        v-if="quoteItems.length"
                        class="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50/70 p-4 text-xs leading-relaxed text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200"
                    >
                        <Info class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                        Certaines prestations dépendent d’un diagnostic ou d’un devis personnalisé. Le professionnel vous répondra avec un tarif
                        adapté à votre besoin.
                    </p>
                </div>

                <aside class="space-y-4">
                    <Card v-if="profile">
                        <CardHeader>
                            <CardTitle class="text-base">Contacter le professionnel</CardTitle>
                            <CardDescription>{{ profile.business_name }}</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-2.5 text-sm">
                            <a
                                v-if="profile.whatsapp"
                                :href="`https://wa.me/${profile.whatsapp.replace(/[^0-9]/g, '')}`"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="flex items-center gap-2 rounded-lg border border-border/60 bg-background px-3 py-2 text-muted-foreground transition hover:border-brand-300 hover:text-brand-700 dark:hover:text-brand-300"
                            >
                                <MessageSquare class="size-4 shrink-0" aria-hidden="true" />
                                WhatsApp : {{ profile.whatsapp }}
                            </a>
                            <a
                                v-if="profile.phone"
                                :href="`tel:${profile.phone}`"
                                class="flex items-center gap-2 rounded-lg border border-border/60 bg-background px-3 py-2 text-muted-foreground transition hover:border-brand-300 hover:text-brand-700 dark:hover:text-brand-300"
                            >
                                <Phone class="size-4 shrink-0" aria-hidden="true" />
                                {{ profile.phone }}
                            </a>
                            <p
                                v-if="profile.address"
                                class="flex items-start gap-2 rounded-lg border border-border/60 bg-background px-3 py-2 text-muted-foreground"
                            >
                                <MapPin class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                                {{ profile.address }}
                                <span v-if="profile.city">, {{ profile.city }}</span>
                            </p>
                            <Button v-if="profile.slug" as-child variant="brand" class="w-full">
                                <Link :href="route('professionals.show', profile.slug)"> Voir le profil complet </Link>
                            </Button>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">Partager cette fiche</CardTitle>
                            <CardDescription>Envoyez les tarifs à un proche ou à un client.</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-2">
                            <Button v-if="!preview" as-child variant="outline" class="w-full justify-start">
                                <a :href="whatsappShareUrl" target="_blank" rel="noopener noreferrer">
                                    <Share2 class="size-4" aria-hidden="true" />
                                    Partager sur WhatsApp
                                </a>
                            </Button>
                            <Button v-if="!preview" variant="outline" class="w-full justify-start" @click="copyPublicLink">
                                <Check v-if="copied" class="size-4 text-success" aria-hidden="true" />
                                <Copy v-else class="size-4" aria-hidden="true" />
                                {{ copied ? 'Lien copié' : 'Copier le lien public' }}
                            </Button>
                            <Button v-if="!preview" as-child variant="outline" class="w-full justify-start">
                                <a :href="sheet.pdf_url">
                                    <Download class="size-4" aria-hidden="true" />
                                    Télécharger en PDF
                                </a>
                            </Button>
                            <p v-if="preview" class="text-xs leading-relaxed text-muted-foreground">
                                Les actions de partage seront disponibles après la publication de la fiche.
                            </p>
                        </CardContent>
                    </Card>

                    <p class="flex items-start gap-2 px-1 text-[11px] leading-relaxed text-muted-foreground">
                        <Info class="mt-0.5 size-3.5 shrink-0" aria-hidden="true" />
                        Les tarifs affichés sont fournis par le professionnel. Demandez toujours un devis pour confirmer le montant final.
                    </p>
                </aside>
            </div>
        </div>
    </PublicLayout>
</template>
