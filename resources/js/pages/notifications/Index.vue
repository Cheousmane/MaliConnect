<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import NotificationIcon from '@/components/NotificationIcon.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, NotificationItem, StatusTone } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { BellOff, CheckCheck, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

type Props = {
    notifications: {
        data: NotificationItem[];
        current_page: number;
        last_page: number;
        total: number;
    };
    unreadCount: number;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Notifications', href: '/notifications' }];

const processing = ref(false);

const toneClasses: Record<string, string> = {
    neutral: 'bg-muted text-muted-foreground',
    brand: 'bg-brand-50 text-brand-600 dark:bg-brand-950/60 dark:text-brand-300',
    success: 'bg-success/10 text-success',
    warning: 'bg-warning/10 text-amber-600 dark:text-amber-300',
    danger: 'bg-destructive/10 text-destructive',
    info: 'bg-info/10 text-info',
};

const tone = (value?: StatusTone): StatusTone => value ?? 'neutral';

const markAllAsRead = () => {
    processing.value = true;

    router.post(
        route('notifications.read-all'),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                processing.value = false;
            },
        },
    );
};

const remove = (notification: NotificationItem) => {
    router.delete(route('notifications.destroy', notification.id), { preserveScroll: true });
};

const pageUrl = (page: number): string => route('notifications.index', { page });
</script>

<template>
    <Head title="Notifications" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader
                eyebrow="Centre de notifications"
                title="Notifications"
                description="Suivez les demandes, devis, réservations et avis qui concernent votre activité."
            >
                <template #actions>
                    <Button v-if="props.unreadCount > 0" type="button" variant="outline" :disabled="processing" @click="markAllAsRead">
                        <CheckCheck class="size-4" aria-hidden="true" />
                        Tout marquer comme lu
                    </Button>
                </template>
            </PageHeader>

            <Card>
                <CardHeader class="flex-row items-start justify-between gap-3 space-y-0">
                    <div>
                        <CardTitle class="text-base">Historique</CardTitle>
                        <CardDescription class="mt-1">
                            {{
                                props.unreadCount > 0
                                    ? `${props.unreadCount} notification${props.unreadCount > 1 ? 's' : ''} non lue${props.unreadCount > 1 ? 's' : ''}.`
                                    : 'Toutes vos notifications ont été lues.'
                            }}
                        </CardDescription>
                    </div>
                    <span class="rounded-full bg-muted px-2.5 py-1 text-[11px] font-medium text-muted-foreground"
                        >{{ props.notifications.total }} au total</span
                    >
                </CardHeader>

                <CardContent>
                    <ul v-if="props.notifications.data.length" class="space-y-2">
                        <li
                            v-for="notification in props.notifications.data"
                            :key="notification.id"
                            class="flex items-start gap-2 rounded-xl border border-border/70 bg-background transition-colors hover:border-border"
                            :class="!notification.read ? 'border-brand-100 bg-brand-50/30 dark:border-brand-900/70 dark:bg-brand-950/20' : ''"
                        >
                            <Link
                                :href="route('notifications.read', notification.id)"
                                method="post"
                                as="button"
                                preserve-scroll
                                class="flex min-w-0 flex-1 items-start gap-3 p-4 text-left"
                            >
                                <span :class="['flex size-10 shrink-0 items-center justify-center rounded-xl', toneClasses[tone(notification.tone)]]">
                                    <NotificationIcon :name="notification.icon" class="size-4" />
                                </span>

                                <span class="min-w-0 flex-1">
                                    <span class="flex items-start justify-between gap-2">
                                        <span class="text-sm font-medium text-foreground">{{ notification.title }}</span>
                                        <span
                                            v-if="!notification.read"
                                            class="mt-1.5 size-1.5 shrink-0 rounded-full bg-brand-500"
                                            aria-label="Non lue"
                                        />
                                    </span>
                                    <span v-if="notification.description" class="mt-0.5 block text-xs leading-relaxed text-muted-foreground">
                                        {{ notification.description }}
                                    </span>
                                    <span class="mt-1 block text-[11px] text-muted-foreground/80">{{ notification.time }}</span>
                                </span>
                            </Link>

                            <Button
                                type="button"
                                variant="ghost"
                                size="icon-sm"
                                class="m-2 shrink-0 text-muted-foreground hover:text-destructive"
                                :aria-label="`Supprimer la notification « ${notification.title} »`"
                                @click="remove(notification)"
                            >
                                <Trash2 class="size-4" aria-hidden="true" />
                            </Button>
                        </li>
                    </ul>

                    <EmptyState
                        v-else
                        title="Aucune notification"
                        description="Les nouvelles demandes, devis, réservations et avis apparaîtront ici."
                        :icon="BellOff"
                    />
                </CardContent>
            </Card>

            <nav v-if="props.notifications.last_page > 1" class="flex items-center justify-between gap-3" aria-label="Pagination des notifications">
                <p class="text-xs text-muted-foreground">Page {{ props.notifications.current_page }} sur {{ props.notifications.last_page }}</p>
                <div class="flex items-center gap-2">
                    <Button
                        as-child
                        variant="outline"
                        size="sm"
                        :class="props.notifications.current_page <= 1 ? 'pointer-events-none opacity-50' : ''"
                    >
                        <Link :href="pageUrl(props.notifications.current_page - 1)">Précédent</Link>
                    </Button>
                    <Button
                        as-child
                        variant="outline"
                        size="sm"
                        :class="props.notifications.current_page >= props.notifications.last_page ? 'pointer-events-none opacity-50' : ''"
                    >
                        <Link :href="pageUrl(props.notifications.current_page + 1)">Suivant</Link>
                    </Button>
                </div>
            </nav>
        </div>
    </AppLayout>
</template>
