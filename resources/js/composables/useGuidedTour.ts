import { getTourForRole, type TourDefinition, type TourStep } from '@/lib/guidedTours';
import { router, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref, watch } from 'vue';

type TourStatus = 'not_started' | 'in_progress' | 'completed' | 'skipped';

interface TourState {
    role: string;
    status: TourStatus;
    current_step: number;
    started_at: string | null;
    completed_at: string | null;
}

const open = ref(false);
const showWelcome = ref(false);
const showResume = ref(false);
const activeIndex = ref(0);
const resolving = ref(false);
let initialized = false;

function csrfToken(): string {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);
    if (match) return decodeURIComponent(match[1]);
    return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
}

async function api(path: string, method: 'GET' | 'POST' | 'PATCH', payload?: Record<string, unknown>) {
    const response = await fetch(path, {
        method,
        headers: {
            'Content-Type': 'application/json',
            'X-XSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
        },
        credentials: 'same-origin',
        body: payload ? JSON.stringify(payload) : undefined,
    });

    if (!response.ok) throw new Error('Tour request failed');
    return response.json();
}

function canAccessStep(step: TourStep, permissions: string[]): boolean {
    if (step.permissionsAll?.length && !step.permissionsAll.every((p) => permissions.includes(p))) return false;
    if (step.permissionsAny?.length && !step.permissionsAny.some((p) => permissions.includes(p))) return false;
    return true;
}

function resolveSelector(step: TourStep | null): string | null {
    if (!step) return null;
    const selectors = [step.selector, ...(step.selectors ?? [])].filter(Boolean) as string[];
    for (const selector of selectors) {
        if (document.querySelector(selector)) return selector;
    }
    return selectors[0] ?? null;
}

export function useGuidedTour() {
    const page = usePage();

    const serverTour = computed(() => ((page.props as any).guidedTour as TourState | null) ?? null);
    const role = computed(() => serverTour.value?.role ?? null);
    const permissions = computed<string[]>(() => ((page.props as any).auth?.permissions as string[] | undefined) ?? []);
    const tour = computed<TourDefinition | null>(() => getTourForRole(role.value));
    const steps = computed<TourStep[]>(() => (tour.value?.steps ?? []).filter((step) => canAccessStep(step, permissions.value)));
    const currentStep = computed<TourStep | null>(() => steps.value[activeIndex.value] ?? null);
    const progressLabel = computed(() => {
        if (!steps.value.length) return '';
        return `Étape ${activeIndex.value + 1} sur ${steps.value.length}`;
    });

    const syncLocal = () => {
        const state = serverTour.value;
        if (!state) return;
        const max = Math.max(steps.value.length - 1, 0);
        activeIndex.value = Math.min(Math.max(state.current_step ?? 0, 0), max);
    };

    const persist = async (status: TourStatus, current_step = activeIndex.value) => {
        try {
            await api('/tour', 'PATCH', { status, current_step });
        } catch {
            // Retenté à l'action suivante.
        }
    };

    const waitForSelector = async (step: TourStep | null, attempts = 18): Promise<Element | null> => {
        const selectors = [step?.selector, ...(step?.selectors ?? [])].filter(Boolean) as string[];
        if (selectors.length === 0) return null;
        for (let i = 0; i < attempts; i++) {
            for (const selector of selectors) {
                const el = document.querySelector(selector);
                if (el) return el;
            }
            await new Promise((r) => setTimeout(r, 150));
        }
        return null;
    };

    const ensureStepVisible = async (): Promise<boolean> => {
        const step = currentStep.value;
        if (!step) return false;

        if (step.route) {
            const current = page.url.split('?')[0];
            if (current !== step.route) {
                resolving.value = true;
                await new Promise<void>((resolve) => {
                    router.visit(step.route as string, {
                        preserveScroll: true,
                        onFinish: () => resolve(),
                    });
                });
                await nextTick();
            }
        }

        const el = await waitForSelector(step);
        if (el) {
            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            el.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center', inline: 'center' });
            await new Promise((r) => setTimeout(r, reduceMotion ? 0 : 120));
            resolving.value = false;
            return true;
        }

        resolving.value = false;
        if (step.skipIfMissing) return false;
        return true;
    };

    const moveToReachable = async (direction: 1 | -1): Promise<boolean> => {
        let guard = 0;
        while (guard < steps.value.length) {
            guard += 1;
            const ok = await ensureStepVisible();
            if (ok) return true;

            const nextIndex = activeIndex.value + direction;
            if (nextIndex < 0 || nextIndex >= steps.value.length) return false;
            activeIndex.value = nextIndex;
        }
        return false;
    };

    const start = async (fromBeginning = false) => {
        if (!tour.value || steps.value.length === 0) return;
        syncLocal();
        if (fromBeginning) activeIndex.value = 0;
        showResume.value = false;
        showWelcome.value = fromBeginning || serverTour.value?.status === 'not_started';
        open.value = true;
        if (!showWelcome.value) {
            await persist('in_progress', activeIndex.value);
            const ok = await moveToReachable(1);
            if (!ok) await finish();
        }
    };

    const begin = async () => {
        showWelcome.value = false;
        activeIndex.value = 0;
        await persist('in_progress', 0);
        const ok = await moveToReachable(1);
        if (!ok) await finish();
    };

    const closeWelcomeOnly = async () => {
        showWelcome.value = false;
        open.value = false;
        await persist('skipped', activeIndex.value);
    };

    const resume = async () => {
        showResume.value = false;
        open.value = true;
        await persist('in_progress', activeIndex.value);
        const ok = await moveToReachable(1);
        if (!ok) await finish();
    };

    const dismissResume = async () => {
        showResume.value = false;
        open.value = false;
    };

    const next = async () => {
        if (activeIndex.value < steps.value.length - 1) {
            activeIndex.value += 1;
            await persist('in_progress', activeIndex.value);
            const ok = await moveToReachable(1);
            if (!ok) await finish();
        } else {
            await finish();
        }
    };

    const prev = async () => {
        if (activeIndex.value > 0) {
            activeIndex.value -= 1;
            await persist('in_progress', activeIndex.value);
            const ok = await moveToReachable(-1);
            if (!ok) activeIndex.value = Math.min(activeIndex.value + 1, steps.value.length - 1);
        }
    };

    const skip = async () => {
        open.value = false;
        showWelcome.value = false;
        showResume.value = false;
        await persist('skipped', activeIndex.value);
    };

    const finish = async () => {
        open.value = false;
        showWelcome.value = false;
        showResume.value = false;
        await persist('completed', Math.max(steps.value.length - 1, 0));
    };

    const restart = async () => {
        if (!tour.value || steps.value.length === 0) return;
        try {
            await api('/tour/restart', 'POST');
        } catch {
            // Continuer localement.
        }
        activeIndex.value = 0;
        showWelcome.value = false;
        showResume.value = false;
        open.value = true;
        await persist('in_progress', 0);
        const ok = await moveToReachable(1);
        if (!ok) await finish();
    };

    onMounted(async () => {
        if (initialized) return;
        initialized = true;
        syncLocal();
        if (!tour.value || steps.value.length === 0) return;
        if (serverTour.value?.status === 'not_started') {
            await start(false);
        } else if (serverTour.value?.status === 'in_progress') {
            showResume.value = true;
        }
    });

    watch(
        () => page.url,
        async () => {
            if (open.value && !showWelcome.value && !showResume.value) {
                const ok = await ensureStepVisible();
                if (!ok && currentStep.value?.skipIfMissing) {
                    await moveToReachable(1);
                }
            }
        },
    );

    return {
        open,
        showWelcome,
        showResume,
        tour,
        steps,
        activeIndex,
        currentStep,
        progressLabel,
        resolving,
        start,
        begin,
        closeWelcomeOnly,
        resume,
        dismissResume,
        next,
        prev,
        skip,
        finish,
        restart,
    };
}
