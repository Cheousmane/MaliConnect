/**
 * Formatage localisé des données affichées dans l'administration.
 */

/**
 * Formate un montant en francs CFA.
 */
export function formatMoney(value: number | string | null | undefined, currency = 'XOF'): string {
    const amount = Number(value ?? 0);

    if (!Number.isFinite(amount)) {
        return `0 ${currency === 'XOF' ? 'FCFA' : currency}`;
    }

    const formatted = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(amount);

    return `${formatted} ${currency === 'XOF' ? 'FCFA' : currency}`;
}

/**
 * Formate un nombre entier.
 */
export function formatNumber(value: number | string | null | undefined): string {
    return new Intl.NumberFormat('fr-FR').format(Number(value ?? 0));
}

/**
 * Formate une date courte.
 */
export function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium' }).format(new Date(value));
}

/**
 * Formate une date et une heure.
 */
export function formatDateTime(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
}
