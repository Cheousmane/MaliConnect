<script setup lang="ts">
import { TransitionRoot } from '@headlessui/vue';
import { Head, useForm } from '@inertiajs/vue3';
import { CheckCircle2, Eye, EyeOff, LoaderCircle, ShieldCheck } from 'lucide-vue-next';
import { ref } from 'vue';

import FormField from '@/components/FormField.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Mot de passe',
        href: '/settings/password',
    },
];

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const showCurrentPassword = ref(false);
const showPassword = ref(false);
const showConfirmation = ref(false);

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: (errors: Record<string, string>) => {
            if (errors.password) {
                form.reset('password', 'password_confirmation');
                document.getElementById('password')?.focus();
            }

            if (errors.current_password) {
                form.reset('current_password');
                document.getElementById('current_password')?.focus();
            }
        },
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Mot de passe" />

        <SettingsLayout>
            <div class="space-y-6">
                <Card>
                    <CardHeader>
                        <div class="flex items-start gap-2.5">
                            <span
                                class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-950/60 dark:text-brand-300"
                            >
                                <ShieldCheck class="size-4" aria-hidden="true" />
                            </span>
                            <div>
                                <CardTitle class="text-base">Sécurité du compte</CardTitle>
                                <CardDescription class="mt-1"> Utilisez un mot de passe long et unique pour protéger votre espace. </CardDescription>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent>
                        <form @submit.prevent="updatePassword" class="max-w-xl space-y-5">
                            <FormField label="Mot de passe actuel" for="current_password" :error="form.errors.current_password" required>
                                <div class="relative">
                                    <Input
                                        id="current_password"
                                        v-model="form.current_password"
                                        :type="showCurrentPassword ? 'text' : 'password'"
                                        autocomplete="current-password"
                                        placeholder="Votre mot de passe actuel"
                                        class="pr-11"
                                        :aria-invalid="!!form.errors.current_password"
                                    />
                                    <button
                                        type="button"
                                        class="absolute right-1.5 top-1/2 flex size-8 -translate-y-1/2 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                        :aria-label="showCurrentPassword ? 'Masquer le mot de passe actuel' : 'Afficher le mot de passe actuel'"
                                        @click="showCurrentPassword = !showCurrentPassword"
                                    >
                                        <EyeOff v-if="showCurrentPassword" class="size-4" aria-hidden="true" />
                                        <Eye v-else class="size-4" aria-hidden="true" />
                                    </button>
                                </div>
                            </FormField>

                            <FormField
                                label="Nouveau mot de passe"
                                for="password"
                                :error="form.errors.password"
                                hint="8 caractères minimum."
                                required
                            >
                                <div class="relative">
                                    <Input
                                        id="password"
                                        v-model="form.password"
                                        :type="showPassword ? 'text' : 'password'"
                                        autocomplete="new-password"
                                        placeholder="Votre nouveau mot de passe"
                                        class="pr-11"
                                        :aria-invalid="!!form.errors.password"
                                    />
                                    <button
                                        type="button"
                                        class="absolute right-1.5 top-1/2 flex size-8 -translate-y-1/2 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                        :aria-label="showPassword ? 'Masquer le nouveau mot de passe' : 'Afficher le nouveau mot de passe'"
                                        @click="showPassword = !showPassword"
                                    >
                                        <EyeOff v-if="showPassword" class="size-4" aria-hidden="true" />
                                        <Eye v-else class="size-4" aria-hidden="true" />
                                    </button>
                                </div>
                            </FormField>

                            <FormField
                                label="Confirmer le nouveau mot de passe"
                                for="password_confirmation"
                                :error="form.errors.password_confirmation"
                                required
                            >
                                <div class="relative">
                                    <Input
                                        id="password_confirmation"
                                        v-model="form.password_confirmation"
                                        :type="showConfirmation ? 'text' : 'password'"
                                        autocomplete="new-password"
                                        placeholder="Répétez le nouveau mot de passe"
                                        class="pr-11"
                                        :aria-invalid="!!form.errors.password_confirmation"
                                    />
                                    <button
                                        type="button"
                                        class="absolute right-1.5 top-1/2 flex size-8 -translate-y-1/2 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                        :aria-label="showConfirmation ? 'Masquer la confirmation' : 'Afficher la confirmation'"
                                        @click="showConfirmation = !showConfirmation"
                                    >
                                        <EyeOff v-if="showConfirmation" class="size-4" aria-hidden="true" />
                                        <Eye v-else class="size-4" aria-hidden="true" />
                                    </button>
                                </div>
                            </FormField>

                            <div class="flex flex-wrap items-center gap-4 pt-1">
                                <Button type="submit" variant="brand" :disabled="form.processing">
                                    <LoaderCircle v-if="form.processing" class="size-4 animate-spin" aria-hidden="true" />
                                    Mettre à jour le mot de passe
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
                                        Mot de passe mis à jour.
                                    </p>
                                </TransitionRoot>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <div class="rounded-xl border border-border/80 bg-muted/30 p-4">
                    <p class="text-sm font-medium text-foreground">Conseils pour un mot de passe solide</p>
                    <ul class="mt-2 space-y-1.5 text-sm text-muted-foreground">
                        <li>• Utilisez au moins 12 caractères, avec des lettres, des chiffres et des symboles.</li>
                        <li>• Évitez les informations personnelles faciles à deviner.</li>
                        <li>• Ne réutilisez pas un mot de passe déjà utilisé sur un autre service.</li>
                    </ul>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
