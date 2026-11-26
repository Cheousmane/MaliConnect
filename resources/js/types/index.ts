import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
    roles: UserRoleValue[];
    permissions: string[];
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export type StatusTone = 'neutral' | 'brand' | 'success' | 'warning' | 'danger' | 'info';

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
    badge?: string;
    disabled?: boolean;
    external?: boolean;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NotificationItem {
    id: string | number;
    title: string;
    description?: string;
    time: string;
    read?: boolean;
    tone?: StatusTone;
    icon?: string;
    href?: string;
    url?: string;
}

export interface TableColumn {
    key: string;
    label: string;
    class?: string;
    align?: 'left' | 'center' | 'right';
    hideOnMobile?: boolean;
}

export interface StatTrend {
    value: string;
    direction: 'up' | 'down' | 'neutral';
    label?: string;
}

export interface SharedData {
    [key: string]: unknown;
    name: string;
    auth: Auth;
    flash: {
        success?: string | null;
        error?: string | null;
    };
    notificationsPreview: NotificationItem[];
    unreadNotificationsCount: number;
    unreadMessageNotificationsCount: number;
    ziggy: {
        location: string;
        url: string;
        port: null | number;
        defaults: Record<string, unknown>;
        routes: Record<string, string>;
    };
}

export type AccountStatusValue = 'active' | 'suspended' | 'closed';

export type UserRoleValue = 'super-admin' | 'admin' | 'customer' | 'professional' | 'company-manager';

export interface User {
    id: number;
    name: string;
    email: string;
    phone?: string | null;
    avatar?: string | null;
    locale?: string;
    email_verified_at: string | null;
    last_login_at?: string | null;
    status: AccountStatusValue;
    roles?: UserRoleValue[];
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;
