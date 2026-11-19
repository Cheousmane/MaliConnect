<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppearanceTabs from '@/components/AppearanceTabs.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { type BreadcrumbItem } from '@/types';

import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Apparence',
        href: '/settings/appearance',
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Apparence" />

        <SettingsLayout>
            <div class="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Apparence de l’interface</CardTitle>
                        <CardDescription> Choisissez le thème qui vous convient. Votre préférence est enregistrée sur cet appareil. </CardDescription>
                    </CardHeader>

                    <CardContent>
                        <AppearanceTabs />
                    </CardContent>
                </Card>

                <div class="rounded-xl border border-border/80 bg-muted/30 p-4">
                    <p class="text-sm font-medium text-foreground">Le thème système s’adapte automatiquement</p>
                    <p class="mt-1 text-sm leading-relaxed text-muted-foreground">
                        Avec l’option Système, MaliConnect suit le réglage clair ou sombre de votre appareil et s’ajuste dès qu’il change.
                    </p>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
