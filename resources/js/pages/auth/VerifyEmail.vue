<script setup lang="ts">
import FormField from '@/components/FormField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { AlertCircle, CheckCircle2, ExternalLink, LoaderCircle, LogOut, MailCheck } from 'lucide-vue-next';

interface Props {
    status?: string;
    error?: string;
    email?: string;
    isAuthenticated?: boolean;
    verificationUrl?: string | null;
}

const props = withDefaults(defineProps<Props>(), {
    status: undefined,
    error: undefined,
    email: '',
    isAuthenticated: false,
    verificationUrl: null,
});

const form = useForm({
    email: props.email,
});

const submit = () => {
    form.post(route('verification.send'), {
        preserveScroll: true,
        onSuccess: () => form.reset('email'),
    });
};
</script>

<template>
    <AuthLayout title="Vérifiez votre adresse e-mail" description="Cliquez sur le lien que nous venons de vous envoyer pour activer votre compte.">
        <Head title="Vérification de l’e-mail" />

        <div class="mb-5 flex justify-center">
            <div
                class="flex size-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 ring-1 ring-inset ring-brand-100 dark:bg-brand-950/60 dark:text-brand-300 dark:ring-brand-900"
            >
                <MailCheck class="size-7" aria-hidden="true" />
            </div>
        </div>

        <div
            v-if="error"
            class="mb-5 flex items-start gap-2.5 rounded-lg border border-destructive/20 bg-destructive/10 p-3.5 text-sm text-destructive"
            role="alert"
        >
            <AlertCircle class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
            <span>{{ error }}</span>
        </div>

        <div
            v-if="status === 'verification-link-sent'"
            class="mb-5 flex items-start gap-2.5 rounded-lg border border-success/20 bg-success/10 p-3.5 text-sm text-success"
            role="status"
        >
            <CheckCircle2 class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
            <span>Un nouveau lien de vérification vient d’être envoyé à votre adresse e-mail.</span>
        </div>

        <div v-if="verificationUrl" class="mb-5 rounded-lg border border-border/80 bg-muted/40 p-3.5 text-xs leading-relaxed text-muted-foreground">
            <p class="font-medium text-foreground">Mode développement</p>
            <p class="mt-1">
                Le courrier est écrit dans <code class="font-mono">storage/logs/laravel.log</code>. Vous pouvez aussi ouvrir directement le lien :
            </p>
            <Button as-child variant="outline" size="sm" class="mt-2">
                <a :href="verificationUrl" target="_blank" rel="noopener">
                    <ExternalLink class="size-3.5" aria-hidden="true" />
                    Ouvrir le lien de vérification
                </a>
            </Button>
        </div>

        <form v-if="!isAuthenticated" class="space-y-4 stagger-children" @submit.prevent="submit">
            <FormField
                label="Adresse e-mail"
                for="email"
                :error="form.errors.email"
                hint="Saisissez l’adresse e-mail de votre compte pour recevoir un nouveau lien."
            >
                <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    name="email"
                    required
                    autocomplete="username"
                    placeholder="vous@exemple.com"
                    :aria-invalid="!!form.errors.email"
                />
            </FormField>

            <Button type="submit" variant="brand" size="lg" class="w-full" :disabled="form.processing">
                <LoaderCircle v-if="form.processing" class="size-4 animate-spin" aria-hidden="true" />
                Renvoyer le lien de vérification
            </Button>
        </form>

        <form v-else class="space-y-3 stagger-children" @submit.prevent="submit">
            <Button type="submit" variant="brand" size="lg" class="w-full" :disabled="form.processing">
                <LoaderCircle v-if="form.processing" class="size-4 animate-spin" aria-hidden="true" />
                Renvoyer l’e-mail de vérification
            </Button>

            <Button as-child variant="ghost" class="w-full text-muted-foreground">
                <Link :href="route('logout')" method="post" as="button">
                    <LogOut class="size-4" aria-hidden="true" />
                    Se déconnecter
                </Link>
            </Button>
        </form>

        <p class="mt-6 text-center text-xs leading-relaxed text-muted-foreground">
            Vous n’avez pas reçu d’e-mail ? Vérifiez vos courriers indésirables, ou
            <Link :href="route('password.request')" class="font-medium text-brand-700 underline-offset-4 hover:underline dark:text-brand-300">
                demandez un nouveau lien
            </Link>
            .
        </p>
    </AuthLayout>
</template>
