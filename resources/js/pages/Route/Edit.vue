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

import {
    ArrowLeft,
    Bus,
    Clock3,
    DoorOpen,
    GripVertical,
    MapPin,
    Navigation,
    Route as RouteIcon,
    Ruler,
    Save,
    Search,
    Wand2,
    X,
} from 'lucide-vue-next';

import {
    edit,
    index,
    update,
} from '@/actions/App/Http/Controllers/RouteController';
import type { BreadcrumbItem } from '@/types';

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
    geometry: GeoJSON.LineString;
    distance: number;
    duration: number;
    coordinates: [number, number][];
};

type Waypoint = {
    lng: number;
    lat: number;
};

type RouteStop = {
    id: number;
    stop_name: string;
    stop_type: 'origin' | 'stop' | 'destination' | 'landmark';
    address: string | null;
    latitude: number;
    longitude: number;
    mapbox_feature_id: string | null;
    stop_order: number;
};

type RouteModel = {
    id: number;
    route_name: string;
    gate_id: number | null;
    gate: Gate | null;
    origin_name: string;
    origin_lat: number;
    origin_lng: number;
    destination_name: string;
    destination_lat: number;
    destination_lng: number;
    distance_meters: number | null;
    duration_seconds: number | null;
    route_geometry: string | null;
    stops: RouteStop[];
};

/* ─────────────────────────────────────────────────────────────────────────────
   Props
───────────────────────────────────────────────────────────────────────────── */

const props = defineProps<{
    route: RouteModel;
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
   Breadcrumbs
───────────────────────────────────────────────────────────────────────────── */

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Routes', href: index().url },
    { title: props.route.route_name, href: edit(props.route.id).url },
];

/* ─────────────────────────────────────────────────────────────────────────────
   Refs
───────────────────────────────────────────────────────────────────────────── */

const mapEl = ref<HTMLElement | null>(null);
const map = ref<mapboxgl.Map | null>(null);

const destinationQuery = ref(props.route.destination_name || '');
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

const allRouteOptions = ref<AlternativeRoute[]>([]);
const selectedRouteIndex = ref(0);
const showAlternatives = ref(false);

const waypoints = ref<Waypoint[]>([]);

/* ─────────────────────────────────────────────────────────────────────────────
   Existing stops
───────────────────────────────────────────────────────────────────────────── */

const existingIntermediateStops: StopItem[] = props.route.stops
    .filter((stop) => stop.stop_type !== 'origin' && stop.stop_type !== 'destination')
    .sort((a, b) => a.stop_order - b.stop_order)
    .map((stop) => ({
        stop_name: stop.stop_name,
        stop_type: stop.stop_type,
        address: stop.address,
        latitude: Number(stop.latitude),
        longitude: Number(stop.longitude),
        mapbox_feature_id: stop.mapbox_feature_id,
        stop_order: stop.stop_order,
    }));

/* ─────────────────────────────────────────────────────────────────────────────
   Origin
───────────────────────────────────────────────────────────────────────────── */

const origin = {
    name: props.mapConfig.pitx.name,
    lat: Number(props.mapConfig.pitx.lat),
    lng: Number(props.mapConfig.pitx.lng),
};

const form = useForm({
    route_name: props.route.route_name,
    gate_id: props.route.gate_id?.toString() ?? '',
    origin_name: origin.name,
    origin_lat: origin.lat,
    origin_lng: origin.lng,
    destination_name: props.route.destination_name,
    destination_lat: Number(props.route.destination_lat) as number | null,
    destination_lng: Number(props.route.destination_lng) as number | null,
    distance_meters: props.route.distance_meters as number | null,
    duration_seconds: props.route.duration_seconds as number | null,
    route_geometry: props.route.route_geometry as string | null,
    stops: existingIntermediateStops,
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

const totalVisibleStops = computed(() => {
    if (!hasDestination.value) return 1;
    return form.stops.length + 2;
});

const routeReady = computed(() => {
    return hasDestination.value && !!form.route_geometry;
});

const selectedGate = computed(() =>
    props.gates.find((g) => String(g.id) === form.gate_id) ?? null,
);

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

function emptyFeatureCollection(): GeoJSON.FeatureCollection {
    return {
        type: 'FeatureCollection',
        features: [],
    };
}

function lineFeature(
    geometry: GeoJSON.LineString,
): GeoJSON.Feature<GeoJSON.LineString> {
    return {
        type: 'Feature',
        properties: {},
        geometry,
    };
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

function isDuplicateStopCandidate(lng: number, lat: number, name: string) {
    const normalizedName = name.toLowerCase().trim();

    return form.stops.some((stop) => {
        const sameName = stop.stop_name.toLowerCase().trim() === normalizedName;
        const nearSameSpot =
            haversine([stop.longitude, stop.latitude], [lng, lat]) < 80;

        return sameName || nearSameSpot;
    });
}

function syncAlternativeRouteLayers() {
    if (!map.value) return;

    for (let i = 1; i <= 2; i++) {
        const src = map.value.getSource(`alt-route-${i}`) as mapboxgl.GeoJSONSource;
        src?.setData(emptyFeatureCollection());
    }

    const remaining = allRouteOptions.value.filter(
        (_, index) => index !== selectedRouteIndex.value,
    );

    remaining.slice(0, 2).forEach((route, idx) => {
        const src = map.value?.getSource(`alt-route-${idx + 1}`) as mapboxgl.GeoJSONSource;

        src?.setData({
            type: 'FeatureCollection',
            features: [lineFeature(route.geometry)],
        });
    });

    showAlternatives.value = allRouteOptions.value.length > 1;
}

function applySelectedRoute(index: number) {
    const selected = allRouteOptions.value[index];
    if (!selected || !map.value) return;

    selectedRouteIndex.value = index;
    form.distance_meters = Math.round(selected.distance);
    form.duration_seconds = Math.round(selected.duration);
    form.route_geometry = JSON.stringify(selected.geometry);
    routeCoordinates.value = [...selected.coordinates];

    const primarySrc = map.value.getSource('route-line') as mapboxgl.GeoJSONSource;
    primarySrc?.setData({
        type: 'FeatureCollection',
        features: [lineFeature(selected.geometry)],
    });

    syncAlternativeRouteLayers();
    renderStopMarkers();
    renderWaypointMarkers();
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
                data: emptyFeatureCollection(),
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
                selectAlternativeRouteByLayer(i);
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
            data: emptyFeatureCollection(),
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

        if (form.destination_lat !== null && form.destination_lng !== null) {
            destinationMarker.value = new mapboxgl.Marker({
                color: '#dc2626',
                draggable: true,
            })
                .setLngLat([form.destination_lng, form.destination_lat])
                .setPopup(new mapboxgl.Popup().setText(form.destination_name))
                .addTo(map.value!);

            destinationMarker.value.on('dragend', async () => {
                const ll = destinationMarker.value!.getLngLat();

                form.destination_lat = ll.lat;
                form.destination_lng = ll.lng;

                const place = await reversePlace(ll.lng, ll.lat);

                if (place) {
                    form.destination_name =
                        place.text || place.place_name || form.destination_name;
                    destinationQuery.value =
                        place.place_name || form.destination_name;
                }

                form.stops = [];
                waypoints.value = [];
                landmarkSuggestions.value = [];
                showLandmarks.value = false;

                clearStopMarkers();
                clearWaypointMarkers();
                clearAlternativeRouteLayers();
                clearRouteLine();

                await redrawRoute();
            });
        }

        if (props.route.route_geometry) {
            try {
                const geometry = JSON.parse(props.route.route_geometry) as GeoJSON.LineString;

                allRouteOptions.value = [
                    {
                        index: 0,
                        geometry,
                        distance: props.route.distance_meters ?? 0,
                        duration: props.route.duration_seconds ?? 0,
                        coordinates: geometry.coordinates as [number, number][],
                    },
                ];

                applySelectedRoute(0);
                fitMap();
            } catch {
                // ignore invalid geometry
            }
        } else if (hasDestination.value) {
            redrawRoute();
        } else {
            renderStopMarkers();
        }
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
const MAX_LANDMARK_DISTANCE_M = 900;

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
    const destinationChanged =
        form.destination_lng !== item.longitude ||
        form.destination_lat !== item.latitude;

    if (destinationChanged) {
        form.stops = [];
        waypoints.value = [];
        landmarkSuggestions.value = [];
        showLandmarks.value = false;

        clearStopMarkers();
        clearWaypointMarkers();
        clearAlternativeRouteLayers();
        clearRouteLine();
    }

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
            form.destination_name = place.text || place.place_name || form.destination_name;
            destinationQuery.value = place.place_name || form.destination_name;
        }

        form.stops = [];
        waypoints.value = [];
        landmarkSuggestions.value = [];
        showLandmarks.value = false;

        clearStopMarkers();
        clearWaypointMarkers();
        clearAlternativeRouteLayers();
        clearRouteLine();

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
    waypointMarkers.value.forEach((marker) => marker.remove());
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
    applySelectedRoute(index);
    lineClickMessage.value = `Switched to Route option ${index + 1}.`;
}

function selectAlternativeRouteByLayer(layerAltIndex: number) {
    const remaining = allRouteOptions.value
        .map((route, index) => ({ route, index }))
        .filter((item) => item.index !== selectedRouteIndex.value);

    const target = remaining[layerAltIndex - 1];
    if (!target) return;

    applySelectedRoute(target.index);
    lineClickMessage.value = `Switched to Route option ${target.index + 1}.`;
}

function clearAlternativeRouteLayers() {
    allRouteOptions.value = [];
    showAlternatives.value = false;
    selectedRouteIndex.value = 0;

    if (!map.value) return;

    for (let i = 1; i <= 2; i++) {
        const src = map.value.getSource(`alt-route-${i}`) as mapboxgl.GeoJSONSource;
        src?.setData(emptyFeatureCollection());
    }
}

/* ─────────────────────────────────────────────────────────────────────────────
   Stops
───────────────────────────────────────────────────────────────────────────── */

async function addStopFromSuggestion(
    item: SearchSuggestion,
    stopType: StopItem['stop_type'] = 'stop',
) {
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
        stop_type: stopType,
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
    if (!hasDestination.value || routeCoordinates.value.length < 2) return;

    loadingLandmarks.value = true;
    showLandmarks.value = true;
    landmarkSuggestions.value = [];

    try {
        const routeDistanceKm = Math.max(1, (form.distance_meters ?? 0) / 1000);

        const intervalKm =
            routeDistanceKm <= 8 ? 2 : routeDistanceKm <= 20 ? 4 : 6;

        const sampled = sampleRouteAtIntervals(routeCoordinates.value, intervalKm);

        const searchPoints: [number, number][] = [
            [origin.lng, origin.lat],
            ...sampled.slice(0, 5),
            [form.destination_lng!, form.destination_lat!],
        ];

        const seenIds = new Set<string>();
        const results: SearchSuggestion[] = [];

        for (const [lng, lat] of searchPoints) {
            const url = new URL(
                'https://api.mapbox.com/geocoding/v5/mapbox.places/terminal,bus stop,station,market,hospital,mall,plaza,school.json',
            );

            url.searchParams.set('access_token', mapboxgl.accessToken);
            url.searchParams.set('proximity', `${lng},${lat}`);
            url.searchParams.set('limit', '6');
            url.searchParams.set('country', 'ph');
            url.searchParams.set('language', 'en');
            url.searchParams.set('types', 'poi');

            const res = await fetch(url.toString());
            const data = await res.json();

            for (const f of data.features ?? []) {
                const candidate: SearchSuggestion = {
                    id: f.id,
                    name: f.text || f.place_name,
                    full_address: f.place_name,
                    longitude: f.center[0],
                    latitude: f.center[1],
                };

                if (seenIds.has(candidate.id)) continue;

                const snapped = snapToRoute(candidate.longitude, candidate.latitude);
                const distanceFromRoute = haversine(
                    snapped,
                    [candidate.longitude, candidate.latitude],
                );

                if (distanceFromRoute > MAX_LANDMARK_DISTANCE_M) continue;

                if (
                    isDuplicateStopCandidate(
                        candidate.longitude,
                        candidate.latitude,
                        candidate.name,
                    )
                ) {
                    continue;
                }

                if (
                    candidate.name.toLowerCase().trim() ===
                    form.destination_name.toLowerCase().trim()
                ) {
                    continue;
                }

                seenIds.add(candidate.id);
                results.push(candidate);
            }
        }

        landmarkSuggestions.value = results.slice(0, 10);

        if (!landmarkSuggestions.value.length) {
            lineClickMessage.value =
                'No nearby landmark suggestions were found for this route.';
        } else {
            lineClickMessage.value =
                'Landmark suggestions are ready. Add the ones you want as stops.';
        }
    } finally {
        loadingLandmarks.value = false;
    }
}

async function addLandmarkAsStop(item: SearchSuggestion) {
    await addStopFromSuggestion(item, 'landmark');
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
        const markerColor = stop.stop_type === 'landmark' ? '#8b5cf6' : '#f59e0b';

        const marker = new mapboxgl.Marker({
            color: markerColor,
            draggable: true,
        })
            .setLngLat([stop.longitude, stop.latitude])
            .setPopup(
                new mapboxgl.Popup().setText(
                    `${index + 2}. ${stop.stop_name} (${stop.stop_type})`,
                ),
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
    stopMarkers.value.forEach((marker) => marker.remove());
    stopMarkers.value = [];
}

function clearRouteLine() {
    const src = map.value?.getSource('route-line') as
        | mapboxgl.GeoJSONSource
        | undefined;

    src?.setData(emptyFeatureCollection());
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
        ...waypoints.value.map((waypoint) => `${waypoint.lng},${waypoint.lat}`),
        ...form.stops.map((stop) => `${stop.longitude},${stop.latitude}`),
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
        allRouteOptions.value = [];
        selectedRouteIndex.value = 0;
        showAlternatives.value = false;

        clearRouteLine();
        clearAlternativeRouteLayers();

        lineClickMessage.value =
            'No route could be generated for the current points. Try changing the destination or stops.';

        return;
    }

    allRouteOptions.value = data.routes.slice(0, 3).map((route: any, index: number) => ({
        index,
        geometry: route.geometry as GeoJSON.LineString,
        distance: route.distance,
        duration: route.duration,
        coordinates: route.geometry.coordinates as [number, number][],
    }));

    applySelectedRoute(0);
    fitMap();
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

function removeStop(index: number) {
    form.stops.splice(index, 1);
    renderStopMarkers();
    redrawRoute();
}

/* ─────────────────────────────────────────────────────────────────────────────
   Misc
───────────────────────────────────────────────────────────────────────────── */

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

    allRouteOptions.value = [];
    showAlternatives.value = false;
    selectedRouteIndex.value = 0;

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
    form.put(update(props.route.id).url);
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
    <Head :title="`Edit Route — ${route.route_name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">

            <!-- Header card -->
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
                                    {{ route.route_name }}
                                </h1>
                                <div class="ml-2 flex flex-1 items-center">
                                    <hr class="h-px w-full border border-rose-500" />
                                    <div class="border-7 border-rose-500 rounded-xs">
                                        <div class="border-3 border-white rounded-xs"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-between">
                                <div class="flex flex-wrap items-center gap-2">
                                    <Badge class="border-0 bg-muted font-mono text-muted-foreground">
                                        {{ form.origin_name }} → {{ form.destination_name || '…' }}
                                    </Badge>
                                    <Badge v-if="selectedGate" class="border-0 bg-slate-100 text-slate-600">
                                        {{ selectedGate.gate_name }}
                                    </Badge>
                                    <div class="flex items-center gap-1.5">
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
                        <div class="py-2">
                            <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">Stops</span>
                            <span class="rounded bg-muted px-2 py-0.5 font-mono text-sm font-semibold tabular-nums">{{ totalVisibleStops }}</span>
                        </div>
                        <div class="py-2">
                            <!-- <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">Distance</span> -->
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
                            <div class="items-center flex">
                                <div class="h-full mr-4">
                                    <DoorOpen class="h-4 w-4 inline-block text-primary" />
                                </div>
                                <span class="text-sm ">{{ selectedGate?.gate_name ?? '—' }}</span>
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

            <!-- Top row: map | stops -->
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

                                        <p
                                            v-if="!hasDestination"
                                            class="mt-2 text-xs text-muted-foreground"
                                        >
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
                                    <span class="inline-block h-2 w-2 rounded-full bg-violet-500" />
                                    Landmarks
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

                    <Card
                        v-if="showAlternatives && allRouteOptions.length > 1"
                        class="py-6"
                    >
                        <CardHeader class="flex items-center justify-between">
                            <div>
                                <CardTitle class="text-base">Alternative Routes</CardTitle>
                            </div>
                        </CardHeader>

                        <CardContent class="pt-6 space-y-2 border-t border-slate-100">
                            <button
                                v-for="routeOption in allRouteOptions"
                                :key="routeOption.index"
                                type="button"
                                :class="[
                                    'w-full rounded-xl border p-3.5 text-left transition-colors',
                                    selectedRouteIndex === routeOption.index
                                        ? 'border-primary/50 bg-primary/5'
                                        : 'hover:bg-muted/40',
                                ]"
                                @click="selectAlternativeRoute(routeOption.index)"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        :class="[
                                            'flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-xs font-bold',
                                            selectedRouteIndex === routeOption.index
                                                ? 'bg-primary text-primary-foreground'
                                                : 'bg-muted text-muted-foreground',
                                        ]"
                                    >
                                        {{ routeOption.index + 1 }}
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="text-sm font-medium">
                                                Route {{ routeOption.index + 1 }}
                                                <span
                                                    v-if="selectedRouteIndex === routeOption.index"
                                                    class="ml-1 text-xs font-normal text-muted-foreground"
                                                >
                                                    Current
                                                </span>
                                            </p>
                                            <p class="shrink-0 text-xs text-muted-foreground">
                                                {{ fmtDistance(routeOption.distance) }} ·
                                                {{ fmtDuration(routeOption.duration) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </button>
                            <Button
                                v-if="hasDestination"
                                type="button"
                                variant="outline"
                                class="h-8 text-muted-foreground hover:text-destructive-foreground hover:bg-destructive w-full"
                                @click="clearDestination"
                            >
                                Clear
                            </Button>
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
                                                    {{ stop.stop_name }}
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
                                <!-- <CardDescription class="text-xs">
                                    Search stops within 500 m of the active route, or auto-generate by interval.
                                </CardDescription> -->
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
