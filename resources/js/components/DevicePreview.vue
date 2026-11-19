<script setup lang="ts">
import { cn } from '@/lib/utils';
import { BatteryFull, Laptop, Signal, Smartphone, Wifi } from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

type Device = 'mobile' | 'desktop';

const STATUS_BAR_HEIGHT = 30;

const device = ref<Device>('mobile');
const screenRef = ref<HTMLElement | null>(null);
const iframeRef = ref<HTMLIFrameElement | null>(null);
const scale = ref(1);

let resizeObserver: ResizeObserver | null = null;

const dimensions = computed(() => (device.value === 'mobile' ? { width: 390, height: 844 } : { width: 1280, height: 800 }));

const isMobile = computed(() => device.value === 'mobile');

const previewUrl = computed(() => {
    if (typeof window === 'undefined') {
        return '/?preview=1';
    }

    const url = new URL(window.location.href);
    url.search = '';
    url.hash = '';
    url.searchParams.set('preview', '1');

    return url.toString();
});

const screenStyle = computed(() => ({
    aspectRatio: `${dimensions.value.width} / ${dimensions.value.height}`,
}));

const viewportStyle = computed(() => ({
    width: `${dimensions.value.width}px`,
    height: `${dimensions.value.height}px`,
    transform: `scale(${scale.value})`,
}));

const iframeStyle = computed(() => ({
    width: `${dimensions.value.width}px`,
    height: `${dimensions.value.height - (isMobile.value ? STATUS_BAR_HEIGHT : 0)}px`,
}));

function updateScale(): void {
    if (!screenRef.value) {
        return;
    }

    scale.value = screenRef.value.clientWidth / dimensions.value.width;
}

function observeScreen(): void {
    if (!resizeObserver || !screenRef.value) {
        return;
    }

    resizeObserver.disconnect();
    resizeObserver.observe(screenRef.value);
    updateScale();
}

function handleFrameLoad(): void {
    const doc = iframeRef.value?.contentDocument;

    if (!doc) {
        return;
    }

    // Masque les barres de défilement pour conserver l'aspect d'un vrai écran mobile.
    const style = doc.createElement('style');
    style.textContent = 'html{scrollbar-width:none}::-webkit-scrollbar{display:none}';
    doc.head.appendChild(style);
}

onMounted(() => {
    if (typeof ResizeObserver !== 'undefined') {
        resizeObserver = new ResizeObserver(updateScale);
        observeScreen();
    } else {
        updateScale();
    }
});

watch(device, async () => {
    await nextTick();
    observeScreen();
});

onBeforeUnmount(() => {
    resizeObserver?.disconnect();
});
</script>

<template>
    <div class="flex flex-col items-center gap-5">
        <div
            class="inline-flex items-center rounded-full border border-border/70 bg-muted/60 p-1"
            role="group"
            aria-label="Choisir le format de l’aperçu"
        >
            <button
                type="button"
                :aria-pressed="isMobile"
                :class="
                    cn(
                        'inline-flex items-center gap-2 rounded-full px-3.5 py-2 text-xs font-semibold transition-colors sm:px-4',
                        isMobile ? 'bg-background text-foreground shadow-soft' : 'text-muted-foreground hover:text-foreground',
                    )
                "
                @click="device = 'mobile'"
            >
                <Smartphone class="size-3.5" aria-hidden="true" />
                Mobile
            </button>
            <button
                type="button"
                :aria-pressed="!isMobile"
                :class="
                    cn(
                        'inline-flex items-center gap-2 rounded-full px-3.5 py-2 text-xs font-semibold transition-colors sm:px-4',
                        !isMobile ? 'bg-background text-foreground shadow-soft' : 'text-muted-foreground hover:text-foreground',
                    )
                "
                @click="device = 'desktop'"
            >
                <Laptop class="size-3.5" aria-hidden="true" />
                Ordinateur
            </button>
        </div>

        <div class="w-full" :class="isMobile ? 'max-w-[340px] sm:max-w-[360px]' : 'max-w-4xl'">
            <div
                :class="
                    isMobile
                        ? 'relative mx-auto rounded-[2.6rem] border-[7px] border-neutral-900 bg-neutral-900 p-1 shadow-xl ring-1 ring-black/10 dark:border-neutral-800 dark:bg-neutral-800'
                        : 'overflow-hidden rounded-xl border border-border/80 bg-card shadow-elevated'
                "
            >
                <div v-if="!isMobile" class="flex items-center gap-2 border-b border-border/70 bg-muted/40 px-4 py-3">
                    <span class="size-2.5 rounded-full bg-destructive/60" aria-hidden="true" />
                    <span class="size-2.5 rounded-full bg-warning/60" aria-hidden="true" />
                    <span class="size-2.5 rounded-full bg-success/60" aria-hidden="true" />
                    <div
                        class="mx-auto flex h-6 w-full max-w-xs items-center justify-center rounded-md border border-border/60 bg-background/70 text-[10px] text-muted-foreground"
                    >
                        maliconnect.ml
                    </div>
                    <span class="w-[38px] shrink-0" aria-hidden="true" />
                </div>

                <div ref="screenRef" class="relative overflow-hidden bg-background" :class="isMobile ? 'rounded-[2rem]' : ''" :style="screenStyle">
                    <div class="absolute left-0 top-0 origin-top-left" :style="viewportStyle">
                        <div
                            v-if="isMobile"
                            class="relative z-20 flex h-[30px] items-center justify-between bg-background px-5 text-[11px] font-semibold text-foreground"
                            aria-hidden="true"
                        >
                            <span>9:41</span>
                            <div class="flex items-center gap-1.5">
                                <Signal class="size-3" />
                                <Wifi class="size-3" />
                                <BatteryFull class="size-4" />
                            </div>
                        </div>

                        <div
                            v-if="isMobile"
                            class="absolute left-1/2 top-2 z-30 h-5 w-16 -translate-x-1/2 rounded-full bg-neutral-950"
                            aria-hidden="true"
                        />

                        <iframe
                            ref="iframeRef"
                            :src="previewUrl"
                            title="Aperçu interactif de l’interface MaliConnect"
                            class="block border-0"
                            :style="iframeStyle"
                            loading="lazy"
                            @load="handleFrameLoad"
                        />
                    </div>
                </div>
            </div>
        </div>

        <p class="max-w-md text-center text-xs leading-relaxed text-muted-foreground">
            Aperçu interactif de l’interface réelle. Faites défiler à l’intérieur de l’écran.
            <a
                :href="previewUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="font-medium text-brand-700 underline-offset-4 hover:underline dark:text-brand-300"
            >
                Ouvrir en plein écran
            </a>
        </p>
    </div>
</template>
