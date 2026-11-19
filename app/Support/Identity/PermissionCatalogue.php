<?php

namespace App\Support\Identity;

/**
 * Catalogue des permissions granulaires et des rôles applicatifs.
 *
 * Les droits effectifs sont toujours décrits permission par permission :
 * aucune logique ne s'appuie sur le seul nom d'un rôle.
 */
final class PermissionCatalogue
{
    /**
     * Permissions du domaine « identité et compte personnel ».
     *
     * @var list<string>
     */
    public const IDENTITY = [
        'profile.view.own',
        'profile.update.own',
        'password.update.own',
    ];

    /**
     * Permissions d'administration des comptes.
     *
     * @var list<string>
     */
    public const USERS = [
        'users.view',
        'users.viewAny',
        'users.update',
        'users.suspend',
        'users.activate',
        'users.delete',
        'users.assign_roles',
    ];

    /**
     * Permissions de gestion des rôles.
     *
     * @var list<string>
     */
    public const ROLES = [
        'roles.view',
        'roles.manage',
    ];

    /**
     * Permissions d'administration générale.
     *
     * @var list<string>
     */
    public const ADMINISTRATION = [
        'admin.access',
        'statistics.view',
        'categories.manage',
        'audit.view',
    ];

    /**
     * Permissions du domaine « professionnels ».
     *
     * @var list<string>
     */
    public const PROFESSIONALS = [
        'professionals.view',
        'professionals.manage',
        'professionals.update.own',
    ];

    /**
     * Permissions du domaine « services ».
     *
     * @var list<string>
     */
    public const SERVICES = [
        'services.view',
        'services.manage',
        'services.update.own',
    ];

    /**
     * Permissions du domaine « devis ».
     *
     * @var list<string>
     */
    public const QUOTES = [
        'quotes.view',
        'quotes.manage',
        'quotes.create',
        'quotes.respond',
    ];

    /**
     * Permissions du domaine « réservations ».
     *
     * @var list<string>
     */
    public const BOOKINGS = [
        'bookings.view',
        'bookings.manage',
        'bookings.create',
        'bookings.update.own',
        'bookings.respond',
        'bookings.confirm',
        'bookings.complete',
    ];

    /**
     * Permissions du domaine « avis ».
     *
     * @var list<string>
     */
    public const REVIEWS = [
        'reviews.view',
        'reviews.manage',
        'reviews.create',
        'reviews.respond',
    ];

    /**
     * Permissions du domaine « entreprise ».
     *
     * @var list<string>
     */
    public const COMPANY = [
        'company.view',
        'company.manage',
        'company.team.manage',
    ];

    /**
     * Permissions du domaine « profils professionnels ».
     *
     * @var list<string>
     */
    public const PROVIDER_PROFILES = [
        'provider_profiles.view',
        'provider_profiles.view.all',
        'provider_profiles.create',
        'provider_profiles.update.own',
        'provider_profiles.update',
        'provider_profiles.submit',
        'provider_profiles.deactivate.own',
        'provider_profiles.verify',
        'provider_profiles.refuse',
        'provider_profiles.suspend',
        'provider_profiles.feature',
        'provider_profiles.media.manage',
        'provider_profiles.services.manage',
        'provider_profiles.areas.manage',
        'provider_profiles.schedule.manage',
    ];

    /**
     * Permissions du domaine « entreprises ».
     *
     * @var list<string>
     */
    public const BUSINESSES = [
        'businesses.create',
        'businesses.view.own',
        'businesses.update.own',
        'businesses.manage_members',
        'businesses.manage_profiles',
        'businesses.view.all',
        'businesses.update',
        'businesses.verify',
        'businesses.suspend',
        'businesses.activate',
    ];

    /**
     * Permissions du domaine « demandes de devis ».
     *
     * @var list<string>
     */
    public const SERVICE_REQUESTS = [
        'service_requests.create',
        'service_requests.respond',
        'service_requests.quote',
        'service_requests.complete',
        'service_requests.manage',
    ];

    /**
     * Permissions du domaine « abonnements ».
     *
     * @var list<string>
     */
    public const SUBSCRIPTIONS = [
        'subscriptions.view',
        'subscriptions.manage',
    ];

    /**
     * Permissions du domaine « paiements ».
     *
     * @var list<string>
     */
    public const PAYMENTS = [
        'payments.view',
        'payments.manage',
    ];

    /**
     * Permissions du domaine « formules commerciales ».
     *
     * @var list<string>
     */
    public const PLANS = [
        'plans.view',
        'plans.manage',
    ];

    /**
     * Permissions du domaine « signalements ».
     *
     * @var list<string>
     */
    public const REPORTS = [
        'reports.create',
        'reports.view',
        'reports.manage',
    ];

    /**
     * Toutes les permissions du catalogue.
     *
     * @return list<string>
     */
    /**
     * Libellé lisible d'une permission.
     */
    public static function label(string $permission): string
    {
        return match ($permission) {
            'admin.access' => 'Accès administration',
            'audit.view' => 'Consultation du journal d’audit',
            'bookings.complete' => 'Finaliser une réservation',
            'bookings.confirm' => 'Confirmer une réservation',
            'bookings.create' => 'Créer une réservation',
            'bookings.manage' => 'Gérer les réservations',
            'bookings.respond' => 'Répondre à une réservation',
            'bookings.update.own' => 'Modifier ses réservations',
            'bookings.view' => 'Consulter les réservations',
            'businesses.activate' => 'Activer une entreprise',
            'businesses.create' => 'Créer une entreprise',
            'businesses.manage_members' => 'Gérer les collaborateurs',
            'businesses.manage_profiles' => 'Gérer les profils de l’entreprise',
            'businesses.suspend' => 'Suspendre une entreprise',
            'businesses.update' => 'Modifier une entreprise',
            'businesses.update.own' => 'Modifier sa propre entreprise',
            'businesses.verify' => 'Vérifier une entreprise',
            'businesses.view.all' => 'Consulter toutes les entreprises',
            'businesses.view.own' => 'Consulter sa propre entreprise',
            'categories.manage' => 'Gérer les catégories',
            'company.manage' => 'Gérer l’entreprise',
            'company.team.manage' => 'Gérer l’équipe',
            'company.view' => 'Consulter l’entreprise',
            'password.update.own' => 'Modifier son mot de passe',
            'payments.manage' => 'Gérer les paiements',
            'payments.view' => 'Consulter les paiements',
            'plans.manage' => 'Gérer les formules',
            'plans.view' => 'Consulter les formules',
            'professionals.manage' => 'Gérer les professionnels',
            'professionals.update.own' => 'Modifier son profil professionnel',
            'professionals.view' => 'Consulter les professionnels',
            'profile.update.own' => 'Modifier son profil',
            'profile.view.own' => 'Consulter son profil',
            'provider_profiles.areas.manage' => 'Gérer les zones d’intervention',
            'provider_profiles.create' => 'Créer un profil professionnel',
            'provider_profiles.deactivate.own' => 'Désactiver son profil',
            'provider_profiles.feature' => 'Mettre en avant un profil',
            'provider_profiles.media.manage' => 'Gérer les médias',
            'provider_profiles.refuse' => 'Refuser un profil',
            'provider_profiles.schedule.manage' => 'Gérer les horaires',
            'provider_profiles.services.manage' => 'Gérer les services',
            'provider_profiles.submit' => 'Soumettre un profil',
            'provider_profiles.suspend' => 'Suspendre un profil',
            'provider_profiles.update' => 'Modifier un profil',
            'provider_profiles.update.own' => 'Modifier son propre profil',
            'provider_profiles.verify' => 'Vérifier un profil',
            'provider_profiles.view' => 'Consulter les profils',
            'provider_profiles.view.all' => 'Consulter tous les profils',
            'quotes.create' => 'Créer un devis',
            'quotes.manage' => 'Gérer les devis',
            'quotes.respond' => 'Répondre à un devis',
            'quotes.view' => 'Consulter les devis',
            'reports.create' => 'Créer un signalement',
            'reports.manage' => 'Gérer les signalements',
            'reports.view' => 'Consulter les signalements',
            'reviews.create' => 'Créer un avis',
            'reviews.manage' => 'Gérer les avis',
            'reviews.respond' => 'Répondre à un avis',
            'reviews.view' => 'Consulter les avis',
            'roles.manage' => 'Gérer les rôles',
            'roles.view' => 'Consulter les rôles',
            'service_requests.complete' => 'Finaliser une demande',
            'service_requests.create' => 'Créer une demande',
            'service_requests.manage' => 'Gérer les demandes',
            'service_requests.quote' => 'Envoyer un devis',
            'service_requests.respond' => 'Répondre à une demande',
            'services.manage' => 'Gérer les services',
            'services.update.own' => 'Modifier ses services',
            'services.view' => 'Consulter les services',
            'statistics.view' => 'Consulter les statistiques',
            'subscriptions.manage' => 'Gérer les abonnements',
            'subscriptions.view' => 'Consulter les abonnements',
            'users.activate' => 'Activer un compte',
            'users.assign_roles' => 'Attribuer des rôles',
            'users.delete' => 'Supprimer un compte',
            'users.suspend' => 'Suspendre un compte',
            'users.update' => 'Modifier un compte',
            'users.view' => 'Consulter un compte',
            'users.viewAny' => 'Consulter les comptes',
            'identity' => 'Gestion de l’identité',
            default => $permission,
        };
    }

    public static function all(): array
    {
        return array_values(array_unique(array_merge(
            self::IDENTITY,
            self::USERS,
            self::ROLES,
            self::ADMINISTRATION,
            self::PROFESSIONALS,
            self::SERVICES,
            self::QUOTES,
            self::BOOKINGS,
            self::REVIEWS,
            self::COMPANY,
            self::PROVIDER_PROFILES,
            self::BUSINESSES,
            self::SERVICE_REQUESTS,
            self::SUBSCRIPTIONS,
            self::PAYMENTS,
            self::PLANS,
            self::REPORTS,
        )));
    }

    /**
     * Permissions associées à un rôle.
     *
     * @return list<string>
     */
    public static function forRole(string $role): array
    {
        return match ($role) {
            // Super administrateur : toutes les permissions, y compris l'attribution de rôles.
            'super-admin' => self::all(),

            // Administrateur : supervision courante, sans gestion des rôles ni suppression de comptes.
            'admin' => array_values(array_diff(
                array_merge(
                    self::USERS,
                    self::ROLES,
                    self::ADMINISTRATION,
                    self::PROFESSIONALS,
                    self::SERVICES,
                    self::QUOTES,
                    self::BOOKINGS,
                    self::REVIEWS,
                    self::PROVIDER_PROFILES,
                    self::BUSINESSES,
                    self::SERVICE_REQUESTS,
                    self::SUBSCRIPTIONS,
                    self::PAYMENTS,
                    self::PLANS,
                    self::REPORTS,
                ),
                [
                    'users.assign_roles',
                    'users.delete',
                    'provider_profiles.create',
                    'provider_profiles.update.own',
                    'provider_profiles.deactivate.own',
                    'businesses.create',
                    'businesses.update.own',
                    'service_requests.create',
                    'bookings.create',
                ],
            )),

            // Client : recherche, demandes, réservations et avis.
            'customer' => array_merge(
                self::IDENTITY,
                [
                    'professionals.view',
                    'provider_profiles.view',
                    'quotes.create',
                    'bookings.create',
                    'reviews.create',
                    'service_requests.create',
                    'reports.create',
                ],
            ),

            // Professionnel : gestion de son activité.
            'professional' => array_merge(
                self::IDENTITY,
                [
                    'professionals.view',
                    'professionals.update.own',
                    'provider_profiles.view',
                    'provider_profiles.create',
                    'provider_profiles.update.own',
                    'provider_profiles.submit',
                    'provider_profiles.deactivate.own',
                    'provider_profiles.media.manage',
                    'provider_profiles.services.manage',
                    'provider_profiles.areas.manage',
                    'provider_profiles.schedule.manage',
                    'services.view',
                    'services.update.own',
                    'quotes.view',
                    'quotes.respond',
                    'bookings.view',
                    'bookings.update.own',
                    'reviews.view',
                    'reviews.respond',
                    'service_requests.respond',
                    'service_requests.quote',
                    'service_requests.complete',
                    'bookings.respond',
                    'bookings.confirm',
                    'bookings.complete',
                    'reports.create',
                ],
            ),

            // Gestionnaire d'entreprise : pilotage de sa structure et de son équipe.
            'company-manager' => array_merge(
                self::IDENTITY,
                self::COMPANY,
                [
                    'professionals.view',
                    'professionals.update.own',
                    'provider_profiles.view',
                    'provider_profiles.create',
                    'provider_profiles.update.own',
                    'provider_profiles.submit',
                    'provider_profiles.deactivate.own',
                    'provider_profiles.media.manage',
                    'provider_profiles.services.manage',
                    'provider_profiles.areas.manage',
                    'provider_profiles.schedule.manage',
                    'businesses.create',
                    'businesses.view.own',
                    'businesses.update.own',
                    'businesses.manage_members',
                    'businesses.manage_profiles',
                    'services.view',
                    'services.update.own',
                    'quotes.view',
                    'quotes.respond',
                    'bookings.view',
                    'bookings.update.own',
                    'reviews.view',
                    'reviews.respond',
                    'users.view',
                    'service_requests.respond',
                    'service_requests.quote',
                    'service_requests.complete',
                    'bookings.respond',
                    'bookings.confirm',
                    'bookings.complete',
                    'reports.create',
                ],
            ),

            default => [],
        };
    }
}
