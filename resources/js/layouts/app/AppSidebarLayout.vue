<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import FlashMessages from '@/components/FlashMessages.vue';
import GuidedTour from '@/components/GuidedTour.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />

        <AppContent variant="sidebar" class="min-w-0 overflow-x-clip border border-border/60 bg-muted/30 shadow-soft dark:bg-background">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />

            <FlashMessages />

            <div class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <div class="mx-auto w-full max-w-7xl">
                    <slot />
                </div>
            </div>
        </AppContent>
        <GuidedTour />
    </AppShell>
</template>
