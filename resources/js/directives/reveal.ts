import type { Directive } from 'vue';

/**
 * Révèle un élément lorsqu'il entre dans le viewport.
 *
 * La classe `reveal` est ajoutée côté client (avant peinture) : sans
 * JavaScript, le contenu reste donc parfaitement lisible.
 *
 * Usage : `v-reveal` ou `v-reveal="120"` (délai en millisecondes).
 */
type RevealElement = HTMLElement & { __revealObserver?: IntersectionObserver };

export const vReveal: Directive<RevealElement, number | undefined> = {
    beforeMount(el, binding) {
        el.classList.add('reveal');

        if (typeof binding.value === 'number') {
            el.style.setProperty('--reveal-delay', `${binding.value}ms`);
        }
    },

    mounted(el) {
        if (typeof IntersectionObserver === 'undefined') {
            el.classList.add('is-revealed');

            return;
        }

        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) {
                        return;
                    }

                    el.classList.add('is-revealed');
                    observer.disconnect();
                });
            },
            { threshold: 0.12, rootMargin: '0px 0px -8% 0px' },
        );

        observer.observe(el);
        el.__revealObserver = observer;
    },

    unmounted(el) {
        el.__revealObserver?.disconnect();
        delete el.__revealObserver;
    },
};

declare module 'vue' {
    export interface GlobalDirectives {
        vReveal: typeof vReveal;
    }
}
