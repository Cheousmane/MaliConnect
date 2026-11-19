<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import PublicLayout from '@/layouts/public/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Layers, Search } from 'lucide-vue-next';

type Subcategory = { id: number; name: string; slug: string };

type Category = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    icon: string | null;
    color: string | null;
    profiles_count: number;
    subcategories: Subcategory[];
};

defineProps<{
    categories: Category[];
}>();
</script>

<template>
    <Head title="Catégories de services" />

    <PublicLayout>
        <div class="page-container py-14 sm:py-20">
            <PageHeader
                eyebrow="Catalogue"
                title="Catégories de services"
                description="Cinq grands domaines pour trouver le bon professionnel, et leurs sous-catégories détaillées."
            >
                <template #actions>
                    <Button as-child variant="brand">
                        <Link :href="route('search')">
                            <Search class="size-4" aria-hidden="true" />
                            Rechercher un professionnel
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <div class="mt-10 grid gap-4 lg:grid-cols-2">
                <Card v-for="category in categories" :key="category.id" class="flex flex-col">
                    <CardHeader>
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <CardTitle class="text-base">
                                    <Link :href="route('categories.show', category.slug)" class="hover:text-brand-700 dark:hover:text-brand-300">
                                        {{ category.name }}
                                    </Link>
                                </CardTitle>
                                <CardDescription class="mt-1">{{ category.description }}</CardDescription>
                            </div>
                            <span
                                v-if="category.color"
                                class="size-3 shrink-0 rounded-full"
                                :style="{ backgroundColor: category.color }"
                                aria-hidden="true"
                            />
                        </div>
                    </CardHeader>

                    <CardContent class="flex flex-1 flex-col justify-between gap-4">
                        <div>
                            <p class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-[0.1em] text-muted-foreground">
                                <Layers class="size-3.5" aria-hidden="true" />
                                {{ category.subcategories.length }} sous-catégorie{{ category.subcategories.length > 1 ? 's' : '' }}
                            </p>

                            <ul class="mt-3 flex flex-wrap gap-2">
                                <li v-for="subcategory in category.subcategories" :key="subcategory.id">
                                    <Link
                                        :href="`${route('categories.show', category.slug)}?sous-categorie=${subcategory.slug}`"
                                        class="inline-flex items-center rounded-full border border-border/70 bg-background px-3 py-1.5 text-xs text-muted-foreground transition-colors hover:border-brand-200 hover:text-foreground dark:hover:border-brand-800"
                                    >
                                        {{ subcategory.name }}
                                    </Link>
                                </li>
                            </ul>
                        </div>

                        <div class="flex items-center justify-between gap-3 pt-1">
                            <span class="text-xs text-muted-foreground">
                                {{ category.profiles_count }} professionnel{{ category.profiles_count > 1 ? 's' : '' }} vérifié{{
                                    category.profiles_count > 1 ? 's' : ''
                                }}
                            </span>
                            <Link
                                :href="route('categories.show', category.slug)"
                                class="inline-flex items-center gap-1 text-sm font-medium text-brand-700 underline-offset-4 hover:underline dark:text-brand-300"
                            >
                                Voir la catégorie
                                <ArrowRight class="size-3.5" aria-hidden="true" />
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </PublicLayout>
</template>
