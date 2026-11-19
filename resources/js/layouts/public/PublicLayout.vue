<script setup lang="ts">
import FlashMessages from '@/components/FlashMessages.vue';
import PublicFooter from '@/components/public/PublicFooter.vue';
import PublicHeader from '@/components/public/PublicHeader.vue';
</script>

<template>
    <div class="flex min-h-svh flex-col overflow-x-clip bg-background">
        <PublicHeader />

        <main class="flex-1">
            <slot />
        </main>

        <PublicFooter />

        <FlashMessages />
    </div>
</template>
