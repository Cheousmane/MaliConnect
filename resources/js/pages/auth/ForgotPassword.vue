<script setup lang="ts">
import FormField from '@/components/FormField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle2, LoaderCircle, Mail } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <AuthLayout title="Mot de passe oublié ?" description="Indiquez votre adresse e-mail et nous vous enverrons un lien de réinitialisation.">
        <Head title="Mot de passe oublié" />

        <div
            v-if="status"
            class="mb-5 flex items-start gap-2.5 rounded-lg border border-success/20 bg-success/10 px-3.5 py-3 text-sm text-success animate-scale-in"
            role="status"
        >
            <CheckCircle2 class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
            <span>{{ status }}</span>
        </div>

        <form @submit.prevent="submit" class="space-y-5 stagger-children">
            <FormField label="Adresse e-mail" for="email" :error="form.errors.email" required>
                <div class="relative">
                    <Mail class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
                    <Input
                        id="email"
                        type="email"
                        name="email"
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

            <Button type="submit" variant="brand" size="lg" class="w-full press" :disabled="form.processing">
                <LoaderCircle v-if="form.processing" class="size-4 animate-spin" aria-hidden="true" />
                Envoyer le lien de réinitialisation
            </Button>
        </form>

        <div class="mt-6 text-center">
            <Link
                :href="route('login')"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft class="size-4" aria-hidden="true" />
                Revenir à la connexion
            </Link>
        </div>
    </AuthLayout>
</template>
