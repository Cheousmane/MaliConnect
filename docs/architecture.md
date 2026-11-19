# Architecture technique — MaliConnect

> Document de référence pour le développement fonctionnel.
> Dernière mise à jour : 17 novembre 2026.

## 1. Objectif

MaliConnect est une marketplace de mise en relation entre particuliers et
professionnels au Mali. L'objectif technique est de construire un **monolithe
modulaire** maintenable, testable et capable d'évoluer vers PostgreSQL en
production sans réécrire les fonctionnalités.

Ce document décrit :

- l'état actuel du socle Laravel ;
- les règles d'organisation du code ;
- les responsabilités de chaque module métier ;
- l'ordre de construction recommandé.

Les fonctionnalités métier ne sont volontairement pas implémentées à ce stade.

---

## 2. Principes directeurs

1. **Monolithe modulaire** : une seule application Laravel, des frontières
   métier explicites. Pas de microservices prématurés.
2. **Modules progressifs** : un module n'est créé que lorsqu'un lot fonctionnel
   en a besoin. Aucun dossier vide destiné à « faire propre ».
3. **Sens de dépendance unique** : les modules de base ne dépendent jamais des
   modules métier de haut niveau. Les cycles sont interdits.
4. **Base de données partagée** : les clés étrangères entre modules sont
   autorisées, mais un module ne manipule pas directement les modèles d'un
   autre module en écriture.
5. **Contrats et événements** : les communications inter-modules passent par des
   contrats (interfaces) ou des événements de domaine, pas par des appels
   implicites.
6. **Français pour l'interface et la documentation, anglais pour le code** :
   classes, méthodes, variables et routes nommées en anglais ; libellés,
   messages, commentaires et documentation en français.
7. **Configuration par environnement** : aucune valeur propre à un environnement
   ne doit être codée en dur.

---

## 3. État actuel du socle

### 3.1 Versions

| Élément | Version |
| --- | --- |
| Laravel | 12.72.0 |
| PHP | 8.2.12 |
| Composer | 2.8.8 |
| Node.js | 22.14.0 |
| npm | 11.19.1 |
| Inertia Laravel | 2.0.28 |
| Inertia Vue | 2.3.29 |
| Vue | 3.5.45 |
| Vite | 6.4.6 |
| Tailwind CSS | 3.4.19 |
| TypeScript | 5.9.3 |
| Spatie Permission | 6.25.0 |
| Pest | 3.8.7 |
| SQLite | PDO SQLite activé |
| PostgreSQL | PDO PostgreSQL activé (serveur 17 disponible localement) |

### 3.2 Structure actuelle

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/              # Authentification du starter kit
│   │   └── Settings/          # Profil et mot de passe
│   ├── Middleware/            # HandleInertiaRequests
│   └── Requests/
├── Models/                    # Modèles à plat : User, Category, Professional…
├── Providers/
└── ...

database/
├── factories/                 # 7 factories métier + UserFactory
├── migrations/                # 12 migrations métier + permissions
└── seeders/                   # Catégories, sous-catégories, rôles

resources/js/
├── components/                # Composants partagés + ui shadcn-vue
├── composables/
├── layouts/
├── pages/                     # Pages Inertia
└── types/

routes/
├── web.php                    # Accueil, dashboard
├── auth.php                   # Connexion, inscription, mots de passe
└── settings.php               # Profil, mot de passe, apparence
```

### 3.3 Schéma de données actuel

| Table | Rôle | Remarques |
| --- | --- | --- |
| `users` | Comptes | `phone`, `avatar`, `locale` ajoutés ; rôles Spatie |
| `categories` | Catégories métier | 12 catégories seedées |
| `subcategories` | Sous-catégories | 62 sous-catégories seedées |
| `professionals` | Anciens profils professionnels | Conservée pour l'historique ; remplacée par `provider_profiles` |
| `provider_profiles` | Profils professionnels | Rattachés à un utilisateur et éventuellement à une entreprise |
| `businesses` | Entreprises | Portent plusieurs profils et leurs collaborateurs |
| `business_members` | Collaborateurs d'entreprise | Rôles `owner`, `manager`, `agent` |
| `services` | Prestations d'un professionnel | Tarification fixe, fourchette, horaire ou devis |
| `service_requests` | Demandes de devis | Peut référencer une prestation tarifée (`pricing_item_id`) |
| `service_request_quotes` | Réponses chiffrées des professionnels | Une réponse par demande |
| `pricing_sheets` | Fiches tarifaires | Statut, validité, devise et jeton public UUID |
| `pricing_items` | Prestations d'une fiche tarifaire | Prix, mode de facturation, conditions, service lié |
| `quotes` | Anciennes demandes et réponses de devis | **Conservée pour l'historique** |
| `bookings` | Réservations | Peut être liée à un devis |
| `reviews` | Anciens avis clients | Conservée pour l'historique ; remplacée par `provider_reviews` |
| `provider_reviews` | Avis liés aux prestations terminées | Un avis par réservation ; modération et réponse du professionnel |
| `favorites` | Professionnels favoris | Unicité (`user_id`, `provider_profile_id`) |
| `notifications` | Notifications Laravel | Canal `database` par défaut, canaux extensibles |
| `roles`, `permissions`, … | Spatie Permission | Rôles `admin`, `professional`, `customer` |

Les tables de monétisation (`plans`, `subscriptions`, `payments`), de
modération (`reports`, `admin_audit_logs`) et de tarification
(`pricing_sheets`, `pricing_items`) sont désormais en place. Les tables
historiques `professionals`, `quotes`, `bookings` et `reviews` restent tant que
la migration des données n'est pas terminée.

### 3.4 Routes existantes

- `/` : page d'accueil Inertia.
- `/dashboard` : tableau de bord authentifié et vérifié.
- `/login`, `/register`, `/forgot-password`, `/reset-password` : authentification.
- `/verify-email`, `/confirm-password` : vérification et confirmation.
- `/settings/profile`, `/settings/password`, `/settings/appearance` : compte.
- Catalogue public : `/recherche`, `/categories`, `/professionnels/{slug}`,
  `/services/{service}`.
- Espace client : `/demandes`, `/reservations`, `/avis`, `/favoris`,
  `/notifications`.
- Espace professionnel : `/pro/profiles`, `/pro/businesses`, `/pro/tarifs`,
  `/pro/demandes`, `/pro/reservations`, `/pro/avis`.
- Fiches tarifaires publiques : `/tarifs/{token}`, `/tarifs/{token}/pdf`.
- Administration : `/admin/users`, `/admin/provider-profiles`, `/admin/avis`.

Le détail des routes métier figure dans les sections 7.5 à 7.9.

### 3.5 Tests

- Pest 3.8.7, base SQLite `:memory:` pour les tests.
- `tests/Pest.php` initialise Laravel et `RefreshDatabase` pour Feature et Unit.
- Les factories couvrent les utilisateurs, catégories, profils, services,
  entreprises, demandes, devis, réservations, avis et favoris.
- Dernière validation complète : **275 tests, 1192 assertions**.
- Contrôles statiques : ESLint, Prettier, Pint et `vue-tsc` passent sans erreur ; `npm run build` réussit.

---

## 4. Configuration par environnement

### 4.1 Développement local — SQLite

```dotenv
APP_ENV=local
APP_URL=http://localhost:8000
APP_TIMEZONE=Africa/Bamako
APP_LOCALE=fr

DB_CONNECTION=sqlite
DB_FOREIGN_KEYS=true
DB_BUSY_TIMEOUT=5000
```

Le fichier `database/database.sqlite` est créé localement et ignoré par Git.
SQLite convient au développement et aux tests, pas à la production.

### 4.2 Tests — SQLite en mémoire

`phpunit.xml` force une base `:memory:` isolée, avec locale française et fuseau
`Africa/Bamako`. Les tests ne touchent jamais la base de développement.

### 4.3 Production — PostgreSQL

Le basculement se fait uniquement par variables d'environnement :

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=maliconnect
DB_USERNAME=maliconnect
DB_PASSWORD=secret
DB_SSLMODE=prefer
DB_SEARCH_PATH=public
```

Aucun code métier ne doit dépendre du driver. Les points de vigilance sont :

- éviter les requêtes SQL spécifiques à SQLite (`strftime`, `AUTOINCREMENT`) ;
- utiliser le query builder ou Eloquent ;
- pour la recherche plein texte, prévoir une abstraction avant d'utiliser les
  fonctionnalités `tsvector` de PostgreSQL ;
- tester la suite sur les deux moteurs en intégration continue.

### 4.4 Fuseau horaire et locale

- `APP_TIMEZONE=Africa/Bamako` (UTC+0, sans heure d'été).
- `APP_LOCALE=fr`, `APP_FALLBACK_LOCALE=fr`, `APP_FAKER_LOCALE=fr_FR`.
- Les dates sont stockées via Eloquent et exposées en ISO 8601 ; l'affichage
  français est géré côté Vue.
- Les montants sont exprimés en **francs CFA (XOF), sans décimales**, stockés en
  entiers ou en `decimal(12, 0)`.

---

## 5. Architecture cible

### 5.1 Arborescence backend

```text
app/
├── Http/                       # Noyau HTTP : auth, settings, middlewares
├── Models/                     # Modèles partagés actuels, migrés progressivement
├── Modules/
│   ├── Identity/
│   ├── Users/
│   ├── Businesses/
│   ├── Catalog/
│   ├── Search/
│   ├── ServiceRequests/
│   ├── Quotes/
│   ├── Bookings/
│   ├── Reviews/
│   ├── Favorites/
│   ├── Subscriptions/
│   ├── Payments/
│   └── Notifications/
├── Providers/
└── Support/                    # Utilitaires transverses (montants, téléphone…)
```

Chaque module suit la même anatomie, créée **au moment où il devient utile** :

```text
app/Modules/Businesses/
├── Actions/                    # Cas d'usage en écriture
├── Data/                       # DTO simples si nécessaire
├── Enums/                      # Statuts et types
├── Events/                     # Événements de domaine
├── Http/
│   ├── Controllers/
│   ├── Requests/               # Validation
│   └── Resources/              # Sérialisation Inertia / API
├── Listeners/
├── Models/
├── Policies/
├── Providers/
│   └── BusinessesServiceProvider.php
├── Queries/                    # Lectures complexes réutilisables
├── Routes/
│   └── web.php
└── Services/                   # Services de domaine
```

Les dossiers inutiles ne sont pas créés. Un module peut démarrer avec
`Models`, `Http`, `Routes` et `Providers`, puis s'enrichir.

### 5.2 Enregistrement des modules

Chaque module possède un `ServiceProvider` ajouté à `bootstrap/providers.php`
lors de sa création. Il charge :

- les routes du module avec leur préfixe et leur nom ;
- les migrations si elles sont centralisées (recommandé au démarrage) ;
- les écouteurs d'événements propres au module.

### 5.3 Règles de dépendance

```text
Identity
   └── Users
         └── Businesses
               └── Catalog
                     └── Search (lecture)

ServiceRequests ──> Users, Catalog, Businesses
Quotes ──────────> ServiceRequests, Businesses
Bookings ────────> Quotes, Businesses, Users
Reviews ─────────> Bookings, Businesses, Users
Favorites ───────> Users, Businesses
Subscriptions ───> Businesses, Payments
Payments ────────> passerelle externe uniquement
Notifications ───> événements de tous les modules (écoute seule)
```

Règles concrètes :

- un module ne référence jamais un modèle d'un autre module pour **écrire** ;
- les lectures inter-modules passent par une `Query` ou un contrat exposé ;
- les effets de bord passent par des événements ;
- les clés étrangères inter-modules restent autorisées ;
- aucun module métier ne dépend de `Notifications` ; c'est `Notifications` qui
  écoute les événements.

### 5.4 Migrations

Au démarrage, les migrations restent **centralisées** dans
`database/migrations` pour éviter les problèmes d'ordre de chargement. Le nom du
fichier porte le module :

```text
2026_11_20_100000_create_businesses_tables.php
2026_11_25_100000_create_service_requests_tables.php
```

Si le nombre de migrations devient difficile à gérer, un passage à des
migrations par module (`loadMigrationsFrom`) pourra être étudié, mais ce n'est
pas nécessaire pour le MVP.

---

## 6. Responsabilités des modules

| Module | Responsabilité | Tables principales | Dépend de | Lot |
| --- | --- | --- | --- | --- |
| **Identity** | Inscription, connexion, vérification e-mail, mots de passe, sessions, rôles et permissions | `users`, tables Spatie | — | 1 |
| **Users** | Profil client, coordonnées, préférences, espace compte | `users`, `user_addresses` (à venir) | Identity | 1 |
| **Businesses** | Profil professionnel, vérification, localisation, médias, tableau de bord pro | `professionals` | Identity, Users | 2 |
| **Catalog** | Catégories, sous-catégories, prestations et tarifs | `categories`, `subcategories`, `services` | Businesses | 2 |
| **Search** | Découverte et filtrage des professionnels et prestations | Aucune au départ (lecture) | Businesses, Catalog | 3 |
| **ServiceRequests** | Demandes clients : besoin, budget, lieu, date souhaitée | `service_requests` (à créer) | Users, Catalog, Businesses | 4 |
| **Quotes** | Réponses chiffrées des professionnels aux demandes | `quotes` (à faire évoluer) | ServiceRequests, Businesses | 4 |
| **Bookings** | Acceptation, planification, suivi et clôture des interventions | `bookings` | Quotes, Businesses, Users | 5 |
| **Reviews** | Avis clients, réponses pro, agrégation des notes | `reviews` | Bookings, Businesses, Users | 5 |
| **Favorites** | Professionnels et prestations mis de côté par un client | `favorites` (à créer) | Users, Businesses, Catalog | 6 |
| **Subscriptions** | Plans, droits et échéances des abonnements professionnels | `plans`, `subscriptions` (à créer) | Businesses, Payments | 7 |
| **Payments** | Paiements, transactions, remboursements, webhooks | `payments`, `transactions` (à créer) | Passerelle externe | 7 |
| **PricingSheets** | Fiches tarifaires professionnelles, prestations chiffrées, partage public et PDF | `pricing_sheets`, `pricing_items` | Businesses, Catalog, ServiceRequests | 8 |
| **Notifications** | Notifications in-app, e-mail, SMS futur | `notifications` | Événements | 6 |

### 6.1 Précisions par module

**Identity** garde l'authentification du starter kit. Les contrôleurs actuels
`Auth\*` y seront déplacés lors du lot 1, sans changer les URL publiques.

**Users** ne doit pas devenir un fourre-tout. Il gère l'identité civile et les
préférences du client. Les données professionnelles appartiennent à
`Businesses`.

**Businesses** porte le profil professionnel, la vérification, les médias et la
localisation. Un utilisateur ne peut avoir qu'un seul profil professionnel
(contrainte ajoutée sur `professionals.user_id`).

**Catalog** distingue les catégories globales (administrées) des prestations
propres à un professionnel. Les catégories ne sont pas des services.

**Search** n'a pas de table au MVP. Il expose des requêtes de lecture et des
filtres (catégorie, ville, note, texte). Une abstraction permettra plus tard
d'ajouter l'index plein texte PostgreSQL ou un moteur externe.

**ServiceRequests** représente le besoin du client. **Quotes** représente la
réponse du professionnel. Le schéma actuel `quotes` fusionne les deux ; la
scission est prévue au lot 4 (voir section 9).

**Bookings** matérialise une intervention planifiée. Les transitions de statut
doivent être atomiques (transaction + verrou) pour éviter les doubles
réservations.

**Reviews** n'accepte un avis que pour une réservation terminée, une seule fois.
La mise à jour de `professionals.rating_average` et `rating_count` se fait par
un listener d'événement, jamais depuis le contrôleur.

**Favorites** est une simple table de liaison `user_id` + cible. Elle ne doit pas
être ajoutée avant le lot 6.

**Subscriptions** remplacera les champs provisoires
`professionals.subscription_plan` et `subscription_expires_at`. Les droits
(visibilité, mise en avant, quota de demandes) seront calculés par ce module.

**Payments** ne stocke jamais de données de carte. Il expose un contrat
`PaymentGateway` et des implémentations par fournisseur (Orange Money, Moov
Money, carte bancaire). Les webhooks sont idempotents.

**Notifications** écoute les événements métier. Les notifications in-app
utilisent le canal `database` de Laravel ; l'e-mail passe par la file d'attente ;
le SMS sera ajouté derrière un contrat.

**PricingSheets** regroupe les tarifs d'un profil professionnel dans une fiche
versionnable. Une fiche appartient à un `ProviderProfile` et reste gérée par son
propriétaire ou les responsables de l'entreprise porteuse. Les prestations
(`pricing_items`) peuvent être liées à un `Service` existant sans le dupliquer.
Le statut (`draft`, `published`, `archived`) est contrôlé côté serveur : une
fiche n'est publique que si elle est publiée, non expirée et rattachée à un
profil vérifié et actif. Un jeton UUID (`public_token`) sert aux liens de
partage et aux PDF, sans exposer l'identifiant interne. Aucun prix n'est inventé :
une prestation sans montant affiche « Sur devis », « Sur diagnostic » ou
« Prix non communiqué ». Les demandes de devis peuvent référencer une
`pricing_item_id` afin de conserver le contexte tarifaire.

---

## 7. Organisation frontend

### 7.1 Arborescence cible

```text
resources/js/
├── components/                 # Composants partagés (AppHeader, TextLink…)
│   └── ui/                     # shadcn-vue, ne pas modifier sans raison
├── composables/                # Composables partagés
├── layouts/                    # AppLayout, AuthLayout, SettingsLayout
├── lib/                        # Utilitaires (cn, formatage)
├── modules/
│   ├── businesses/
│   │   ├── components/         # Composants propres au module
│   │   └── types.ts
│   ├── catalog/
│   ├── service-requests/
│   └── ...
├── pages/                      # Pages Inertia
│   ├── businesses/
│   │   ├── Index.vue
│   │   ├── Show.vue
│   │   └── Edit.vue
│   ├── service-requests/
│   └── ...
└── types/                      # Types partagés (SharedData, User, NavItem…)
```

### 7.2 Conventions

- Une page Inertia correspond à un fichier sous `resources/js/pages`.
- Les pages publiques utilisent des URL françaises :
  `/prestataires`, `/categories`, `/demandes`, `/devis`, `/reservations`.
- Les noms de routes restent en anglais : `professionals.index`,
  `service-requests.store`, `bookings.show`.
- Les espaces authentifiés sont préfixés :
  - `/pro` pour l'espace professionnel (`pro.`) ;
  - `/mon-compte` pour l'espace client (`account.`) ;
  - `/admin` pour l'administration (`admin.`).
- Les composants spécifiques à un domaine vivent dans
  `resources/js/modules/{module}/components`, pas dans `components/` global.
- Les types partagés restent dans `resources/js/types` ; les types propres à un
  module vivent dans `resources/js/modules/{module}/types.ts`.
- Les appels de routes utilisent Ziggy (`route('professionals.show', slug)`).
- Les formulaires utilisent `useForm` d'Inertia et affichent les erreurs via
  `InputError`.
- Les messages de succès et d'erreur passent par la prop partagée `flash`
  (`success`, `error`), déjà exposée par `HandleInertiaRequests`.

### 7.3 Layouts disponibles

| Layout | Fichier | Usage |
| --- | --- | --- |
| Public | `layouts/public/PublicLayout.vue` | Accueil et pages publiques |
| Authentification | `layouts/auth/AuthSplitLayout.vue` | Connexion, inscription, mots de passe |
| Professionnel | `layouts/app/AppSidebarLayout.vue` | Tableau de bord et paramètres |
| Administration | `layouts/admin/AdminLayout.vue` | Gestion des comptes utilisateurs |

Le layout d'administration (`AdminLayout`) et la variante `admin` de la sidebar
sont utilisés par le module d'identité pour la gestion des comptes
(`/admin/users`). Les autres sections d'administration restent non routées et
seront branchées lors des lots métier.

Le tableau de bord professionnel utilise des données de démonstration statiques,
clairement signalées « Aperçu de démonstration ». Elles seront remplacées par
les contrôleurs et requêtes des lots métier. Aucune note, étoile ou évaluation
client fictive n'est affichée : les indicateurs restent factuels et neutres.

### 7.4 Aperçu embarqué de l'interface publique

La page d'accueil propose un aperçu interactif de l'interface réelle dans un
cadre de smartphone (`components/DevicePreview.vue`). L'aperçu charge la même
page avec le paramètre `?preview=1` dans une iframe same-origin, redimensionnée
par `ResizeObserver` (390 × 844 en mobile, 1280 × 800 en ordinateur).

Le composable `composables/usePreviewMode.ts` détecte à la fois le paramètre
`preview` et le contexte d'iframe (`window.self !== window.top`). En mode aperçu,
la section `#apercu`, les liens de navigation qui y mènent et le lien du pied de
page sont masqués afin d'éviter toute récursion. L'iframe reste interactive :
navigation, menu mobile et défilement internes fonctionnent comme dans
l'application réelle.

La direction visuelle publique repose sur une palette unie (vert `brand`, accent
or, neutres chauds), sans dégradés décoratifs. Les témoignages, notes et avis
fictifs ont été retirés ; les fonctionnalités sont présentées avec leur statut
réel (« Disponible », « Aperçu » ou « Bientôt »).

### 7.5 Module d'identité et d'authentification

Le module d'identité couvre l'inscription, la connexion, la déconnexion, la
réinitialisation du mot de passe, la vérification de l'adresse e-mail, la
gestion du numéro de téléphone, la modification du profil, la modification du
mot de passe et le statut du compte.

#### Statut du compte

Le statut est porté par l'enum `App\Enums\AccountStatus` (`active`,
`suspended`, `closed`) et stocké sur `users.status`.

- Un compte `suspended` ou `closed` est déconnecté et renvoyé vers la page de
  connexion par le middleware `active`
  (`app/Http/Middleware/EnsureAccountIsActive.php`).
- Le statut est appliqué à toutes les routes authentifiées (`auth`, `settings`,
  `admin`) et à la connexion elle-même.
- Le statut n'est jamais accepté depuis une requête utilisateur : il n'est pas
  mass-assignable et seul l'espace d'administration peut le modifier.

#### Rôles et permissions

Les rôles sont gérés par Spatie Laravel Permission et décrits par l'enum
`App\Enums\UserRole` :

| Rôle | Clé | Rôle |
| --- | --- | --- |
| Super Admin | `super-admin` | Toutes les permissions du catalogue |
| Admin | `admin` | Supervision des comptes et du catalogue métier |
| Client | `customer` | Recherche, demandes, réservations, avis |
| Professionnel | `professional` | Gestion de son activité |
| Gestionnaire d'entreprise | `company-manager` | Pilotage de sa structure et de son équipe |

Les droits effectifs ne sont jamais déduits du nom du rôle : le catalogue
`app/Support/Identity/PermissionCatalogue.php` associe à chaque rôle une liste
explicite de permissions granulaires (`users.suspend`, `users.assign_roles`,
`quotes.respond`, `company.team.manage`, …). Le seeder `RoleSeeder` applique ce
catalogue et `IdentityProvisioner` garantit la présence du rôle « client » à
l'inscription.

Un compte inscrit ne reçoit que le rôle « client » : aucune permission
d'administration n'est attribuée automatiquement.

Le premier super administrateur se crée en ligne de commande, jamais depuis
l'inscription publique :

```bash
php artisan app:create-super-admin cheousmane@gmail.com --name="Super administrateur"
```

La commande génère un mot de passe temporaire affiché une seule fois, marque
l'adresse comme vérifiée et active le compte. Si le compte existe déjà, elle se
contente de lui attribuer le rôle `super-admin` sans le dupliquer. Le mot de
passe doit être changé après la première connexion.

#### Règles de sécurité

- Les requêtes utilisent les validations Laravel (`FormRequest`) et la règle
  `App\Rules\MaliPhoneNumber` (numéro malien de huit chiffres, normalisé en
  `+223XXXXXXXX`).
- Les tentatives de connexion sont limitées à 5 essais par couple
  « e-mail + adresse IP » (`LoginRequest`).
- `UserPolicy` protège les comptes : consultation, mise à jour, changement de
  statut, attribution de rôles et suppression. Aucun administrateur ne peut
  agir sur son propre compte ni sur ses propres rôles.
- L'attribution des rôles est réservée au super administrateur
  (permission `users.assign_roles`), et le dernier super administrateur ne peut
  pas être rétrogradé.
- Les routes d'administration (`routes/admin.php`) cumulent les middlewares
  `auth`, `verified`, `active` et `role:super-admin|admin`.

#### Vérification de l'adresse e-mail

La vérification repose sur un lien signé (`signed`), valable le temps défini par
`auth.verification.expire` (60 minutes par défaut).

- Le lien est auto-suffisant : la signature de l'URL et l'empreinte de
  l'adresse e-mail prouvent la demande. Aucune session active n'est requise, le
  compte peut donc être validé depuis un autre appareil ; l'utilisateur est
  connecté automatiquement à ce moment-là.
- Un lien falsifié, expiré ou devenu obsolète (adresse e-mail modifiée) ne
  modifie aucun compte : l'utilisateur revient vers la page de vérification,
  qui explique la situation et propose de demander un nouveau lien.
- La page `verification.notice` reste accessible sans session : un visiteur
  saisit son adresse e-mail pour recevoir un nouveau lien. La réponse est
  identique que l'adresse existe ou non, afin de ne révéler aucun compte.
- En développement, le transport `log` écrit le message dans
  `storage/logs/laravel.log` ; la page de vérification affiche alors
  directement le lien de vérification (jamais en production). Pour recevoir de
  vrais messages en local, passer `MAIL_MAILER=smtp` dans `.env` (Mailpit par
  exemple sur `http://localhost:8025`).

#### Routes du module

| Route | Nom | Description |
| --- | --- | --- |
| `POST /register` | `register` | Inscription (rôle « client ») |
| `POST /login` | `login` | Connexion (limitation de débit, contrôle du statut) |
| `POST /logout` | `logout` | Déconnexion et invalidation de session |
| `GET/POST /forgot-password` | `password.request`, `password.email` | Demande de réinitialisation |
| `GET/POST /reset-password` | `password.reset`, `password.store` | Réinitialisation du mot de passe |
| `GET /verify-email` | `verification.notice` | Invite à vérifier l'adresse e-mail |
| `GET /verify-email/{id}/{hash}` | `verification.verify` | Validation de l'adresse e-mail |
| `GET/PATCH /settings/profile` | `profile.edit`, `profile.update` | Profil, téléphone et e-mail |
| `PUT /settings/password` | `password.update` | Changement de mot de passe |
| `GET /admin/users` | `admin.users.index` | Liste filtrable des comptes |
| `GET /admin/users/{user}` | `admin.users.show` | Détail d'un compte |
| `PATCH /admin/users/{user}/status` | `admin.users.status.update` | Changement de statut |
| `PATCH /admin/users/{user}/roles` | `admin.users.roles.update` | Attribution de rôles |

### 7.6 Module professionnels et entreprises

Le module gère les profils professionnels (`provider_profiles`) et les
entreprises (`businesses`) qui les portent.

#### Tables

| Table | Rôle | Contraintes principales |
| --- | --- | --- |
| `provider_profiles` | Profil professionnel | `user_id`, `business_id` (nul), `category_id`, slug unique, index sur `status`, `business_id`, `category_id` |
| `businesses` | Entreprise | `owner_id` (nul), slug unique, index sur `status` et `city` |
| `business_members` | Collaboration | unique `(business_id, user_id)`, index sur `user_id` |
| `service_areas` | Zone d'intervention | `provider_profile_id` cascade, index `(city, commune)` |
| `provider_profile_media` | Photos, réalisations, couverture | `provider_profile_id` cascade, index `(provider_profile_id, type)` |
| `provider_profile_hours` | Horaires hebdomadaires | unique `(provider_profile_id, day_of_week)` |
| `provider_profile_availabilities` | Créneaux disponibles | unique `(provider_profile_id, date, starts_at)` |

La table `services` est partagée : `professional_id` devient facultatif et
`provider_profile_id` porte les profils du nouveau module. L'ancienne table
`professionals` reste en place tant que les devis, réservations et avis n'ont
pas basculé sur `provider_profiles` (voir §9).

#### Statuts de vérification

L'enum `App\Enums\ProfileStatus` décrit les quatre statuts : `pending`
(en attente), `verified` (vérifié), `refused` (refusé) et `suspended`
(suspendu).

- Un profil `pending`, `refused` ou `suspended` n'est **jamais** présenté
  publiquement comme vérifié : le scope `publiclyVisible()` ne retient que les
  profils `verified` et actifs.
- La visibilité publique tient aussi compte du compte propriétaire et de
  l'entreprise porteuse : la suspension d'un compte ou d'une entreprise masque
  immédiatement ses profils, sans modifier leur statut de vérification. Une
  entreprise suspendue ne peut plus gérer ses profils ni ses collaborateurs.
- Un refus et une suspension conservent un motif (`refusal_reason`) affiché au
  professionnel.
- Le profil reste modifiable et re-soumetable après un refus.

#### Entreprises et collaborateurs

L'enum `App\Enums\BusinessMemberRole` décrit les rôles d'un collaborateur :
`owner` (responsable), `manager` (gestionnaire) et `agent` (collaborateur).

- Seul un responsable (`owner`) peut gérer les collaborateurs ; le dernier
  responsable ne peut être ni rétrogradé ni retiré.
- Un responsable ou un gestionnaire peut piloter les profils de l'entreprise ;
  un collaborateur simple ne le peut pas.
- Une entreprise porte plusieurs profils, chacun rattaché à un compte.

#### Autorisations

Les policies `ProviderProfilePolicy` et `BusinessPolicy` s'appuient sur les
permissions granulaires du catalogue : `provider_profiles.create`,
`provider_profiles.update.own`, `provider_profiles.verify`,
`provider_profiles.refuse`, `provider_profiles.suspend`,
`provider_profiles.media.manage`, `provider_profiles.services.manage`,
`provider_profiles.areas.manage`, `provider_profiles.schedule.manage`,
`businesses.create`, `businesses.manage_members`, `businesses.manage_profiles`.

#### Routes

| Route | Nom | Description |
| --- | --- | --- |
| `GET /pro/profiles` | `pro.profiles.index` | Profils pilotés par l'utilisateur |
| `GET /pro/profiles/create` | `pro.profiles.create` | Formulaire de création |
| `POST /pro/profiles` | `pro.profiles.store` | Création du profil |
| `GET /pro/profiles/{profile}` | `pro.profiles.show` | Gestion du profil |
| `PATCH /pro/profiles/{profile}` | `pro.profiles.update` | Modification du profil |
| `POST /pro/profiles/{profile}/submit` | `pro.profiles.submit` | Soumission à la vérification |
| `POST /pro/profiles/{profile}/deactivate` | `pro.profiles.deactivate` | Désactivation |
| `POST /pro/profiles/{profile}/media` | `pro.profiles.media.store` | Ajout d'un média |
| `PUT /pro/profiles/{profile}/areas` | `pro.profiles.areas.update` | Zones d'intervention |
| `PUT /pro/profiles/{profile}/schedule` | `pro.profiles.schedule.update` | Horaires et disponibilités |
| `POST /pro/profiles/{profile}/services` | `pro.profiles.services.store` | Services proposés |
| `GET /pro/businesses` | `pro.businesses.index` | Entreprises de l'utilisateur |
| `POST /pro/businesses` | `pro.businesses.store` | Création d'une entreprise |
| `GET /pro/businesses/{business}` | `pro.businesses.show` | Fiche et collaborateurs |
| `POST /pro/businesses/{business}/members` | `pro.businesses.members.store` | Ajout d'un collaborateur |
| `PATCH /pro/businesses/{business}/members/{member}` | `pro.businesses.members.update` | Changement de rôle |
| `DELETE /pro/businesses/{business}/members/{member}` | `pro.businesses.members.destroy` | Retrait d'un collaborateur |
| `GET /admin/provider-profiles` | `admin.provider-profiles.index` | File de vérification |
| `GET /admin/provider-profiles/{profile}` | `admin.provider-profiles.show` | Dossier de vérification |
| `POST /admin/provider-profiles/{profile}/review` | `admin.provider-profiles.review` | Approbation, refus, suspension |

### 7.7 Catalogue de services et recherche publique

#### Catalogue

Le catalogue initial (voir `database/seeders/CategorySeeder.php`) compte cinq
catégories de premier niveau, chacune déclinée en sous-catégories :

| Catégorie | Sous-catégories |
| --- | --- |
| Maison et habitat | Plomberie, Électricité, Menuiserie, Maçonnerie et BTP, Peinture, Nettoyage, Jardinage, Chauffage, Piscine, Énergie solaire |
| Informatique et technologie | Dépannage, Téléphone, Réseau, Site web, Câblage, Vidéosurveillance, Maintenance, Bureautique, Infographie, Branding, Logo et charte, Supports de communication, Retouche photo, Création de contenu |
| Transport et automobile | Mécanique auto, Mécanique moto, Diagnostic auto, Vidange, Freinage, Pneumatique, Électricité auto, Tôlerie, Alignement des roues, Taxi, Livraison, Déménagement, Remorquage, Location de véhicule |
| Services personnels | Coiffure, Esthétique, Massage, Ménage, Garde d’enfants, Cours particuliers, Soutien, Santé à domicile, Traiteur, Événementiel |
| Professionnels et entreprises | Comptabilité, Droit, Conseil, Traduction, Recrutement, Nettoyage de bureaux, Formation, Marketing, Audit |

La hiérarchie repose sur les tables `categories` et `subcategories`
(`subcategories.category_id`). Les catégories hors catalogue initial sont
désactivées sans être supprimées : les données historiques restent cohérentes.

#### Gestion des services

Un service (`services`, rattaché à `provider_profiles`) décrit une prestation :
nom, description, type de tarification (`fixed`, `from`, `range`, `quote`,
`hourly`), bornes de prix, durée estimée et activation. Les règles de
validation (`app/Http/Requests/Pro/ServiceRequest.php`) imposent un prix pour
un tarif fixe et une fourchette cohérente (`price_max` ≥ `price_min`) pour un
tarif au fourchette.

#### Recherche publique

Le moteur `app/Services/Search/ProviderProfileSearch.php` applique les
filtres suivants : mot-clé, catégorie, sous-catégorie, commune, quartier,
fourchette de prix, note minimale, disponibilité et statut de vérification.

- Seuls les profils visibles publiquement sont renvoyés : le scope
  `publiclyVisible()` exige un profil vérifié et actif, un compte propriétaire
  actif et, le cas échéant, une entreprise vérifiée.
- Les relations affichées sont chargées en eager loading et les compteurs
  utilisent des sous-requêtes (`withCount`) : aucune requête N+1, le nombre de
  requêtes reste stable quel que soit le nombre de résultats.
- Les tris disponibles sont la pertinence (nom commercial commençant par le
  mot-clé en tête, note en départage), la note et les nouveautés.
- Les filtres sont exposés en français dans l'URL (`q`, `categorie`,
  `sous-categorie`, `commune`, `quartier`, `prix-min`, `prix-max`, `note-min`,
  `disponible`, `tri`) et traduits en filtres techniques par `SearchRequest`.
- L'autocomplétion (`GET /recherche/suggestions`) interroge les profils, les
  catégories et les sous-catégories ; elle est limitée en débit.
- La structure des filtres est prête pour une recherche géographique
  avancée : les colonnes `city`, `commune`, `quartier`, `latitude` et
  `longitude` sont déjà indexées.

#### Pages publiques

| Route | Nom | Description |
| --- | --- | --- |
| `GET /categories` | `categories.index` | Liste des catégories et sous-catégories |
| `GET /categories/{category:slug}` | `categories.show` | Détail d'une catégorie et ses professionnels |
| `GET /recherche` | `search` | Résultats de recherche, filtres et tri |
| `GET /recherche/suggestions` | `search.suggestions` | Autocomplétion (JSON) |
| `GET /professionnels/{profile:slug}` | `professionals.show` | Profil public d'un professionnel |
| `GET /services/{service}` | `services.show` | Détail public d'un service |

### 7.8 Demandes de devis et réservations

#### Modèle de données

| Table | Rôle |
| --- | --- |
| `service_requests` | Demande de devis : client, profil destinataire, service, description, localisation, budget, date souhaitée, statut |
| `service_request_quotes` | Devis envoyé par le professionnel : montant, durée, validité, statut |
| `service_request_messages` | Questions et réponses échangées sur une demande |
| `service_request_attachments` | Photos et documents joints (disque privé `local`, 5 fichiers max, 4 Mo par fichier) |
| `provider_bookings` | Réservation : créneau (`starts_at`, `ends_at`), durée, montant, statut, annulation |

Les anciennes tables `quotes` et `bookings` restent en place pour les écrans
historiques ; le nouveau parcours utilise exclusivement `service_requests` et
`provider_bookings` (voir §9.0).

#### Statuts

Demandes (`App\Enums\ServiceRequestStatus`) : `pending`, `in_progress`,
`quoted`, `accepted`, `refused`, `cancelled`, `completed`.

Réservations (`App\Enums\BookingStatus`) : `pending`, `confirmed`,
`completed`, `cancelled`.

Transitions autorisées côté professionnel :

| Cible | Statuts de départ |
| --- | --- |
| `in_progress` | `pending` |
| `refused` | `pending`, `in_progress`, `quoted` |
| `completed` | `accepted` |

Côté client, l'annulation est possible tant que la demande n'est ni acceptée,
ni close. L'acceptation d'un devis place la demande en `accepted` et crée la
réservation correspondante.

#### Routes

| Route | Nom | Accès |
| --- | --- | --- |
| `GET /demandes` | `requests.index` | Client authentifié |
| `GET /demandes/nouveau` | `requests.create` | Client authentifié |
| `POST /demandes` | `requests.store` | Client authentifié |
| `GET /demandes/{serviceRequest}` | `requests.show` | Client propriétaire ou professionnel destinataire |
| `GET /demandes/{serviceRequest}/pieces-jointes/{attachment}` | `requests.attachments.download` | Parties prenantes de la demande |
| `POST /demandes/{serviceRequest}/messages` | `requests.messages.store` | Parties prenantes, demande ouverte |
| `POST /demandes/{serviceRequest}/annuler` | `requests.cancel` | Client propriétaire |
| `POST /demandes/{serviceRequest}/devis/{quote}/accepter` | `requests.quotes.accept` | Client propriétaire |
| `GET /reservations` | `bookings.index` | Client authentifié |
| `POST /reservations/{booking}/annuler` | `bookings.cancel` | Client ou professionnel concerné |
| `GET /pro/demandes` | `pro.requests.index` | Profil vérifié piloté par le compte |
| `GET /pro/demandes/{serviceRequest}` | `pro.requests.show` | Profil destinataire |
| `POST /pro/demandes/{serviceRequest}/statut` | `pro.requests.status.update` | Profil destinataire |
| `POST /pro/demandes/{serviceRequest}/messages` | `pro.requests.messages.store` | Profil destinataire |
| `POST /pro/demandes/{serviceRequest}/devis` | `pro.requests.quotes.store` | Profil destinataire |
| `GET /pro/reservations` | `pro.bookings.index` | Profil vérifié piloté par le compte |
| `POST /pro/reservations/{booking}/statut` | `pro.bookings.status.update` | Profil destinataire |

#### Sécurité et cohérence

- `ServiceRequestPolicy` et `ProviderBookingPolicy` vérifient systématiquement
  l'appartenance : un client n'accède qu'à ses demandes, un professionnel qu'aux
  demandes de ses profils. Les gestionnaires d'entreprise habilités
  (`ProviderProfile::isManagedBy`) sont assimilés au professionnel.
- Les changements de statut passent par des transitions explicites ; une
  transition interdite est refusée avant toute écriture.
- `BookingScheduler` exécute l'acceptation d'un devis et la création de la
  réservation dans une transaction. Le profil est verrouillé
  (`lockForUpdate`) puis les chevauchements sont contrôlés : deux réservations
  actives (`pending`, `confirmed`) ne peuvent jamais se superposer.
- `AvailabilityChecker` applique les disponibilités ponctuelles déclarées
  (elles font foi, y compris les plages marquées indisponibles), puis, à
  défaut, les horaires hebdomadaires d'ouverture.
- Les devis et messages sont refusés sur une demande close. Un devis ne peut
  être accepté que par le client propriétaire et uniquement tant qu'il est en
  attente.
- Les pièces jointes sont validées (`jpg`, `jpeg`, `png`, `webp`, `pdf`) et
  stockées sur le disque privé `local`. L'URL exposée pointe vers
  `requests.attachments.download`, qui vérifie l'accès à la demande avant de
  servir le fichier. Un repli sur le disque `public` permet de télécharger les
  pièces jointes historiques sans les exposer directement.

Les tests correspondants se trouvent dans
`tests/Feature/Requests/ServiceRequestTest.php` et
`tests/Feature/Requests/BookingTest.php`.

### 7.9 Avis, favoris et notifications

#### Modèle de données

| Table | Rôle |
| --- | --- |
| `provider_reviews` | Avis d'un client sur une réservation terminée : note 1–5, commentaire, statut de modération, publication et réponse du professionnel |
| `favorites` | Professionnels enregistrés par un utilisateur ; contrainte unique (`user_id`, `provider_profile_id`) |
| `notifications` | Table standard Laravel (`database`), alimentée par les classes de notification applicatives |

Un avis est rattaché à une réservation unique (`provider_booking_id`), ce qui
interdit plusieurs évaluations pour la même prestation. Les statuts
(`App\Enums\ReviewStatus`) sont `pending`, `approved` et `rejected` ; seuls les
avis approuvés sont publics et alimentent `rating_average` / `rating_count`.

#### Règles métier des avis

- Éligibilité : le client est propriétaire de la réservation, la réservation est
  `completed`, `completed_at` et `starts_at` sont renseignés, le créneau est
  passé et aucun avis n'existe déjà.
- La création est exécutée dans une transaction avec verrou sur la réservation
  (`ReviewService::create`) afin d'empêcher deux avis concurrents.
- La modération appartient à l'administration (`reviews.manage`). Un avis refusé
  reste privé et ne modifie pas la note du profil.
- Le professionnel ne peut répondre qu'à un avis approuvé et non encore répondu
  (`ProviderReviewPolicy::respond`).
- Les agrégats du profil sont recalculés exclusivement à partir des avis
  approuvés après chaque décision de modération.

#### Routes

| Route | Nom | Accès |
| --- | --- | --- |
| `GET /avis` | `reviews.index` | Client authentifié (ses propres avis) |
| `GET /reservations/{booking}/avis` | `reviews.create` | Client propriétaire d'une réservation éligible |
| `POST /reservations/{booking}/avis` | `reviews.store` | Client propriétaire d'une réservation éligible |
| `GET /pro/avis` | `pro.reviews.index` | Profil vérifié piloté par le compte |
| `POST /pro/avis/{review}/reponse` | `pro.reviews.respond` | Gestionnaire du profil évalué |
| `GET /admin/avis` | `admin.reviews.index` | Permission `reviews.manage` |
| `POST /admin/avis/{review}/moderation` | `admin.reviews.moderate` | Permission `reviews.manage` |
| `POST /demandes/{serviceRequest}/devis/{quote}/refuser` | `requests.quotes.reject` | Client propriétaire de la demande |

#### Favoris

- Accessibles à tout compte authentifié, vérifié et actif.
- Seuls les profils publiquement visibles peuvent être ajoutés.
- `FavoriteController` utilise `firstOrCreate` et la contrainte d'unicité pour
  éviter les doublons ; le retrait est idempotent.
- La liste est paginée et limitée aux favoris du compte connecté.

#### Notifications

- `ApplicationNotification` centralise les canaux via
  `config/notifications.php` (`database` par défaut, `mail` activable sans
  modifier les classes concrètes, canal push à brancher via un canal dédié).
- Neuf notifications couvrent les parcours : nouvelle demande, réponse du
  professionnel, nouveau devis, devis accepté/refusé, nouvelle réservation,
  réservation confirmée/annulée, nouvel avis.
- `NotificationDispatcher::toProfileManagers()` notifie le propriétaire du
  profil, le responsable de l'entreprise et les collaborateurs habilités
  (`ProviderProfile::managers()`).
- Le centre de notifications expose l'historique paginé, le compteur de non
  lues, le marquage individuel ou global comme lu, la suppression et les liens
  vers les ressources concernées.
- Les props Inertia partagées `notificationsPreview` et
  `unreadNotificationsCount` alimentent le menu de notifications.

Les tests correspondants se trouvent dans
`tests/Feature/Reviews/ReviewTest.php`,
`tests/Feature/Reviews/FavoriteTest.php` et
`tests/Feature/Notifications/NotificationTest.php`.

---

## 8. Conventions transverses

### 8.1 Nommage

- Classes, méthodes, variables, tables et colonnes en anglais.
- Commentaires, documentation et libellés en français.
- Statuts en constantes ou enums PHP backed (`string`).
- Références publiques : slug pour les professionnels, `DV-…` pour les devis,
  `RV-…` pour les réservations.

### 8.2 Autorisation

- Rôles Spatie : `super-admin`, `admin`, `customer`, `professional`,
  `company-manager` (voir §7.5).
- Les droits effectifs proviennent des permissions granulaires du catalogue,
  jamais du seul nom du rôle.
- Les contrôleurs ne contiennent pas de logique de rôle ; utiliser des
  `Policy` et des `Gate`.
- Les routes professionnelles vérifient l'existence et la propriété du profil
  professionnel.
- Les actions sensibles (accepter un devis, annuler une réservation) sont
  autorisées par policy et validées par transition de statut.

### 8.3 Validation

- Une `FormRequest` par cas d'usage en écriture.
- Messages en français dans `lang/fr` lorsque les fichiers de langue seront
  publiés.
- Les règles métier (date future, montant cohérent, transition autorisée) sont
  dans des `Actions` ou `Services`, pas dans les contrôleurs.

### 8.4 Événements et listeners

Exemples prévus :

- `QuoteAccepted` → création d'une réservation, notification des deux parties.
- `BookingCompleted` → demande d'avis, mise à jour des statistiques.
- `ReviewPublished` → recalcul de la note moyenne du professionnel.
- `SubscriptionExpired` → retrait des avantages payants.

Les listeners qui envoient des notifications ou des e-mails sont mis en file
d'attente (`ShouldQueue`).

### 8.5 Performance

- Pagination systématique des listes.
- Eager loading explicite (`with`) dans les requêtes.
- Index composites déjà présents sur les recherches courantes.
- `Model::preventLazyLoading()` pourra être activé hors production après
  correction des tests concernés.
- Les agrégats (note moyenne, nombre d'avis) sont dénormalisés et mis à jour par
  événements.

### 8.6 Sécurité

- Aucun secret dans le dépôt ; `.env` ignoré.
- Mots de passe hachés, validation de mot de passe Fortify-like du starter kit.
- Vérification d'e-mail activable par `MustVerifyEmail`.
- Limitation de débit sur les routes sensibles (déjà en place pour la
  vérification d'e-mail).
- Webhooks de paiement signés et idempotents.
- Téléversements validés (type MIME, taille) et stockés hors racine publique.

### 8.7 Recherche

Au MVP : filtres SQL (catégorie, sous-catégorie, ville, note, statut actif).
Ensuite : abstraction `ProfessionalSearch` avec implémentation SQL, puis
implémentation PostgreSQL `tsvector` ou moteur externe si le volume l'exige.

### 8.8 Paiements et abonnements

- `PaymentGateway` : contrat unique (`charge`, `refund`, `handleWebhook`).
- Les montants sont en XOF entiers.
- Chaque paiement possède une clé d'idempotence.
- `Subscriptions` calcule les droits ; `Payments` ne connaît pas les plans.
- Les champs d'abonnement actuels sur `professionals` sont temporaires et seront
  migrés vers `subscriptions`.

---

## 9. Évolutions de schéma prévues

### 9.0 Bascule de `professionals` vers `provider_profiles`

Le module professionnels et entreprises (§7.6) s'appuie sur `provider_profiles`.
La table historique `professionals` reste utilisées par les devis, les
réservations et les avis, qui pointent encore vers `professional_id`.

La bascule se fera dans un lot dédié :

1. reprise des lignes `professionals` vers `provider_profiles` (statut, ville,
   coordonnées, médias) ;
2. ajout de `provider_profile_id` à `quotes`, `bookings` et `reviews` ;
3. retrait des colonnes dépréciées de `professionals` ;
4. retrait du modèle `Professional` et de la table `services.professional_id`.

### 9.1 Scission `quotes` → `service_requests` + `quotes`

**Statut : réalisé pour le nouveau parcours.**

Le schéma historique (`quotes`, `bookings`) reste utilisé par les écrans
antérieurs. Le nouveau parcours s'appuie sur les tables suivantes :

```text
service_requests
├── id, customer_id, provider_profile_id, service_id, category_id
├── reference, title, description
├── address, city, commune, quartier, latitude, longitude
├── budget_min, budget_max, desired_date, preferred_time
├── status (pending, in_progress, quoted, accepted, refused, cancelled, completed)
├── responded_at, closed_at
└── timestamps

service_request_quotes
├── id, service_request_id, provider_profile_id, service_id
├── reference, message, amount, duration_minutes
├── status (pending, accepted, rejected, expired), valid_until
├── responded_at
└── timestamps

service_request_messages / service_request_attachments
provider_bookings
├── id, customer_id, provider_profile_id
├── service_request_id, service_request_quote_id, service_id
├── reference, starts_at, ends_at, duration_minutes, amount
├── address, notes, status
├── cancelled_by, cancellation_reason, cancelled_at
├── confirmed_at, completed_at
└── timestamps
```

La reprise des données historiques de `quotes`/`bookings` vers ces tables
reste à planifier ; elle se fera dans un lot dédié, sans casser les écrans
existants.

### 9.2 Abonnements

Retirer à terme de `professionals` :

- `subscription_plan`
- `subscription_expires_at`

Les remplacer par `plans`, `subscriptions` et éventuellement
`subscription_invoices`.

### 9.3 Autres tables à créer

- `favorites` : `user_id`, cible polymorphe ou `professional_id` + contrainte
  d'unicité.
- `payments` : `payable_type`, `payable_id`, montant, devise, statut,
  référence externe, clé d'idempotence.
- `notifications` : table standard Laravel.
- `user_addresses` si plusieurs adresses sont nécessaires.

---

## 10. Feuille de route recommandée

| Lot | Contenu | Critère de sortie |
| --- | --- | --- |
| **0** | Socle, configuration, factories, tests | Fait : migrations, seed, 36 tests, TypeScript propre |
| **1** | Identity + Users | Auth déplacée, profil client, rôles vérifiés, tests |
| **2** | Businesses + Catalog | Inscription pro, profil, catégories, prestations |
| **3** | Search | Annuaire public filtrable et paginé |
| **4** | ServiceRequests + Quotes | Fait : demande client, réponse pro, acceptation, messages, pièces jointes |
| **5** | Bookings + Reviews | Fait : réservations, clôture, avis modérés et réponse du professionnel |
| **6** | Favorites + Notifications | Fait : favoris, centre de notifications in-app et socle e-mail/push |
| **7** | Subscriptions + Payments | Plans, droits, paiement et webhooks |

Chaque lot doit inclure :

- migrations + modèles + factories ;
- policies et validation ;
- pages Inertia et routes ;
- tests Feature ;
- mise à jour de ce document si une décision change.

---

## 11. Tests et intégration continue

### 11.1 Tests locaux

```bash
php artisan migrate:fresh --seed
php artisan test
npx vue-tsc --noEmit
npx eslint .
npm run format:check
vendor/bin/pint --test
npm run build
```

### 11.2 CI

- **SQLite** : migration, seed, build et suite Pest, avec `DB_CONNECTION` et
  `DB_DATABASE` explicitement définis dans le workflow.
- **PostgreSQL 17** : mêmes migrations et suite Pest sur un service PostgreSQL.
- **Qualité** : `composer validate --strict`, Pint, Prettier, ESLint, TypeScript.
- `.env.example` décrit une configuration locale complète (SQLite, mail `log`,
  queue et cache `database`) alignée sur `config/*.php`.

La double exécution SQLite/PostgreSQL garantit que le basculement de production
ne dépend d'aucune spécificité du moteur local.

---

## 12. Décisions d'architecture (ADR synthétiques)

| Décision | Choix | Raison |
| --- | --- | --- |
| Style | Monolithe modulaire | Équipe réduite, vitesse, simplicité de déploiement |
| Modules | Créés au besoin | Éviter les dossiers vides et la complexité artificielle |
| Migrations | Centralisées | Ordre fiable, démarrage simple |
| Modèles existants | Restent dans `app/Models` au départ | Éviter un déplacement massif sans valeur fonctionnelle |
| Repositories | Non | Eloquent suffit ; `Queries`/`Actions` pour la complexité |
| Base locale | SQLite | Zéro installation, tests rapides |
| Base production | PostgreSQL | Robustesse, recherche plein texte, concurrence |
| Montants | XOF entiers | Pas de décimales au franc CFA |
| URL publiques | Françaises | SEO et lisibilité pour le public malien |
| Code | Anglais | Convention Laravel et cohérence de l'écosystème |
| Recherche | SQL d'abord | Pas de moteur externe avant besoin réel |
| Notifications | Laravel natif | Database + mail, SMS plus tard |

---

## 13. Prochaines étapes concrètes

À l'ouverture du lot 1 :

1. Créer `app/Modules/Identity` et y déplacer les contrôleurs `Auth\*`, les
   requests et les routes, sans changer les URL.
2. Créer `app/Modules/Users` pour le profil client et les préférences.
3. Ajouter les policies de base et les tests d'autorisation.
4. Ne créer `Businesses` qu'au lot 2, avec ses migrations et son provider.
5. Mettre à jour ce document à chaque décision structurante.

Tant qu'un module n'a pas de code, son dossier ne doit pas exister.

## Back-office d'administration

Le tableau de bord analytique est servi par /admin. Les indicateurs sont calculés uniquement à partir des données réelles (utilisateurs, professionnels, entreprises, profils en attente, demandes, réservations, prestations terminées, abonnements actifs, revenus encaissés). Les revenus proviennent exclusivement des paiements au statut paid.

Modules : utilisateurs, entreprises, professionnels, vérification des profils, catégories, sous-catégories, services, demandes, réservations, avis, abonnements, paiements, signalements, journal d'audit.

Chaque action sensible est journalisée via App\Services\Admin\AuditLogger et contrôlée par les policies et permissions Spatie.

