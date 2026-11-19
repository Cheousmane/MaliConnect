<script setup lang="ts">
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import PublicLayout from '@/layouts/public/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Clock, MapPin, MessageSquare, Star, Store, Wallet } from 'lucide-vue-next';

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

type Professional = {
    id: number;
    business_name: string;
    slug: string;
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
};

defineProps<{
    service: Service;
    professional: Professional;
}>();

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
</script>

<template>
    <Head :title="service.name" />

    <PublicLayout>
        <div class="page-container py-12 sm:py-16">
            <nav class="mb-6 flex flex-wrap items-center gap-1.5 text-sm text-muted-foreground" aria-label="Fil d'Ariane">
                <Link :href="route('categories.index')" class="hover:text-foreground">Catégories</Link>
                <span aria-hidden="true">/</span>
                <Link v-if="professional.category" :href="route('categories.show', professional.category.slug)" class="hover:text-foreground">
                    {{ professional.category.name }}
                </Link>
                <span aria-hidden="true">/</span>
                <span class="text-foreground">{{ service.name }}</span>
            </nav>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
                <div class="min-w-0">
                    <header>
                        <h1 class="font-display text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">
                            {{ service.name }}
                        </h1>
                        <p class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-sm text-muted-foreground">
                            <span class="inline-flex items-center gap-1.5">
                                <Wallet class="size-4" aria-hidden="true" />
                                {{ service.formatted_price }}
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <Clock class="size-4" aria-hidden="true" />
                                {{ formatDuration(service.duration_minutes) }}
                            </span>
                        </p>
                    </header>

                    <Card class="mt-6">
                        <CardHeader>
                            <CardTitle class="text-base">Description du service</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p v-if="service.description" class="whitespace-pre-line text-sm leading-relaxed text-muted-foreground">
                                {{ service.description }}
                            </p>
                            <p v-else class="text-sm text-muted-foreground">Le professionnel n’a pas encore détaillé cette prestation.</p>
                        </CardContent>
                    </Card>
                </div>

                <aside class="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">Professionnel</CardTitle>
                            <CardDescription class="mt-1">Cette prestation est proposée par un profil vérifié.</CardDescription>
                        </CardHeader>

                        <CardContent class="space-y-4">
                            <div>
                                <p class="font-display text-base font-semibold text-foreground">
                                    <Link
                                        :href="route('professionals.show', professional.slug)"
                                        class="hover:text-brand-700 dark:hover:text-brand-300"
                                    >
                                        {{ professional.business_name }}
                                    </Link>
                                </p>
                                <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-muted-foreground">
                                    <span v-if="professional.category" class="inline-flex items-center gap-1">
                                        <Store class="size-3.5" aria-hidden="true" />
                                        {{ professional.category.name }}
                                    </span>
                                    <span v-if="professional.subcategory">· {{ professional.subcategory.name }}</span>
                                </p>
                                <p class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                                    <span class="inline-flex items-center gap-1">
                                        <MapPin class="size-3.5" aria-hidden="true" />
                                        {{ professional.city }}<span v-if="professional.commune"> · {{ professional.commune }}</span>
                                    </span>
                                    <span v-if="professional.rating_count > 0" class="inline-flex items-center gap-1">
                                        <Star class="size-3.5 fill-gold-400 text-gold-400" aria-hidden="true" />
                                        {{ professional.rating_average.toFixed(1) }}
                                    </span>
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <StatusBadge tone="success" label="Profil vérifié" />
                                <StatusBadge v-if="!professional.is_available" tone="neutral" label="Indisponible" />
                            </div>

                            <Button as-child variant="brand" class="w-full">
                                <Link :href="route('requests.create', { profil: professional.slug })">
                                    <MessageSquare class="size-4" aria-hidden="true" />
                                    Demander cette prestation
                                </Link>
                            </Button>

                            <Button as-child variant="outline" class="w-full">
                                <Link :href="route('professionals.show', professional.slug)">Voir le profil complet</Link>
                            </Button>
                        </CardContent>
                    </Card>
                </aside>
            </div>
        </div>
    </PublicLayout>
</template>
