<script setup lang="ts">
import { useGuidedTour } from '@/composables/useGuidedTour';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const {
    open,
    showWelcome,
    showResume,
    tour,
    steps,
    activeIndex,
    currentStep,
    progressLabel,
    resolving,
    begin,
    closeWelcomeOnly,
    resume,
    dismissResume,
    next,
    prev,
    skip,
    finish,
    restart,
} = useGuidedTour();

const targetRect = ref<DOMRect | null>(null);
const dialogRef = ref<HTMLElement | null>(null);
let raf = 0;
let lastFocus: HTMLElement | null = null;

const updateRect = () => {
    const step = currentStep.value;
    const selectors = [step?.selector, ...(step?.selectors ?? [])].filter(Boolean) as string[];
    for (const selector of selectors) {
        const el = document.querySelector(selector);
        if (el) {
            targetRect.value = el.getBoundingClientRect();
            return;
        }
    }
    targetRect.value = null;
};

const loop = () => {
    if (open.value && !showWelcome.value && !showResume.value) updateRect();
    raf = window.requestAnimationFrame(loop);
};

const focusDialog = async () => {
    await nextTick();
    const el = dialogRef.value?.querySelector<HTMLElement>('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
    el?.focus();
};

const onKeydown = (e: KeyboardEvent) => {
    if (!(open.value || showResume.value)) return;
    if (e.key === 'Escape') {
        e.preventDefault();
        if (showWelcome.value) void closeWelcomeOnly();
        else if (showResume.value) void dismissResume();
        else void skip();
    }
    if (open.value && !showWelcome.value && !showResume.value) {
        if (e.key === 'ArrowRight') { e.preventDefault(); void next(); }
        if (e.key === 'ArrowLeft') { e.preventDefault(); void prev(); }
    }
};

onMounted(() => {
    raf = window.requestAnimationFrame(loop);
    window.addEventListener('resize', updateRect);
    window.addEventListener('scroll', updateRect, true);
    window.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
    window.cancelAnimationFrame(raf);
    window.removeEventListener('resize', updateRect);
    window.removeEventListener('scroll', updateRect, true);
    window.removeEventListener('keydown', onKeydown);
});

watch([currentStep, open, showWelcome, showResume], async () => {
    await nextTick();
    updateRect();
    if (open.value || showResume.value) {
        if (!lastFocus && document.activeElement instanceof HTMLElement) lastFocus = document.activeElement;
        await focusDialog();
    } else if (lastFocus) {
        lastFocus.focus();
        lastFocus = null;
    }
});

const tooltipStyle = computed(() => {
    const rect = targetRect.value;
    const placement = currentStep.value?.placement ?? 'bottom';
    const margin = 12;
    const width = 340;
    const vw = window.innerWidth;
    const vh = window.innerHeight;
    const mobile = vw < 640;

    if (mobile) {
        return {
            left: '0.75rem',
            right: '0.75rem',
            bottom: '0.75rem',
            width: 'auto',
        } as Record<string, string>;
    }

    if (!rect) {
        return {
            top: '50%',
            left: '50%',
            transform: 'translate(-50%, -50%)',
            width: `min(${width}px, calc(100vw - 2rem))`,
        } as Record<string, string>;
    }

    let top = rect.bottom + margin;
    let left = rect.left + rect.width / 2 - width / 2;

    if (placement === 'top') top = rect.top - margin - 230;
    else if (placement === 'left') { top = rect.top; left = rect.left - width - margin; }
    else if (placement === 'right') { top = rect.top; left = rect.right + margin; }
    else if (placement === 'center') {
        return { top: '50%', left: '50%', transform: 'translate(-50%, -50%)', width: `min(${width}px, calc(100vw - 2rem))` } as Record<string, string>;
    }

    if (left < 12) left = 12;
    if (left + width > vw - 12) left = vw - width - 12;
    if (top < 12) top = 12;
    if (top > vh - 280) top = Math.max(12, vh - 280);

    return { top: `${top}px`, left: `${left}px`, width: `min(${width}px, calc(100vw - 2rem))` } as Record<string, string>;
});

const highlightStyle = computed(() => {
    const rect = targetRect.value;
    if (!rect) return null;
    const pad = 6;
    return {
        top: `${Math.max(rect.top - pad, 8)}px`,
        left: `${Math.max(rect.left - pad, 8)}px`,
        width: `${rect.width + pad * 2}px`,
        height: `${rect.height + pad * 2}px`,
    } as Record<string, string>;
});

const progressPercent = computed(() => {
    if (!steps.value.length) return 0;
    return Math.round(((activeIndex.value + 1) / steps.value.length) * 100);
});

const reduceMotion = computed(() => window.matchMedia('(prefers-reduced-motion: reduce)').matches);
</script>

<template>
    <Teleport to="body">
        <div v-if="open || showResume" class="fixed inset-0 z-[120]" aria-live="polite">
            <div class="absolute inset-0 bg-black/45" @click="showWelcome ? closeWelcomeOnly() : showResume ? dismissResume() : undefined" />

            <div
                v-if="open && !showWelcome && !showResume && highlightStyle"
                class="pointer-events-none absolute rounded-xl border-2 border-brand-500 shadow-[0_0_0_9999px_rgba(15,23,42,0.45)]"
                :class="reduceMotion ? '' : 'transition-all duration-200'"
                :style="highlightStyle"
            />

            <div
                v-if="showResume"
                ref="dialogRef"
                role="dialog"
                aria-modal="true"
                aria-label="Reprendre la visite guidée"
                class="absolute left-1/2 top-1/2 w-[min(30rem,calc(100vw-2rem))] -translate-x-1/2 -translate-y-1/2 rounded-2xl border border-border bg-card p-6 shadow-2xl"
            >
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-brand-600 dark:text-brand-400">Visite guidée</p>
                <h2 class="mt-2 font-display text-xl font-semibold text-foreground">Reprendre la visite ?</h2>
                <p class="mt-2 text-sm leading-relaxed text-muted-foreground">
                    Vous aviez une visite en cours pour votre espace {{ tour?.label?.toLowerCase() ?? '' }}. Vous pouvez la reprendre ou la laisser de côté.
                </p>
                <div class="mt-5 flex flex-wrap justify-end gap-2">
                    <button type="button" class="inline-flex h-10 items-center rounded-lg border border-border px-4 text-sm font-medium text-foreground hover:bg-muted" @click="dismissResume">Plus tard</button>
                    <button type="button" class="inline-flex h-10 items-center rounded-lg bg-brand-600 px-4 text-sm font-medium text-white hover:bg-brand-700" @click="resume">Reprendre</button>
                </div>
            </div>

            <div
                v-else-if="showWelcome"
                ref="dialogRef"
                role="dialog"
                aria-modal="true"
                aria-label="Bienvenue dans la visite guidée"
                class="absolute left-1/2 top-1/2 w-[min(32rem,calc(100vw-2rem))] -translate-x-1/2 -translate-y-1/2 rounded-2xl border border-border bg-card p-6 shadow-2xl"
            >
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-brand-600 dark:text-brand-400">Visite guidée</p>
                <h2 class="mt-2 font-display text-2xl font-semibold tracking-tight text-foreground">{{ tour?.welcomeTitle }}</h2>
                <p class="mt-2 text-sm leading-relaxed text-muted-foreground">{{ tour?.welcomeDescription }}</p>
                <div class="mt-5 flex flex-wrap justify-end gap-2">
                    <button type="button" class="inline-flex h-10 items-center rounded-lg border border-border px-4 text-sm font-medium text-foreground hover:bg-muted" @click="closeWelcomeOnly">Passer</button>
                    <button type="button" class="inline-flex h-10 items-center rounded-lg bg-brand-600 px-4 text-sm font-medium text-white hover:bg-brand-700" @click="begin">Commencer la visite</button>
                </div>
            </div>

            <div
                v-else-if="currentStep"
                ref="dialogRef"
                role="dialog"
                aria-modal="true"
                :aria-label="currentStep.title"
                :aria-describedby="`tour-step-${currentStep.id}`"
                class="absolute rounded-2xl border border-border bg-card p-4 shadow-2xl"
                :style="tooltipStyle"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-brand-600 dark:text-brand-400">{{ progressLabel }}</p>
                        <h3 class="mt-1 font-display text-lg font-semibold text-foreground">{{ currentStep.title }}</h3>
                    </div>
                    <button type="button" class="rounded-md px-2 py-1 text-xs text-muted-foreground hover:bg-muted hover:text-foreground" @click="skip">Ignorer</button>
                </div>

                <p :id="`tour-step-${currentStep.id}`" class="mt-2 text-sm leading-relaxed text-muted-foreground">{{ currentStep.content }}</p>

                <div class="mt-4" aria-hidden="true">
                    <div class="h-1.5 overflow-hidden rounded-full bg-muted">
                        <div class="h-full rounded-full bg-brand-600" :class="reduceMotion ? '' : 'transition-[width] duration-300'" :style="{ width: `${progressPercent}%` }" />
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between gap-2">
                    <button
                        type="button"
                        class="inline-flex h-9 items-center rounded-lg border border-border px-3 text-sm font-medium text-foreground hover:bg-muted disabled:opacity-50"
                        :disabled="activeIndex === 0 || resolving"
                        @click="prev"
                    >
                        Précédent
                    </button>
                    <div class="flex items-center gap-2">
                        <button type="button" class="inline-flex h-9 items-center rounded-lg px-3 text-sm font-medium text-muted-foreground hover:bg-muted hover:text-foreground" @click="restart">Recommencer</button>
                        <button
                            type="button"
                            class="inline-flex h-9 items-center rounded-lg bg-brand-600 px-3 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
                            :disabled="resolving"
                            @click="activeIndex === steps.length - 1 ? finish() : next()"
                        >
                            {{ activeIndex === steps.length - 1 ? 'Terminer' : 'Suivant' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
