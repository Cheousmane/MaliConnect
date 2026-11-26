<script setup lang="ts">
import HeroSearch from '@/components/public/landing/HeroSearch.vue';
import HeroVisual from '@/components/public/landing/HeroVisual.vue';
import AnimatedNumber from '@/components/public/landing/AnimatedNumber.vue';
import CategoryTile from '@/components/public/landing/CategoryTile.vue';
import LocationMap from '@/components/LocationMap.vue';
import DevicePreview from '@/components/DevicePreview.vue';
import PlansSection from '@/components/public/landing/PlansSection.vue';
import ProDashboardPreview from '@/components/public/landing/ProDashboardPreview.vue';
import ProfessionalSpotlight from '@/components/public/landing/ProfessionalSpotlight.vue';
import SectionHeader from '@/components/public/landing/SectionHeader.vue';
import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/public/PublicLayout.vue';
import type {
    LandingCategory,
    LandingPlan,
    LandingPriceExample,
    LandingProfessional,
    LandingStats,
} from '@/types/landing';
import type { SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { useScroll } from '@vueuse/core';
import type { LucideIcon } from 'lucide-vue-next';
import {
    ArrowRight,
    BadgeCheck,
    CalendarCheck,
    CheckCircle2,
    ClipboardList,
    FileText,
    LayoutDashboard,
    MapPin,
    MessagesSquare,
    ReceiptText,
    Search,
    ShieldCheck,
    Sparkles,
    Store,
    TrendingUp,
    Users,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps<{
    categories: LandingCategory[];
    profiles: LandingProfessional[];
    stats: LandingStats;
    plans: LandingPlan[];
    locations: string[];
    priceExample: LandingPriceExample | null;
}>();

const page = usePage<SharedData>();
const authUser = computed(() => page.props.auth?.user ?? null);

const profilesWithCoords = computed(() => props.profiles.filter((p) => p.latitude != null && p.longitude != null));
const mapMarkers = computed(() =>
    profilesWithCoords.value.map((p) => ({
        latitude: p.latitude as number,
        longitude: p.longitude as number,
        label: p.business_name,
        href: route('professionals.show', p.slug),
    })),
);
const featuredProfessional = computed(() => props.profiles[0] ?? null);
const proPlan = computed(() => props.plans.find((plan) => plan.code === 'pro') ?? null);

const steps: Array<{ title: string; description: string; icon: LucideIcon }> = [
    {
        title: 'Recherchez le service',
        description: 'Décrivez votre besoin, choisissez une catégorie et précisez votre commune ou votre quartier.',
        icon: Search,
    },
    {
        title: 'Comparez les professionnels',
        description: 'Consultez les profils vérifiés, les prestations proposées et les tarifs annoncés.',
        icon: TrendingUp,
    },
    {
        title: 'Contactez ou demandez un devis',
        description: 'Échangez directement avec le professionnel ou envoyez une demande détaillée pour recevoir un devis.',
        icon: MessagesSquare,
    },
];

const commitments: Array<{ title: string; description: string; icon: LucideIcon }> = [
    {
        title: 'Profils vérifiables',
        description: 'Chaque profil présenté publiquement a été contrôlé par l’administration avant sa publication.',
        icon: BadgeCheck,
    },
    {
        title: 'Tarifs plus transparents',
        description: 'Les professionnels publient leurs prestations et leurs prix, ou indiquent clairement « sur devis ».',
        icon: ReceiptText,
    },
    {
        title: 'Recherche locale simplifiée',
        description: 'Filtrez par catégorie, commune, quartier, budget ou disponibilité, sans détour.',
        icon: MapPin,
    },
    {
        title: 'Contact direct',
        description: 'Téléphone, WhatsApp ou demande de devis : vous parlez directement au professionnel.',
        icon: MessagesSquare,
    },
];

const proBenefits: Array<{ label: string; icon: LucideIcon }> = [
    { label: 'Développer votre visibilité locale', icon: TrendingUp },
    { label: 'Présenter vos prestations', icon: ClipboardList },
    { label: 'Publier vos tarifs', icon: FileText },
    { label: 'Recevoir des demandes de devis', icon: MessagesSquare },
    { label: 'Gérer vos réservations', icon: CalendarCheck },
    { label: 'Suivre vos statistiques', icon: LayoutDashboard },
];

const ctaProHref = computed(() => (authUser.value ? route('pro.profiles.index') : route('register')));

const { y: scrollY } = useScroll(typeof window !== 'undefined' ? window : undefined);
const heroParallax = computed(() => Math.min(24, scrollY.value * 0.05));

let floatObserver: IntersectionObserver | null = null;

onMounted(() => {
    if (typeof IntersectionObserver === 'undefined') return;
    floatObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                entry.target.classList.toggle('is-visible', entry.isIntersecting);
            });
        },
        { threshold: 0.15 },
    );

    document.querySelectorAll<HTMLElement>('.landing-float-card').forEach((el) => floatObserver?.observe(el));
});

onBeforeUnmount(() => {
    floatObserver?.disconnect();
});
</script>

<template>
    <Head title="Tous les bons professionnels, près de vous à Bamako">
        <meta
            head-key="description"
            name="description"
            content="Trouvez les services dont vous avez besoin, comparez les tarifs et contactez les meilleurs professionnels à Bamako. Profils vérifiés, recherche locale et contact direct."
        />
        <meta head-key="og:title" property="og:title" content="MaliConnect — Tous les bons professionnels, près de vous" />
        <meta
            head-key="og:description"
            property="og:description"
            content="Trouvez les services dont vous avez besoin, comparez les tarifs et contactez les meilleurs professionnels à Bamako."
        />
        <meta head-key="og:type" property="og:type" content="website" />
        <meta head-key="og:locale" property="og:locale" content="fr_FR" />
        <meta head-key="og:site_name" property="og:site_name" content="MaliConnect" />
        <meta head-key="twitter:card" name="twitter:card" content="summary_large_image" />
    </Head>

    <PublicLayout>
        <!-- ============ HÉRO ============ -->
        <section id="accueil" class="relative overflow-hidden">
            <div class="hero-mesh pointer-events-none absolute inset-0 opacity-70 transition-transform duration-300" :style="{ transform: 'translate3d(0, ' + heroParallax + 'px, 0)' }" aria-hidden="true" />
            <div
                class="pointer-events-none absolute inset-x-0 top-0 h-px bg-brand-300/40 dark:bg-brand-800/60"
                aria-hidden="true"
            />

            <div class="page-container relative pb-14 pt-10 sm:pt-14 lg:pb-20 lg:pt-20">
                <div class="grid items-center gap-14 lg:grid-cols-[1.04fr_0.96fr] lg:gap-10">
                    <div class="max-w-2xl">
                        <div
                            class="inline-flex items-center gap-2 rounded-full border border-border/80 bg-card/80 px-3 py-1.5 text-[11px] font-medium text-muted-foreground shadow-soft backdrop-blur animate-fade-up"
                        >
                            <span class="relative flex size-1.5" aria-hidden="true">
                                <span class="absolute inline-flex size-full rounded-full bg-brand-500 opacity-70 animate-pulse-ring" />
                                <span class="relative inline-flex size-1.5 rounded-full bg-brand-600" />
                            </span>
                            Bamako · Marketplace de services
                        </div>

                        <h1
                            class="mt-6 text-balance font-display text-[40px] font-semibold leading-[1.05] tracking-[-0.035em] text-foreground animate-fade-up sm:text-5xl lg:text-[60px]"
                            style="animation-delay: 80ms"
                        >
                            Tous les bons professionnels,
                            <span class="relative inline-block">
                                <span class="text-brand-600 dark:text-brand-400">près de vous.</span>
                                <span
                                    class="hero-underline absolute bottom-1 left-0 h-[3px] w-0 bg-brand-600 animate-underline-draw dark:bg-brand-400"
                                    aria-hidden="true"
                                />
                            </span>
                        </h1>

                        <p
                            class="mt-5 max-w-xl text-pretty text-base leading-relaxed text-muted-foreground animate-fade-up sm:text-lg"
                            style="animation-delay: 160ms"
                        >
                            Trouvez les services dont vous avez besoin, comparez les tarifs et contactez les meilleurs professionnels à Bamako.
                        </p>

                        <div class="animate-fade-up" style="animation-delay: 240ms">
                            <HeroSearch :locations="locations" :categories="categories" />
                        </div>

                        <ul
                            class="mt-7 flex flex-wrap gap-x-6 gap-y-3 text-xs text-muted-foreground animate-fade-up"
                            style="animation-delay: 320ms"
                        >
                            <li class="flex items-center gap-2">
                                <ShieldCheck class="size-4 text-brand-600 dark:text-brand-400" aria-hidden="true" />
                                Profils vérifiés par l’administration
                            </li>
                            <li class="flex items-center gap-2">
                                <ReceiptText class="size-4 text-brand-600 dark:text-brand-400" aria-hidden="true" />
                                Tarifs annoncés
                            </li>
                            <li class="flex items-center gap-2">
                                <MessagesSquare class="size-4 text-brand-600 dark:text-brand-400" aria-hidden="true" />
                                Contact direct
                            </li>
                        </ul>
                    </div>

                    <div class="animate-fade-up" style="animation-delay: 300ms">
                        <HeroVisual :professional="featuredProfessional" :categories="categories" :stats="stats" />
                    </div>
                </div>

                <!-- Bandeau de chiffres réels -->
                <dl class="mt-16 grid gap-6 border-t border-border/60 pt-8 sm:grid-cols-3 sm:gap-8">
                    <div class="flex items-baseline gap-3">
                        <dt class="sr-only">Catégories de services</dt>
                        <dd class="font-display text-2xl font-semibold tracking-tight text-foreground"><AnimatedNumber :value="stats.categories" /></dd>
                        <span class="text-sm text-muted-foreground">catégories de services</span>
                    </div>
                    <div class="flex items-baseline gap-3 sm:border-l sm:border-border/60 sm:pl-8">
                        <dt class="sr-only">Savoir-faire référencés</dt>
                        <dd class="font-display text-2xl font-semibold tracking-tight text-foreground"><AnimatedNumber :value="stats.subcategories" /></dd>
                        <span class="text-sm text-muted-foreground">savoir-faire référencés</span>
                    </div>
                    <div class="flex items-baseline gap-3 sm:border-l sm:border-border/60 sm:pl-8">
                        <dt class="sr-only">Profils vérifiés</dt>
                        <dd class="font-display text-2xl font-semibold tracking-tight text-foreground"><AnimatedNumber :value="100" /> %</dd>
                        <span class="text-sm text-muted-foreground">de profils vérifiés avant publication</span>
                    </div>
                </dl>
            </div>
        </section>

        <!-- ============ CATÉGORIES ============ -->
        <section id="categories" class="scroll-mt-24 border-t border-border/60 py-16 sm:py-20 lg:py-24">
            <div class="page-container">
                <SectionHeader
                    v-reveal
                    eyebrow="Explorer"
                    title="Un savoir-faire pour chaque besoin."
                    description="Maison, technologie, automobile, services personnels ou entreprises : parcourez les métiers du quotidien et trouvez le professionnel adapté."
                />

                <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
                    <CategoryTile
                        v-for="(category, index) in categories"
                        :key="category.id"
                        v-reveal="Math.min(index, 4) * 80"
                        :category="category"
                        :featured="index < 2"
                        :class="index < 2 ? 'lg:col-span-3' : 'lg:col-span-2'"
                    />
                </div>

                <div v-reveal class="mt-8 flex flex-wrap items-center gap-3">
                    <Button as-child variant="outline" size="sm">
                        <Link :href="route('categories.index')">
                            Voir tout le catalogue
                            <ArrowRight class="size-3.5" aria-hidden="true" />
                        </Link>
                    </Button>
                    <p class="text-xs text-muted-foreground">De nouvelles spécialités sont ajoutées au catalogue au fil des validations.</p>
                </div>
            </div>
        </section>

        <!-- ============ PROFESSIONNELS ============ -->
        <section id="professionnels" class="scroll-mt-24 border-t border-border/60 bg-muted/20 py-16 sm:py-20 lg:py-24">
            <div class="page-container">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                    <SectionHeader
                        v-reveal
                        eyebrow="L’annuaire"
                        title="Des professionnels vérifiés, près de chez vous."
                        description="Chaque profil visible ici a été contrôlé par l’administration. Les profils en attente ou refusés ne sont jamais présentés comme vérifiés."
                    />

                    <div v-reveal class="shrink-0">
                        <Button as-child variant="outline" size="sm">
                            <Link :href="route('search')">
                                <Search class="size-3.5" aria-hidden="true" />
                                Rechercher dans l’annuaire
                            </Link>
                        </Button>
                    </div>
                </div>

                <div v-if="profiles.length" class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <ProfessionalSpotlight
                        v-for="(professional, index) in profiles"
                        :key="professional.id"
                        v-reveal="Math.min(index, 5) * 90"
                        :professional="professional"
                    />

                    <article
                        v-if="profiles.length < 3"
                        v-reveal="120"
                        class="flex h-full flex-col justify-center rounded-2xl border border-dashed border-border bg-card/60 p-6 text-center"
                        :class="profiles.length === 1 ? 'lg:col-span-2' : ''"
                    >
                        <span class="mx-auto flex size-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-950/60 dark:text-brand-300">
                            <Store class="size-5" aria-hidden="true" />
                        </span>
                        <h3 class="mt-4 font-display text-lg font-semibold text-foreground">Vous êtes professionnel ?</h3>
                        <p class="mx-auto mt-1.5 max-w-sm text-sm leading-relaxed text-muted-foreground">
                            Créez votre profil, présentez vos prestations et rejoignez un annuaire construit sur la confiance.
                        </p>
                        <div class="mt-5">
                            <Button as-child variant="brand">
                                <Link :href="ctaProHref">
                                    Créer mon profil
                                    <ArrowRight class="size-4" aria-hidden="true" />
                                </Link>
                            </Button>
                        </div>
                    </article>
                </div>

                <div v-else v-reveal class="mt-10 rounded-2xl border border-dashed border-border bg-card p-8 text-center sm:p-12">
                    <p class="font-display text-lg font-semibold text-foreground">L’annuaire se construit</p>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-muted-foreground">
                        Aucun profil vérifié n’est encore publié. Créez le vôtre pour rejoindre le catalogue dès sa validation.
                    </p>
                    <Button as-child variant="brand" class="mt-6">
                        <Link :href="ctaProHref">
                            Créer mon compte
                            <ArrowRight class="size-4" aria-hidden="true" />
                        </Link>
                    </Button>
                </div>
            </div>
        </section>

        <!-- ============ CARTE DES PROFESSIONNELS ============ -->
        <section id="carte" class="scroll-mt-24 border-t border-border/60 py-16 sm:py-20 lg:py-24">
            <div class="page-container">
                <SectionHeader
                    v-reveal
                    align="center"
                    eyebrow="Carte"
                    title="Les professionnels près de chez vous."
                    description="Visualisez la position des professionnels référencés à Bamako et dans les environs."
                />

                <div v-reveal="120" class="mx-auto mt-10 max-w-4xl">
                    <LocationMap
                        v-if="mapMarkers.length"
                        :markers="mapMarkers"
                        height="360px"
                    />
                    <div v-else class="rounded-2xl border border-dashed border-border bg-card p-8 text-center">
                        <p class="font-display text-lg font-semibold text-foreground">La carte se construit</p>
                        <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-muted-foreground">
                            Les professionnels apparaîtront ici dès qu'ils auront renseigné leur position sur leur profil.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============ FONCTIONNEMENT ============ -->
        <section id="fonctionnement" class="scroll-mt-24 border-t border-border/60 py-16 sm:py-20 lg:py-24">
            <div class="page-container">
                <SectionHeader
                    v-reveal
                    align="center"
                    eyebrow="Comment ça marche"
                    title="Du besoin au contact, en trois étapes."
                    description="Une expérience fluide, pensée pour aller droit au but et vous faire gagner du temps."
                />

                <div v-reveal class="relative mt-12 grid gap-5 md:grid-cols-3">
                    <div
                        class="pointer-events-none absolute left-[16%] right-[16%] top-9 hidden h-px overflow-hidden md:block"
                        aria-hidden="true"
                    >
                        <span class="step-connector block h-full w-0 bg-brand-400/70 transition-[width] duration-700 ease-out" />
                    </div>

                    <article
                        v-for="(step, index) in steps"
                        :key="step.title"
                        v-reveal="index * 120"
                        class="landing-float-card relative rounded-2xl border border-border/70 bg-card p-6 shadow-soft"
                        :style="{ '--float-delay': `${index * 180}ms` }"
                    >
                        <div class="flex items-center justify-between">
                            <span class="step-icon flex size-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600 ring-1 ring-inset ring-brand-100 dark:bg-brand-950/60 dark:text-brand-300 dark:ring-brand-900">
                                <component :is="step.icon" class="size-5" aria-hidden="true" />
                            </span>
                            <span class="font-display text-3xl font-semibold text-border" aria-hidden="true">0{{ index + 1 }}</span>
                        </div>

                        <h3 class="mt-5 font-display text-lg font-semibold text-foreground">{{ step.title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-muted-foreground">{{ step.description }}</p>
                    </article>
                </div>

                <div v-reveal class="mt-8 flex justify-center">
                    <Button as-child variant="brand" size="lg" class="h-12 px-6">
                        <Link :href="route('search')">
                            Rechercher un professionnel
                            <ArrowRight class="size-4" aria-hidden="true" />
                        </Link>
                    </Button>
                </div>
            </div>
        </section>

        <!-- ============ APERÇU MOBILE ============ -->
        <section id="apercu" class="scroll-mt-24 border-t border-border/60 py-16 sm:py-20 lg:py-24">
            <div class="page-container">
                <SectionHeader
                    v-reveal
                    align="center"
                    eyebrow="L'expérience"
                    title="L'annuaire dans votre poche."
                    description="Une interface pensée pour le mobile : recherche rapide, profils clairs et contact en un geste."
                />

                <div v-reveal="120" class="mx-auto mt-10 max-w-sm">
                    <DevicePreview />
                </div>
            </div>
        </section>

        <!-- ============ TRANSPARENCE DES TARIFS ============ -->
        <section id="tarifs" class="scroll-mt-24 border-t border-border/60 bg-muted/20 py-16 sm:py-20 lg:py-24">
            <div class="page-container">
                <div class="grid items-center gap-12 lg:grid-cols-[1fr_0.9fr] lg:gap-16">
                    <div v-reveal>
                        <SectionHeader
                            eyebrow="Transparence"
                            title="Plus de transparence avant de vous engager."
                            description="Comparez les prestations et les tarifs annoncés par les professionnels avant de les contacter. Quand un prix dépend du projet, la mention « sur devis » est affichée clairement."
                        />

                        <ul class="mt-6 space-y-3">
                            <li class="flex items-start gap-2.5 text-sm text-muted-foreground">
                                <CheckCircle2 class="mt-0.5 size-4 shrink-0 text-brand-600 dark:text-brand-400" aria-hidden="true" />
                                Consultez les prix publiés par chaque professionnel.
                            </li>
                            <li class="flex items-start gap-2.5 text-sm text-muted-foreground">
                                <CheckCircle2 class="mt-0.5 size-4 shrink-0 text-brand-600 dark:text-brand-400" aria-hidden="true" />
                                Comparez les prestations avant de choisir.
                            </li>
                            <li class="flex items-start gap-2.5 text-sm text-muted-foreground">
                                <CheckCircle2 class="mt-0.5 size-4 shrink-0 text-brand-600 dark:text-brand-400" aria-hidden="true" />
                                Demandez un devis personnalisé quand le besoin le nécessite.
                            </li>
                        </ul>

                        <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                            <Button as-child variant="brand" size="lg" class="h-12 px-6">
                                <Link :href="route('search')">
                                    Comparer les professionnels
                                    <ArrowRight class="size-4" aria-hidden="true" />
                                </Link>
                            </Button>
                        </div>
                    </div>

                    <!-- Fiche tarifaire : exemple réel quand il existe -->
                    <div v-reveal="120" class="relative">
                        <div class="pointer-events-none absolute -inset-4 rounded-3xl bg-brand-100/60 blur-2xl dark:bg-brand-950/40" aria-hidden="true" />

                        <div class="landing-float-card relative rounded-2xl border border-border/70 bg-card p-5 shadow-elevated sm:p-6">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="flex size-9 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-950/60 dark:text-brand-300">
                                        <ReceiptText class="size-4" aria-hidden="true" />
                                    </span>
                                    <div>
                                        <p class="font-display text-sm font-semibold text-foreground">Fiche tarifaire</p>
                                        <p class="text-[11px] text-muted-foreground">Exemple issu du catalogue</p>
                                    </div>
                                </div>
                                <span class="rounded-full border border-border/70 bg-muted/60 px-2.5 py-0.5 text-[10px] font-medium text-muted-foreground">
                                    Prestation
                                </span>
                            </div>

                            <dl class="mt-5 divide-y divide-border/60 border-y border-border/60">
                                <div class="flex items-center justify-between gap-4 py-3 text-sm">
                                    <dt class="min-w-0 truncate text-foreground/90">
                                        {{ priceExample?.service_name ?? 'Prestation au catalogue' }}
                                    </dt>
                                    <dd class="shrink-0 font-medium text-foreground">
                                        {{ priceExample?.formatted_price ?? 'Prix communiqué par le professionnel' }}
                                    </dd>
                                </div>
                                <div class="flex items-center justify-between gap-4 py-3 text-sm">
                                    <dt class="text-muted-foreground">Durée</dt>
                                    <dd class="text-muted-foreground">Selon la prestation</dd>
                                </div>
                                <div class="flex items-center justify-between gap-4 py-3 text-sm">
                                    <dt class="text-muted-foreground">Zone d’intervention</dt>
                                    <dd class="text-muted-foreground">Bamako et environs</dd>
                                </div>
                            </dl>

                            <div class="mt-4 flex items-start gap-2.5 rounded-xl bg-brand-50/70 p-3 text-[11px] leading-relaxed text-brand-800 dark:bg-brand-950/40 dark:text-brand-200">
                                <ShieldCheck class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                                <span>Les tarifs affichés sont renseignés par les professionnels et vérifiés lors de la validation du profil.</span>
                            </div>

                            <Button v-if="priceExample" as-child variant="outline" size="sm" class="mt-4 w-full">
                                <Link :href="route('professionals.show', priceExample.professional_slug)">
                                    Voir le profil de {{ priceExample.professional_name }}
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============ ESPACE PROFESSIONNEL ============ -->
        <section id="pro" class="scroll-mt-24 py-16 sm:py-20 lg:py-24">
            <div class="page-container">
                <div v-reveal class="relative overflow-hidden rounded-3xl bg-brand-900 px-6 py-10 text-white sm:px-10 sm:py-12 lg:px-14 lg:py-16">
                    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                        <div class="absolute -right-24 -top-24 size-72 rounded-full bg-brand-500/20 blur-3xl" />
                        <div class="absolute -bottom-28 -left-10 size-72 rounded-full bg-gold-500/10 blur-3xl" />
                        <div class="arch-frame absolute -right-10 top-10 hidden h-64 w-44 border border-white/10 lg:block" />
                    </div>

                    <div class="relative grid items-center gap-10 lg:grid-cols-[1fr_0.85fr] lg:gap-14">
                        <div>
                            <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-[11px] font-semibold text-gold-200">
                                <Sparkles class="size-3.5" aria-hidden="true" />
                                Espace professionnel
                            </span>

                            <h2 class="mt-5 max-w-xl text-balance font-display text-3xl font-semibold leading-tight tracking-tight sm:text-4xl">
                                Votre savoir-faire mérite d’être vu.
                            </h2>
                            <p class="mt-4 max-w-xl text-pretty text-sm leading-relaxed text-brand-100/85 sm:text-base">
                                Un espace dédié pour présenter vos services, publier vos tarifs, recevoir des demandes et suivre votre activité au
                                quotidien.
                            </p>

                            <ul class="mt-6 grid gap-3 sm:grid-cols-2">
                                <li v-for="benefit in proBenefits" :key="benefit.label" class="flex items-center gap-2.5 text-sm text-brand-50/90">
                                    <component :is="benefit.icon" class="size-4 shrink-0 text-gold-300" aria-hidden="true" />
                                    {{ benefit.label }}
                                </li>
                            </ul>

                            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                                <Button as-child size="lg" class="h-12 bg-white px-6 text-brand-900 shadow-none hover:bg-brand-50">
                                    <Link :href="ctaProHref">
                                        Développer mon activité
                                        <ArrowRight class="size-4" aria-hidden="true" />
                                    </Link>
                                </Button>
                                <Button
                                    as-child
                                    variant="ghost"
                                    size="lg"
                                    class="h-12 border border-white/15 px-6 text-white hover:bg-white/10 hover:text-white"
                                >
                                    <a href="#formules">Voir les formules</a>
                                </Button>
                            </div>
                        </div>

                        <ProDashboardPreview :plan="proPlan" :professionals="stats.professionals" />
                    </div>
                </div>
            </div>
        </section>

        <!-- ============ FORMULES ============ -->
        <section id="formules" class="scroll-mt-24 border-t border-border/60 bg-muted/20 py-16 sm:py-20 lg:py-24">
            <div class="page-container">
                <SectionHeader
                    v-reveal
                    align="center"
                    eyebrow="Formules professionnelles"
                    title="Grandissez à votre rythme."
                    description="Des formules lisibles, sans surprise. Commencez gratuitement, puis choisissez la formule adaptée à votre activité."
                />

                <div v-reveal="80" class="mt-8">
                    <PlansSection :plans="plans" />
                </div>
            </div>
        </section>

        <!-- ============ ENGAGEMENTS ============ -->
        <section id="confiance" class="scroll-mt-24 border-t border-border/60 py-16 sm:py-20 lg:py-24">
            <div class="page-container">
                <SectionHeader
                    v-reveal
                    align="center"
                    eyebrow="Nos engagements"
                    title="La confiance se construit sur des engagements clairs."
                    description="Pas d’avis inventés, pas de profils fantômes : MaliConnect s’appuie uniquement sur des informations réelles et vérifiées."
                />

                <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <article
                        v-for="(commitment, index) in commitments"
                        :key="commitment.title"
                        v-reveal="index * 90"
                        class="landing-float-card lift rounded-2xl border border-border/70 bg-card p-5 shadow-soft hover:border-brand-200 hover:shadow-card dark:hover:border-brand-800"
                        :style="{ '--float-delay': `${index * 140}ms` }"
                    >
                        <span class="lift-icon flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600 ring-1 ring-inset ring-brand-100 dark:bg-brand-950/60 dark:text-brand-300 dark:ring-brand-900">
                            <component :is="commitment.icon" class="size-5" aria-hidden="true" />
                        </span>
                        <h3 class="mt-4 font-display text-base font-semibold text-foreground">{{ commitment.title }}</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-muted-foreground">{{ commitment.description }}</p>
                    </article>
                </div>
            </div>
        </section>

        <!-- ============ APPEL À L’ACTION FINAL ============ -->
        <section class="border-t border-border/60 py-16 sm:py-20 lg:py-24">
            <div class="page-container">
                <div
                    v-reveal
                    class="relative overflow-hidden rounded-3xl border border-brand-200/70 bg-brand-50 px-6 py-12 text-center dark:border-brand-900 dark:bg-brand-950/30 sm:px-10 sm:py-16"
                >


                    <div class="relative">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-700 dark:text-brand-300">Rejoindre MaliConnect</p>

                        <h2 class="mx-auto mt-3 max-w-2xl text-balance font-display text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">
                            Le bon professionnel n’est peut-être qu’à quelques clics.
                        </h2>

                        <p class="mx-auto mt-3 max-w-xl text-pretty text-sm leading-relaxed text-muted-foreground sm:text-base">
                            Trouvez un professionnel près de chez vous, ou faites découvrir votre savoir-faire à toute la ville.
                        </p>

                        <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                            <Button as-child variant="brand" size="lg" class="h-12 px-6">
                                <Link :href="route('search')">
                                    Je recherche un professionnel
                                    <ArrowRight class="size-4" aria-hidden="true" />
                                </Link>
                            </Button>

                            <Button as-child variant="outline" size="lg" class="h-12 px-6">
                                <Link :href="ctaProHref">
                                    Je suis un professionnel
                                    <Users class="size-4 text-brand-600 dark:text-brand-400" aria-hidden="true" />
                                </Link>
                            </Button>
                        </div>

                        <p class="mt-5 text-xs text-muted-foreground">
                            Inscription gratuite · Profils vérifiés · Contact direct avec les professionnels
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>

<style scoped>
.landing-float-card.is-visible {
    animation: landing-float 5s ease-in-out var(--float-delay, 300ms) infinite;
}

.landing-float-card.is-visible:hover,
.landing-float-card.is-visible:focus-within {
    animation-play-state: paused;
}

@keyframes landing-float {
    0%,
    100% {
        transform: translateY(0) scale(1);
    }
    50% {
        transform: translateY(-8px) scale(1.005);
    }
}

@media (prefers-reduced-motion: reduce) {
    .landing-float-card.is-visible {
        animation: none;
    }
}
</style>
