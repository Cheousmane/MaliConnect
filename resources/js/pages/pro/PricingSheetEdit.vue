<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import PricingSheetEditor from '@/components/pricing/PricingSheetEditor.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, StatusTone } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Archive,
    ArrowLeft,
    CalendarDays,
    CheckCircle2,
    Copy,
    Download,
    Eye,
    FileText,
    Globe,
    LoaderCircle,
    Pencil,
    Send,
    Trash2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

type ServiceOption = { id: number; name: string };

type CategoryOption = { id: number; name: string };

type PricingTypeOption = {
    value: string;
    label: string;
    requires_price: boolean;
    unit_suffix: string | null;
};

type SheetItem = {
    id: number;
    service_id: number | null;
    service_name: string | null;
    category_id: number | null;
    category_name: string | null;
    name: string;
    description: string | null;
    pricing_type: string;
    pricing_type_label: string;
    price: string | number | null;
    price_min: string | number | null;
    price_max: string | number | null;
    formatted_price: string;
    billing_unit: string | null;
    conditions: string | null;
    estimated_duration_minutes: number | null;
    formatted_duration: string | null;
    is_available: boolean;
    requires_diagnostic: boolean;
    sort_order: number;
};

type ManageSheet = {
    id: number;
    provider_profile_id: number;
    name: string;
    description: string | null;
    currency: string;
    currency_label: string;
    general_conditions: string | null;
    status: string;
    status_label: string;
    status_tone: string;
    valid_until: string | null;
    published_at: string | null;
    archived_at: string | null;
    created_at: string | null;
    updated_at: string | null;
    is_expired: boolean;
    category: { id: number; name: string } | null;
    profile: { id: number; business_name: string; slug: string } | null;
    public_url: string;
    pdf_url: string;
    items: SheetItem[];
};

const props = defineProps<{
    sheet: ManageSheet;
    categories: CategoryOption[];
    pricingTypes: PricingTypeOption[];
    statuses: Array<{ value: string; label: string; tone: string }>;
    services: ServiceOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Fiches tarifaires',
        href: '/pro/tarifs',
    },
    {
        title: props.sheet.name,
        href: `/pro/tarifs/${props.sheet.id}`,
    },
];

const statusForm = useForm({ status: '' });
const processing = ref(false);
const copied = ref(false);

const tone = (value: string): StatusTone => {
    if (value === 'success' || value === 'warning' || value === 'danger' || value === 'info' || value === 'brand') {
        return value;
    }

    return 'neutral';
};

const isPublic = computed(() => props.sheet.status === 'published' && !props.sheet.is_expired);

const availableItems = computed(() => props.sheet.items.filter((item) => item.is_available).length);

const formatDate = (value: string | null): string => {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium' }).format(new Date(value));
};

const changeStatus = (status: string) => {
    const labels: Record<string, string> = {
        published: 'Publier cette fiche ? Elle deviendra visible sur votre profil public.',
        draft: 'Repasser cette fiche en brouillon ? Elle ne sera plus visible publiquement.',
        archived: 'Archiver cette fiche ? Elle restera consultable dans votre espace professionnel.',
    };

    if (!window.confirm(labels[status] ?? 'Confirmer ce changement de statut ?')) {
        return;
    }

    statusForm.status = status;
    statusForm.patch(route('pro.pricing-sheets.status', props.sheet.id), { preserveScroll: true });
};

const duplicateSheet = () => {
    processing.value = true;

    router.post(
        route('pro.pricing-sheets.duplicate', props.sheet.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                processing.value = false;
            },
        },
    );
};

const destroySheet = () => {
    if (!window.confirm(`Supprimer définitivement « ${props.sheet.name} » et toutes ses prestations ?`)) {
        return;
    }

    processing.value = true;

    router.delete(route('pro.pricing-sheets.destroy', props.sheet.id), {
        onFinish: () => {
            processing.value = false;
        },
    });
};

const copyPublicLink = async () => {
    try {
        await navigator.clipboard.writeText(props.sheet.public_url);
        copied.value = true;
        window.setTimeout(() => {
            copied.value = false;
        }, 2200);
    } catch {
        window.prompt('Copiez le lien public de cette fiche :', props.sheet.public_url);
    }
};
</script>

<template>
    <Head :title="`Fiche tarifaire — ${sheet.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader eyebrow="Espace professionnel" :title="sheet.name" :description="sheet.profile?.business_name ?? 'Fiche tarifaire'">
                <template #actions>
                    <Button as-child variant="ghost">
                        <Link :href="route('pro.pricing-sheets.index')">
                            <ArrowLeft class="size-4" aria-hidden="true" />
                            Retour
                        </Link>
                    </Button>
                    <Button as-child variant="outline">
                        <a :href="route('pro.pricing-sheets.preview', sheet.id)" target="_blank" rel="noopener noreferrer">
                            <Eye class="size-4" aria-hidden="true" />
                            Aperçu
                        </a>
                    </Button>
                    <Button v-if="isPublic" as-child variant="outline">
                        <a :href="sheet.public_url" target="_blank" rel="noopener noreferrer">
                            <Globe class="size-4" aria-hidden="true" />
                            Voir en public
                        </a>
                    </Button>
                    <Button v-if="isPublic" as-child variant="outline">
                        <a :href="sheet.pdf_url">
                            <Download class="size-4" aria-hidden="true" />
                            PDF
                        </a>
                    </Button>
                </template>
            </PageHeader>

            <Card>
                <CardHeader>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <CardTitle class="text-base">Cycle de vie de la fiche</CardTitle>
                                <StatusBadge :tone="tone(sheet.status_tone)" :label="sheet.status_label" />
                                <StatusBadge v-if="sheet.is_expired" tone="danger" label="Validité dépassée" />
                            </div>
                            <CardDescription class="mt-1.5">
                                {{ availableItems }} prestation{{ availableItems > 1 ? 's' : '' }} disponible{{ availableItems > 1 ? 's' : '' }} sur
                                {{ sheet.items.length }}.
                                <span v-if="sheet.valid_until"> · Valable jusqu’au {{ formatDate(sheet.valid_until) }}. </span>
                            </CardDescription>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <Button
                                v-if="sheet.status !== 'published'"
                                variant="brand"
                                size="sm"
                                :disabled="statusForm.processing"
                                @click="changeStatus('published')"
                            >
                                <LoaderCircle
                                    v-if="statusForm.processing && statusForm.status === 'published'"
                                    class="size-4 animate-spin"
                                    aria-hidden="true"
                                />
                                <Send v-else class="size-4" aria-hidden="true" />
                                Publier
                            </Button>
                            <Button v-else variant="outline" size="sm" :disabled="statusForm.processing" @click="changeStatus('draft')">
                                <LoaderCircle
                                    v-if="statusForm.processing && statusForm.status === 'draft'"
                                    class="size-4 animate-spin"
                                    aria-hidden="true"
                                />
                                <Pencil v-else class="size-4" aria-hidden="true" />
                                Repasser en brouillon
                            </Button>

                            <Button
                                v-if="sheet.status !== 'archived'"
                                variant="outline"
                                size="sm"
                                :disabled="statusForm.processing"
                                @click="changeStatus('archived')"
                            >
                                <LoaderCircle
                                    v-if="statusForm.processing && statusForm.status === 'archived'"
                                    class="size-4 animate-spin"
                                    aria-hidden="true"
                                />
                                <Archive v-else class="size-4" aria-hidden="true" />
                                Archiver
                            </Button>
                        </div>
                    </div>

                    <p v-if="statusForm.errors.status" class="mt-2 text-xs font-medium text-destructive" role="alert">
                        {{ statusForm.errors.status }}
                    </p>
                </CardHeader>

                <CardContent class="space-y-4">
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-lg border border-border/60 bg-muted/30 px-3 py-2.5">
                            <p class="text-[11px] uppercase tracking-wide text-muted-foreground">Créée le</p>
                            <p class="mt-0.5 flex items-center gap-1.5 text-sm font-medium text-foreground">
                                <CalendarDays class="size-3.5 text-muted-foreground" aria-hidden="true" />
                                {{ formatDate(sheet.created_at) }}
                            </p>
                        </div>
                        <div class="rounded-lg border border-border/60 bg-muted/30 px-3 py-2.5">
                            <p class="text-[11px] uppercase tracking-wide text-muted-foreground">Mise à jour</p>
                            <p class="mt-0.5 flex items-center gap-1.5 text-sm font-medium text-foreground">
                                <CalendarDays class="size-3.5 text-muted-foreground" aria-hidden="true" />
                                {{ formatDate(sheet.updated_at) }}
                            </p>
                        </div>
                        <div class="rounded-lg border border-border/60 bg-muted/30 px-3 py-2.5">
                            <p class="text-[11px] uppercase tracking-wide text-muted-foreground">Publication</p>
                            <p class="mt-0.5 text-sm font-medium text-foreground">{{ formatDate(sheet.published_at) }}</p>
                        </div>
                        <div class="rounded-lg border border-border/60 bg-muted/30 px-3 py-2.5">
                            <p class="text-[11px] uppercase tracking-wide text-muted-foreground">Devise</p>
                            <p class="mt-0.5 text-sm font-medium text-foreground">{{ sheet.currency_label }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2 border-t border-border/60 pt-3">
                        <Button variant="outline" size="sm" @click="copyPublicLink">
                            <CheckCircle2 v-if="copied" class="size-4 text-success" aria-hidden="true" />
                            <Copy v-else class="size-4" aria-hidden="true" />
                            {{ copied ? 'Lien copié' : 'Copier le lien public' }}
                        </Button>

                        <Button variant="outline" size="sm" :disabled="processing" @click="duplicateSheet">
                            <LoaderCircle v-if="processing" class="size-4 animate-spin" aria-hidden="true" />
                            <FileText v-else class="size-4" aria-hidden="true" />
                            Dupliquer
                        </Button>

                        <Button
                            variant="ghost"
                            size="sm"
                            class="ml-auto text-destructive hover:text-destructive"
                            :disabled="processing"
                            @click="destroySheet"
                        >
                            <Trash2 class="size-4" aria-hidden="true" />
                            Supprimer la fiche
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <PricingSheetEditor mode="edit" :sheet="sheet" :categories="categories" :pricing-types="pricingTypes" :services="services" />
        </div>
    </AppLayout>
</template>
