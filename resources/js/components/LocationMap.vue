<script setup lang="ts">
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

// Vite ne résout pas automatiquement les images de marqueurs Leaflet.
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

delete (L.Icon.Default.prototype as { _getIconUrl?: unknown })._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

type MapMarker = {
    latitude: number;
    longitude: number;
    label?: string;
    href?: string;
};

type SearchResult = {
    display_name: string;
    lat: string;
    lon: string;
};

type ResolvedLocation = {
    latitude: number;
    longitude: number;
    displayName: string;
    address: string;
    city: string;
    commune: string;
    quartier: string;
};

const props = withDefaults(
    defineProps<{
        latitude?: number | null;
        longitude?: number | null;
        zoom?: number;
        height?: string;
        interactive?: boolean;
        markers?: MapMarker[];
        label?: string;
        reverseGeocode?: boolean;
    }>(),
    { zoom: 14, height: '320px', interactive: false, markers: () => [], label: '', reverseGeocode: false },
);

const emit = defineEmits<{
    (e: 'update:latitude', value: number): void;
    (e: 'update:longitude', value: number): void;
    (e: 'resolve', value: ResolvedLocation): void;
}>();

const container = ref<HTMLDivElement | null>(null);
const searchQuery = ref('');
const searchResults = ref<SearchResult[]>([]);
const searchLoading = ref(false);
const searchOpen = ref(false);
const geoLoading = ref(false);
const geoError = ref('');
const tilesError = ref(false);

let map: L.Map | null = null;
let marker: L.Marker | null = null;
let markersLayer: L.LayerGroup | null = null;
let resizeObserver: ResizeObserver | null = null;
let searchTimer: ReturnType<typeof setTimeout> | null = null;
let searchAbort: AbortController | null = null;
let reverseAbort: AbortController | null = null;
let reverseTimer: ReturnType<typeof setTimeout> | null = null;
const resolvingAddress = ref(false);
const resolvedLabel = ref('');

const hasCoords = computed(
    () => props.latitude != null && props.longitude != null && !Number.isNaN(props.latitude) && !Number.isNaN(props.longitude),
);

const center = computed<[number, number]>(() =>
    hasCoords.value ? [props.latitude as number, props.longitude as number] : [12.6392, -8.0029],
);

const osmLink = computed(() => {
    if (!hasCoords.value) return null;
    const lat = (props.latitude as number).toFixed(6);
    const lng = (props.longitude as number).toFixed(6);
    return `https://www.openstreetmap.org/?mlat=${lat}&mlon=${lng}#map=16/${lat}/${lng}`;
});

function emitCoords(lat: number, lng: number) {
    emit('update:latitude', Number(lat.toFixed(6)));
    emit('update:longitude', Number(lng.toFixed(6)));
}

function pickFirst(...values: Array<string | undefined | null>): string {
    for (const value of values) {
        if (typeof value === 'string' && value.trim() !== '') return value.trim();
    }
    return '';
}

async function fetchReverseGeocode(lat: number, lng: number) {
    if (!props.reverseGeocode) return;

    if (reverseAbort) reverseAbort.abort();
    reverseAbort = new AbortController();
    resolvingAddress.value = true;

    try {
        const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&addressdetails=1&lat=${lat}&lon=${lng}`;
        const res = await fetch(url, {
            signal: reverseAbort.signal,
            headers: { 'Accept-Language': 'fr' },
        });
        if (!res.ok) throw new Error('Géocodage inverse indisponible');

        const data = (await res.json()) as {
            display_name?: string;
            address?: Record<string, string | undefined>;
        };
        const a = data.address ?? {};
        const city = pickFirst(a.city, a.town, a.village, a.municipality, a.county, a.state, 'Bamako');
        const commune = pickFirst(a.city_district, a.municipality, a.suburb, a.borough);
        const quartier = pickFirst(a.neighbourhood, a.quarter, a.suburb, a.hamlet, a.residential);
        const road = pickFirst(a.road, a.pedestrian, a.footway, a.path);
        const houseNumber = pickFirst(a.house_number);
        const address = [houseNumber, road].filter(Boolean).join(' ').trim();

        const resolved: ResolvedLocation = {
            latitude: Number(lat.toFixed(6)),
            longitude: Number(lng.toFixed(6)),
            displayName: data.display_name ?? '',
            address,
            city,
            commune,
            quartier,
        };

        resolvedLabel.value = [resolved.address, resolved.quartier, resolved.commune, resolved.city].filter(Boolean).join(', ');
        emit('resolve', resolved);
    } catch (e) {
        if ((e as Error).name !== 'AbortError') {
            resolvedLabel.value = '';
        }
    } finally {
        resolvingAddress.value = false;
    }
}

function scheduleReverseGeocode(lat: number, lng: number) {
    if (!props.reverseGeocode) return;
    if (reverseTimer) clearTimeout(reverseTimer);
    reverseTimer = setTimeout(() => {
        void fetchReverseGeocode(lat, lng);
    }, 350);
}

function placeMarker(lat: number, lng: number, pan = true) {
    if (!map) return;

    if (marker) {
        marker.setLatLng([lat, lng]);
    } else {
        marker = L.marker([lat, lng], { draggable: props.interactive }).addTo(map);
        if (props.interactive) {
            marker.on('dragend', () => {
                const pos = marker?.getLatLng();
                if (pos) {
                    emitCoords(pos.lat, pos.lng);
                    scheduleReverseGeocode(pos.lat, pos.lng);
                }
            });
        }
    }

    if (pan) {
        map.setView([lat, lng], Math.max(map.getZoom(), props.zoom));
    }
}

function renderMarkers() {
    if (!map) return;

    if (!markersLayer) {
        markersLayer = L.layerGroup().addTo(map);
    }
    markersLayer.clearLayers();

    if (props.markers.length === 0) return;

    const bounds: L.LatLngExpression[] = [];
    for (const item of props.markers) {
        if (item.latitude == null || item.longitude == null) continue;
        const m = L.marker([item.latitude, item.longitude]);
        if (item.label || item.href) {
            const label = item.label ? `<div style="font-weight:600;margin-bottom:2px">${escapeHtml(item.label)}</div>` : '';
            const link = item.href ? `<a href="${escapeAttr(item.href)}" style="color:#0a6d46;text-decoration:underline">Voir le profil</a>` : '';
            m.bindPopup(`${label}${link}`);
        }
        m.addTo(markersLayer);
        bounds.push([item.latitude, item.longitude]);
    }

    if (!hasCoords.value && bounds.length > 0) {
        if (bounds.length === 1) {
            map.setView(bounds[0], props.zoom);
        } else {
            map.fitBounds(L.latLngBounds(bounds), { padding: [24, 24], maxZoom: 15 });
        }
    }
}

function escapeHtml(value: string): string {
    return value
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');
}

function escapeAttr(value: string): string {
    return escapeHtml(value);
}

async function searchPlaces() {
    const q = searchQuery.value.trim();
    if (q.length < 3) {
        searchResults.value = [];
        searchOpen.value = false;
        return;
    }

    if (searchAbort) searchAbort.abort();
    searchAbort = new AbortController();
    searchLoading.value = true;
    geoError.value = '';

    try {
        const url = `https://nominatim.openstreetmap.org/search?format=json&limit=5&countrycodes=ml&q=${encodeURIComponent(q)}`;
        const res = await fetch(url, {
            signal: searchAbort.signal,
            headers: { 'Accept-Language': 'fr' },
        });
        if (!res.ok) throw new Error('Recherche indisponible');
        const data = (await res.json()) as SearchResult[];
        searchResults.value = data;
        searchOpen.value = data.length > 0;
    } catch (e) {
        if ((e as Error).name !== 'AbortError') {
            searchResults.value = [];
            searchOpen.value = false;
        }
    } finally {
        searchLoading.value = false;
    }
}

function onSearchInput() {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(searchPlaces, 400);
}

function selectResult(result: SearchResult) {
    const lat = Number(result.lat);
    const lng = Number(result.lon);
    placeMarker(lat, lng);
    emitCoords(lat, lng);
    scheduleReverseGeocode(lat, lng);
    searchQuery.value = result.display_name.split(',').slice(0, 2).join(',').trim();
    searchOpen.value = false;
    searchResults.value = [];
}

function locateMe() {
    if (!navigator.geolocation) {
        geoError.value = 'La géolocalisation n’est pas disponible sur cet appareil.';
        return;
    }

    geoLoading.value = true;
    geoError.value = '';

    navigator.geolocation.getCurrentPosition(
        (pos) => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            placeMarker(lat, lng);
            emitCoords(lat, lng);
            scheduleReverseGeocode(lat, lng);
            geoLoading.value = false;
        },
        () => {
            geoError.value = 'Impossible de récupérer votre position. Vérifiez les autorisations.';
            geoLoading.value = false;
        },
        { enableHighAccuracy: true, timeout: 10000 },
    );
}

function init() {
    if (!container.value || map) return;

    map = L.map(container.value, {
        zoomControl: true,
        scrollWheelZoom: props.interactive,
        dragging: props.interactive,
        touchZoom: props.interactive || true,
        doubleClickZoom: props.interactive,
    }).setView(center.value, hasCoords.value ? props.zoom : 12);

    const tiles = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19,
    }).addTo(map);

    tiles.on('tileerror', () => {
        tilesError.value = true;
    });
    tiles.on('load', () => {
        tilesError.value = false;
    });

    if (hasCoords.value) {
        placeMarker(props.latitude as number, props.longitude as number, false);
    }

    renderMarkers();

    if (props.interactive) {
        map.on('click', (event: L.LeafletMouseEvent) => {
            const { lat, lng } = event.latlng;
            placeMarker(lat, lng);
            emitCoords(lat, lng);
            geoError.value = '';
            scheduleReverseGeocode(lat, lng);
        });
    }

    // Corrige le rendu dans les conteneurs dont la taille change après montage.
    resizeObserver = new ResizeObserver(() => {
        map?.invalidateSize();
    });
    resizeObserver.observe(container.value);
    setTimeout(() => map?.invalidateSize(), 100);
}

onMounted(async () => {
    await nextTick();
    init();
});

watch(
    () => [props.latitude, props.longitude],
    () => {
        if (!map) return;

        if (hasCoords.value) {
            placeMarker(props.latitude as number, props.longitude as number);
        } else if (marker) {
            map.removeLayer(marker);
            marker = null;
            resolvedLabel.value = '';
        }
    },
);

watch(
    () => props.markers,
    () => {
        renderMarkers();
    },
    { deep: true },
);

onBeforeUnmount(() => {
    if (searchTimer) clearTimeout(searchTimer);
    if (searchAbort) searchAbort.abort();
    if (reverseTimer) clearTimeout(reverseTimer);
    if (reverseAbort) reverseAbort.abort();
    if (resizeObserver) {
        resizeObserver.disconnect();
        resizeObserver = null;
    }
    if (map) {
        map.remove();
        map = null;
        marker = null;
        markersLayer = null;
    }
});
</script>

<template>
    <div class="overflow-hidden rounded-xl border border-border/70 shadow-soft">
        <div v-if="interactive" class="border-b border-border/60 bg-background p-2.5">
            <div class="flex flex-col gap-2 sm:flex-row">
                <div class="relative min-w-0 flex-1">
                    <input
                        v-model="searchQuery"
                        type="search"
                        class="h-9 w-full rounded-lg border border-input bg-background px-3 text-sm outline-none ring-brand-500/30 transition focus:ring-2"
                        placeholder="Rechercher une adresse au Mali…"
                        aria-label="Rechercher une adresse"
                        @input="onSearchInput"
                        @focus="searchResults.length > 0 && (searchOpen = true)"
                        @blur="searchOpen = false"
                        @keydown.escape="searchOpen = false"
                    />
                    <div
                        v-if="searchOpen"
                        class="absolute left-0 right-0 top-full z-[1000] mt-1 overflow-hidden rounded-lg border border-border bg-popover shadow-lg"
                    >
                        <button
                            v-for="result in searchResults"
                            :key="`${result.lat}-${result.lon}`"
                            type="button"
                            class="block w-full px-3 py-2 text-left text-xs text-foreground transition hover:bg-muted/60"
                            @mousedown.prevent
                            @click="selectResult(result)"
                        >
                            {{ result.display_name }}
                        </button>
                    </div>
                </div>
                <button
                    type="button"
                    class="inline-flex h-9 shrink-0 items-center justify-center rounded-lg border border-border bg-background px-3 text-xs font-medium text-foreground transition hover:bg-muted/60 disabled:opacity-60"
                    :disabled="geoLoading"
                    @click="locateMe"
                >
                    {{ geoLoading ? 'Localisation…' : 'Ma position' }}
                </button>
            </div>
            <p v-if="geoError" class="mt-2 text-[11px] text-danger">{{ geoError }}</p>
        </div>

        <div class="relative">
            <div
                ref="container"
                :style="{ height }"
                class="w-full bg-muted/30"
                role="region"
                aria-label="Carte de localisation"
            />
            <div
                v-if="tilesError"
                class="pointer-events-none absolute inset-x-0 bottom-0 bg-background/90 px-3 py-2 text-center text-[11px] text-muted-foreground"
            >
                Certaines tuiles de la carte n’ont pas pu être chargées.
            </div>
        </div>

        <div class="space-y-1.5 border-t border-border/60 bg-background px-3 py-2 text-[11px] text-muted-foreground">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <span v-if="interactive">Cliquez sur la carte ou déplacez le marqueur pour définir la position.</span>
                <span v-else-if="markers.length > 1">{{ markers.length }} professionnels localisés</span>
                <span v-else-if="hasCoords || markers.length === 1">Localisation</span>
                <span v-else>Position non renseignée</span>

                <div class="flex items-center gap-3">
                    <span v-if="hasCoords" class="font-medium text-foreground">
                        {{ (latitude as number).toFixed(5) }}, {{ (longitude as number).toFixed(5) }}
                    </span>
                    <a
                        v-if="osmLink"
                        :href="osmLink"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="font-medium text-brand-700 underline-offset-4 hover:underline dark:text-brand-300"
                    >
                        Ouvrir la carte
                    </a>
                </div>
            </div>
            <p v-if="reverseGeocode && resolvingAddress" class="text-muted-foreground">Résolution de l’adresse…</p>
            <p v-else-if="reverseGeocode && resolvedLabel" class="truncate text-foreground/80" :title="resolvedLabel">
                {{ resolvedLabel }}
            </p>
        </div>
    </div>
</template>
