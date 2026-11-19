<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { AlertTriangle, LoaderCircle } from 'lucide-vue-next';

import FormField from '@/components/FormField.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';

const form = useForm({
    password: '',
});

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => document.getElementById('delete-password')?.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <div class="overflow-hidden rounded-xl border border-destructive/20 bg-card shadow-soft">
        <div class="flex items-start gap-3 border-b border-destructive/15 bg-destructive/5 p-5">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-destructive/10 text-destructive">
                <AlertTriangle class="size-5" aria-hidden="true" />
            </span>
            <div class="min-w-0">
                <h3 class="font-display text-base font-semibold text-foreground">Supprimer le compte</h3>
                <p class="mt-1 text-sm leading-relaxed text-muted-foreground">
                    La suppression de votre compte efface définitivement vos informations et vos données. Cette action est irréversible.
                </p>
            </div>
        </div>

        <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-xs leading-relaxed text-muted-foreground">
                Avant de continuer, assurez-vous d’avoir sauvegardé les informations qui vous sont utiles.
            </p>

            <Dialog>
                <DialogTrigger as-child>
                    <Button variant="destructive" class="shrink-0">Supprimer mon compte</Button>
                </DialogTrigger>

                <DialogContent class="max-w-md">
                    <form class="space-y-5" @submit.prevent="deleteUser">
                        <DialogHeader class="space-y-3">
                            <DialogTitle>Confirmer la suppression du compte</DialogTitle>
                            <DialogDescription>
                                Toutes vos données seront définitivement supprimées. Saisissez votre mot de passe pour confirmer votre choix.
                            </DialogDescription>
                        </DialogHeader>

                        <FormField label="Mot de passe" for="delete-password" :error="form.errors.password" required>
                            <Input
                                id="delete-password"
                                v-model="form.password"
                                type="password"
                                name="password"
                                autocomplete="current-password"
                                placeholder="Votre mot de passe"
                                :aria-invalid="!!form.errors.password"
                            />
                        </FormField>

                        <DialogFooter class="flex-col gap-2 sm:flex-row sm:gap-2">
                            <DialogClose as-child>
                                <Button type="button" variant="outline" @click="closeModal">Annuler</Button>
                            </DialogClose>

                            <Button type="submit" variant="destructive" :disabled="form.processing">
                                <LoaderCircle v-if="form.processing" class="size-4 animate-spin" aria-hidden="true" />
                                Supprimer définitivement
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
