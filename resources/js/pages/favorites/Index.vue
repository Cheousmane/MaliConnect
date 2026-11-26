<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, Heart, HeartOff, MapPin, Star } from 'lucide-vue-next';
import { ref } from 'vue';

type ProfileSummary = {
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
    logo_url: string | null;
};

type Favorite = {
    id: number;
    created_at: string | null;
    profile: ProfileSummary;
};

type Props = {
    favorites: {
        data: Favorite[];
        current_page: number;
        last_page: number;
        total: number;
    };
};

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Mes favoris', href: '/favoris' }];

const removing = ref<number | null>(null);

const remove = (favorite: Favorite) => {
    removing.value = favorite.id;

    router.delete(route('favorites.destroy', favorite.profile.id), {
        preserveScroll: true,
        onFinish: () => {
            removing.value = null;
        },
    });
};

const pageUrl = (page: number): string => route('favorites.index', { page });
</script>

<template>
    <Head title="Mes favoris" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader
                eyebrow="Favoris"
                title="Mes favoris"
                description="Retrouvez les professionnels que vous souhaitez consulter ou contacter plus tard."
            >
                <template #actions>
                    <Button as-child variant="outline">
                        <Link :href="route('search')">Découvrir des professionnels</Link>
                    </Button>
                </template>
            </PageHeader>

            <div v-if="favorites.data.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <Card v-for="favorite in favorites.data" :key="favorite.id" class="flex flex-col">
                    <CardHeader class="flex-row items-start gap-3 space-y-0">
                        <div
                            class="flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-border/70 bg-brand-50 text-sm font-semibold text-brand-700 dark:bg-brand-950/60 dark:text-brand-300"
                        >
                            <img
                                v-if="favorite.profile.logo_url"
                                :src="favorite.profile.logo_url"
                                :alt="favorite.profile.business_name"
                                class="size-full object-cover"
                            />
                            <span v-else>{{ favorite.profile.business_name.slice(0, 2).toUpperCase() }}</span>
                        </div>

                        <div class="min-w-0 flex-1">
                            <CardTitle class="truncate text-base">
                                <Link
                                    :href="route('professionals.show', favorite.profile.slug)"
                                    class="hover:text-brand-700 dark:hover:text-brand-300"
                                >
                                    {{ favorite.profile.business_name }}
                                </Link>
                            </CardTitle>
                            <CardDescription class="mt-1 truncate">
                                {{ favorite.profile.category?.name ?? 'Professionnel' }}
                                <span v-if="favorite.profile.subcategory">· {{ favorite.profile.subcategory.name }}</span>
                            </CardDescription>
                        </div>

                        <Button
                            type="button"
                            variant="ghost"
                            size="icon-sm"
                            class="shrink-0 text-muted-foreground hover:text-destructive"
                            :disabled="removing === favorite.id"
                            :aria-label="`Retirer ${favorite.profile.business_name} des favoris`"
                            @click="remove(favorite)"
                        >
                            <HeartOff class="size-4" aria-hidden="true" />
                        </Button>
                    </CardHeader>

                    <CardContent class="flex flex-1 flex-col gap-4">
                        <p v-if="favorite.profile.description" class="line-clamp-2 text-sm leading-relaxed text-muted-foreground">
                            {{ favorite.profile.description }}
                        </p>

                        <div class="mt-auto flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                            <span class="inline-flex items-center gap-1.5">
                                <MapPin class="size-3.5" aria-hidden="true" />
                                {{ favorite.profile.city }}<span v-if="favorite.profile.commune"> · {{ favorite.profile.commune }}</span>
                            </span>
                            <span v-if="favorite.profile.rating_count > 0" class="inline-flex items-center gap-1.5">
                                <Star class="size-3.5 fill-gold-400 text-gold-500" aria-hidden="true" />
                                {{ favorite.profile.rating_average.toFixed(1) }} ({{ favorite.profile.rating_count }})
                            </span>
                            <span v-else class="inline-flex items-center gap-1.5">
                                <Heart class="size-3.5" aria-hidden="true" />
                                Nouveau favori
                            </span>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <Button as-child variant="soft" size="sm" class="flex-1 justify-between">
                                <Link :href="route('professionals.show', favorite.profile.slug)">
                                    Voir le profil
                                    <ArrowRight class="size-4" aria-hidden="true" />
                                </Link>
                            </Button>
                            <Button as-child variant="outline" size="sm">
                                <Link :href="route('requests.create', { provider: favorite.profile.slug })">Demander</Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <EmptyState
                v-else
                title="Aucun favori pour le moment"
                description="Ajoutez des professionnels à vos favoris depuis leur fiche publique pour les retrouver ici."
                :icon="Heart"
            >
                <template #action>
                    <Button as-child variant="brand">
                        <Link :href="route('search')">Explorer l’annuaire</Link>
                    </Button>
                </template>
            </EmptyState>

            <nav v-if="favorites.last_page > 1" class="flex items-center justify-between gap-3" aria-label="Pagination des favoris">
                <p class="text-xs text-muted-foreground">Page {{ favorites.current_page }} sur {{ favorites.last_page }}</p>
                <div class="flex items-center gap-2">
                    <Button as-child variant="outline" size="sm" :class="favorites.current_page <= 1 ? 'pointer-events-none opacity-50' : ''">
                        <Link :href="pageUrl(favorites.current_page - 1)">Précédent</Link>
                    </Button>
                    <Button
                        as-child
                        variant="outline"
                        size="sm"
                        :class="favorites.current_page >= favorites.last_page ? 'pointer-events-none opacity-50' : ''"
                    >
                        <Link :href="pageUrl(favorites.current_page + 1)">Suivant</Link>
                    </Button>
                </div>
            </nav>
        </div>
    </AppLayout>
</template>
