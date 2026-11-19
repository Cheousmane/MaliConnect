<script setup lang="ts">
import FormField from '@/components/FormField.vue';
import PageHeader from '@/components/PageHeader.vue';
import PhoneInput from '@/components/PhoneInput.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Building2, LoaderCircle } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Mon entreprise',
        href: '/pro/businesses',
    },
    {
        title: 'Créer une entreprise',
        href: '/pro/businesses/create',
    },
];

const form = useForm({
    name: '',
    legal_name: '',
    description: '',
    phone: '',
    email: '',
    address: '',
    city: 'Bamako',
    commune: '',
    logo: null as File | null,
});

// Le champ de fichier est géré manuellement : le v-model renvoie une liste
// de fichiers, non un fichier unique.
const onLogoChange = (event: Event) => {
    const input = event.target as HTMLInputElement;

    form.logo = input.files?.[0] ?? null;
};

const submit = () => {
    form.post(route('pro.businesses.store'), {
        forceFormData: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Créer une entreprise" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader
                eyebrow="Espace professionnel"
                title="Créer une entreprise"
                description="Rassemblez vos profils professionnels et invitez vos collaborateurs avec des rôles précis."
            >
                <template #actions>
                    <Button as-child variant="outline">
                        <Link :href="route('pro.businesses.index')">
                            <ArrowLeft class="size-4" aria-hidden="true" />
                            Annuler
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <form class="max-w-3xl" @submit.prevent="submit">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Informations de l’entreprise</CardTitle>
                        <CardDescription>Ces informations sont visibles par les clients sur vos profils.</CardDescription>
                    </CardHeader>

                    <CardContent class="space-y-4">
                        <FormField label="Nom de l’entreprise" for="name" :error="form.errors.name" required>
                            <Input
                                id="name"
                                v-model="form.name"
                                required
                                maxlength="120"
                                placeholder="Atelier Koné & Fils"
                                :aria-invalid="!!form.errors.name"
                            />
                        </FormField>

                        <FormField label="Raison sociale" for="legal_name" :error="form.errors.legal_name">
                            <Input id="legal_name" v-model="form.legal_name" maxlength="180" placeholder="Atelier Koné & Fils SARL" />
                        </FormField>

                        <FormField label="Description" for="description" :error="form.errors.description">
                            <Textarea
                                id="description"
                                v-model="form.description"
                                rows="4"
                                maxlength="2000"
                                placeholder="Présentez votre structure et vos domaines d’intervention."
                            />
                        </FormField>

                        <FormField label="Téléphone" for="phone" :error="form.errors.phone">
                            <PhoneInput id="phone" v-model="form.phone" :invalid="!!form.errors.phone" />
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
                            <Input id="address" v-model="form.address" maxlength="255" />
                        </FormField>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <FormField label="Ville" for="city" :error="form.errors.city" required>
                                <Input id="city" v-model="form.city" required maxlength="100" />
                            </FormField>

                            <FormField label="Commune" for="commune" :error="form.errors.commune">
                                <Input id="commune" v-model="form.commune" maxlength="100" placeholder="Commune IV" />
                            </FormField>
                        </div>

                        <FormField label="Logo" for="logo" :error="form.errors.logo" hint="JPEG, PNG ou WebP, 2 Mo maximum.">
                            <Input id="logo" type="file" accept="image/*" @change="onLogoChange" :aria-invalid="!!form.errors.logo" />
                        </FormField>

                        <Button type="submit" variant="brand" size="lg" :disabled="form.processing">
                            <LoaderCircle v-if="form.processing" class="size-4 animate-spin" aria-hidden="true" />
                            <Building2 v-else class="size-4" aria-hidden="true" />
                            Créer l’entreprise
                        </Button>
                    </CardContent>
                </Card>
            </form>
        </div>
    </AppLayout>
</template>
