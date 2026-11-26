<script setup lang="ts">
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuBadge, SidebarMenuButton, SidebarMenuItem, useSidebar } from '@/components/ui/sidebar';
import type { NavGroup, SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';

defineProps<{
    groups: NavGroup[];
}>();

const page = usePage<SharedData>();
const { isMobile, setOpenMobile } = useSidebar();

function isActive(href: string): boolean {
    const current = page.url.split('?')[0].split('#')[0];

    if (href === '/dashboard' || href === '/admin') {
        return current === href;
    }

    return current === href || current.startsWith(`${href}/`);
}

function onNavigate() {
    if (isMobile.value) setOpenMobile(false);
}

function tourIdFor(href: string): string | undefined {
    const map: Record<string, string> = {
        '/demandes': 'nav-demandes',
        '/reservations': 'nav-reservations',
        '/favoris': 'nav-favoris',
        '/pro/profiles': 'nav-profiles',
        '/pro/demandes': 'nav-pro-demandes',
        '/pro/reservations': 'nav-pro-reservations',
        '/pro/avis': 'nav-pro-avis',
        '/pro/abonnement': 'nav-pro-abonnement',
        '/admin/users': 'nav-admin-users',
        '/admin/roles': 'nav-admin-users',
        '/admin/provider-profiles': 'nav-admin-moderation',
        '/admin/plans': 'nav-admin-plans',
        '/admin/formules': 'nav-admin-plans',
        '/admin/abonnements': 'nav-admin-plans',
        '/admin/journal': 'nav-admin-audit',
    };
    return map[href];
}
</script>

<template>
    <SidebarGroup v-for="group in groups" :key="group.title" class="px-2 py-1">
        <SidebarGroupLabel class="mb-1 px-3 text-[10px] font-semibold uppercase tracking-[0.14em] text-sidebar-foreground/50">
            {{ group.title }}
        </SidebarGroupLabel>

        <SidebarMenu class="gap-0.5">
            <SidebarMenuItem v-for="item in group.items" :key="item.title">
                <SidebarMenuButton
                    :tooltip="item.title"
                    :is-active="isActive(item.href)"
                    :class="[
                        'relative h-10 rounded-lg px-3 text-[13px] text-sidebar-foreground/75 transition-all duration-200',
                        item.disabled
                            ? 'cursor-not-allowed opacity-55 hover:bg-transparent hover:text-sidebar-foreground/75'
                            : 'hover:bg-sidebar-accent hover:text-sidebar-accent-foreground data-[active=true]:bg-brand-50 data-[active=true]:font-semibold data-[active=true]:text-brand-800 data-[active=true]:ring-1 data-[active=true]:ring-inset data-[active=true]:ring-brand-100 data-[active=true]:before:absolute data-[active=true]:before:left-1 data-[active=true]:before:top-1/2 data-[active=true]:before:h-5 data-[active=true]:before:w-[3px] data-[active=true]:before:-translate-y-1/2 data-[active=true]:before:rounded-full data-[active=true]:before:bg-brand-600 dark:data-[active=true]:bg-brand-950/60 dark:data-[active=true]:text-brand-200 dark:data-[active=true]:ring-brand-900 dark:data-[active=true]:before:bg-brand-400',
                    ]"
                    as-child
                >
                    <button
                        v-if="item.disabled"
                        type="button"
                        disabled
                        class="flex w-full items-center gap-2.5 text-left"
                        :data-tour="tourIdFor(item.href)"
                        :aria-label="`${item.title} (bientôt disponible)`"
                    >
                        <component :is="item.icon" class="size-[18px]" aria-hidden="true" />
                        <span class="truncate">{{ item.title }}</span>
                    </button>

                    <a
                        v-else-if="item.external"
                        :href="item.href"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex w-full items-center gap-2.5"
                        :data-tour="tourIdFor(item.href)"
                        @click="onNavigate"
                    >
                        <component :is="item.icon" class="size-[18px]" aria-hidden="true" />
                        <span class="truncate">{{ item.title }}</span>
                    </a>

                    <Link
                        v-else
                        :href="item.href"
                        class="flex w-full items-center gap-2.5"
                        :data-tour="tourIdFor(item.href)"
                        :aria-current="isActive(item.href) ? 'page' : undefined"
                        @click="onNavigate"
                    >
                        <component :is="item.icon" class="size-[18px]" aria-hidden="true" />
                        <span class="truncate">{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>

                <SidebarMenuBadge
                    v-if="item.badge"
                    :class="
                        item.badge === 'Bientôt'
                            ? 'bg-gold-100 text-[9px] uppercase tracking-wide text-gold-800 dark:bg-gold-900/40 dark:text-gold-200'
                            : 'bg-brand-600 text-[10px] text-white'
                    "
                >
                    {{ item.badge }}
                </SidebarMenuBadge>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
