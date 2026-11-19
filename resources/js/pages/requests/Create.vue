<script setup lang="ts">
import FormField from '@/components/FormField.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, LoaderCircle, Paperclip, Send } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

type ServiceItem = {
    id: number;
    name: string;
    price_type: string;
    price_min: string | null;
    formatted_price: string;
};

type Profile = {
    id: number;
    business_name: string;
    slug: string;
    category: string | null;
    city: string;
    commune: string | null;
    services: ServiceItem[];
};

type Professional = {
    id: number;
    business_name: string;
    category: string | null;
    city: string;
    services: ServiceItem[];
};

type PricingItem = {
    id: number;
    name: string;
    service_id: number | null;
    pricing_type: string;
    pricing_type_label: string;
    formatted_price: string;
    price_min: string | null;
    price_max: string | null;
    requires_diagnostic: boolean;
    sheet_name: string | null;
};

type Props = {
    profile: Profile | null;
    professionals: Professional[];
    pricingItem?: PricingItem | null;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Mes demandes',
        href: '/demandes',
    },
    {
        title: 'Nouvelle demande',
        href: '/demandes/nouveau',
    },
];

const form = useForm({
    provider_profile_id: props.profile ? String(props.profile.id) : '',
    service_id: props.pricingItem?.service_id ? String(props.pricingItem.service_id) : '',
    pricing_item_id: props.pricingItem?.id ? String(props.pricingItem.id) : '',
    title: props.pricingItem ? `Devis : ${props.pricingItem.name}` : '',
    description: '',
    address: '',
    city: 'Bamako',
    commune: '',
    quartier: '',
    budget_min: props.pricingItem?.price_min ?? '',
    budget_max: props.pricingItem?.price_max ?? '',
    desired_date: '',
    preferred_time: '',
    attachments: [] as File[],
});

const fileInput = ref<HTMLInputElement | null>(null);

// Les services proposés dépendent du professionnel choisi.
const services = computed(() => {
    const selected = props.professionals.find((item) => String(item.id) === String(form.provider_profile_id));

    return selected?.services ?? [];
});

// Un changement de professionnel réinitialise la prestation sélectionnée.
watch(
    () => form.provider_profile_id,
    () => {
        form.service_id = '';
        form.pricing_item_id = '';
    },
);

const addFiles = (event: Event) => {
    const input = event.target as HTMLInputElement;

    form.attachments = Array.from(input.files ?? []);
};

const removeFile = (index: number) => {
    form.attachments.splice(index, 1);
};

const submit = () => {
    form.post(route('requests.store'), {
        forceFormData: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Nouvelle demande de devis" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <Link
                :href="route('requests.index')"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft class="size-4" aria-hidden="true" />
                Retour à mes demandes
            </Link>

            <PageHeader
                eyebrow="Nouvelle demande"
                title="Demander un devis"
                description="Choisissez un professionnel vérifié, décrivez votre besoin et précisez votre localisation."
            />

            <div v-if="pricingItem" class="rounded-xl border border-brand-200 bg-brand-50/60 p-4 dark:border-brand-900/70 dark:bg-brand-950/30">
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-brand-700 dark:text-brand-300">
                    Prestation tarifée sélectionnée
                </p>
                <p class="mt-1 text-sm font-medium text-foreground">{{ pricingItem.name }} — {{ pricingItem.formatted_price }}</p>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ pricingItem.pricing_type_label }}
                    <span v-if="pricingItem.sheet_name"> · Fiche : {{ pricingItem.sheet_name }}</span>
                    <span v-if="pricingItem.requires_diagnostic"> · Un diagnostic peut être nécessaire.</span>
                </p>
            </div>

            <form class="grid gap-4 lg:grid-cols-2" @submit.prevent="submit">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Professionnel et prestation</CardTitle>
                        <CardDescription>Seuls les profils vérifiés et actifs peuvent être sollicités.</CardDescription>
                    </CardHeader>

                    <CardContent class="space-y-4">
                        <FormField label="Professionnel" for="provider_profile_id" :error="form.errors.provider_profile_id" required>
                            <select
                                id="provider_profile_id"
                                v-model="form.provider_profile_id"
                                required
                                class="h-11 w-full rounded-lg border border-input bg-background px-3.5 text-sm text-foreground"
                            >
                                <option value="">Choisir un professionnel</option>
                                <option v-for="item in professionals" :key="item.id" :value="item.id">
                                    {{ item.business_name }}{{ item.category ? ` — ${item.category}` : '' }}
                                </option>
                            </select>
                        </FormField>

                        <FormField
                            v-if="services.length"
                            label="Service souhaité"
                            for="service_id"
                            :error="form.errors.service_id"
                            hint="Facultatif : la prestation peut être précisée dans la description."
                        >
                            <select
                                id="service_id"
                                v-model="form.service_id"
                                class="h-11 w-full rounded-lg border border-input bg-background px-3.5 text-sm text-foreground"
                            >
                                <option value="">Non précisé</option>
                                <option v-for="service in services" :key="service.id" :value="service.id">
                                    {{ service.name }} — {{ service.formatted_price }}
                                </option>
                            </select>
                        </FormField>

                        <FormField label="Objet de la demande" for="title" :error="form.errors.title" required>
                            <Input
                                id="title"
                                v-model="form.title"
                                required
                                maxlength="120"
                                placeholder="Réparation d’une fuite sous évier"
                                :aria-invalid="!!form.errors.title"
                            />
                        </FormField>

                        <FormField label="Description du besoin" for="description" :error="form.errors.description" required>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                rows="6"
                                required
                                minlength="20"
                                maxlength="3000"
                                placeholder="Décrivez le travail attendu, le contexte et vos contraintes."
                                :aria-invalid="!!form.errors.description"
                            />
                        </FormField>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Localisation, budget et délai</CardTitle>
                        <CardDescription>Ces informations facilitent l’intervention du professionnel.</CardDescription>
                    </CardHeader>

                    <CardContent class="space-y-4">
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

                        <div class="grid gap-4 sm:grid-cols-2">
                            <FormField label="Budget minimum (FCFA)" for="budget_min" :error="form.errors.budget_min">
                                <Input id="budget_min" v-model="form.budget_min" type="number" min="0" placeholder="10000" />
                            </FormField>
                            <FormField label="Budget maximum (FCFA)" for="budget_max" :error="form.errors.budget_max">
                                <Input id="budget_max" v-model="form.budget_max" type="number" min="0" placeholder="50000" />
                            </FormField>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <FormField label="Date souhaitée" for="desired_date" :error="form.errors.desired_date">
                                <Input id="desired_date" v-model="form.desired_date" type="date" />
                            </FormField>
                            <FormField label="Moment préféré" for="preferred_time" :error="form.errors.preferred_time">
                                <select
                                    id="preferred_time"
                                    v-model="form.preferred_time"
                                    class="h-11 w-full rounded-lg border border-input bg-background px-3.5 text-sm text-foreground"
                                >
                                    <option value="">Indifférent</option>
                                    <option value="Matin">Matin</option>
                                    <option value="Après-midi">Après-midi</option>
                                    <option value="Soir">Soir</option>
                                </select>
                            </FormField>
                        </div>

                        <FormField
                            label="Photos ou documents"
                            for="attachments"
                            :error="form.errors.attachments"
                            hint="5 fichiers maximum (images ou PDF, 4 Mo par fichier)."
                        >
                            <div class="flex flex-wrap items-center gap-2">
                                <input
                                    id="attachments"
                                    ref="fileInput"
                                    type="file"
                                    multiple
                                    accept="image/*,application/pdf"
                                    class="text-xs text-muted-foreground"
                                    @change="addFiles"
                                />
                                <Paperclip class="size-4 text-muted-foreground" aria-hidden="true" />
                            </div>

                            <ul v-if="form.attachments.length" class="mt-2 space-y-1">
                                <li
                                    v-for="(file, index) in form.attachments"
                                    :key="file.name"
                                    class="flex items-center justify-between gap-2 rounded-lg border border-border/70 bg-background px-3 py-2 text-xs text-muted-foreground"
                                >
                                    <span class="truncate">{{ file.name }}</span>
                                    <button type="button" class="text-destructive" @click="removeFile(index)">Retirer</button>
                                </li>
                            </ul>
                        </FormField>

                        <Button type="submit" variant="brand" size="lg" :disabled="form.processing">
                            <LoaderCircle v-if="form.processing" class="size-4 animate-spin" aria-hidden="true" />
                            <Send v-else class="size-4" aria-hidden="true" />
                            Envoyer la demande
                        </Button>
                    </CardContent>
                </Card>
            </form>
        </div>
    </AppLayout>
</template>
