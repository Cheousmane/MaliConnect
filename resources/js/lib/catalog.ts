import type { LucideIcon } from 'lucide-vue-next';
import { Briefcase, Car, Home, Monitor, Scissors, Store } from 'lucide-vue-next';

/**
 * Icône affichée pour chaque catégorie du catalogue.
 *
 * La valeur provient de la base (`categories.icon`) : le mapping reste donc
 * volontairement tolérant et retombe sur une icône générique.
 */
const categoryIcons: Record<string, LucideIcon> = {
    home: Home,
    monitor: Monitor,
    car: Car,
    scissors: Scissors,
    briefcase: Briefcase,
};

export function categoryIcon(name: string | null | undefined): LucideIcon {
    if (!name) {
        return Store;
    }

    return categoryIcons[name] ?? Store;
}

/**
 * Convertit une couleur hexadécimale en `rgba()` avec l'opacité demandée.
 *
 * Utilisé pour teinter les vignettes de catégories à partir de la couleur
 * réellement enregistrée, sans introduire de valeurs codées en dur.
 */
export function withAlpha(hex: string | null | undefined, alpha: number): string | undefined {
    if (!hex) {
        return undefined;
    }

    const normalized = hex.trim().replace('#', '');

    const full = normalized.length === 3 ? normalized.split('').map((char) => char + char).join('') : normalized;

    if (!/^[0-9a-f]{6}$/i.test(full)) {
        return undefined;
    }

    const value = Number.parseInt(full, 16);

    const r = (value >> 16) & 255;
    const g = (value >> 8) & 255;
    const b = value & 255;

    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

/**
 * Palette de repli pour les monogrammes de profils sans logo.
 */
const monogramTones = [
    'bg-brand-50 text-brand-700 ring-brand-200 dark:bg-brand-950/60 dark:text-brand-300 dark:ring-brand-900',
    'bg-gold-50 text-gold-700 ring-gold-200 dark:bg-gold-950/50 dark:text-gold-300 dark:ring-gold-900',
    'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-950/50 dark:text-sky-300 dark:ring-sky-900',
    'bg-indigo-50 text-indigo-700 ring-indigo-200 dark:bg-indigo-950/50 dark:text-indigo-300 dark:ring-indigo-900',
];

export function monogramTone(seed: number | string): string {
    const value = typeof seed === 'number' ? seed : Array.from(seed).reduce((total, char) => total + char.charCodeAt(0), 0);

    return monogramTones[Math.abs(value) % monogramTones.length];
}
