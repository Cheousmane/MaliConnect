<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { useAppearance } from '@/composables/useAppearance';
import { Moon, Sun } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref } from 'vue';

const { updateAppearance } = useAppearance();
const isDark = ref(false);
let observer: MutationObserver | null = null;

function syncTheme() {
    isDark.value = document.documentElement.classList.contains('dark');
}

onMounted(() => {
    syncTheme();

    // Suit les changements de thème, y compris lorsque le système bascule automatiquement.
    observer = new MutationObserver(syncTheme);
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});

onUnmounted(() => {
    observer?.disconnect();
});

function toggleTheme() {
    updateAppearance(isDark.value ? 'light' : 'dark');
}
</script>

<template>
    <Button
        variant="ghost"
        size="icon"
        class="relative overflow-hidden text-muted-foreground"
        aria-label="Basculer le thème clair ou sombre"
        @click="toggleTheme"
    >
        <Sun
            class="absolute size-[18px] transition-all duration-500"
            :class="isDark ? 'rotate-90 scale-0 opacity-0' : 'rotate-0 scale-100 opacity-100'"
            aria-hidden="true"
        />
        <Moon
            class="absolute size-[18px] transition-all duration-500"
            :class="isDark ? 'rotate-0 scale-100 opacity-100' : '-rotate-90 scale-0 opacity-0'"
            aria-hidden="true"
        />
    </Button>
</template>
