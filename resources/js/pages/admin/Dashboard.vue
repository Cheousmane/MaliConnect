<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import StatCard from '@/components/StatCard.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import type { BreadcrumbItem, StatusTone } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';
import {
    AlertTriangle,
    ArrowDownRight,
    ArrowUpRight,
    Banknote,
    BriefcaseBusiness,
    Building2,
    CalendarClock,
    CheckCircle2,
    ClipboardList,
    Clock3,
    CreditCard,
    FileText,
    Minus,
    RefreshCw,
    ShieldAlert,
    Store,
    TrendingUp,
    Users,
    Wallet,
    XCircle,
} from 'lucide-vue-next';
import { computed } from 'vue';

type Metric = {
    key: string;
    label: string;
    value: number;
    description: string;
    tone: string;
    is_money?: boolean;
};

type SubscriptionMetrics = {
    active: number;
    trialing: number;
    with_access: number;
    past_due: number;
    expired: number;
    suspended: number;
    pending_payments: number;
    pending_payments_amount: number;
    renewals_upcoming: number;
    mrr: number;
    revenue_total: number;
    revenue_month: number;
    plans: { free: number; pro: number; business: number };
};

type Props = {
    metrics: Metric[];
    revenue: {
        total: number;
        currency: string;
        monthly: Array<{ label: string; amount: number }>;
    };
    subscriptions: SubscriptionMetrics;
    pending: {
        profiles: number;
        businesses: number;
        reviews: number;
        reports: number;
        payments: number;
        oldest_profiles_days: number | null;
        oldest_businesses_days: number | null;
        oldest_reviews_days: number | null;
        oldest_reports_days: number | null;
        oldest_payments_days: number | null;
    };
    alerts: Array<{ tone: string; title: string; description: string; href: string }>;
    trends: Record<string, { current: number; previous: number }>;
    recentRequests: Array<{
        id: number;
        reference: string;
        title: string;
        customer: string | null;
        professional: string | null;
        status: string;
        status_label: string;
        status_tone: string;
        created_at: string | null;
    }>;
    recentBookings: Array<{
        id: number;
        reference: string;
        customer: string | null;
        professional: string | null;
        starts_at: string | null;
        amount: number;
        status: string;
        status_label: string;
        status_tone: string;
    }>;
    recentPayments: Array<{
        id: number;
        reference: string;
        owner: string;
        amount: number;
        currency: string;
        status: string;
        status_label: string;
        status_tone: string;
        paid_at: string | null;
    }>;
    activity: Array<{
        id: number;
        action: string;
        action_label: string;
        description: string;
        actor: string | null;
        created_at: string | null;
    }>;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Administration', href: '/admin' }];

const icons: Record<string, LucideIcon> = {
    users: Users,
    professionals: BriefcaseBusiness,
    businesses: Building2,
    pending_profiles: Clock3,
    requests: ClipboardList,
    bookings: CalendarClock,
    completed_bookings: TrendingUp,
    active_subscriptions: Wallet,
    revenue: Banknote,
};

const tone = (value: string): StatusTone =>
    ['neutral', 'brand', 'success', 'warning', 'danger', 'info'].includes(value) ? (value as StatusTone) : 'neutral';

const formatMoney = (value: number): string => `${new Intl.NumberFormat('fr-FR').format(value)} FCFA`;

const formatDateTime = (value: string | null): string => {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
};

const maxRevenue = computed(() => Math.max(1, ...props.revenue.monthly.map((item) => item.amount)));

const revenueBar = (amount: number): string => `${Math.max(amount > 0 ? 8 : 2, Math.round((amount / maxRevenue.value) * 100))}%`;

const planRows = computed(() => {
    const rows = [
        { key: 'free', label: 'FREE', value: props.subscriptions.plans.free, color: 'bg-muted-foreground/40' },
        { key: 'pro', label: 'PRO', value: props.subscriptions.plans.pro, color: 'bg-brand-500' },
        { key: 'business', label: 'BUSINESS', value: props.subscriptions.plans.business, color: 'bg-gold-500' },
    ];

    const total = rows.reduce((sum, row) => sum + row.value, 0);

    return rows.map((row) => ({
        ...row,
        percentage: total > 0 ? Math.round((row.value / total) * 100) : 0,
    }));
});

const pendingTotal = computed(
    () => props.pending.profiles + props.pending.businesses + props.pending.reviews + props.pending.reports + props.pending.payments,
);

const trendFor = (key: string) => {
    const t = props.trends?.[key];
    if (!t) return null;
    const diff = t.current - t.previous;
    const pct = t.previous > 0 ? Math.round((diff / t.previous) * 100) : t.current > 0 ? 100 : 0;
    return { diff, pct, direction: diff > 0 ? 'up' : diff < 0 ? 'down' : 'flat' as const };
};

const auditIcon = (action: string): LucideIcon => {
    if (action.includes('approved') || action.includes('reactivated')) return CheckCircle2;
    if (action.includes('rejected') || action.includes('refused') || action.includes('cancelled')) return XCircle;
    if (action.includes('suspended') || action.includes('report')) return ShieldAlert;
    if (action.includes('payment')) return CreditCard;
    if (action.includes('user') || action.includes('role')) return Users;
    if (action.includes('plan') || action.includes('subscription')) return Wallet;
    if (action.includes('category') || action.includes('service')) return FileText;
    return Store;
};

const auditTone = (action: string): string => {
    if (action.includes('approved') || action.includes('reactivated')) return 'text-success bg-success/10';
    if (action.includes('rejected') || action.includes('refused') || action.includes('cancelled') || action.includes('suspended'))
        return 'text-danger bg-danger/10';
    if (action.includes('payment')) return 'text-amber-600 bg-amber-50 dark:text-amber-300 dark:bg-amber-950/40';
    return 'text-muted-foreground bg-muted';
};

const metricTrend = (key: string) => {
    const map: Record<string, string> = { users: 'users', requests: 'requests', bookings: 'bookings', revenue: 'revenue' };
    const trendKey = map[key];
    if (!trendKey) return undefined;
    const t = trendFor(trendKey);
    if (!t) return undefined;
    const sign = t.pct > 0 ? '+' : '';
    const direction = t.direction === 'flat' ? 'neutral' : t.direction;
    return { value: `${sign}${t.pct}% vs mois dernier`, direction: direction as 'up' | 'down' | 'neutral' };
};

const metricHref = (key: string): string | undefined => {
    const map: Record<string, string> = {
        users: route('admin.users.index'),
        professionals: route('admin.provider-profiles.index'),
        businesses: route('admin.businesses.index'),
        pending_profiles: route('admin.provider-profiles.index'),
        requests: route('admin.service-requests.index'),
        bookings: route('admin.bookings.index'),
        completed_bookings: route('admin.bookings.index'),
        active_subscriptions: route('admin.subscriptions.index'),
        revenue: route('admin.payments.index'),
    };
    return map[key];
};
</script>

<template>
    <Head title="Administration" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader
                v-reveal
                data-tour="admin-dashboard-header"
                title="Tableau de bord"
                description="Indicateurs réels de la plateforme : activité, abonnements et revenus encaissés."
            >
                <template #actions>
                    <div class="flex flex-wrap gap-2">
                        <Link
                            :href="route('admin.payments.index', { status: 'pending' })"
                            class="inline-flex items-center gap-2 rounded-lg border border-border bg-background px-3.5 py-2 text-sm font-medium text-foreground shadow-soft transition hover:bg-muted"
                        >
                            <CreditCard class="size-4" aria-hidden="true" />
                            Paiements en attente
                            <span class="rounded-full bg-warning/10 px-2 py-0.5 text-xs font-semibold text-amber-700 dark:text-amber-300">
                                {{ subscriptions.pending_payments }}
                            </span>
                        </Link>
                        <Link
                            :href="route('admin.plans.index')"
                            class="inline-flex items-center gap-2 rounded-lg bg-primary px-3.5 py-2 text-sm font-medium text-primary-foreground shadow-soft transition hover:bg-primary/90"
                        >
                            <Wallet class="size-4" aria-hidden="true" />
                            Gérer les formules
                        </Link>
                    </div>
                </template>
            </PageHeader>

            <div v-if="alerts.length" v-reveal="40" class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <Link
                    v-for="alert in alerts"
                    :key="alert.title"
                    :href="alert.href"
                    class="flex items-start gap-3 rounded-xl border border-border/70 bg-card p-4 shadow-soft transition hover:-translate-y-0.5 hover:shadow-card"
                >
                    <span
                        class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg"
                        :class="alert.tone === 'danger' ? 'bg-danger/10 text-danger' : 'bg-warning/10 text-amber-600 dark:text-amber-300'"
                    >
                        <AlertTriangle class="size-4" aria-hidden="true" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-foreground">{{ alert.title }}</p>
                        <p class="mt-1 text-xs leading-relaxed text-muted-foreground">{{ alert.description }}</p>
                    </div>
                </Link>
            </div>

            <div v-reveal="60" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <StatCard
                    v-for="metric in metrics"
                    :key="metric.key"
                    :label="metric.label"
                    :value="metric.is_money ? formatMoney(metric.value) : metric.value"
                    :description="metric.description"
                    :icon="icons[metric.key]"
                    :tone="tone(metric.tone)"
                    :trend="metricTrend(metric.key)"
                    :href="metricHref(metric.key)"
                />
            </div>

            <div v-reveal="120" class="grid gap-4 lg:grid-cols-3">
                <Card class="lg:col-span-2">
                    <CardHeader class="flex flex-row items-start justify-between gap-4">
                        <div>
                            <CardTitle class="text-base">Revenus encaissés</CardTitle>
                            <CardDescription>Uniquement les paiements vérifiés et payés, sur les six derniers mois.</CardDescription>
                        </div>
                        <div class="text-right">
                            <p class="font-display text-xl font-semibold text-foreground">{{ formatMoney(revenue.total) }}</p>
                            <p class="text-xs text-muted-foreground">Total historique</p>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="flex h-40 items-end gap-3 border-b border-border/60 pb-2">
                            <div
                                v-for="item in revenue.monthly"
                                :key="item.label"
                                class="flex min-w-0 flex-1 flex-col items-center justify-end gap-2"
                            >
                                <span class="text-[10px] font-medium text-muted-foreground">{{
                                    item.amount > 0 ? formatMoney(item.amount) : '—'
                                }}</span>
                                <div class="flex h-24 w-full items-end justify-center">
                                    <div
                                        class="w-full max-w-14 rounded-t-lg bg-brand-600 transition-all duration-500"
                                        :style="{ height: revenueBar(item.amount) }"
                                        :title="`${item.label} : ${formatMoney(item.amount)}`"
                                    />
                                </div>
                                <span class="truncate text-[10px] text-muted-foreground">{{ item.label }}</span>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-3 sm:grid-cols-3">
                            <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Ce mois</p>
                                <p class="mt-1 font-display text-lg font-semibold text-foreground">{{ formatMoney(subscriptions.revenue_month) }}</p>
                            </div>
                            <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">MRR</p>
                                <p class="mt-1 font-display text-lg font-semibold text-foreground">{{ formatMoney(subscriptions.mrr) }}</p>
                            </div>
                            <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">En attente</p>
                                <p class="mt-1 font-display text-lg font-semibold text-foreground">
                                    {{ formatMoney(subscriptions.pending_payments_amount) }}
                                </p>
                                <p class="text-[11px] text-muted-foreground">{{ subscriptions.pending_payments }} dossier(s) à vérifier</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Répartition des formules</CardTitle>
                        <CardDescription>Comptes professionnels couverts, y compris la formule FREE par défaut.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div v-for="row in planRows" :key="row.key" class="space-y-1.5">
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-medium text-foreground">{{ row.label }}</span>
                                <span class="text-muted-foreground">{{ row.value }} · {{ row.percentage }} %</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-muted">
                                <div
                                    :class="['h-full rounded-full transition-all duration-500', row.color]"
                                    :style="{ width: `${row.percentage}%` }"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 border-t border-border/60 pt-4 text-sm">
                            <div>
                                <p class="text-xs text-muted-foreground">Payants actifs</p>
                                <p class="font-semibold text-foreground">{{ subscriptions.active }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">En essai gratuit</p>
                                <p class="font-semibold text-foreground">{{ subscriptions.trialing }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Avec accès</p>
                                <p class="font-semibold text-foreground">{{ subscriptions.with_access }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">En retard</p>
                                <p class="font-semibold text-foreground">{{ subscriptions.past_due }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Renouvellements 7 j</p>
                                <p class="font-semibold text-foreground">{{ subscriptions.renewals_upcoming }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Expirés</p>
                                <p class="font-semibold text-foreground">{{ subscriptions.expired }}</p>
                            </div>
                        </div>

                        <Link
                            :href="route('admin.subscriptions.index')"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-border bg-background px-3 py-2 text-sm font-medium text-foreground transition hover:bg-muted"
                        >
                            <RefreshCw class="size-4" aria-hidden="true" />
                            Suivre les abonnements
                        </Link>
                    </CardContent>
                </Card>
            </div>

            <div v-reveal="180" class="grid gap-4 lg:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">File de vérification</CardTitle>
                        <CardDescription>{{ pendingTotal }} élément(s) en attente de décision.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <Link :href="route('admin.provider-profiles.index')" class="flex items-center justify-between rounded-lg border border-border/60 px-3 py-2.5 text-sm transition hover:bg-muted">
                            <div>
                                <span class="text-foreground">Profils professionnels</span>
                                <p v-if="pending.oldest_profiles_days !== null" class="text-[11px] text-muted-foreground">Plus ancien : {{ pending.oldest_profiles_days }} j</p>
                            </div>
                            <span class="font-semibold text-foreground">{{ pending.profiles }}</span>
                        </Link>
                        <Link :href="route('admin.businesses.index')" class="flex items-center justify-between rounded-lg border border-border/60 px-3 py-2.5 text-sm transition hover:bg-muted">
                            <div>
                                <span class="text-foreground">Entreprises</span>
                                <p v-if="pending.oldest_businesses_days !== null" class="text-[11px] text-muted-foreground">Plus ancien : {{ pending.oldest_businesses_days }} j</p>
                            </div>
                            <span class="font-semibold text-foreground">{{ pending.businesses }}</span>
                        </Link>
                        <Link :href="route('admin.reviews.index')" class="flex items-center justify-between rounded-lg border border-border/60 px-3 py-2.5 text-sm transition hover:bg-muted">
                            <div>
                                <span class="text-foreground">Avis à modérer</span>
                                <p v-if="pending.oldest_reviews_days !== null" class="text-[11px] text-muted-foreground">Plus ancien : {{ pending.oldest_reviews_days }} j</p>
                            </div>
                            <span class="font-semibold text-foreground">{{ pending.reviews }}</span>
                        </Link>
                        <Link :href="route('admin.payments.index', { status: 'pending' })" class="flex items-center justify-between rounded-lg border border-border/60 px-3 py-2.5 text-sm transition hover:bg-muted">
                            <div>
                                <span class="text-foreground">Paiements à vérifier</span>
                                <p v-if="pending.oldest_payments_days !== null" class="text-[11px] text-muted-foreground">Plus ancien : {{ pending.oldest_payments_days }} j</p>
                            </div>
                            <span class="font-semibold text-foreground">{{ pending.payments }}</span>
                        </Link>
                        <Link :href="route('admin.reports.index')" class="flex items-center justify-between rounded-lg border border-border/60 px-3 py-2.5 text-sm transition hover:bg-muted">
                            <div>
                                <span class="text-foreground">Signalements ouverts</span>
                                <p v-if="pending.oldest_reports_days !== null" class="text-[11px] text-muted-foreground">Plus ancien : {{ pending.oldest_reports_days }} j</p>
                            </div>
                            <span class="font-semibold text-foreground">{{ pending.reports }}</span>
                        </Link>
                    </CardContent>
                </Card>

                <Card class="lg:col-span-2">
                    <CardHeader class="flex flex-row items-center justify-between gap-3">
                        <div>
                            <CardTitle class="text-base">Derniers paiements</CardTitle>
                            <CardDescription>Les paiements en attente ne sont jamais comptés comme revenus.</CardDescription>
                        </div>
                        <Link :href="route('admin.payments.index')" class="text-sm font-medium text-brand-700 hover:underline dark:text-brand-300">
                            Tout voir
                        </Link>
                    </CardHeader>
                    <CardContent>
                        <div
                            v-if="recentPayments.length === 0"
                            class="rounded-xl border border-dashed border-border px-4 py-8 text-center text-sm text-muted-foreground"
                        >
                            Aucun paiement enregistré.
                        </div>
                        <div v-else class="divide-y divide-border/60">
                            <Link
                                v-for="payment in recentPayments"
                                :key="payment.id"
                                :href="route('admin.payments.show', payment.id)"
                                class="flex items-center justify-between gap-4 py-3 transition hover:bg-muted/30"
                            >
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-foreground">{{ payment.owner }}</p>
                                    <p class="truncate text-xs text-muted-foreground">
                                        {{ payment.reference }} · {{ formatDateTime(payment.paid_at) }}
                                    </p>
                                </div>
                                <div class="flex shrink-0 items-center gap-3">
                                    <span class="text-sm font-semibold text-foreground">{{ formatMoney(payment.amount) }}</span>
                                    <StatusBadge :tone="tone(payment.status_tone)" :label="payment.status_label" />
                                </div>
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div v-reveal="240" class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Dernières demandes</CardTitle>
                        <CardDescription>Demandes de devis déposées récemment.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div
                            v-if="recentRequests.length === 0"
                            class="rounded-xl border border-dashed border-border px-4 py-8 text-center text-sm text-muted-foreground"
                        >
                            Aucune demande.
                        </div>
                        <div v-else class="space-y-3">
                            <div
                                v-for="item in recentRequests"
                                :key="item.id"
                                class="flex items-start justify-between gap-4 rounded-xl border border-border/60 p-3"
                            >
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-foreground">{{ item.title }}</p>
                                    <p class="truncate text-xs text-muted-foreground">
                                        {{ item.reference }} · {{ item.customer ?? 'Client inconnu' }}
                                    </p>
                                    <p class="truncate text-xs text-muted-foreground">{{ item.professional ?? 'Professionnel inconnu' }}</p>
                                </div>
                                <StatusBadge :tone="tone(item.status_tone)" :label="item.status_label" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Dernières réservations</CardTitle>
                        <CardDescription>Créneaux réservés et prestations à venir.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div
                            v-if="recentBookings.length === 0"
                            class="rounded-xl border border-dashed border-border px-4 py-8 text-center text-sm text-muted-foreground"
                        >
                            Aucune réservation.
                        </div>
                        <div v-else class="space-y-3">
                            <div
                                v-for="item in recentBookings"
                                :key="item.id"
                                class="flex items-start justify-between gap-4 rounded-xl border border-border/60 p-3"
                            >
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-foreground">{{ item.professional ?? 'Professionnel inconnu' }}</p>
                                    <p class="truncate text-xs text-muted-foreground">{{ item.reference }} · {{ formatDateTime(item.starts_at) }}</p>
                                    <p class="truncate text-xs text-muted-foreground">
                                        {{ item.customer ?? 'Client inconnu' }} · {{ formatMoney(item.amount) }}
                                    </p>
                                </div>
                                <StatusBadge :tone="tone(item.status_tone)" :label="item.status_label" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card v-reveal="300">
                <CardHeader class="flex flex-row items-center justify-between gap-3">
                    <div>
                        <CardTitle class="text-base">Journal récent</CardTitle>
                        <CardDescription>Dernières actions sensibles réalisées dans l’administration.</CardDescription>
                    </div>
                    <Link :href="route('admin.audit.index')" class="text-sm font-medium text-brand-700 hover:underline dark:text-brand-300">
                        Voir tout
                    </Link>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="activity.length === 0"
                        class="rounded-xl border border-dashed border-border px-4 py-8 text-center text-sm text-muted-foreground"
                    >
                        Aucune action enregistrée.
                    </div>
                    <div v-else class="space-y-3">
                        <div
                            v-for="item in activity"
                            :key="item.id"
                            class="flex items-start gap-3 border-b border-border/50 pb-3 last:border-0 last:pb-0"
                        >
                            <span class="mt-1 flex size-8 shrink-0 items-center justify-center rounded-lg" :class="auditTone(item.action)">
                                <component :is="auditIcon(item.action)" class="size-4" aria-hidden="true" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-foreground">{{ item.description }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ item.action_label }} · {{ item.actor ?? 'Système' }} · {{ formatDateTime(item.created_at) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
