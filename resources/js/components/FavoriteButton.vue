<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Link, router } from '@inertiajs/vue3';
import { Heart, LoaderCircle } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    profileId: number;
    favorited: boolean;
    authenticated: boolean;
}>();

const processing = ref(false);

function toggle() {
    if (!props.authenticated || processing.value) {
        return;
    }

    processing.value = true;

    const options = {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
        },
    };

    if (props.favorited) {
        router.delete(route('favorites.destroy', props.profileId), options);
    } else {
        router.post(route('favorites.store', props.profileId), {}, options);
    }
}
</script>

<template>
    <Button v-if="authenticated" type="button" variant="outline" :disabled="processing" @click="toggle">
        <LoaderCircle v-if="processing" class="size-4 animate-spin" aria-hidden="true" />
        <Heart v-else class="size-4" :class="favorited ? 'fill-current text-brand-600 dark:text-brand-300' : ''" aria-hidden="true" />
        {{ favorited ? 'Retirer des favoris' : 'Ajouter aux favoris' }}
    </Button>

    <Button v-else as-child variant="outline">
        <Link :href="route('login')">
            <Heart class="size-4" aria-hidden="true" />
            Se connecter pour enregistrer
        </Link>
    </Button>
</template>
