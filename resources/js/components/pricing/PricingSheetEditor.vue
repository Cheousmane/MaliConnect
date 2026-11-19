<script setup lang="ts">
import FormField from '@/components/FormField.vue';
import FormSelect from '@/components/FormSelect.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { useForm } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowLeft,
    ArrowUp,
    BadgeCheck,
    Copy,
    GripVertical,
    Info,
    LoaderCircle,
    Plus,
    ReceiptText,
    Save,
    Sparkles,
    Trash2,
} from 'lucide-vue-next';
import { computed, watch } from 'vue';

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

type EditorItem = {
    _key: string;
    id: number | null;
    service_id: string;
    category_id: string;
    name: string;
    description: string;
    pricing_type: string;
    price: string;
    price_min: string;
    price_max: string;
    billing_unit: string;
    conditions: string;
    estimated_duration_minutes: string;
    is_available: boolean;
    requires_diagnostic: boolean;
    formatted_price?: string;
};

type EditorSheet = {
    id: number;
    provider_profile_id: number;
    name: string;
    description: string | null;
    currency: string;
    general_conditions: string | null;
    valid_until: string | null;
    category?: { id: number; name: string } | null;
    items: Array<{
        id: number;
        service_id: number | null;
        category_id: number | null;
        name: string;
        description: string | null;
        pricing_type: string;
        price: string | number | null;
        price_min: string | number | null;
        price_max: string | number | null;
        billing_unit: string | null;
        conditions: string | null;
        estimated_duration_minutes: number | null;
        is_available: boolean;
        requires_diagnostic: boolean;
        formatted_price?: string;
    }>;
};

const props = defineProps<{
    mode: 'create' | 'edit';
    profiles?: ProfileOption[];
    selectedProfile?: { id: number; business_name: string; slug: string; category_id: number | null } | null;
    sheet?: EditorSheet | null;
    categories: CategoryOption[];
    pricingTypes: PricingTypeOption[];
    services?: ServiceOption[];
}>();

const uid = (): string => {
    if (typeof crypto !== 'undefined' && 'randomUUID' in crypto) {
        return crypto.randomUUID();
    }

    return `item-${Date.now()}-${Math.random().toString(36).slice(2)}`;
};

const mapItem = (item: EditorSheet['items'][number]): EditorItem => ({
    _key: uid(),
    id: item.id,
    service_id: item.service_id ? String(item.service_id) : '',
    category_id: item.category_id ? String(item.category_id) : '',
    name: item.name,
    description: item.description ?? '',
    pricing_type: item.pricing_type,
    price: item.price === null ? '' : String(item.price),
    price_min: item.price_min === null ? '' : String(item.price_min),
    price_max: item.price_max === null ? '' : String(item.price_max),
    billing_unit: item.billing_unit ?? '',
    conditions: item.conditions ?? '',
    estimated_duration_minutes: item.estimated_duration_minutes === null ? '' : String(item.estimated_duration_minutes),
    is_available: item.is_available,
    requires_diagnostic: item.requires_diagnostic,
    formatted_price: item.formatted_price,
});

const emptyItem = (quote = false): EditorItem => ({
    _key: uid(),
    id: null,
    service_id: '',
    category_id: '',
    name: '',
    description: '',
    pricing_type: quote ? 'quote' : 'fixed',
    price: '',
    price_min: '',
    price_max: '',
    billing_unit: '',
    conditions: '',
    estimated_duration_minutes: '',
    is_available: true,
    requires_diagnostic: false,
});

const form = useForm({
    provider_profile_id: props.sheet ? String(props.sheet.provider_profile_id) : String(props.selectedProfile?.id ?? ''),
    name: props.sheet?.name ?? '',
    description: props.sheet?.description ?? '',
    category_id: props.sheet?.category?.id ? String(props.sheet.category.id) : String(props.selectedProfile?.category_id ?? ''),
    currency: props.sheet?.currency ?? 'XOF',
    general_conditions: props.sheet?.general_conditions ?? '',
    valid_until: props.sheet?.valid_until ?? '',
    items: (props.sheet?.items ?? []).map(mapItem),
});

const errors = computed(() => form.errors as Record<string, string>);

const itemError = (index: number, field: string): string | undefined => errors.value[`items.${index}.${field}`];

const profileOptions = computed(() =>
    (props.profiles ?? []).map((profile) => ({
        value: String(profile.id),
        label: profile.business_name,
    })),
);

// Une option vide explicite permet d’effacer une sélection : le composant
// FormSelect désactive son option de placeholder.
const categoryOptions = computed(() => [
    { value: '', label: 'Non précisée' },
    ...props.categories.map((category) => ({ value: String(category.id), label: category.name })),
]);

const currencyOptions = [
    { value: 'XOF', label: 'FCFA (XOF)' },
    { value: 'EUR', label: 'Euro (EUR)' },
    { value: 'USD', label: 'Dollar (USD)' },
];

const selectedProfile = computed(() => {
    if (props.mode === 'edit') {
        return null;
    }

    return (props.profiles ?? []).find((profile) => String(profile.id) === String(form.provider_profile_id)) ?? null;
});

const availableServices = computed<ServiceOption[]>(() => {
    if (props.mode === 'edit') {
        return props.services ?? [];
    }

    return selectedProfile.value?.services ?? props.services ?? [];
});

const serviceOptions = computed(() => [
    { value: '', label: 'Aucun service associé' },
    ...availableServices.value.map((service) => ({ value: String(service.id), label: service.name })),
]);

const isQuoteLike = (item: EditorItem): boolean => item.pricing_type === 'quote' || item.requires_diagnostic;

// Le changement de profil met à jour la catégorie et détache les services
// qui n'appartiennent pas au nouveau profil.
watch(
    () => form.provider_profile_id,
    (value) => {
        if (props.mode !== 'create') {
            return;
        }

        const profile = (props.profiles ?? []).find((item) => String(item.id) === String(value));
        form.category_id = profile?.category_id ? String(profile.category_id) : '';
        form.items.forEach((item) => {
            item.service_id = '';
        });
    },
);

const addItem = (quote = false) => {
    form.items.push(emptyItem(quote));
};

const duplicateItem = (index: number) => {
    const source = form.items[index];

    form.items.splice(index + 1, 0, {
        ...source,
        _key: uid(),
        id: null,
        name: `${source.name} (copie)`,
    });
};

const removeItem = (index: number) => {
    if (window.confirm('Supprimer cette prestation de la fiche ?')) {
        form.items.splice(index, 1);
    }
};

const moveItem = (index: number, direction: -1 | 1) => {
    const target = index + direction;

    if (target < 0 || target >= form.items.length) {
        return;
    }

    const [item] = form.items.splice(index, 1);
    form.items.splice(target, 0, item);
};

const submit = () => {
    const payload = form.transform((data) => ({
        ...data,
        items: data.items.map((item, index) => ({
            id: item.id || null,
            service_id: item.service_id || null,
            category_id: item.category_id || null,
            name: item.name,
            description: item.description || null,
            pricing_type: item.pricing_type,
            price: item.price === '' ? null : item.price,
            price_min: item.price_min === '' ? null : item.price_min,
            price_max: item.price_max === '' ? null : item.price_max,
            billing_unit: item.billing_unit || null,
            conditions: item.conditions || null,
            estimated_duration_minutes: item.estimated_duration_minutes === '' ? null : item.estimated_duration_minutes,
            is_available: item.is_available,
            requires_diagnostic: item.requires_diagnostic,
            sort_order: index,
        })),
    }));

    if (props.mode === 'edit' && props.sheet) {
        payload.patch(route('pro.pricing-sheets.update', props.sheet.id), { preserveScroll: true });
    } else {
        payload.post(route('pro.pricing-sheets.store'), { preserveScroll: true });
    }
};
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <Card>
            <CardHeader>
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <CardTitle class="text-base">Informations de la fiche</CardTitle>
                        <CardDescription class="mt-1">
                            Donnez un nom clair à votre fiche et précisez sa catégorie et sa durée de validité.
                        </CardDescription>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-muted px-2.5 py-1 text-[11px] font-medium text-muted-foreground">
                        <ReceiptText class="size-3.5" aria-hidden="true" />
                        {{ form.items.length }} prestation{{ form.items.length > 1 ? 's' : '' }}
                    </span>
                </div>
            </CardHeader>

            <CardContent class="grid gap-4 sm:grid-cols-2">
                <FormField
                    v-if="mode === 'create'"
                    label="Profil professionnel"
                    for="provider_profile_id"
                    :error="errors.provider_profile_id"
                    required
                    class="sm:col-span-2"
                >
                    <FormSelect
                        id="provider_profile_id"
                        v-model="form.provider_profile_id"
                        :options="profileOptions"
                        placeholder="Choisir un profil"
                        :disabled="profileOptions.length <= 1"
                    />
                    <p v-if="selectedProfile" class="text-xs text-muted-foreground">
                        {{ selectedProfile.category ?? 'Catégorie non précisée' }}
                        <span v-if="!selectedProfile.is_publicly_visible"> · Profil non encore visible publiquement</span>
                    </p>
                </FormField>

                <FormField label="Nom de la fiche" for="name" :error="errors.name" required class="sm:col-span-2">
                    <Input id="name" v-model="form.name" maxlength="150" placeholder="Tarifs plomberie et dépannage" required />
                </FormField>

                <FormField label="Catégorie de prestations" for="category_id" :error="errors.category_id">
                    <FormSelect id="category_id" v-model="form.category_id" :options="categoryOptions" />
                </FormField>

                <FormField label="Devise" for="currency" :error="errors.currency" hint="Les montants sont affichés sans décimale.">
                    <FormSelect id="currency" v-model="form.currency" :options="currencyOptions" />
                </FormField>

                <FormField
                    label="Date de validité"
                    for="valid_until"
                    :error="errors.valid_until"
                    hint="Facultative. La fiche reste valable jusqu’à la fin de cette journée."
                >
                    <Input id="valid_until" v-model="form.valid_until" type="date" />
                </FormField>

                <FormField label="Description" for="description" :error="errors.description" class="sm:col-span-2">
                    <Textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        maxlength="2000"
                        placeholder="Présentez l’esprit de vos tarifs, vos délais ou vos zones d’intervention."
                    />
                </FormField>

                <FormField
                    label="Conditions générales"
                    for="general_conditions"
                    :error="errors.general_conditions"
                    hint="Reprises dans le PDF et affichées sous les prestations."
                    class="sm:col-span-2"
                >
                    <Textarea
                        id="general_conditions"
                        v-model="form.general_conditions"
                        rows="4"
                        maxlength="5000"
                        placeholder="Acompte, déplacement, garantie, TVA éventuelle…"
                    />
                </FormField>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <CardTitle class="text-base">Prestations et prix</CardTitle>
                        <CardDescription class="mt-1">
                            Ajoutez vos prestations, associez-les à un service existant et choisissez le mode de tarification.
                        </CardDescription>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Button type="button" variant="outline" size="sm" @click="addItem(true)">
                            <Sparkles class="size-3.5" aria-hidden="true" />
                            Sur devis
                        </Button>
                        <Button type="button" variant="brand" size="sm" @click="addItem()">
                            <Plus class="size-3.5" aria-hidden="true" />
                            Ajouter
                        </Button>
                    </div>
                </div>
            </CardHeader>

            <CardContent>
                <div v-if="form.items.length === 0" class="rounded-xl border border-dashed border-border bg-muted/30 p-8 text-center">
                    <span
                        class="mx-auto flex size-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-950/60 dark:text-brand-300"
                    >
                        <ReceiptText class="size-5" aria-hidden="true" />
                    </span>
                    <p class="mt-3 text-sm font-medium text-foreground">Aucune prestation pour le moment</p>
                    <p class="mx-auto mt-1 max-w-md text-sm text-muted-foreground">
                        Commencez par ajouter une prestation à prix fixe ou une prestation sur devis.
                    </p>
                    <Button type="button" variant="brand" class="mt-4" @click="addItem()">
                        <Plus class="size-4" aria-hidden="true" />
                        Ajouter ma première prestation
                    </Button>
                </div>

                <div v-else class="space-y-4">
                    <article
                        v-for="(item, index) in form.items"
                        :key="item._key"
                        class="rounded-xl border border-border/80 bg-background p-4 shadow-[0_1px_2px_0_rgb(16_24_40_/_0.03)] sm:p-5"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="flex min-w-0 flex-1 items-start gap-2">
                                <span class="mt-2 hidden text-muted-foreground/60 sm:block" aria-hidden="true">
                                    <GripVertical class="size-4" />
                                </span>
                                <span
                                    class="mt-1 flex size-6 shrink-0 items-center justify-center rounded-full bg-brand-50 text-[11px] font-semibold text-brand-700 dark:bg-brand-950/60 dark:text-brand-300"
                                >
                                    {{ index + 1 }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <FormField
                                        :label="`Prestation ${index + 1}`"
                                        :for="`item-name-${index}`"
                                        :error="itemError(index, 'name')"
                                        required
                                    >
                                        <Input
                                            :id="`item-name-${index}`"
                                            v-model="item.name"
                                            maxlength="150"
                                            placeholder="Réparation d’une fuite"
                                            required
                                        />
                                    </FormField>
                                </div>
                            </div>

                            <div class="flex shrink-0 items-center gap-1">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon-sm"
                                    :disabled="index === 0"
                                    aria-label="Monter la prestation"
                                    @click="moveItem(index, -1)"
                                >
                                    <ArrowUp class="size-4" aria-hidden="true" />
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon-sm"
                                    :disabled="index === form.items.length - 1"
                                    aria-label="Descendre la prestation"
                                    @click="moveItem(index, 1)"
                                >
                                    <ArrowDown class="size-4" aria-hidden="true" />
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon-sm"
                                    aria-label="Dupliquer la prestation"
                                    @click="duplicateItem(index)"
                                >
                                    <Copy class="size-4" aria-hidden="true" />
                                </Button>
                                <Button type="button" variant="ghost" size="icon-sm" aria-label="Supprimer la prestation" @click="removeItem(index)">
                                    <Trash2 class="size-4 text-destructive" aria-hidden="true" />
                                </Button>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <FormField label="Service existant" :for="`item-service-${index}`" :error="itemError(index, 'service_id')">
                                <FormSelect :id="`item-service-${index}`" v-model="item.service_id" :options="serviceOptions" />
                            </FormField>

                            <FormField label="Catégorie" :for="`item-category-${index}`" :error="itemError(index, 'category_id')">
                                <FormSelect :id="`item-category-${index}`" v-model="item.category_id" :options="categoryOptions" />
                            </FormField>

                            <FormField label="Mode de tarification" :for="`item-type-${index}`" :error="itemError(index, 'pricing_type')" required>
                                <FormSelect
                                    :id="`item-type-${index}`"
                                    v-model="item.pricing_type"
                                    :options="pricingTypes.map((type) => ({ value: type.value, label: type.label }))"
                                />
                            </FormField>

                            <template v-if="!isQuoteLike(item)">
                                <FormField
                                    label="Prix (FCFA)"
                                    :for="`item-price-${index}`"
                                    :error="itemError(index, 'price')"
                                    hint="Montant principal de la prestation."
                                >
                                    <Input
                                        :id="`item-price-${index}`"
                                        v-model="item.price"
                                        type="number"
                                        min="0"
                                        step="1"
                                        inputmode="numeric"
                                        placeholder="25000"
                                    />
                                </FormField>
                            </template>

                            <FormField
                                :label="isQuoteLike(item) ? 'Prix minimum indicatif (FCFA)' : 'Prix minimum (FCFA)'"
                                :for="`item-min-${index}`"
                                :error="itemError(index, 'price_min')"
                            >
                                <Input
                                    :id="`item-min-${index}`"
                                    v-model="item.price_min"
                                    type="number"
                                    min="0"
                                    step="1"
                                    inputmode="numeric"
                                    placeholder="15000"
                                />
                            </FormField>

                            <FormField
                                :label="isQuoteLike(item) ? 'Prix maximum indicatif (FCFA)' : 'Prix maximum (FCFA)'"
                                :for="`item-max-${index}`"
                                :error="itemError(index, 'price_max')"
                            >
                                <Input
                                    :id="`item-max-${index}`"
                                    v-model="item.price_max"
                                    type="number"
                                    min="0"
                                    step="1"
                                    inputmode="numeric"
                                    placeholder="40000"
                                />
                            </FormField>

                            <FormField label="Unité de facturation" :for="`item-unit-${index}`" :error="itemError(index, 'billing_unit')">
                                <Input :id="`item-unit-${index}`" v-model="item.billing_unit" maxlength="50" placeholder="par m², par déplacement…" />
                            </FormField>

                            <FormField
                                label="Durée estimée (minutes)"
                                :for="`item-duration-${index}`"
                                :error="itemError(index, 'estimated_duration_minutes')"
                            >
                                <Input
                                    :id="`item-duration-${index}`"
                                    v-model="item.estimated_duration_minutes"
                                    type="number"
                                    min="1"
                                    max="10080"
                                    step="1"
                                    inputmode="numeric"
                                    placeholder="60"
                                />
                            </FormField>

                            <FormField
                                label="Description"
                                :for="`item-description-${index}`"
                                :error="itemError(index, 'description')"
                                class="sm:col-span-2 lg:col-span-3"
                            >
                                <Textarea
                                    :id="`item-description-${index}`"
                                    v-model="item.description"
                                    rows="2"
                                    maxlength="2000"
                                    placeholder="Ce qui est inclus, les matériaux, les limites…"
                                />
                            </FormField>

                            <FormField
                                label="Conditions particulières"
                                :for="`item-conditions-${index}`"
                                :error="itemError(index, 'conditions')"
                                class="sm:col-span-2 lg:col-span-3"
                            >
                                <Textarea
                                    :id="`item-conditions-${index}`"
                                    v-model="item.conditions"
                                    rows="2"
                                    maxlength="2000"
                                    placeholder="Déplacement inclus dans Bamako, matériel non fourni…"
                                />
                            </FormField>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-border/60 pt-3">
                            <label class="inline-flex items-center gap-2 text-sm text-muted-foreground">
                                <input
                                    v-model="item.is_available"
                                    type="checkbox"
                                    class="size-4 rounded border-input text-brand-600 focus:ring-brand-500"
                                />
                                Prestation disponible
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-muted-foreground">
                                <input
                                    v-model="item.requires_diagnostic"
                                    type="checkbox"
                                    class="size-4 rounded border-input text-brand-600 focus:ring-brand-500"
                                />
                                Prix après diagnostic
                            </label>
                            <span
                                v-if="item.formatted_price"
                                class="ml-auto inline-flex items-center gap-1.5 text-xs font-medium text-brand-700 dark:text-brand-300"
                            >
                                <BadgeCheck class="size-3.5" aria-hidden="true" />
                                {{ item.formatted_price }}
                            </span>
                        </div>
                    </article>

                    <div class="flex flex-wrap gap-2">
                        <Button type="button" variant="outline" @click="addItem()">
                            <Plus class="size-4" aria-hidden="true" />
                            Ajouter une prestation
                        </Button>
                        <Button type="button" variant="soft" @click="addItem(true)">
                            <Sparkles class="size-4" aria-hidden="true" />
                            Ajouter une prestation sur devis
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <div
            class="flex flex-col-reverse gap-3 rounded-xl border border-border/80 bg-background/95 p-4 shadow-card sm:flex-row sm:items-center sm:justify-between"
        >
            <p class="inline-flex items-center gap-2 text-xs text-muted-foreground">
                <Info class="size-4 shrink-0" aria-hidden="true" />
                Les montants vides ne sont jamais remplacés par un prix fictif.
            </p>

            <div class="flex flex-wrap gap-2">
                <Button as-child type="button" variant="ghost">
                    <a :href="route('pro.pricing-sheets.index')">
                        <ArrowLeft class="size-4" aria-hidden="true" />
                        Annuler
                    </a>
                </Button>
                <Button type="submit" variant="brand" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="size-4 animate-spin" aria-hidden="true" />
                    <Save v-else class="size-4" aria-hidden="true" />
                    {{ mode === 'edit' ? 'Enregistrer les modifications' : 'Créer la fiche' }}
                </Button>
            </div>
        </div>
    </form>
</template>
