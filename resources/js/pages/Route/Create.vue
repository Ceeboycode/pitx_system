<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import InputError from '@/components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

import {
    Bus,
    Clock3,
    GitBranch,
    GripVertical,
    MapPinned,
    Milestone,
    Route as RouteIcon,
    Search,
    Sparkles,
    Trash2,
    Wand2,
} from 'lucide-vue-next';

import { store } from '@/actions/App/Http/Controllers/RouteController';

import mapboxgl from 'mapbox-gl';
import 'mapbox-gl/dist/mapbox-gl.css';

/* ─────────────────────────────────────────────────────────────────────────────
   Types
───────────────────────────────────────────────────────────────────────────── */

type Gate = { id: number; gate_name: string };

type SearchSuggestion = {
    id: string;
    name: string;
    full_address: string;
    latitude: number;
    longitude: number;
};

type StopItem = {
    stop_name: string;
    stop_type: 'origin' | 'stop' | 'destination' | 'landmark';
    address: string | null;
    latitude: number;
    longitude: number;
    mapbox_feature_id: string | null;
    stop_order: number;
};

type AlternativeRoute = {
    index: number;
    geometry: any;
    distance: number;
    duration: number;
    coordinates: [number, number][];
};

type Waypoint = { lng: number; lat: number };

type RouteSnapshot = {
    geometry: any;
    distance: number;
    duration: number;
    coordinates: [number, number][];
};

/* ─────────────────────────────────────────────────────────────────────────────
   Props
───────────────────────────────────────────────────────────────────────────── */

const props = defineProps<{
    gates: Gate[];
    mapConfig: {
        mapboxToken: string;
        pitx: { name: string; lat: number; lng: number };
    };
}>();

mapboxgl.accessToken = props.mapConfig.mapboxToken;

/* ─────────────────────────────────────────────────────────────────────────────
   Refs
───────────────────────────────────────────────────────────────────────────── */

const mapEl = ref<HTMLElement | null>(null);
const map = ref<mapboxgl.Map | null>(null);

const destinationQuery = ref('');
const destinationSuggestions = ref<SearchSuggestion[]>([]);
const destinationMarker = ref<mapboxgl.Marker | null>(null);
const stopMarkers = ref<mapboxgl.Marker[]>([]);

const loadingDestination = ref(false);
const lineClickMessage = ref('');
const routeNameTouched = ref(false);
const ignoreNextMapClick = ref(false);

const stopQuery = ref('');
const stopSuggestions = ref<SearchSuggestion[]>([]);
const loadingStopSearch = ref(false);
const showStopSearch = ref(false);

const autoGenerateInterval = ref(5);
const loadingAutoGenerate = ref(false);

const loadingLandmarks = ref(false);
const landmarkSuggestions = ref<SearchSuggestion[]>([]);
const showLandmarks = ref(false);

const draggedStopIndex = ref<number | null>(null);
const dragOverIndex = ref<number | null>(null);

const routeCoordinates = ref<[number, number][]>([]);

// Alternative routes
const alternativeRoutes = ref<AlternativeRoute[]>([]);
const selectedRouteIndex = ref(0);
const showAlternatives = ref(false);
const originalPrimaryRoute = ref<RouteSnapshot | null>(null);

// Detour waypoints
const waypoints = ref<Waypoint[]>([]);
const waypointMarkers = ref<mapboxgl.Marker[]>([]);

/* ─────────────────────────────────────────────────────────────────────────────
   Origin
───────────────────────────────────────────────────────────────────────────── */

const origin = {
    name: props.mapConfig.pitx.name,
    lat: Number(props.mapConfig.pitx.lat),
    lng: Number(props.mapConfig.pitx.lng),
};

const form = useForm({
    route_name: '',
    gate_id: '',
    origin_name: origin.name,
    origin_lat: origin.lat,
    origin_lng: origin.lng,
    destination_name: '',
    destination_lat: null as number | null,
    destination_lng: null as number | null,
    distance_meters: null as number | null,
    duration_seconds: null as number | null,
    route_geometry: null as string | null,
    stops: [] as StopItem[],
});

let originMarker: mapboxgl.Marker | null = null;

/* ─────────────────────────────────────────────────────────────────────────────
   Computed
───────────────────────────────────────────────────────────────────────────── */

const hasDestination = computed(
    () => form.destination_lat !== null && form.destination_lng !== null,
);

const defaultRouteName = computed(() => {
    if (!form.destination_name) return form.origin_name;
    return `${form.origin_name} → ${form.destination_name}`;
});

const totalBusStops = computed(() => form.stops.length);

const totalVisibleStops = computed(() => {
    if (!hasDestination.value) return 1;
    return form.stops.length + 2;
});

const routeReady = computed(() => {
    return hasDestination.value && !!form.route_geometry;
});

const routeHealthText = computed(() => {
    if (!hasDestination.value) return 'Choose a destination to start building the route.';
    if (!routeCoordinates.value.length) return 'Waiting for route path...';
    return 'Route is ready. You can add stops or detour points.';
});

/* ─────────────────────────────────────────────────────────────────────────────
   Watchers
───────────────────────────────────────────────────────────────────────────── */

watch(
    () => defaultRouteName.value,
    (value) => {
        if (!routeNameTouched.value) {
            form.route_name = value;
        }
    },
    { immediate: true },
);

watch(destinationQuery, async (value) => {
    if (!value.trim()) {
        destinationSuggestions.value = [];
        return;
    }

    loadingDestination.value = true;

    try {
        destinationSuggestions.value = await searchPlaces(value);
    } finally {
        loadingDestination.value = false;
    }
});

watch(stopQuery, async (value) => {
    if (!value.trim()) {
        stopSuggestions.value = [];
        return;
    }

    if (routeCoordinates.value.length < 2) {
        stopSuggestions.value = [];
        return;
    }

    loadingStopSearch.value = true;

    try {
        stopSuggestions.value = await searchPlacesAlongRoute(value);
    } finally {
        loadingStopSearch.value = false;
    }
});

/* ─────────────────────────────────────────────────────────────────────────────
   Helpers
───────────────────────────────────────────────────────────────────────────── */

function onRouteNameInput(value: string | number) {
    routeNameTouched.value = true;
    form.route_name = String(value);
}

function resetRouteNameToDefault() {
    routeNameTouched.value = false;
    form.route_name = defaultRouteName.value;
}

function snapToRoute(lng: number, lat: number): [number, number] {
    if (routeCoordinates.value.length < 2) return [lng, lat];

    let minDist = Infinity;
    let snapped: [number, number] = [lng, lat];

    for (let i = 0; i < routeCoordinates.value.length - 1; i++) {
        const pt = nearestPointOnSegment(
            routeCoordinates.value[i],
            routeCoordinates.value[i + 1],
            [lng, lat],
        );
        const distance = haversine(pt, [lng, lat]);

        if (distance < minDist) {
            minDist = distance;
            snapped = pt;
        }
    }

    return snapped;
}

function nearestPointOnSegment(
    a: [number, number],
    b: [number, number],
    p: [number, number],
): [number, number] {
    const dx = b[0] - a[0];
    const dy = b[1] - a[1];
    const lenSq = dx * dx + dy * dy;

    if (lenSq === 0) return a;

    const t = Math.max(
        0,
        Math.min(1, ((p[0] - a[0]) * dx + (p[1] - a[1]) * dy) / lenSq),
    );

    return [a[0] + t * dx, a[1] + t * dy];
}

function haversine(a: [number, number], b: [number, number]): number {
    const R = 6371000;
    const dLat = ((b[1] - a[1]) * Math.PI) / 180;
    const dLon = ((b[0] - a[0]) * Math.PI) / 180;

    const sa =
        Math.sin(dLat / 2) ** 2 +
        Math.cos((a[1] * Math.PI) / 180) *
            Math.cos((b[1] * Math.PI) / 180) *
            Math.sin(dLon / 2) ** 2;

    return R * 2 * Math.atan2(Math.sqrt(sa), Math.sqrt(1 - sa));
}

function sampleRouteAtIntervals(
    coords: [number, number][],
    intervalKm: number,
): [number, number][] {
    const intervalM = intervalKm * 1000;
    const samples: [number, number][] = [];

    let accumulated = 0;
    let nextTarget = intervalM;

    for (let i = 1; i < coords.length; i++) {
        const segDist = haversine(coords[i - 1], coords[i]);
        accumulated += segDist;

        while (accumulated >= nextTarget) {
            const t = 1 - (accumulated - nextTarget) / segDist;

            samples.push([
                coords[i - 1][0] + t * (coords[i][0] - coords[i - 1][0]),
                coords[i - 1][1] + t * (coords[i][1] - coords[i - 1][1]),
            ]);

            nextTarget += intervalM;
        }
    }

    return samples;
}

function fmtDistance(m: number) {
    if (!m) return '—';
    if (m < 1000) return `${Math.round(m)} m`;
    return `${(m / 1000).toFixed(2)} km`;
}

function fmtDuration(s: number) {
    if (!s) return '—';

    const hours = Math.floor(s / 3600);
    const minutes = Math.ceil((s % 3600) / 60);

    if (hours > 0) {
        return `${hours} hr ${minutes} min`;
    }

    return `${Math.ceil(s / 60)} min`;
}

function getRouteSnapshot(): RouteSnapshot | null {
    if (!form.route_geometry || form.distance_meters === null || form.duration_seconds === null) {
        return null;
    }

    try {
        return {
            geometry: JSON.parse(form.route_geometry),
            distance: form.distance_meters,
            duration: form.duration_seconds,
            coordinates: [...routeCoordinates.value],
        };
    } catch {
        return null;
    }
}

function applyRouteSnapshot(snapshot: RouteSnapshot) {
    form.distance_meters = Math.round(snapshot.distance);
    form.duration_seconds = Math.round(snapshot.duration);
    form.route_geometry = JSON.stringify(snapshot.geometry);
    routeCoordinates.value = [...snapshot.coordinates];

    const primarySrc = map.value?.getSource('route-line') as
        | mapboxgl.GeoJSONSource
        | undefined;

    primarySrc?.setData({
        type: 'FeatureCollection',
        features: [{ type: 'Feature', properties: {}, geometry: snapshot.geometry }],
    });

    renderStopMarkers();
    renderWaypointMarkers();
    fitMap();
}

function refreshAlternativeRouteLayers() {
    for (let i = 1; i <= 2; i++) {
        const src = map.value?.getSource(`alt-route-${i}`) as
            | mapboxgl.GeoJSONSource
            | undefined;

        const alt = alternativeRoutes.value.find((route) => route.index === i);

        src?.setData({
            type: 'FeatureCollection',
            features: alt
                ? [{ type: 'Feature', properties: {}, geometry: alt.geometry }]
                : [],
        });
    }
}

/* ─────────────────────────────────────────────────────────────────────────────
   Map init
───────────────────────────────────────────────────────────────────────────── */

function initMap() {
    if (!mapEl.value) return;

    map.value = new mapboxgl.Map({
        container: mapEl.value,
        style: 'mapbox://styles/mapbox/streets-v12',
        center: [origin.lng, origin.lat],
        zoom: 12,
    });

    map.value.addControl(new mapboxgl.NavigationControl(), 'top-right');

    map.value.on('load', () => {
        originMarker = new mapboxgl.Marker({ color: '#16a34a' })
            .setLngLat([origin.lng, origin.lat])
            .setPopup(new mapboxgl.Popup().setText(origin.name))
            .addTo(map.value!);

        for (let i = 1; i <= 2; i++) {
            map.value!.addSource(`alt-route-${i}`, {
                type: 'geojson',
                data: { type: 'FeatureCollection', features: [] },
            });

            map.value!.addLayer({
                id: `alt-route-hit-${i}`,
                type: 'line',
                source: `alt-route-${i}`,
                paint: { 'line-width': 16, 'line-opacity': 0 },
            });

            map.value!.addLayer({
                id: `alt-route-layer-${i}`,
                type: 'line',
                source: `alt-route-${i}`,
                layout: { 'line-cap': 'round' },
                paint: {
                    'line-width': 4,
                    'line-color': '#94a3b8',
                    'line-dasharray': [2, 2],
                },
            });

            map.value!.on('click', `alt-route-hit-${i}`, (e) => {
                e.preventDefault();
                ignoreNextMapClick.value = true;
                selectAlternativeRoute(i);
            });

            map.value!.on('mouseenter', `alt-route-hit-${i}`, () => {
                map.value?.getCanvas().style.setProperty('cursor', 'pointer');
            });

            map.value!.on('mouseleave', `alt-route-hit-${i}`, () => {
                map.value?.getCanvas().style.setProperty('cursor', '');
            });
        }

        map.value!.addSource('route-line', {
            type: 'geojson',
            data: { type: 'FeatureCollection', features: [] },
        });

        map.value!.addLayer({
            id: 'route-line-layer-hit',
            type: 'line',
            source: 'route-line',
            paint: { 'line-width': 18, 'line-opacity': 0 },
        });

        map.value!.addLayer({
            id: 'route-line-layer',
            type: 'line',
            source: 'route-line',
            paint: { 'line-width': 5, 'line-color': '#2563eb' },
        });

        map.value!.on('mouseenter', 'route-line-layer-hit', () => {
            map.value?.getCanvas().style.setProperty('cursor', 'crosshair');
        });

        map.value!.on('mouseleave', 'route-line-layer-hit', () => {
            map.value?.getCanvas().style.setProperty('cursor', '');
        });

        map.value!.on('click', 'route-line-layer-hit', async (e) => {
            ignoreNextMapClick.value = true;

            if (!hasDestination.value) return;

            await addDetourWaypoint(e.lngLat.lng, e.lngLat.lat);
        });

        map.value!.on('click', async (e) => {
            if (ignoreNextMapClick.value) {
                ignoreNextMapClick.value = false;
                return;
            }

            if (!hasDestination.value) {
                await setDestinationFromCoordinates(e.lngLat.lng, e.lngLat.lat);
                return;
            }

            lineClickMessage.value =
                'Click directly on the blue route line to add a detour waypoint.';
        });
    });
}

/* ─────────────────────────────────────────────────────────────────────────────
   Geocoding
───────────────────────────────────────────────────────────────────────────── */

async function searchPlaces(query: string): Promise<SearchSuggestion[]> {
    if (!query.trim()) return [];

    const url = new URL(
        `https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(query)}.json`,
    );

    url.searchParams.set('access_token', mapboxgl.accessToken);
    url.searchParams.set('autocomplete', 'true');
    url.searchParams.set('limit', '5');
    url.searchParams.set('country', 'ph');
    url.searchParams.set('language', 'en');

    const res = await fetch(url.toString());
    const data = await res.json();

    return (data.features ?? []).map((f: any) => ({
        id: f.id,
        name: f.text || f.place_name,
        full_address: f.place_name,
        longitude: f.center[0],
        latitude: f.center[1],
    }));
}

const MAX_STOP_DISTANCE_M = 500;

async function searchPlacesAlongRoute(query: string): Promise<SearchSuggestion[]> {
    if (!query.trim() || routeCoordinates.value.length < 2) return [];

    const mid = routeCoordinates.value[Math.floor(routeCoordinates.value.length / 2)];

    const url = new URL(
        `https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(query)}.json`,
    );

    url.searchParams.set('access_token', mapboxgl.accessToken);
    url.searchParams.set('autocomplete', 'true');
    url.searchParams.set('limit', '10');
    url.searchParams.set('country', 'ph');
    url.searchParams.set('language', 'en');
    url.searchParams.set('proximity', `${mid[0]},${mid[1]}`);

    const res = await fetch(url.toString());
    const data = await res.json();

    const candidates: SearchSuggestion[] = (data.features ?? []).map((f: any) => ({
        id: f.id,
        name: f.text || f.place_name,
        full_address: f.place_name,
        longitude: f.center[0],
        latitude: f.center[1],
    }));

    const existingNames = new Set(
        form.stops.map((stop) => stop.stop_name.toLowerCase().trim()),
    );

    return candidates
        .filter((candidate) => {
            const snapped = snapToRoute(candidate.longitude, candidate.latitude);

            if (haversine(snapped, [candidate.longitude, candidate.latitude]) > MAX_STOP_DISTANCE_M) {
                return false;
            }

            if (existingNames.has(candidate.name.toLowerCase().trim())) {
                return false;
            }

            if (
                form.stops.some(
                    (stop) =>
                        haversine(
                            [stop.longitude, stop.latitude],
                            [candidate.longitude, candidate.latitude],
                        ) < 50,
                )
            ) {
                return false;
            }

            return true;
        })
        .slice(0, 5);
}

async function reversePlace(lng: number, lat: number) {
    const url = new URL(
        `https://api.mapbox.com/geocoding/v5/mapbox.places/${lng},${lat}.json`,
    );

    url.searchParams.set('access_token', mapboxgl.accessToken);
    url.searchParams.set('country', 'ph');
    url.searchParams.set('language', 'en');
    url.searchParams.set('limit', '1');

    const res = await fetch(url.toString());
    const data = await res.json();

    return data.features?.[0] ?? null;
}

/* ─────────────────────────────────────────────────────────────────────────────
   Destination
───────────────────────────────────────────────────────────────────────────── */

async function setDestinationFromSuggestion(item: SearchSuggestion) {
    form.destination_name = item.name;
    form.destination_lat = item.latitude;
    form.destination_lng = item.longitude;

    destinationQuery.value = item.full_address;
    destinationSuggestions.value = [];
    lineClickMessage.value = '';

    destinationMarker.value?.remove();

    destinationMarker.value = new mapboxgl.Marker({
        color: '#dc2626',
        draggable: true,
    })
        .setLngLat([item.longitude, item.latitude])
        .setPopup(new mapboxgl.Popup().setText(item.name))
        .addTo(map.value!);

    destinationMarker.value.on('dragend', async () => {
        const ll = destinationMarker.value!.getLngLat();

        form.destination_lat = ll.lat;
        form.destination_lng = ll.lng;

        const place = await reversePlace(ll.lng, ll.lat);

        if (place) {
            form.destination_name = place.text || place.place_name || form.destination_name;
            destinationQuery.value = place.place_name || form.destination_name;
        }

        await redrawRoute();
    });

    await redrawRoute();
    fitMap();
}

async function setDestinationFromCoordinates(lng: number, lat: number) {
    const place = await reversePlace(lng, lat);

    await setDestinationFromSuggestion({
        id: place?.id ?? `dest-${lng}-${lat}`,
        name: place?.text || place?.place_name || 'Pinned Destination',
        full_address: place?.place_name || 'Pinned destination',
        latitude: lat,
        longitude: lng,
    });
}

/* ─────────────────────────────────────────────────────────────────────────────
   Detour waypoints
───────────────────────────────────────────────────────────────────────────── */

async function addDetourWaypoint(lng: number, lat: number) {
    const insertIndex = findInsertIndex(lng, lat);
    waypoints.value.splice(insertIndex, 0, { lng, lat });

    lineClickMessage.value =
        'Detour point added. Drag the purple point to reshape the route.';

    await redrawRoute();
    renderWaypointMarkers();
}

function findInsertIndex(lng: number, lat: number): number {
    if (waypoints.value.length === 0 || routeCoordinates.value.length < 2) {
        return waypoints.value.length;
    }

    let nearestIdx = 0;
    let minDist = Infinity;

    for (let i = 0; i < routeCoordinates.value.length; i++) {
        const d = haversine(routeCoordinates.value[i], [lng, lat]);

        if (d < minDist) {
            minDist = d;
            nearestIdx = i;
        }
    }

    let insertAt = 0;

    for (const wp of waypoints.value) {
        let wpIdx = 0;
        let wpMin = Infinity;

        for (let i = 0; i < routeCoordinates.value.length; i++) {
            const d = haversine(routeCoordinates.value[i], [wp.lng, wp.lat]);

            if (d < wpMin) {
                wpMin = d;
                wpIdx = i;
            }
        }

        if (wpIdx < nearestIdx) insertAt++;
    }

    return insertAt;
}

function renderWaypointMarkers() {
    clearWaypointMarkers();

    waypoints.value.forEach((wp, index) => {
        const el = document.createElement('div');
        el.title = 'Drag to reshape route';
        el.style.cssText = `
            width:18px;
            height:18px;
            background:#7c3aed;
            border:3px solid white;
            border-radius:50%;
            cursor:grab;
            box-shadow:0 2px 6px rgba(0,0,0,0.35);
        `;

        const marker = new mapboxgl.Marker({
            element: el,
            draggable: true,
        })
            .setLngLat([wp.lng, wp.lat])
            .addTo(map.value!);

        marker.on('drag', () => {
            const ll = marker.getLngLat();
            waypoints.value[index] = { lng: ll.lng, lat: ll.lat };
        });

        marker.on('dragend', async () => {
            const ll = marker.getLngLat();
            waypoints.value[index] = { lng: ll.lng, lat: ll.lat };
            await redrawRoute();
        });

        el.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            waypoints.value.splice(index, 1);
            marker.remove();
            renderWaypointMarkers();
            redrawRoute();
        });

        waypointMarkers.value.push(marker);
    });
}

function clearWaypointMarkers() {
    waypointMarkers.value.forEach((m) => m.remove());
    waypointMarkers.value = [];
}

function removeAllWaypoints() {
    waypoints.value = [];
    clearWaypointMarkers();
    redrawRoute();
}

/* ─────────────────────────────────────────────────────────────────────────────
   Alternative routes
───────────────────────────────────────────────────────────────────────────── */

function selectAlternativeRoute(index: number) {
    if (!map.value) return;

    if (index === 0) {
        if (!originalPrimaryRoute.value) return;

        selectedRouteIndex.value = 0;
        applyRouteSnapshot(originalPrimaryRoute.value);
        lineClickMessage.value = 'Switched back to Route 1.';
        return;
    }

    const alt = alternativeRoutes.value.find((route) => route.index === index);
    if (!alt) return;

    selectedRouteIndex.value = index;

    applyRouteSnapshot({
        geometry: alt.geometry,
        distance: alt.distance,
        duration: alt.duration,
        coordinates: alt.coordinates,
    });

    lineClickMessage.value = `Switched to Route ${index + 1}.`;
}

function clearAlternativeRouteLayers() {
    for (let i = 1; i <= 2; i++) {
        const src = map.value?.getSource(`alt-route-${i}`) as
            | mapboxgl.GeoJSONSource
            | undefined;

        src?.setData({ type: 'FeatureCollection', features: [] });
    }

    alternativeRoutes.value = [];
    originalPrimaryRoute.value = null;
    showAlternatives.value = false;
    selectedRouteIndex.value = 0;
}

/* ─────────────────────────────────────────────────────────────────────────────
   Stops
───────────────────────────────────────────────────────────────────────────── */

async function addStopFromSuggestion(item: SearchSuggestion) {
    const alreadyExists = form.stops.some(
        (stop) => stop.stop_name.toLowerCase().trim() === item.name.toLowerCase().trim(),
    );

    if (alreadyExists) {
        stopQuery.value = '';
        stopSuggestions.value = [];
        lineClickMessage.value = `"${item.name}" is already added as a stop.`;
        return;
    }

    let [finalLng, finalLat] = [item.longitude, item.latitude];

    if (routeCoordinates.value.length >= 2) {
        [finalLng, finalLat] = snapToRoute(item.longitude, item.latitude);
    }

    if (
        form.stops.some(
            (stop) => haversine([stop.longitude, stop.latitude], [finalLng, finalLat]) < 50,
        )
    ) {
        stopQuery.value = '';
        stopSuggestions.value = [];
        lineClickMessage.value = `A stop already exists very close to "${item.name}".`;
        return;
    }

    form.stops.push({
        stop_name: item.name,
        stop_type: 'stop',
        address: item.full_address,
        latitude: finalLat,
        longitude: finalLng,
        mapbox_feature_id: item.id,
        stop_order: form.stops.length + 2,
    });

    stopQuery.value = '';
    stopSuggestions.value = [];
    showStopSearch.value = false;

    renderStopMarkers();
    await redrawRoute();
}

/* ─────────────────────────────────────────────────────────────────────────────
   Auto tools
───────────────────────────────────────────────────────────────────────────── */

async function autoGenerateStops() {
    if (routeCoordinates.value.length < 2) return;

    loadingAutoGenerate.value = true;

    try {
        const samples = sampleRouteAtIntervals(
            routeCoordinates.value,
            autoGenerateInterval.value,
        );

        const newStops: StopItem[] = [];

        for (const [lng, lat] of samples) {
            const place = await reversePlace(lng, lat);

            newStops.push({
                stop_name:
                    place?.text ||
                    place?.place_name ||
                    `Stop (${lat.toFixed(4)}, ${lng.toFixed(4)})`,
                stop_type: 'stop',
                address: place?.place_name ?? null,
                latitude: lat,
                longitude: lng,
                mapbox_feature_id: place?.id ?? null,
                stop_order: 0,
            });
        }

        form.stops = newStops;
        renderStopMarkers();
        await redrawRoute();
    } finally {
        loadingAutoGenerate.value = false;
    }
}

async function suggestLandmarks() {
    if (!hasDestination.value) return;

    loadingLandmarks.value = true;
    landmarkSuggestions.value = [];
    showLandmarks.value = false;

    try {
        const sampleCoords =
            routeCoordinates.value.length >= 2
                ? sampleRouteAtIntervals(
                      routeCoordinates.value,
                      Math.max(3, (form.distance_meters ?? 10000) / 1000 / 4),
                  )
                : [];

        const pointsToSearch: [number, number][] = [
            [origin.lng, origin.lat],
            ...sampleCoords.slice(0, 3),
            [form.destination_lng!, form.destination_lat!],
        ];

        const seen = new Set<string>();
        const results: SearchSuggestion[] = [];

        for (const [lng, lat] of pointsToSearch) {
            const url = new URL(
                'https://api.mapbox.com/geocoding/v5/mapbox.places/terminal bus stop landmark.json',
            );

            url.searchParams.set('access_token', mapboxgl.accessToken);
            url.searchParams.set('proximity', `${lng},${lat}`);
            url.searchParams.set('limit', '3');
            url.searchParams.set('country', 'ph');
            url.searchParams.set('types', 'poi');

            const res = await fetch(url.toString());
            const data = await res.json();

            for (const f of data.features ?? []) {
                if (!seen.has(f.id)) {
                    seen.add(f.id);

                    results.push({
                        id: f.id,
                        name: f.text || f.place_name,
                        full_address: f.place_name,
                        longitude: f.center[0],
                        latitude: f.center[1],
                    });
                }
            }
        }

        landmarkSuggestions.value = results;
        showLandmarks.value = true;
    } finally {
        loadingLandmarks.value = false;
    }
}

async function addLandmarkAsStop(item: SearchSuggestion) {
    await addStopFromSuggestion(item);
    landmarkSuggestions.value = landmarkSuggestions.value.filter(
        (landmark) => landmark.id !== item.id,
    );
}

/* ─────────────────────────────────────────────────────────────────────────────
   Markers
───────────────────────────────────────────────────────────────────────────── */

function renderStopMarkers() {
    clearStopMarkers();

    form.stops.forEach((stop, index) => {
        const marker = new mapboxgl.Marker({
            color: '#f59e0b',
            draggable: true,
        })
            .setLngLat([stop.longitude, stop.latitude])
            .setPopup(new mapboxgl.Popup().setText(`${index + 2}. ${stop.stop_name}`))
            .addTo(map.value!);

        marker.on('dragend', async () => {
            const ll = marker.getLngLat();
            let [finalLng, finalLat] = [ll.lng, ll.lat];

            if (routeCoordinates.value.length >= 2) {
                [finalLng, finalLat] = snapToRoute(ll.lng, ll.lat);
                marker.setLngLat([finalLng, finalLat]);
            }

            const place = await reversePlace(finalLng, finalLat);

            form.stops[index].latitude = finalLat;
            form.stops[index].longitude = finalLng;

            if (place) {
                form.stops[index].stop_name =
                    place.text || place.place_name || form.stops[index].stop_name;
                form.stops[index].address =
                    place.place_name || form.stops[index].address;
            }

            await redrawRoute();
        });

        stopMarkers.value.push(marker);
    });
}

function clearStopMarkers() {
    stopMarkers.value.forEach((m) => m.remove());
    stopMarkers.value = [];
}

function clearRouteLine() {
    const src = map.value?.getSource('route-line') as
        | mapboxgl.GeoJSONSource
        | undefined;

    src?.setData({ type: 'FeatureCollection', features: [] });
}

/* ─────────────────────────────────────────────────────────────────────────────
   Route fetch & draw
───────────────────────────────────────────────────────────────────────────── */

async function redrawRoute() {
    if (!form.destination_lat || !form.destination_lng || !map.value) {
        form.distance_meters = null;
        form.duration_seconds = null;
        form.route_geometry = null;
        routeCoordinates.value = [];
        clearRouteLine();
        clearAlternativeRouteLayers();
        clearStopMarkers();
        return;
    }

    const allCoords: string[] = [
        `${origin.lng},${origin.lat}`,
        ...waypoints.value.map((w) => `${w.lng},${w.lat}`),
        ...form.stops.map((s) => `${s.longitude},${s.latitude}`),
        `${form.destination_lng},${form.destination_lat}`,
    ];

    const url = new URL(
        `https://api.mapbox.com/directions/v5/mapbox/driving/${allCoords.join(';')}`,
    );

    url.searchParams.set('geometries', 'geojson');
    url.searchParams.set('overview', 'full');
    url.searchParams.set('steps', 'false');
    url.searchParams.set('alternatives', 'true');
    url.searchParams.set('access_token', mapboxgl.accessToken);

    const res = await fetch(url.toString());
    const data = await res.json();

    if (!data.routes?.length) {
        form.distance_meters = null;
        form.duration_seconds = null;
        form.route_geometry = null;
        routeCoordinates.value = [];
        originalPrimaryRoute.value = null;
        clearRouteLine();
        clearAlternativeRouteLayers();
        return;
    }

    const primary = data.routes[0];

    form.distance_meters = Math.round(primary.distance);
    form.duration_seconds = Math.round(primary.duration);
    form.route_geometry = JSON.stringify(primary.geometry);
    routeCoordinates.value = primary.geometry.coordinates as [number, number][];
    selectedRouteIndex.value = 0;

    originalPrimaryRoute.value = {
        geometry: primary.geometry,
        distance: primary.distance,
        duration: primary.duration,
        coordinates: primary.geometry.coordinates as [number, number][],
    };

    const primarySrc = map.value.getSource('route-line') as mapboxgl.GeoJSONSource;

    primarySrc.setData({
        type: 'FeatureCollection',
        features: [{ type: 'Feature', properties: {}, geometry: primary.geometry }],
    });

    alternativeRoutes.value = [];

    const alts = data.routes.slice(1, 3);

    alts.forEach((alt: any, i: number) => {
        const altIndex = i + 1;

        alternativeRoutes.value.push({
            index: altIndex,
            geometry: alt.geometry,
            distance: alt.distance,
            duration: alt.duration,
            coordinates: alt.geometry.coordinates as [number, number][],
        });
    });

    refreshAlternativeRouteLayers();

    showAlternatives.value = alternativeRoutes.value.length > 0;

    renderStopMarkers();
    renderWaypointMarkers();
    fitMap();
}

/* ─────────────────────────────────────────────────────────────────────────────
   Map fit
───────────────────────────────────────────────────────────────────────────── */

function fitMap() {
    if (!map.value) return;

    const bounds = new mapboxgl.LngLatBounds();
    bounds.extend([origin.lng, origin.lat]);

    if (form.destination_lat && form.destination_lng) {
        bounds.extend([form.destination_lng, form.destination_lat]);
    }

    form.stops.forEach((stop) => bounds.extend([stop.longitude, stop.latitude]));
    waypoints.value.forEach((waypoint) =>
        bounds.extend([waypoint.lng, waypoint.lat]),
    );

    map.value.fitBounds(bounds, { padding: 60, maxZoom: 14 });
}

/* ─────────────────────────────────────────────────────────────────────────────
   Drag reorder
───────────────────────────────────────────────────────────────────────────── */

function onDragStart(index: number) {
    draggedStopIndex.value = index;
}

function onDragOver(event: DragEvent, index: number) {
    event.preventDefault();
    dragOverIndex.value = index;
}

function onDrop(index: number) {
    if (
        draggedStopIndex.value === null ||
        draggedStopIndex.value === index
    ) {
        draggedStopIndex.value = null;
        dragOverIndex.value = null;
        return;
    }

    const arr = [...form.stops];
    const [moved] = arr.splice(draggedStopIndex.value, 1);
    arr.splice(index, 0, moved);
    form.stops = arr;

    draggedStopIndex.value = null;
    dragOverIndex.value = null;

    renderStopMarkers();
    redrawRoute();
}

function onDragEnd() {
    draggedStopIndex.value = null;
    dragOverIndex.value = null;
}

/* ─────────────────────────────────────────────────────────────────────────────
   Misc
───────────────────────────────────────────────────────────────────────────── */

function removeStop(index: number) {
    form.stops.splice(index, 1);
    renderStopMarkers();
    redrawRoute();
}

function clearDestination() {
    form.destination_name = '';
    form.destination_lat = null;
    form.destination_lng = null;

    destinationQuery.value = '';
    destinationSuggestions.value = [];

    form.distance_meters = null;
    form.duration_seconds = null;
    form.route_geometry = null;
    form.stops = [];

    lineClickMessage.value = '';
    routeCoordinates.value = [];

    landmarkSuggestions.value = [];
    showLandmarks.value = false;

    alternativeRoutes.value = [];
    showAlternatives.value = false;
    selectedRouteIndex.value = 0;
    originalPrimaryRoute.value = null;

    waypoints.value = [];

    destinationMarker.value?.remove();
    destinationMarker.value = null;

    clearStopMarkers();
    clearWaypointMarkers();
    clearRouteLine();
    clearAlternativeRouteLayers();

    if (!routeNameTouched.value) {
        form.route_name = form.origin_name;
    }
}

function buildRouteStopsForSubmit(): StopItem[] {
    return [
        {
            stop_name: form.origin_name,
            stop_type: 'origin',
            address: form.origin_name,
            latitude: Number(form.origin_lat),
            longitude: Number(form.origin_lng),
            mapbox_feature_id: null,
            stop_order: 1,
        },
        ...form.stops.map((stop, index) => ({
            ...stop,
            stop_order: index + 2,
        })),
        {
            stop_name: form.destination_name,
            stop_type: 'destination',
            address: destinationQuery.value || form.destination_name,
            latitude: Number(form.destination_lat),
            longitude: Number(form.destination_lng),
            mapbox_feature_id: null,
            stop_order: form.stops.length + 2,
        },
    ];
}

async function submit() {
    if (!form.destination_name || !form.destination_lat || !form.destination_lng) {
        form.setError('destination_name', 'Please select or pin a destination.');
        return;
    }

    form.clearErrors('destination_name');
    form.stops = buildRouteStopsForSubmit();

    await nextTick();
    form.post(store());
}

onMounted(async () => {
    await nextTick();
    initMap();
});

onBeforeUnmount(() => {
    originMarker?.remove();
    destinationMarker.value?.remove();
    clearStopMarkers();
    clearWaypointMarkers();
    map.value?.remove();
});
</script>

<template>
    <Head title="Create Route" />

    <AppLayout>
        <div class="space-y-6 p-4 sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge variant="outline" class="gap-1">
                            <RouteIcon class="h-3.5 w-3.5" />
                            Route Builder
                        </Badge>

                        <Badge variant="secondary">
                            Fixed Origin: {{ form.origin_name }}
                        </Badge>
                    </div>

                    <div>
                        <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">
                            Create Route
                        </h1>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Set the destination, shape the path, add bus stops, then save the final route.
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="!hasDestination"
                        @click="clearDestination"
                    >
                        <Trash2 class="mr-2 h-4 w-4" />
                        Clear Route
                    </Button>

                    <Button
                        :disabled="form.processing"
                        @click="submit"
                    >
                        Save Route
                    </Button>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <Card class="rounded-2xl">
                    <CardHeader class="pb-3">
                        <CardDescription class="flex items-center gap-2">
                            <MapPinned class="h-4 w-4" />
                            Destination
                        </CardDescription>
                        <CardTitle class="text-base sm:text-lg">
                            {{ form.destination_name || 'Not selected yet' }}
                        </CardTitle>
                    </CardHeader>
                </Card>

                <Card class="rounded-2xl">
                    <CardHeader class="pb-3">
                        <CardDescription class="flex items-center gap-2">
                            <Milestone class="h-4 w-4" />
                            Distance
                        </CardDescription>
                        <CardTitle class="text-base sm:text-lg">
                            {{ form.distance_meters ? fmtDistance(form.distance_meters) : '—' }}
                        </CardTitle>
                    </CardHeader>
                </Card>

                <Card class="rounded-2xl">
                    <CardHeader class="pb-3">
                        <CardDescription class="flex items-center gap-2">
                            <Clock3 class="h-4 w-4" />
                            Duration
                        </CardDescription>
                        <CardTitle class="text-base sm:text-lg">
                            {{ form.duration_seconds ? fmtDuration(form.duration_seconds) : '—' }}
                        </CardTitle>
                    </CardHeader>
                </Card>

                <Card class="rounded-2xl">
                    <CardHeader class="pb-3">
                        <CardDescription class="flex items-center gap-2">
                            <Bus class="h-4 w-4" />
                            Stops in Preview
                        </CardDescription>
                        <CardTitle class="text-base sm:text-lg">
                            {{ totalVisibleStops }}
                        </CardTitle>
                    </CardHeader>
                </Card>
            </div>

            <Card class="rounded-2xl">
                <CardHeader>
                    <CardTitle>Basic Details</CardTitle>
                    <CardDescription>
                        Set the route name and assign the gate before saving.
                    </CardDescription>
                </CardHeader>

                <CardContent class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="route_name">Route Name</Label>
                        <Input
                            id="route_name"
                            :model-value="form.route_name"
                            placeholder="Enter route name"
                            @update:model-value="onRouteNameInput"
                        />
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs text-muted-foreground">
                                Suggested: {{ defaultRouteName }}
                            </p>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="resetRouteNameToDefault"
                            >
                                Use Suggested Name
                            </Button>
                        </div>
                        <InputError :message="form.errors.route_name" />
                    </div>

                    <div class="space-y-2">
                        <Label for="gate_id">Gate</Label>
                        <Select v-model="form.gate_id">
                            <SelectTrigger id="gate_id">
                                <SelectValue placeholder="Select gate" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="gate in gates"
                                    :key="gate.id"
                                    :value="String(gate.id)"
                                >
                                    {{ gate.gate_name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.gate_id" />
                    </div>
                </CardContent>
            </Card>

            <div class="grid gap-6 xl:grid-cols-[1.4fr_0.8fr]">
                <div class="space-y-6">
                    <Card class="rounded-2xl">
                        <CardHeader>
                            <CardTitle>Select Destination</CardTitle>
                            <CardDescription>
                                Search a destination or click directly on the map to pin one.
                            </CardDescription>
                        </CardHeader>

                        <CardContent class="space-y-3">
                            <div class="space-y-2">
                                <Label>Destination Search</Label>
                                <div class="relative">
                                    <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        v-model="destinationQuery"
                                        class="pl-9"
                                        placeholder="Search destination in the Philippines..."
                                    />
                                </div>

                                <div
                                    v-if="destinationSuggestions.length"
                                    class="rounded-xl border bg-background shadow-sm"
                                >
                                    <button
                                        v-for="item in destinationSuggestions"
                                        :key="item.id"
                                        type="button"
                                        class="block w-full border-b px-4 py-3 text-left last:border-b-0 hover:bg-muted/50"
                                        @click="setDestinationFromSuggestion(item)"
                                    >
                                        <div class="font-medium">{{ item.name }}</div>
                                        <div class="text-xs text-muted-foreground">
                                            {{ item.full_address }}
                                        </div>
                                    </button>
                                </div>

                                <p class="text-xs text-muted-foreground">
                                    Tip: Before a destination is selected, clicking the map will pin the destination.
                                </p>
                            </div>

                            <InputError :message="form.errors.destination_name" />
                        </CardContent>
                    </Card>

                    <Card class="overflow-hidden rounded-2xl">
                        <CardHeader>
                            <CardTitle>Map Workspace</CardTitle>
                            <CardDescription>
                                Build and inspect the route visually.
                            </CardDescription>
                            <CardAction>
                                <Badge variant="secondary">
                                    {{ routeHealthText }}
                                </Badge>
                            </CardAction>
                        </CardHeader>

                        <CardContent class="space-y-4">
                            <div
                                v-if="lineClickMessage"
                                class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-700"
                            >
                                {{ lineClickMessage }}
                            </div>

                            <div
                                v-if="hasDestination"
                                class="rounded-xl border border-purple-200 bg-purple-50 p-4"
                            >
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="space-y-1">
                                        <p class="text-sm font-semibold text-purple-800">
                                            Route Reshaping
                                        </p>
                                        <p class="text-xs text-purple-700">
                                            Click the blue line to add a detour point. Drag the purple marker to reshape the route. Right-click a purple marker to remove it.
                                        </p>
                                    </div>

                                    <Button
                                        v-if="waypoints.length"
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        class="border-purple-300 text-purple-700 hover:bg-purple-100"
                                        @click="removeAllWaypoints"
                                    >
                                        Clear Detour Points
                                    </Button>
                                </div>
                            </div>

                            <div
                                ref="mapEl"
                                class="h-[420px] w-full rounded-xl border sm:h-[560px]"
                            />

                            <div class="flex flex-wrap gap-4 text-xs text-muted-foreground">
                                <span class="flex items-center gap-1.5">
                                    <span class="inline-block h-2.5 w-2.5 rounded-full bg-green-600" />
                                    Origin
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <span class="inline-block h-2.5 w-2.5 rounded-full bg-red-600" />
                                    Destination
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <span class="inline-block h-2.5 w-2.5 rounded-full bg-yellow-500" />
                                    Bus stops
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <span class="inline-block h-2.5 w-2.5 rounded-full bg-purple-600" />
                                    Detour points
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <span class="inline-block w-5 border-t-2 border-dashed border-slate-400" />
                                    Alternative routes
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card
                        v-if="showAlternatives && alternativeRoutes.length"
                        class="rounded-2xl"
                    >
                        <CardHeader>
                            <CardTitle>Alternative Routes</CardTitle>
                            <CardDescription>
                                Click a dashed line on the map or choose an option below.
                            </CardDescription>
                        </CardHeader>

                        <CardContent class="space-y-3">
                            <button
                                type="button"
                                :class="[
                                    'w-full rounded-xl border p-4 text-left transition',
                                    selectedRouteIndex === 0
                                        ? 'border-blue-500 bg-blue-50'
                                        : 'hover:bg-muted/40',
                                ]"
                                @click="selectAlternativeRoute(0)"
                            >
                                <div class="flex items-start gap-3">
                                    <div class="rounded-md bg-blue-100 p-2 text-blue-700">
                                        <RouteIcon class="h-4 w-4" />
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                            <p class="font-medium">Route 1</p>
                                            <p class="text-xs text-muted-foreground">
                                                {{ originalPrimaryRoute ? fmtDistance(originalPrimaryRoute.distance) : '—' }}
                                                ·
                                                {{ originalPrimaryRoute ? fmtDuration(originalPrimaryRoute.duration) : '—' }}
                                            </p>
                                        </div>
                                        <p class="mt-1 text-xs text-muted-foreground">
                                            Main route currently stored as the primary option.
                                        </p>
                                    </div>
                                </div>
                            </button>

                            <button
                                v-for="alt in alternativeRoutes"
                                :key="alt.index"
                                type="button"
                                :class="[
                                    'w-full rounded-xl border p-4 text-left transition',
                                    selectedRouteIndex === alt.index
                                        ? 'border-blue-500 bg-blue-50'
                                        : 'hover:bg-muted/40',
                                ]"
                                @click="selectAlternativeRoute(alt.index)"
                            >
                                <div class="flex items-start gap-3">
                                    <div class="rounded-md bg-slate-100 p-2 text-slate-700">
                                        <GitBranch class="h-4 w-4" />
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                            <p class="font-medium">Route {{ alt.index + 1 }}</p>
                                            <p class="text-xs text-muted-foreground">
                                                {{ fmtDistance(alt.distance) }} · {{ fmtDuration(alt.duration) }}
                                            </p>
                                        </div>
                                        <p class="mt-1 text-xs text-muted-foreground">
                                            Alternate suggestion shown as a dashed line on the map.
                                        </p>
                                    </div>
                                </div>
                            </button>
                        </CardContent>
                    </Card>

                    <div class="grid gap-6 lg:grid-cols-2">
                        <Card
                            v-if="hasDestination"
                            class="rounded-2xl"
                        >
                            <CardHeader>
                                <CardTitle>Add Stop by Search</CardTitle>
                                <CardDescription>
                                    Search places close to the active route.
                                </CardDescription>
                            </CardHeader>

                            <CardContent class="space-y-3">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-xs text-muted-foreground">
                                        Only results within 500m of the route are shown.
                                    </p>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        @click="showStopSearch = !showStopSearch"
                                    >
                                        {{ showStopSearch ? 'Hide Search' : 'Show Search' }}
                                    </Button>
                                </div>

                                <div v-if="showStopSearch" class="space-y-2">
                                    <Input
                                        v-model="stopQuery"
                                        placeholder="Search a stop along the route..."
                                        :disabled="loadingStopSearch"
                                    />

                                    <div
                                        v-if="stopSuggestions.length"
                                        class="rounded-xl border bg-background"
                                    >
                                        <button
                                            v-for="item in stopSuggestions"
                                            :key="item.id"
                                            type="button"
                                            class="block w-full border-b px-4 py-3 text-left last:border-b-0 hover:bg-muted/50"
                                            @click="addStopFromSuggestion(item)"
                                        >
                                            <div class="font-medium">{{ item.name }}</div>
                                            <div class="text-xs text-muted-foreground">
                                                {{ item.full_address }}
                                            </div>
                                        </button>
                                    </div>

                                    <p
                                        v-if="stopSuggestions.length === 0 && stopQuery && !loadingStopSearch"
                                        class="text-xs text-amber-600"
                                    >
                                        No places found within 500m of the route.
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        <Card
                            v-if="hasDestination"
                            class="rounded-2xl"
                        >
                            <CardHeader>
                                <CardTitle>Auto Tools</CardTitle>
                                <CardDescription>
                                    Generate stops or suggest landmarks faster.
                                </CardDescription>
                            </CardHeader>

                            <CardContent class="space-y-5">
                                <div class="space-y-2 rounded-xl border p-4">
                                    <div class="flex items-center gap-2">
                                        <Wand2 class="h-4 w-4 text-muted-foreground" />
                                        <p class="text-sm font-medium">Auto-Generate Stops</p>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <Input
                                            v-model.number="autoGenerateInterval"
                                            type="number"
                                            min="1"
                                            max="50"
                                            class="w-24"
                                        />
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="secondary"
                                            :disabled="loadingAutoGenerate || routeCoordinates.length < 2"
                                            @click="autoGenerateStops"
                                        >
                                            {{ loadingAutoGenerate ? 'Generating…' : 'Generate' }}
                                        </Button>
                                    </div>

                                    <p class="text-xs text-muted-foreground">
                                        Replaces current stops with evenly spaced stops.
                                    </p>
                                </div>

                                <div class="space-y-2 rounded-xl border p-4">
                                    <div class="flex items-center gap-2">
                                        <Sparkles class="h-4 w-4 text-muted-foreground" />
                                        <p class="text-sm font-medium">Landmark Suggestions</p>
                                    </div>

                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="secondary"
                                        :disabled="loadingLandmarks"
                                        @click="suggestLandmarks"
                                    >
                                        {{ loadingLandmarks ? 'Searching…' : 'Suggest Landmarks' }}
                                    </Button>

                                    <div
                                        v-if="showLandmarks && landmarkSuggestions.length"
                                        class="max-h-56 overflow-y-auto rounded-xl border bg-background"
                                    >
                                        <div
                                            v-for="item in landmarkSuggestions"
                                            :key="item.id"
                                            class="flex items-center justify-between gap-3 border-b px-4 py-3 last:border-b-0"
                                        >
                                            <div class="min-w-0">
                                                <div class="truncate text-sm font-medium">
                                                    {{ item.name }}
                                                </div>
                                                <div class="text-xs text-muted-foreground">
                                                    {{ item.full_address }}
                                                </div>
                                            </div>

                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="outline"
                                                class="shrink-0"
                                                @click="addLandmarkAsStop(item)"
                                            >
                                                Add
                                            </Button>
                                        </div>
                                    </div>

                                    <p
                                        v-if="showLandmarks && !landmarkSuggestions.length && !loadingLandmarks"
                                        class="text-xs text-muted-foreground"
                                    >
                                        No additional landmarks found.
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <div class="space-y-6">
                    <Card class="rounded-2xl">
                        <CardHeader>
                            <CardTitle>Builder Status</CardTitle>
                            <CardDescription>
                                Quick overview of the current route setup.
                            </CardDescription>
                        </CardHeader>

                        <CardContent class="space-y-4 text-sm">
                            <div class="grid gap-3">
                                <div class="rounded-xl border p-4">
                                    <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                        Route Name
                                    </p>
                                    <p class="mt-1 font-semibold">
                                        {{ form.route_name || defaultRouteName }}
                                    </p>
                                </div>

                                <div class="rounded-xl border p-4">
                                    <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                        Origin
                                    </p>
                                    <p class="mt-1 font-semibold">{{ form.origin_name }}</p>
                                    <p class="mt-1 text-xs text-muted-foreground">
                                        {{ form.origin_lat }}, {{ form.origin_lng }}
                                    </p>
                                </div>

                                <div class="rounded-xl border p-4">
                                    <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                        Destination
                                    </p>
                                    <p class="mt-1 font-semibold">
                                        {{ form.destination_name || 'No destination selected' }}
                                    </p>
                                    <p
                                        v-if="form.destination_lat && form.destination_lng"
                                        class="mt-1 text-xs text-muted-foreground"
                                    >
                                        {{ form.destination_lat }}, {{ form.destination_lng }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="rounded-2xl">
                        <CardHeader>
                            <CardTitle>Route Summary</CardTitle>
                            <CardDescription>
                                Live values update as you edit the map.
                            </CardDescription>
                        </CardHeader>

                        <CardContent class="space-y-3 text-sm">
                            <div class="flex items-center justify-between rounded-lg border px-3 py-2">
                                <span class="text-muted-foreground">Distance</span>
                                <span class="font-medium">
                                    {{ form.distance_meters ? fmtDistance(form.distance_meters) : '—' }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between rounded-lg border px-3 py-2">
                                <span class="text-muted-foreground">Duration</span>
                                <span class="font-medium">
                                    {{ form.duration_seconds ? fmtDuration(form.duration_seconds) : '—' }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between rounded-lg border px-3 py-2">
                                <span class="text-muted-foreground">Bus Stops</span>
                                <span class="font-medium">{{ totalBusStops }}</span>
                            </div>

                            <div class="flex items-center justify-between rounded-lg border px-3 py-2">
                                <span class="text-muted-foreground">Detour Points</span>
                                <span class="font-medium">{{ waypoints.length }}</span>
                            </div>

                            <div
                                v-if="alternativeRoutes.length"
                                class="flex items-center justify-between rounded-lg border px-3 py-2"
                            >
                                <span class="text-muted-foreground">Selected Route Option</span>
                                <span class="font-medium">
                                    {{ selectedRouteIndex + 1 }} / {{ alternativeRoutes.length + 1 }}
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card
                        v-if="waypoints.length"
                        class="rounded-2xl"
                    >
                        <CardHeader>
                            <CardTitle>Detour Points</CardTitle>
                            <CardDescription>
                                These shape the route but are not saved as bus stops.
                            </CardDescription>
                        </CardHeader>

                        <CardContent class="space-y-3">
                            <div
                                v-for="(wp, index) in waypoints"
                                :key="index"
                                class="flex items-start justify-between gap-3 rounded-xl border border-purple-200 bg-purple-50 p-3"
                            >
                                <div>
                                    <p class="font-medium text-purple-800">
                                        Waypoint {{ index + 1 }}
                                    </p>
                                    <p class="text-xs text-purple-700">
                                        {{ wp.lat.toFixed(5) }}, {{ wp.lng.toFixed(5) }}
                                    </p>
                                </div>

                                <Button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    class="text-red-500 hover:text-red-700"
                                    @click="waypoints.splice(index, 1); renderWaypointMarkers(); redrawRoute()"
                                >
                                    Remove
                                </Button>
                            </div>

                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                class="w-full border-purple-300 text-purple-700 hover:bg-purple-100"
                                @click="removeAllWaypoints"
                            >
                                Clear All Detour Points
                            </Button>
                        </CardContent>
                    </Card>

                    <Card class="rounded-2xl">
                        <CardHeader>
                            <CardTitle>Stops Preview</CardTitle>
                            <CardDescription>
                                Drag to reorder intermediate bus stops.
                            </CardDescription>
                        </CardHeader>

                        <CardContent class="space-y-3">
                            <div class="rounded-xl border border-green-200 bg-green-50 p-4">
                                <div class="flex items-start gap-3">
                                    <div class="rounded-full bg-green-600 px-2 py-0.5 text-xs font-semibold text-white">
                                        1
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ form.origin_name }}</p>
                                        <p class="text-[11px] uppercase tracking-wide text-muted-foreground">
                                            Origin
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-for="(stop, index) in form.stops"
                                :key="`${stop.stop_name}-${stop.latitude}-${index}`"
                                :draggable="true"
                                :class="[
                                    'cursor-grab select-none rounded-xl border p-4 transition',
                                    dragOverIndex === index ? 'border-blue-400 bg-blue-50' : '',
                                    draggedStopIndex === index ? 'opacity-60' : '',
                                ]"
                                @dragstart="onDragStart(index)"
                                @dragover="onDragOver($event, index)"
                                @drop="onDrop(index)"
                                @dragend="onDragEnd"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex min-w-0 items-start gap-3">
                                        <GripVertical class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />

                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="rounded-full bg-amber-500 px-2 py-0.5 text-xs font-semibold text-white">
                                                    {{ index + 2 }}
                                                </span>
                                                <p class="font-medium">
                                                    {{ stop.stop_name }}
                                                </p>
                                            </div>

                                            <p class="mt-1 text-xs text-muted-foreground">
                                                {{ stop.address || 'No address available' }}
                                            </p>

                                            <p class="mt-1 text-[11px] uppercase tracking-wide text-muted-foreground">
                                                {{ stop.stop_type }}
                                            </p>
                                        </div>
                                    </div>

                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="destructive"
                                        class="shrink-0"
                                        @click="removeStop(index)"
                                    >
                                        Remove
                                    </Button>
                                </div>
                            </div>

                            <div
                                v-if="hasDestination"
                                class="rounded-xl border border-red-200 bg-red-50 p-4"
                            >
                                <div class="flex items-start gap-3">
                                    <div class="rounded-full bg-red-600 px-2 py-0.5 text-xs font-semibold text-white">
                                        {{ form.stops.length + 2 }}
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ form.destination_name }}</p>
                                        <p class="text-[11px] uppercase tracking-wide text-muted-foreground">
                                            Destination
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="!hasDestination"
                                class="rounded-xl border border-dashed p-4 text-sm text-muted-foreground"
                            >
                                Set a destination to preview the complete route stop sequence.
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
