<script setup lang="ts">
import LocationMap from '@/components/LocationMap.vue';
import PageHeader from '@/components/PageHeader.vue';
import PhoneInput from '@/components/PhoneInput.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, LoaderCircle, Store } from 'lucide-vue-next';
import { computed, watch } from 'vue';

type Category = {
    id: number;
    name: string;
    subcategories: Array<{ id: number; name: string }>;
};

type Business = {
    id: number;
    name: string;
};

type Props = {
    categories: Category[];
    businesses: Business[];
};

const props = defineProps<Props>();

const page = usePage();

// L'entreprise porteuse peut être présélectionnée depuis la fiche entreprise.
const requestedBusiness = new URL(page.url, window.location.origin).searchParams.get('business') ?? '';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Mes profils',
        href: '/pro/profiles',
    },
    {
        title: 'Créer un profil',
        href: '/pro/profiles/create',
    },
];

const form = useForm({
    business_name: '',
    category_id: '',
    subcategory_id: '',
    business_id: requestedBusiness,
    description: '',
    phone: '',
    whatsapp: '',
    email: '',
    address: '',
    city: 'Bamako',
    commune: '',
    quartier: '',
    latitude: '',
    longitude: '',
    years_experience: '',
    response_time_hours: '',
    logo: null as File | null,
});

// Les sous-catégories dépendent de la catégorie choisie.
const subcategories = computed(() => props.categories.find((category) => String(category.id) === form.category_id)?.subcategories ?? []);

watch(
    () => form.category_id,
    () => {
        form.subcategory_id = '';
    },
);

const submit = () => {
    form.post(route('pro.profiles.store'), {
        forceFormData: true,
        preserveScroll: true,
    });
};

// Le champ de fichier est géré manuellement : le v-model renvoie une liste
// de fichiers, non un fichier unique.
const onLogoChange = (event: Event) => {
    const input = event.target as HTMLInputElement;

    form.logo = input.files?.[0] ?? null;
};
</script>

<template>
    <Head title="Créer un profil professionnel" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader
                eyebrow="Espace professionnel"
                title="Créer un profil professionnel"
                description="Présentez votre activité : nom commercial, catégorie, coordonnées et localisation. Le profil sera soumis à la vérification administrative."
            >
                <template #actions>
                    <Button as-child variant="outline">
                        <Link :href="route('pro.profiles.index')">
                            <ArrowLeft class="size-4" aria-hidden="true" />
                            Annuler
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <form class="grid gap-4 lg:grid-cols-2" @submit.prevent="submit">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Identité du profil</CardTitle>
                        <CardDescription>Le nom commercial est le nom affiché aux clients.</CardDescription>
                    </CardHeader>

                    <CardContent class="space-y-4">
                        <FormField label="Nom commercial" for="business_name" :error="form.errors.business_name" required>
                            <Input
                                id="business_name"
                                v-model="form.business_name"
                                required
                                maxlength="120"
                                placeholder="Atelier Koné"
                                :aria-invalid="!!form.errors.business_name"
                            />
                        </FormField>

                        <FormField label="Catégorie" for="category_id" :error="form.errors.category_id" required>
                            <select
                                id="category_id"
                                v-model="form.category_id"
                                required
                                class="h-11 w-full rounded-lg border border-input bg-background px-3.5 text-sm text-foreground"
                            >
                                <option value="">Choisir une catégorie</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                        </FormField>

                        <FormField label="Sous-catégorie" for="subcategory_id" :error="form.errors.subcategory_id">
                            <select
                                id="subcategory_id"
                                v-model="form.subcategory_id"
                                class="h-11 w-full rounded-lg border border-input bg-background px-3.5 text-sm text-foreground disabled:opacity-60"
                                :disabled="subcategories.length === 0"
                            >
                                <option value="">Non précisée</option>
                                <option v-for="subcategory in subcategories" :key="subcategory.id" :value="subcategory.id">
                                    {{ subcategory.name }}
                                </option>
                            </select>
                        </FormField>

                        <FormField
                            v-if="businesses.length"
                            label="Entreprise porteuse"
                            for="business_id"
                            :error="form.errors.business_id"
                            hint="Rattachez le profil à une entreprise que vous gérez."
                        >
                            <select
                                id="business_id"
                                v-model="form.business_id"
                                class="h-11 w-full rounded-lg border border-input bg-background px-3.5 text-sm text-foreground"
                            >
                                <option value="">Profil personnel</option>
                                <option v-for="business in businesses" :key="business.id" :value="business.id">
                                    {{ business.name }}
                                </option>
                            </select>
                        </FormField>

                        <FormField label="Description" for="description" :error="form.errors.description">
                            <Textarea
                                id="description"
                                v-model="form.description"
                                rows="4"
                                maxlength="2000"
                                placeholder="Présentez votre savoir-faire, vosIntervention et vos délais."
                            />
                        </FormField>

                        <FormField label="Logo ou photo" for="logo" :error="form.errors.logo" hint="JPEG, PNG ou WebP, 2 Mo maximum.">
                            <Input id="logo" type="file" accept="image/*" @change="onLogoChange" :aria-invalid="!!form.errors.logo" />
                        </FormField>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Coordonnées et localisation</CardTitle>
                        <CardDescription>Informations utilisées pour vous joindre et vous situer.</CardDescription>
                    </CardHeader>

                    <CardContent class="space-y-4">
                        <FormField label="Téléphone" for="phone" :error="form.errors.phone">
                            <PhoneInput id="phone" v-model="form.phone" placeholder="76 12 34 56" :invalid="!!form.errors.phone" />
                        </FormField>

                        <FormField label="WhatsApp" for="whatsapp" :error="form.errors.whatsapp">
                            <PhoneInput id="whatsapp" v-model="form.whatsapp" placeholder="76 12 34 56" :invalid="!!form.errors.whatsapp" />
                        </FormField>

                        <FormField label="Adresse e-mail" for="email" :error="form.errors.email">
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                maxlength="255"
                                placeholder="contact@exemple.com"
                                :aria-invalid="!!form.errors.email"
                            />
                        </FormField>

                        <FormField label="Adresse" for="address" :error="form.errors.address">
                            <Input id="address" v-model="form.address" maxlength="255" placeholder="Rue, point de repère" />
                        </FormField>

                        <div class="grid gap-4 sm:grid-cols-3">
                            <FormField label="Ville" for="city" :error="form.errors.city" required>
                                <Input id="city" v-model="form.city" required maxlength="100" />
                            </FormField>

                            <FormField label="Commune" for="commune" :error="form.errors.commune">
                                <Input id="commune" v-model="form.commune" maxlength="100" placeholder="Commune IV" />
                            </FormField>

                            <FormField label="Quartier" for="quartier" :error="form.errors.quartier">
                                <Input id="quartier" v-model="form.quartier" maxlength="100" placeholder="Badalabougou" />
                            </FormField>
                        </div>

                        <LocationMap
                            :latitude="form.latitude ? Number(form.latitude) : null"
                            :longitude="form.longitude ? Number(form.longitude) : null"
                            interactive
                            height="300px"
                            @update:latitude="form.latitude = String($event)"
                            @update:longitude="form.longitude = String($event)"
                        />

                        <div class="grid gap-4 sm:grid-cols-2">
                            <FormField label="Latitude" for="latitude" :error="form.errors.latitude">
                                <Input id="latitude" v-model="form.latitude" type="number" step="any" placeholder="12.6392" />
                            </FormField>

                            <FormField label="Longitude" for="longitude" :error="form.errors.longitude">
                                <Input id="longitude" v-model="form.longitude" type="number" step="any" placeholder="-8.0029" />
                            </FormField>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <FormField label="Années d’expérience" for="years_experience" :error="form.errors.years_experience">
                                <Input id="years_experience" v-model="form.years_experience" type="number" min="0" max="80" />
                            </FormField>

                            <FormField label="Délai de réponse (heures)" for="response_time_hours" :error="form.errors.response_time_hours">
                                <Input id="response_time_hours" v-model="form.response_time_hours" type="number" min="0" max="168" />
                            </FormField>
                        </div>
                    </CardContent>
                </Card>

                <div class="flex flex-wrap items-center gap-3 lg:col-span-2">
                    <Button type="submit" variant="brand" size="lg" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="size-4 animate-spin" aria-hidden="true" />
                        <Store v-else class="size-4" aria-hidden="true" />
                        Créer le profil
                    </Button>

                    <Button as-child variant="ghost" size="lg">
                        <Link :href="route('pro.profiles.index')">Annuler</Link>
                    </Button>

                    <p class="text-xs text-muted-foreground">Le profil est créé en attente de vérification et reste modifiable à tout moment.</p>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
