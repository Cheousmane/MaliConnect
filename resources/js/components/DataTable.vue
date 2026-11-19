<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { cn } from '@/lib/utils';
import type { TableColumn } from '@/types';
import type { LucideIcon } from 'lucide-vue-next';
import type { HTMLAttributes } from 'vue';

interface Props {
    columns: TableColumn[];
    rows: Record<string, any>[];
    rowKey?: string;
    loading?: boolean;
    emptyTitle?: string;
    emptyDescription?: string;
    emptyIcon?: LucideIcon;
    caption?: string;
    class?: HTMLAttributes['class'];
}

const props = withDefaults(defineProps<Props>(), {
    rowKey: 'id',
    loading: false,
    emptyTitle: 'Aucun résultat',
    emptyDescription: 'Les éléments correspondant à votre recherche apparaîtront ici.',
});

const alignClasses: Record<NonNullable<TableColumn['align']>, string> = {
    left: 'text-left',
    center: 'text-center',
    right: 'text-right',
};

function rowIdentifier(row: Record<string, unknown>, index: number): string | number {
    const value = row[props.rowKey];
    return typeof value === 'string' || typeof value === 'number' ? value : index;
}

function formatValue(value: unknown): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    if (typeof value === 'boolean') {
        return value ? 'Oui' : 'Non';
    }

    if (typeof value === 'string' || typeof value === 'number') {
        return String(value);
    }

    return '—';
}
</script>

<template>
    <div :class="cn('w-full', props.class)">
        <!-- Version bureau : tableau sémantique classique. -->
        <div class="hidden md:block">
            <Table>
                <caption v-if="caption" class="sr-only">
                    {{
                        caption
                    }}
                </caption>
                <TableHeader>
                    <TableRow class="hover:bg-transparent">
                        <TableHead v-for="column in columns" :key="column.key" :class="cn(alignClasses[column.align ?? 'left'], column.class)">
                            {{ column.label }}
                        </TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <template v-if="loading">
                        <TableRow v-for="index in 5" :key="`skeleton-${index}`" class="hover:bg-transparent">
                            <TableCell v-for="column in columns" :key="column.key">
                                <Skeleton class="h-4" :class="column.key === columns[0]?.key ? 'w-36' : 'w-20'" />
                            </TableCell>
                        </TableRow>
                    </template>

                    <template v-else-if="rows.length > 0">
                        <TableRow v-for="(row, index) in rows" :key="rowIdentifier(row, index)">
                            <TableCell v-for="column in columns" :key="column.key" :class="cn(alignClasses[column.align ?? 'left'], column.class)">
                                <slot :name="`cell-${column.key}`" :row="row" :value="row[column.key]" :column="column" :index="index">
                                    {{ formatValue(row[column.key]) }}
                                </slot>
                            </TableCell>
                        </TableRow>
                    </template>

                    <TableRow v-else class="hover:bg-transparent">
                        <TableCell :colspan="columns.length" class="p-4">
                            <EmptyState :icon="emptyIcon" :title="emptyTitle" :description="emptyDescription" compact>
                                <template v-if="$slots['empty-action']" #action>
                                    <slot name="empty-action" />
                                </template>
                            </EmptyState>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <!-- Version mobile : chaque ligne devient une carte lisible. -->
        <div class="space-y-3 md:hidden">
            <template v-if="loading">
                <div v-for="index in 3" :key="`mobile-skeleton-${index}`" class="rounded-xl border border-border/80 bg-card p-4 shadow-soft">
                    <Skeleton class="h-4 w-2/3" />
                    <Skeleton class="mt-3 h-3 w-1/2" />
                    <Skeleton class="mt-3 h-3 w-1/3" />
                </div>
            </template>

            <EmptyState v-else-if="rows.length === 0" :icon="emptyIcon" :title="emptyTitle" :description="emptyDescription" compact>
                <template v-if="$slots['empty-action']" #action>
                    <slot name="empty-action" />
                </template>
            </EmptyState>

            <template v-else>
                <template v-for="(row, index) in rows" :key="rowIdentifier(row, index)">
                    <slot name="mobile-row" :row="row" :index="index">
                        <div class="rounded-xl border border-border/80 bg-card p-4 shadow-soft">
                            <dl class="space-y-2.5">
                                <div
                                    v-for="column in columns.filter((item) => !item.hideOnMobile)"
                                    :key="column.key"
                                    class="flex items-start justify-between gap-4"
                                >
                                    <dt class="shrink-0 text-[11px] font-semibold uppercase tracking-[0.08em] text-muted-foreground">
                                        {{ column.label }}
                                    </dt>
                                    <dd class="min-w-0 break-words text-right text-sm text-foreground">
                                        <slot :name="`cell-${column.key}`" :row="row" :value="row[column.key]" :column="column" :index="index">
                                            {{ formatValue(row[column.key]) }}
                                        </slot>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </slot>
                </template>
            </template>
        </div>
    </div>
</template>
