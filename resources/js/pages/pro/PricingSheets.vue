<script setup lang="ts">
import FormSelect from '@/components/FormSelect.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, StatusTone } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Archive, CalendarDays, Copy, Eye, FileText, Globe, LoaderCircle, Pencil, Plus, ReceiptText, Send, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

type ProfileOption = {
    id: number;
    business_name: string;
    slug: string;
    category: string | null;
    category_id: number | null;
    is_publicly_visible: boolean;
};

type SheetSummary = {
    id: number;
    name: string;
    status: string;
    status_label: string;
    status_tone: string;
    is_expired: boolean;
    currency: string;
    valid_until: string | null;
    items_count: number;
    available_items_count: number;
    profile: { id: number; business_name: string; slug: string } | null;
    category: string | null;
    updated_at: string | null;
    public_url: string;
};

type Props = {
    sheets: {
        data: SheetSummary[];
        current_page: number;
        last_page: number;
        total: number;
    };
    profiles: ProfileOption[];
    stats: {
        total: number;
        published: number;
        draft: number;
        archived: number;
    };
    statuses: Array<{ value: string; label: string; tone: string }>;
    filters: {
        statut: string;
        profil: number | null;
    };
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Fiches tarifaires',
        href: '/pro/tarifs',
    },
];

const filters = ref({
    statut: props.filters.statut ?? '',
    profil: props.filters.profil ? String(props.filters.profil) : '',
});

const processingSheet = ref<number | null>(null);
const copiedSheet = ref<number | null>(null);

const statusOptions = computed(() => [
    { value: '', label: 'Tous les statuts' },
    ...props.statuses.map((status) => ({ value: status.value, label: status.label })),
]);

const profileOptions = computed(() => [
    { value: '', label: 'Tous les profils' },
    ...props.profiles.map((profile) => ({ value: String(profile.id), label: profile.business_name })),
]);

const stats = computed(() => [
    {
        label: 'Fiches au total',
        value: props.stats.total,
        icon: ReceiptText,
        tone: 'text-brand-600 bg-brand-50 dark:bg-brand-950/60 dark:text-brand-300',
    },
    {
        label: 'Publiées',
        value: props.stats.published,
        icon: Globe,
        tone: 'text-success bg-success/10',
    },
    {
        label: 'Brouillons',
        value: props.stats.draft,
        icon: FileText,
        tone: 'text-amber-700 bg-amber-50 dark:bg-amber-950/40 dark:text-amber-300',
    },
    {
        label: 'Archivées',
        value: props.stats.archived,
        icon: Archive,
        tone: 'text-muted-foreground bg-muted',
    },
]);

const tone = (value: string): StatusTone => {
    if (value === 'success' || value === 'warning' || value === 'danger' || value === 'info' || value === 'brand') {
        return value;
    }

    return 'neutral';
};

const formatDate = (value: string | null): string => {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium' }).format(new Date(value));
};

const applyFilters = () => {
    router.get(
        route('pro.pricing-sheets.index'),
        {
            statut: filters.value.statut || undefined,
            profil: filters.value.profil || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const goToPage = (page: number) => {
    if (page < 1 || page > props.sheets.last_page) {
        return;
    }

    router.get(
        route('pro.pricing-sheets.index'),
        {
            page,
            statut: filters.value.statut || undefined,
            profil: filters.value.profil || undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
};

const changeStatus = (sheet: SheetSummary, status: string) => {
    const labels: Record<string, string> = {
        published: 'Publier cette fiche ? Elle deviendra visible sur votre profil public.',
        draft: 'Repasser cette fiche en brouillon ? Elle ne sera plus visible publiquement.',
        archived: 'Archiver cette fiche ? Elle restera consultable dans votre espace professionnel.',
    };

    if (!window.confirm(labels[status] ?? 'Confirmer ce changement de statut ?')) {
        return;
    }

    processingSheet.value = sheet.id;

    router.patch(
        route('pro.pricing-sheets.status', sheet.id),
        { status },
        {
            preserveScroll: true,
            onFinish: () => {
                processingSheet.value = null;
            },
        },
    );
};

const duplicateSheet = (sheet: SheetSummary) => {
    processingSheet.value = sheet.id;

    router.post(
        route('pro.pricing-sheets.duplicate', sheet.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                processingSheet.value = null;
            },
        },
    );
};

const destroySheet = (sheet: SheetSummary) => {
    if (!window.confirm(`Supprimer définitivement « ${sheet.name} » et toutes ses prestations ?`)) {
        return;
    }

    processingSheet.value = sheet.id;

    router.delete(route('pro.pricing-sheets.destroy', sheet.id), {
        preserveScroll: true,
        onFinish: () => {
            processingSheet.value = null;
        },
    });
};

const copyPublicLink = async (sheet: SheetSummary) => {
    try {
        await navigator.clipboard.writeText(sheet.public_url);
        copiedSheet.value = sheet.id;
        window.setTimeout(() => {
            copiedSheet.value = null;
        }, 2200);
    } catch {
        window.prompt('Copiez le lien public de cette fiche :', sheet.public_url);
    }
};
</script>

<template>
    <Head title="Fiches tarifaires" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader
                eyebrow="Espace professionnel"
                title="Fiches tarifaires"
                description="Créez des fiches claires, organisez vos prestations et partagez vos tarifs avec vos clients."
            >
                <template #actions>
                    <Button as-child variant="brand">
                        <Link :href="route('pro.pricing-sheets.create')">
                            <Plus class="size-4" aria-hidden="true" />
                            Nouvelle fiche
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <Card v-for="stat in stats" :key="stat.label">
                    <CardContent class="flex items-center gap-3 p-4 sm:p-5">
                        <span :class="['flex size-10 shrink-0 items-center justify-center rounded-xl', stat.tone]">
                            <component :is="stat.icon" class="size-5" aria-hidden="true" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-xl font-semibold tabular-nums text-foreground">{{ stat.value }}</p>
                            <p class="truncate text-xs text-muted-foreground">{{ stat.label }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card v-if="profiles.length === 0">
                <CardHeader>
                    <CardTitle class="text-base">Aucun profil professionnel</CardTitle>
                    <CardDescription>
                        Une fiche tarifaire est toujours rattachée à un profil. Créez d’abord votre profil professionnel.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Button as-child variant="brand">
                        <Link :href="route('pro.profiles.create')">
                            <Plus class="size-4" aria-hidden="true" />
                            Créer mon profil
                        </Link>
                    </Button>
                </CardContent>
            </Card>

            <template v-else>
                <Card>
                    <CardContent class="grid gap-3 p-4 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] sm:items-end sm:p-5">
                        <div class="grid gap-1.5">
                            <label for="filter-profil" class="text-xs font-medium text-muted-foreground">Profil</label>
                            <FormSelect id="filter-profil" v-model="filters.profil" :options="profileOptions" @change="applyFilters" />
                        </div>

                        <div class="grid gap-1.5">
                            <label for="filter-statut" class="text-xs font-medium text-muted-foreground">Statut</label>
                            <FormSelect id="filter-statut" v-model="filters.statut" :options="statusOptions" @change="applyFilters" />
                        </div>

                        <Button variant="outline" @click="applyFilters">
                            <ReceiptText class="size-4" aria-hidden="true" />
                            Filtrer
                        </Button>
                    </CardContent>
                </Card>

                <Card v-if="sheets.total === 0">
                    <CardHeader>
                        <CardTitle class="text-base">Aucune fiche tarifaire</CardTitle>
                        <CardDescription>
                            {{
                                filters.statut || filters.profil
                                    ? 'Aucune fiche ne correspond à ces filtres.'
                                    : 'Présentez vos prix par prestation, en toute transparence.'
                            }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="rounded-xl border border-dashed border-border bg-muted/30 p-6 text-center">
                            <span
                                class="mx-auto flex size-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-950/60 dark:text-brand-300"
                            >
                                <ReceiptText class="size-5" aria-hidden="true" />
                            </span>
                            <p class="mt-3 text-sm font-medium text-foreground">Créez votre première fiche tarifaire</p>
                            <p class="mx-auto mt-1 max-w-md text-sm text-muted-foreground">
                                Ajoutez vos prestations, leurs prix et leurs conditions, puis publiez la fiche sur votre profil.
                            </p>
                            <Button as-child variant="brand" class="mt-4">
                                <Link :href="route('pro.pricing-sheets.create')">
                                    <Plus class="size-4" aria-hidden="true" />
                                    Créer une fiche
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <Card v-for="sheet in sheets.data" :key="sheet.id" class="flex flex-col">
                        <CardHeader>
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <CardTitle class="truncate text-base">{{ sheet.name }}</CardTitle>
                                    <CardDescription class="mt-1 truncate">
                                        {{ sheet.profile?.business_name ?? 'Profil non précisé' }}
                                        <span v-if="sheet.category"> · {{ sheet.category }}</span>
                                    </CardDescription>
                                </div>
                                <StatusBadge :tone="tone(sheet.status_tone)" :label="sheet.status_label" />
                            </div>
                        </CardHeader>

                        <CardContent class="flex flex-1 flex-col justify-between gap-4">
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div class="rounded-lg border border-border/60 bg-muted/30 px-3 py-2">
                                    <p class="text-[11px] uppercase tracking-wide text-muted-foreground">Prestations</p>
                                    <p class="mt-0.5 font-medium text-foreground">
                                        {{ sheet.available_items_count }}
                                        <span class="text-xs font-normal text-muted-foreground">/ {{ sheet.items_count }} disponibles</span>
                                    </p>
                                </div>

                                <div class="rounded-lg border border-border/60 bg-muted/30 px-3 py-2">
                                    <p class="text-[11px] uppercase tracking-wide text-muted-foreground">Mise à jour</p>
                                    <p class="mt-0.5 flex items-center gap-1.5 font-medium text-foreground">
                                        <CalendarDays class="size-3.5 text-muted-foreground" aria-hidden="true" />
                                        {{ formatDate(sheet.updated_at) }}
                                    </p>
                                </div>
                            </div>

                            <p
                                v-if="sheet.valid_until"
                                class="text-xs"
                                :class="sheet.is_expired ? 'font-medium text-destructive' : 'text-muted-foreground'"
                            >
                                {{ sheet.is_expired ? 'Validité dépassée le' : 'Valable jusqu’au' }}
                                {{ formatDate(sheet.valid_until) }}
                            </p>

                            <div class="flex flex-wrap items-center gap-1.5 border-t border-border/60 pt-3">
                                <Button as-child variant="outline" size="sm">
                                    <Link :href="route('pro.pricing-sheets.show', sheet.id)">
                                        <Pencil class="size-3.5" aria-hidden="true" />
                                        Modifier
                                    </Link>
                                </Button>

                                <Button as-child variant="ghost" size="icon-sm" title="Aperçu de la fiche">
                                    <a :href="route('pro.pricing-sheets.preview', sheet.id)" target="_blank" rel="noopener noreferrer">
                                        <Eye class="size-4" aria-hidden="true" />
                                        <span class="sr-only">Aperçu</span>
                                    </a>
                                </Button>

                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    :title="copiedSheet === sheet.id ? 'Lien copié' : 'Copier le lien public'"
                                    @click="copyPublicLink(sheet)"
                                >
                                    <Copy class="size-4" aria-hidden="true" />
                                    <span class="sr-only">Copier le lien public</span>
                                </Button>

                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    title="Dupliquer la fiche"
                                    :disabled="processingSheet === sheet.id"
                                    @click="duplicateSheet(sheet)"
                                >
                                    <LoaderCircle v-if="processingSheet === sheet.id" class="size-4 animate-spin" aria-hidden="true" />
                                    <FileText v-else class="size-4" aria-hidden="true" />
                                    <span class="sr-only">Dupliquer</span>
                                </Button>

                                <Button
                                    v-if="sheet.status !== 'published'"
                                    variant="ghost"
                                    size="icon-sm"
                                    title="Publier la fiche"
                                    :disabled="processingSheet === sheet.id"
                                    @click="changeStatus(sheet, 'published')"
                                >
                                    <Send class="size-4" aria-hidden="true" />
                                    <span class="sr-only">Publier</span>
                                </Button>
                                <Button
                                    v-else
                                    variant="ghost"
                                    size="icon-sm"
                                    title="Repasser en brouillon"
                                    :disabled="processingSheet === sheet.id"
                                    @click="changeStatus(sheet, 'draft')"
                                >
                                    <Pencil class="size-4" aria-hidden="true" />
                                    <span class="sr-only">Dépublier</span>
                                </Button>

                                <Button
                                    v-if="sheet.status !== 'archived'"
                                    variant="ghost"
                                    size="icon-sm"
                                    title="Archiver la fiche"
                                    :disabled="processingSheet === sheet.id"
                                    @click="changeStatus(sheet, 'archived')"
                                >
                                    <Archive class="size-4" aria-hidden="true" />
                                    <span class="sr-only">Archiver</span>
                                </Button>

                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    class="ml-auto text-destructive hover:text-destructive"
                                    title="Supprimer la fiche"
                                    :disabled="processingSheet === sheet.id"
                                    @click="destroySheet(sheet)"
                                >
                                    <Trash2 class="size-4" aria-hidden="true" />
                                    <span class="sr-only">Supprimer</span>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div v-if="sheets.last_page > 1" class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-xs text-muted-foreground">
                        Page {{ sheets.current_page }} sur {{ sheets.last_page }} · {{ sheets.total }} fiche{{ sheets.total > 1 ? 's' : '' }}
                    </p>

                    <div class="flex gap-2">
                        <Button variant="outline" size="sm" :disabled="sheets.current_page <= 1" @click="goToPage(sheets.current_page - 1)">
                            Précédent
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="sheets.current_page >= sheets.last_page"
                            @click="goToPage(sheets.current_page + 1)"
                        >
                            Suivant
                        </Button>
                    </div>
                </div>
            </template>
        </div>
    </AppLayout>
</template>
