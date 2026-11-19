<script setup lang="ts">
import ProfessionalCard from '@/components/public/ProfessionalCard.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import PublicLayout from '@/layouts/public/PublicLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, Filter, LoaderCircle, Search, SlidersHorizontal, X } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

type Category = { id: number; name: string; slug: string };
type Subcategory = { id: number; name: string; slug: string; category_id: number };

type Professional = {
    id: number;
    business_name: string;
    slug: string;
    description: string | null;
    city: string;
    commune: string | null;
    quartier: string | null;
    is_available: boolean;
    rating_average: number;
    rating_count: number;
    category: Category | null;
    subcategory: Subcategory | null;
    business: { id: number; name: string } | null;
    services_count: number;
    media_count: number;
};

type Filters = {
    q: string;
    categorie: string;
    'sous-categorie': string;
    commune: string;
    quartier: string;
    'prix-min': string;
    'prix-max': string;
    'note-min': string;
    disponible: boolean;
    tri: string;
};

type Props = {
    results: {
        data: Professional[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filters: Filters;
    facets: {
        categories: Category[];
        subcategories: Subcategory[];
        communes: string[];
        prices: { min: number | null; max: number | null };
    };
    sorts: Array<{ value: string; label: string }>;
};

const props = defineProps<Props>();

const filters = ref<Filters>({ ...props.filters });
const panelOpen = ref(false);
const suggestions = ref<Array<{ type: string; label: string; url: string }>>([]);
const suggesting = ref(false);
let timer: ReturnType<typeof setTimeout> | null = null;

const availableSubcategories = computed(() =>
    props.facets.subcategories.filter((item) => String(item.category_id) === String(filters.value.categorie)),
);

const activeFilterCount = computed(() => {
    let count = 0;

    if (filters.value.categorie) count++;
    if (filters.value['sous-categorie']) count++;
    if (filters.value.commune) count++;
    if (filters.value.quartier) count++;
    if (filters.value['prix-min'] || filters.value['prix-max']) count++;
    if (filters.value['note-min']) count++;
    if (filters.value.disponible) count++;

    return count;
});

// La sous-catégorie suit la catégorie choisie.
watch(
    () => filters.value.categorie,
    () => {
        filters.value['sous-categorie'] = '';
    },
);

const buildUrl = (page?: number): string => {
    const url = new URL(window.location.href);
    const query = new URLSearchParams();

    if (filters.value.q) query.set('q', filters.value.q);
    if (filters.value.categorie) query.set('categorie', filters.value.categorie);
    if (filters.value['sous-categorie']) query.set('sous-categorie', filters.value['sous-categorie']);
    if (filters.value.commune) query.set('commune', filters.value.commune);
    if (filters.value.quartier) query.set('quartier', filters.value.quartier);
    if (filters.value['prix-min']) query.set('prix-min', filters.value['prix-min']);
    if (filters.value['prix-max']) query.set('prix-max', filters.value['prix-max']);
    if (filters.value['note-min']) query.set('note-min', filters.value['note-min']);
    if (filters.value.disponible) query.set('disponible', '1');
    if (filters.value.tri && filters.value.tri !== 'relevance') query.set('tri', filters.value.tri);

    url.search = query.toString();

    if (page && page > 1) {
        url.searchParams.set('page', String(page));
    }

    return url.toString();
};

const submit = (page?: number) => {
    panelOpen.value = false;
    suggestions.value = [];

    router.get(buildUrl(page), {}, { preserveScroll: true, preserveState: true });
};

const reset = () => {
    filters.value = {
        q: '',
        categorie: '',
        'sous-categorie': '',
        commune: '',
        quartier: '',
        'prix-min': '',
        'prix-max': '',
        'note-min': '',
        disponible: false,
        tri: 'relevance',
    };

    submit();
};

// Autocomplétion : les suggestions sont demandées après une courte pause.
const onKeywordInput = () => {
    if (timer) {
        clearTimeout(timer);
    }

    const term = filters.value.q.trim();

    if (term.length < 2) {
        suggestions.value = [];

        return;
    }

    timer = setTimeout(async () => {
        suggesting.value = true;

        try {
            const response = await fetch(`${route('search.suggestions')}?q=${encodeURIComponent(term)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            const payload = await response.json();

            suggestions.value = payload.data ?? [];
        } finally {
            suggesting.value = false;
        }
    }, 250);
};

const goToSuggestion = (url: string) => {
    suggestions.value = [];
    router.get(url, {}, { preserveScroll: true });
};

const pageUrl = (page: number): string => buildUrl(page);

onBeforeUnmount(() => {
    if (timer) {
        clearTimeout(timer);
    }
});
</script>

<template>
    <Head title="Rechercher un professionnel" />

    <PublicLayout>
        <div class="page-container py-12 sm:py-16">
            <header class="max-w-2xl">
                <h1 class="font-display text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">Trouver un professionnel</h1>
                <p class="mt-3 text-base leading-relaxed text-muted-foreground">
                    Recherchez par métier, quartier, prix ou disponibilité. Seuls les profils vérifiés par l’administration sont présentés.
                </p>
            </header>

            <form class="relative mt-8 flex max-w-3xl items-center gap-2" @submit.prevent="submit()">
                <div class="relative flex-1">
                    <Search class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
                    <input
                        v-model="filters.q"
                        type="search"
                        placeholder="Plombier, ménage, cours de maths…"
                        class="h-11 w-full rounded-lg border border-input bg-background pl-10 pr-3.5 text-sm text-foreground shadow-[0_1px_2px_0_rgb(16_24_40_/_0.03)]"
                        aria-label="Mot-clé"
                        autocomplete="off"
                        @input="onKeywordInput"
                    />

                    <LoaderCircle v-if="suggesting" class="absolute right-3 top-1/2 size-4 -translate-y-1/2 animate-spin text-muted-foreground" />

                    <ul
                        v-if="suggestions.length"
                        class="top-13 absolute left-0 right-0 z-20 overflow-hidden rounded-xl border border-border/80 bg-card p-1 shadow-elevated"
                    >
                        <li v-for="suggestion in suggestions" :key="suggestion.type + suggestion.label">
                            <button
                                type="button"
                                class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 text-left text-sm text-foreground transition-colors hover:bg-muted"
                                @click="goToSuggestion(suggestion.url)"
                            >
                                <span class="truncate">{{ suggestion.label }}</span>
                                <span class="shrink-0 text-[10px] uppercase tracking-wide text-muted-foreground">
                                    {{ suggestion.type }}
                                </span>
                            </button>
                        </li>
                    </ul>
                </div>

                <Button type="submit" variant="brand">Rechercher</Button>
                <Button type="button" variant="outline" class="lg:hidden" @click="panelOpen = !panelOpen">
                    <SlidersHorizontal class="size-4" aria-hidden="true" />
                    Filtres
                    <span v-if="activeFilterCount" class="ml-1 rounded-full bg-brand-600 px-1.5 text-[10px] text-white">
                        {{ activeFilterCount }}
                    </span>
                </Button>
            </form>

            <div class="mt-10 grid gap-6 lg:grid-cols-[260px_minmax(0,1fr)]">
                <aside
                    :class="
                        panelOpen
                            ? 'block rounded-2xl border border-border/70 bg-card p-5 shadow-soft'
                            : 'hidden lg:sticky lg:top-24 lg:block lg:self-start lg:rounded-2xl lg:border lg:border-border/70 lg:bg-card lg:p-5 lg:shadow-soft'
                    "
                >
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="flex items-center gap-2 text-sm font-semibold text-foreground">
                            <Filter class="size-4" aria-hidden="true" />
                            Filtres
                        </h2>
                        <button
                            v-if="activeFilterCount"
                            type="button"
                            class="text-xs font-medium text-muted-foreground transition-colors hover:text-foreground"
                            @click="reset"
                        >
                            Réinitialiser
                        </button>
                    </div>

                    <div class="mt-4 space-y-4">
                        <div>
                            <label for="categorie" class="text-[11px] font-semibold uppercase tracking-[0.1em] text-muted-foreground">
                                Type de professionnel
                            </label>
                            <select
                                id="categorie"
                                v-model="filters.categorie"
                                class="mt-1.5 h-10 w-full rounded-lg border border-input bg-background px-3 text-sm text-foreground"
                                @change="submit()"
                            >
                                <option value="">Toutes les catégories</option>
                                <option v-for="category in facets.categories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                        </div>

                        <div v-if="availableSubcategories.length">
                            <label for="sous-categorie" class="text-[11px] font-semibold uppercase tracking-[0.1em] text-muted-foreground">
                                Sous-catégorie
                            </label>
                            <select
                                id="sous-categorie"
                                v-model="filters['sous-categorie']"
                                class="mt-1.5 h-10 w-full rounded-lg border border-input bg-background px-3 text-sm text-foreground"
                                @change="submit()"
                            >
                                <option value="">Toutes</option>
                                <option v-for="subcategory in availableSubcategories" :key="subcategory.id" :value="subcategory.id">
                                    {{ subcategory.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label for="commune" class="text-[11px] font-semibold uppercase tracking-[0.1em] text-muted-foreground"> Commune </label>
                            <select
                                id="commune"
                                v-model="filters.commune"
                                class="mt-1.5 h-10 w-full rounded-lg border border-input bg-background px-3 text-sm text-foreground"
                                @change="submit()"
                            >
                                <option value="">Toutes les communes</option>
                                <option v-for="commune in facets.communes" :key="commune" :value="commune">{{ commune }}</option>
                            </select>
                        </div>

                        <div>
                            <label for="quartier" class="text-[11px] font-semibold uppercase tracking-[0.1em] text-muted-foreground">
                                Quartier
                            </label>
                            <input
                                id="quartier"
                                v-model="filters.quartier"
                                type="text"
                                placeholder="Badalabougou"
                                class="mt-1.5 h-10 w-full rounded-lg border border-input bg-background px-3 text-sm text-foreground"
                                @change="submit()"
                            />
                        </div>

                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.1em] text-muted-foreground">Budget (FCFA)</p>
                            <div class="mt-1.5 flex items-center gap-2">
                                <input
                                    v-model="filters['prix-min']"
                                    type="number"
                                    min="0"
                                    :placeholder="facets.prices.min?.toString() ?? '0'"
                                    class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm text-foreground"
                                    aria-label="Prix minimum"
                                />
                                <span class="text-xs text-muted-foreground">à</span>
                                <input
                                    v-model="filters['prix-max']"
                                    type="number"
                                    min="0"
                                    :placeholder="facets.prices.max?.toString() ?? '—'"
                                    class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm text-foreground"
                                    aria-label="Prix maximum"
                                />
                            </div>
                        </div>

                        <div>
                            <label for="note-min" class="text-[11px] font-semibold uppercase tracking-[0.1em] text-muted-foreground">
                                Note minimale
                            </label>
                            <select
                                id="note-min"
                                v-model="filters['note-min']"
                                class="mt-1.5 h-10 w-full rounded-lg border border-input bg-background px-3 text-sm text-foreground"
                                @change="submit()"
                            >
                                <option value="">Indifférente</option>
                                <option value="3">3 et plus</option>
                                <option value="4">4 et plus</option>
                                <option value="4.5">4,5 et plus</option>
                            </select>
                        </div>

                        <label class="flex items-center gap-2 text-sm text-foreground">
                            <input
                                v-model="filters.disponible"
                                type="checkbox"
                                class="size-4 rounded border-input text-brand-600 focus:ring-brand-500"
                                @change="submit()"
                            />
                            Disponible actuellement
                        </label>

                        <div class="rounded-lg border border-border/70 bg-muted/30 p-3">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.1em] text-muted-foreground">Statut de vérification</p>
                            <div class="mt-2">
                                <StatusBadge tone="success" label="Profils vérifiés uniquement" />
                            </div>
                            <p class="mt-2 text-[11px] leading-relaxed text-muted-foreground">
                                Les profils en attente, refusés ou suspendus ne sont jamais présentés publiquement.
                            </p>
                        </div>
                    </div>
                </aside>

                <div class="min-w-0">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-sm text-muted-foreground">{{ results.total }} résultat{{ results.total > 1 ? 's' : '' }}</p>

                        <label class="flex items-center gap-2 text-xs text-muted-foreground">
                            <span class="font-medium uppercase tracking-[0.1em]">Trier par</span>
                            <select
                                :value="filters.tri"
                                class="h-9 rounded-lg border border-input bg-background px-2.5 text-sm text-foreground"
                                @change="
                                    filters.tri = ($event.target as HTMLSelectElement).value;
                                    submit();
                                "
                            >
                                <option v-for="sort in sorts" :key="sort.value" :value="sort.value">{{ sort.label }}</option>
                            </select>
                        </label>
                    </div>

                    <div v-if="results.data.length" class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        <ProfessionalCard v-for="professional in results.data" :key="professional.id" :professional="professional" />
                    </div>

                    <Card v-else class="mt-5 border-dashed">
                        <CardContent class="py-12 text-center">
                            <span class="mx-auto flex size-11 items-center justify-center rounded-xl bg-muted text-muted-foreground">
                                <X class="size-5" aria-hidden="true" />
                            </span>
                            <p class="mt-3 text-sm font-medium text-foreground">Aucun professionnel ne correspond à votre recherche</p>
                            <p class="mx-auto mt-1 max-w-md text-sm text-muted-foreground">
                                Essayez un mot-clé plus large, une autre commune ou retirez un filtre.
                            </p>
                            <Button type="button" variant="outline" class="mt-5" @click="reset">Réinitialiser les filtres</Button>
                        </CardContent>
                    </Card>

                    <nav v-if="results.last_page > 1" class="mt-8 flex items-center justify-between gap-3" aria-label="Pagination">
                        <Button as-child variant="outline" size="sm" :class="results.current_page <= 1 ? 'pointer-events-none opacity-50' : ''">
                            <Link :href="pageUrl(results.current_page - 1)">
                                <ChevronLeft class="size-3.5" aria-hidden="true" />
                                Précédent
                            </Link>
                        </Button>

                        <p class="text-xs text-muted-foreground">Page {{ results.current_page }} sur {{ results.last_page }}</p>

                        <Button
                            as-child
                            variant="outline"
                            size="sm"
                            :class="results.current_page >= results.last_page ? 'pointer-events-none opacity-50' : ''"
                        >
                            <Link :href="pageUrl(results.current_page + 1)">
                                Suivant
                                <ChevronRight class="size-3.5" aria-hidden="true" />
                            </Link>
                        </Button>
                    </nav>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
