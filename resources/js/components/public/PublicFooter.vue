<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import { Button } from '@/components/ui/button';
import type { SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowUpRight, Mail, MapPin, Search } from 'lucide-vue-next';
import { computed } from 'vue';

const year = new Date().getFullYear();

const page = usePage<SharedData>();
const authUser = computed(() => page.props.auth?.user ?? null);
const ctaProHref = computed(() => (authUser.value ? route('pro.profiles.index') : route('register')));

const platformLinks = [
    { label: 'Rechercher un professionnel', href: route('search') },
    { label: 'Toutes les catégories', href: route('categories.index') },
    { label: 'Comment ça marche', href: '#fonctionnement' },
    { label: 'Transparence des tarifs', href: '#tarifs' },
];

const professionalLinks = computed(() => [
    { label: 'Développer mon activité', href: ctaProHref.value },
    { label: 'Formules et tarifs', href: '#formules' },
    { label: 'Se connecter', href: route('login') },
    { label: 'Créer un compte', href: route('register') },
]);
</script>

<template>
    <footer class="border-t border-border/70 bg-muted/30">
        <div class="page-container py-12 sm:py-16">
            <div class="grid gap-10 lg:grid-cols-[1.5fr_1fr_1fr_1.1fr]">
                <div class="max-w-sm">
                    <AppLogo />

                    <p class="mt-5 text-sm leading-relaxed text-muted-foreground">
                        MaliConnect rapproche les particuliers des professionnels de confiance à Bamako : recherche locale, profils vérifiés et
                        tarifs plus transparents.
                    </p>

                    <div class="mt-5">
                        <Button as-child variant="soft" size="sm">
                            <Link :href="route('search')">
                                <Search class="size-3.5" aria-hidden="true" />
                                Trouver un professionnel
                            </Link>
                        </Button>
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-foreground">Plateforme</h3>
                    <ul class="mt-4 space-y-2.5">
                        <li v-for="link in platformLinks" :key="link.label">
                            <Link
                                v-if="!link.href.startsWith('#')"
                                :href="link.href"
                                class="text-sm text-muted-foreground transition-colors duration-200 hover:text-brand-700 dark:hover:text-brand-300"
                            >
                                {{ link.label }}
                            </Link>
                            <a
                                v-else
                                :href="link.href"
                                class="text-sm text-muted-foreground transition-colors duration-200 hover:text-brand-700 dark:hover:text-brand-300"
                            >
                                {{ link.label }}
                            </a>
                        </li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-foreground">Professionnels</h3>
                    <ul class="mt-4 space-y-2.5">
                        <li v-for="link in professionalLinks" :key="link.label">
                            <Link
                                v-if="!link.href.startsWith('#')"
                                :href="link.href"
                                class="inline-flex items-center gap-1 text-sm text-muted-foreground transition-colors duration-200 hover:text-brand-700 dark:hover:text-brand-300"
                            >
                                {{ link.label }}
                            </Link>
                            <a
                                v-else
                                :href="link.href"
                                class="inline-flex items-center gap-1 text-sm text-muted-foreground transition-colors duration-200 hover:text-brand-700 dark:hover:text-brand-300"
                            >
                                {{ link.label }}
                                <ArrowUpRight class="size-3" aria-hidden="true" />
                            </a>
                        </li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-foreground">Contact</h3>
                    <ul class="mt-4 space-y-3">
                        <li class="flex items-start gap-2.5 text-sm text-muted-foreground">
                            <MapPin class="mt-0.5 size-4 shrink-0 text-brand-600 dark:text-brand-400" aria-hidden="true" />
                            <span>Bamako, Mali</span>
                        </li>
                        <li class="flex items-start gap-2.5 text-sm text-muted-foreground">
                            <Mail class="mt-0.5 size-4 shrink-0 text-brand-600 dark:text-brand-400" aria-hidden="true" />
                            <a href="mailto:contact@maliconnect.ml" class="transition-colors hover:text-foreground">contact@maliconnect.ml</a>
                        </li>
                    </ul>

                    <p class="mt-5 text-xs leading-relaxed text-muted-foreground">
                        Une question, un signalement ? Écrivez-nous : nous répondons depuis Bamako.
                    </p>
                </div>
            </div>

            <div class="mt-12 flex flex-col items-start justify-between gap-4 border-t border-border/70 pt-6 sm:flex-row sm:items-center">
                <p class="text-xs text-muted-foreground">© {{ year }} MaliConnect. Tous droits réservés.</p>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-muted-foreground">
                    <Link :href="route('privacy')" class="transition-colors hover:text-brand-700 dark:hover:text-brand-300">
                        Politique de confidentialité
                    </Link>
                    <span class="hidden sm:inline text-border" aria-hidden="true">•</span>
                    <span>Profils vérifiés avant publication</span>
                </div>
            </div>
        </div>
    </footer>
</template>
