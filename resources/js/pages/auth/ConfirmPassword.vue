<script setup lang="ts">
import FormField from '@/components/FormField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, LockKeyhole } from 'lucide-vue-next';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <AuthLayout title="Confirmez votre mot de passe" description="Cette zone est sécurisée. Confirmez votre mot de passe pour continuer.">
        <Head title="Confirmer le mot de passe" />

        <form @submit.prevent="submit" class="space-y-5">
            <FormField label="Mot de passe" for="password" :error="form.errors.password" required>
                <div class="relative">
                    <LockKeyhole
                        class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <Input
                        id="password"
                        type="password"
                        required
                        autofocus
                        autocomplete="current-password"
                        v-model="form.password"
                        placeholder="Votre mot de passe"
                        class="pl-10"
                        :aria-invalid="!!form.errors.password"
                    />
                </div>
            </FormField>

            <Button type="submit" variant="brand" size="lg" class="w-full" :disabled="form.processing">
                <LoaderCircle v-if="form.processing" class="size-4 animate-spin" aria-hidden="true" />
                Confirmer
            </Button>
        </form>
    </AuthLayout>
</template>
