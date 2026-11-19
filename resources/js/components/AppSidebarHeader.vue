<script setup lang="ts">
import GlobalSearch from '@/components/GlobalSearch.vue';
import NotificationMenu from '@/components/NotificationMenu.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Separator } from '@/components/ui/separator';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { getInitials } from '@/composables/useInitials';
import type { BreadcrumbItemType, SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
    variant?: 'professional' | 'admin';
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
    variant: 'professional',
});

const page = usePage<SharedData>();
const user = computed(() => page.props.auth.user);
const currentTitle = computed(() => {
    const lastBreadcrumb = props.breadcrumbs[props.breadcrumbs.length - 1];
    return lastBreadcrumb?.title ?? (props.variant === 'admin' ? 'Administration' : 'Espace professionnel');
});
</script>

<template>
    <header class="glass-surface sticky top-0 z-30 flex h-16 shrink-0 items-center gap-2 border-b border-border/60 px-3 sm:px-5 lg:px-6">
        <div class="flex min-w-0 items-center gap-2">
            <SidebarTrigger class="-ml-1 size-9 text-muted-foreground" />

            <Separator orientation="vertical" class="mx-1 hidden h-5 sm:block" />

            <div class="min-w-0">
                <Breadcrumb v-if="breadcrumbs.length > 0" class="hidden sm:block">
                    <BreadcrumbList>
                        <template v-for="(item, index) in breadcrumbs" :key="index">
                            <BreadcrumbItem>
                                <BreadcrumbPage v-if="index === breadcrumbs.length - 1" class="text-[13px] font-medium">
                                    {{ item.title }}
                                </BreadcrumbPage>
                                <BreadcrumbLink v-else :href="item.href" class="text-[13px]">
                                    {{ item.title }}
                                </BreadcrumbLink>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator v-if="index !== breadcrumbs.length - 1" />
                        </template>
                    </BreadcrumbList>
                </Breadcrumb>

                <p class="truncate text-sm font-semibold text-foreground sm:hidden">{{ currentTitle }}</p>
            </div>

            <StatusBadge v-if="variant === 'admin'" tone="brand" label="Administration" class="ml-1 hidden md:inline-flex" />
        </div>

        <div class="ml-auto flex shrink-0 items-center gap-1 sm:gap-1.5">
            <GlobalSearch class="hidden lg:flex" />
            <GlobalSearch mode="icon" class="lg:hidden" />
            <ThemeToggle class="hidden sm:inline-flex" />
            <NotificationMenu />

            <Separator orientation="vertical" class="mx-1 hidden h-5 sm:block" />

            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="ghost" size="icon" class="rounded-full p-0.5" aria-label="Ouvrir le menu du compte">
                        <Avatar class="size-8 overflow-hidden rounded-full ring-1 ring-border/80">
                            <AvatarImage :src="user.avatar ?? ''" :alt="user.name" />
                            <AvatarFallback
                                class="rounded-full bg-brand-50 text-[11px] font-semibold text-brand-700 dark:bg-brand-950 dark:text-brand-300"
                            >
                                {{ getInitials(user.name) }}
                            </AvatarFallback>
                        </Avatar>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-60">
                    <UserMenuContent :user="user" />
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </header>
</template>
