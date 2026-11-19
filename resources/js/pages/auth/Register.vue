<script setup lang="ts">
import FormField from '@/components/FormField.vue';
import PhoneInput from '@/components/PhoneInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowRight, Eye, EyeOff, LoaderCircle, LockKeyhole, Mail, UserRound } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    plan?: string;
}>();

const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    plan: props.plan ?? '',
});

const showPassword = ref(false);
const showConfirmation = ref(false);

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthBase title="Créez votre compte" description="Quelques informations suffisent pour rejoindre MaliConnect.">
        <Head title="Créer un compte" />

        <form @submit.prevent="submit" class="flex flex-col gap-4">
            <FormField label="Nom complet" for="name" :error="form.errors.name" required>
                <div class="relative">
                    <UserRound
                        class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <Input
                        id="name"
                        type="text"
                        required
                        autofocus
                        autocomplete="name"
                        v-model="form.name"
                        placeholder="Votre nom et prénom"
                        class="pl-10"
                        :aria-invalid="!!form.errors.name"
                    />
                </div>
            </FormField>

            <FormField label="Adresse e-mail" for="email" :error="form.errors.email" required>
                <div class="relative">
                    <Mail class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
                    <Input
                        id="email"
                        type="email"
                        required
                        autocomplete="email"
                        v-model="form.email"
                        placeholder="vous@exemple.com"
                        class="pl-10"
                        :aria-invalid="!!form.errors.email"
                    />
                </div>
            </FormField>

            <FormField label="Numéro de téléphone" for="phone" :error="form.errors.phone" hint="Facultatif. 8 chiffres, par exemple 76 12 34 56.">
                <PhoneInput id="phone" v-model="form.phone" placeholder="76 12 34 56" :invalid="!!form.errors.phone" />
            </FormField>

            <FormField label="Mot de passe" for="password" :error="form.errors.password" hint="8 caractères minimum." required>
                <div class="relative">
                    <LockKeyhole
                        class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <Input
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        required
                        autocomplete="new-password"
                        v-model="form.password"
                        placeholder="Créez un mot de passe"
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
                        :type="showConfirmation ? 'text' : 'password'"
                        required
                        autocomplete="new-password"
                        v-model="form.password_confirmation"
                        placeholder="Répétez le mot de passe"
                        class="pl-10 pr-11"
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

            <Button type="submit" variant="brand" size="lg" class="mt-2 w-full" :disabled="form.processing">
                <LoaderCircle v-if="form.processing" class="size-4 animate-spin" aria-hidden="true" />
                Créer mon compte
                <ArrowRight v-if="!form.processing" class="size-4" aria-hidden="true" />
            </Button>
        </form>

        <p class="mt-6 text-center text-sm text-muted-foreground">
            Vous avez déjà un compte ?
            <Link
                :href="route('login')"
                class="font-semibold text-brand-700 underline-offset-4 transition-colors hover:underline dark:text-brand-300"
            >
                Se connecter
            </Link>
        </p>
    </AuthBase>
</template>
