<script setup lang="ts">
import { TransitionRoot } from '@headlessui/vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

import DeleteUser from '@/components/DeleteUser.vue';
import FormField from '@/components/FormField.vue';
import PhoneInput from '@/components/PhoneInput.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { accountStatusLabel, localPhoneNumber, roleLabel } from '@/lib/identity';
import { type BreadcrumbItem, type SharedData, type User } from '@/types';
import { CheckCircle2, LoaderCircle, MailWarning, ShieldCheck } from 'lucide-vue-next';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profil',
        href: '/settings/profile',
    },
];

const page = usePage<SharedData>();
const user = page.props.auth.user as User;
const roles = page.props.auth.roles ?? [];

// Formate la dernière connexion pour un affichage lisible.
const lastLogin = user.last_login_at
    ? new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(user.last_login_at))
    : 'Première connexion à venir';

const form = useForm({
    name: user.name,
    email: user.email,
    phone: localPhoneNumber(user.phone),
});

const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Profil" />

        <SettingsLayout>
            <div class="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Informations personnelles</CardTitle>
                        <CardDescription>Mettez à jour votre nom et votre adresse e-mail.</CardDescription>
                    </CardHeader>

                    <CardContent>
                        <form @submit.prevent="submit" class="max-w-xl space-y-5">
                            <FormField label="Nom complet" for="name" :error="form.errors.name" required>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    required
                                    autocomplete="name"
                                    placeholder="Votre nom et prénom"
                                    :aria-invalid="!!form.errors.name"
                                />
                            </FormField>

                            <FormField
                                label="Adresse e-mail"
                                for="email"
                                :error="form.errors.email"
                                hint="Cette adresse vous permet de vous connecter et de recevoir nos notifications."
                                required
                            >
                                <Input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    required
                                    autocomplete="username"
                                    placeholder="vous@exemple.com"
                                    :aria-invalid="!!form.errors.email"
                                />
                            </FormField>

                            <FormField
                                label="Numéro de téléphone"
                                for="phone"
                                :error="form.errors.phone"
                                hint="8 chiffres, par exemple 76 12 34 56. Laissez vide pour le retirer."
                            >
                                <PhoneInput id="phone" v-model="form.phone" placeholder="76 12 34 56" :invalid="!!form.errors.phone" />
                            </FormField>

                            <div v-if="mustVerifyEmail && !user.email_verified_at" class="rounded-xl border border-warning/25 bg-warning/10 p-4">
                                <div class="flex items-start gap-3">
                                    <MailWarning class="mt-0.5 size-4 shrink-0 text-amber-600 dark:text-amber-300" aria-hidden="true" />
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-foreground">Votre adresse e-mail n’est pas encore vérifiée.</p>
                                        <Link
                                            :href="route('verification.send')"
                                            method="post"
                                            as="button"
                                            class="mt-1 text-sm font-medium text-brand-700 underline-offset-4 hover:underline dark:text-brand-300"
                                        >
                                            Cliquez ici pour renvoyer l’e-mail de vérification.
                                        </Link>

                                        <p v-if="status === 'verification-link-sent'" class="mt-2 text-xs font-medium text-success">
                                            Un nouveau lien de vérification vient d’être envoyé à votre adresse e-mail.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-4 pt-1">
                                <Button type="submit" variant="brand" :disabled="form.processing">
                                    <LoaderCircle v-if="form.processing" class="size-4 animate-spin" aria-hidden="true" />
                                    Enregistrer les modifications
                                </Button>

                                <TransitionRoot
                                    :show="form.recentlySuccessful"
                                    enter="transition ease-in-out duration-200"
                                    enter-from="opacity-0 translate-y-1"
                                    enter-to="opacity-100 translate-y-0"
                                    leave="transition ease-in-out duration-200"
                                    leave-from="opacity-100"
                                    leave-to="opacity-0"
                                >
                                    <p class="flex items-center gap-1.5 text-sm font-medium text-success">
                                        <CheckCircle2 class="size-4" aria-hidden="true" />
                                        Profil mis à jour.
                                    </p>
                                </TransitionRoot>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <div class="flex items-start gap-2.5">
                            <span
                                class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-950/60 dark:text-brand-300"
                            >
                                <ShieldCheck class="size-4" aria-hidden="true" />
                            </span>
                            <div>
                                <CardTitle class="text-base">État du compte</CardTitle>
                                <CardDescription>Statut d’activité, rôle appliqué et dernière connexion.</CardDescription>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent>
                        <dl class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <dt class="text-[11px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">Statut</dt>
                                <dd class="mt-1.5">
                                    <StatusBadge
                                        :tone="user.status === 'active' ? 'success' : user.status === 'suspended' ? 'warning' : 'neutral'"
                                        :label="accountStatusLabel(user.status)"
                                    />
                                </dd>
                            </div>

                            <div>
                                <dt class="text-[11px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">Rôle</dt>
                                <dd class="mt-1.5 flex flex-wrap gap-1.5">
                                    <StatusBadge v-for="role in roles" :key="role" tone="brand" :label="roleLabel(role)" />
                                    <span v-if="roles.length === 0" class="text-sm text-muted-foreground">Aucun rôle</span>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-[11px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">Dernière connexion</dt>
                                <dd class="mt-1.5 text-sm text-foreground">{{ lastLogin }}</dd>
                            </div>
                        </dl>
                    </CardContent>
                </Card>

                <DeleteUser />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
