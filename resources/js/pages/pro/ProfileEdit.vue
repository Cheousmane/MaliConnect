<script setup lang="ts">
import FormField from '@/components/FormField.vue';
import PageHeader from '@/components/PageHeader.vue';
import PhoneInput from '@/components/PhoneInput.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { localPhoneNumber } from '@/lib/identity';
import type { BreadcrumbItem, StatusTone } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { AlertTriangle, CalendarClock, Image, LoaderCircle, MapPin, Pencil, Plus, Power, Send, Trash2, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

type Area = {
    id?: number;
    city: string;
    commune: string | null;
    quartier: string | null;
    is_primary?: boolean;
};

type Hour = {
    day_of_week: number;
    opens_at: string | null;
    closes_at: string | null;
    is_closed: boolean;
};

type Availability = {
    id?: number;
    date: string;
    starts_at: string;
    ends_at: string;
    label: string | null;
};

type Media = {
    id: number;
    type: string;
    type_label: string;
    url: string;
    caption: string | null;
};

type Service = {
    id: number;
    name: string;
    description: string | null;
    price_type: string;
    price_min: string | null;
    price_max: string | null;
    duration_minutes: number | null;
    formatted_price: string;
    is_active: boolean;
};

type Profile = {
    id: number;
    business_name: string;
    slug: string;
    status: string;
    status_label: string;
    status_tone: string;
    is_active: boolean;
    is_publicly_visible: boolean;
    description: string | null;
    logo_path: string | null;
    phone: string | null;
    whatsapp: string | null;
    email: string | null;
    address: string | null;
    city: string;
    commune: string | null;
    quartier: string | null;
    latitude: number | null;
    longitude: number | null;
    years_experience: number | null;
    refusal_reason: string | null;
    category: { id: number; name: string } | null;
    subcategory: { id: number; name: string } | null;
    business: { id: number; name: string } | null;
    areas: Area[];
    hours: Hour[];
    availabilities: Availability[];
    media: Media[];
    services: Service[];
};

type Category = {
    id: number;
    name: string;
    subcategories: Array<{ id: number; name: string }>;
};

type Props = {
    profile: Profile;
    categories: Category[];
    statuses: Array<{ value: string; label: string; tone: string }>;
    canSubmit: boolean;
    canManageMedia: boolean;
    canManageSchedule: boolean;
    canManageAreas: boolean;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Mes profils',
        href: '/pro/profiles',
    },
    {
        title: props.profile.business_name,
        href: `/pro/profiles/${props.profile.id}`,
    },
];

const dayLabels = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];

const tone = (value: string): StatusTone => {
    if (value === 'success' || value === 'warning' || value === 'danger') {
        return value;
    }

    return 'neutral';
};

// Formulaire principal du profil.
const form = useForm({
    business_name: props.profile.business_name,
    category_id: props.profile.category?.id ?? '',
    subcategory_id: props.profile.subcategory?.id ?? '',
    description: props.profile.description ?? '',
    phone: localPhoneNumber(props.profile.phone),
    whatsapp: localPhoneNumber(props.profile.whatsapp),
    email: props.profile.email ?? '',
    address: props.profile.address ?? '',
    city: props.profile.city,
    commune: props.profile.commune ?? '',
    quartier: props.profile.quartier ?? '',
    latitude: props.profile.latitude ?? '',
    longitude: props.profile.longitude ?? '',
    years_experience: props.profile.years_experience ?? '',
    logo: null as File | null,
});

const subcategories = computed(() => props.categories.find((category) => category.id === Number(form.category_id))?.subcategories ?? []);

// Zones d'intervention.
const areasForm = useForm({
    areas: props.profile.areas.map((area) => ({
        city: area.city,
        commune: area.commune ?? '',
        quartier: area.quartier ?? '',
    })),
});

// Horaires et disponibilités.
const scheduleForm = useForm({
    hours: dayLabels.map((_, index) => {
        const existing = props.profile.hours.find((hour) => hour.day_of_week === index);

        return {
            day_of_week: index,
            opens_at: existing?.opens_at ?? '',
            closes_at: existing?.closes_at ?? '',
            is_closed: existing?.is_closed ?? true,
        };
    }),
    availabilities: props.profile.availabilities.map((availability) => ({
        date: availability.date,
        starts_at: availability.starts_at,
        ends_at: availability.ends_at,
        label: availability.label ?? '',
    })),
});

// Médias.
const mediaForm = useForm({
    media: null as File | null,
    type: 'realisation',
    caption: '',
});

// Services.
const serviceForm = useForm({
    name: '',
    description: '',
    price_min: '',
    price_max: '',
    price_type: 'quote',
    duration_minutes: '',
});

// Formulaire de modification d'un service existant.
const editForm = useForm({
    name: '',
    description: '',
    price_min: '',
    price_max: '',
    price_type: 'quote',
    duration_minutes: '',
    is_active: true,
});

const editingId = ref<number | null>(null);

const submittingProfile = ref(false);

// Soumission du profil à la vérification administrative.
const reviewForm = useForm({});

const submitForReview = () => {
    reviewForm.post(route('pro.profiles.submit', props.profile.id), { preserveScroll: true });
};

// Les champs de type fichier sont gérés manuellement : le v-model renvoie
// une liste de fichiers, non un fichier unique.
const onLogoChange = (event: Event) => {
    const input = event.target as HTMLInputElement;

    form.logo = input.files?.[0] ?? null;
};

const onMediaChange = (event: Event) => {
    const input = event.target as HTMLInputElement;

    mediaForm.media = input.files?.[0] ?? null;
};

const saveProfile = () => {
    submittingProfile.value = true;

    form.patch(route('pro.profiles.update', props.profile.id), {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => {
            submittingProfile.value = false;
        },
    });
};

const saveAreas = () => {
    areasForm.put(route('pro.profiles.areas.update', props.profile.id), { preserveScroll: true });
};

const saveSchedule = () => {
    scheduleForm.put(route('pro.profiles.schedule.update', props.profile.id), { preserveScroll: true });
};

const uploadMedia = () => {
    mediaForm.post(route('pro.profiles.media.store', props.profile.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => mediaForm.reset('media', 'caption'),
    });
};

const removeMedia = (media: Media) => {
    mediaForm.delete(route('pro.profiles.media.destroy', [props.profile.id, media.id]), { preserveScroll: true });
};

const addService = () => {
    serviceForm.post(route('pro.profiles.services.store', props.profile.id), {
        preserveScroll: true,
        onSuccess: () => serviceForm.reset(),
    });
};

// Remplit le formulaire de modification avec les valeurs du service.
const fillEditForm = (service: Service) => {
    editForm.name = service.name;
    editForm.description = service.description ?? '';
    editForm.price_min = service.price_min ?? '';
    editForm.price_max = service.price_max ?? '';
    editForm.price_type = service.price_type;
    editForm.duration_minutes = service.duration_minutes === null ? '' : String(service.duration_minutes);
    editForm.is_active = service.is_active;
};

const startEdit = (service: Service) => {
    fillEditForm(service);
    editingId.value = service.id;
};

const saveEdit = () => {
    if (editingId.value === null) {
        return;
    }

    editForm.patch(route('pro.profiles.services.update', [props.profile.id, editingId.value]), {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
        },
    });
};

const toggleService = (service: Service) => {
    fillEditForm(service);
    editForm.is_active = !service.is_active;

    editForm.patch(route('pro.profiles.services.update', [props.profile.id, service.id]), {
        preserveScroll: true,
    });
};

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

const removeService = (service: Service) => {
    serviceForm.delete(route('pro.profiles.services.destroy', [props.profile.id, service.id]), { preserveScroll: true });
};

const addArea = () => {
    areasForm.areas.push({ city: '', commune: '', quartier: '' });
};

const removeArea = (index: number) => {
    areasForm.areas.splice(index, 1);
};

const addAvailability = () => {
    scheduleForm.availabilities.push({ date: '', starts_at: '09:00', ends_at: '18:00', label: '' });
};

const removeAvailability = (index: number) => {
    scheduleForm.availabilities.splice(index, 1);
};
</script>

<template>
    <Head :title="profile.business_name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader :eyebrow="profile.category?.name ?? 'Profil professionnel'" :title="profile.business_name">
                <template #meta>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <StatusBadge :tone="tone(profile.status_tone)" :label="profile.status_label" />
                        <span v-if="profile.business" class="text-xs text-muted-foreground">{{ profile.business }}</span>
                        <span class="text-xs text-muted-foreground"
                            >{{ profile.city }}<span v-if="profile.commune"> · {{ profile.commune }}</span></span
                        >
                    </div>
                </template>

                <template #actions>
                    <Button v-if="canSubmit" type="button" variant="brand" :disabled="reviewForm.processing" @click="submitForReview">
                        <LoaderCircle v-if="reviewForm.processing" class="size-4 animate-spin" aria-hidden="true" />
                        <Send v-else class="size-4" aria-hidden="true" />
                        Soumettre pour vérification
                    </Button>
                </template>
            </PageHeader>

            <div v-if="profile.status === 'refused' && profile.refusal_reason" class="rounded-xl border border-destructive/25 bg-destructive/5 p-4">
                <p class="flex items-center gap-2 text-sm font-medium text-destructive">
                    <AlertTriangle class="size-4" aria-hidden="true" />
                    Profil refusé
                </p>
                <p class="mt-1 text-sm text-muted-foreground">{{ profile.refusal_reason }}</p>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Informations du profil</CardTitle>
                        <CardDescription>Identité, coordonnées et localisation.</CardDescription>
                    </CardHeader>

                    <CardContent class="space-y-4">
                        <FormField label="Nom commercial" for="business_name" :error="form.errors.business_name" required>
                            <Input
                                id="business_name"
                                v-model="form.business_name"
                                required
                                maxlength="120"
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
                                <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
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

                        <FormField label="Description" for="description" :error="form.errors.description">
                            <Textarea id="description" v-model="form.description" rows="4" maxlength="2000" />
                        </FormField>

                        <FormField label="Téléphone" for="phone" :error="form.errors.phone">
                            <PhoneInput id="phone" v-model="form.phone" :invalid="!!form.errors.phone" />
                        </FormField>

                        <FormField label="WhatsApp" for="whatsapp" :error="form.errors.whatsapp">
                            <PhoneInput id="whatsapp" v-model="form.whatsapp" :invalid="!!form.errors.whatsapp" />
                        </FormField>

                        <FormField label="Adresse e-mail" for="email" :error="form.errors.email">
                            <Input id="email" v-model="form.email" type="email" maxlength="255" :aria-invalid="!!form.errors.email" />
                        </FormField>

                        <FormField label="Adresse" for="address" :error="form.errors.address">
                            <Input id="address" v-model="form.address" maxlength="255" />
                        </FormField>

                        <div class="grid gap-4 sm:grid-cols-3">
                            <FormField label="Ville" for="city" :error="form.errors.city" required>
                                <Input id="city" v-model="form.city" required maxlength="100" />
                            </FormField>
                            <FormField label="Commune" for="commune" :error="form.errors.commune">
                                <Input id="commune" v-model="form.commune" maxlength="100" />
                            </FormField>
                            <FormField label="Quartier" for="quartier" :error="form.errors.quartier">
                                <Input id="quartier" v-model="form.quartier" maxlength="100" />
                            </FormField>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <FormField label="Latitude" for="latitude" :error="form.errors.latitude">
                                <Input id="latitude" v-model="form.latitude" type="number" step="any" />
                            </FormField>
                            <FormField label="Longitude" for="longitude" :error="form.errors.longitude">
                                <Input id="longitude" v-model="form.longitude" type="number" step="any" />
                            </FormField>
                        </div>

                        <FormField label="Logo ou photo" for="logo" :error="form.errors.logo" hint="JPEG, PNG ou WebP, 2 Mo maximum.">
                            <Input id="logo" type="file" accept="image/*" @change="onLogoChange" :aria-invalid="!!form.errors.logo" />
                        </FormField>

                        <Button type="button" variant="brand" :disabled="form.processing" @click="saveProfile">
                            <LoaderCircle v-if="form.processing" class="size-4 animate-spin" aria-hidden="true" />
                            Enregistrer les modifications
                        </Button>
                    </CardContent>
                </Card>

                <div class="space-y-4">
                    <Card v-if="canManageAreas">
                        <CardHeader>
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <CardTitle class="text-base">Zones d’intervention</CardTitle>
                                    <CardDescription>Villes et communes couvertes par vos prestations.</CardDescription>
                                </div>
                                <Button type="button" variant="ghost" size="sm" @click="addArea">
                                    <Plus class="size-3.5" aria-hidden="true" />
                                    Ajouter
                                </Button>
                            </div>
                        </CardHeader>

                        <CardContent class="space-y-3">
                            <div v-for="(area, index) in areasForm.areas" :key="index" class="flex flex-wrap items-end gap-2">
                                <div class="min-w-[140px] flex-1">
                                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.1em] text-muted-foreground">
                                        Ville
                                    </label>
                                    <Input v-model="area.city" placeholder="Bamako" />
                                </div>
                                <div class="min-w-[140px] flex-1">
                                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-[0.1em] text-muted-foreground">
                                        Commune
                                    </label>
                                    <Input v-model="area.commune" placeholder="Commune IV" />
                                </div>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    class="text-muted-foreground"
                                    aria-label="Retirer la zone"
                                    @click="removeArea(index)"
                                >
                                    <X class="size-4" aria-hidden="true" />
                                </Button>
                            </div>

                            <p v-if="areasForm.errors.areas" class="text-xs text-destructive">{{ areasForm.errors.areas }}</p>

                            <Button type="button" variant="outline" :disabled="areasForm.processing" @click="saveAreas">
                                <MapPin class="size-4" aria-hidden="true" />
                                Enregistrer les zones
                            </Button>
                        </CardContent>
                    </Card>

                    <Card v-if="canManageSchedule">
                        <CardHeader>
                            <CardTitle class="text-base">Horaires et disponibilités</CardTitle>
                            <CardDescription>Indiquez vos jours d’ouverture et vos créneaux disponibles.</CardDescription>
                        </CardHeader>

                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <div
                                    v-for="hour in scheduleForm.hours"
                                    :key="hour.day_of_week"
                                    class="flex flex-wrap items-center gap-2 rounded-lg border border-border/80 bg-background px-3 py-2"
                                >
                                    <span class="w-24 shrink-0 text-sm font-medium text-foreground">{{ dayLabels[hour.day_of_week] }}</span>

                                    <label class="flex items-center gap-2 text-xs text-muted-foreground">
                                        <input v-model="hour.is_closed" type="checkbox" class="size-4 rounded border-input" />
                                        Fermé
                                    </label>

                                    <template v-if="!hour.is_closed">
                                        <Input
                                            v-model="hour.opens_at"
                                            type="time"
                                            class="w-32"
                                            :aria-label="`Ouverture ${dayLabels[hour.day_of_week]}`"
                                        />
                                        <span class="text-xs text-muted-foreground">à</span>
                                        <Input
                                            v-model="hour.closes_at"
                                            type="time"
                                            class="w-32"
                                            :aria-label="`Fermeture ${dayLabels[hour.day_of_week]}`"
                                        />
                                    </template>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-muted-foreground">Créneaux disponibles</p>
                                    <Button type="button" variant="ghost" size="sm" @click="addAvailability">
                                        <Plus class="size-3.5" aria-hidden="true" />
                                        Ajouter
                                    </Button>
                                </div>

                                <p v-if="scheduleForm.availabilities.length === 0" class="text-sm text-muted-foreground">
                                    Aucun créneau ponctuel défini.
                                </p>

                                <div v-for="(availability, index) in scheduleForm.availabilities" :key="index" class="flex flex-wrap items-end gap-2">
                                    <Input v-model="availability.date" type="date" class="w-40" />
                                    <Input v-model="availability.starts_at" type="time" class="w-28" />
                                    <Input v-model="availability.ends_at" type="time" class="w-28" />
                                    <Input v-model="availability.label" placeholder="Matin" class="w-32" />
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        class="text-muted-foreground"
                                        aria-label="Retirer le créneau"
                                        @click="removeAvailability(index)"
                                    >
                                        <X class="size-4" aria-hidden="true" />
                                    </Button>
                                </div>
                            </div>

                            <Button type="button" variant="outline" :disabled="scheduleForm.processing" @click="saveSchedule">
                                <CalendarClock class="size-4" aria-hidden="true" />
                                Enregistrer les horaires
                            </Button>
                        </CardContent>
                    </Card>

                    <Card v-if="canManageMedia">
                        <CardHeader>
                            <CardTitle class="text-base">Photos de réalisations</CardTitle>
                            <CardDescription>Illustrez votre savoir-faire avec des photos de votre travail.</CardDescription>
                        </CardHeader>

                        <CardContent class="space-y-4">
                            <div v-if="profile.media.length" class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                                <figure
                                    v-for="media in profile.media"
                                    :key="media.id"
                                    class="group relative overflow-hidden rounded-xl border border-border/80 bg-muted/40"
                                >
                                    <img :src="media.url" :alt="media.caption ?? media.type_label" class="h-28 w-full object-cover" />
                                    <figcaption class="flex items-center justify-between gap-2 px-2 py-1.5 text-[11px] text-muted-foreground">
                                        <span class="truncate">{{ media.type_label }}</span>
                                        <button
                                            type="button"
                                            class="text-muted-foreground transition-colors hover:text-destructive"
                                            :aria-label="`Supprimer le média ${media.id}`"
                                            @click="removeMedia(media)"
                                        >
                                            <Trash2 class="size-3.5" aria-hidden="true" />
                                        </button>
                                    </figcaption>
                                </figure>
                            </div>

                            <form class="space-y-3" @submit.prevent="uploadMedia">
                                <Input type="file" accept="image/*" @change="onMediaChange" />
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <select
                                        v-model="mediaForm.type"
                                        class="h-11 w-full rounded-lg border border-input bg-background px-3.5 text-sm text-foreground"
                                        aria-label="Type de média"
                                    >
                                        <option value="realisation">Réalisation</option>
                                        <option value="photo">Photo</option>
                                        <option value="cover">Couverture</option>
                                    </select>
                                    <Input v-model="mediaForm.caption" placeholder="Légende" maxlength="200" />
                                </div>
                                <p v-if="mediaForm.errors.media" class="text-xs text-destructive">{{ mediaForm.errors.media }}</p>

                                <Button type="submit" variant="outline" :disabled="mediaForm.processing">
                                    <LoaderCircle v-if="mediaForm.processing" class="size-4 animate-spin" aria-hidden="true" />
                                    <Image v-else class="size-4" aria-hidden="true" />
                                    Ajouter une photo
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">Services proposés</CardTitle>
                            <CardDescription>Décrivez les prestations que vous proposez.</CardDescription>
                        </CardHeader>

                        <CardContent class="space-y-4">
                            <ul v-if="profile.services.length" class="space-y-2">
                                <li
                                    v-for="service in profile.services"
                                    :key="service.id"
                                    class="rounded-lg border border-border/80 bg-background px-3.5 py-3"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-medium text-foreground">{{ service.name }}</p>
                                            <p v-if="service.description" class="mt-0.5 text-xs text-muted-foreground">
                                                {{ service.description }}
                                            </p>
                                            <p class="mt-1.5 text-xs text-muted-foreground">
                                                {{ service.formatted_price }}
                                                <span v-if="service.duration_minutes">· {{ formatDuration(service.duration_minutes) }}</span>
                                            </p>
                                        </div>

                                        <div class="flex shrink-0 items-center gap-2">
                                            <StatusBadge
                                                :tone="service.is_active ? 'success' : 'neutral'"
                                                :label="service.is_active ? 'Actif' : 'Inactif'"
                                            />
                                            <button
                                                type="button"
                                                class="text-muted-foreground transition-colors hover:text-foreground"
                                                :aria-label="`${service.is_active ? 'Désactiver' : 'Activer'} le service ${service.name}`"
                                                @click="toggleService(service)"
                                            >
                                                <Power class="size-4" aria-hidden="true" />
                                            </button>
                                            <button
                                                type="button"
                                                class="text-muted-foreground transition-colors hover:text-foreground"
                                                :aria-label="`Modifier le service ${service.name}`"
                                                @click="startEdit(service)"
                                            >
                                                <Pencil class="size-4" aria-hidden="true" />
                                            </button>
                                            <button
                                                type="button"
                                                class="text-muted-foreground transition-colors hover:text-destructive"
                                                :aria-label="`Supprimer le service ${service.name}`"
                                                @click="removeService(service)"
                                            >
                                                <Trash2 class="size-4" aria-hidden="true" />
                                            </button>
                                        </div>
                                    </div>

                                    <form
                                        v-if="editingId === service.id"
                                        class="mt-3 space-y-3 border-t border-border/70 pt-3"
                                        @submit.prevent="saveEdit"
                                    >
                                        <Input v-model="editForm.name" placeholder="Nom du service" maxlength="120" required />
                                        <Textarea v-model="editForm.description" rows="2" placeholder="Description courte" maxlength="1000" />

                                        <div class="grid gap-3 sm:grid-cols-2">
                                            <Input v-model="editForm.price_min" type="number" min="0" placeholder="Prix minimum (FCFA)" />
                                            <Input v-model="editForm.price_max" type="number" min="0" placeholder="Prix maximum (FCFA)" />
                                        </div>

                                        <div class="grid gap-3 sm:grid-cols-2">
                                            <select
                                                v-model="editForm.price_type"
                                                class="h-11 w-full rounded-lg border border-input bg-background px-3.5 text-sm text-foreground"
                                                aria-label="Type de tarification"
                                            >
                                                <option value="quote">Sur devis</option>
                                                <option value="fixed">Prix fixe</option>
                                                <option value="from">À partir de</option>
                                                <option value="range">Fourchette</option>
                                                <option value="hourly">Tarif horaire</option>
                                            </select>
                                            <Input
                                                v-model="editForm.duration_minutes"
                                                type="number"
                                                min="15"
                                                step="15"
                                                placeholder="Durée estimée (minutes)"
                                            />
                                        </div>

                                        <label class="flex items-center gap-2 text-sm text-foreground">
                                            <input
                                                v-model="editForm.is_active"
                                                type="checkbox"
                                                class="size-4 rounded border-input text-brand-600 focus:ring-brand-500"
                                            />
                                            Service visible dans le catalogue
                                        </label>

                                        <p v-if="editForm.errors.name" class="text-xs text-destructive">{{ editForm.errors.name }}</p>

                                        <div class="flex items-center gap-2">
                                            <Button type="submit" variant="brand" size="sm" :disabled="editForm.processing">
                                                <LoaderCircle v-if="editForm.processing" class="size-4 animate-spin" aria-hidden="true" />
                                                Enregistrer
                                            </Button>
                                            <Button type="button" variant="ghost" size="sm" @click="editingId = null">Annuler</Button>
                                        </div>
                                    </form>
                                </li>
                            </ul>
                            <p v-else class="text-sm text-muted-foreground">Aucun service enregistré pour le moment.</p>

                            <form class="space-y-3 border-t border-border/70 pt-4" @submit.prevent="addService">
                                <Input v-model="serviceForm.name" placeholder="Nom du service" maxlength="120" required />
                                <Textarea v-model="serviceForm.description" rows="2" placeholder="Description courte" maxlength="1000" />
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <Input v-model="serviceForm.price_min" type="number" min="0" placeholder="Prix minimum (FCFA)" />
                                    <Input v-model="serviceForm.price_max" type="number" min="0" placeholder="Prix maximum (FCFA)" />
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <select
                                        v-model="serviceForm.price_type"
                                        class="h-11 w-full rounded-lg border border-input bg-background px-3.5 text-sm text-foreground"
                                        aria-label="Type de tarification"
                                    >
                                        <option value="quote">Sur devis</option>
                                        <option value="fixed">Prix fixe</option>
                                        <option value="from">À partir de</option>
                                        <option value="range">Fourchette</option>
                                        <option value="hourly">Tarif horaire</option>
                                    </select>
                                    <Input
                                        v-model="serviceForm.duration_minutes"
                                        type="number"
                                        min="15"
                                        step="15"
                                        placeholder="Durée estimée (minutes)"
                                    />
                                </div>
                                <p v-if="serviceForm.errors.name" class="text-xs text-destructive">{{ serviceForm.errors.name }}</p>

                                <Button type="submit" variant="outline" :disabled="serviceForm.processing">
                                    <Plus class="size-4" aria-hidden="true" />
                                    Ajouter un service
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
