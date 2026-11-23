<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import type { SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { useScroll } from '@vueuse/core';
import { ArrowRight, Menu, Search } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage<SharedData>();
const authUser = computed(() => page.props.auth?.user ?? null);

const { y } = useScroll(typeof window !== 'undefined' ? window : undefined);
const scrolled = computed(() => y.value > 8);

const scrollProgress = computed(() => {
    if (typeof document === 'undefined') {
        return 0;
    }

    const scrollable = document.documentElement.scrollHeight - document.documentElement.clientHeight;

    if (scrollable <= 0) {
        return 0;
    }

    return Math.min(100, (y.value / scrollable) * 100);
});

const navigation = [
    { label: 'Catégories', href: '#categories' },
    { label: 'Professionnels', href: '#professionnels' },
    { label: 'Fonctionnement', href: '#fonctionnement' },
    { label: 'Aperçu', href: '#apercu' },
    { label: 'Tarifs', href: '#tarifs' },
];
</script>

<template>
    <header
        class="glass-surface sticky top-0 z-40 border-b transition-shadow duration-300"
        :class="scrolled ? 'border-border/70 shadow-soft' : 'border-transparent'"
    >
        <div class="page-container flex h-16 items-center justify-between gap-4 lg:h-[72px]">
            <Link :href="route('home')" class="shrink-0" aria-label="MaliConnect — Accueil">
                <AppLogo />
            </Link>

            <nav class="hidden items-center gap-1 lg:flex" aria-label="Navigation principale">
                <a
                    v-for="item in navigation"
                    :key="item.label"
                    :href="item.href"
                    class="rounded-lg px-3.5 py-2 text-sm font-medium text-muted-foreground transition-colors duration-200 hover:bg-muted/70 hover:text-foreground"
                >
                    {{ item.label }}
                </a>
            </nav>

            <div class="flex items-center gap-1.5 sm:gap-2">
                <ThemeToggle class="hidden sm:inline-flex" />

                <Button as-child variant="ghost" size="sm" class="hidden gap-1.5 text-muted-foreground sm:inline-flex">
                    <Link :href="route('search')">
                        <Search class="size-4" aria-hidden="true" />
                        Rechercher
                    </Link>
                </Button>

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
                                <Link
                                    :href="route('search')"
                                    class="flex items-center justify-between rounded-lg bg-brand-50 px-3 py-3 text-sm font-semibold text-brand-800 transition-colors hover:bg-brand-100 dark:bg-brand-950/60 dark:text-brand-200"
                                >
                                    Rechercher un professionnel
                                    <Search class="size-4" aria-hidden="true" />
                                </Link>

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

        <div
            class="pointer-events-none absolute inset-x-0 bottom-0 h-[2px] origin-left bg-brand-600/80 transition-[width] duration-200 ease-out dark:bg-brand-400/80"
            :style="{ width: scrollProgress + '%' }"
            aria-hidden="true"
        />
    </header>
</template>
