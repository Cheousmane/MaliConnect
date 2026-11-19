<script setup lang="ts">
import ProfessionalCard from '@/components/public/ProfessionalCard.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import PublicLayout from '@/layouts/public/PublicLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

type Subcategory = { id: number; name: string; slug: string };

type Category = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    icon: string | null;
    color: string | null;
    subcategories: Subcategory[];
};

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
    category: { id: number; name: string; slug: string } | null;
    subcategory: { id: number; name: string; slug: string } | null;
    business: { id: number; name: string } | null;
    services_count: number;
    media_count: number;
};

const props = defineProps<{
    category: Category;
    selectedSubcategory: string;
    profiles: {
        data: Professional[];
        current_page: number;
        last_page: number;
        total: number;
    };
}>();

const keyword = ref('');
const page = ref(props.profiles.current_page);

const pageUrl = (target: number): string => {
    const url = new URL(window.location.href);
    url.searchParams.set('page', String(target));

    return url.toString();
};

const selectSubcategory = (slug: string) => {
    const url = new URL(window.location.href);

    if (slug) {
        url.searchParams.set('sous-categorie', slug);
    } else {
        url.searchParams.delete('sous-categorie');
    }

    url.searchParams.delete('page');

    router.get(url.toString(), {}, { preserveScroll: true });
};

const submit = () => {
    const url = new URL(window.location.href);

    if (keyword.value) {
        url.searchParams.set('q', keyword.value);
    } else {
        url.searchParams.delete('q');
    }

    url.searchParams.delete('page');

    router.get(url.toString(), {}, { preserveScroll: true });
};

const hasFilters = computed(() => props.profiles.total > 0);

watch(
    () => props.profiles.current_page,
    (value) => {
        page.value = value;
    },
);
</script>

<template>
    <Head :title="category.name" />

    <PublicLayout>
        <div class="page-container py-14 sm:py-20">
            <nav class="mb-6 flex items-center gap-1.5 text-sm text-muted-foreground" aria-label="Fil d'Ariane">
                <Link :href="route('categories.index')" class="hover:text-foreground">Catégories</Link>
                <span aria-hidden="true">/</span>
                <span class="text-foreground">{{ category.name }}</span>
            </nav>

            <header class="max-w-2xl">
                <h1 class="font-display text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">
                    {{ category.name }}
                </h1>
                <p v-if="category.description" class="mt-3 text-base leading-relaxed text-muted-foreground">
                    {{ category.description }}
                </p>
            </header>

            <form class="mt-8 flex max-w-xl items-center gap-2" @submit.prevent="submit">
                <div class="relative flex-1">
                    <Search class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
                    <input
                        v-model="keyword"
                        type="search"
                        placeholder="Rechercher dans cette catégorie"
                        class="h-11 w-full rounded-lg border border-input bg-background pl-10 pr-3.5 text-sm text-foreground shadow-[0_1px_2px_0_rgb(16_24_40_/_0.03)]"
                        aria-label="Rechercher dans cette catégorie"
                    />
                </div>
                <Button type="submit" variant="brand">Rechercher</Button>
            </form>

            <div v-if="category.subcategories.length" class="mt-8">
                <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">Sous-catégories</p>
                <ul class="mt-3 flex flex-wrap gap-2">
                    <li>
                        <button
                            type="button"
                            :class="
                                selectedSubcategory === ''
                                    ? 'rounded-full bg-brand-600 px-3.5 py-1.5 text-xs font-medium text-white'
                                    : 'rounded-full border border-border/70 bg-background px-3.5 py-1.5 text-xs text-muted-foreground transition-colors hover:text-foreground'
                            "
                            @click="selectSubcategory('')"
                        >
                            Toutes
                        </button>
                    </li>
                    <li v-for="subcategory in category.subcategories" :key="subcategory.id">
                        <button
                            type="button"
                            :class="
                                selectedSubcategory === subcategory.slug
                                    ? 'rounded-full bg-brand-600 px-3.5 py-1.5 text-xs font-medium text-white'
                                    : 'rounded-full border border-border/70 bg-background px-3.5 py-1.5 text-xs text-muted-foreground transition-colors hover:text-foreground'
                            "
                            @click="selectSubcategory(subcategory.slug)"
                        >
                            {{ subcategory.name }}
                        </button>
                    </li>
                </ul>
            </div>

            <p class="mt-10 text-sm text-muted-foreground">
                {{ profiles.total }} professionnel{{ profiles.total > 1 ? 's' : '' }} vérifié{{ profiles.total > 1 ? 's' : '' }}
            </p>

            <div v-if="hasFilters" class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <ProfessionalCard v-for="professional in profiles.data" :key="professional.id" :professional="professional" />
            </div>

            <Card v-else class="mt-5 border-dashed">
                <CardContent class="py-12 text-center">
                    <p class="text-sm font-medium text-foreground">Aucun professionnel vérifié dans cette catégorie</p>
                    <p class="mx-auto mt-1 max-w-md text-sm text-muted-foreground">
                        Le catalogue se construit progressivement. Écrrivez à l’équipe pour faire connaître votre activité.
                    </p>
                    <Button as-child variant="outline" class="mt-5">
                        <Link :href="route('search')">Voir tous les professionnels</Link>
                    </Button>
                </CardContent>
            </Card>

            <nav v-if="profiles.last_page > 1" class="mt-8 flex items-center justify-between gap-3" aria-label="Pagination">
                <Button as-child variant="outline" size="sm" :class="page <= 1 ? 'pointer-events-none opacity-50' : ''">
                    <Link :href="pageUrl(page - 1)">
                        <ChevronLeft class="size-3.5" aria-hidden="true" />
                        Précédent
                    </Link>
                </Button>

                <p class="text-xs text-muted-foreground">Page {{ profiles.current_page }} sur {{ profiles.last_page }}</p>

                <Button as-child variant="outline" size="sm" :class="page >= profiles.last_page ? 'pointer-events-none opacity-50' : ''">
                    <Link :href="pageUrl(page + 1)">
                        Suivant
                        <ChevronRight class="size-3.5" aria-hidden="true" />
                    </Link>
                </Button>
            </nav>
        </div>
    </PublicLayout>
</template>
