<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { usePreviewMode } from '@/composables/usePreviewMode';
import type { SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, Menu } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage<SharedData>();
const authUser = computed(() => page.props.auth?.user ?? null);
const isPreviewMode = usePreviewMode();

const baseNavigation = [
    { label: 'Fonctionnalités', href: '#fonctionnalites' },
    { label: 'Catégories', href: '#categories' },
    { label: 'Professionnels', href: '#annuaire' },
];

const navigation = computed(() =>
    isPreviewMode.value
        ? baseNavigation
        : [...baseNavigation, { label: 'Recherche', href: '/recherche' }, { label: 'Aperçu mobile', href: '#apercu' }],
);
</script>

<template>
    <header class="glass-surface sticky top-0 z-40 border-b border-border/60">
        <div class="page-container flex h-16 items-center justify-between gap-4 lg:h-[72px]">
            <Link :href="route('home')" class="shrink-0" aria-label="MaliConnect — Accueil">
                <AppLogo />
            </Link>

            <nav class="hidden items-center gap-1 lg:flex" aria-label="Navigation principale">
                <a
                    v-for="item in navigation"
                    :key="item.label"
                    :href="item.href"
                    class="rounded-lg px-3.5 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted/70 hover:text-foreground"
                >
                    {{ item.label }}
                </a>
            </nav>

            <div class="flex items-center gap-1.5 sm:gap-2">
                <ThemeToggle class="hidden sm:inline-flex" />

                <template v-if="authUser">
                    <Button as-child variant="brand" size="sm" class="hidden sm:inline-flex">
                        <Link :href="route('dashboard')">
                            Mon espace
                            <ArrowRight class="size-4" aria-hidden="true" />
                        </Link>
                    </Button>
                </template>

                <template v-else>
                    <Button as-child variant="ghost" size="sm" class="hidden text-muted-foreground sm:inline-flex">
                        <Link :href="route('login')">Se connecter</Link>
                    </Button>
                    <Button as-child variant="brand" size="sm" class="hidden sm:inline-flex">
                        <Link :href="route('register')">Créer un compte</Link>
                    </Button>
                </template>

                <Button v-if="!authUser" as-child variant="brand" size="sm" class="sm:hidden">
                    <Link :href="route('register')">S’inscrire</Link>
                </Button>

                <Sheet>
                    <SheetTrigger as-child>
                        <Button variant="ghost" size="icon" class="lg:hidden" aria-label="Ouvrir le menu">
                            <Menu class="size-5" aria-hidden="true" />
                        </Button>
                    </SheetTrigger>
                    <SheetContent side="right" class="w-[min(20rem,calc(100vw-2rem))] p-0">
                        <SheetTitle class="sr-only">Menu de navigation</SheetTitle>

                        <div class="flex h-full flex-col">
                            <div class="border-b border-border/70 px-5 py-5">
                                <AppLogo show-tagline />
                            </div>

                            <nav class="flex-1 space-y-1 overflow-y-auto p-4" aria-label="Navigation mobile">
                                <a
                                    v-for="item in navigation"
                                    :key="item.label"
                                    :href="item.href"
                                    class="flex items-center justify-between rounded-lg px-3 py-3 text-sm font-medium text-foreground transition-colors hover:bg-muted"
                                >
                                    {{ item.label }}
                                    <ArrowRight class="size-4 text-muted-foreground" aria-hidden="true" />
                                </a>
                            </nav>

                            <div class="space-y-2 border-t border-border/70 p-4">
                                <div class="flex items-center justify-between rounded-lg border border-border/70 bg-background/60 px-3 py-2">
                                    <span class="text-sm font-medium text-muted-foreground">Apparence</span>
                                    <ThemeToggle />
                                </div>

                                <template v-if="authUser">
                                    <Button as-child variant="brand" class="w-full">
                                        <Link :href="route('dashboard')">Mon espace</Link>
                                    </Button>
                                </template>

                                <template v-else>
                                    <Button as-child variant="outline" class="w-full">
                                        <Link :href="route('login')">Se connecter</Link>
                                    </Button>
                                    <Button as-child variant="brand" class="w-full">
                                        <Link :href="route('register')">Créer un compte</Link>
                                    </Button>
                                </template>
                            </div>
                        </div>
                    </SheetContent>
                </Sheet>
            </div>
        </div>
    </header>
</template>
