<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { useAppearance } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';

import SearchInput from '@/components/SearchInput.vue';
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
    RiAiGenerate,
    RiArrowLeftLine,
    RiBus2Line,
    RiCheckboxCircleLine,
    RiCloseLine,
    RiDraggable,
    RiMapPin2Line,
    RiSearchLine,
} from 'vue-remix-icons';

import {
    edit,
    index,
    update,
} from '@/actions/App/Http/Controllers/RouteController';
import type { BreadcrumbItem } from '@/types';

import mapboxgl from 'mapbox-gl';
import 'mapbox-gl/dist/mapbox-gl.css';

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

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Routes', href: index().url },
    { title: props.route.route_name, href: edit(props.route.id).url },
];

const mapEl = ref<HTMLElement | null>(null);
const map = ref<mapboxgl.Map | null>(null);
const { resolvedAppearance } = useAppearance();

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

const originalPrimaryRoute = computed(() => allRouteOptions.value[0] ?? null);
const alternativeRoutes = computed(() => allRouteOptions.value.slice(1));

const waypoints = ref<Waypoint[]>([]);

const existingIntermediateStops: StopItem[] = props.route.stops
    .filter(
        (stop) =>
            stop.stop_type !== 'origin' && stop.stop_type !== 'destination',
    )
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

const selectedGate = computed(
    () => props.gates.find((g) => String(g.id) === form.gate_id) ?? null,
);

const routeHealthText = computed(() => {
    if (!hasDestination.value)
        return 'Choose a destination to start building the route.';
    if (!routeCoordinates.value.length) return 'Waiting for route path...';
    return 'Route is ready. You can add stops or detour points.';
});

const routeHealthStatus = computed<'idle' | 'loading' | 'ready'>(() => {
    if (!hasDestination.value) return 'idle';
    if (!routeCoordinates.value.length) return 'loading';
    return 'ready';
});

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
        const src = map.value.getSource(
            `alt-route-${i}`,
        ) as mapboxgl.GeoJSONSource;
        src?.setData(emptyFeatureCollection());
    }

    const remaining = allRouteOptions.value.filter(
        (_, index) => index !== selectedRouteIndex.value,
    );

    remaining.slice(0, 2).forEach((route, idx) => {
        const src = map.value?.getSource(
            `alt-route-${idx + 1}`,
        ) as mapboxgl.GeoJSONSource;

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

    const primarySrc = map.value.getSource(
        'route-line',
    ) as mapboxgl.GeoJSONSource;
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

    form.stops.forEach((stop) =>
        bounds.extend([stop.longitude, stop.latitude]),
    );
    waypoints.value.forEach((waypoint) =>
        bounds.extend([waypoint.lng, waypoint.lat]),
    );

    map.value.fitBounds(bounds, {
        padding: 60,
        maxZoom: 14,
    });
}

function initMap() {
    if (!mapEl.value) return;

    map.value = new mapboxgl.Map({
        container: mapEl.value,
        style: 'mapbox://styles/mapbox/standard',
        config: {
            basemap: {
                lightPreset:
                    resolvedAppearance.value === 'dark' ? 'night' : 'day',
            },
        },
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
                const geometry = JSON.parse(
                    props.route.route_geometry,
                ) as GeoJSON.LineString;

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
            } catch {}
        } else if (hasDestination.value) {
            redrawRoute();
        } else {
            renderStopMarkers();
        }
    });
}

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

async function searchPlacesAlongRoute(
    query: string,
): Promise<SearchSuggestion[]> {
    if (!query.trim() || routeCoordinates.value.length < 2) return [];

    const mid =
        routeCoordinates.value[Math.floor(routeCoordinates.value.length / 2)];

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

    const candidates: SearchSuggestion[] = (data.features ?? []).map(
        (f: any) => ({
            id: f.id,
            name: f.text || f.place_name,
            full_address: f.place_name,
            longitude: f.center[0],
            latitude: f.center[1],
        }),
    );

    const existingNames = new Set(
        form.stops.map((stop) => stop.stop_name.toLowerCase().trim()),
    );

    return candidates
        .filter((candidate) => {
            const snapped = snapToRoute(
                candidate.longitude,
                candidate.latitude,
            );

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
            form.destination_name =
                place.text || place.place_name || form.destination_name;
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
        const src = map.value.getSource(
            `alt-route-${i}`,
        ) as mapboxgl.GeoJSONSource;
        src?.setData(emptyFeatureCollection());
    }
}

async function addStopFromSuggestion(
    item: SearchSuggestion,
    stopType: StopItem['stop_type'] = 'stop',
) {
    const alreadyExists = form.stops.some(
        (stop) =>
            stop.stop_name.toLowerCase().trim() ===
            item.name.toLowerCase().trim(),
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
            (stop) =>
                haversine(
                    [stop.longitude, stop.latitude],
                    [finalLng, finalLat],
                ) < 50,
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

        const sampled = sampleRouteAtIntervals(
            routeCoordinates.value,
            intervalKm,
        );

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

                const snapped = snapToRoute(
                    candidate.longitude,
                    candidate.latitude,
                );
                const distanceFromRoute = haversine(snapped, [
                    candidate.longitude,
                    candidate.latitude,
                ]);

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

function renderStopMarkers() {
    clearStopMarkers();

    form.stops.forEach((stop, index) => {
        const markerColor =
            stop.stop_type === 'landmark' ? '#8b5cf6' : '#f59e0b';

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
    stopMarkers.value.forEach((marker) => marker.remove());
    stopMarkers.value = [];
}

function clearRouteLine() {
    const src = map.value?.getSource('route-line') as
        | mapboxgl.GeoJSONSource
        | undefined;

    src?.setData(emptyFeatureCollection());
}

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

    allRouteOptions.value = data.routes
        .slice(0, 3)
        .map((route: any, index: number) => ({
            index,
            geometry: route.geometry as GeoJSON.LineString,
            distance: route.distance,
            duration: route.duration,
            coordinates: route.geometry.coordinates as [number, number][],
        }));

    applySelectedRoute(0);
    fitMap();
}

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
        form.setError(
            'destination_name',
            'Please select or pin a destination.',
        );
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

watch(resolvedAppearance, (appearance) => {
    if (map.value?.isStyleLoaded()) {
        map.value.setConfigProperty(
            'basemap',
            'lightPreset',
            appearance === 'dark' ? 'night' : 'day',
        );
    }
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
        <div
            class="flex h-full min-h-0 w-full flex-1 flex-col gap-4 lg:flex-row lg:items-stretch"
        >
            <Card class="flex min-h-0 min-w-0 flex-1 flex-col lg:h-full">
                <CardHeader class="flex flex-row items-start gap-3">
                    <Button as-child variant="header-actions" size="icon">
                        <Link :href="index().url" aria-label="Back to routes">
                            <RiArrowLeftLine class="h-4 w-4" />
                        </Link>
                    </Button>
                    <div class="flex min-w-0 flex-col">
                        <CardTitle class="font-semibold">Edit Route</CardTitle>
                        <CardDescription>
                            Update the route, map its path, and organize its
                            stops.
                        </CardDescription>
                    </div>
                </CardHeader>
                <div
                    class="no-scrollbar flex h-full max-h-full min-h-0 flex-1 flex-row"
                >
                    <div
                        class="order-1 grid h-full max-h-full min-w-0 flex-1 grid-cols-2 items-stretch gap-4 px-6 pt-2"
                    >
                        <div
                            class="flex max-h-full min-h-0 flex-1 flex-col px-0"
                        >
                            <CardContent
                                class="flex min-h-0 flex-1 flex-col gap-2 px-0"
                            >
                                <!-- TODO: remind me soon to put redesign this part -->
                                <!-- <div
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
                                </div> -->
                                <div class="relative min-h-0 w-full flex-1">
                                    <div
                                        ref="mapEl"
                                        class="route-map h-full w-full overflow-hidden rounded-md p-0"
                                    />

                                    <div
                                        class="pointer-events-none absolute inset-x-3 top-3 z-10 max-w-2/3"
                                    >
                                        <Card class="pointer-events-auto">
                                            <CardHeader class="mb-2">
                                                <CardTitle class="text-sm"
                                                    >Destination</CardTitle
                                                >
                                                <CardDescription
                                                    class="text-custom-shadow/80"
                                                >
                                                    {{
                                                        lineClickMessage ||
                                                        'Click the map to pin destination.'
                                                    }}
                                                </CardDescription>
                                            </CardHeader>

                                            <CardContent class="relative">
                                                <SearchInput
                                                    v-model="destinationQuery"
                                                    placeholder="Search destination..."
                                                />
                                            </CardContent>

                                            <div
                                                class="mt-2 flex flex-col gap-y-2"
                                            >
                                                <div
                                                    v-if="hasDestination"
                                                    class="mx-6 flex cursor-pointer items-center justify-between rounded-md border border-custom-accent-3 bg-custom-accent-3/10 px-3 py-2 text-left hover:bg-custom-accent-3/5"
                                                >
                                                    <div
                                                        class="flex flex-row items-center gap-2"
                                                    >
                                                        <RiMapPin2Line
                                                            class="h-4 w-4 shrink-0 text-custom-accent-3"
                                                        />
                                                        <span
                                                            class="min-w-0 truncate text-sm font-semibold"
                                                        >
                                                            {{
                                                                form.destination_name
                                                            }}
                                                        </span>
                                                    </div>

                                                    <RiCheckboxCircleLine
                                                        class="h-4 w-4 shrink-0 text-custom-accent-3"
                                                    />
                                                </div>

                                                <div
                                                    v-if="
                                                        destinationSuggestions.length
                                                    "
                                                    class="mx-6 overflow-hidden"
                                                >
                                                    <button
                                                        v-for="item in destinationSuggestions"
                                                        :key="item.id"
                                                        type="button"
                                                        class="flex w-full cursor-pointer items-start gap-2 rounded-md px-3 py-2 text-left hover:bg-custom-primary/10"
                                                        @click="
                                                            setDestinationFromSuggestion(
                                                                item,
                                                            )
                                                        "
                                                    >
                                                        <RiMapPin2Line
                                                            class="mt-0.5 h-4 w-4 shrink-0 text-custom-shadow/80"
                                                        />
                                                        <div class="min-w-0">
                                                            <div
                                                                class="truncate text-sm font-semibold text-custom-shadow"
                                                            >
                                                                {{ item.name }}
                                                            </div>
                                                            <div
                                                                class="truncate text-xs text-custom-shadow/80"
                                                            >
                                                                {{
                                                                    item.full_address
                                                                }}
                                                            </div>
                                                        </div>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- <p v-else class="text-xs text-custom-shadow/80 pt-2 text-center">Click anywhere on the map to set destination.</p> -->

                                            <InputError
                                                :message="
                                                    form.errors.destination_name
                                                "
                                            />
                                        </Card>
                                    </div>
                                </div>
                            </CardContent>
                        </div>
                        <div
                            class="no-scrollbar space-y-4 overflow-y-auto px-0"
                        >
                            <div class="space-y-4">
                                <div class="flex items-center gap-3 pt-2">
                                    <p
                                        class="text-base font-semibold text-custom-accent-3"
                                    >
                                        Alternative Routes
                                        <!-- <Badge variant="accent-3">{{ totalVisibleStops }}</Badge> -->
                                    </p>
                                    <Separator class="flex-1" />
                                </div>

                                <CardContent class="space-y-2 px-0">
                                    <!-- TODO: use Button component and style the active index with primary-outline and the rest ghost-outline -->
                                    <button
                                        type="button"
                                        :class="[
                                            'w-full cursor-pointer rounded-md border p-3 text-left transition-all duration-300 hover:-translate-y-0.5',
                                            selectedRouteIndex === 0
                                                ? 'border-custom-accent-3 bg-custom-accent-3/10 hover:bg-custom-accent-3/5'
                                                : 'border-custom-bg-dark bg-transparent hover:bg-custom-accent-3/5 dark:border-custom-bg-light',
                                        ]"
                                        @click="selectAlternativeRoute(0)"
                                    >
                                        <div class="flex items-center gap-3">
                                            <div class="min-w-0 flex-1">
                                                <div
                                                    class="flex items-center justify-between gap-2"
                                                >
                                                    <p
                                                        class="flex items-center gap-x-2"
                                                    >
                                                        <span
                                                            class="font-semibold"
                                                        >
                                                            Route 1
                                                        </span>
                                                        <span class="text-xs">
                                                            Primary
                                                        </span>
                                                    </p>
                                                    <p
                                                        class="shrink-0 text-xs text-custom-shadow/80"
                                                    >
                                                        {{
                                                            originalPrimaryRoute
                                                                ? fmtDistance(
                                                                      originalPrimaryRoute.distance,
                                                                  )
                                                                : '—'
                                                        }}
                                                        |
                                                        {{
                                                            originalPrimaryRoute
                                                                ? fmtDuration(
                                                                      originalPrimaryRoute.duration,
                                                                  )
                                                                : '—'
                                                        }}
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
                                            'w-full cursor-pointer rounded-md border p-3 text-left transition-all duration-300 hover:-translate-y-0.5',
                                            selectedRouteIndex === alt.index
                                                ? 'border-custom-accent-3 bg-custom-accent-3/10 hover:bg-custom-accent-3/5'
                                                : 'border-custom-bg-dark bg-transparent hover:bg-custom-accent-3/5 dark:border-custom-bg-light',
                                        ]"
                                        @click="
                                            selectAlternativeRoute(alt.index)
                                        "
                                    >
                                        <div class="flex items-center gap-3">
                                            <div class="min-w-0 flex-1">
                                                <div
                                                    class="flex items-center justify-between gap-2"
                                                >
                                                    <p
                                                        class="flex items-center gap-x-2"
                                                    >
                                                        <span
                                                            class="font-semibold"
                                                        >
                                                            Route
                                                            {{ alt.index + 1 }}
                                                        </span>
                                                        <span class="text-xs">
                                                            Alternate
                                                        </span>
                                                    </p>
                                                    <p
                                                        class="shrink-0 text-xs text-custom-shadow/80"
                                                    >
                                                        {{
                                                            fmtDistance(
                                                                alt.distance,
                                                            )
                                                        }}
                                                        |
                                                        {{
                                                            fmtDuration(
                                                                alt.duration,
                                                            )
                                                        }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </button>
                                </CardContent>
                            </div>

                            <div v-if="waypoints.length" class="space-y-4">
                                <div class="flex items-center gap-3 pt-2">
                                    <p
                                        class="text-base font-semibold text-custom-accent-3"
                                    >
                                        Detour Points
                                        <!-- <Badge variant="accent-3">{{ totalVisibleStops }}</Badge> -->
                                    </p>
                                    <Separator class="flex-1" />
                                </div>

                                <CardContent class="space-y-2 px-0">
                                    <div
                                        v-for="(wp, index) in waypoints"
                                        :key="index"
                                        class="flex w-full items-center gap-2.5 rounded-md border border-custom-bg-dark bg-transparent p-3 text-left transition-all duration-300 dark:border-custom-bg-light"
                                    >
                                        <p
                                            class="tex-xs flex-1 font-mono font-semibold"
                                        >
                                            {{ wp.lat.toFixed(4) }},
                                            {{ wp.lng.toFixed(4) }}
                                        </p>
                                        <Button
                                            type="button"
                                            aria-label="Remove detour point"
                                            @click="
                                                waypoints.splice(index, 1);
                                                renderWaypointMarkers();
                                                redrawRoute();
                                            "
                                            class="flex h-6 w-6 cursor-pointer items-center rounded-full text-custom-shadow transition-all duration-300 hover:bg-destructive/20 hover:text-destructive"
                                        >
                                            <RiCloseLine class="h-4 w-4" />
                                        </Button>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="destructive"
                                        class="w-full"
                                        @click="removeAllWaypoints"
                                    >
                                        Clear all
                                    </Button>
                                </CardContent>
                            </div>

                            <div class="space-y-4">
                                <div class="flex items-center gap-3 pt-2">
                                    <p
                                        class="font-semibold text-custom-accent-3"
                                    >
                                        Add Stops
                                        <!-- <Badge variant="accent-3">{{ totalVisibleStops }}</Badge> -->
                                    </p>
                                    <Separator class="flex-1" />
                                </div>

                                <CardContent class="space-y-2 px-0">
                                    <div class="space-y-2">
                                        <div class="relative">
                                            <RiSearchLine
                                                class="pointer-events-none absolute top-1/2 left-3 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground"
                                            />
                                            <Input
                                                v-model="stopQuery"
                                                class="h-10 pr-9 pl-9 text-sm"
                                                placeholder="Search route stop..."
                                                :disabled="loadingStopSearch"
                                            />
                                            <button
                                                v-if="stopQuery"
                                                type="button"
                                                class="absolute top-1/2 right-3 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                                @click="
                                                    stopQuery = '';
                                                    stopSuggestions = [];
                                                "
                                            >
                                                <RiCloseLine
                                                    class="h-3.5 w-3.5"
                                                />
                                            </button>
                                        </div>

                                        <div v-if="stopSuggestions.length">
                                            <button
                                                v-for="item in stopSuggestions"
                                                :key="item.id"
                                                type="button"
                                                class="flex w-full cursor-pointer items-start gap-2 rounded-md px-3 py-2 text-left hover:bg-custom-primary/10"
                                                @click="
                                                    addStopFromSuggestion(item)
                                                "
                                            >
                                                <RiMapPin2Line
                                                    class="mt-0.5 h-4 w-4 shrink-0 text-custom-shadow/80"
                                                />
                                                <div class="min-w-0">
                                                    <div
                                                        class="truncate text-sm font-semibold text-custom-shadow"
                                                    >
                                                        {{ item.name }}
                                                    </div>
                                                    <div
                                                        class="truncate text-xs text-custom-shadow/80"
                                                    >
                                                        {{ item.full_address }}
                                                    </div>
                                                </div>
                                            </button>
                                        </div>

                                        <InputError
                                            v-if="
                                                !stopSuggestions.length &&
                                                stopQuery &&
                                                !loadingStopSearch
                                            "
                                            message="No places found within 500 m of the route."
                                        />
                                    </div>

                                    <div class="flex items-center gap-3 pt-2">
                                        <Separator class="flex-1" />
                                        <p class="text-sm">
                                            or
                                            <!-- <Badge variant="accent-3">{{ totalVisibleStops }}</Badge> -->
                                        </p>
                                        <Separator class="flex-1" />
                                    </div>

                                    <div class="space-y-2">
                                        <!-- <div class="flex items-center gap-1.5 text-xs font-medium">
                                            <RiMagicLine class="h-3.5 w-3.5 text-muted-foreground" />
                                            Auto-Generate Stops
                                        </div> -->

                                        <div class="flex items-center gap-2">
                                            <Input
                                                v-model.number="
                                                    autoGenerateInterval
                                                "
                                                type="number"
                                                min="1"
                                                max="50"
                                                class="h-8 w-20 flex-1 text-sm"
                                            />
                                            <span
                                                class="text-sm text-custom-shadow"
                                                >km apart</span
                                            >
                                            <Button
                                                type="button"
                                                variant="float-primary"
                                                class="group items-center"
                                                :disabled="
                                                    loadingAutoGenerate ||
                                                    routeCoordinates.length < 2
                                                "
                                                @click="autoGenerateStops"
                                            >
                                                <RiAiGenerate
                                                    class="text-custom-bg-light dark:text-custom-shadow"
                                                />
                                                {{
                                                    loadingAutoGenerate
                                                        ? 'Generating...'
                                                        : 'Generate'
                                                }}
                                            </Button>
                                        </div>
                                        <div>
                                            <p
                                                class="pt-2 text-center text-xs text-custom-shadow/80"
                                            >
                                                This replaces all current stops.
                                            </p>
                                        </div>
                                    </div>
                                </CardContent>
                            </div>
                        </div>
                    </div>
                </div>
            </Card>

            <Card class="min-h-0 lg:flex lg:h-full lg:w-100">
                <CardHeader>
                    <CardTitle>Details</CardTitle>
                    <CardDescription
                        >Configure and review details</CardDescription
                    >
                </CardHeader>

                <CardContent
                    class="no-scrollbar min-h-0 flex-1 space-y-6 overflow-y-auto py-2"
                >
                    <section class="space-y-4">
                        <div class="space-y-4">
                            <div class="flex flex-col gap-y-2">
                                <Label for="route_name_sidebar"> Name </Label>
                                <Input
                                    id="route_name_sidebar"
                                    :model-value="form.route_name"
                                    placeholder="Enter route name"
                                    class="h-10"
                                    @update:model-value="onRouteNameInput"
                                />
                                <InputError :message="form.errors.route_name" />
                            </div>

                            <div class="flex flex-col gap-y-2">
                                <Label for="gate_id_sidebar"> Gate </Label>
                                <Select v-model="form.gate_id">
                                    <SelectTrigger
                                        id="gate_id_sidebar"
                                        class="w-full"
                                    >
                                        <SelectValue
                                            placeholder="Select a gate..."
                                        />
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
                            <div class="gap-x-4 flex flex-row">
                                <div class="flex flex-col gap-y-2 flex-1">
                                    <Label> Distance </Label>
                                    <div
                                        class="flex h-9 w-full min-w-0 items-center rounded-md border border-custom-bg-dark bg-custom-bg p-3 text-sm text-custom-shadow transition-[color,background-color,border-color,box-shadow] outline-none dark:border-none dark:border-custom-bg-light dark:bg-custom-bg-dark dark:shadow-sm dark:shadow-white/5"
                                    >
                                        <span>{{
                                            form.distance_meters
                                                ? fmtDistance(form.distance_meters)
                                                : '—'
                                        }}</span>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-y-2 flex-1">
                                    <Label> Est. Travel Duration </Label>
                                    <div
                                        class="flex h-9 w-full min-w-0 items-center rounded-md border border-custom-bg-dark bg-custom-bg p-3 text-sm text-custom-shadow transition-[color,background-color,border-color,box-shadow] outline-none dark:border-none dark:border-custom-bg-light dark:bg-custom-bg-dark dark:shadow-sm dark:shadow-white/5"
                                    >
                                        <span>{{
                                            form.duration_seconds
                                                ? fmtDuration(form.duration_seconds)
                                                : '—'
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-center gap-3 pt-2">
                                <p
                                    class="text-base font-semibold text-custom-accent-3"
                                >
                                    Stops
                                    <Badge variant="accent-3">{{
                                        totalVisibleStops
                                    }}</Badge>
                                </p>
                                <Separator class="flex-1" />
                            </div>

                            <div
                                v-if="!hasDestination"
                                class="rounded-md border border-dashed border-custom-bg-dark p-3 text-center shadow-none dark:border-custom-bg-light"
                            >
                                <p class="text-sm text-custom-shadow/80">
                                    Set a destination to see the stop sequence.
                                </p>
                            </div>

                            <div v-else class="relative">
                                <div
                                    class="absolute top-6 bottom-6 left-[18px] w-px bg-slate-200"
                                />
                                <div class="space-y-1">
                                    <div
                                        class="flex items-start gap-3 rounded-lg p-2"
                                    >
                                        <div
                                            class="relative z-10 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-green-600 text-[9px] font-bold text-white ring-2 ring-background"
                                        >
                                            1
                                        </div>
                                        <div class="min-w-0 pt-0.5">
                                            <p
                                                class="truncate text-sm leading-tight font-medium"
                                            >
                                                {{ form.origin_name }}
                                            </p>
                                            <p
                                                class="text-[10px] tracking-wide text-green-600 uppercase"
                                            >
                                                Origin
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        v-for="(stop, index) in form.stops"
                                        :key="`${stop.stop_name}-${stop.latitude}-${index}`"
                                        :draggable="true"
                                        :class="[
                                            'flex cursor-grab items-start gap-3 rounded-lg p-2 transition-colors select-none',
                                            dragOverIndex === index
                                                ? 'bg-blue-50 ring-1 ring-blue-300'
                                                : 'hover:bg-muted/40',
                                            draggedStopIndex === index
                                                ? 'opacity-50'
                                                : '',
                                        ]"
                                        @dragstart="onDragStart(index)"
                                        @dragover="onDragOver($event, index)"
                                        @drop="onDrop(index)"
                                        @dragend="onDragEnd"
                                    >
                                        <div
                                            :class="[
                                                'relative z-10 flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[9px] font-bold text-white ring-2 ring-background',
                                                stop.stop_type === 'landmark'
                                                    ? 'bg-violet-500'
                                                    : 'bg-amber-500',
                                            ]"
                                        >
                                            {{ index + 2 }}
                                        </div>
                                        <div class="min-w-0 flex-1 pt-0.5">
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <p
                                                    class="truncate text-sm leading-tight font-medium"
                                                >
                                                    {{ stop.stop_name }}
                                                </p>
                                                <span
                                                    v-if="
                                                        stop.stop_type ===
                                                        'landmark'
                                                    "
                                                    class="rounded bg-violet-100 px-1.5 py-0.5 text-[10px] font-medium text-violet-700"
                                                    >Landmark</span
                                                >
                                            </div>
                                            <p
                                                class="truncate text-[11px] text-muted-foreground"
                                            >
                                                {{
                                                    stop.address || 'No address'
                                                }}
                                            </p>
                                        </div>
                                        <div
                                            class="flex shrink-0 items-center gap-1"
                                        >
                                            <RiDraggable
                                                class="h-3.5 w-3.5 text-muted-foreground/50"
                                            />
                                            <button
                                                type="button"
                                                class="rounded p-0.5 text-muted-foreground/50 hover:text-destructive"
                                                @click="removeStop(index)"
                                            >
                                                <RiCloseLine
                                                    class="h-3.5 w-3.5"
                                                />
                                            </button>
                                        </div>
                                    </div>

                                    <div
                                        class="flex items-start gap-3 rounded-lg p-2"
                                    >
                                        <div
                                            class="relative z-10 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-600 text-[9px] font-bold text-white ring-2 ring-background"
                                        >
                                            {{ form.stops.length + 2 }}
                                        </div>
                                        <div class="min-w-0 pt-0.5">
                                            <p
                                                class="truncate text-sm leading-tight font-medium"
                                            >
                                                {{ form.destination_name }}
                                            </p>
                                            <p
                                                class="text-[10px] tracking-wide text-red-600 uppercase"
                                            >
                                                Destination
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <Separator />

                        <div class="flex flex-row items-center justify-end">
                            <Button
                                :variant="
                                    form.processing || !routeReady
                                        ? 'disabled'
                                        : 'float-primary'
                                "
                                size="icon-text"
                                :disabled="form.processing || !routeReady"
                                @click="submit"
                            >
                                {{
                                    form.processing
                                        ? 'Saving...'
                                        : 'Save Changes'
                                }}
                            </Button>
                        </div>
                    </section>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<style scoped>
.route-map :deep(.mapboxgl-ctrl-group) {
    background-color: var(--custom-bg-dark);
    box-shadow: 0 0 0 1px
        color-mix(in oklch, var(--custom-shadow) 25%, transparent);
}

.route-map :deep(.mapboxgl-ctrl-group button) {
    background-color: var(--custom-bg-dark);
}

.route-map :deep(.mapboxgl-ctrl-group button + button) {
    border-top-color: color-mix(
        in oklch,
        var(--custom-shadow) 25%,
        transparent
    );
}

.route-map :deep(.mapboxgl-ctrl-group button:hover) {
    background-color: var(--custom-secondary);
}
</style>
