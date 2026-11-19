<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import PricingSheetEditor from '@/components/pricing/PricingSheetEditor.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Plus, ReceiptText, Store } from 'lucide-vue-next';

type ServiceOption = { id: number; name: string };

type ProfileOption = {
    id: number;
    business_name: string;
    slug: string;
    category: string | null;
    category_id: number | null;
    is_publicly_visible: boolean;
    services?: ServiceOption[];
};

type CategoryOption = { id: number; name: string };

type PricingTypeOption = {
    value: string;
    label: string;
    requires_price: boolean;
    unit_suffix: string | null;
};

const props = defineProps<{
    profiles: ProfileOption[];
    selectedProfile: { id: number; business_name: string; slug: string; category_id: number | null } | null;
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
        title: 'Nouvelle fiche',
        href: '/pro/tarifs/nouveau',
    },
];
</script>

<template>
    <Head title="Nouvelle fiche tarifaire" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader
                eyebrow="Espace professionnel"
                title="Nouvelle fiche tarifaire"
                description="Regroupez vos prestations, leurs prix et leurs conditions dans une fiche claire et partageable."
            >
                <template #actions>
                    <Button as-child variant="ghost">
                        <Link :href="route('pro.pricing-sheets.index')">
                            <ArrowLeft class="size-4" aria-hidden="true" />
                            Retour aux fiches
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <Card v-if="props.profiles.length === 0">
                <CardHeader>
                    <CardTitle class="text-base">Créez d’abord un profil professionnel</CardTitle>
                    <CardDescription>
                        Les fiches tarifaires sont rattachées à un profil. Votre profil porte votre nom commercial et vos coordonnées.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="rounded-xl border border-dashed border-border bg-muted/30 p-6 text-center">
                        <span
                            class="mx-auto flex size-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-950/60 dark:text-brand-300"
                        >
                            <Store class="size-5" aria-hidden="true" />
                        </span>
                        <p class="mt-3 text-sm font-medium text-foreground">Aucun profil disponible</p>
                        <p class="mx-auto mt-1 max-w-md text-sm text-muted-foreground">
                            Créez votre profil professionnel, puis revenez ici pour composer vos tarifs.
                        </p>
                        <Button as-child variant="brand" class="mt-4">
                            <Link :href="route('pro.profiles.create')">
                                <Plus class="size-4" aria-hidden="true" />
                                Créer mon profil
                            </Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <template v-else>
                <div
                    class="flex items-start gap-3 rounded-xl border border-brand-100 bg-brand-50/60 p-4 text-sm text-brand-900 dark:border-brand-900/70 dark:bg-brand-950/30 dark:text-brand-100"
                >
                    <ReceiptText class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                    <p class="leading-relaxed">
                        La fiche est créée en brouillon. Vous pourrez l’apercevoir, la compléter puis la publier depuis sa page de gestion.
                    </p>
                </div>

                <PricingSheetEditor
                    mode="create"
                    :profiles="props.profiles"
                    :selected-profile="props.selectedProfile"
                    :categories="props.categories"
                    :pricing-types="props.pricingTypes"
                    :services="props.services"
                />
            </template>
        </div>
    </AppLayout>
</template>
