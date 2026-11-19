<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { cn } from '@/lib/utils';

interface Props {
    class?: string;
    showTagline?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showTagline: false,
});
</script>

<template>
    <div :class="cn('flex min-w-0 items-center gap-2.5', props.class)">
        <div
            class="flex size-9 shrink-0 items-center justify-center rounded-[11px] bg-brand-600 text-white shadow-soft ring-1 ring-inset ring-white/20"
        >
            <AppLogoIcon class="size-5" />
        </div>

        <div class="grid min-w-0 leading-none group-data-[collapsible=icon]:hidden">
            <span class="truncate font-display text-[15px] font-semibold tracking-tight text-foreground">
                Mali<span class="text-brand-600 dark:text-brand-400">Connect</span>
            </span>
            <span v-if="showTagline" class="mt-1 truncate text-[10px] font-medium uppercase tracking-[0.16em] text-muted-foreground">
                Le bon pro, au bon moment
            </span>
        </div>
    </div>
</template>
