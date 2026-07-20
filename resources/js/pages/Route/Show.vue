<script setup lang="ts">
import { useAppearance } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';
import { toast } from 'vue-sonner';

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
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';

import { Archive, ArchiveX, CheckCircle2, MapPinned } from 'lucide-vue-next';
import { RiArrowLeftLine, RiEditLine } from 'vue-remix-icons';

import { edit, index } from '@/actions/App/Http/Controllers/RouteController';
import type { BreadcrumbItem } from '@/types';

import mapboxgl from 'mapbox-gl';
import 'mapbox-gl/dist/mapbox-gl.css';

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

const props = defineProps<{
    route: RouteModel;
    mapConfig: {
        mapboxToken: string;
    };
}>();

mapboxgl.accessToken = props.mapConfig.mapboxToken;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Routes', href: index().url },
    { title: props.route.route_name, href: '#' },
];

const mapEl = ref<HTMLElement | null>(null);
const map = ref<mapboxgl.Map | null>(null);
const { resolvedAppearance } = useAppearance();
const archiveOpen = ref(false);

const sortedStops = computed(() =>
    [...props.route.stops].sort((a, b) => a.stop_order - b.stop_order),
);

const totalStops = computed(() => props.route.stops.length);

const routeHealthText = computed(() => {
    if (!props.route.route_geometry) return 'No saved route geometry.';
    return 'Route data is available and ready to inspect.';
});

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

function initMap() {
    if (!mapEl.value) return;

    const originLat = Number(props.route.origin_lat);
    const originLng = Number(props.route.origin_lng);

    map.value = new mapboxgl.Map({
        container: mapEl.value,
        style: 'mapbox://styles/mapbox/standard',
        config: {
            basemap: {
                lightPreset:
                    resolvedAppearance.value === 'dark' ? 'night' : 'day',
            },
        },
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
                        'line-color':
                            resolvedAppearance.value === 'dark'
                                ? '#3b82f6'
                                : '#2563eb',
                    },
                });
            } catch {}
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

watch(resolvedAppearance, (appearance) => {
    if (!map.value?.isStyleLoaded()) return;

    map.value.setConfigProperty(
        'basemap',
        'lightPreset',
        appearance === 'dark' ? 'night' : 'day',
    );

    if (map.value.getLayer('route-line-layer')) {
        map.value.setPaintProperty(
            'route-line-layer',
            'line-color',
            appearance === 'dark' ? '#3b82f6' : '#2563eb',
        );
    }
});

onBeforeUnmount(() => {
    map.value?.remove();
});
</script>

<template>
    <Head :title="route.route_name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full min-h-0 w-full flex-1 flex-col gap-4 lg:flex-row lg:items-stretch"
        >
            <Card class="flex min-h-0 min-w-0 flex-1 flex-col lg:h-full">
                <CardHeader class="flex flex-row items-start gap-3">
                    <Button as-child variant="header-actions" size="icon-text">
                        <Link :href="index().url" aria-label="Back to routes">
                            <RiArrowLeftLine class="h-4 w-4" />
                        </Link>
                    </Button>
                    <div class="flex min-w-0 flex-1 flex-col">
                        <CardTitle class="truncate font-semibold">{{
                            route.route_name
                        }}</CardTitle>
                        <CardDescription
                            >Review the route path, destination, and stop
                            sequence.</CardDescription
                        >
                    </div>
                    <Button as-child variant="header-actions" size="icon-text">
                        <Link
                            :href="edit(route.id).url"
                            aria-label="Edit route"
                        >
                            <RiEditLine class="h-4 w-4" />
                            <span>Edit</span>
                        </Link>
                    </Button>
                </CardHeader>

                <CardContent class="flex min-h-0 flex-1 flex-col gap-4 pt-2">
                    <div class="relative min-h-[420px] flex-1">
                        <div
                            ref="mapEl"
                            class="route-map h-full w-full overflow-hidden rounded-md"
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
                                        >{{ routeHealthText }}</CardDescription
                                    >
                                </CardHeader>
                                <CardContent>
                                    <div
                                        class="flex items-center justify-between gap-2 rounded-md border border-custom-accent-3 bg-custom-accent-3/10 px-3 py-2"
                                    >
                                        <MapPinned
                                            class="h-4 w-4 shrink-0 text-custom-accent-3"
                                        />
                                        <span
                                            class="min-w-0 flex-1 truncate text-sm font-semibold"
                                            >{{ route.destination_name }}</span
                                        >
                                        <CheckCircle2
                                            class="h-4 w-4 shrink-0 text-custom-accent-3"
                                        />
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>

                    <div
                        class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-custom-shadow/80"
                    >
                        <span class="flex items-center gap-1.5"
                            ><span
                                class="h-2 w-2 rounded-full bg-green-600"
                            />Origin</span
                        >
                        <span class="flex items-center gap-1.5"
                            ><span
                                class="h-2 w-2 rounded-full bg-red-600"
                            />Destination</span
                        >
                        <span class="flex items-center gap-1.5"
                            ><span
                                class="h-2 w-2 rounded-full bg-amber-500"
                            />Bus stops</span
                        >
                        <span class="flex items-center gap-1.5"
                            ><span
                                class="h-2 w-2 rounded-full bg-violet-500"
                            />Landmarks</span
                        >
                    </div>
                </CardContent>
            </Card>

            <Card class="min-h-0 lg:flex lg:h-full lg:w-100">
                <CardHeader>
                    <CardTitle>Details</CardTitle>
                    <CardDescription
                        >Route information and stop sequence</CardDescription
                    >
                </CardHeader>

                <CardContent
                    class="no-scrollbar min-h-0 flex-1 space-y-6 overflow-y-auto py-2"
                >
                    <section class="space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="col-span-2 space-y-2">
                                <p class="text-sm font-medium">Route</p>
                                <div
                                    class="flex h-9 items-center rounded-md border border-custom-bg-dark bg-custom-bg px-3 text-sm dark:border-custom-bg-light dark:bg-custom-bg-dark"
                                >
                                    {{ route.route_name }}
                                </div>
                            </div>
                            <div class="space-y-2">
                                <p class="text-sm font-medium">Gate</p>
                                <div
                                    class="flex h-9 items-center rounded-md border border-custom-bg-dark bg-custom-bg px-3 text-sm dark:border-custom-bg-light dark:bg-custom-bg-dark"
                                >
                                    {{ route.gate?.gate_name ?? '—' }}
                                </div>
                            </div>
                            <div class="space-y-2">
                                <p class="text-sm font-medium">Stops</p>
                                <div
                                    class="flex h-9 items-center rounded-md border border-custom-bg-dark bg-custom-bg px-3 text-sm dark:border-custom-bg-light dark:bg-custom-bg-dark"
                                >
                                    {{ totalStops }}
                                </div>
                            </div>
                            <div class="space-y-2">
                                <p class="text-sm font-medium">Distance</p>
                                <div
                                    class="flex h-9 items-center rounded-md border border-custom-bg-dark bg-custom-bg px-3 text-sm dark:border-custom-bg-light dark:bg-custom-bg-dark"
                                >
                                    {{
                                        route.distance_meters
                                            ? fmtDistance(route.distance_meters)
                                            : '—'
                                    }}
                                </div>
                            </div>
                            <div class="space-y-2">
                                <p class="text-sm font-medium">
                                    Travel Duration
                                </p>
                                <div
                                    class="flex h-9 items-center rounded-md border border-custom-bg-dark bg-custom-bg px-3 text-sm dark:border-custom-bg-light dark:bg-custom-bg-dark"
                                >
                                    {{
                                        route.duration_seconds
                                            ? fmtDuration(
                                                  route.duration_seconds,
                                              )
                                            : '—'
                                    }}
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-center gap-3 pt-2">
                                <p class="font-semibold text-custom-accent-3">
                                    Stops
                                    <Badge variant="accent-3">{{
                                        totalStops
                                    }}</Badge>
                                </p>
                                <Separator class="flex-1" />
                            </div>
                            <div v-if="sortedStops.length" class="relative">
                                <div
                                    class="absolute top-6 bottom-6 left-[18px] w-px bg-slate-200"
                                />
                                <div class="space-y-1">
                                    <div
                                        v-for="(stop, stopIndex) in sortedStops"
                                        :key="stop.id"
                                        class="group flex items-start gap-3 rounded-lg p-2 transition-colors hover:bg-muted/40"
                                    >
                                        <div
                                            :class="[
                                                'relative z-10 flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[9px] font-bold text-white ring-2 ring-background',
                                                stop.stop_type === 'origin'
                                                    ? 'bg-green-600'
                                                    : stop.stop_type ===
                                                        'destination'
                                                      ? 'bg-red-600'
                                                      : stop.stop_type ===
                                                          'landmark'
                                                        ? 'bg-violet-500'
                                                        : 'bg-amber-500',
                                            ]"
                                        >
                                            {{ stopIndex + 1 }}
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
                                                    :class="[
                                                        'shrink-0 rounded px-1.5 py-0.5 text-[10px] font-medium',
                                                        stopTypeBadgeClass(
                                                            stop.stop_type,
                                                        ),
                                                    ]"
                                                    >{{
                                                        stopTypeLabel(
                                                            stop.stop_type,
                                                        )
                                                    }}</span
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
                                    </div>
                                </div>
                            </div>
                            <div
                                v-else
                                class="rounded-md border border-dashed border-custom-bg-dark p-3 text-center text-sm text-custom-shadow/80 dark:border-custom-bg-light"
                            >
                                No stops available for this route.
                            </div>
                        </div>

                        <div class="space-y-4 pb-2">
                            <div class="flex items-center gap-3 pt-2">
                                <p class="font-semibold text-custom-accent-3">
                                    Activity
                                </p>
                                <Separator class="flex-1" />
                            </div>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <p class="text-xs text-custom-shadow/80">
                                        Created
                                    </p>
                                    <p>{{ route.created_at_human ?? '—' }}</p>
                                    <p
                                        v-if="route.creator"
                                        class="text-xs text-muted-foreground"
                                    >
                                        by {{ route.creator.name }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-custom-shadow/80">
                                        Last updated
                                    </p>
                                    <p>{{ route.updated_at_human ?? '—' }}</p>
                                    <p
                                        v-if="route.updater"
                                        class="text-xs text-muted-foreground"
                                    >
                                        by {{ route.updater.name }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <Separator class="mt-4" />
                        <div class="flex items-center justify-end gap-2">
                            <Button as-child variant="float" size="icon-text"
                                ><Link :href="edit(route.id).url"
                                    ><RiEditLine class="h-4 w-4" />Edit</Link
                                ></Button
                            >
                            <Button
                                variant="destructive"
                                size="icon-text"
                                @click="openArchiveDialog"
                                ><Archive class="h-4 w-4" />Archive</Button
                            >
                        </div>
                    </section>
                </CardContent>
            </Card>

            <Dialog :open="archiveOpen" @update:open="archiveOpen = $event">
                <DialogContent class="p-4 sm:max-w-md">
                    <DialogHeader
                        ><DialogTitle>Archive Route</DialogTitle
                        ><DialogDescription
                            >Are you sure you want to archive
                            <span class="font-semibold text-custom-accent-3">{{
                                route.route_name
                            }}</span
                            >? This action will remove it from active
                            records.</DialogDescription
                        ></DialogHeader
                    >
                    <DialogFooter class="gap-2 sm:justify-end">
                        <Button variant="outline" @click="archiveOpen = false"
                            >Cancel</Button
                        >
                        <Button variant="destructive" @click="archiveRoute"
                            ><ArchiveX class="h-4 w-4" />Archive</Button
                        >
                    </DialogFooter>
                </DialogContent>
            </Dialog>
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
