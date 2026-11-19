<script setup lang="ts">
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

interface Props {
    label: string;
    for?: string;
    hint?: string;
    error?: string;
    required?: boolean;
    class?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <div :class="cn('grid gap-2', $props.class)">
        <div class="flex items-center justify-between gap-3">
            <Label :for="$props.for" class="text-[13px] font-medium text-foreground">
                {{ label }}
                <span v-if="required" class="text-destructive" aria-hidden="true">*</span>
            </Label>
            <slot name="action" />
        </div>

        <slot />

        <p v-if="error" class="text-xs font-medium text-destructive" role="alert">{{ error }}</p>
        <p v-else-if="hint" class="text-xs leading-relaxed text-muted-foreground">{{ hint }}</p>
    </div>
</template>
