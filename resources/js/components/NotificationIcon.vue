<script setup lang="ts">
import {
    Bell,
    CalendarCheck,
    CalendarPlus,
    CalendarX,
    CheckCircle2,
    ClipboardList,
    MessageSquare,
    Star,
    Wallet,
    XCircle,
    type LucideIcon,
} from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    name?: string;
}>();

const icons: Record<string, LucideIcon> = {
    bell: Bell,
    'clipboard-list': ClipboardList,
    'message-square': MessageSquare,
    wallet: Wallet,
    'check-circle': CheckCircle2,
    'x-circle': XCircle,
    'calendar-plus': CalendarPlus,
    'calendar-check': CalendarCheck,
    'calendar-x': CalendarX,
    star: Star,
};

const icon = computed(() => icons[props.name ?? 'bell'] ?? Bell);
</script>

<template>
    <component :is="icon" aria-hidden="true" />
</template>
