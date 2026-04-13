<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

import {
    Archive,
    ArrowLeft,
    CheckCircle2,
    Clock3,
    DoorOpen,
    MapPinned,
    Pencil,
    Route as RouteIcon,
    Ruler,
    ArchiveX,
} from 'lucide-vue-next';

import { edit, index } from '@/actions/App/Http/Controllers/RouteController';
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
const archiveOpen = ref(false);

/* ─────────────────────────────────────────────────────────────────────────────
   Computed
───────────────────────────────────────────────────────────────────────────── */

const sortedStops = computed(() =>
    [...props.route.stops].sort((a, b) => a.stop_order - b.stop_order),
);

const totalStops = computed(() => props.route.stops.length);

const routeHealthText = computed(() => {
    if (!props.route.route_geometry) return 'No saved route geometry.';
    return 'Route data is available and ready to inspect.';
});

const startStop = computed(() => {
    return (
        sortedStops.value.find((stop) => stop.stop_type === 'origin') ??
        sortedStops.value[0] ??
        null
    );
});

const endStop = computed(() => {
    return (
        [...sortedStops.value]
            .reverse()
            .find((stop) => stop.stop_type === 'destination') ??
        sortedStops.value[sortedStops.value.length - 1] ??
        null
    );
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

function openArchiveDialog() {
    archiveOpen.value = true;
}

function archiveRoute() {
    router.delete(`/routes/${props.route.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            archiveOpen.value = false;
            toast.success('Route archived successfully.');
        },
        onError: () => {
            toast.error('Failed to archive route.');
        },
    });
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
                                        {{ route.origin_name }} → {{ route.destination_name }}
                                    </Badge>
                                    <Badge v-if="route.gate" class="border-0 bg-slate-100 text-slate-600">
                                        {{ route.gate.gate_name }}
                                    </Badge>
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
                                    <Button
                                        as-child
                                        variant="outline"
                                        class="group/segment rounded-lg bg-card border-slate-200 text-slate-600 hover:bg-slate-100 gap-0 cursor-pointer"
                                    >
                                        <Link :href="edit(route.id).url">
                                            <Pencil class="h-4 w-4 shrink-0" />
                                            <span class="max-w-0 overflow-hidden whitespace-nowrap opacity-0 transition-all duration-300 group-hover/segment:ml-2 group-hover/segment:max-w-24 group-hover/segment:opacity-100">
                                                Edit Route
                                            </span>
                                        </Link>
                                    </Button>
                                    <Button
                                        variant="outline"
                                        class="group/segment rounded-lg bg-card border-rose-200 text-rose-600 hover:bg-rose-50 hover:text-rose-700 gap-0 cursor-pointer"
                                        @click="openArchiveDialog"
                                    >
                                        <Archive class="h-4 w-4 shrink-0" />
                                        <span class="max-w-0 overflow-hidden whitespace-nowrap opacity-0 transition-all duration-300 group-hover/segment:ml-2 group-hover/segment:max-w-32 group-hover/segment:opacity-100">
                                            Archive Route
                                        </span>
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardHeader>
            </Card>

            <div class="grid items-start gap-4 xl:grid-cols-[1fr_380px]">

                <!-- Map card -->
                <Card class="py-6">
                    <CardHeader class="flex items-center justify-between">
                        <CardTitle class="text-base">Map Workspace</CardTitle>
                    </CardHeader>

                    <CardContent class="p-6 grid divide-y gap-y-2 border-t border-slate-100">
                        <div class="relative">
                            <div
                                ref="mapEl"
                                class="h-[500px] w-full overflow-hidden rounded-lg border sm:h-[620px]"
                            />
                            <div
                                class="pointer-events-none absolute inset-x-3 top-3 z-10 sm:right-auto sm:left-3 sm:w-[420px]"
                            >
                                <div
                                    class="pointer-events-auto rounded-lg border bg-background/95 p-4 shadow-lg backdrop-blur"
                                >
                                    <div class="mb-2">
                                        <p class="text-sm font-semibold">Destination</p>
                                        <p class="text-xs text-muted-foreground">
                                            Saved destination for this route.
                                        </p>
                                    </div>
                                    <div
                                        class="flex items-center gap-2 rounded-lg border border-primary bg-primary/10 p-4"
                                    >
                                        <MapPinned class="h-4 w-4 shrink-0 text-primary" />
                                        <span class="min-w-0 truncate text-sm font-medium text-primary">
                                            {{ route.destination_name }}
                                        </span>
                                        <CheckCircle2 class="ml-auto h-4 w-4 shrink-0 text-primary" />
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

                <!-- Stops card (scrollable, capped at map height) -->
                <Card class="py-6">
                    <CardHeader class="flex items-center justify-between">
                        <CardTitle class="text-base">Stops</CardTitle>
                    </CardHeader>

                    <CardContent class="overflow-y-auto p-6 border-t border-slate-100 max-h-[575px] sm:max-h-[695px]">
                        <div v-if="sortedStops.length" class="relative">
                            <div class="absolute top-6 bottom-6 left-[18px] w-px bg-slate-200" />

                            <div class="space-y-1">
                                <div
                                    v-for="(stop, index) in sortedStops"
                                    :key="stop.id"
                                    class="group flex items-start gap-3 rounded-lg p-2 transition-colors hover:bg-muted"
                                >
                                    <div
                                        :class="[
                                            'relative z-10 flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[9px] font-bold text-white ring-2 ring-background',
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

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                            <p class="truncate max-h-[1.25rem] text-sm leading-tight font-medium transition-[max-height] duration-300 ease-in-out group-hover:max-h-20 group-hover:whitespace-normal">
                                                {{ stop.stop_name }}
                                            </p>
                                            <span
                                                :class="[
                                                    'inline-flex shrink-0 items-center rounded-full border px-2 py-0.5 text-[10px] font-medium',
                                                    stopTypeBadgeClass(stop.stop_type),
                                                ]"
                                            >
                                                {{ stopTypeLabel(stop.stop_type) }}
                                            </span>
                                        </div>
                                        <p class="truncate max-h-[1rem] text-[11px] text-muted-foreground transition-[max-height] duration-300 ease-in-out group-hover:max-h-12 group-hover:whitespace-normal">
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
            </div>

            <!-- Bottom row: route summary | activity -->
            <div class="grid gap-4 xl:grid-cols-2">

                <!-- Route Summary -->
                <Card class="py-6">
                    <CardHeader>
                        <CardTitle>Route Summary</CardTitle>
                    </CardHeader>
                    <CardContent class="px-6 grid divide-y gap-y-2 pt-2 border-t border-slate-100">
                        <div class="py-2">
                            <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">Origin</span>
                            <span class="text-sm font-semibold truncate block">{{ startStop?.stop_name ?? route.origin_name }}</span>
                        </div>
                        <div class="py-2">
                            <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">Destination</span>
                            <span class="text-sm font-semibold truncate block">{{ endStop?.stop_name ?? route.destination_name }}</span>
                        </div>
                        <div class="py-2">
                            <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">Stops</span>
                            <span class="rounded bg-muted px-2 py-0.5 font-mono text-sm font-semibold tabular-nums">{{ totalStops }}</span>
                        </div>
                        <div class="py-2">
                            <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">Distance</span>
                            <div class="items-center flex">
                                <div class="h-full mr-4">
                                    <Ruler class="h-4 w-4 inline-block text-primary" />
                                </div>
                                <span class="text-sm font-semibold">{{ route.distance_meters ? fmtDistance(route.distance_meters) : '—' }}</span>
                            </div>
                        </div>
                        <div class="py-2">
                            <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">Duration</span>
                            <div class="items-center flex">
                                <div class="h-full mr-4">
                                    <Clock3 class="h-4 w-4 inline-block text-primary" />
                                </div>
                                <span class="text-sm font-semibold">{{ route.duration_seconds ? fmtDuration(route.duration_seconds) : '—' }}</span>
                            </div>
                        </div>
                        <div class="py-2">
                            <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">Gate</span>
                            <div class="items-center flex">
                                <div class="h-full mr-4">
                                    <DoorOpen class="h-4 w-4 inline-block text-primary" />
                                </div>
                                <span class="text-sm font-semibold">{{ route.gate?.gate_name ?? '—' }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Activity -->
                <Card class="py-6">
                    <CardHeader>
                        <CardTitle>Activity</CardTitle>
                    </CardHeader>
                    <CardContent class="px-6 grid divide-y gap-y-2 pt-2 border-t border-slate-100">
                        <div class="py-2">
                            <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">Created</span>
                            <span class="text-sm">{{ route.created_at_human ?? '—' }}</span>
                            <span v-if="route.creator" class="text-xs text-muted-foreground block">
                                by <span class="font-medium text-foreground">{{ route.creator.name }}</span>
                            </span>
                        </div>
                        <div class="py-2">
                            <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">Last Updated</span>
                            <span class="text-sm">{{ route.updated_at_human ?? '—' }}</span>
                            <span v-if="route.updater" class="text-xs text-muted-foreground block">
                                by <span class="font-medium text-foreground">{{ route.updater.name }}</span>
                            </span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Archive dialog -->
            <Dialog :open="archiveOpen" @update:open="archiveOpen = $event">
                <DialogContent class="sm:max-w-md p-4">
                    <DialogHeader>
                        <DialogTitle>Archive Route</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to archive
                            <span class="font-semibold text-foreground">{{ route.route_name }}</span>?
                            This action will remove it from active records.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter class="gap-2 sm:justify-end">
                        <Button class="cursor-pointer hover:bg-slate-100" variant="outline" @click="archiveOpen = false">
                            Cancel
                        </Button>
                        <Button
                            class="bg-destructive text-destructive-foreground cursor-pointer hover:bg-destructive/90"
                            @click="archiveRoute"

                        >
                            <ArchiveX class="h-4 w-4" />
                            Archive
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
