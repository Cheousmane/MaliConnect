import type { AccountStatusValue, UserRoleValue } from '@/types';

/**
 * Libellés français des rôles applicatifs.
 */
export const ROLE_LABELS: Record<UserRoleValue, string> = {
    'super-admin': 'Super administrateur',
    admin: 'Administrateur',
    customer: 'Client',
    professional: 'Professionnel',
    'company-manager': 'Gestionnaire d’entreprise',
};

/**
 * Libellés français des statuts de compte.
 */
export const ACCOUNT_STATUS_LABELS: Record<AccountStatusValue, string> = {
    active: 'Actif',
    suspended: 'Suspendu',
    closed: 'Clôturé',
};

/**
 * Pays proposed pour la saisie du numéro de téléphone.
 *
 * Seul le Mali est ouvert : les autres indicatifs sont listés pour indiquer
 * la direction prise, sans permettre une saisie non prise en charge.
 */
export interface PhoneCountry {
    code: string;
    label: string;
    available: boolean;
}

export const PHONE_COUNTRIES: PhoneCountry[] = [
    { code: '+223', label: 'Mali', available: true },
    { code: '+221', label: 'Sénégal', available: false },
    { code: '+226', label: 'Burkina Faso', available: false },
    { code: '+225', label: 'Côte d’Ivoire', available: false },
    { code: '+224', label: 'Guinée', available: false },
];

/**
 * Indicatif du pays par défaut.
 */
export const DEFAULT_COUNTRY_CODE = '+223';

/**
 * Retourne le libellé lisible d'un rôle.
 */
export function roleLabel(role: string): string {
    return ROLE_LABELS[role as UserRoleValue] ?? role;
}

/**
 * Retourne le libellé lisible d'un statut de compte.
 */
export function accountStatusLabel(status: string): string {
    return ACCOUNT_STATUS_LABELS[status as AccountStatusValue] ?? status;
}

/**
 * Extrait la partie locale d'un numéro stocké au format international.
 *
 * Le numéro saisi reste au format local (8 chiffres) : l'indicatif est
 * porté par le sélecteur de pays.
 */
export function localPhoneNumber(value?: string | null): string {
    if (!value) {
        return '';
    }

    const digits = value.replace(/\D+/g, '');

    return digits.startsWith('223') ? digits.slice(3) : digits;
}
