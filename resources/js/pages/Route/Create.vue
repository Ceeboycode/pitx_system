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
        <div class="sticky top-0 z-20 border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/80">
            <div class="flex h-14 items-center justify-between gap-4 px-4 sm:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                        <RouteIcon class="h-4 w-4" />
                    </div>

                    <div class="min-w-0">
                        <div class="flex items-center gap-2 text-sm font-semibold">
                            <span class="truncate">Create Route</span>
                            <span
                                v-if="form.destination_name"
                                class="hidden shrink-0 text-muted-foreground sm:inline"
                            >
                                → {{ form.destination_name }}
                            </span>
                        </div>
                        <p class="truncate text-xs text-muted-foreground">
                            Fixed origin:
                            <span class="font-medium text-foreground">{{ form.origin_name }}</span>
                        </p>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <div class="hidden items-center gap-1.5 sm:flex">
                        <span
                            class="h-2 w-2 rounded-full"
                            :class="{
                                'bg-muted-foreground': routeHealthStatus === 'idle',
                                'animate-pulse bg-amber-400': routeHealthStatus === 'loading',
                                'bg-green-500': routeHealthStatus === 'ready',
                            }"
                        />
                        <span class="text-xs text-muted-foreground">{{ routeHealthText }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-4 sm:p-6">
            <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="flex items-center gap-3 rounded-xl border bg-card px-4 py-3 shadow-sm">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                        <MapPinned class="h-4 w-4" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                            Destination
                        </p>
                        <p class="truncate text-sm font-semibold">
                            {{ form.destination_name || '—' }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 rounded-xl border bg-card px-4 py-3 shadow-sm">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                        <Milestone class="h-4 w-4" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                            Distance
                        </p>
                        <p class="text-sm font-semibold">
                            {{ form.distance_meters ? fmtDistance(form.distance_meters) : '—' }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 rounded-xl border bg-card px-4 py-3 shadow-sm">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                        <Clock3 class="h-4 w-4" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                            Duration
                        </p>
                        <p class="text-sm font-semibold">
                            {{ form.duration_seconds ? fmtDuration(form.duration_seconds) : '—' }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 rounded-xl border bg-card px-4 py-3 shadow-sm">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-green-50 text-green-600">
                        <Bus class="h-4 w-4" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                            Total Stops
                        </p>
                        <p class="text-sm font-semibold">{{ totalVisibleStops }}</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-[1fr_380px]">
                <div class="space-y-5">
                    <Card class="overflow-hidden rounded-2xl">
                        <CardHeader class="pb-3">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <CardTitle class="text-base">Map Workspace</CardTitle>
                                    <CardDescription class="text-xs">
                                        Search on the map, pin destination, and shape the route.
                                    </CardDescription>
                                </div>

                                <Badge
                                    :variant="routeHealthStatus === 'ready' ? 'default' : 'secondary'"
                                    class="shrink-0 text-xs"
                                >
                                    <span
                                        class="mr-1.5 h-1.5 w-1.5 rounded-full"
                                        :class="{
                                            'bg-muted-foreground': routeHealthStatus === 'idle',
                                            'bg-amber-400': routeHealthStatus === 'loading',
                                            'bg-green-400': routeHealthStatus === 'ready',
                                        }"
                                    />
                                    {{ routeHealthStatus === 'ready' ? 'Route ready' : routeHealthStatus === 'loading' ? 'Computing…' : 'Awaiting destination' }}
                                </Badge>
                            </div>
                        </CardHeader>

                        <CardContent class="space-y-3 pt-0">
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

                            <div class="flex flex-wrap gap-x-5 gap-y-1.5 text-xs text-muted-foreground">
                                <span class="flex items-center gap-1.5">
                                    <span class="inline-block h-2 w-2 rounded-full bg-green-600" />
                                    Origin
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <span class="inline-block h-2 w-2 rounded-full bg-red-600" />
                                    Destination
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <span class="inline-block h-2 w-2 rounded-full bg-yellow-500" />
                                    Bus stops
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <span class="inline-block h-2 w-2 rounded-full bg-purple-600" />
                                    Detour pts
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <span class="inline-block w-5 border-t-2 border-dashed border-slate-400" />
                                    Alternatives
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card v-if="showAlternatives && alternativeRoutes.length" class="rounded-2xl">
                        <CardHeader class="pb-3">
                            <CardTitle class="text-base">Alternative Routes</CardTitle>
                            <CardDescription class="text-xs">
                                Click a dashed line on the map or choose below.
                            </CardDescription>
                        </CardHeader>

                        <CardContent class="space-y-2 pt-0">
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

                    <div v-if="hasDestination" class="grid gap-5 sm:grid-cols-2">
                        <Card class="rounded-2xl">
                            <CardHeader class="pb-3">
                                <div>
                                    <CardTitle class="text-base">Add Stop</CardTitle>
                                    <CardDescription class="text-xs">
                                        Search stops within 500 m of the active route.
                                    </CardDescription>
                                </div>
                            </CardHeader>

                            <CardContent class="space-y-2 pt-0">
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
                            </CardContent>
                        </Card>

                        <Card class="rounded-2xl">
                            <CardHeader class="pb-3">
                                <CardTitle class="text-base">Auto Tools</CardTitle>
                                <CardDescription class="text-xs">
                                    Generate or suggest stops automatically.
                                </CardDescription>
                            </CardHeader>

                            <CardContent class="space-y-4 pt-0">
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

                                <div class="space-y-2 rounded-xl border bg-muted/30 p-3">
                                    <div class="flex items-center gap-1.5 text-xs font-medium">
                                        <Sparkles class="h-3.5 w-3.5 text-muted-foreground" />
                                        Landmark Suggestions
                                    </div>

                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="secondary"
                                        class="h-8 w-full text-xs"
                                        :disabled="loadingLandmarks"
                                        @click="suggestLandmarks"
                                    >
                                        {{ loadingLandmarks ? 'Searching…' : 'Suggest Landmarks' }}
                                    </Button>

                                    <div
                                        v-if="showLandmarks && landmarkSuggestions.length"
                                        class="max-h-48 overflow-y-auto rounded-xl border bg-background"
                                    >
                                        <div
                                            v-for="item in landmarkSuggestions"
                                            :key="item.id"
                                            class="flex items-center gap-2 border-b px-3 py-2.5 last:border-b-0"
                                        >
                                            <div class="min-w-0 flex-1">
                                                <div class="truncate text-xs font-medium">{{ item.name }}</div>
                                                <div class="truncate text-[11px] text-muted-foreground">
                                                    {{ item.full_address }}
                                                </div>
                                            </div>
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="outline"
                                                class="h-6 shrink-0 px-2 text-xs"
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
                                        No landmarks found.
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <div class="space-y-5">
                    <Card class="rounded-2xl">
                        <CardHeader class="pb-2">
                            <CardTitle class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                Route Summary
                            </CardTitle>
                        </CardHeader>

                        <CardContent class="space-y-0 pt-0">
                            <div class="flex items-center gap-3 border-b py-3">
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-green-600 text-[10px] font-bold text-white">
                                    A
                                </span>
                                <div class="min-w-0">
                                    <p class="text-[10px] uppercase tracking-wide text-muted-foreground">Origin</p>
                                    <p class="truncate text-sm font-medium">{{ form.origin_name }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 border-b py-3">
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-600 text-[10px] font-bold text-white">
                                    B
                                </span>
                                <div class="min-w-0">
                                    <p class="text-[10px] uppercase tracking-wide text-muted-foreground">Destination</p>
                                    <p class="truncate text-sm font-medium">
                                        {{ form.destination_name || '—' }}
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-x-3 gap-y-0">
                                <div class="border-b border-r py-3 pr-3">
                                    <p class="text-[10px] uppercase tracking-wide text-muted-foreground">Distance</p>
                                    <p class="text-sm font-semibold">
                                        {{ form.distance_meters ? fmtDistance(form.distance_meters) : '—' }}
                                    </p>
                                </div>
                                <div class="border-b py-3 pl-3">
                                    <p class="text-[10px] uppercase tracking-wide text-muted-foreground">Duration</p>
                                    <p class="text-sm font-semibold">
                                        {{ form.duration_seconds ? fmtDuration(form.duration_seconds) : '—' }}
                                    </p>
                                </div>
                                <div class="border-b border-r py-3 pr-3">
                                    <p class="text-[10px] uppercase tracking-wide text-muted-foreground">Stops</p>
                                    <p class="text-sm font-semibold">{{ totalBusStops }}</p>
                                </div>
                                <div class="border-b py-3 pl-3">
                                    <p class="text-[10px] uppercase tracking-wide text-muted-foreground">Detours</p>
                                    <p class="text-sm font-semibold">{{ waypoints.length }}</p>
                                </div>
                                <div v-if="alternativeRoutes.length" class="col-span-2 py-3">
                                    <p class="text-[10px] uppercase tracking-wide text-muted-foreground">Selected Route</p>
                                    <p class="text-sm font-semibold">
                                        {{ selectedRouteIndex + 1 }} / {{ alternativeRoutes.length + 1 }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card v-if="waypoints.length" class="rounded-2xl">
                        <CardHeader class="pb-2">
                            <div class="flex items-center justify-between gap-2">
                                <CardTitle class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                    Detour Points
                                </CardTitle>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    class="h-6 text-xs text-muted-foreground hover:text-destructive"
                                    @click="removeAllWaypoints"
                                >
                                    Clear all
                                </Button>
                            </div>
                        </CardHeader>

                        <CardContent class="space-y-2 pt-0">
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
                        </CardContent>
                    </Card>

                    <Card class="rounded-2xl">
                        <CardHeader class="pb-2">
                            <div class="flex items-center justify-between gap-2">
                                <div>
                                    <CardTitle class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                        Stops Preview
                                    </CardTitle>
                                    <CardDescription class="text-xs">
                                        Drag intermediate stops to reorder.
                                    </CardDescription>
                                </div>
                                <Badge variant="secondary" class="text-xs">{{ totalVisibleStops }}</Badge>
                            </div>
                        </CardHeader>

                        <CardContent class="pt-0">
                            <div
                                v-if="!hasDestination"
                                class="rounded-xl border border-dashed px-4 py-6 text-center text-xs text-muted-foreground"
                            >
                                Set a destination to see the stop sequence.
                            </div>

                            <div v-else class="relative">
                                <div class="absolute bottom-6 left-[18px] top-6 w-px bg-border" />

                                <div class="space-y-1">
                                    <div class="relative flex items-start gap-3 rounded-xl px-3 py-3">
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
                                            'relative flex cursor-grab select-none items-start gap-3 rounded-xl px-3 py-3 transition-colors',
                                            dragOverIndex === index ? 'bg-blue-50 ring-1 ring-blue-300' : 'hover:bg-muted/40',
                                            draggedStopIndex === index ? 'opacity-50' : '',
                                        ]"
                                        @dragstart="onDragStart(index)"
                                        @dragover="onDragOver($event, index)"
                                        @drop="onDrop(index)"
                                        @dragend="onDragEnd"
                                    >
                                        <div class="relative z-10 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-amber-500 ring-2 ring-background text-[9px] font-bold text-white">
                                            {{ index + 2 }}
                                        </div>

                                        <div class="min-w-0 flex-1 pt-0.5">
                                            <p class="truncate text-sm font-medium leading-tight">{{ stop.stop_name }}</p>
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

                    <div class="rounded-2xl border bg-card p-4 shadow-sm">
                        <div class="space-y-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold">Route Details</p>
                                    <p class="text-xs text-muted-foreground">
                                        Enter the route name, select the gate, then save.
                                    </p>
                                </div>

                                <Button
                                    v-if="hasDestination"
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="h-8 text-muted-foreground hover:text-destructive"
                                    @click="clearDestination"
                                >
                                    <X class="mr-1.5 h-3.5 w-3.5" />
                                    Clear
                                </Button>
                            </div>

                            <div class="space-y-1.5">
                                <Label for="route_name_sidebar" class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                    Route Name
                                </Label>
                                <Input
                                    id="route_name_sidebar"
                                    :model-value="form.route_name"
                                    placeholder="Enter route name"
                                    class="h-10"
                                    @update:model-value="onRouteNameInput"
                                />
                                <div class="flex items-center justify-between gap-3">
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

                            <div class="space-y-1.5">
                                <Label for="gate_id_sidebar" class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                    <DoorOpen class="mr-1 inline h-3.5 w-3.5" />
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

                            <div class="grid gap-2 sm:grid-cols-2">
                                <Button
                                    as-child
                                    type="button"
                                    variant="outline"
                                    class="w-full"
                                >
                                    <Link :href="index().url">
                                        <ArrowLeft class="mr-2 h-4 w-4" />
                                        Back to Routes
                                    </Link>
                                </Button>

                                <Button
                                    class="w-full"
                                    :disabled="form.processing || !routeReady"
                                    @click="submit"
                                >
                                    <Save class="mr-2 h-4 w-4" />
                                    {{ form.processing ? 'Saving Route…' : 'Save Route' }}
                                </Button>
                            </div>

                            <p
                                v-if="!routeReady"
                                class="text-center text-[11px] text-muted-foreground"
                            >
                                {{ !hasDestination ? 'Select a destination first.' : 'Waiting for route to compute.' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
