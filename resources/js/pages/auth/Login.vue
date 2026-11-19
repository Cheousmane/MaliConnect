<script setup lang="ts">
import FormField from '@/components/FormField.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowRight, CheckCircle2, Eye, EyeOff, LoaderCircle, LockKeyhole, Mail } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AuthBase title="Bon retour parmi nous" description="Connectez-vous pour retrouver votre espace et vos échanges.">
        <Head title="Connexion" />

        <div
            v-if="status"
            class="mb-5 flex items-start gap-2.5 rounded-lg border border-success/20 bg-success/10 px-3.5 py-3 text-sm text-success"
            role="status"
        >
            <CheckCircle2 class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
            <span>{{ status }}</span>
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-5">
            <FormField label="Adresse e-mail" for="email" :error="form.errors.email" required>
                <div class="relative">
                    <Mail class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
                    <Input
                        id="email"
                        type="email"
                        required
                        autofocus
                        autocomplete="email"
                        v-model="form.email"
                        placeholder="vous@exemple.com"
                        class="pl-10"
                        :aria-invalid="!!form.errors.email"
                    />
                </div>
            </FormField>

            <FormField label="Mot de passe" for="password" :error="form.errors.password" required>
                <template #action>
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-xs font-medium text-brand-700 transition-colors hover:text-brand-800 dark:text-brand-300 dark:hover:text-brand-200"
                    >
                        Mot de passe oublié ?
                    </Link>
                </template>

                <div class="relative">
                    <LockKeyhole
                        class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <Input
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        required
                        autocomplete="current-password"
                        v-model="form.password"
                        placeholder="Votre mot de passe"
                        class="pl-10 pr-11"
                        :aria-invalid="!!form.errors.password"
                    />
                    <button
                        type="button"
                        class="absolute right-1.5 top-1/2 flex size-8 -translate-y-1/2 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                        :aria-label="showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
                        @click="showPassword = !showPassword"
                    >
                        <EyeOff v-if="showPassword" class="size-4" aria-hidden="true" />
                        <Eye v-else class="size-4" aria-hidden="true" />
                    </button>
                </div>
            </FormField>

            <Label for="remember" class="flex w-fit cursor-pointer items-center gap-2.5 text-sm font-normal text-muted-foreground">
                <Checkbox id="remember" v-model:checked="form.remember" />
                Rester connecté sur cet appareil
            </Label>

            <Button type="submit" variant="brand" size="lg" class="mt-1 w-full" :disabled="form.processing">
                <LoaderCircle v-if="form.processing" class="size-4 animate-spin" aria-hidden="true" />
                Se connecter
                <ArrowRight v-if="!form.processing" class="size-4" aria-hidden="true" />
            </Button>
        </form>

        <p class="mt-6 text-center text-sm text-muted-foreground">
            Pas encore de compte ?
            <Link
                :href="route('register')"
                class="font-semibold text-brand-700 underline-offset-4 transition-colors hover:underline dark:text-brand-300"
            >
                Créer un compte
            </Link>
        </p>
    </AuthBase>
</template>
