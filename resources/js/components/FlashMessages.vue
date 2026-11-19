<script setup lang="ts">
import { Button } from '@/components/ui/button';
import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { AlertCircle, CheckCircle2, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const page = usePage<SharedData>();

const success = ref<string | null>(null);
const error = ref<string | null>(null);
let timer: ReturnType<typeof setTimeout> | null = null;

function scheduleDismiss() {
    if (timer) {
        clearTimeout(timer);
    }

    if (!success.value && !error.value) {
        return;
    }

    timer = setTimeout(() => {
        success.value = null;
        error.value = null;
    }, 6000);
}

watch(
    () => page.props.flash,
    (flash) => {
        success.value = flash?.success ?? null;
        error.value = flash?.error ?? null;
        scheduleDismiss();
    },
    { immediate: true, deep: true },
);

function dismiss() {
    success.value = null;
    error.value = null;
}

const hasMessage = computed(() => Boolean(success.value || error.value));
</script>

<template>
    <Transition
        enter-active-class="transition duration-300 ease-smooth"
        enter-from-class="translate-y-2 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="hasMessage" class="fixed right-4 top-20 z-50 w-[min(24rem,calc(100vw-2rem))]" role="status" aria-live="polite">
            <div
                v-if="success"
                class="flex items-start gap-3 rounded-xl border border-success/20 bg-card p-3.5 shadow-elevated ring-1 ring-success/10"
            >
                <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-success/10 text-success">
                    <CheckCircle2 class="size-4" aria-hidden="true" />
                </span>
                <p class="min-w-0 flex-1 pt-1 text-sm text-foreground">{{ success }}</p>
                <Button variant="ghost" size="icon-sm" class="shrink-0 text-muted-foreground" aria-label="Fermer" @click="dismiss">
                    <X class="size-3.5" aria-hidden="true" />
                </Button>
            </div>

            <div
                v-else-if="error"
                class="flex items-start gap-3 rounded-xl border border-destructive/20 bg-card p-3.5 shadow-elevated ring-1 ring-destructive/10"
            >
                <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-destructive/10 text-destructive">
                    <AlertCircle class="size-4" aria-hidden="true" />
                </span>
                <p class="min-w-0 flex-1 pt-1 text-sm text-foreground">{{ error }}</p>
                <Button variant="ghost" size="icon-sm" class="shrink-0 text-muted-foreground" aria-label="Fermer" @click="dismiss">
                    <X class="size-3.5" aria-hidden="true" />
                </Button>
            </div>
        </div>
    </Transition>
</template>
