<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Abonnements SaaS
    |--------------------------------------------------------------------------
    |
    | Les valeurs commerciales (prix, limites, fonctionnalités) vivent dans la
    | table `plans` et sont modifiables depuis l'administration. Ce fichier ne
    | contient que les règles de fonctionnement du cycle de vie.
    |
    */

    'subscriptions' => [

        // Nombre de jours de grâce après l'échéance avant expiration effective.
        'grace_days' => (int) env('SUBSCRIPTION_GRACE_DAYS', 7),

        // Délai avant échéance pour envoyer le rappel de renouvellement.
        'reminder_days' => (int) env('SUBSCRIPTION_REMINDER_DAYS', 7),

        // Devise de référence de la plateforme.
        'currency' => env('SUBSCRIPTION_CURRENCY', 'XOF'),

        // Instructions affichées au professionnel avant le dépôt d'une preuve.
        'payment_instructions' => env(
            'SUBSCRIPTION_PAYMENT_INSTRUCTIONS',
            'Effectuez le paiement au numéro Orange Money officiel de MaliConnect, puis joignez une capture ou un reçu lisible. Votre abonnement ne sera activé qu’après vérification par notre équipe.',
        ),

        // Disque privé utilisé pour les preuves de paiement.
        'proof_disk' => env('SUBSCRIPTION_PROOF_DISK', 'local'),

        // Taille maximale d'une preuve, en kilo-octets.
        'proof_max_kb' => (int) env('SUBSCRIPTION_PROOF_MAX_KB', 5120),

        // Moyens de paiement acceptés.
        'payment_methods' => [
            'mobile_money',
            'orange_money',
            'bank_transfer',
            'cash',
            'other',
        ],

        // Le mode de paiement par défaut pour la création d'un paiement manuellement.
        'default_gateway' => env('SUBSCRIPTION_DEFAULT_GATEWAY', 'manual'),

    ],

];
