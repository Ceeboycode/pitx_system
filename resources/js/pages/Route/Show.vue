<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

import {
    ArrowLeft,
    MapPinned,
    Pencil,
    Route as RouteIcon,
    Timer,
    Map,
    Building2,
    CircleDot,
} from 'lucide-vue-next';

import { index, edit } from '@/actions/App/Http/Controllers/RouteController';
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

type RouteStop = {
    id: number;
    stop_name: string;
    stop_type: 'origin' | 'stop' | 'destination' | 'landmark';
    address: string | null;
    latitude: number;
    longitude: number;
    stop_order: number;
};

type User = {
    id: number;
    name: string;
};

type RouteModel = {
    id: number;
    route_name: string;
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
    creator: User | null;
    updater: User | null;
    created_at_human: string | null;
    updated_at_human: string | null;
};

/* ─────────────────────────────────────────────────────────────────────────────
   Props
───────────────────────────────────────────────────────────────────────────── */
const props = defineProps<{
    route: RouteModel;
    mapConfig: {
        mapboxToken: string;
    };
}>();

mapboxgl.accessToken = props.mapConfig.mapboxToken;

/* ─────────────────────────────────────────────────────────────────────────────
   Breadcrumbs
───────────────────────────────────────────────────────────────────────────── */
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Routes', href: index().url },
    { title: props.route.route_name, href: '#' },
];

/* ─────────────────────────────────────────────────────────────────────────────
   Refs
───────────────────────────────────────────────────────────────────────────── */
const mapEl = ref<HTMLElement | null>(null);
const map = ref<mapboxgl.Map | null>(null);

/* ─────────────────────────────────────────────────────────────────────────────
   Computed
───────────────────────────────────────────────────────────────────────────── */
const sortedStops = computed(() =>
    [...props.route.stops].sort((a, b) => a.stop_order - b.stop_order),
);

const totalStops = computed(() => props.route.stops.length);

const routeSummary = computed(() => {
    const first = sortedStops.value[0];
    const last = sortedStops.value[sortedStops.value.length - 1];

    return {
        start: first?.stop_name ?? props.route.origin_name,
        end: last?.stop_name ?? props.route.destination_name,
    };
});

/* ─────────────────────────────────────────────────────────────────────────────
   Helpers
───────────────────────────────────────────────────────────────────────────── */
function fmtDistance(m: number) {
    if (!m) return '—';

    if (m < 1000) {
        return `${Math.round(m)} m`;
    }

    return `${(m / 1000).toFixed(2)} km`;
}

function fmtDuration(s: number) {
    if (!s) return '—';

    const hours = Math.floor(s / 3600);
    const mins = Math.ceil((s % 3600) / 60);

    if (hours > 0) {
        return `${hours} hr ${mins} min`;
    }

    return `${Math.ceil(s / 60)} min`;
}

function stopTypeBadgeClass(type: RouteStop['stop_type']) {
    switch (type) {
        case 'origin':
            return 'border-green-200 bg-green-50 text-green-700';
        case 'destination':
            return 'border-red-200 bg-red-50 text-red-700';
        case 'landmark':
            return 'border-purple-200 bg-purple-50 text-purple-700';
        default:
            return 'border-blue-200 bg-blue-50 text-blue-700';
    }
}

function markerColor(type: RouteStop['stop_type']) {
    switch (type) {
        case 'origin':
            return '#16a34a';
        case 'destination':
            return '#dc2626';
        case 'landmark':
            return '#9333ea';
        default:
            return '#f59e0b';
    }
}

function stopTypeLabel(type: RouteStop['stop_type']) {
    switch (type) {
        case 'origin':
            return 'Origin';
        case 'destination':
            return 'Destination';
        case 'landmark':
            return 'Landmark';
        default:
            return 'Stop';
    }
}

/* ─────────────────────────────────────────────────────────────────────────────
   Map
───────────────────────────────────────────────────────────────────────────── */
function initMap() {
    if (!mapEl.value) return;

    const originLat = Number(props.route.origin_lat);
    const originLng = Number(props.route.origin_lng);

    map.value = new mapboxgl.Map({
        container: mapEl.value,
        style: 'mapbox://styles/mapbox/streets-v12',
        center: [originLng, originLat],
        zoom: 12,
        interactive: true,
    });

    map.value.addControl(new mapboxgl.NavigationControl(), 'top-right');

    map.value.on('load', () => {
        if (!map.value) return;

        if (props.route.route_geometry) {
            try {
                const geometry = JSON.parse(props.route.route_geometry);

                map.value.addSource('route-line', {
                    type: 'geojson',
                    data: {
                        type: 'FeatureCollection',
                        features: [
                            {
                                type: 'Feature',
                                properties: {},
                                geometry,
                            },
                        ],
                    },
                });

                map.value.addLayer({
                    id: 'route-line-layer',
                    type: 'line',
                    source: 'route-line',
                    paint: {
                        'line-width': 5,
                        'line-color': '#2563eb',
                    },
                });
            } catch {
                // ignore invalid geometry
            }
        }

        sortedStops.value.forEach((stop) => {
            new mapboxgl.Marker({
                color: markerColor(stop.stop_type),
            })
                .setLngLat([Number(stop.longitude), Number(stop.latitude)])
                .setPopup(
                    new mapboxgl.Popup({ offset: 20 }).setHTML(`
                        <div style="min-width: 180px;">
                            <div style="font-weight: 600; font-size: 14px;">
                                ${stop.stop_order}. ${stop.stop_name}
                            </div>
                            <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                                ${stopTypeLabel(stop.stop_type)}
                            </div>
                            ${
                                stop.address
                                    ? `<div style="font-size: 12px; margin-top: 6px;">${stop.address}</div>`
                                    : ''
                            }
                        </div>
                    `),
                )
                .addTo(map.value!);
        });

        if (sortedStops.value.length >= 2) {
            const bounds = new mapboxgl.LngLatBounds();

            sortedStops.value.forEach((stop) => {
                bounds.extend([Number(stop.longitude), Number(stop.latitude)]);
            });

            map.value.fitBounds(bounds, {
                padding: 60,
                maxZoom: 14,
            });
        }
    });
}

onMounted(async () => {
    await nextTick();
    initMap();
});

onBeforeUnmount(() => {
    map.value?.remove();
});
</script>

<template>
    <Head :title="route.route_name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4 sm:p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge variant="outline" class="gap-1">
                            <RouteIcon class="h-3.5 w-3.5" />
                            Route Details
                        </Badge>

                        <Badge v-if="route.gate" variant="secondary">
                            {{ route.gate.gate_name }}
                        </Badge>
                    </div>

                    <div>
                        <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">
                            {{ route.route_name }}
                        </h1>
                        <p class="mt-1 text-sm text-muted-foreground">
                            View the route overview, map preview, and ordered stops.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                        <div class="inline-flex items-center gap-1.5">
                            <CircleDot class="h-4 w-4" />
                            <span>{{ routeSummary.start }}</span>
                        </div>
                        <span class="hidden sm:inline">→</span>
                        <div class="inline-flex items-center gap-1.5">
                            <MapPinned class="h-4 w-4" />
                            <span>{{ routeSummary.end }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Button as-child variant="outline">
                        <Link :href="index().url">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Back to Routes
                        </Link>
                    </Button>

                    <Button as-child>
                        <Link :href="edit(route.id).url">
                            <Pencil class="mr-2 h-4 w-4" />
                            Edit Route
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Top summary cards -->
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <Card class="rounded-2xl">
                    <CardHeader class="pb-3">
                        <CardDescription class="flex items-center gap-2">
                            <Building2 class="h-4 w-4" />
                            Gate
                        </CardDescription>
                        <CardTitle class="text-lg">
                            {{ route.gate?.gate_name ?? 'No gate assigned' }}
                        </CardTitle>
                    </CardHeader>
                </Card>

                <Card class="rounded-2xl">
                    <CardHeader class="pb-3">
                        <CardDescription class="flex items-center gap-2">
                            <RouteIcon class="h-4 w-4" />
                            Total Stops
                        </CardDescription>
                        <CardTitle class="text-lg">
                            {{ totalStops }}
                        </CardTitle>
                    </CardHeader>
                </Card>

                <Card class="rounded-2xl">
                    <CardHeader class="pb-3">
                        <CardDescription class="flex items-center gap-2">
                            <Map class="h-4 w-4" />
                            Distance
                        </CardDescription>
                        <CardTitle class="text-lg">
                            {{ route.distance_meters ? fmtDistance(route.distance_meters) : '—' }}
                        </CardTitle>
                    </CardHeader>
                </Card>

                <Card class="rounded-2xl">
                    <CardHeader class="pb-3">
                        <CardDescription class="flex items-center gap-2">
                            <Timer class="h-4 w-4" />
                            Estimated Duration
                        </CardDescription>
                        <CardTitle class="text-lg">
                            {{ route.duration_seconds ? fmtDuration(route.duration_seconds) : '—' }}
                        </CardTitle>
                    </CardHeader>
                </Card>
            </div>

            <!-- Main content -->
            <div class="grid gap-6 xl:grid-cols-[1.1fr_1.4fr]">
                <!-- Left column -->
                <div class="space-y-6">
                    <!-- Route overview -->
                    <Card class="rounded-2xl">
                        <CardHeader>
                            <CardTitle>Route Overview</CardTitle>
                            <CardDescription>
                                Basic route information and endpoint details.
                            </CardDescription>
                        </CardHeader>

                        <CardContent>
                            <div class="space-y-4">
                                <div class="rounded-xl border p-4">
                                    <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                        Route Name
                                    </p>
                                    <p class="mt-1 text-sm font-semibold">
                                        {{ route.route_name }}
                                    </p>
                                </div>

                                <div class="grid gap-4 md:grid-cols-2">
                                    <div class="rounded-xl border p-4">
                                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                            Origin
                                        </p>
                                        <p class="mt-1 font-semibold">
                                            {{ route.origin_name }}
                                        </p>
                                        <p class="mt-1 text-xs text-muted-foreground">
                                            {{ Number(route.origin_lat).toFixed(6) }},
                                            {{ Number(route.origin_lng).toFixed(6) }}
                                        </p>
                                    </div>

                                    <div class="rounded-xl border p-4">
                                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                            Destination
                                        </p>
                                        <p class="mt-1 font-semibold">
                                            {{ route.destination_name }}
                                        </p>
                                        <p class="mt-1 text-xs text-muted-foreground">
                                            {{ Number(route.destination_lat).toFixed(6) }},
                                            {{ Number(route.destination_lng).toFixed(6) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Audit info -->
                    <Card class="rounded-2xl">
                        <CardHeader>
                            <CardTitle>Activity</CardTitle>
                            <CardDescription>
                                Creation and latest update information.
                            </CardDescription>
                        </CardHeader>

                        <CardContent class="space-y-4">
                            <div class="rounded-xl border p-4">
                                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                    Created By
                                </p>
                                <p class="mt-1 font-semibold">
                                    {{ route.creator?.name ?? '—' }}
                                </p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ route.created_at_human ?? '—' }}
                                </p>
                            </div>

                            <div class="rounded-xl border p-4">
                                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                    Last Updated By
                                </p>
                                <p class="mt-1 font-semibold">
                                    {{ route.updater?.name ?? '—' }}
                                </p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ route.updated_at_human ?? '—' }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Right column -->
                <div class="space-y-6">
                    <!-- Map card -->
                    <Card class="rounded-2xl overflow-hidden">
                        <CardHeader>
                            <CardTitle>Route Map</CardTitle>
                            <CardDescription>
                                Interactive map with route path and stop markers.
                            </CardDescription>
                            <CardAction>
                                <div class="flex flex-wrap gap-3 text-xs text-muted-foreground">
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="inline-block h-2.5 w-2.5 rounded-full bg-green-600" />
                                        Origin
                                    </span>
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="inline-block h-2.5 w-2.5 rounded-full bg-red-600" />
                                        Destination
                                    </span>
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="inline-block h-2.5 w-2.5 rounded-full bg-yellow-500" />
                                        Stop
                                    </span>
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="inline-block h-2.5 w-2.5 rounded-full bg-purple-600" />
                                        Landmark
                                    </span>
                                </div>
                            </CardAction>
                        </CardHeader>

                        <CardContent class="space-y-3">
                            <div
                                ref="mapEl"
                                class="h-[320px] w-full rounded-xl border sm:h-[420px]"
                            />

                            <p class="text-xs text-muted-foreground">
                                You can zoom, drag, and click markers to inspect each stop.
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Stops card -->
                    <Card class="rounded-2xl">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                Stops
                                <Badge variant="secondary">{{ totalStops }}</Badge>
                            </CardTitle>
                            <CardDescription>
                                Ordered stop list for this route.
                            </CardDescription>
                        </CardHeader>

                        <CardContent>
                            <div v-if="sortedStops.length" class="space-y-5">
                                <!-- Mobile / compact list -->
                                <div class="grid gap-3 lg:hidden">
                                    <div
                                        v-for="stop in sortedStops"
                                        :key="stop.id"
                                        class="rounded-xl border p-4"
                                    >
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="space-y-1">
                                                <div class="flex items-center gap-2">
                                                    <Badge variant="outline">
                                                        #{{ stop.stop_order }}
                                                    </Badge>
                                                    <span
                                                        :class="[
                                                            'inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium',
                                                            stopTypeBadgeClass(stop.stop_type),
                                                        ]"
                                                    >
                                                        {{ stopTypeLabel(stop.stop_type) }}
                                                    </span>
                                                </div>

                                                <p class="text-sm font-semibold">
                                                    {{ stop.stop_name }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="mt-3 space-y-1 text-sm text-muted-foreground">
                                            <p>{{ stop.address || 'No address provided' }}</p>
                                            <p class="text-xs">
                                                {{ Number(stop.latitude).toFixed(5) }},
                                                {{ Number(stop.longitude).toFixed(5) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Desktop table -->
                                <div class="hidden overflow-hidden rounded-xl border lg:block">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead class="w-20">Order</TableHead>
                                                <TableHead>Stop Name</TableHead>
                                                <TableHead>Type</TableHead>
                                                <TableHead>Address</TableHead>
                                                <TableHead class="text-right">
                                                    Coordinates
                                                </TableHead>
                                            </TableRow>
                                        </TableHeader>

                                        <TableBody>
                                            <TableRow
                                                v-for="stop in sortedStops"
                                                :key="stop.id"
                                                class="hover:bg-muted/40"
                                            >
                                                <TableCell>
                                                    <Badge variant="outline">
                                                        {{ stop.stop_order }}
                                                    </Badge>
                                                </TableCell>

                                                <TableCell class="font-medium">
                                                    {{ stop.stop_name }}
                                                </TableCell>

                                                <TableCell>
                                                    <span
                                                        :class="[
                                                            'inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium',
                                                            stopTypeBadgeClass(stop.stop_type),
                                                        ]"
                                                    >
                                                        {{ stopTypeLabel(stop.stop_type) }}
                                                    </span>
                                                </TableCell>

                                                <TableCell class="text-sm text-muted-foreground">
                                                    {{ stop.address || '—' }}
                                                </TableCell>

                                                <TableCell class="text-right text-xs text-muted-foreground">
                                                    {{ Number(stop.latitude).toFixed(5) }},
                                                    {{ Number(stop.longitude).toFixed(5) }}
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </div>
                            </div>

                            <div
                                v-else
                                class="rounded-xl border border-dashed p-6 text-center text-sm text-muted-foreground"
                            >
                                No stops available for this route yet.
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
