<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogTitle } from '@/components/ui/dialog';
import type { StatusTone } from '@/types';
import { AlertTriangle, CheckCircle2, Info, LoaderCircle, ShieldAlert } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    title: string;
    description?: string;
    confirmLabel?: string;
    cancelLabel?: string;
    tone?: StatusTone;
    processing?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    confirmLabel: 'Confirmer',
    cancelLabel: 'Annuler',
    tone: 'danger',
    processing: false,
});

const emits = defineEmits<{
    (e: 'confirm'): void;
}>();

const open = defineModel<boolean>('open', { default: false });

const toneClasses: Record<StatusTone, string> = {
    neutral: 'bg-muted text-muted-foreground',
    brand: 'bg-brand-50 text-brand-600 dark:bg-brand-950/60 dark:text-brand-300',
    success: 'bg-success/10 text-success',
    warning: 'bg-warning/10 text-amber-600 dark:text-amber-300',
    danger: 'bg-destructive/10 text-destructive',
    info: 'bg-info/10 text-info',
};

const icon = computed(() => {
    if (props.tone === 'success') return CheckCircle2;
    if (props.tone === 'info' || props.tone === 'brand') return Info;
    if (props.tone === 'warning') return AlertTriangle;
    return ShieldAlert;
});
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="max-w-md">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                <div :class="['flex size-11 shrink-0 items-center justify-center rounded-xl', toneClasses[props.tone]]">
                    <component :is="icon" class="size-5" aria-hidden="true" />
                </div>
                <div class="min-w-0">
                    <DialogTitle class="font-display text-lg">{{ title }}</DialogTitle>
                    <DialogDescription v-if="description" class="mt-2 text-sm leading-relaxed">
                        {{ description }}
                    </DialogDescription>
                </div>
            </div>

            <DialogFooter class="mt-2 gap-2 sm:gap-2">
                <Button variant="outline" :disabled="processing" @click="open = false">{{ cancelLabel }}</Button>
                <Button :variant="tone === 'danger' ? 'destructive' : 'default'" :disabled="processing" @click="emits('confirm')">
                    <LoaderCircle v-if="processing" class="size-4 animate-spin" aria-hidden="true" />
                    {{ confirmLabel }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
