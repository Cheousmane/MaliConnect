<script setup lang="ts">
import PlanCard from '@/components/public/landing/PlanCard.vue';
import type { BillingCycle, LandingPlan } from '@/types/landing';
import { computed, ref } from 'vue';

const props = defineProps<{
    plans: LandingPlan[];
}>();

const cycle = ref<BillingCycle>('monthly');

/**
 * Nombre de mois offerts par la facturation annuelle, calculé à partir des
 * tarifs réellement configurés (et non d'une valeur codée en dur).
 */
const freeMonths = computed(() => {
    const discounts = props.plans
        .filter((plan) => plan.price_monthly > 0 && plan.price_yearly > 0)
        .map((plan) => (plan.price_monthly * 12 - plan.price_yearly) / plan.price_monthly);

    if (!discounts.length) {
        return 0;
    }

    return Math.max(0, Math.round(Math.min(...discounts)));
});
</script>

<template>
    <div v-if="plans.length">
        <div class="flex flex-col items-center gap-6">
            <div class="inline-flex items-center rounded-full border border-border/70 bg-card p-1 shadow-soft" role="group" aria-label="Périodicité de facturation">
                <button
                    type="button"
                    class="rounded-full px-4 py-2 text-xs font-semibold transition-all duration-300"
                    :class="cycle === 'monthly' ? 'bg-brand-600 text-white shadow-soft' : 'text-muted-foreground hover:text-foreground'"
                    :aria-pressed="cycle === 'monthly'"
                    @click="cycle = 'monthly'"
                >
                    Mensuel
                </button>
                <button
                    type="button"
                    class="rounded-full px-4 py-2 text-xs font-semibold transition-all duration-300"
                    :class="cycle === 'yearly' ? 'bg-brand-600 text-white shadow-soft' : 'text-muted-foreground hover:text-foreground'"
                    :aria-pressed="cycle === 'yearly'"
                    @click="cycle = 'yearly'"
                >
                    Annuel
                    <span v-if="freeMonths > 0" class="ml-1 text-[10px] font-medium opacity-80">{{ freeMonths }} mois offerts</span>
                </button>
            </div>
        </div>

        <div class="mt-10 grid gap-5 md:grid-cols-3">
            <PlanCard v-for="plan in plans" :key="plan.id" v-reveal="Math.min(plans.indexOf(plan), 2) * 80" :plan="plan" :cycle="cycle" />
        </div>

        <p class="mt-8 text-center text-xs leading-relaxed text-muted-foreground">
            Les formules payantes incluent un essai gratuit. Vous pouvez changer de formule à tout moment, sans engagement.
        </p>
    </div>
</template>
