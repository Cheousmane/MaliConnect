import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: ['class'],
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{vue,js,ts,jsx,tsx}',
    ],
    theme: {
        container: {
            center: true,
            padding: {
                DEFAULT: '1rem',
                sm: '1.5rem',
                lg: '2rem',
            },
            screens: {
                '2xl': '1280px',
            },
        },
        extend: {
            fontFamily: {
                sans: ['Instrument Sans', ...defaultTheme.fontFamily.sans],
                display: ['Sora', 'Instrument Sans', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                background: 'hsl(var(--background))',
                foreground: 'hsl(var(--foreground))',
                card: {
                    DEFAULT: 'hsl(var(--card))',
                    foreground: 'hsl(var(--card-foreground))',
                },
                popover: {
                    DEFAULT: 'hsl(var(--popover))',
                    foreground: 'hsl(var(--popover-foreground))',
                },
                primary: {
                    DEFAULT: 'hsl(var(--primary))',
                    foreground: 'hsl(var(--primary-foreground))',
                },
                secondary: {
                    DEFAULT: 'hsl(var(--secondary))',
                    foreground: 'hsl(var(--secondary-foreground))',
                },
                muted: {
                    DEFAULT: 'hsl(var(--muted))',
                    foreground: 'hsl(var(--muted-foreground))',
                },
                accent: {
                    DEFAULT: 'hsl(var(--accent))',
                    foreground: 'hsl(var(--accent-foreground))',
                },
                destructive: {
                    DEFAULT: 'hsl(var(--destructive))',
                    foreground: 'hsl(var(--destructive-foreground))',
                },
                success: {
                    DEFAULT: 'hsl(var(--success))',
                    foreground: 'hsl(var(--success-foreground))',
                },
                warning: {
                    DEFAULT: 'hsl(var(--warning))',
                    foreground: 'hsl(var(--warning-foreground))',
                },
                info: {
                    DEFAULT: 'hsl(var(--info))',
                    foreground: 'hsl(var(--info-foreground))',
                },
                border: 'hsl(var(--border))',
                input: 'hsl(var(--input))',
                ring: 'hsl(var(--ring))',
                // Échelle de marque MaliConnect — vert sahel.
                brand: {
                    50: '#edfcf4',
                    100: '#d3f8e2',
                    200: '#aaefc8',
                    300: '#72e0a7',
                    400: '#3ac983',
                    500: '#16ad69',
                    600: '#0a8a54',
                    700: '#0a6d46',
                    800: '#0c573a',
                    900: '#0b4731',
                    950: '#03271b',
                },
                // Accent or, utilisé avec parcimonie.
                gold: {
                    50: '#fffbeb',
                    100: '#fef3c7',
                    200: '#fde68a',
                    300: '#fcd34d',
                    400: '#fbbf24',
                    500: '#f59e0b',
                    600: '#d97706',
                    700: '#b45309',
                    800: '#92400e',
                    900: '#78350f',
                    950: '#451a03',
                },
                chart: {
                    1: 'hsl(var(--chart-1))',
                    2: 'hsl(var(--chart-2))',
                    3: 'hsl(var(--chart-3))',
                    4: 'hsl(var(--chart-4))',
                    5: 'hsl(var(--chart-5))',
                },
                sidebar: {
                    DEFAULT: 'hsl(var(--sidebar-background))',
                    foreground: 'hsl(var(--sidebar-foreground))',
                    primary: 'hsl(var(--sidebar-primary))',
                    'primary-foreground': 'hsl(var(--sidebar-primary-foreground))',
                    accent: 'hsl(var(--sidebar-accent))',
                    'accent-foreground': 'hsl(var(--sidebar-accent-foreground))',
                    border: 'hsl(var(--sidebar-border))',
                    ring: 'hsl(var(--sidebar-ring))',
                },
            },
            borderRadius: {
                sm: 'calc(var(--radius) - 6px)',
                md: 'calc(var(--radius) - 4px)',
                lg: 'var(--radius)',
                xl: 'calc(var(--radius) + 6px)',
                '2xl': 'calc(var(--radius) + 12px)',
            },
            boxShadow: {
                soft: '0 1px 2px 0 rgb(16 24 40 / 0.04), 0 8px 24px -12px rgb(16 24 40 / 0.10)',
                card: '0 1px 3px 0 rgb(16 24 40 / 0.05), 0 18px 40px -24px rgb(16 24 40 / 0.18)',
                elevated: '0 24px 70px -28px rgb(16 24 40 / 0.30)',
                brand: '0 12px 32px -12px hsl(var(--primary) / 0.55)',
                'inner-soft': 'inset 0 1px 0 0 rgb(255 255 255 / 0.12)',
            },
            keyframes: {
                'fade-in': {
                    from: { opacity: '0' },
                    to: { opacity: '1' },
                },
                'fade-up': {
                    from: { opacity: '0', transform: 'translateY(8px)' },
                    to: { opacity: '1', transform: 'translateY(0)' },
                },
                'scale-in': {
                    from: { opacity: '0', transform: 'scale(0.97)' },
                    to: { opacity: '1', transform: 'scale(1)' },
                },
                float: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-8px)' },
                },
                'pulse-ring': {
                    '0%': { transform: 'scale(0.95)', opacity: '0.7' },
                    '70%, 100%': { transform: 'scale(1.35)', opacity: '0' },
                },
            },
            animation: {
                'fade-in': 'fade-in 0.5s ease-out both',
                'fade-up': 'fade-up 0.9s cubic-bezier(0.16, 1, 0.3, 1) both',
                'scale-in': 'scale-in 0.35s cubic-bezier(0.16, 1, 0.3, 1) both',
                float: 'float 8s ease-in-out infinite',
                'pulse-ring': 'pulse-ring 3s cubic-bezier(0.24, 0, 0.38, 1) infinite',
            },
            transitionTimingFunction: {
                smooth: 'cubic-bezier(0.16, 1, 0.3, 1)',
            },
        },
    },
    plugins: [require('tailwindcss-animate')],
};
