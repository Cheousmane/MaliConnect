<script setup lang="ts">
import DevicePreview from '@/components/DevicePreview.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { usePreviewMode } from '@/composables/usePreviewMode';
import PublicLayout from '@/layouts/public/PublicLayout.vue';
import type { SharedData, StatusTone } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';
import {
    ArrowRight,
    BadgeCheck,
    Briefcase,
    Car,
    CheckCircle2,
    ChevronDown,
    ClipboardList,
    Home,
    Inbox,
    LayoutDashboard,
    MapPin,
    MessageSquare,
    MessagesSquare,
    Monitor,
    MousePointerClick,
    Scissors,
    Search,
    ShieldCheck,
    Sparkles,
    Store,
    UserRound,
    Users,
    Wallet,
    Wrench,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Feature {
    title: string;
    description: string;
    icon: LucideIcon;
    status: string;
    tone: StatusTone;
}

type CategoryItem = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    icon: string | null;
    color: string | null;
    subcategories_count: number;
};

type ProfileItem = {
    id: number;
    business_name: string;
    slug: string;
    city: string;
    commune: string | null;
    category: string | null;
    subcategory: string | null;
    rating_average: number;
    rating_count: number;
    is_available: boolean;
};

defineProps<{
    categories: CategoryItem[];
    profiles: ProfileItem[];
    stats: { categories: number; subcategories: number; professionals: number };
    plans: Array<{
        id: number;
        code: string;
        name: string;
        description: string | null;
        price_monthly: number;
        price_yearly: number;
        currency: string;
        max_services: number;
        max_media: number;
        max_profiles: number;
        max_pricing_sheets: number;
        formatted_monthly: string;
        formatted_yearly: string;
        is_free: boolean;
        features: {
            advanced_stats: boolean;
            gallery: boolean;
            collaborators: boolean;
            priority_visibility: boolean;
            priority_support: boolean;
        };
    }>;
}>();

const page = usePage<SharedData>();
const authUser = computed(() => page.props.auth?.user ?? null);
const isPreviewMode = usePreviewMode();

// Mot-clé de la recherche publique.
const keyword = ref('');

const search = () => {
    router.get(route('search'), keyword.value ? { q: keyword.value } : {}, { preserveScroll: true });
};

// Icône associée à chaque catégorie du catalogue.
const categoryIcons: Record<string, LucideIcon> = {
    home: Home,
    monitor: Monitor,
    car: Car,
    scissors: Scissors,
    briefcase: Briefcase,
};

const iconFor = (name: string | null): LucideIcon => (name && categoryIcons[name]) || Store;

const features: Feature[] = [
    {
        title: 'Recherche publique',
        description: 'Trouvez un professionnel par mot-clé, catégorie, commune, quartier, prix ou disponibilité.',
        icon: Search,
        status: 'Disponible',
        tone: 'success',
    },
    {
        title: 'Profils vérifiés',
        description: 'Chaque profil est contrôlé par l’administration avant d’être présenté publiquement.',
        icon: BadgeCheck,
        status: 'Disponible',
        tone: 'success',
    },
    {
        title: 'Contact direct',
        description: 'Échangez directement avec le professionnel, sans intermédiaire inutile.',
        icon: MessagesSquare,
        status: 'Bientôt',
        tone: 'neutral',
    },
    {
        title: 'Demandes et devis',
        description: 'Décrivez votre besoin et recevez des réponses adaptées à votre projet.',
        icon: ClipboardList,
        status: 'Bientôt',
        tone: 'neutral',
    },
    {
        title: 'Espace professionnel',
        description: 'Un tableau de bord pour présenter vos services et suivre votre activité.',
        icon: LayoutDashboard,
        status: 'Aperçu',
        tone: 'brand',
    },
    {
        title: 'Compte sécurisé',
        description: 'Inscription, connexion et gestion de votre profil en toute simplicité.',
        icon: ShieldCheck,
        status: 'Disponible',
        tone: 'success',
    },
];

const steps = [
    {
        title: 'Décrivez votre besoin',
        description: 'Choisissez une catégorie, précisez votre ville et partagez quelques détails utiles.',
        icon: Search,
    },
    {
        title: 'Trouvez le bon professionnel',
        description: 'Consultez des profils clairs et des informations vérifiées près de chez vous.',
        icon: Users,
    },
    {
        title: 'Échangez en confiance',
        description: 'Contactez directement la personne qui vous convient et convenez des prochaines étapes.',
        icon: MessagesSquare,
    },
];

const proBenefits = ['Un profil professionnel clair', 'Une visibilité locale', 'Des demandes mieux qualifiées', 'Un suivi simple au quotidien'];

const proProfileItems: Array<{ label: string; hint: string; icon: LucideIcon }> = [
    { label: 'Présentation et services', hint: 'À compléter', icon: UserRound },
    { label: 'Zone d’intervention', hint: 'Bamako', icon: MapPin },
    { label: 'Demandes reçues', hint: 'Bientôt', icon: Inbox },
    { label: 'Messages', hint: 'Bientôt', icon: MessageSquare },
];

const previewBenefits = ['Navigation simple et accessible', 'Lecture confortable sur petit écran', 'Même expérience sur ordinateur et tablette'];
</script>

<template>
    <Head title="Le bon professionnel, au bon moment" />

    <PublicLayout>
        <!-- Héro -->
        <section id="accueil" class="scroll-mt-24">
            <div class="page-container pb-16 pt-14 sm:pb-20 sm:pt-20 lg:pb-24 lg:pt-24">
                <div class="grid items-center gap-12 lg:grid-cols-[1.05fr_0.95fr] lg:gap-16">
                    <div class="max-w-2xl">
                        <!-- <div
                            class="inline-flex items-center gap-2 rounded-full border border-border/80 bg-muted/40 px-3 py-1.5 text-[11px] font-medium text-muted-foreground"
                        >
                            <span class="size-1.5 rounded-full bg-brand-500" aria-hidden="true" />
                            Projet en cours de développement
                        </div> -->

                        <h1
                            class="mt-6 text-balance font-display text-[38px] font-semibold leading-[1.08] tracking-[-0.03em] text-foreground sm:text-5xl lg:text-[56px]"
                        >
                            Le bon professionnel,
                            <span class="text-brand-700 dark:text-brand-300">au bon moment.</span>
                        </h1>

                        <p class="mt-5 max-w-xl text-pretty text-base leading-relaxed text-muted-foreground sm:text-lg">
                            MaliConnect prépare une plateforme simple et humaine pour rapprocher les particuliers des professionnels de confiance,
                            partout au Mali.
                        </p>
                        <p class="mt-3 max-w-xl text-pretty text-sm leading-relaxed text-muted-foreground">
                            Parcourez le catalogue des professionnels vérifiés, ou découvrez la vision du projet et un aperçu réel de l’expérience
                            mobile.
                        </p>

                        <form class="mt-7 flex max-w-xl items-center gap-2" @submit.prevent="search">
                            <div class="relative flex-1">
                                <Search
                                    class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                                    aria-hidden="true"
                                />
                                <input
                                    v-model="keyword"
                                    type="search"
                                    placeholder="Plombier, ménage, cours de maths…"
                                    class="h-12 w-full rounded-lg border border-input bg-background pl-10 pr-3.5 text-[15px] text-foreground shadow-[0_1px_2px_0_rgb(16_24_40_/_0.03)]"
                                    aria-label="Rechercher un professionnel"
                                />
                            </div>
                            <Button type="submit" variant="brand" size="lg" class="h-12 px-6 text-[15px]">Rechercher</Button>
                        </form>

                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            <Button as-child variant="brand" size="lg" class="h-12 gap-2 px-6 text-[15px]">
                                <a href="#fonctionnalites">
                                    Découvrir les fonctionnalités
                                    <ArrowRight class="size-4" aria-hidden="true" />
                                </a>
                            </Button>
                            <Button as-child variant="outline" size="lg" class="h-12 gap-2 px-6 text-[15px]">
                                <Link :href="authUser ? route('dashboard') : route('register')">
                                    {{ authUser ? 'Accéder à mon espace' : 'Créer mon compte' }}
                                    <BadgeCheck class="size-4 text-brand-600 dark:text-brand-400" aria-hidden="true" />
                                </Link>
                            </Button>
                        </div>

                        <ul class="mt-8 flex flex-wrap gap-x-6 gap-y-3 text-xs text-muted-foreground">
                            <li class="flex items-center gap-2">
                                <Users class="size-4 text-brand-600 dark:text-brand-400" aria-hidden="true" />
                                Une mise en relation simple
                            </li>
                            <li class="flex items-center gap-2">
                                <MessagesSquare class="size-4 text-brand-600 dark:text-brand-400" aria-hidden="true" />
                                Un contact direct
                            </li>
                            <li class="flex items-center gap-2">
                                <MapPin class="size-4 text-brand-600 dark:text-brand-400" aria-hidden="true" />
                                Pensé pour le Mali
                            </li>
                        </ul>
                    </div>

                    <!-- Aperçu de l’interface -->
                    <div class="mx-auto w-full max-w-md lg:max-w-none">
                        <div class="rounded-2xl border border-border/80 bg-card p-5 shadow-card sm:p-6">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-muted-foreground">Aperçu de l’interface</p>
                                    <p class="mt-1 font-display text-sm font-semibold text-foreground">Trouver un professionnel</p>
                                </div>
                                <StatusBadge tone="neutral" label="Maquette" />
                            </div>

                            <div class="mt-5 space-y-3">
                                <div class="rounded-xl border border-border/80 bg-muted/30 p-3.5">
                                    <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">Service</p>
                                    <div class="mt-1.5 flex items-center gap-2.5">
                                        <Wrench class="size-4 text-brand-600 dark:text-brand-400" aria-hidden="true" />
                                        <span class="text-sm font-medium text-foreground">Plomberie</span>
                                        <ChevronDown class="ml-auto size-4 text-muted-foreground" aria-hidden="true" />
                                    </div>
                                </div>

                                <div class="rounded-xl border border-border/80 bg-muted/30 p-3.5">
                                    <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">Ville</p>
                                    <div class="mt-1.5 flex items-center gap-2.5">
                                        <MapPin class="size-4 text-brand-600 dark:text-brand-400" aria-hidden="true" />
                                        <span class="text-sm font-medium text-foreground">Bamako</span>
                                        <ChevronDown class="ml-auto size-4 text-muted-foreground" aria-hidden="true" />
                                    </div>
                                </div>

                                <div class="flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-3 text-sm font-medium text-white">
                                    <Search class="size-4" aria-hidden="true" />
                                    Rechercher
                                </div>
                            </div>

                            <div class="mt-5 border-t border-border/70 pt-4">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">Catégories</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <span
                                        v-for="category in categories.slice(0, 4)"
                                        :key="category.id"
                                        class="inline-flex items-center gap-1.5 rounded-full border border-border/70 bg-background px-3 py-1.5 text-xs text-muted-foreground"
                                    >
                                        <component
                                            :is="iconFor(category.icon)"
                                            class="size-3.5 text-brand-600 dark:text-brand-400"
                                            aria-hidden="true"
                                        />
                                        {{ category.name }}
                                    </span>
                                </div>
                            </div>

                            <div
                                class="mt-5 flex items-start gap-2.5 rounded-xl bg-brand-50/80 p-3 text-[11px] leading-relaxed text-brand-800 dark:bg-brand-950/40 dark:text-brand-200"
                            >
                                <ShieldCheck class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                                <span>Des profils clairs, un contact direct et une expérience pensée pour le Mali.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Fonctionnalités -->
        <section id="fonctionnalites" class="scroll-mt-24 border-t border-border/60 bg-muted/20 py-16 sm:py-20 lg:py-24">
            <div class="page-container">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-700 dark:text-brand-300">Ce que nous construisons</p>
                    <h2 class="mt-3 text-balance font-display text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">
                        Une plateforme pensée pour la confiance.
                    </h2>
                    <p class="mt-3 text-pretty text-sm leading-relaxed text-muted-foreground sm:text-base">
                        MaliConnect prépare les outils essentiels pour trouver, contacter et travailler avec les bons professionnels, en toute
                        simplicité.
                    </p>
                </div>

                <div class="mt-10 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <article
                        v-for="feature in features"
                        :key="feature.title"
                        class="rounded-2xl border border-border/70 bg-card p-5 shadow-soft transition-colors hover:border-brand-200 dark:hover:border-brand-800"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <span
                                class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 ring-1 ring-inset ring-brand-100 dark:bg-brand-950/50 dark:text-brand-300 dark:ring-brand-900"
                            >
                                <component :is="feature.icon" class="size-5" aria-hidden="true" />
                            </span>
                            <StatusBadge :tone="feature.tone" :label="feature.status" />
                        </div>
                        <h3 class="mt-4 font-display text-base font-semibold text-foreground">{{ feature.title }}</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-muted-foreground">{{ feature.description }}</p>
                    </article>
                </div>

                <p class="mx-auto mt-6 max-w-2xl text-center text-xs leading-relaxed text-muted-foreground">
                    Les fonctionnalités marquées « Bientôt » seront activées progressivement. Aucune date de lancement n’est annoncée à ce stade.
                </p>
            </div>
        </section>

        <!-- Catégories -->
        <section id="categories" class="scroll-mt-24 py-16 sm:py-20 lg:py-24">
            <div class="page-container">
                <div class="max-w-2xl">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-700 dark:text-brand-300">Les métiers du quotidien</p>
                    <h2 class="mt-3 text-balance font-display text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">
                        Des savoir-faire pour chaque besoin.
                    </h2>
                    <p class="mt-3 text-pretty text-sm leading-relaxed text-muted-foreground sm:text-base">
                        Le catalogue couvre la maison, la technologie, l’automobile, les services personnels et les professionnels.
                    </p>
                </div>

                <div class="mt-10 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="category in categories"
                        :key="category.id"
                        :href="route('categories.show', category.slug)"
                        class="flex items-start gap-3 rounded-xl border border-border/70 bg-card p-4 transition-colors hover:border-brand-200 dark:hover:border-brand-800"
                    >
                        <span
                            class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600 ring-1 ring-inset ring-brand-100 dark:bg-brand-950/50 dark:text-brand-300 dark:ring-brand-900"
                        >
                            <component :is="iconFor(category.icon)" class="size-5" aria-hidden="true" />
                        </span>
                        <div class="min-w-0">
                            <h3 class="font-display text-sm font-semibold text-foreground">{{ category.name }}</h3>
                            <p class="mt-0.5 text-xs leading-relaxed text-muted-foreground">{{ category.description }}</p>
                            <p class="mt-1.5 text-[11px] font-medium text-muted-foreground/80">
                                {{ category.subcategories_count }} sous-catégorie{{ category.subcategories_count > 1 ? 's' : '' }}
                            </p>
                        </div>
                    </Link>
                </div>

                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <Button as-child variant="outline" size="sm">
                        <Link :href="route('categories.index')">Voir tout le catalogue</Link>
                    </Button>
                    <p class="text-xs text-muted-foreground">D’autres catégories seront ajoutées progressivement.</p>
                </div>
            </div>
        </section>

        <!-- Professionnels vérifiés -->
        <section id="annuaire" class="scroll-mt-24 border-t border-border/60 bg-muted/20 py-16 sm:py-20 lg:py-24">
            <div class="page-container">
                <div class="max-w-2xl">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-700 dark:text-brand-300">L’annuaire</p>
                    <h2 class="mt-3 text-balance font-display text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">
                        Des professionnels vérifiés par nos soins.
                    </h2>
                    <p class="mt-3 text-pretty text-sm leading-relaxed text-muted-foreground sm:text-base">
                        Chaque profil passe par une vérification administrative avant d’être présenté publiquement. Un profil en attente n’est jamais
                        visible comme vérifié.
                    </p>
                </div>

                <div v-if="profiles.length" class="mt-10 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="professional in profiles"
                        :key="professional.id"
                        :href="route('professionals.show', professional.slug)"
                        class="flex items-start justify-between gap-3 rounded-xl border border-border/70 bg-card p-4 transition-colors hover:border-brand-200 dark:hover:border-brand-800"
                    >
                        <div class="min-w-0">
                            <p class="truncate font-display text-sm font-semibold text-foreground">{{ professional.business_name }}</p>
                            <p class="mt-0.5 truncate text-xs text-muted-foreground">
                                {{ professional.category }}<span v-if="professional.subcategory"> · {{ professional.subcategory }}</span>
                            </p>
                            <p class="mt-1.5 flex items-center gap-1.5 text-xs text-muted-foreground">
                                <MapPin class="size-3.5" aria-hidden="true" />
                                {{ professional.city }}<span v-if="professional.commune"> · {{ professional.commune }}</span>
                            </p>
                        </div>
                        <StatusBadge tone="success" label="Vérifié" class="shrink-0" />
                    </Link>
                </div>

                <div v-else class="mt-10 rounded-2xl border border-dashed border-border bg-card p-8 text-center">
                    <p class="text-sm font-medium text-foreground">L’annuaire se construit</p>
                    <p class="mx-auto mt-1 max-w-md text-sm text-muted-foreground">
                        Aucun profil vérifié n’est encore publié. Créez le vôtre pour rejoindre le catalogue dès sa validation.
                    </p>
                    <Button as-child variant="brand" class="mt-5">
                        <Link :href="authUser ? route('pro.profiles.index') : route('register')">
                            {{ authUser ? 'Créer mon profil' : 'Créer mon compte' }}
                        </Link>
                    </Button>
                </div>

                <div class="mt-6">
                    <Button as-child variant="outline" size="sm">
                        <Link :href="route('search')">Rechercher un professionnel</Link>
                    </Button>
                </div>
            </div>
        </section>

        <!-- Fonctionnement -->
        <section id="fonctionnement" class="scroll-mt-24 border-t border-border/60 bg-muted/20 py-16 sm:py-20 lg:py-24">
            <div class="page-container">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-700 dark:text-brand-300">Un parcours simple</p>
                    <h2 class="mt-3 text-balance font-display text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">
                        Du besoin au contact, en trois étapes.
                    </h2>
                    <p class="mt-3 text-pretty text-sm leading-relaxed text-muted-foreground sm:text-base">
                        Une expérience fluide, pensée pour aller droit au but et vous faire gagner du temps.
                    </p>
                </div>

                <div class="mt-10 grid gap-4 md:grid-cols-3">
                    <div v-for="(step, index) in steps" :key="step.title" class="rounded-2xl border border-border/70 bg-card p-6 shadow-soft">
                        <div class="flex items-center justify-between">
                            <span
                                class="flex size-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600 ring-1 ring-inset ring-brand-100 dark:bg-brand-950/50 dark:text-brand-300 dark:ring-brand-900"
                            >
                                <component :is="step.icon" class="size-5" aria-hidden="true" />
                            </span>
                            <span class="font-display text-3xl font-semibold text-border">0{{ index + 1 }}</span>
                        </div>
                        <h3 class="mt-5 font-display text-lg font-semibold text-foreground">{{ step.title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-muted-foreground">{{ step.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Espace professionnel -->
        <section id="professionnels" class="scroll-mt-24 py-16 sm:py-20 lg:py-24">
            <div class="page-container">
                <div class="rounded-3xl bg-brand-900 px-6 py-10 text-white sm:px-10 sm:py-12 lg:px-14 lg:py-16">
                    <div class="grid items-center gap-10 lg:grid-cols-[1fr_0.85fr] lg:gap-16">
                        <div>
                            <span
                                class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-[11px] font-semibold text-gold-200"
                            >
                                <Sparkles class="size-3.5" aria-hidden="true" />
                                Espace professionnel · Aperçu
                            </span>

                            <h2 class="mt-5 max-w-xl text-balance font-display text-3xl font-semibold leading-tight tracking-tight sm:text-4xl">
                                Votre savoir-faire mérite d’être vu.
                            </h2>
                            <p class="mt-4 max-w-xl text-pretty text-sm leading-relaxed text-brand-100/85 sm:text-base">
                                MaliConnect prépare un espace dédié aux professionnels : un profil soigné, une présentation claire de vos services et
                                un suivi simple de vos demandes.
                            </p>

                            <ul class="mt-6 grid gap-3 sm:grid-cols-2">
                                <li v-for="benefit in proBenefits" :key="benefit" class="flex items-center gap-2.5 text-sm text-brand-50/90">
                                    <CheckCircle2 class="size-4 shrink-0 text-gold-300" aria-hidden="true" />
                                    {{ benefit }}
                                </li>
                            </ul>

                            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                                <Button as-child size="lg" class="h-12 bg-white px-6 text-brand-900 shadow-none hover:bg-brand-50">
                                    <Link :href="authUser ? route('dashboard') : route('register')">
                                        {{ authUser ? 'Accéder à mon espace' : 'Créer mon espace pro' }}
                                        <ArrowRight class="size-4" aria-hidden="true" />
                                    </Link>
                                </Button>
                                <Button
                                    as-child
                                    variant="ghost"
                                    size="lg"
                                    class="h-12 border border-white/15 px-6 text-white hover:bg-white/10 hover:text-white"
                                >
                                    <a href="mailto:pro@maliconnect.ml">Parler à l’équipe</a>
                                </Button>
                            </div>
                        </div>

                        <div class="mx-auto w-full max-w-sm">
                            <div class="rounded-2xl border border-white/15 bg-white/[0.06] p-5">
                                <div class="flex items-center gap-3">
                                    <span
                                        class="flex size-12 shrink-0 items-center justify-center rounded-full bg-white/10 text-gold-200 ring-1 ring-inset ring-white/15"
                                    >
                                        <UserRound class="size-5" aria-hidden="true" />
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold">Votre profil professionnel</p>
                                        <p class="text-xs text-brand-100/80">Prêt à présenter votre savoir-faire</p>
                                    </div>
                                </div>

                                <div class="mt-5 space-y-2.5">
                                    <div
                                        v-for="item in proProfileItems"
                                        :key="item.label"
                                        class="flex items-center justify-between gap-3 rounded-xl bg-white/[0.06] px-3.5 py-3"
                                    >
                                        <span class="flex min-w-0 items-center gap-2.5 text-xs text-brand-50/90">
                                            <component :is="item.icon" class="size-4 shrink-0 text-gold-300" aria-hidden="true" />
                                            <span class="truncate">{{ item.label }}</span>
                                        </span>
                                        <span class="shrink-0 text-[10px] font-medium text-brand-200/70">{{ item.hint }}</span>
                                    </div>
                                </div>

                                <div
                                    class="mt-5 flex items-start gap-2.5 rounded-xl border border-white/10 bg-white/[0.04] px-3.5 py-3 text-[11px] leading-relaxed text-brand-100/80"
                                >
                                    <ShieldCheck class="mt-0.5 size-4 shrink-0 text-gold-300" aria-hidden="true" />
                                    <span>Vérification d’identité et informations claires pour inspirer confiance.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Aperçu mobile -->
        <section v-if="!isPreviewMode" id="apercu" class="scroll-mt-24 border-t border-border/60 bg-muted/20 py-16 sm:py-20 lg:py-24">
            <div class="page-container">
                <div class="grid items-center gap-12 lg:grid-cols-[0.85fr_1.15fr] lg:gap-16">
                    <div class="max-w-xl">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-700 dark:text-brand-300">L’expérience mobile</p>
                        <h2 class="mt-3 text-balance font-display text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">
                            Pensé pour le mobile, dès le premier jour.
                        </h2>
                        <p class="mt-3 text-pretty text-sm leading-relaxed text-muted-foreground sm:text-base">
                            Une interface claire, rapide et confortable sur smartphone. Explorez l’aperçu réel de MaliConnect et basculez entre les
                            formats mobile et ordinateur.
                        </p>

                        <ul class="mt-6 space-y-3">
                            <li v-for="benefit in previewBenefits" :key="benefit" class="flex items-center gap-2.5 text-sm text-muted-foreground">
                                <CheckCircle2 class="size-4 shrink-0 text-brand-600 dark:text-brand-400" aria-hidden="true" />
                                {{ benefit }}
                            </li>
                        </ul>

                        <p class="mt-6 flex items-start gap-2 text-xs leading-relaxed text-muted-foreground">
                            <MousePointerClick class="mt-0.5 size-4 shrink-0 text-brand-600 dark:text-brand-400" aria-hidden="true" />
                            Aperçu interactif : faites défiler à l’intérieur de l’écran et utilisez la navigation comme sur un vrai smartphone.
                        </p>
                    </div>

                    <div
                        class="-mx-4 rounded-none border-x-0 bg-background/70 p-3 shadow-none sm:mx-0 sm:rounded-3xl sm:border sm:p-6 sm:shadow-soft lg:p-8"
                    >
                        <DevicePreview />
                    </div>
                </div>
            </div>
        </section>

        <!-- Appel à l’action final -->
        <section class="py-16 sm:py-20 lg:py-24">
            <div class="page-container">
                <div
                    class="rounded-3xl border border-brand-100 bg-brand-50/70 px-6 py-12 text-center dark:border-brand-900 dark:bg-brand-950/30 sm:px-10 sm:py-16"
                >
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-700 dark:text-brand-300">Rejoindre le projet</p>
                    <h2 class="mx-auto mt-3 max-w-2xl text-balance font-display text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">
                        Le bon professionnel n’est peut-être qu’à quelques clics.
                    </h2>
                    <p class="mx-auto mt-3 max-w-xl text-pretty text-sm leading-relaxed text-muted-foreground sm:text-base">
                        Créez votre compte, découvrez l’aperçu de l’espace professionnel et suivez les prochaines étapes de MaliConnect.
                    </p>

                    <div class="mt-7 flex flex-col justify-center gap-3 sm:flex-row">
                        <Button as-child variant="brand" size="lg" class="h-12 px-6">
                            <Link :href="authUser ? route('dashboard') : route('register')">
                                {{ authUser ? 'Accéder à mon espace' : 'Créer mon compte' }}
                                <ArrowRight class="size-4" aria-hidden="true" />
                            </Link>
                        </Button>
                        <Button as-child variant="outline" size="lg" class="h-12 px-6">
                            <a href="#fonctionnalites">Revoir les fonctionnalités</a>
                        </Button>
                    </div>

                    <p class="mt-5 text-xs text-muted-foreground">Inscription gratuite · Projet en cours de développement.</p>
                </div>
            </div>
        </section>

        <!-- Abonnement SaaS -->
        <section
            id="abonnement"
            class="scroll-mt-24 border-t border-brand-200/40 bg-brand-50/30 py-16 dark:border-brand-900/40 dark:bg-brand-950/20 sm:py-20 lg:py-24"
        >
            <div class="page-container">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-700 dark:text-brand-300">Espace professionnel</p>
                    <h2 class="mt-3 text-balance font-display text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">
                        Gérez vos services, gagnez du temps.
                    </h2>
                    <p class="mt-3 text-pretty text-sm leading-relaxed text-muted-foreground sm:text-base">
                        Créez votre compte professionnel, présentez vos services et recevez des demandes qualifiées, pour un flux de travail optimisé.
                    </p>
                </div>

                <div class="mt-10 grid gap-6 md:grid-cols-3">
                    <article
                        v-for="plan in plans"
                        :key="plan.id"
                        class="rounded-2xl border p-6 shadow-soft transition-all duration-300 hover:-translate-y-1 hover:shadow-card"
                        :class="[
                            plan.code === 'pro'
                                ? 'border-brand-200/60 bg-gradient-to-br from-brand-50 to-white dark:from-brand-950/30 dark:to-background/60'
                                : plan.code === 'business'
                                  ? 'border-gold-200/60 bg-gradient-to-br from-gold-50 to-white dark:from-gold-950/30 dark:to-background/60'
                                  : 'border-border/70 bg-card',
                        ]"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="font-display text-xl font-semibold text-foreground">{{ plan.name }}</h3>
                                <p class="text-xs text-muted-foreground">
                                    {{ plan.is_free ? 'Gratuit' : `${plan.formatted_monthly} /mois · ${plan.formatted_yearly} /an` }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p
                                    class="text-2xl font-semibold"
                                    :class="[plan.code === 'pro' ? 'text-brand-700' : plan.code === 'business' ? 'text-gold-600' : 'text-brand-700']"
                                >
                                    {{ plan.is_free ? '0' : plan.formatted_monthly.replace(' FCFA', '') }}
                                </p>
                                <p class="text-xs text-muted-foreground">par mois</p>
                            </div>
                        </div>
                        <div class="mt-5 space-y-3">
                            <div class="flex items-center gap-2 text-sm" :class="plan.code === 'free' ? 'text-muted-foreground' : ''">
                                <CheckCircle2
                                    class="size-4 shrink-0"
                                    :class="[plan.code === 'pro' ? 'text-brand-600' : plan.code === 'business' ? 'text-gold-600' : 'text-success']"
                                    aria-hidden="true"
                                />
                                <span>{{ plan.max_profiles }} profil{{ plan.max_profiles > 1 ? 's' : '' }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm" :class="plan.code === 'free' ? 'text-muted-foreground' : ''">
                                <CheckCircle2
                                    class="size-4 shrink-0"
                                    :class="[plan.code === 'pro' ? 'text-brand-600' : plan.code === 'business' ? 'text-gold-600' : 'text-success']"
                                    aria-hidden="true"
                                />
                                <span>{{ plan.max_services === 0 ? 'Services illimités' : `${plan.max_services} services` }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm" :class="plan.code === 'free' ? 'text-muted-foreground' : ''">
                                <CheckCircle2
                                    class="size-4 shrink-0"
                                    :class="[plan.code === 'pro' ? 'text-brand-600' : plan.code === 'business' ? 'text-gold-600' : 'text-success']"
                                    aria-hidden="true"
                                />
                                <span>
                                    {{
                                        plan.max_pricing_sheets === 0
                                            ? 'Fiches illimitées'
                                            : `${plan.max_pricing_sheets} fiche${plan.max_pricing_sheets > 1 ? 's' : ''} tarifaire${plan.max_pricing_sheets > 1 ? 's' : ''}`
                                    }}
                                </span>
                            </div>
                            <div
                                v-if="plan.features.gallery"
                                class="flex items-center gap-2 text-sm"
                                :class="plan.code === 'free' ? 'text-muted-foreground' : ''"
                            >
                                <CheckCircle2
                                    class="size-4 shrink-0"
                                    :class="[plan.code === 'pro' ? 'text-brand-600' : plan.code === 'business' ? 'text-gold-600' : 'text-success']"
                                    aria-hidden="true"
                                />
                                <span>Galerie photos{{ plan.max_media > 0 ? ` (${plan.max_media} max.)` : '' }}</span>
                            </div>
                            <div
                                v-if="plan.features.advanced_stats"
                                class="flex items-center gap-2 text-sm"
                                :class="plan.code === 'free' ? 'text-muted-foreground' : ''"
                            >
                                <CheckCircle2
                                    class="size-4 shrink-0"
                                    :class="[plan.code === 'pro' ? 'text-brand-600' : plan.code === 'business' ? 'text-gold-600' : 'text-success']"
                                    aria-hidden="true"
                                />
                                <span>Statistiques avancées</span>
                            </div>
                            <div
                                v-if="plan.features.collaborators"
                                class="flex items-center gap-2 text-sm"
                                :class="plan.code === 'free' ? 'text-muted-foreground' : ''"
                            >
                                <CheckCircle2
                                    class="size-4 shrink-0"
                                    :class="[plan.code === 'pro' ? 'text-brand-600' : plan.code === 'business' ? 'text-gold-600' : 'text-success']"
                                    aria-hidden="true"
                                />
                                <span>Collaborateurs et permissions</span>
                            </div>
                            <div
                                v-if="plan.features.priority_visibility"
                                class="flex items-center gap-2 text-sm"
                                :class="plan.code === 'free' ? 'text-muted-foreground' : ''"
                            >
                                <CheckCircle2
                                    class="size-4 shrink-0"
                                    :class="[plan.code === 'pro' ? 'text-brand-600' : plan.code === 'business' ? 'text-gold-600' : 'text-success']"
                                    aria-hidden="true"
                                />
                                <span>Mise en avant</span>
                            </div>
                            <div
                                v-if="plan.features.priority_support"
                                class="flex items-center gap-2 text-sm"
                                :class="plan.code === 'free' ? 'text-muted-foreground' : ''"
                            >
                                <CheckCircle2
                                    class="size-4 shrink-0"
                                    :class="[plan.code === 'pro' ? 'text-brand-600' : plan.code === 'business' ? 'text-gold-600' : 'text-success']"
                                    aria-hidden="true"
                                />
                                <span>Support prioritaire</span>
                            </div>
                        </div>
                        <div class="mt-6">
                            <Button
                                as-child
                                variant="brand"
                                size="sm"
                                class="h-11 w-full"
                                :class="[
                                    plan.code === 'pro'
                                        ? 'bg-brand-600 hover:bg-brand-700'
                                        : plan.code === 'business'
                                          ? 'bg-gold-600 hover:bg-gold-700'
                                          : '',
                                ]"
                            >
                                <Link
                                    :href="
                                        authUser
                                            ? route('subscription.index', { owner_type: 'profile', owner_id: authUser.id })
                                            : plan.is_free
                                              ? route('register')
                                              : route('register', { plan: plan.code })
                                    "
                                >
                                    {{ authUser ? 'Gérer mon abonnement' : 'Créer mon espace pro' }}
                                    <ArrowRight class="size-4" aria-hidden="true" />
                                </Link>
                            </Button>
                        </div>
                    </article>
                </div>

                <div class="mt-10 text-center">
                    <p class="text-sm text-muted-foreground">
                        Aucun engagement. Vous pouvez changer de formule à tout moment. Les formules payantes incluent un essai gratuit de 30 jours.
                    </p>
                    <div class="mt-4 flex items-center justify-center gap-8 text-xs text-muted-foreground">
                        <div class="flex items-center gap-2">
                            <BadgeCheck class="size-4 text-brand-600" aria-hidden="true" />
                            <span>Sécurisé</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <ShieldCheck class="size-4 text-brand-600" aria-hidden="true" />
                            <span>Données protégées</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <Wallet class="size-4 text-brand-600" aria-hidden="true" />
                            <span>Paiement sécurisé</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
