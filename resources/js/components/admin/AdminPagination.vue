<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';

interface Props {
    currentPage: number;
    lastPage: number;
    total: number;
    label?: string;
}

withDefaults(defineProps<Props>(), {
    label: 'éléments',
});

// Conserve les filtres existants lors du changement de page.
const pageUrl = (page: number): string => {
    const url = new URL(window.location.href);
    url.searchParams.set('page', String(page));

    return url.toString();
};
</script>

<template>
    <nav v-if="lastPage > 1" class="mt-5 flex flex-wrap items-center justify-between gap-3" :aria-label="`Pagination des ${label}`">
        <p class="text-xs text-muted-foreground">{{ total }} {{ label }} · page {{ currentPage }} sur {{ lastPage }}</p>

        <div class="flex items-center gap-2">
            <Button as-child variant="outline" size="sm" :class="currentPage <= 1 ? 'pointer-events-none opacity-50' : ''">
                <Link :href="pageUrl(currentPage - 1)" :aria-disabled="currentPage <= 1">Précédent</Link>
            </Button>
            <Button as-child variant="outline" size="sm" :class="currentPage >= lastPage ? 'pointer-events-none opacity-50' : ''">
                <Link :href="pageUrl(currentPage + 1)" :aria-disabled="currentPage >= lastPage">Suivant</Link>
            </Button>
        </div>
    </nav>
</template>
