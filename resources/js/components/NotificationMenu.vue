<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import type { NotificationItem, SharedData } from '@/types';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    Bell,
    BellOff,
    CalendarCheck,
    CalendarPlus,
    CalendarX,
    CheckCheck,
    CheckCircle2,
    ClipboardList,
    MessageSquare,
    Star,
    Wallet,
    XCircle,
    type LucideIcon,
} from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage<SharedData>();

const items = computed<NotificationItem[]>(() => page.props.notificationsPreview ?? []);
const unreadCount = computed(() => page.props.unreadNotificationsCount ?? 0);

const toneClasses: Record<string, string> = {
    neutral: 'bg-muted text-muted-foreground',
    brand: 'bg-brand-50 text-brand-600 dark:bg-brand-950/60 dark:text-brand-300',
    success: 'bg-success/10 text-success',
    warning: 'bg-warning/10 text-amber-600 dark:text-amber-300',
    danger: 'bg-destructive/10 text-destructive',
    info: 'bg-info/10 text-info',
};

const iconMap: Record<string, LucideIcon> = {
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

function resolveIcon(icon?: string): LucideIcon {
    return iconMap[icon ?? 'bell'] ?? Bell;
}

function markAllAsRead() {
    router.post(route('notifications.read-all'), {}, { preserveScroll: true });
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                variant="ghost"
                size="icon"
                class="relative text-muted-foreground"
                :aria-label="`Notifications (${unreadCount} non lues)`"
                data-tour="header-notifications"
            >
                <Bell class="size-[18px]" aria-hidden="true" />
                <span
                    v-if="unreadCount > 0"
                    class="absolute right-2 top-2 flex size-2 rounded-full bg-gold-500 ring-2 ring-background"
                    aria-hidden="true"
                />
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" class="w-[min(22rem,calc(100vw-2rem))] overflow-hidden p-0">
            <div class="flex items-center justify-between gap-3 px-4 py-3">
                <div>
                    <p class="font-display text-sm font-semibold text-foreground">Notifications</p>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        {{ unreadCount > 0 ? `${unreadCount} non lue${unreadCount > 1 ? 's' : ''}` : 'Tout est à jour' }}
                    </p>
                </div>
                <Button
                    v-if="unreadCount > 0"
                    variant="ghost"
                    size="sm"
                    class="h-8 gap-1.5 px-2 text-xs text-brand-700 dark:text-brand-300"
                    @click="markAllAsRead"
                >
                    <CheckCheck class="size-3.5" aria-hidden="true" />
                    Tout lire
                </Button>
            </div>

            <DropdownMenuSeparator class="my-0" />

            <div v-if="items.length > 0" class="max-h-[min(24rem,60vh)] overflow-y-auto p-1.5">
                <Link
                    v-for="(notification, index) in items"
                    :key="notification.id"
                    :href="route('notifications.read', notification.id)"
                    method="post"
                    as="button"
                    preserve-scroll
                    class="flex w-full gap-3 rounded-lg px-2.5 py-3 text-left transition-colors hover:bg-muted/60"
                    :class="!notification.read ? 'bg-brand-50/40 dark:bg-brand-950/20' : ''"
                >
                    <span class="mt-2 w-4 shrink-0 text-right text-[10px] font-semibold text-muted-foreground/60" aria-hidden="true">
                        {{ index + 1 }}
                    </span>
                    <div :class="['flex size-9 shrink-0 items-center justify-center rounded-lg', toneClasses[notification.tone ?? 'neutral']]">
                        <component :is="resolveIcon(notification.icon)" class="size-4" aria-hidden="true" />
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-[13px] font-medium leading-snug text-foreground">{{ notification.title }}</p>
                            <span v-if="!notification.read" class="mt-1.5 size-1.5 shrink-0 rounded-full bg-brand-500" aria-label="Non lue" />
                        </div>
                        <p v-if="notification.description" class="mt-0.5 line-clamp-2 text-xs leading-relaxed text-muted-foreground">
                            {{ notification.description }}
                        </p>
                        <p class="mt-1 text-[11px] text-muted-foreground/80">{{ notification.time }}</p>
                    </div>
                </Link>
            </div>

            <EmptyState
                v-else
                title="Aucune notification"
                description="Vos prochaines alertes apparaîtront ici."
                :icon="BellOff"
                compact
                class="m-2 border-0"
            />

            <DropdownMenuSeparator class="my-0" />
            <div class="p-1.5">
                <Button as-child variant="ghost" size="sm" class="w-full justify-center text-xs text-muted-foreground">
                    <Link :href="route('notifications.index')">Voir toutes les notifications</Link>
                </Button>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
