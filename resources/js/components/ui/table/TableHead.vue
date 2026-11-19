<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <th
        :class="
            cn(
                'h-11 whitespace-nowrap px-4 text-left align-middle text-[11px] font-semibold uppercase tracking-[0.08em] text-muted-foreground',
                props.class,
            )
        "
    >
        <slot />
    </th>
</template>
