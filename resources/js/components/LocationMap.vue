<script setup lang="ts">
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

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

const props = withDefaults(
    defineProps<{
        latitude?: number | null;
        longitude?: number | null;
        zoom?: number;
        height?: string;
        interactive?: boolean;
    }>(),
    { zoom: 14, height: '320px', interactive: false },
);

const emit = defineEmits<{
    (e: 'update:latitude', value: number): void;
    (e: 'update:longitude', value: number): void;
}>();

const container = ref<HTMLDivElement | null>(null);
let map: L.Map | null = null;
let marker: L.Marker | null = null;

const hasCoords = computed(
    () => props.latitude != null && props.longitude != null && !Number.isNaN(props.latitude) && !Number.isNaN(props.longitude),
);

const center = computed<[number, number]>(() =>
    hasCoords.value ? [props.latitude as number, props.longitude as number] : [12.6392, -8.0029],
);

function placeMarker(lat: number, lng: number) {
    if (!map) {
        return;
    }

    if (marker) {
        marker.setLatLng([lat, lng]);
    } else {
        marker = L.marker([lat, lng]).addTo(map);
    }

    map.setView([lat, lng], map.getZoom());
}

function init() {
    if (!container.value || map) {
        return;
    }

    map = L.map(container.value, {
        zoomControl: true,
        scrollWheelZoom: props.interactive,
        dragging: props.interactive,
        touchZoom: props.interactive,
        doubleClickZoom: props.interactive,
    }).setView(center.value, hasCoords.value ? props.zoom : 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19,
    }).addTo(map);

    if (hasCoords.value) {
        marker = L.marker([props.latitude as number, props.longitude as number]).addTo(map);
    }

    if (props.interactive) {
        map.on('click', (event: L.LeafletMouseEvent) => {
            const { lat, lng } = event.latlng;
            placeMarker(lat, lng);
            emit('update:latitude', Number(lat.toFixed(6)));
            emit('update:longitude', Number(lng.toFixed(6)));
        });
    }
}

onMounted(() => {
    // Leaflet a besoin que le conteneur soit peint.
    setTimeout(init, 0);
});

watch(
    () => [props.latitude, props.longitude],
    () => {
        if (!map) {
            return;
        }

        if (hasCoords.value) {
            const lat = props.latitude as number;
            const lng = props.longitude as number;

            if (marker) {
                marker.setLatLng([lat, lng]);
            } else {
                marker = L.marker([lat, lng]).addTo(map);
            }

            map.setView([lat, lng], map.getZoom() < 12 ? props.zoom : map.getZoom());
        } else if (marker) {
            map.removeLayer(marker);
            marker = null;
        }
    },
);

onBeforeUnmount(() => {
    if (map) {
        map.remove();
        map = null;
        marker = null;
    }
});
</script>

<template>
    <div class="overflow-hidden rounded-xl border border-border/70 shadow-soft">
        <div ref="container" :style="{ height }" class="w-full bg-muted/30" />

        <div v-if="interactive" class="flex items-center justify-between gap-2 border-t border-border/60 bg-background px-3 py-2 text-[11px] text-muted-foreground">
            <span>Cliquez sur la carte pour placer le marqueur.</span>
            <span v-if="hasCoords" class="font-medium text-foreground">
                {{ (latitude as number).toFixed(5) }}, {{ (longitude as number).toFixed(5) }}
            </span>
        </div>
    </div>
</template>
