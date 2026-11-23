<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Link, router } from '@inertiajs/vue3';
import { ChevronDown, LoaderCircle, MapPin, Search } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref, useId } from 'vue';

type Suggestion = {
    type: string;
    label: string;
    url: string;
};

const props = withDefaults(
    defineProps<{
        locations: string[];
        categories: Array<{ name: string; slug: string }>;
        initialQuery?: string;
    }>(),
    { initialQuery: '', locations: () => [], categories: () => [] },
);

const uid = useId();

const keyword = ref(props.initialQuery);
const location = ref('');

const suggestions = ref<Suggestion[]>([]);
const suggesting = ref(false);
const open = ref(false);
const activeIndex = ref(-1);

let timer: ReturnType<typeof setTimeout> | null = null;
let controller: AbortController | null = null;

const listId = `${uid}-suggestions`;
const showSuggestions = computed(() => open.value && suggestions.value.length > 0);

const optionId = (index: number) => `${uid}-option-${index}`;
const activeOptionId = computed(() => (activeIndex.value >= 0 ? optionId(activeIndex.value) : undefined));

const resetSuggestions = () => {
    suggestions.value = [];
    open.value = false;
    activeIndex.value = -1;
};

const onInput = () => {
    if (timer) {
        clearTimeout(timer);
    }

    controller?.abort();

    const term = keyword.value.trim();

    if (term.length < 2) {
        resetSuggestions();
        suggesting.value = false;

        return;
    }

    timer = setTimeout(async () => {
        controller = new AbortController();
        suggesting.value = true;

        try {
            const response = await fetch(`${route('search.suggestions')}?q=${encodeURIComponent(term)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });

            if (!response.ok) {
                resetSuggestions();

                return;
            }

            const payload = await response.json();

            suggestions.value = Array.isArray(payload?.data) ? payload.data.slice(0, 6) : [];
            open.value = suggestions.value.length > 0;
            activeIndex.value = -1;
        } catch (error) {
            if ((error as Error).name !== 'AbortError') {
                resetSuggestions();
            }
        } finally {
            suggesting.value = false;
        }
    }, 240);
};

const move = (direction: 1 | -1) => {
    if (!suggestions.value.length) {
        return;
    }

    open.value = true;
    const count = suggestions.value.length;
    activeIndex.value = (activeIndex.value + direction + count) % count;
};

const choose = (suggestion: Suggestion) => {
    resetSuggestions();
    router.get(suggestion.url);
};

const submit = () => {
    if (open.value && activeIndex.value >= 0 && suggestions.value[activeIndex.value]) {
        choose(suggestions.value[activeIndex.value]);

        return;
    }

    resetSuggestions();

    const params: Record<string, string> = {};

    if (keyword.value.trim()) {
        params.q = keyword.value.trim();
    }

    if (location.value) {
        params.commune = location.value;
    }

    router.get(route('search'), params);
};

const onBlur = () => {
    // Laisse le temps au clic sur une suggestion d'aboutir.
    window.setTimeout(() => {
        open.value = false;
    }, 140);
};

onBeforeUnmount(() => {
    if (timer) {
        clearTimeout(timer);
    }

    controller?.abort();
});
</script>

<template>
    <form class="relative mt-7 w-full" role="search" @submit.prevent="submit">
        <div
            class="rounded-2xl border border-border/80 bg-card/95 p-2 shadow-elevated backdrop-blur-md transition-shadow duration-300 focus-within:border-brand-300 focus-within:shadow-card dark:focus-within:border-brand-800"
        >
            <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-[minmax(0,1fr)_1px_200px_auto] xl:items-center">
                <!-- Mot-clé + suggestions -->
                <div class="relative">
                    <Search class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />

                    <label :for="`${uid}-keyword`" class="sr-only">Rechercher un service ou un professionnel</label>

                    <input
                        :id="`${uid}-keyword`"
                        v-model="keyword"
                        type="search"
                        role="combobox"
                        aria-autocomplete="list"
                        aria-label="Rechercher un service ou un professionnel"
                        autocomplete="off"
                        :aria-expanded="showSuggestions"
                        :aria-controls="listId"
                        :aria-activedescendant="activeOptionId"
                        placeholder="Plombier, ménage, coiffeur…"
                        class="h-12 w-full rounded-xl border-0 bg-muted/50 pl-10 pr-10 text-[15px] text-foreground placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-brand-500/40 dark:bg-muted/40 xl:bg-transparent xl:shadow-none"
                        @input="onInput"
                        @focus="open = suggestions.length > 0"
                        @blur="onBlur"
                        @keydown.down.prevent="move(1)"
                        @keydown.up.prevent="move(-1)"
                        @keydown.enter.prevent="submit"
                        @keydown.esc="resetSuggestions"
                    />

                    <LoaderCircle
                        v-if="suggesting"
                        class="absolute right-3 top-1/2 size-4 -translate-y-1/2 animate-spin text-muted-foreground"
                        aria-hidden="true"
                    />

                    <ul
                        v-if="showSuggestions"
                        :id="listId"
                        role="listbox"
                        class="absolute inset-x-0 top-[calc(100%+8px)] z-30 max-h-72 overflow-y-auto rounded-xl border border-border/80 bg-popover p-1 shadow-elevated"
                    >
                        <li
                            v-for="(suggestion, index) in suggestions"
                            :id="optionId(index)"
                            :key="`${suggestion.type}-${suggestion.label}`"
                            role="option"
                            :aria-selected="index === activeIndex"
                            class="cursor-pointer rounded-lg px-3 py-2 text-sm text-foreground transition-colors"
                            :class="index === activeIndex ? 'bg-muted' : 'hover:bg-muted'"
                            @mousedown.prevent="choose(suggestion)"
                            @mouseenter="activeIndex = index"
                        >
                            <span class="flex items-center justify-between gap-3">
                                <span class="truncate">{{ suggestion.label }}</span>
                                <span class="shrink-0 text-[10px] uppercase tracking-wide text-muted-foreground">{{ suggestion.type }}</span>
                            </span>
                        </li>
                    </ul>
                </div>

                <div class="hidden h-8 w-px bg-border xl:block" aria-hidden="true" />

                <!-- Localisation -->
                <div class="relative">
                    <MapPin class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />

                    <label :for="`${uid}-location`" class="sr-only">Localisation</label>

                    <select
                        :id="`${uid}-location`"
                        v-model="location"
                        class="h-12 w-full appearance-none rounded-xl border-0 bg-muted/50 pl-10 pr-9 text-sm text-foreground focus-visible:ring-2 focus-visible:ring-brand-500/40 dark:bg-muted/40 xl:bg-transparent xl:shadow-none"
                    >
                        <option value="">Toutes zones</option>
                        <option v-for="place in locations" :key="place" :value="place">{{ place }}</option>
                    </select>

                    <ChevronDown class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
                </div>

                <Button type="submit" variant="brand" size="lg" class="h-12 w-full gap-2 px-6 text-[15px] sm:col-span-2 xl:col-span-1 xl:w-auto">
                    <Search class="size-4" aria-hidden="true" />
                    Rechercher
                </Button>
            </div>
        </div>

        <div v-if="categories.length" class="mt-3 flex flex-wrap items-center gap-x-2 gap-y-2 text-xs">
            <span class="text-muted-foreground">Populaire :</span>

            <Link
                v-for="category in categories.slice(0, 5)"
                :key="category.slug"
                :href="route('categories.show', category.slug)"
                class="rounded-full border border-border/70 bg-background/80 px-2.5 py-1 font-medium text-foreground/80 transition-colors hover:border-brand-200 hover:bg-brand-50 hover:text-brand-800 dark:hover:border-brand-800 dark:hover:bg-brand-950/50 dark:hover:text-brand-200"
            >
                {{ category.name }}
            </Link>
        </div>
    </form>
</template>
