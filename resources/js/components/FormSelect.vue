<script setup lang="ts">
import { cn } from '@/lib/utils';
import { ChevronDown } from 'lucide-vue-next';
import type { HTMLAttributes } from 'vue';

interface Option {
    label: string;
    value: string;
    disabled?: boolean;
}

interface Props {
    options: Option[];
    placeholder?: string;
    class?: HTMLAttributes['class'];
    disabled?: boolean;
    id?: string;
}

defineProps<Props>();

const model = defineModel<string>({ default: '' });
</script>

<template>
    <div class="relative">
        <select
            :id="$props.id"
            v-model="model"
            :disabled="$props.disabled"
            :class="
                cn(
                    'flex h-11 w-full appearance-none rounded-lg border border-input bg-background px-3.5 pr-10 text-base text-foreground shadow-[0_1px_2px_0_rgb(16_24_40_/_0.03)] transition-all duration-200 hover:border-muted-foreground/40 focus-visible:border-primary focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-primary/10 disabled:cursor-not-allowed disabled:bg-muted/50 disabled:opacity-60 md:text-sm',
                    $props.class,
                )
            "
        >
            <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
            <option v-for="option in options" :key="option.value" :value="option.value" :disabled="option.disabled">
                {{ option.label }}
            </option>
        </select>

        <ChevronDown class="pointer-events-none absolute right-3.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
    </div>
</template>
