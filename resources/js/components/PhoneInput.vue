<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { DEFAULT_COUNTRY_CODE, PHONE_COUNTRIES, type PhoneCountry } from '@/lib/identity';
import { ChevronDown } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    id: string;
    modelValue?: string;
    placeholder?: string;
    invalid?: boolean;
    countries?: PhoneCountry[];
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    placeholder: '76 12 34 56',
    invalid: false,
    countries: () => PHONE_COUNTRIES,
});

const emit = defineEmits<{
    (event: 'update:modelValue', value: string): void;
}>();

// Pays sélectionné : seul un pays disponible peut être retenu.
const country = ref<string>(DEFAULT_COUNTRY_CODE);

const selectedCountry = computed(() => props.countries.find((item) => item.code === country.value));

const optionLabel = (item: PhoneCountry): string => `${item.code} ${item.label}${item.available ? '' : ' (bientôt)'}`;
</script>

<template>
    <div class="flex items-stretch">
        <div class="relative shrink-0">
            <select
                :id="`${props.id}-country`"
                v-model="country"
                :aria-label="`Indicatif du pays (${selectedCountry?.label ?? ''})`"
                class="h-11 w-[124px] cursor-pointer appearance-none rounded-l-lg border border-r-0 border-input bg-background py-2 pl-3.5 pr-8 text-sm font-medium text-foreground shadow-[0_1px_2px_0_rgb(16_24_40_/_0.03)] transition-all duration-200 hover:border-muted-foreground/40 focus-visible:border-primary focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-primary/10 disabled:cursor-not-allowed disabled:bg-muted/50 disabled:opacity-60"
            >
                <option v-for="item in props.countries" :key="item.code" :value="item.code" :disabled="!item.available">
                    {{ optionLabel(item) }}
                </option>
            </select>

            <ChevronDown class="pointer-events-none absolute right-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
        </div>

        <Input
            :id="props.id"
            :model-value="props.modelValue"
            type="tel"
            autocomplete="tel"
            inputmode="tel"
            :placeholder="props.placeholder"
            :aria-invalid="props.invalid"
            class="rounded-l-none border-l-0 pl-3.5"
            @update:model-value="(value) => emit('update:modelValue', String(value))"
        />
    </div>
</template>
