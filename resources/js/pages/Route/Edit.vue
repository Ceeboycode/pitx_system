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
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';

import {
    RiAiGenerate,
    RiArchive2Line,
    RiCheckboxCircleLine,
    RiCloseLine,
    RiDraggable,
    RiMapPin2Line,
    RiSearchLine,
    RiDashboardHorizontalLine,
    RiFileListLine,
    RiRoadMapLine,
    RiAlertLine,
    RiBusLine,
} from 'vue-remix-icons';

import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import ArchiveRouteDialog from '@/components/internal/route/ArchiveRouteDialog.vue';
import { can } from '@/lib/can';

import {
    edit,
    index,
    update,
} from '@/actions/App/Http/Controllers/RouteController';
import type { BreadcrumbItem } from '@/types';

// import mapboxgl from 'mapbox-gl';
// import 'mapbox-gl/dist/mapbox-gl.css';
import { LeadPanel } from '@/components/ui/_panels';
import { LeadingCard } from '@/components/ui/_leading-card';
import { 
    Tabs,
    TabsContent,
    TabsList,
    TabsTrigger,
} from '@/components/ui/_tabs';
import Overview from '@/components/internal/route/edit/OverviewTab.vue';
import Details from '@/components/internal/route/edit/DetailsTab.vue';
import Vehicles from '@/components/internal/route/edit/VehiclesTab.vue';
import Dispatches from '@/components/internal/route/edit/DispatchesTab.vue';
import IncidentReports from '@/components/internal/route/edit/IncidentReportsTab.vue';

type Gate = {
    id: number;
    gate_name: string;
};

// type SearchSuggestion = {
//     id: string;
//     name: string;
//     full_address: string;
//     latitude: number;
//     longitude: number;
// };

// type StopItem = {
//     stop_name: string;
//     stop_type: 'origin' | 'stop' | 'destination' | 'landmark';
//     address: string | null;
//     latitude: number;
//     longitude: number;
//     mapbox_feature_id: string | null;
//     stop_order: number;
// };

// type AlternativeRoute = {
//     index: number;
//     geometry: GeoJSON.LineString;
//     distance: number;
//     duration: number;
//     coordinates: [number, number][];
// };

// type Waypoint = {
//     lng: number;
//     lat: number;
// };

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

// mapboxgl.accessToken = props.mapConfig.mapboxToken;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Routes', href: index().url },
    { title: props.route.route_name, href: edit(props.route.id).url },
];

const canArchiveRoute = can('routes.archive');
const archiveOpen = ref(false);

// const loadingDestination = ref(false);
// const loadingStopSearch = ref(false);
// const loadingAutoGenerate = ref(false);
// const loadingLandmarks = ref(false);

// const landmarkSuggestions = ref<SearchSuggestion[]>([]);
// const showLandmarks = ref(false);

// const showAlternatives = ref(false);

// const originalPrimaryRoute = computed(() => allRouteOptions.value[0] ?? null);
// const alternativeRoutes = computed(() => allRouteOptions.value.slice(1));

// const defaultRouteName = computed(() => {
//     if (!form.destination_name) return form.origin_name;
//     return `${form.origin_name} → ${form.destination_name}`;
// });

// const totalVisibleStops = computed(() => {
//     if (!hasDestination.value) return 1;
//     return form.stops.length + 2;
// });

// const selectedGate = computed(
//     () => props.gates.find((g) => String(g.id) === form.gate_id) ?? null,
// );

// watch(
//     () => defaultRouteName.value,
//     (value) => {
//         if (!routeNameTouched.value) {
//             form.route_name = value;
//         }
//     },
//     { immediate: true },
// );

// function resetRouteNameToDefault() {
//     routeNameTouched.value = false;
//     form.route_name = defaultRouteName.value;
// }

// function isDuplicateStopCandidate(lng: number, lat: number, name: string) {
//     const normalizedName = name.toLowerCase().trim();

//     return form.stops.some((stop) => {
//         const sameName = stop.stop_name.toLowerCase().trim() === normalizedName;
//         const nearSameSpot =
//             haversine([stop.longitude, stop.latitude], [lng, lat]) < 80;

//         return sameName || nearSameSpot;
//     });
// }

// const MAX_STOP_DISTANCE_M = 500;
// const MAX_LANDMARK_DISTANCE_M = 900;

// function removeAllWaypoints() {
//     waypoints.value = [];
//     clearWaypointMarkers();
//     redrawRoute();
// }

// async function suggestLandmarks() {
//     if (!hasDestination.value || routeCoordinates.value.length < 2) return;

//     loadingLandmarks.value = true;
//     showLandmarks.value = true;
//     landmarkSuggestions.value = [];

//     try {
//         const routeDistanceKm = Math.max(1, (form.distance_meters ?? 0) / 1000);

//         const intervalKm =
//             routeDistanceKm <= 8 ? 2 : routeDistanceKm <= 20 ? 4 : 6;

//         const sampled = sampleRouteAtIntervals(
//             routeCoordinates.value,
//             intervalKm,
//         );

//         const searchPoints: [number, number][] = [
//             [origin.lng, origin.lat],
//             ...sampled.slice(0, 5),
//             [form.destination_lng!, form.destination_lat!],
//         ];

//         const seenIds = new Set<string>();
//         const results: SearchSuggestion[] = [];

//         for (const [lng, lat] of searchPoints) {
//             const url = new URL(
//                 'https://api.mapbox.com/geocoding/v5/mapbox.places/terminal,bus stop,station,market,hospital,mall,plaza,school.json',
//             );

//             url.searchParams.set('access_token', mapboxgl.accessToken);
//             url.searchParams.set('proximity', `${lng},${lat}`);
//             url.searchParams.set('limit', '6');
//             url.searchParams.set('country', 'ph');
//             url.searchParams.set('language', 'en');
//             url.searchParams.set('types', 'poi');

//             const res = await fetch(url.toString());
//             const data = await res.json();

//             for (const f of data.features ?? []) {
//                 const candidate: SearchSuggestion = {
//                     id: f.id,
//                     name: f.text || f.place_name,
//                     full_address: f.place_name,
//                     longitude: f.center[0],
//                     latitude: f.center[1],
//                 };

//                 if (seenIds.has(candidate.id)) continue;

//                 const snapped = snapToRoute(
//                     candidate.longitude,
//                     candidate.latitude,
//                 );
//                 const distanceFromRoute = haversine(snapped, [
//                     candidate.longitude,
//                     candidate.latitude,
//                 ]);

//                 if (distanceFromRoute > MAX_LANDMARK_DISTANCE_M) continue;

//                 if (
//                     isDuplicateStopCandidate(
//                         candidate.longitude,
//                         candidate.latitude,
//                         candidate.name,
//                     )
//                 ) {
//                     continue;
//                 }

//                 if (
//                     candidate.name.toLowerCase().trim() ===
//                     form.destination_name.toLowerCase().trim()
//                 ) {
//                     continue;
//                 }

//                 seenIds.add(candidate.id);
//                 results.push(candidate);
//             }
//         }

//         landmarkSuggestions.value = results.slice(0, 10);

//         if (!landmarkSuggestions.value.length) {
//             lineClickMessage.value =
//                 'No nearby landmark suggestions were found for this route.';
//         } else {
//             lineClickMessage.value =
//                 'Landmark suggestions are ready. Add the ones you want as stops.';
//         }
//     } finally {
//         loadingLandmarks.value = false;
//     }
// }

// async function addLandmarkAsStop(item: SearchSuggestion) {
//     await addStopFromSuggestion(item, 'landmark');
//     landmarkSuggestions.value = landmarkSuggestions.value.filter(
//         (landmark) => landmark.id !== item.id,
//     );
// }

// function clearDestination() {
//     form.destination_name = '';
//     form.destination_lat = null;
//     form.destination_lng = null;

//     destinationQuery.value = '';
//     destinationSuggestions.value = [];

//     form.distance_meters = null;
//     form.duration_seconds = null;
//     form.route_geometry = null;
//     form.stops = [];

//     lineClickMessage.value = '';
//     routeCoordinates.value = [];

//     landmarkSuggestions.value = [];
//     showLandmarks.value = false;

//     allRouteOptions.value = [];
//     showAlternatives.value = false;
//     selectedRouteIndex.value = 0;

//     waypoints.value = [];
//     stopQuery.value = '';
//     stopSuggestions.value = [];

//     destinationMarker.value?.remove();
//     destinationMarker.value = null;

//     clearStopMarkers();
//     clearWaypointMarkers();
//     clearRouteLine();
//     clearAlternativeRouteLayers();

//     if (!routeNameTouched.value) {
//         form.route_name = form.origin_name;
//     }
// }

const tabs = [
    {
        value: 'overview',
        label: 'Overview',
        icon: RiDashboardHorizontalLine,
        component: Overview,
    },
    {
        value: 'details',
        label: 'Details',
        icon: RiFileListLine,
        component: Details,
    },
    {
        value: 'vehicles',
        label: 'Vehicles',
        icon: RiBusLine,
        component: Vehicles,
    },
    {
        value: 'dispatches',
        label: 'Dispatches',
        icon: RiRoadMapLine,
        component: Dispatches,
    },
    {
        value: 'incident-reports',
        label: 'Incident Reports',
        icon: RiAlertLine,
        component: IncidentReports,
    },
] as const;

</script>

<template>
    <Head :title="`Route — ${route.route_name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <LeadPanel>
            <LeadingCard
                :title="route.route_name"
                description="Update the route, map its path, and organize its stops."
                variant="entity-details"
                :back="index().url"
            >
                <DropdownMenuItem
                    class="group cursor-pointer"
                    :disabled="!canArchiveRoute"
                    @click="archiveOpen = true"
                >
                    <RiArchive2Line class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                    Archive
                </DropdownMenuItem>
            </LeadingCard>
            <Tabs default-value="details">
                <TabsList>
                    <TabsTrigger v-for="tab in tabs" :key="tab.value" :value="tab.value">
                        <component :is="tab.icon" class="h-4 w-4"/>
                        <span>{{ tab.label }}</span>
                    </TabsTrigger>
                </TabsList>
                <TabsContent v-for="tab in tabs" :key="tab.value" :value="tab.value">
                    <component :is="tab.component" :route="route" :gates="gates" :map-config="mapConfig" />
                </TabsContent>
            </Tabs>
        </LeadPanel>

        <ArchiveRouteDialog v-model:open="archiveOpen" :route="route" />
    </AppLayout>
</template>