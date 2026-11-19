<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import type { BreadcrumbItem, StatusTone } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

type ReportDetail = {
    id: number;
    reference: string;
    subject: string;
    description: string;
    status: string;
    status_label: string;
    status_tone: string;
    reporter: { id: number; name: string; email: string } | string | null;
    target: { type: string; label: string; meta: string; url: string } | null;
    resolution_note: string | null;
    handled_by: string | null;
    handled_at: string | null;
    created_at: string | null;
};

type Props = { report: ReportDetail };

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: '/admin' },
    { title: 'Signalements', href: '/admin/signalements' },
    { title: props.report.reference, href: `/admin/signalements/${props.report.id}` },
];

const tone = (value: string): StatusTone =>
    ['neutral', 'brand', 'success', 'warning', 'danger', 'info'].includes(value) ? (value as StatusTone) : 'neutral';

const formatDate = (value: string | null): string => {
    if (!value) return '—';
    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
};
</script>

<template>
    <Head :title="`Signalement · ${props.report.reference}`" />
    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <PageHeader eyebrow="Signalement" :title="props.report.reference" :description="props.report.subject">
                <template #actions>
                    <Button as-child variant="outline">
                        <Link :href="route('admin.reports.index')">
                            <ArrowLeft class="size-4" aria-hidden="true" />
                            Retour
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Détails du signalement</CardTitle>
                        <CardDescription>Informations soumises.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm">
                        <div>
                            <p class="text-xs text-muted-foreground">Sujet</p>
                            <p class="font-medium text-foreground">{{ props.report.subject }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Description</p>
                            <p class="whitespace-pre-line text-foreground">{{ props.report.description }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Statut</p>
                            <div class="mt-1">
                                <StatusBadge :tone="tone(props.report.status_tone)" :label="props.report.status_label" />
                            </div>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Créé le</p>
                            <p class="font-medium text-foreground">{{ formatDate(props.report.created_at) }}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Parties prenantes</CardTitle>
                        <CardDescription>Signalant et professionnel concerné.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm">
                        <div>
                            <p class="text-xs text-muted-foreground">Signalant</p>
                            <p class="font-medium text-foreground">
                                {{
                                    typeof props.report.reporter === 'object' && props.report.reporter !== null
                                        ? props.report.reporter.name
                                        : (props.report.reporter ?? '—')
                                }}
                            </p>
                        </div>
                        <div v-if="props.report.target">
                            <p class="text-xs text-muted-foreground">Cible</p>
                            <p class="font-medium text-foreground">{{ props.report.target.label }}</p>
                            <p class="text-xs text-muted-foreground">{{ props.report.target.type }} · {{ props.report.target.meta }}</p>
                        </div>
                        <div v-if="props.report.resolution_note">
                            <p class="text-xs text-muted-foreground">Note de résolution</p>
                            <p class="text-foreground">{{ props.report.resolution_note }}</p>
                        </div>
                        <div v-if="props.report.handled_by">
                            <p class="text-xs text-muted-foreground">Traité par</p>
                            <p class="font-medium text-foreground">{{ props.report.handled_by }}</p>
                            <p class="text-xs text-muted-foreground">{{ formatDate(props.report.handled_at) }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AdminLayout>
</template>
