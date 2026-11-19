<script setup lang="ts">
import FormField from '@/components/FormField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, LoaderCircle, LockKeyhole } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    token: string;
    email: string;
}

const props = defineProps<Props>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <AuthLayout title="Nouveau mot de passe" description="Choisissez un mot de passe solide pour sécuriser votre compte.">
        <Head title="Réinitialiser le mot de passe" />

        <form @submit.prevent="submit" class="space-y-4">
            <FormField label="Adresse e-mail" for="email" :error="form.errors.email">
                <Input id="email" type="email" name="email" autocomplete="email" v-model="form.email" readonly class="bg-muted/40" />
            </FormField>

            <FormField label="Nouveau mot de passe" for="password" :error="form.errors.password" hint="8 caractères minimum." required>
                <div class="relative">
                    <LockKeyhole
                        class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <Input
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        name="password"
                        required
                        autofocus
                        autocomplete="new-password"
                        v-model="form.password"
                        placeholder="Votre nouveau mot de passe"
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

            <FormField label="Confirmer le mot de passe" for="password_confirmation" :error="form.errors.password_confirmation" required>
                <div class="relative">
                    <LockKeyhole
                        class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <Input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        v-model="form.password_confirmation"
                        placeholder="Répétez le mot de passe"
                        class="pl-10"
                        :aria-invalid="!!form.errors.password_confirmation"
                    />
                </div>
            </FormField>

            <Button type="submit" variant="brand" size="lg" class="mt-2 w-full" :disabled="form.processing">
                <LoaderCircle v-if="form.processing" class="size-4 animate-spin" aria-hidden="true" />
                Réinitialiser le mot de passe
            </Button>
        </form>
    </AuthLayout>
</template>
