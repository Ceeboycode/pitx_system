<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { ArchivedNotice } from '@/components/ui/_archived-notice';
import { Head } from '@inertiajs/vue3';
import {
    ref
} from 'vue';

import {
    RiArchive2Line,
    RiDashboardHorizontalLine,
    RiFileListLine,
    RiRoadMapLine,
    RiAlertLine,
    RiBusLine,
    RiShutDownLine,
    RiHistoryLine,
} from 'vue-remix-icons';

import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import ArchiveRouteDialog from '@/components/internal/route/ArchiveRouteDialog.vue';
import ToggleRouteStatusDialog from '@/components/internal/route/ToggleRouteStatusDialog.vue';
import { can } from '@/lib/can';

import {
    edit,
    index,
} from '@/actions/App/Http/Controllers/RouteController';
import type { BreadcrumbItem } from '@/types';

// import mapboxgl from 'mapbox-gl';
// import 'mapbox-gl/dist/mapbox-gl.css';
import { LeadPanel, PanelLayout, SidePanel } from '@/components/ui/_panels';
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
import History from '@/components/internal/route/edit/HistoryTab.vue';

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
    mapbox_feature_id: string | null;
    stop_order: number;
};

type RouteModel = {
    id: number;
    route_name: string;
    status: 'active' | 'inactive';
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
    isArchived?: boolean;
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

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Routes', href: index().url },
    { title: props.route.route_name, href: edit(props.route.id).url },
];

const canArchiveRoute = can('routes.archive');
const canUpdateRoute = can('routes.update');
const archiveOpen = ref(false);
const toggleOpen = ref(false);

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
    {
        value: 'history',
        label: 'History',
        icon: RiHistoryLine,
        component: History,
    }
] as const;

</script>

<template>
    <Head :title="`Route — ${route.route_name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <PanelLayout>
            <LeadPanel>
                <ArchivedNotice v-if="props.isArchived" entity="route" />
                <LeadingCard
                    :title="route.route_name"
                    description="Update the route, map its path, and organize its stops."
                    variant="entity-details"
                    :back="index().url"
                    :status="route.status"
                >
                    <DropdownMenuItem
                        class="group cursor-pointer"
                        :disabled="!canUpdateRoute"
                        @click="toggleOpen = true"
                    >
                        <RiShutDownLine class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                        {{ route.status === 'active' ? 'Inactivate' : 'Activate' }}
                    </DropdownMenuItem>
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
            
            <SidePanel>
                
            </SidePanel>
        </PanelLayout>
        
        <ArchiveRouteDialog v-model:open="archiveOpen" :route="route" />
        <ToggleRouteStatusDialog v-model:open="toggleOpen" :route="route" />
    </AppLayout>
</template>