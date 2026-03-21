<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
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
import { Separator } from '@/components/ui/separator';

import {
    ArrowLeft,
    Building2,
    CheckCircle2,
    CircleDot,
    Clock3,
    Map as MapIcon,
    MapPinned,
    Milestone,
    Pencil,
    Route as RouteIcon,
    User2,
} from 'lucide-vue-next';

import { edit, index } from '@/actions/App/Http/Controllers/RouteController';
import type { BreadcrumbItem } from '@/types';

import mapboxgl from 'mapbox-gl';
import 'mapbox-gl/dist/mapbox-gl.css';
import vehicleTypes from '@/routes/vehicle-types';

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

const totalVisibleStops = computed(() => {
    return sortedStops.value.length || 0;
});

const routeHealthText = computed(() => {
    if (!props.route.route_geometry) return 'No saved route geometry.';
    return 'Route data is available and ready to inspect.';
});

const startStop = computed(() => {
    return sortedStops.value.find((stop) => stop.stop_type === 'origin')
        ?? sortedStops.value[0]
        ?? null;
});

const endStop = computed(() => {
    return [...sortedStops.value]
        .reverse()
        .find((stop) => stop.stop_type === 'destination')
        ?? sortedStops.value[sortedStops.value.length - 1]
        ?? null;
});

/* ─────────────────────────────────────────────────────────────────────────────
   Helpers
───────────────────────────────────────────────────────────────────────────── */

function fmtDistance(m: number) {
    if (!m) return '—';
    if (m < 1000) return `${Math.round(m)} m`;
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
            return 'border-violet-200 bg-violet-50 text-violet-700';
        default:
            return 'border-amber-200 bg-amber-50 text-amber-700';
    }
}

function markerColor(type: RouteStop['stop_type']) {
    switch (type) {
        case 'origin':
            return '#16a34a';
        case 'destination':
            return '#dc2626';
        case 'landmark':
            return '#8b5cf6';
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
        <div
            class="sticky top-0 z-20 border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/80"
        >
            <div class="flex h-14 items-center justify-between gap-4 px-4 sm:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                        <RouteIcon class="h-4 w-4" />
                    </div>

                    <div class="min-w-0">
                        <div class="flex items-center gap-2 text-sm font-semibold">
                            <span class="truncate">{{ route.route_name }}</span>
                            <span
                                v-if="route.destination_name"
                                class="hidden shrink-0 text-muted-foreground sm:inline"
                            >
                                → {{ route.destination_name }}
                            </span>
                        </div>
                        <p class="truncate text-xs text-muted-foreground">
                            Fixed origin:
                            <span class="font-medium text-foreground">{{ route.origin_name }}</span>
                        </p>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <div class="hidden items-center gap-1.5 sm:flex">
                        <span class="h-2 w-2 rounded-full bg-green-500" />
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
                            {{ route.destination_name || '—' }}
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
                            {{ route.distance_meters ? fmtDistance(route.distance_meters) : '—' }}
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
                            {{ route.duration_seconds ? fmtDuration(route.duration_seconds) : '—' }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 rounded-xl border bg-card px-4 py-3 shadow-sm">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-green-50 text-green-600">
                        <RouteIcon class="h-4 w-4" />
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
                                        Inspect the saved route path and stop locations.
                                    </CardDescription>
                                </div>

                                <Badge variant="secondary" class="shrink-0 text-xs">
                                    View only
                                </Badge>
                            </div>
                        </CardHeader>

                        <CardContent class="space-y-3 pt-0">
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
                                                Saved destination for this route.
                                            </p>
                                        </div>

                                        <div
                                            class="flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2"
                                        >
                                            <MapPinned class="h-3.5 w-3.5 shrink-0 text-red-600" />
                                            <span class="min-w-0 truncate text-sm font-medium text-red-800">
                                                {{ route.destination_name }}
                                            </span>
                                            <CheckCircle2 class="ml-auto h-3.5 w-3.5 shrink-0 text-red-500" />
                                        </div>
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
                                    Landmark stops
                                </span>
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
                                        Ordered stop sequence for this route.
                                    </CardDescription>
                                </div>
                                <Badge variant="secondary" class="text-xs">
                                    {{ totalVisibleStops }}
                                </Badge>
                            </div>
                        </CardHeader>

                        <CardContent class="pt-0">
                            <div v-if="sortedStops.length" class="relative">
                                <div class="absolute bottom-6 left-[18px] top-6 w-px bg-border" />

                                <div class="space-y-1">
                                    <div
                                        v-for="(stop, index) in sortedStops"
                                        :key="stop.id"
                                        class="relative flex items-start gap-3 rounded-xl px-3 py-3 transition-colors hover:bg-muted/40"
                                    >
                                        <div
                                            :class="[
                                                'relative z-10 flex h-5 w-5 shrink-0 items-center justify-center rounded-full ring-2 ring-background text-[9px] font-bold text-white',
                                                stop.stop_type === 'origin'
                                                    ? 'bg-green-600'
                                                    : stop.stop_type === 'destination'
                                                      ? 'bg-red-600'
                                                      : stop.stop_type === 'landmark'
                                                        ? 'bg-violet-500'
                                                        : 'bg-amber-500',
                                            ]"
                                        >
                                            {{ index + 1 }}
                                        </div>

                                        <div class="min-w-0 flex-1 pt-0.5">
                                            <div class="flex items-center gap-2">
                                                <p class="truncate text-sm font-medium leading-tight">
                                                    {{ stop.stop_name }}
                                                </p>
                                                <span
                                                    :class="[
                                                        'inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-medium',
                                                        stopTypeBadgeClass(stop.stop_type),
                                                    ]"
                                                >
                                                    {{ stopTypeLabel(stop.stop_type) }}
                                                </span>
                                            </div>

                                            <p class="truncate text-[11px] text-muted-foreground">
                                                {{ stop.address || 'No address' }}
                                            </p>
                                            <p class="text-[10px] text-muted-foreground">
                                                {{ Number(stop.latitude).toFixed(5) }},
                                                {{ Number(stop.longitude).toFixed(5) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-else
                                class="rounded-xl border border-dashed px-4 py-6 text-center text-xs text-muted-foreground"
                            >
                                No stops available for this route.
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="rounded-2xl">
                        <CardHeader>
                            <CardTitle>Stops Table</CardTitle>
                            <CardDescription>
                                Structured stop data for this route.
                            </CardDescription>
                        </CardHeader>

                        <CardContent>
                            <div v-if="sortedStops.length" class="overflow-hidden rounded-xl border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead class="w-20">Order</TableHead>
                                            <TableHead>Stop Name</TableHead>
                                            <TableHead>Type</TableHead>
                                            <TableHead>Address</TableHead>
                                            <TableHead class="text-right">Coordinates</TableHead>
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
                                        <TableRow v-if="sortedStops.length === 0">
                                            <TableCell
                                                colspan="5"
                                                class="py-10 text-center text-muted-foreground"
                                            >
                                                No stops found.
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
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
                                    <p class="truncate text-sm font-medium">
                                        {{ startStop?.stop_name ?? route.origin_name }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 border-b py-3">
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-600 text-[10px] font-bold text-white">
                                    B
                                </span>
                                <div class="min-w-0">
                                    <p class="text-[10px] uppercase tracking-wide text-muted-foreground">Destination</p>
                                    <p class="truncate text-sm font-medium">
                                        {{ endStop?.stop_name ?? route.destination_name }}
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-x-3 gap-y-0">
                                <div class="border-b border-r py-3 pr-3">
                                    <p class="text-[10px] uppercase tracking-wide text-muted-foreground">Distance</p>
                                    <p class="text-sm font-semibold">
                                        {{ route.distance_meters ? fmtDistance(route.distance_meters) : '—' }}
                                    </p>
                                </div>
                                <div class="border-b py-3 pl-3">
                                    <p class="text-[10px] uppercase tracking-wide text-muted-foreground">Duration</p>
                                    <p class="text-sm font-semibold">
                                        {{ route.duration_seconds ? fmtDuration(route.duration_seconds) : '—' }}
                                    </p>
                                </div>
                                <div class="border-b border-r py-3 pr-3">
                                    <p class="text-[10px] uppercase tracking-wide text-muted-foreground">Stops</p>
                                    <p class="text-sm font-semibold">{{ totalStops }}</p>
                                </div>
                                <div class="border-b py-3 pl-3">
                                    <p class="text-[10px] uppercase tracking-wide text-muted-foreground">Gate</p>
                                    <p class="text-sm font-semibold">
                                        {{ route.gate?.gate_name ?? '—' }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="rounded-2xl">
                        <CardHeader>
                            <CardTitle>Route Info</CardTitle>
                            <CardDescription>
                                Basic route details and endpoints.
                            </CardDescription>
                        </CardHeader>

                        <CardContent class="space-y-4 text-sm">
                            <div class="rounded-xl border p-4">
                                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                    Route Name
                                </p>
                                <p class="mt-1 font-semibold">{{ route.route_name }}</p>
                            </div>

                            <div class="rounded-xl border p-4">
                                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                    Gate
                                </p>
                                <p class="mt-1 font-semibold">
                                    {{ route.gate?.gate_name ?? 'No gate assigned' }}
                                </p>
                            </div>

                            <div class="rounded-xl border p-4">
                                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                    Origin Coordinates
                                </p>
                                <p class="mt-1 font-semibold">
                                    {{ Number(route.origin_lat).toFixed(6) }},
                                    {{ Number(route.origin_lng).toFixed(6) }}
                                </p>
                            </div>

                            <div class="rounded-xl border p-4">
                                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                    Destination Coordinates
                                </p>
                                <p class="mt-1 font-semibold">
                                    {{ Number(route.destination_lat).toFixed(6) }},
                                    {{ Number(route.destination_lng).toFixed(6) }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="rounded-2xl">
                        <CardHeader>
                            <CardTitle>Activity</CardTitle>
                            <CardDescription>
                                Creation and update history.
                            </CardDescription>
                        </CardHeader>

                        <CardContent class="space-y-4 text-sm">
                            <div class="rounded-xl border p-4">
                                <div class="flex items-center gap-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                    <User2 class="h-3.5 w-3.5" />
                                    Created By
                                </div>
                                <p class="mt-1 font-semibold">
                                    {{ route.creator?.name ?? '—' }}
                                </p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ route.created_at_human ?? '—' }}
                                </p>
                            </div>

                            <div class="rounded-xl border p-4">
                                <div class="flex items-center gap-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                    <User2 class="h-3.5 w-3.5" />
                                    Last Updated By
                                </div>
                                <p class="mt-1 font-semibold">
                                    {{ route.updater?.name ?? '—' }}
                                </p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ route.updated_at_human ?? '—' }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <div class="rounded-2xl border bg-card p-4 shadow-sm">
                        <div class="space-y-4">
                            <div>
                                <p class="text-sm font-semibold">Quick Actions</p>
                                <p class="text-xs text-muted-foreground">
                                    Go back to the route list or edit this route.
                                </p>
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

                                <Button as-child class="w-full">
                                    <Link :href="edit(route.id).url">
                                        <Pencil class="mr-2 h-4 w-4" />
                                        Edit Route
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
