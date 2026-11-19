import { cva, type VariantProps } from 'class-variance-authority';

export { default as Button } from './Button.vue';

export const buttonVariants = cva(
    'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-lg text-sm font-medium ring-offset-background transition-all duration-200 ease-smooth focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/60 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 active:scale-[0.98] [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0',
    {
        variants: {
            variant: {
                default: 'bg-primary text-primary-foreground shadow-soft hover:bg-primary/90 hover:shadow-card',
                brand: 'bg-brand-600 text-white shadow-soft hover:bg-brand-700',
                destructive: 'bg-destructive text-destructive-foreground shadow-soft hover:bg-destructive/90',
                outline: 'border border-border bg-background/70 shadow-soft hover:border-input hover:bg-muted hover:text-foreground',
                secondary: 'bg-secondary text-secondary-foreground shadow-soft hover:bg-secondary/80',
                ghost: 'text-muted-foreground hover:bg-muted hover:text-foreground',
                link: 'text-primary underline-offset-4 hover:underline',
                soft: 'bg-brand-50 text-brand-700 hover:bg-brand-100 dark:bg-brand-950/60 dark:text-brand-300 dark:hover:bg-brand-900/60',
                success: 'bg-success text-success-foreground shadow-soft hover:bg-success/90',
                warning: 'bg-warning text-warning-foreground shadow-soft hover:bg-warning/90',
                info: 'bg-info text-info-foreground shadow-soft hover:bg-info/90',
            },
            size: {
                default: 'h-10 px-4 py-2',
                sm: 'h-8 rounded-md px-3 text-xs',
                lg: 'h-11 rounded-lg px-6',
                icon: 'h-10 w-10',
                'icon-sm': 'h-8 w-8 rounded-md',
                'icon-lg': 'h-11 w-11',
            },
        },
        defaultVariants: {
            variant: 'default',
            size: 'default',
        },
    },
);

export type ButtonVariants = VariantProps<typeof buttonVariants>;
