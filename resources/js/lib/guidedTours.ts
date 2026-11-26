export type TourPlacement = 'top' | 'bottom' | 'left' | 'right' | 'center';

export interface TourStep {
    id: string;
    title: string;
    content: string;
    selector?: string;
    selectors?: string[];
    route?: string;
    placement?: TourPlacement;
    permissionsAny?: string[];
    permissionsAll?: string[];
    skipIfMissing?: boolean;
}

export interface TourDefinition {
    role: string;
    label: string;
    version: number;
    welcomeTitle: string;
    welcomeDescription: string;
    steps: TourStep[];
}

const customerTour: TourDefinition = {
    role: 'customer',
    label: 'Client',
    version: 1,
    welcomeTitle: 'Bienvenue dans votre espace client',
    welcomeDescription: 'Découvrez rapidement comment rechercher un professionnel, suivre vos demandes et gérer votre compte.',
    steps: [
        {
            id: 'customer-sidebar',
            title: 'Navigation principale',
            content: 'Utilisez cette barre latérale pour accéder à vos demandes, réservations, favoris, avis et notifications.',
            selector: '[data-tour="sidebar-nav"]',
            route: '/dashboard',
            placement: 'right',
        },
        {
            id: 'customer-dashboard',
            title: 'Votre tableau de bord',
            content: 'Cet espace vous donne une vue d’ensemble de votre activité : profils, demandes récentes et actions utiles.',
            selector: '[data-tour="dashboard-header"]',
            route: '/dashboard',
            placement: 'bottom',
        },
        {
            id: 'customer-requests',
            title: 'Mes demandes',
            content: 'Retrouvez ici vos demandes de service, leur statut et les échanges liés à vos besoins.',
            selector: '[data-tour="nav-demandes"]',
            route: '/dashboard',
            placement: 'right',
            skipIfMissing: true,
        },
        {
            id: 'customer-bookings',
            title: 'Mes réservations',
            content: 'Suivez vos réservations, consultez les détails et laissez un avis après une prestation terminée.',
            selector: '[data-tour="nav-reservations"]',
            route: '/dashboard',
            placement: 'right',
            skipIfMissing: true,
        },
        {
            id: 'customer-favorites',
            title: 'Mes favoris',
            content: 'Enregistrez les professionnels qui vous intéressent pour les retrouver facilement.',
            selector: '[data-tour="nav-favoris"]',
            route: '/dashboard',
            placement: 'right',
            skipIfMissing: true,
        },
        {
            id: 'customer-notifications',
            title: 'Notifications',
            content: 'Restez informé des réponses, réservations et mises à jour importantes liées à votre compte.',
            selector: '[data-tour="header-notifications"]',
            route: '/dashboard',
            placement: 'bottom',
            skipIfMissing: true,
        },
        {
            id: 'customer-search',
            title: 'Recherche globale',
            content: 'Recherchez rapidement une page ou une action depuis n’importe quel écran.',
            selector: '[data-tour="header-search"]',
            route: '/dashboard',
            placement: 'bottom',
            skipIfMissing: true,
        },
    ],
};

const professionalTour: TourDefinition = {
    role: 'professional',
    label: 'Professionnel',
    version: 1,
    welcomeTitle: 'Bienvenue dans votre espace professionnel',
    welcomeDescription: 'Cette visite vous aide à configurer vos profils, gérer vos demandes et suivre votre activité.',
    steps: [
        {
            id: 'pro-sidebar',
            title: 'Navigation professionnelle',
            content: 'Accédez à vos profils, fiches tarifaires, demandes, réservations, avis et abonnement depuis cette barre.',
            selector: '[data-tour="sidebar-nav"]',
            route: '/dashboard',
            placement: 'right',
        },
        {
            id: 'pro-dashboard',
            title: 'Tableau de bord',
            content: 'Suivez votre activité, la complétude de votre profil et les dernières actions importantes.',
            selector: '[data-tour="dashboard-header"]',
            route: '/dashboard',
            placement: 'bottom',
        },
        {
            id: 'pro-profiles',
            title: 'Mes profils',
            content: 'Créez et complétez vos profils professionnels pour être visible auprès des clients.',
            selector: '[data-tour="nav-profiles"]',
            route: '/dashboard',
            placement: 'right',
            skipIfMissing: true,
        },
        {
            id: 'pro-requests',
            title: 'Demandes reçues',
            content: 'Consultez les demandes clients, répondez rapidement et transformez-les en opportunités.',
            selector: '[data-tour="nav-pro-demandes"]',
            route: '/dashboard',
            placement: 'right',
            skipIfMissing: true,
        },
        {
            id: 'pro-bookings',
            title: 'Réservations',
            content: 'Gérez vos rendez-vous et le suivi des prestations avec vos clients.',
            selector: '[data-tour="nav-pro-reservations"]',
            route: '/dashboard',
            placement: 'right',
            skipIfMissing: true,
        },
        {
            id: 'pro-reviews',
            title: 'Avis reçus',
            content: 'Consultez les avis clients et répondez pour renforcer votre crédibilité.',
            selector: '[data-tour="nav-pro-avis"]',
            route: '/dashboard',
            placement: 'right',
            skipIfMissing: true,
        },
        {
            id: 'pro-subscription',
            title: 'Abonnement',
            content: 'Gérez votre formule, vos limites et les options de monétisation de votre activité.',
            selector: '[data-tour="nav-pro-abonnement"]',
            route: '/dashboard',
            placement: 'right',
            skipIfMissing: true,
        },
    ],
};

const companyManagerTour: TourDefinition = {
    role: 'company-manager',
    label: 'Gestionnaire d’entreprise',
    version: 1,
    welcomeTitle: 'Bienvenue dans votre espace gestionnaire',
    welcomeDescription: 'Découvrez les outils pour gérer l’activité, les profils, les demandes et le suivi opérationnel.',
    steps: [
        {
            id: 'manager-sidebar',
            title: 'Navigation gestionnaire',
            content: 'Cette barre latérale regroupe les outils de gestion de l’entreprise et des profils associés.',
            selector: '[data-tour="sidebar-nav"]',
            route: '/dashboard',
            placement: 'right',
        },
        {
            id: 'manager-dashboard',
            title: 'Vue d’ensemble',
            content: 'Le tableau de bord vous aide à suivre l’activité, les demandes et les indicateurs importants.',
            selector: '[data-tour="dashboard-header"]',
            route: '/dashboard',
            placement: 'bottom',
        },
        {
            id: 'manager-profiles',
            title: 'Profils et entreprise',
            content: 'Gérez les profils professionnels et les informations de l’entreprise depuis ces sections.',
            selector: '[data-tour="nav-profiles"]',
            route: '/dashboard',
            placement: 'right',
            skipIfMissing: true,
        },
        {
            id: 'manager-requests',
            title: 'Suivi des demandes',
            content: 'Suivez les demandes entrantes et coordonnez les réponses de manière efficace.',
            selector: '[data-tour="nav-pro-demandes"]',
            route: '/dashboard',
            placement: 'right',
            skipIfMissing: true,
        },
        {
            id: 'manager-notifications',
            title: 'Notifications',
            content: 'Recevez les alertes importantes pour ne rien manquer dans la gestion quotidienne.',
            selector: '[data-tour="header-notifications"]',
            route: '/dashboard',
            placement: 'bottom',
            skipIfMissing: true,
        },
    ],
};

const adminTour: TourDefinition = {
    role: 'admin',
    label: 'Administrateur',
    version: 1,
    welcomeTitle: 'Bienvenue dans l’administration MaliConnect',
    welcomeDescription: 'Cette visite vous présente les outils de modération, de gestion et de supervision de la plateforme.',
    steps: [
        {
            id: 'admin-sidebar',
            title: 'Navigation administration',
            content: 'Accédez aux sections clés : tableau de bord, modération, utilisateurs, catalogue, plans et journal.',
            selector: '[data-tour="sidebar-nav"]',
            route: '/admin',
            placement: 'right',
        },
        {
            id: 'admin-dashboard',
            title: 'Tableau de bord admin',
            content: 'Visualisez les indicateurs globaux, les éléments en attente et l’état de la plateforme.',
            selector: '[data-tour="admin-dashboard-header"]',
            route: '/admin',
            placement: 'bottom',
        },
        {
            id: 'admin-moderation',
            title: 'Modération',
            content: 'Vérifiez les profils, entreprises, avis, paiements et signalements en attente.',
            selector: '[data-tour="nav-admin-moderation"]',
            route: '/admin',
            placement: 'right',
            permissionsAny: ['provider_profiles.verify', 'businesses.verify', 'reviews.manage', 'payments.review', 'reports.manage'],
            skipIfMissing: true,
        },
        {
            id: 'admin-users',
            title: 'Utilisateurs et accès',
            content: 'Gérez les comptes, rôles et permissions selon les responsabilités.',
            selector: '[data-tour="nav-admin-users"]',
            route: '/admin',
            placement: 'right',
            permissionsAny: ['users.view', 'roles.manage'],
            skipIfMissing: true,
        },
        {
            id: 'admin-plans',
            title: 'Offres et abonnements',
            content: 'Suivez les plans, abonnements et paiements pour piloter la monétisation.',
            selector: '[data-tour="nav-admin-plans"]',
            route: '/admin',
            placement: 'right',
            permissionsAny: ['plans.manage', 'subscriptions.manage', 'payments.review'],
            skipIfMissing: true,
        },
    ],
};

const superAdminTour: TourDefinition = {
    role: 'super-admin',
    label: 'Super administrateur',
    version: 1,
    welcomeTitle: 'Bienvenue dans l’espace super administrateur',
    welcomeDescription: 'Vous disposez d’une vue complète sur la configuration, la supervision et l’administration de MaliConnect.',
    steps: [
        {
            id: 'superadmin-sidebar',
            title: 'Navigation complète',
            content: 'Accédez à l’ensemble des modules d’administration, y compris la configuration sensible.',
            selector: '[data-tour="sidebar-nav"]',
            route: '/admin',
            placement: 'right',
        },
        {
            id: 'superadmin-dashboard',
            title: 'Supervision globale',
            content: 'Surveillez l’activité, les volumes et les éléments critiques de la plateforme.',
            selector: '[data-tour="admin-dashboard-header"]',
            route: '/admin',
            placement: 'bottom',
        },
        {
            id: 'superadmin-users',
            title: 'Gestion des accès',
            content: 'Administrez les utilisateurs, rôles et permissions avec un niveau de contrôle complet.',
            selector: '[data-tour="nav-admin-users"]',
            route: '/admin',
            placement: 'right',
            permissionsAny: ['users.view', 'roles.manage'],
            skipIfMissing: true,
        },
        {
            id: 'superadmin-audit',
            title: 'Journal d’audit',
            content: 'Consultez l’historique des actions sensibles pour assurer la traçabilité et la sécurité.',
            selector: '[data-tour="nav-admin-audit"]',
            route: '/admin',
            placement: 'right',
            permissionsAny: ['audit.view'],
            skipIfMissing: true,
        },
        {
            id: 'superadmin-settings',
            title: 'Configuration avancée',
            content: 'Pilotez les paramètres globaux, offres et règles de la plateforme depuis l’administration.',
            selector: '[data-tour="nav-admin-plans"]',
            route: '/admin',
            placement: 'right',
            permissionsAny: ['plans.manage', 'subscriptions.manage'],
            skipIfMissing: true,
        },
    ],
};

export const guidedTours: Record<string, TourDefinition> = {
    customer: customerTour,
    professional: professionalTour,
    'company-manager': companyManagerTour,
    admin: adminTour,
    'super-admin': superAdminTour,
};

export function getTourForRole(role?: string | null): TourDefinition | null {
    if (!role) return null;
    return guidedTours[role] ?? null;
}
