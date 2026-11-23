<script setup lang="ts">
import GlobalSearch from '@/components/GlobalSearch.vue';
import NotificationMenu from '@/components/NotificationMenu.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { Separator } from '@/components/ui/separator';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItemType } from '@/types';
import { computed } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
    variant?: 'professional' | 'admin';
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
    variant: 'professional',
});

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
            <GlobalSearch mode="icon" />

            <NotificationMenu />
        </div>
    </header>
</template>
