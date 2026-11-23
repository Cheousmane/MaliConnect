<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import GlobalSearch from '@/components/GlobalSearch.vue';
import NotificationMenu from '@/components/NotificationMenu.vue';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import type { BreadcrumbItemType, NavItem, SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, Inbox, LayoutDashboard, Menu, MessageSquare } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage<SharedData>();
const currentPath = computed(() => page.url.split('?')[0]);

const mainNavItems: NavItem[] = [
    { title: 'Tableau de bord', href: '/dashboard', icon: LayoutDashboard },
    { title: 'Demandes', href: '#', icon: Inbox, disabled: true, badge: 'Bientôt' },
    { title: 'Messages', href: '#', icon: MessageSquare, disabled: true, badge: 'Bientôt' },
];

function isActive(href: string): boolean {
    return currentPath.value === href;
}
</script>

<template>
    <header class="glass-surface sticky top-0 z-40 border-b border-border/60">
        <div class="page-container flex h-16 items-center gap-3">
            <nav class="hidden items-center gap-1 lg:flex" aria-label="Navigation principale">
                <template v-for="item in mainNavItems" :key="item.title">
                    <span
                        v-if="item.disabled"
                        class="flex cursor-not-allowed items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-muted-foreground/60"
                    >
                        <component :is="item.icon" class="size-4" aria-hidden="true" />
                        {{ item.title }}
                        <span
                            class="rounded-full bg-gold-100 px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-wide text-gold-800 dark:bg-gold-900/40 dark:text-gold-200"
                        >
                            {{ item.badge }}
                        </span>
                    </span>

                    <Link
                        v-else
                        :href="item.href"
                        :class="[
                            'flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                            isActive(item.href)
                                ? 'bg-brand-50 text-brand-800 dark:bg-brand-950/60 dark:text-brand-200'
                                : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                        ]"
                    >
                        <component :is="item.icon" class="size-4" aria-hidden="true" />
                        {{ item.title }}
                    </Link>
                </template>
            </nav>

            <div class="ml-auto flex shrink-0 items-center gap-1 sm:gap-1.5">
                <GlobalSearch mode="icon" />

                <NotificationMenu />

                <Sheet>
                    <SheetTrigger as-child>
                        <Button variant="ghost" size="icon" class="lg:hidden" aria-label="Ouvrir le menu">
                            <Menu class="size-5" aria-hidden="true" />
                        </Button>
                    </SheetTrigger>

                    <SheetContent side="left" class="w-[min(19rem,calc(100vw-2rem))] p-0">
                        <SheetTitle class="sr-only">Menu de navigation</SheetTitle>

                        <div class="flex h-full flex-col">
                            <div class="border-b border-border/70 px-5 py-5">
                                <AppLogo show-tagline />
                            </div>

                            <nav class="flex-1 space-y-1 overflow-y-auto p-4" aria-label="Navigation mobile">
                                <template v-for="item in mainNavItems" :key="item.title">
                                    <span
                                        v-if="item.disabled"
                                        class="flex items-center justify-between rounded-lg px-3 py-3 text-sm font-medium text-muted-foreground/60"
                                    >
                                        <span class="flex items-center gap-2.5">
                                            <component :is="item.icon" class="size-4" aria-hidden="true" />
                                            {{ item.title }}
                                        </span>
                                        <span
                                            class="rounded-full bg-gold-100 px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-wide text-gold-800 dark:bg-gold-900/40 dark:text-gold-200"
                                        >
                                            {{ item.badge }}
                                        </span>
                                    </span>

                                    <Link
                                        v-else
                                        :href="item.href"
                                        class="flex items-center justify-between rounded-lg px-3 py-3 text-sm font-medium text-foreground transition-colors hover:bg-muted"
                                    >
                                        <span class="flex items-center gap-2.5">
                                            <component :is="item.icon" class="size-4" aria-hidden="true" />
                                            {{ item.title }}
                                        </span>
                                        <ArrowRight class="size-4 text-muted-foreground" aria-hidden="true" />
                                    </Link>
                                </template>
                            </nav>


                        </div>
                    </SheetContent>
                </Sheet>
            </div>
        </div>

        <div v-if="props.breadcrumbs.length > 0" class="border-t border-border/50">
            <div class="page-container flex h-11 items-center">
                <Breadcrumb>
                    <BreadcrumbList>
                        <template v-for="(item, index) in props.breadcrumbs" :key="index">
                            <BreadcrumbItem>
                                <BreadcrumbPage v-if="index === props.breadcrumbs.length - 1" class="text-xs font-medium">
                                    {{ item.title }}
                                </BreadcrumbPage>
                                <BreadcrumbLink v-else :href="item.href" class="text-xs">
                                    {{ item.title }}
                                </BreadcrumbLink>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator v-if="index !== props.breadcrumbs.length - 1" />
                        </template>
                    </BreadcrumbList>
                </Breadcrumb>
            </div>
        </div>
    </header>
</template>
