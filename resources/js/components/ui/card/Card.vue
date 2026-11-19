<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <div :class="cn('rounded-xl border border-border/80 bg-card text-card-foreground shadow-soft', props.class)">
        <slot />
    </div>
</template>
