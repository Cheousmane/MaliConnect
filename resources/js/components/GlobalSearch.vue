<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/vue3';
import { useEventListener } from '@vueuse/core';
import { ArrowUpRight, LayoutDashboard, Palette, Search, SearchX, ShieldCheck, UserRound } from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';

interface Props {
    mode?: 'full' | 'icon';
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    mode: 'full',
});

const open = ref(false);
const query = ref('');
const input = ref<HTMLInputElement | null>(null);

const quickLinks = [
    {
        title: 'Vue d’ensemble',
        description: 'Tableau de bord professionnel',
        href: route('dashboard'),
        icon: LayoutDashboard,
    },
    {
        title: 'Profil',
        description: 'Informations personnelles et professionnelles',
        href: route('profile.edit'),
        icon: UserRound,
    },
    {
        title: 'Apparence',
        description: 'Thème clair, sombre ou système',
        href: route('appearance'),
        icon: Palette,
    },
    {
        title: 'Sécurité',
        description: 'Mot de passe et connexion',
        href: route('password.edit'),
        icon: ShieldCheck,
    },
];

const filteredLinks = computed(() => {
    const normalized = query.value.trim().toLowerCase();

    if (!normalized) {
        return quickLinks;
    }

    return quickLinks.filter((link) => `${link.title} ${link.description}`.toLowerCase().includes(normalized));
});

watch(open, async (isOpen) => {
    if (isOpen) {
        await nextTick();
        input.value?.focus();
    } else {
        query.value = '';
    }
});

useEventListener('keydown', (event: KeyboardEvent) => {
    if (event.key.toLowerCase() === 'k' && (event.metaKey || event.ctrlKey)) {
        event.preventDefault();
        open.value = true;
    }
});

function close() {
    open.value = false;
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button
                v-if="props.mode === 'full'"
                variant="outline"
                :class="
                    cn(
                        'h-9 w-52 justify-start gap-2 rounded-lg border-border/80 bg-muted/40 px-3 text-muted-foreground shadow-none hover:bg-muted/70 lg:w-60',
                        props.class,
                    )
                "
            >
                <Search class="size-4" aria-hidden="true" />
                <span class="text-sm font-normal">Rechercher…</span>
                <kbd class="ml-auto rounded border border-border bg-background px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground">
                    Ctrl K
                </kbd>
            </Button>

            <Button v-else variant="ghost" size="icon" class="text-muted-foreground" aria-label="Ouvrir la recherche">
                <Search class="size-[18px]" aria-hidden="true" />
            </Button>
        </DialogTrigger>

        <DialogContent class="top-4 max-w-xl translate-y-0 gap-0 overflow-hidden p-0 sm:top-[12%]">
            <DialogTitle class="sr-only">Recherche globale</DialogTitle>
            <DialogDescription class="sr-only">Recherchez une page ou un réglage dans MaliConnect.</DialogDescription>

            <div class="flex items-center gap-3 border-b border-border/70 px-4">
                <Search class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
                <input
                    ref="input"
                    v-model="query"
                    type="search"
                    placeholder="Rechercher une page, un réglage…"
                    class="h-12 w-full min-w-0 bg-transparent text-sm text-foreground outline-none placeholder:text-muted-foreground/80"
                    aria-label="Rechercher une page ou un réglage"
                />
                <kbd
                    class="hidden shrink-0 rounded border border-border bg-muted px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground sm:block"
                >
                    Échap
                </kbd>
            </div>

            <div class="max-h-[min(24rem,60vh)] overflow-y-auto p-2">
                <p class="px-2.5 py-1.5 text-[11px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">
                    {{ query ? 'Résultats' : 'Accès rapides' }}
                </p>

                <div v-if="filteredLinks.length > 0" class="space-y-0.5">
                    <Link
                        v-for="link in filteredLinks"
                        :key="link.href"
                        :href="link.href"
                        class="group flex items-center gap-3 rounded-lg px-2.5 py-2.5 transition-colors hover:bg-muted"
                        @click="close"
                    >
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-950/60 dark:text-brand-300"
                        >
                            <component :is="link.icon" class="size-4" aria-hidden="true" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium text-foreground">{{ link.title }}</span>
                            <span class="block truncate text-xs text-muted-foreground">{{ link.description }}</span>
                        </span>
                        <ArrowUpRight
                            class="size-4 shrink-0 text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100"
                            aria-hidden="true"
                        />
                    </Link>
                </div>

                <div v-else class="flex flex-col items-center justify-center px-4 py-10 text-center">
                    <SearchX class="size-8 text-muted-foreground/60" aria-hidden="true" />
                    <p class="mt-3 text-sm font-medium text-foreground">Aucun résultat</p>
                    <p class="mt-1 text-xs text-muted-foreground">Essayez un autre mot-clé.</p>
                </div>
            </div>

            <div class="flex items-center justify-between gap-3 border-t border-border/70 bg-muted/30 px-4 py-2 text-[11px] text-muted-foreground">
                <span>Navigation rapide MaliConnect</span>
                <span class="hidden sm:inline">Ctrl K pour ouvrir</span>
            </div>
        </DialogContent>
    </Dialog>
</template>
