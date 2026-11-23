/**
 * Structures partagées par les sections éditoriales de la page d'accueil.
 *
 * Toutes les valeurs proviennent du catalogue réel : aucune donnée de
 * démonstration n'est introduite côté frontend.
 */

export interface LandingCategory {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    icon: string | null;
    color: string | null;
    subcategories_count: number;
}

export interface LandingService {
    name: string;
    formatted_price: string;
    price_type: string | null;
}

export interface LandingProfessional {
    id: number;
    business_name: string;
    slug: string;
    description: string | null;
    city: string;
    commune: string | null;
    quartier: string | null;
    latitude: number | null;
    longitude: number | null;
    is_available: boolean;
    rating_average: number;
    rating_count: number;
    category: { id: number; name: string; slug: string } | null;
    subcategory: { id: number; name: string; slug: string } | null;
    services_count: number;
    media_count: number;
    years_experience: number | null;
    logo_url: string | null;
    services: LandingService[];
}

export interface LandingPlanFeatures {
    advanced_stats: boolean;
    gallery: boolean;
    collaborators: boolean;
    priority_visibility: boolean;
    priority_support: boolean;
    advanced_quotes: boolean;
    bookings: boolean;
    simple_pricing_sheets: boolean;
    full_pricing_sheets: boolean;
    profile_badge: boolean;
    highlight_options: boolean;
}

export interface LandingPlan {
    id: number;
    code: string;
    name: string;
    description: string | null;
    price_monthly: number;
    price_yearly: number;
    amount_monthly: string;
    amount_yearly: string;
    currency: string;
    max_services: number;
    max_media: number;
    max_profiles: number;
    max_pricing_sheets: number;
    is_free: boolean;
    trial_days: number;
    features: LandingPlanFeatures;
}

export interface LandingStats {
    categories: number;
    subcategories: number;
    professionals: number;
    services: number;
}

export interface LandingPriceExample {
    service_name: string;
    professional_name: string;
    professional_slug: string;
    formatted_price: string;
}

export type BillingCycle = 'monthly' | 'yearly';
