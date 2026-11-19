<?php

namespace App\Enums;

/**
 * Motifs de signalement proposés aux utilisateurs.
 */
enum ReportReason: string
{
    case FakeProfile = 'fake_profile';

    case InappropriateContent = 'inappropriate_content';

    case Fraud = 'fraud';

    case Spam = 'spam';

    case Other = 'other';

    /**
     * Libellé affiché dans l'interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::FakeProfile => 'Faux profil ou usurpation',
            self::InappropriateContent => 'Contenu inapproprié',
            self::Fraud => 'Fraude ou tentative d’arnaque',
            self::Spam => 'Spam ou démarchage abusif',
            self::Other => 'Autre motif',
        };
    }
}
