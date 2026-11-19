<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#0a6d46">
        <meta
            name="description"
            content="MaliConnect met en relation particuliers et professionnels de confiance partout au Mali."
        >

        <title inertia>{{ config('app.name', 'MaliConnect') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link
            href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|sora:400,500,600,700"
            rel="stylesheet"
        >

        {{-- Applique le thème avant le premier rendu pour éviter un flash clair / sombre. --}}
        <script>
            (function () {
                try {
                    const appearance = localStorage.getItem('appearance');
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                    const isDark = appearance === 'dark' || ((!appearance || appearance === 'system') && prefersDark);
                    document.documentElement.classList.toggle('dark', isDark);
                } catch (error) {
                    // Stockage indisponible : le thème système reste appliqué par défaut.
                }
            })();
        </script>

        @routes
        @vite(['resources/js/app.ts'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
