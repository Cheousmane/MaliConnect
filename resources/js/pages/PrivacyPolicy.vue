<script setup lang="ts">
import PublicLayout from '@/layouts/public/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ShieldCheck } from 'lucide-vue-next';

const lastUpdated = '26 novembre 2026';

const sections = [
    {
        title: '1. Responsable du traitement',
        paragraphs: [
            'MaliConnect est une plateforme de mise en relation entre particuliers et professionnels à Bamako, Mali. Pour toute question relative à la protection de vos données, vous pouvez nous contacter à l’adresse contact@maliconnect.ml.',
        ],
    },
    {
        title: '2. Données que nous collectons',
        items: [
            'Données de compte : nom, adresse e-mail, numéro de téléphone, mot de passe (stocké sous forme chiffrée), photo de profil le cas échéant.',
            'Données professionnelles : nom commercial, description, catégorie/service, coordonnées, adresse, ville/commune/quartier, position géographique si vous la renseignez, horaires, médias, tarifs et fiches tarifaires.',
            'Données d’utilisation : demandes de service, messages, devis, réservations, avis, favoris, notifications et pièces jointes que vous choisissez de partager.',
            'Données de paiement/abonnement : informations liées à la formule choisie, justificatifs de paiement transmis pour vérification, statut et historique d’abonnement.',
            'Données techniques : informations de session, journaux de sécurité, adresse IP et informations nécessaires au fonctionnement, à la sécurité et à l’audit des actions sensibles.',
        ],
    },
    {
        title: '3. Finalités du traitement',
        items: [
            'Créer et gérer votre compte utilisateur.',
            'Permettre la recherche, la publication et la consultation de profils professionnels.',
            'Gérer les demandes, échanges, devis, réservations, avis et favoris.',
            'Gérer les abonnements, paiements, vérifications et obligations de modération.',
            'Assurer la sécurité de la plateforme, prévenir les abus, détecter les fraudes et maintenir un journal d’audit administratif.',
            'Vous envoyer des notifications liées à votre activité sur MaliConnect (demandes, réservations, avis, abonnements, modération, etc.).',
        ],
    },
    {
        title: '4. Partage des données',
        paragraphs: [
            'MaliConnect ne vend pas vos données personnelles. Vos informations sont partagées uniquement lorsque cela est nécessaire au fonctionnement du service :',
        ],
        items: [
            'Avec les autres utilisateurs : les informations que vous rendez publiques sur un profil professionnel, ainsi que les informations nécessaires au traitement d’une demande, d’un devis, d’une réservation ou d’un avis.',
            'Avec les administrateurs habilités : pour la vérification des profils, la modération, la gestion des paiements, des signalements et la sécurité de la plateforme.',
            'Avec des prestataires techniques strictement nécessaires : hébergement, stockage, envoi d’e-mails ou services de cartographie lorsque vous utilisez la carte (par exemple OpenStreetMap/Nominatim).',
        ],
    },
    {
        title: '5. Conservation des données',
        paragraphs: [
            'Nous conservons vos données aussi longtemps que votre compte est actif ou que cela est nécessaire pour fournir le service, respecter nos obligations légales, résoudre des litiges, prévenir les abus et faire appliquer nos règles. Certaines données peuvent être conservées sous forme archivée ou journalisée pour des raisons de sécurité, d’audit ou de preuve.',
        ],
    },
    {
        title: '6. Sécurité',
        paragraphs: [
            'Nous mettons en œuvre des mesures techniques et organisationnelles raisonnables pour protéger vos données : contrôle d’accès par rôles et permissions, vérification des comptes, limitation de débit, journalisation des actions sensibles, protection des sessions et restrictions d’accès aux fichiers privés. Aucun système n’étant totalement sécurisé, nous vous recommandons également de protéger vos identifiants.',
        ],
    },
    {
        title: '7. Vos droits',
        paragraphs: [
            'Selon la réglementation applicable, vous pouvez demander l’accès, la rectification, la mise à jour ou la suppression de certaines données vous concernant, sous réserve des obligations légales ou légitimes de conservation.',
        ],
        items: [
            'Vous pouvez modifier une partie de vos informations depuis vos paramètres de compte.',
            'Vous pouvez demander la suppression de votre compte, lorsque cette suppression est compatible avec les obligations légales, comptables, de sécurité ou de prévention des litiges.',
            'Vous pouvez nous contacter pour toute demande relative à vos données personnelles à contact@maliconnect.ml.',
        ],
    },
    {
        title: '8. Cookies et stockage local',
        paragraphs: [
            'MaliConnect utilise des cookies ou mécanismes similaires nécessaires au fonctionnement du service, notamment pour la session, la sécurité, l’authentification et vos préférences d’interface (par exemple l’apparence). Nous n’utilisons pas ces mécanismes pour vendre vos données personnelles.',
        ],
    },
    {
        title: '9. Modifications',
        paragraphs: [
            'Cette politique peut être mise à jour pour refléter les évolutions du service, de la sécurité ou des exigences légales. La date de dernière mise à jour affichée en haut de cette page indique la version en vigueur.',
        ],
    },
];
</script>

<template>
    <Head title="Politique de confidentialité" />

    <PublicLayout>
        <section class="border-b border-border/60 bg-muted/20 py-12 sm:py-16">
            <div class="page-container">
                <Link
                    :href="route('home')"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
                >
                    <ArrowLeft class="size-4" aria-hidden="true" />
                    Retour à l’accueil
                </Link>

                <div class="mt-6 max-w-3xl">
                    <span class="inline-flex items-center gap-2 rounded-full border border-brand-200 bg-brand-50 px-3 py-1.5 text-[11px] font-semibold text-brand-700 dark:border-brand-900 dark:bg-brand-950/40 dark:text-brand-300">
                        <ShieldCheck class="size-3.5" aria-hidden="true" />
                        Confidentialité
                    </span>
                    <h1 class="mt-4 font-display text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">
                        Politique de confidentialité
                    </h1>
                    <p class="mt-4 max-w-2xl text-sm leading-relaxed text-muted-foreground sm:text-base">
                        Cette politique explique comment MaliConnect collecte, utilise, protège et partage vos données lorsque vous utilisez la
                        plateforme. Elle s’applique aux particuliers, professionnels et administrateurs utilisant MaliConnect.
                    </p>
                    <p class="mt-4 text-xs text-muted-foreground">Dernière mise à jour : {{ lastUpdated }}</p>
                </div>
            </div>
        </section>

        <section class="py-12 sm:py-16">
            <div class="page-container">
                <div class="mx-auto max-w-4xl space-y-6">
                    <article
                        v-for="section in sections"
                        :key="section.title"
                        class="rounded-2xl border border-border/70 bg-card p-6 shadow-soft sm:p-7"
                    >
                        <h2 class="font-display text-lg font-semibold text-foreground sm:text-xl">{{ section.title }}</h2>

                        <div class="mt-4 space-y-4 text-sm leading-relaxed text-muted-foreground sm:text-[15px]">
                            <p v-for="paragraph in section.paragraphs ?? []" :key="paragraph">{{ paragraph }}</p>

                            <ul v-if="section.items?.length" class="space-y-2">
                                <li v-for="item in section.items" :key="item" class="flex gap-3">
                                    <span class="mt-2 size-1.5 shrink-0 rounded-full bg-brand-500" aria-hidden="true" />
                                    <span>{{ item }}</span>
                                </li>
                            </ul>
                        </div>
                    </article>

                    <div class="rounded-2xl border border-brand-200/70 bg-brand-50 p-6 dark:border-brand-900 dark:bg-brand-950/30 sm:p-7">
                        <h2 class="font-display text-lg font-semibold text-foreground">Contact</h2>
                        <p class="mt-3 text-sm leading-relaxed text-muted-foreground sm:text-[15px]">
                            Pour toute question ou demande liée à vos données personnelles, contactez-nous à
                            <a href="mailto:contact@maliconnect.ml" class="font-medium text-brand-700 underline-offset-4 hover:underline dark:text-brand-300">
                                contact@maliconnect.ml
                            </a>.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
