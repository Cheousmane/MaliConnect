<script setup lang="ts">
import { Star } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    modelValue?: number;
    readonly?: boolean;
    size?: 'sm' | 'md' | 'lg';
    showValue?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: 0,
    readonly: false,
    size: 'md',
    showValue: false,
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: number): void;
}>();

const hovered = ref(0);
const displayValue = computed(() => hovered.value || props.modelValue);

const sizeClasses: Record<NonNullable<Props['size']>, string> = {
    sm: 'size-3.5',
    md: 'size-5',
    lg: 'size-7',
};

const labels = ['', 'Très insatisfait', 'Insatisfait', 'Correct', 'Satisfait', 'Excellent'];

const currentLabel = computed(() => labels[props.modelValue] ?? '');

function select(value: number) {
    if (!props.readonly) {
        emit('update:modelValue', value);
    }
}
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <div class="flex items-center gap-0.5" role="group" aria-label="Note sur 5">
            <button
                v-for="star in 5"
                :key="star"
                type="button"
                :disabled="readonly"
                :aria-label="`${star} étoile${star > 1 ? 's' : ''}`"
                :aria-pressed="modelValue === star"
                class="rounded-md p-0.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/60 disabled:cursor-default"
                @click="select(star)"
                @mouseenter="!readonly && (hovered = star)"
                @mouseleave="hovered = 0"
            >
                <Star
                    :class="[
                        sizeClasses[size],
                        star <= displayValue ? 'fill-gold-400 text-gold-500' : 'fill-transparent text-border',
                        readonly ? '' : 'transition-transform duration-150 hover:scale-110',
                    ]"
                    aria-hidden="true"
                />
            </button>
        </div>

        <span v-if="showValue && modelValue > 0" class="text-xs font-medium text-muted-foreground">{{ modelValue }}/5</span>
        <span v-if="!readonly && showValue && currentLabel" class="text-xs text-muted-foreground">{{ currentLabel }}</span>
    </div>
</template>
