import { onMounted, ref } from 'vue';

type Appearance = 'light' | 'dark' | 'system';

function getSavedAppearance(): Appearance | null {
    try {
        return localStorage.getItem('appearance') as Appearance | null;
    } catch {
        // Le stockage peut être indisponible en navigation privée.
        return null;
    }
}

export function updateTheme(value: Appearance) {
    if (value === 'system') {
        const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        document.documentElement.classList.toggle('dark', systemTheme === 'dark');
    } else {
        document.documentElement.classList.toggle('dark', value === 'dark');
    }
}

const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
let isInitialized = false;

const handleSystemThemeChange = () => {
    updateTheme(getSavedAppearance() || 'system');
};

export function initializeTheme() {
    // Applique le thème enregistré, ou le thème système par défaut.
    updateTheme(getSavedAppearance() || 'system');

    // Le listener n'est installé qu'une seule fois, quel que soit le nombre de composants montés.
    if (!isInitialized) {
        mediaQuery.addEventListener('change', handleSystemThemeChange);
        isInitialized = true;
    }
}

export function useAppearance() {
    const appearance = ref<Appearance>(getSavedAppearance() || 'system');

    onMounted(() => {
        appearance.value = getSavedAppearance() || 'system';
    });

    function updateAppearance(value: Appearance) {
        appearance.value = value;

        try {
            localStorage.setItem('appearance', value);
        } catch {
            // Le thème reste appliqué pour la session en cours même si le stockage échoue.
        }

        updateTheme(value);
    }

    return {
        appearance,
        updateAppearance,
    };
}
