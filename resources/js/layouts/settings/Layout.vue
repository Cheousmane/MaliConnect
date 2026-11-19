<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import { cn } from '@/lib/utils';
import { Link, usePage } from '@inertiajs/vue3';
import { Palette, ShieldCheck, UserRound } from 'lucide-vue-next';
import { computed } from 'vue';

const items = [
    {
        title: 'Profil',
        description: 'Informations personnelles',
        href: route('profile.edit'),
        icon: UserRound,
    },
    {
        title: 'Mot de passe',
        description: 'Sécurité du compte',
        href: route('password.edit'),
        icon: ShieldCheck,
    },
    {
        title: 'Apparence',
        description: 'Thème de l’interface',
        href: route('appearance'),
        icon: Palette,
    },
];

const page = usePage();
const currentPath = computed(() => page.url.split('?')[0]);

function isActive(href: string): boolean {
    // Les routes nommées peuvent renvoyer une URL absolue : on compare uniquement le chemin.
    try {
        return currentPath.value === new URL(href, window.location.origin).pathname;
    } catch {
        return currentPath.value === href;
    }
}
</script>

<template>
    <div class="space-y-6">
        <PageHeader
            eyebrow="Paramètres"
            title="Votre compte"
            description="Gérez vos informations personnelles, votre sécurité et l’apparence de votre espace MaliConnect."
        />

        <div class="grid gap-6 lg:grid-cols-[248px_minmax(0,1fr)]">
            <aside class="min-w-0">
                <nav
                    class="no-scrollbar flex gap-1 overflow-x-auto rounded-xl border border-border/80 bg-card p-1.5 shadow-soft lg:flex-col"
                    aria-label="Paramètres du compte"
                >
                    <Link
                        v-for="item in items"
                        :key="item.href"
                        :href="item.href"
                        :aria-current="isActive(item.href) ? 'page' : undefined"
                        :class="
                            cn(
                                'group flex min-w-fit items-center gap-2.5 rounded-lg px-3 py-2.5 text-sm transition-colors duration-200 lg:min-w-0',
                                isActive(item.href)
                                    ? 'bg-brand-50 font-semibold text-brand-800 ring-1 ring-inset ring-brand-100 dark:bg-brand-950/60 dark:text-brand-200 dark:ring-brand-900'
                                    : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                            )
                        "
                    >
                        <component :is="item.icon" class="size-4 shrink-0" aria-hidden="true" />
                        <span class="min-w-0">
                            <span class="block truncate">{{ item.title }}</span>
                            <span class="hidden truncate text-[11px] font-normal text-muted-foreground lg:block">
                                {{ item.description }}
                            </span>
                        </span>
                    </Link>
                </nav>
            </aside>

            <section class="min-w-0">
                <slot />
            </section>
        </div>
    </div>
</template>
