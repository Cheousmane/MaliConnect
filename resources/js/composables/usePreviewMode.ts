import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Détecte si la page est affichée dans l'aperçu embarqué (iframe)
 * ou ouverte avec le paramètre `preview`.
 */
export function usePreviewMode() {
    const page = usePage();

    return computed(() => {
        if (typeof window === 'undefined') {
            return false;
        }

        try {
            // Toute page chargée dans l'iframe est considérée comme un aperçu.
            if (window.self !== window.top) {
                return true;
            }
        } catch {
            return true;
        }

        try {
            return new URL(page.url, window.location.origin).searchParams.has('preview');
        } catch {
            return false;
        }
    });
}
