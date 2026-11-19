<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Link } from '@inertiajs/vue3';
import { BadgeCheck, CheckCircle2, ShieldCheck, Sparkles } from 'lucide-vue-next';

defineProps<{
    title?: string;
    description?: string;
}>();

const benefits = [
    { icon: BadgeCheck, label: 'Des profils clairs et vérifiés' },
    { icon: ShieldCheck, label: 'Des échanges simples et directs' },
    { icon: Sparkles, label: 'Une expérience pensée pour le Mali' },
];
</script>

<template>
    <div class="relative grid min-h-svh overflow-hidden bg-background lg:grid-cols-[1.05fr_1fr]">
        <!-- Panneau de marque, visible sur grand écran. -->
        <aside class="relative hidden overflow-hidden bg-brand-900 p-10 text-white lg:flex lg:flex-col xl:p-14">
            <Link
                :href="route('home')"
                class="relative z-10 flex items-center gap-3 transition-opacity hover:opacity-80"
                aria-label="Retour a l'accueil"
            >
                <div class="flex size-10 items-center justify-center rounded-xl bg-white/10 ring-1 ring-inset ring-white/20 backdrop-blur">
                    <AppLogoIcon class="size-6" />
                </div>
                <span class="font-display text-lg font-semibold tracking-tight"> Mali<span class="text-gold-300">Connect</span> </span>
            </Link>

            <div class="relative z-10 my-auto max-w-lg py-16">
                <p class="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.2em] text-gold-300">
                    <span class="bg-gold-00/80 h-px w-6" aria-hidden="true" />
                    La confiance, à portée de main
                </p>

                <h2 class="mt-6 font-display text-4xl font-semibold leading-[1.12] tracking-tight xl:text-[44px]">
                    Le bon professionnel,<br />
                    <span class="text-gold-300">au bon moment.</span>
                </h2>

                <p class="mt-5 max-w-md text-[15px] leading-relaxed text-brand-100/85">
                    MaliConnect prépare une nouvelle façon de trouver et de contacter les professionnels de confiance, partout au Mali. Une expérience
                    simple, humaine et pensée pour le quotidien.
                </p>

                <ul class="mt-8 space-y-3.5">
                    <li v-for="benefit in benefits" :key="benefit.label" class="flex items-center gap-3 text-sm text-brand-50/90">
                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-white/10 ring-1 ring-inset ring-white/15">
                            <component :is="benefit.icon" class="size-3.5 text-gold-300" aria-hidden="true" />
                        </span>
                        {{ benefit.label }}
                    </li>
                </ul>
            </div>

            <div class="relative z-10 rounded-2xl border border-white/10 bg-white/[0.06] p-5">
                <p class="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-gold-300">
                    <Sparkles class="size-3.5" aria-hidden="true" />
                    Projet en cours de développement
                </p>
                <p class="mt-3 text-sm leading-relaxed text-brand-50/90">
                    MaliConnect avance par étapes. Créez votre compte dès maintenant et découvrez l’aperçu de l’espace professionnel.
                </p>
                <div class="mt-4 flex items-center gap-2 text-[11px] text-brand-200/80">
                    <CheckCircle2 class="size-3.5 shrink-0 text-gold-300" aria-hidden="true" />
                    Inscription gratuite · Aucune fonctionnalité payante active
                </div>
            </div>
        </aside>

        <!-- Panneau de formulaire. -->
        <main class="relative flex min-h-svh flex-col">
            <header class="relative z-10 flex items-center justify-end gap-4 px-5 py-5 sm:px-8 lg:px-10">
                <div class="flex items-center gap-1">
                    <ThemeToggle />
                </div>
            </header>

            <div class="relative z-10 flex flex-1 items-center justify-center px-5 pb-12 pt-4 sm:px-8 lg:px-10">
                <div class="w-full max-w-[420px]">
                    <div class="mb-8">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-brand-600 dark:text-brand-400">Espace sécurisé</p>
                        <h1 v-if="title" class="mt-2 font-display text-2xl font-semibold tracking-tight text-foreground sm:text-[28px]">
                            {{ title }}
                        </h1>
                        <p v-if="description" class="mt-2 text-sm leading-relaxed text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>

                    <slot />

                    <p class="mt-8 text-center text-xs leading-relaxed text-muted-foreground">
                        En continuant, vous acceptez les conditions d’utilisation et la politique de confidentialité de MaliConnect.
                    </p>
                </div>
            </div>
        </main>
    </div>
</template>
