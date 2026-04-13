<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import InputError from '@/components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
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
import { Separator } from '@/components/ui/separator';
import type { BreadcrumbItem } from '@/types';
import {
    ArrowLeft,
    Bus,
    CheckCircle2,
    Clock3,
    DoorOpen,
    GripVertical,
    MapPin,
    MapPinned,
    Milestone,
    Navigation,
    Route as RouteIcon,
    Save,
    Search,
    Sparkles,
    Wand2,
    X,
    Ruler,

} from 'lucide-vue-next';

import { index, store } from '@/actions/App/Http/Controllers/RouteController';

import mapboxgl from 'mapbox-gl';
import 'mapbox-gl/dist/mapbox-gl.css';

/* ─────────────────────────────────────────────────────────────────────────────
   Types
───────────────────────────────────────────────────────────────────────────── */

type Gate = {
    id: number;
    gate_name: string;
};

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

type Waypoint = {
    lng: number;
    lat: number;
};

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
        pitx: {
            name: string;
            lat: number;
            lng: number;
        };
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
const waypointMarkers = ref<mapboxgl.Marker[]>([]);

const loadingDestination = ref(false);
const loadingStopSearch = ref(false);
const loadingAutoGenerate = ref(false);
const loadingLandmarks = ref(false);

const lineClickMessage = ref('');
const routeNameTouched = ref(false);
const ignoreNextMapClick = ref(false);

const stopQuery = ref('');
const stopSuggestions = ref<SearchSuggestion[]>([]);
const autoGenerateInterval = ref(5);

const landmarkSuggestions = ref<SearchSuggestion[]>([]);
const showLandmarks = ref(false);

const draggedStopIndex = ref<number | null>(null);
const dragOverIndex = ref<number | null>(null);

const routeCoordinates = ref<[number, number][]>([]);

const alternativeRoutes = ref<AlternativeRoute[]>([]);
const selectedRouteIndex = ref(0);
const showAlternatives = ref(false);
const originalPrimaryRoute = ref<RouteSnapshot | null>(null);

const waypoints = ref<Waypoint[]>([]);

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

const routeHealthStatus = computed<'idle' | 'loading' | 'ready'>(() => {
    if (!hasDestination.value) return 'idle';
    if (!routeCoordinates.value.length) return 'loading';
    return 'ready';
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
    const query = value.trim();

    if (!query) {
        destinationSuggestions.value = [];
        return;
    }

    loadingDestination.value = true;

    try {
        destinationSuggestions.value = await searchPlaces(query);
    } finally {
        loadingDestination.value = false;
    }
});

watch(stopQuery, async (value) => {
    const query = value.trim();

    if (!query || routeCoordinates.value.length < 2) {
        stopSuggestions.value = [];
        return;
    }

    loadingStopSearch.value = true;

    try {
        stopSuggestions.value = await searchPlacesAlongRoute(query);
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

    if (hours > 0) return `${hours} hr ${minutes} min`;
    return `${Math.ceil(s / 60)} min`;
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
        features: [
            {
                type: 'Feature',
                properties: {},
                geometry: snapshot.geometry,
            },
        ],
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
                ? [
                      {
                          type: 'Feature',
                          properties: {},
                          geometry: alt.geometry,
                      },
                  ]
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
        zoom: 11.8,
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
            paint: {
                'line-width': 18,
                'line-opacity': 0,
            },
        });

        map.value!.addLayer({
            id: 'route-line-layer',
            type: 'line',
            source: 'route-line',
            paint: {
                'line-width': 5,
                'line-color': '#2563eb',
            },
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
    url.searchParams.set('limit', '6');
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

            if (
                haversine(snapped, [candidate.longitude, candidate.latitude]) >
                MAX_STOP_DISTANCE_M
            ) {
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
        .slice(0, 6);
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
    form.clearErrors('destination_name');

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
            form.destination_name =
                place.text || place.place_name || form.destination_name;
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
        el.style.cssText =
            'width:18px;height:18px;background:#7c3aed;border:3px solid white;border-radius:50%;cursor:grab;box-shadow:0 2px 6px rgba(0,0,0,0.35);';

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

        src?.setData({
            type: 'FeatureCollection',
            features: [],
        });
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
        (stop) =>
            stop.stop_name.toLowerCase().trim() ===
            item.name.toLowerCase().trim(),
    );

    if (alreadyExists) {
        lineClickMessage.value = `"${item.name}" is already added as a stop.`;
        stopQuery.value = '';
        stopSuggestions.value = [];
        return;
    }

    let [finalLng, finalLat] = [item.longitude, item.latitude];

    if (routeCoordinates.value.length >= 2) {
        [finalLng, finalLat] = snapToRoute(item.longitude, item.latitude);
    }

    if (
        form.stops.some(
            (stop) =>
                haversine(
                    [stop.longitude, stop.latitude],
                    [finalLng, finalLat],
                ) < 50,
        )
    ) {
        lineClickMessage.value =
            `A stop already exists very close to "${item.name}".`;
        stopQuery.value = '';
        stopSuggestions.value = [];
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
            .setPopup(
                new mapboxgl.Popup().setText(`${index + 2}. ${stop.stop_name}`),
            )
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
                    place.text ||
                    place.place_name ||
                    form.stops[index].stop_name;

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

    src?.setData({
        type: 'FeatureCollection',
        features: [],
    });
}

/* ─────────────────────────────────────────────────────────────────────────────
   Route fetch & draw
───────────────────────────────────────────────────────────────────────────── */

async function redrawRoute() {
    if (
        form.destination_lat === null ||
        form.destination_lng === null ||
        !map.value
    ) {
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
        features: [
            {
                type: 'Feature',
                properties: {},
                geometry: primary.geometry,
            },
        ],
    });

    alternativeRoutes.value = [];

    data.routes.slice(1, 3).forEach((alt: any, i: number) => {
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

function fitMap() {
    if (!map.value) return;

    const bounds = new mapboxgl.LngLatBounds();
    bounds.extend([origin.lng, origin.lat]);

    if (form.destination_lat !== null && form.destination_lng !== null) {
        bounds.extend([form.destination_lng, form.destination_lat]);
    }

    form.stops.forEach((stop) => bounds.extend([stop.longitude, stop.latitude]));
    waypoints.value.forEach((waypoint) =>
        bounds.extend([waypoint.lng, waypoint.lat]),
    );

    map.value.fitBounds(bounds, {
        padding: 60,
        maxZoom: 14,
    });
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
    if (draggedStopIndex.value === null || draggedStopIndex.value === index) {
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
    stopQuery.value = '';
    stopSuggestions.value = [];

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
    if (
        !form.destination_name ||
        form.destination_lat === null ||
        form.destination_lng === null
    ) {
        form.setError('destination_name', 'Please select or pin a destination.');
        return;
    }

    form.clearErrors('destination_name');
    form.stops = buildRouteStopsForSubmit();

    await nextTick();

    form.post(store().url);
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

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Routes', href: index().url },
    { title: 'Create', href: '#' },
];
</script>

<template>
    <Head title="Create Route" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Card>
                <CardHeader class="py-0">
                    <div class="flex items-center gap-4">
                        <div
                            class="relative h-32 w-32 shrink-0 overflow-hidden rounded-lg border-2 bg-primary shadow-sm flex items-center justify-center"
                        >
                            <RouteIcon class="h-10 w-10 text-primary-foreground" />
                        </div>

                        <div class="gap-2 w-full">
                            <div class="flex flex-row gap-2 pb-2 w-full items-center">
                                <h1 class="text-2xl leading-tight font-bold tracking-tight">
                                    <!-- {{ route.route_name }} -->
                                    Create Route
                                </h1>
                                <div class="ml-2 flex flex-1 items-center">
                                    <hr class="h-px w-full border border-rose-500" />
                                    <div class="border-7 border-rose-500 rounded-xs">
                                        <div class="border-3 border-white rounded-xs"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-end">
                                <div class="flex shrink-0 items-center gap-2">
                                    <Button
                                        as-child
                                        variant="outline"
                                        class="rounded-lg bg-card border-slate-200 text-slate-600 hover:bg-slate-100 cursor-pointer"
                                    >
                                        <Link :href="index().url">
                                            <ArrowLeft class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardHeader>
            </Card>

            <div class="grid gap-4 xl:grid-cols-2">
                <Card class="py-6">
                    <CardHeader>
                        <CardTitle>Route Summary</CardTitle>
                    </CardHeader>
                    <CardContent class="px-6 grid divide-y gap-y-2 pt-2 border-t border-slate-100">
                        <div class="py-2">
                            <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">Origin</span>
                            <span class="text-sm font-semibold truncate block">{{ form.origin_name }}</span>
                        </div>
                        <div class="py-2">
                            <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">Destination</span>
                            <span class="text-sm font-semibold truncate block">{{ form.destination_name || '—' }}</span>
                        </div>
                        <div class="">
                            <div class="items-center flex">
                                <div class="h-full mr-4">
                                    <Ruler class="h-4 w-4 inline-block text-primary" />
                                </div>
                                <span class="text-sm ">{{ form.distance_meters ? fmtDistance(form.distance_meters) : '—' }}</span>
                            </div>
                            <div class="items-center flex">
                                <div class="h-full mr-4">
                                    <Clock3 class="h-4 w-4 inline-block text-primary" />
                                </div>
                                <span class="text-sm ">{{ form.duration_seconds ? fmtDuration(form.duration_seconds) : '—' }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="py-6 h-fit">
                    <CardHeader>
                            <CardTitle>Route Details</CardTitle>
                    </CardHeader>
                    <CardContent class="px-6 pt-6 space-y-4 border-t border-slate-100">
                        <div class="space-y-1.5">
                            <Label
                                for="route_name_sidebar"
                                class="text-xs font-medium uppercase tracking-wide text-muted-foreground"
                            >
                                Route Name
                            </Label>
                            <Input
                                id="route_name_sidebar"
                                :model-value="form.route_name"
                                placeholder="Enter route name"
                                class="h-10"
                                @update:model-value="onRouteNameInput"
                            />
                            <div class="flex items-center justify-between gap-3 pt-4">
                                <p class="text-xs text-muted-foreground">
                                    Suggested:
                                    <span class="italic">{{ defaultRouteName }}</span>
                                </p>
                                <button
                                    type="button"
                                    class="text-xs text-primary underline underline-offset-2 hover:no-underline"
                                    @click="resetRouteNameToDefault"
                                >
                                    Use suggested
                                </button>
                            </div>
                            <InputError :message="form.errors.route_name" />
                        </div>

                        <div class="space-y-1.5 pt-4">
                            <Label
                                for="gate_id_sidebar"
                                class="text-xs font-medium uppercase tracking-wide text-muted-foreground"
                            >
                                Gate Assignment
                            </Label>

                            <Select v-model="form.gate_id">
                                <SelectTrigger id="gate_id_sidebar" class="h-10">
                                    <SelectValue placeholder="Select a gate…" />
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

                        <Separator />

                            <Button
                                variant="outline"
                                class="w-full cursor-pointer bg-primary text-primary-foreground hover:bg-primary/90 rounded-lg hover:text-primary-foreground"
                                :disabled="form.processing || !routeReady"
                                @click="submit"
                            >
                                <Save class="h-4 w-4" />
                                {{ form.processing ? 'Saving Changes…' : 'Save Changes' }}
                            </Button>
                        <!-- </div> -->

                        <p
                            v-if="!routeReady"
                            class="text-center text-[11px] text-muted-foreground"
                        >
                            {{
                                !hasDestination
                                    ? 'Select a destination first.'
                                    : 'Waiting for route to compute.'
                            }}
                        </p>
                    </CardContent>
                </Card>
            </div>
        <!-- </div> -->

            <div class="grid items-start gap-4 xl:grid-cols-[1fr_380px]">
                <div class="space-y-4">
                    <Card class="py-6">
                        <CardHeader class="flex items-center justify-between">
                            <CardTitle class="text-base">Map Workspace</CardTitle>
                        </CardHeader>

                        <CardContent class="p-6 space-y-3 border-t border-slate-100">
                            <div
                                v-if="lineClickMessage"
                                class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 text-xs text-amber-700"
                            >
                                <Navigation class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                                {{ lineClickMessage }}
                            </div>

                            <div
                                v-if="hasDestination"
                                class="flex items-start gap-2 rounded-lg border border-purple-200 bg-purple-50 px-3 py-2.5"
                            >
                                <div class="mt-1 h-2 w-2 shrink-0 rounded-full bg-purple-500" />
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-medium text-purple-800">Route Reshaping</p>
                                    <p class="text-xs text-purple-600">
                                        Click the blue line to add a detour point. Drag purple markers to reshape. Right-click to remove.
                                    </p>
                                </div>
                                <Button
                                    v-if="waypoints.length"
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    class="h-7 shrink-0 border border-purple-300 text-xs text-purple-700 hover:bg-purple-100"
                                    @click="removeAllWaypoints"
                                >
                                    Clear ({{ waypoints.length }})
                                </Button>
                            </div>

                            <div class="relative">
                                <div
                                    ref="mapEl"
                                    class="h-[500px] w-full overflow-hidden rounded-xl border sm:h-[620px]"
                                />

                                <div class="pointer-events-none absolute inset-x-3 top-3 z-10 sm:left-3 sm:right-auto sm:w-[420px]">
                                    <div class="pointer-events-auto rounded-2xl border bg-background/95 p-3 shadow-lg backdrop-blur">
                                        <div class="mb-2">
                                            <p class="text-sm font-semibold">Destination</p>
                                            <p class="text-xs text-muted-foreground">
                                                Search here or click the map to pin destination.
                                            </p>
                                        </div>

                                        <div class="relative">
                                            <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                            <Input
                                                v-model="destinationQuery"
                                                class="h-10 pl-9 pr-9"
                                                placeholder="Search destination..."
                                            />
                                            <button
                                                v-if="destinationQuery"
                                                type="button"
                                                class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                                @click="destinationQuery = ''; destinationSuggestions = []"
                                            >
                                                <X class="h-3.5 w-3.5" />
                                            </button>
                                        </div>

                                        <div
                                            v-if="destinationSuggestions.length"
                                            class="mt-2 overflow-hidden rounded-xl border bg-background shadow-sm"
                                        >
                                            <button
                                                v-for="item in destinationSuggestions"
                                                :key="item.id"
                                                type="button"
                                                class="flex w-full items-start gap-3 border-b px-3 py-3 text-left last:border-b-0 hover:bg-muted/50"
                                                @click="setDestinationFromSuggestion(item)"
                                            >
                                                <MapPin class="mt-0.5 h-3.5 w-3.5 shrink-0 text-red-500" />
                                                <div class="min-w-0">
                                                    <div class="truncate text-sm font-medium">{{ item.name }}</div>
                                                    <div class="truncate text-xs text-muted-foreground">
                                                        {{ item.full_address }}
                                                    </div>
                                                </div>
                                            </button>
                                        </div>

                                        <div
                                            v-if="hasDestination"
                                            class="mt-2 flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2"
                                        >
                                            <MapPinned class="h-3.5 w-3.5 shrink-0 text-red-600" />
                                            <span class="min-w-0 truncate text-sm font-medium text-red-800">
                                                {{ form.destination_name }}
                                            </span>
                                            <CheckCircle2 class="ml-auto h-3.5 w-3.5 shrink-0 text-red-500" />
                                        </div>

                                        <p v-else class="mt-2 text-xs text-muted-foreground">
                                            Click anywhere on the map to set destination.
                                        </p>

                                        <InputError :message="form.errors.destination_name" />
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card v-if="showAlternatives && alternativeRoutes.length" class="rounded-lg py-6">
                        <CardHeader class="flex items-center justify-between">
                            <div>
                                <CardTitle class="text-base">Alternative Routes</CardTitle>
                            </div>
                        </CardHeader>

                        <CardContent class="pt-6 space-y-2 border-t border-slate-100">
                            <button
                                type="button"
                                :class="[
                                    'w-full rounded-xl border p-3.5 text-left transition-colors',
                                    selectedRouteIndex === 0
                                        ? 'border-primary/50 bg-primary/5'
                                        : 'hover:bg-muted/40',
                                ]"
                                @click="selectAlternativeRoute(0)"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        :class="[
                                            'flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-xs font-bold',
                                            selectedRouteIndex === 0
                                                ? 'bg-primary text-primary-foreground'
                                                : 'bg-muted text-muted-foreground',
                                        ]"
                                    >
                                        1
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="text-sm font-medium">
                                                Route 1
                                                <span class="ml-1 text-xs font-normal text-muted-foreground">
                                                    (Primary)
                                                </span>
                                            </p>
                                            <p class="shrink-0 text-xs text-muted-foreground">
                                                {{ originalPrimaryRoute ? fmtDistance(originalPrimaryRoute.distance) : '—' }}
                                                ·
                                                {{ originalPrimaryRoute ? fmtDuration(originalPrimaryRoute.duration) : '—' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </button>

                            <button
                                v-for="alt in alternativeRoutes"
                                :key="alt.index"
                                type="button"
                                :class="[
                                    'w-full rounded-xl border p-3.5 text-left transition-colors',
                                    selectedRouteIndex === alt.index
                                        ? 'border-primary/50 bg-primary/5'
                                        : 'hover:bg-muted/40',
                                ]"
                                @click="selectAlternativeRoute(alt.index)"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        :class="[
                                            'flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-xs font-bold',
                                            selectedRouteIndex === alt.index
                                                ? 'bg-primary text-primary-foreground'
                                                : 'bg-muted text-muted-foreground',
                                        ]"
                                    >
                                        {{ alt.index + 1 }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="text-sm font-medium">
                                                Route {{ alt.index + 1 }}
                                                <span class="ml-1 text-xs font-normal text-muted-foreground">
                                                    Alternate
                                                </span>
                                            </p>
                                            <p class="shrink-0 text-xs text-muted-foreground">
                                                {{ fmtDistance(alt.distance) }} · {{ fmtDuration(alt.duration) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </button>
                        </CardContent>
                    </Card>
                </div>
                <div class="space-y-4">
                    <Card v-if="waypoints.length" class="py-6">
                        <CardHeader class="flex items-center justify-between">
                            <CardTitle class="font-semibold tracking-wide text-muted-foreground">
                                Detour Points
                            </CardTitle>
                        </CardHeader>

                        <CardContent class="pt-6 space-y-2 border-t border-slate-100">
                            <div
                                v-for="(wp, index) in waypoints"
                                :key="index"
                                class="flex items-center gap-2.5 rounded-lg border border-purple-200 bg-purple-50 px-3 py-2"
                            >
                                <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-purple-600 text-[10px] font-bold text-white">
                                    {{ index + 1 }}
                                </div>
                                <p class="flex-1 font-mono text-xs text-purple-700">
                                    {{ wp.lat.toFixed(4) }}, {{ wp.lng.toFixed(4) }}
                                </p>
                                <button
                                    type="button"
                                    class="text-purple-400 hover:text-red-500"
                                    @click="waypoints.splice(index, 1); renderWaypointMarkers(); redrawRoute()"
                                >
                                    <X class="h-3.5 w-3.5" />
                                </button>
                            </div>
                            <Button
                                type="button"
                                variant="ghost"
                                class="h-8 text-xs text-muted-foreground hover:bg-destructive px-2 hover:text-destructive-foreground cursor-pointer w-full"
                                @click="removeAllWaypoints"
                            >
                                Clear all
                            </Button>
                        </CardContent>
                    </Card>

                    <Card class="py-6">
                        <CardHeader class="flex items-center justify-between">
                            <div>
                                <CardTitle class="font-semibold tracking-wide text-muted-foreground">
                                    Stops Preview
                                </CardTitle>
                            </div>
                            <Badge variant="secondary" class="text-xs">{{ totalVisibleStops }}</Badge>
                        </CardHeader>

                        <CardContent class="overflow-y-auto pt-6 border-t border-slate-100 max-h-[645px] sm:max-h-[765px]">
                            <div
                                v-if="!hasDestination"
                                class="rounded-lg border border-dashed px-4 py-6 text-center text-xs text-muted-foreground"
                            >
                                Set a destination to see the stop sequence.
                            </div>

                            <div v-else class="relative">
                                <div class="absolute top-6 bottom-6 left-[18px] w-px bg-slate-200" />

                                <div class="space-y-1">
                                    <div class="group flex items-start gap-3 rounded-lg p-2 transition-colors hover:bg-muted">
                                        <div class="relative z-10 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-green-600 ring-2 ring-background text-[9px] font-bold text-white">
                                            1
                                        </div>
                                        <div class="min-w-0 pt-0.5">
                                            <p class="text-sm font-medium leading-tight">{{ form.origin_name }}</p>
                                            <p class="text-[10px] uppercase tracking-wide text-green-600">
                                                Origin
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        v-for="(stop, index) in form.stops"
                                        :key="`${stop.stop_name}-${stop.latitude}-${index}`"
                                        :draggable="true"
                                        :class="[
                                            'flex cursor-grab select-none items-start gap-3 rounded-lg p-2 transition-colors',
                                            dragOverIndex === index ? 'bg-blue-50 ring-1 ring-blue-300' : 'hover:bg-muted/40',
                                            draggedStopIndex === index ? 'opacity-50' : '',
                                        ]"
                                        @dragstart="onDragStart(index)"
                                        @dragover="onDragOver($event, index)"
                                        @drop="onDrop(index)"
                                        @dragend="onDragEnd"
                                    >
                                        <div
                                            :class="[
                                                'relative z-10 flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[9px] font-bold text-white ring-2 ring-background',
                                                stop.stop_type === 'landmark' ? 'bg-violet-500' : 'bg-amber-500',
                                            ]"
                                        >
                                            {{ index + 2 }}
                                        </div>

                                        <div class="min-w-0 flex-1 pt-0.5">
                                            <div class="flex items-center gap-2">
                                                <p class="truncate text-sm font-medium leading-tight">
                                                    {{ stop.stop_name}}
                                                </p>
                                                <span
                                                    v-if="stop.stop_type === 'landmark'"
                                                    class="rounded bg-violet-100 px-1.5 py-0.5 text-[10px] font-medium text-violet-700"
                                                >
                                                    Landmark
                                                </span>
                                            </div>
                                            <p class="truncate text-[11px] text-muted-foreground">
                                                {{ stop.address || 'No address' }}
                                            </p>
                                        </div>

                                        <div class="flex shrink-0 items-center gap-1">
                                            <GripVertical class="h-3.5 w-3.5 text-muted-foreground/50" />
                                            <button
                                                type="button"
                                                class="rounded p-0.5 text-muted-foreground/50 hover:text-destructive"
                                                @click="removeStop(index)"
                                            >
                                                <X class="h-3.5 w-3.5" />
                                            </button>
                                        </div>
                                    </div>

                                    <div class="relative flex items-start gap-3 rounded-xl px-3 py-3">
                                        <div class="relative z-10 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-600 ring-2 ring-background text-[9px] font-bold text-white">
                                            {{ form.stops.length + 2 }}
                                        </div>
                                        <div class="min-w-0 pt-0.5">
                                            <p class="text-sm font-medium leading-tight">{{ form.destination_name }}</p>
                                            <p class="text-[10px] uppercase tracking-wide text-red-600">
                                                Destination
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card v-if="hasDestination" class="py-6">
                        <CardHeader class="flex items-center justify-between">
                            <div>
                                <CardTitle class="text-base">Add Stop</CardTitle>
                            </div>
                        </CardHeader>

                        <CardContent class="pt-6 space-y-4 border-t border-slate-100">
                            <div class="space-y-2">
                                <div class="relative">
                                    <Search class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        v-model="stopQuery"
                                        class="h-10 pl-9 pr-9 text-sm"
                                        placeholder="Search route stop..."
                                        :disabled="loadingStopSearch"
                                    />
                                    <button
                                        v-if="stopQuery"
                                        type="button"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                        @click="stopQuery = ''; stopSuggestions = []"
                                    >
                                        <X class="h-3.5 w-3.5" />
                                    </button>
                                </div>

                                <div
                                    v-if="stopSuggestions.length"
                                    class="overflow-hidden rounded-xl border bg-background shadow-sm"
                                >
                                    <button
                                        v-for="item in stopSuggestions"
                                        :key="item.id"
                                        type="button"
                                        class="flex w-full items-start gap-2.5 border-b px-3.5 py-2.5 text-left last:border-b-0 hover:bg-muted/50"
                                        @click="addStopFromSuggestion(item)"
                                    >
                                        <Bus class="mt-0.5 h-3.5 w-3.5 shrink-0 text-amber-500" />
                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-medium">{{ item.name }}</div>
                                            <div class="truncate text-xs text-muted-foreground">
                                                {{ item.full_address }}
                                            </div>
                                        </div>
                                    </button>
                                </div>

                                <p
                                    v-if="!stopSuggestions.length && stopQuery && !loadingStopSearch"
                                    class="text-xs text-amber-600"
                                >
                                    No places found within 500 m of the route.
                                </p>
                            </div>

                            <div class="space-y-2 rounded-xl border bg-muted/30 p-3">
                                <div class="flex items-center gap-1.5 text-xs font-medium">
                                    <Wand2 class="h-3.5 w-3.5 text-muted-foreground" />
                                    Auto-Generate Stops
                                </div>

                                <div class="flex items-center gap-2">
                                    <Input
                                        v-model.number="autoGenerateInterval"
                                        type="number"
                                        min="1"
                                        max="50"
                                        class="h-8 w-20 text-sm"
                                    />
                                    <span class="text-xs text-muted-foreground">km apart</span>

                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="secondary"
                                        class="ml-auto h-8 text-xs"
                                        :disabled="loadingAutoGenerate || routeCoordinates.length < 2"
                                        @click="autoGenerateStops"
                                    >
                                        {{ loadingAutoGenerate ? 'Generating…' : 'Generate' }}
                                    </Button>
                                </div>

                                <p class="text-[11px] text-muted-foreground">
                                    Replaces all current stops.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
