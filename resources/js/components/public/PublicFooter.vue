<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import { usePreviewMode } from '@/composables/usePreviewMode';
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight, Mail, MapPin } from 'lucide-vue-next';
import { computed } from 'vue';

const year = new Date().getFullYear();
const isPreviewMode = usePreviewMode();

const basePlatformLinks = [
    { label: 'Fonctionnalités', href: '#fonctionnalites' },
    { label: 'Catégories de services', href: '#categories' },
    { label: 'Comment ça marche', href: '#fonctionnement' },
];

const platformLinks = computed(() =>
    isPreviewMode.value
        ? basePlatformLinks
        : [
              ...basePlatformLinks,
              { label: 'Catalogue', href: '/categories' },
              { label: 'Trouver un professionnel', href: '/recherche' },
              { label: 'Aperçu mobile', href: '#apercu' },
          ],
);

const professionalLinks = [
    { label: 'Devenir professionnel', href: '#professionnels' },
    { label: 'Créer mon profil', href: '/pro/profiles/create' },
    { label: 'Se connecter', href: route('login') },
    { label: 'Créer un compte', href: route('register') },
];
</script>

<template>
    <footer class="border-t border-border/70 bg-muted/30">
        <div class="page-container py-12 sm:py-16">
            <div class="grid gap-10 lg:grid-cols-[1.4fr_1fr_1fr_1.1fr]">
                <div class="max-w-sm">
                    <AppLogo show-tagline />
                    <p class="mt-5 text-sm leading-relaxed text-muted-foreground">
                        MaliConnect prépare une plateforme simple et humaine pour rapprocher les particuliers des professionnels de confiance, partout
                        au Mali.
                    </p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-foreground">Plateforme</h3>
                    <ul class="mt-4 space-y-2.5">
                        <li v-for="link in platformLinks" :key="link.label">
                            <a
                                :href="link.href"
                                class="text-sm text-muted-foreground transition-colors hover:text-brand-700 dark:hover:text-brand-300"
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
                                :href="link.href"
                                class="inline-flex items-center gap-1 text-sm text-muted-foreground transition-colors hover:text-brand-700 dark:hover:text-brand-300"
                            >
                                {{ link.label }}
                                <ArrowUpRight v-if="link.href.startsWith('#')" class="size-3" aria-hidden="true" />
                            </Link>
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
                </div>
            </div>

            <div class="mt-12 flex flex-col items-start justify-between gap-4 border-t border-border/70 pt-6 sm:flex-row sm:items-center">
                <p class="text-xs text-muted-foreground">© {{ year }} MaliConnect. Tous droits réservés.</p>
            </div>
        </div>
    </footer>
</template>
