# Rapport d'audit — MaliConnect

Date : 10 novembre 2026
Périmètre : socle Laravel, configuration, frontend, base de données, tests.
Hors périmètre : fonctionnalités métier (aucune n'a été développée).

---

## 1. Synthèse

Le socle est sain et moderne : Laravel 12.72, PHP 8.2, Inertia 2, Vue 3.5,
Tailwind 3.4, Spatie Permission et Pest 3. La base SQLite fonctionne, les
migrations et les seeders passent, et 7 factories métier sont disponibles.

L'audit a toutefois relevé plusieurs problèmes de configuration qui auraient
bloqué le développement :

- `vue-tsc` ne démarrait pas à cause d'un `tsconfig.json` invalide ;
- plusieurs composants Vue avaient des erreurs de typage ;
- les alias shadcn-vue pointaient vers des dossiers inexistants ou mal casés ;
- `.env.example` était resté celui du starter kit Laravel ;
- le fuseau horaire était figé sur UTC ;
- `HandleInertiaRequests` dupliquait `parent::share()` et exposait une citation
  de démonstration ;
- la CI exécutait des commandes correctives (`pint`, `format`, `lint --fix`) au
  lieu de vérifier le code ;
- aucun job PostgreSQL n'existait ;
- `professionals.user_id` n'était pas unique ;
- le lien symbolique de stockage public n'était pas créé.

Tous ces points ont été corrigés. Aucune fonctionnalité métier n'a été ajoutée.

---

## 2. Versions et dépendances

### 2.1 Environnement

| Élément | Version |
| --- | --- |
| Laravel | 12.72.0 |
| PHP | 8.2.12 |
| Composer | 2.8.8 |
| Node.js | 22.14.0 |
| npm | 11.19.1 |

Extensions PHP vérifiées : `pdo_sqlite`, `pdo_pgsql`, `pgsql`.

### 2.2 Dépendances PHP principales

| Package | Version |
| --- | --- |
| `laravel/framework` | 12.72.0 |
| `inertiajs/inertia-laravel` | 2.0.28 |
| `spatie/laravel-permission` | 6.25.0 |
| `tightenco/ziggy` | 2.6.5 |
| `laravel/tinker` | 2.11.1 |
| `pestphp/pest` | 3.8.7 |
| `pestphp/pest-plugin-laravel` | 3.2.0 |
| `laravel/pint` | 1.30.4 |

### 2.3 Dépendances JavaScript principales

| Package | Version |
| --- | --- |
| `vue` | 3.5.45 |
| `@inertiajs/vue3` | 2.3.29 |
| `vite` | 6.4.6 |
| `tailwindcss` | 3.4.19 |
| `typescript` | 5.9.3 |
| `vue-tsc` | 3.3.14 |
| `eslint` | 9.39.5 |
| `prettier` | 3.9.10 |
| `ziggy-js` | 2.6.5 |

Aucune dépendance manquante n'a été identifiée pour le socle. Aucun package
métier n'a été ajouté prématurément.

---

## 3. Structure et routes

### 3.1 Structure

- `app/Models` contient les 8 modèles actuels à plat.
- `app/Http/Controllers/Auth` et `Settings` proviennent du starter kit.
- `resources/js` suit la structure Inertia/Vue standard.
- Les composants shadcn-vue sont dans `resources/js/components/ui`.
- Aucun module métier n'est encore créé, conformément à la consigne.

### 3.2 Routes

Routes existantes :

- accueil `/` ;
- dashboard `/dashboard` (auth + verified) ;
- authentification complète (login, register, mot de passe oublié, reset) ;
- vérification d'e-mail et confirmation de mot de passe ;
- paramètres du compte (profil, mot de passe, apparence).

Aucune route marketplace. Le cache de routes a été vérifié avec succès.

---

## 4. Base de données et migrations

### 4.1 État

- 12 migrations métier + migrations Laravel + permissions Spatie.
- SQLite local opérationnel.
- Seed : 12 catégories, 62 sous-catégories, rôles `admin`, `professional`,
  `customer`.
- 7 factories métier : Category, Subcategory, Professional, Service, Quote,
  Booking, Review.
- `professionals.user_id` désormais unique (migration ajoutée).
- Lien `public/storage` créé.

### 4.2 Observations de schéma

1. `quotes` fusionne la demande client et la réponse professionnelle. Une
   scission `service_requests` / `quotes` est recommandée au lot 4.
2. `professionals.subscription_plan` et `subscription_expires_at` sont des
   champs provisoires à migrer vers un module `Subscriptions`.
3. Les tables `favorites`, `subscriptions`, `payments`, `notifications` et
   `service_requests` n'existent pas encore ; c'est normal, elles seront créées
   dans leurs lots.
4. `rating_average` et `rating_count` sont dénormalisés ; ils devront être mis à
   jour par événement, pas manuellement.
5. Les montants sont en `decimal(12, 0)` : cohérent avec le franc CFA sans
   décimales.

---

## 5. Configuration

### 5.1 Corrigé

- `config/app.php` : fuseau horaire piloté par `APP_TIMEZONE`.
- `.env` : `APP_TIMEZONE=Africa/Bamako`, bloc SQLite documenté, bloc PostgreSQL
  commenté, `DB_FOREIGN_KEYS`, `DB_BUSY_TIMEOUT`.
- `.env.example` : réécrit pour MaliConnect (nom, URL, locale française,
  timezone, SQLite, PostgreSQL commenté, e-mail de contact).
- `config/database.php` : `busy_timeout`, `search_path` et `sslmode` pilotés par
  l'environnement.
- `phpunit.xml` : locale française et timezone de test.
- `components.json` : alias corrigés vers `@/components`, `@/components/ui`,
  `@/lib`, `@/composables`.
- `package.json` : nom, version, description, scripts `type-check`,
  `lint:check`, contrainte Inertia alignée sur la version installée.
- `composer.json` : nom, description, mots-clés, scripts `test` et `lint`.
- `HandleInertiaRequests` : doublon supprimé, citation de démonstration retirée,
  prop `flash` ajoutée en lazy.
- `AppServiceProvider` : commentaires traduits en français.
- `tsconfig.json` : types invalides retirés.
- `resources/js/types/vite-env.d.ts` : déclaration Vite ajoutée.
- `resources/js/app.ts` : déclaration de module invalide retirée.
- Composants Vue : typage de `usePage<SharedData>()`, avatar nullable, `NavItem`
  unifié sur `href`, breadcrumbs par défaut, `TextLink` typé, `Welcome` corrigé.
- CI : workflows de tests SQLite + PostgreSQL 17 et de qualité en mode
  vérification.
- Documentation : `docs/architecture.md` ajouté.

### 5.2 PostgreSQL

Le serveur PostgreSQL 17 est installé et démarré sur le port 5432. Le mot de
passe du compte `postgres` n'étant pas connu, un cluster temporaire en
authentification `trust` a été initialisé sur le port 55432 pour valider la
configuration. Résultat :

- migrations : OK ;
- seed : OK (12 catégories, 62 sous-catégories, 3 rôles) ;
- suite Pest : 36 tests, 98 assertions, toutes réussies.

Le cluster temporaire a été arrêté et supprimé. Le basculement production se
fait uniquement par variables d'environnement, sans modification de code, et la
CI exécute également la suite sur PostgreSQL 17.

### 5.3 Sécurité des dépendances npm

`npm audit` signalait 30 vulnérabilités : 2 critiques, 17 hautes, 8 modérées et
3 basses. Elles ont été corrigées sans `npm audit fix --force` :

- mise à jour de Vite 6.1.1 → 6.4.6 ;
- mise à jour de Vue 3.5.13 → 3.5.45 ;
- mise à jour d'Inertia Vue 2.0.3 → 2.3.29 ;
- mise à jour de Ziggy 2.5.1 → 2.6.5 ;
- mise à jour d'ESLint 9.21.0 → 9.39.5 ;
- mise à jour de TypeScript 5.7.3 → 5.9.3 et vue-tsc 2.2.2 → 3.3.14 ;
- mise à jour de Tailwind CSS 3.4.19 et de Sucrase 3.35.1 ;
- mise à jour des plugins Prettier, ESLint et des utilitaires Vite ;
- ajout d'un override ciblé `minimatch@11.0.6` pour
  `@typescript-eslint/typescript-estree`, dont l'API utilisée est compatible ;
- suppression des dépendances optionnelles Linux inutiles (Tailwind 4,
  Lightning CSS) et alignement de Rollup Linux sur 4.68.2.

Résultat final : **`npm audit` et `npm audit --omit=dev` retournent 0
vulnérabilité**. Les versions majeures incompatibles (Vite 7/8, Tailwind 4,
ESLint 10, Inertia 3) n'ont pas été forcées.

---

## 6. Tests et qualité

### 6.1 Avant corrections

- `vue-tsc` échouait au démarrage (configuration de types invalide).
- Erreurs de typage dans `AppHeader`, `AuthSplitLayout`, `Welcome`,
  `AppSidebarHeader`, `NavMain`, `TextLink`, `UserInfo`.
- `npx eslint .` passait.
- `php artisan route:cache` passait.

### 6.2 Après corrections

- `npx vue-tsc --noEmit` : aucune erreur.
- `npx eslint .` : aucune erreur.
- Suite Pest : 36 tests réussis, 98 assertions.
- `npm audit` : 0 vulnérabilité.
- CI : vérifications Pint, Prettier, ESLint, TypeScript, Composer, Pest sur
  SQLite et PostgreSQL.

---

## 7. Validation finale

Commandes exécutées ou à exécuter avant livraison :

```bash
php artisan migrate:fresh --seed
php artisan test
npm run build
npx vue-tsc --noEmit
npx eslint .
npm run format:check
vendor/bin/pint --test
composer validate
php artisan optimize
php artisan optimize:clear
```

Résultats connus :

- migrations et seed SQLite : OK ;
- migrations et seed PostgreSQL 17.4 : OK ;
- tests SQLite : 36 réussis, 98 assertions ;
- tests PostgreSQL 17.4 : 36 réussis, 98 assertions ;
- `npm audit` : 0 vulnérabilité ;
- TypeScript : OK ;
- ESLint : OK ;
- Prettier : OK ;
- Pint : OK ;
- build Vite : OK ;
- route cache : OK.

---

## 8. Risques et points de vigilance

| Risque | Impact | Mitigation |
| --- | --- | --- |
| `quotes` fusionne demande et réponse | Refonte douloureuse si le volume augmente | Scission prévue au lot 4, avant mise en production |
| Champs d'abonnement sur `professionals` | Couplage prématuré | Migration vers `Subscriptions` au lot 7 |
| Absence de tests PostgreSQL locaux | Différences de comportement | Job CI PostgreSQL 17 + validation locale sur cluster temporaire |
| Recherche SQL au départ | Performances à volume élevé | Abstraction puis index plein texte |
| Paiements non modélisés | Risque de dette | Contrat `PaymentGateway` avant toute intégration |
| Mot de passe PostgreSQL local inconnu | Pas de test manuel sur le service 5432 | Cluster temporaire validé ; CI ; ne pas bloquer le développement |

---

## 9. Conclusion

Le socle est prêt pour le développement fonctionnel. La configuration est
alignée sur le produit, SQLite est opérationnel en local, PostgreSQL est préparé
pour la production, et la CI vérifie les deux moteurs. L'architecture cible est
documentée dans `docs/architecture.md`.

Aucun module métier n'a été créé artificiellement : ils seront introduits lot
par lot, en commençant par Identity et Users.
