<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { ArchivedNotice } from '@/components/ui/_archived-notice';
import { index, toggleStatus } from '@/routes/vehicles';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

import RouteMapDialog from '@/components/routes/RouteMapDialog.vue';
import {
    RouteStopsDialog,
    VehicleArchiveDialog,
} from '@/components/internal/vehicles';
import {
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
} from '@/components/ui/dialog';
import {
    DropdownMenuItem,
} from '@/components/ui/dropdown-menu';
import { can } from '@/lib/can';
import { vehicleDocumentPreview } from '@/lib/document-preview';
import { DocumentPreviewCard } from '@/components/internal/preview-cards';

import {
    RiArchive2Line,
    RiCloseLine,
    RiDashboardHorizontalLine,
    RiFileListLine,
    RiRoadMapLine,
    RiFolderLine,
    RiAlertLine,
    RiHistoryLine,
} from "vue-remix-icons";
import { PanelLayout, LeadPanel, SidePanel } from '@/components/ui/_panels';
import { LeadingCard } from '@/components/ui/_leading-card';
import { 
    Tabs,
    TabsContent,
    TabsList,
    TabsTrigger,
} from '@/components/ui/_tabs';

import Overview from '@/components/internal/vehicles/show/OverviewTab.vue';
import Details from '@/components/internal/vehicles/show/DetailsTab.vue';
import Documents from '@/components/internal/vehicles/show/DocumentsTab.vue';
import Dispatches from '@/components/internal/vehicles/show/DispatchesTab.vue';
import IncidentReports from '@/components/internal/vehicles/show/IncidentReportsTab.vue';
import History from '@/components/internal/vehicles/show/HistoryTab.vue';

type UserMini = { id?: number; name: string };

type VehicleDocument = {
    id: number;
    document_type: string;
    file_name?: string | null;
    file_mime_type?: string | null;
    file_size?: number | null;
    file_url?: string | null;
    status?: string | null;
    issued_at?: string | null;
    expires_at?: string | null;
    remarks?: string | null;
    created_at?: string | null;
    updated_at?: string | null;
};

type RouteStop = {
    id: number;
    stop_name: string;
    stop_type: 'origin' | 'stop' | 'destination' | 'landmark' | string;
    address: string | null;
    latitude: number;
    longitude: number;
    stop_order: number;
};

type RouteData = {
    id: number;
    route_name: string;
    gate: { id: number; gate_name: string } | null;
    origin_name: string;
    origin_lat: number;
    origin_lng: number;
    destination_name: string;
    destination_lat: number;
    destination_lng: number;
    distance_meters: number | null;
    duration_seconds: number | null;
    route_geometry: string | null;
    status: string | null;
    stops: RouteStop[];
} | null;

type VehicleModel = {
    id: number;
    vehicle_type_id?: number | null;
    vehicle_type?: string | null;
    plate_number?: string | null;
    body_number?: string | null;
    capacity?: string | number | null;
    color?: string | null;
    engine_number?: string | null;
    chassis_number?: string | null;
    make_model?: string | null;
    status?: string | null;
    verification_status?: string | null;
    docs_status?: string | null;
    verification_remark?: string | null;
    operator_remark?: string | null;
    suspension_remark?: string | null;
    created_at?: string | null;
    updated_at?: string | null;
    deleted_at?: string | null;
    company?: {
        id: number;
        company_name: string;
        company_code?: string | null;
        company_email?: string | null;
        company_phone?: string | null;
        company_address?: string | null;
    } | null;
    route?: RouteData;
    documents?: VehicleDocument[];
    creator?: UserMini | null;
    updater?: UserMini | null;
    deleter?: UserMini | null;
};

const props = defineProps<{
    isArchived?: boolean;
    vehicle: VehicleModel;
    mapConfig: { mapboxToken: string };
}>();

const vehicle = computed(() => props.vehicle);
const company = computed(() => props.vehicle.company ?? null);
const route = computed(() => props.vehicle.route ?? null);
const docs = computed(() => props.vehicle.documents ?? []);

const canArchiveVehicle = computed(() => can('vehicles.archive'));
const canUpdateVehicleStatus = computed(() => can('vehicles.toggleStatus'));
const archiveOpen = ref(false);

const VEHICLES_INDEX_URL = index().url;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Vehicles', href: VEHICLES_INDEX_URL },
    {
        title: vehicle.value.plate_number || `Vehicle #${vehicle.value.id}`,
        href: '#',
    },
];

const canViewVehicle = can('vehicles.view');


const sortedStops = computed(() =>
    [...(route.value?.stops ?? [])].sort((a, b) => a.stop_order - b.stop_order),
);

const verifiedCount = computed(
    () => docs.value.filter((d) => d.status === 'verified').length,
);
const pendingCount = computed(
    () => docs.value.filter((d) => d.status === 'pending').length,
);
const invalidCount = computed(
    () => docs.value.filter((d) => d.status === 'invalid').length,
);
const expiredCount = computed(
    () => docs.value.filter((d) => isExpired(d.expires_at)).length,
);
const actionRequiredCount = computed(
    () =>
        docs.value.filter(
            (d) =>
                d.status === 'pending' ||
                d.status === 'invalid' ||
                isExpired(d.expires_at),
        ).length,
);
const docsCompletionRate = computed(() => {
    if (!docs.value.length) return 0;
    return Math.round((verifiedCount.value / docs.value.length) * 100);
});

const docHealthSummary = computed(() => {
    const parts = [];
    parts.push(`${verifiedCount.value} verified`);
    if (pendingCount.value) parts.push(`${pendingCount.value} pending`);
    if (invalidCount.value) parts.push(`${invalidCount.value} invalid`);
    if (expiredCount.value) parts.push(`${expiredCount.value} expired`);
    return parts.join(' · ');
});

const vehicleMeta = computed(() => [
    { label: 'Vehicle Type', value: vehicle.value.vehicle_type ?? '—' },
    { label: 'Plate Number', value: vehicle.value.plate_number || '—' },
    { label: 'Body Number', value: vehicle.value.body_number || '—' },
    { label: 'Capacity', value: vehicle.value.capacity || '—' },
    { label: 'Color', value: vehicle.value.color || '—' },
    { label: 'Make / Model', value: vehicle.value.make_model || '—' },
    {
        label: 'Route',
        value: route.value?.route_name || 'No route assigned',
        helper: route.value?.gate?.gate_name
            ? `Gate: ${route.value.gate.gate_name}`
            : null,
    },
    {
        label: 'Status',
        value: humanize(vehicle.value.status),
        helper: vehicle.value.suspension_remark || vehicle.value.operator_remark || null,
    },
]);

const statusForm = useForm({
    status: props.vehicle.status ?? 'active',
    suspension_remark: props.vehicle.suspension_remark ?? '',
});

const statusRequiresReason = computed(
    () => statusForm.status === 'suspended',
);

function submitStatusUpdate() {
    statusForm.patch(toggleStatus(vehicle.value.id).url, {
        preserveScroll: true,
    });
}

function humanize(text?: string | null) {
    if (!text) return '—';
    return text.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function requiredLabel(type?: string | null) {
    switch (type) {
        case 'ltfrb_certificate':
            return 'LTFRB Certificate';
        case 'cpc':
            return 'Certificate of Public Convenience';
        case 'or_cr':
            return 'OR / CR';
        default:
            return humanize(type);
    }
}

function formatDate(date?: string | null) {
    if (!date) return '—';
    const d = new Date(date);
    if (Number.isNaN(d.getTime())) return '—';
    return d.toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function formatDateTime(date?: string | null) {
    if (!date) return '—';
    const d = new Date(date);
    if (Number.isNaN(d.getTime())) return '—';
    return d.toLocaleString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
    });
}

function fmtDistance(m?: number | null) {
    if (!m) return '—';
    return m < 1000 ? `${Math.round(m)} m` : `${(m / 1000).toFixed(2)} km`;
}

function fmtDuration(s?: number | null) {
    if (!s) return '—';
    const h = Math.floor(s / 3600);
    const m = Math.ceil((s % 3600) / 60);
    return h > 0 ? `${h}h ${m}m` : `${Math.ceil(s / 60)}m`;
}

function formatBytes(value?: number | null) {
    if (!value) return '—';
    const units = ['B', 'KB', 'MB', 'GB'];
    let size = value;
    let unitIndex = 0;

    while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex++;
    }

    return `${size.toFixed(size >= 10 || unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
}

function isExpired(expiresAt?: string | null) {
    if (!expiresAt) return false;
    const d = new Date(expiresAt);
    return !Number.isNaN(d.getTime()) && d.getTime() < Date.now();
}

function statusVariant(
    s?: string | null,
): 'success' | 'warning' | 'destructive' | 'outline' {
    switch (s) {
        case 'verified':
        case 'active':
        case 'complete':
            return 'default'
        case 'pending':
        case 'draft':
        case 'for_verification':
        case 'partial':
            return 'secondary'
        case 'invalid':
        case 'expired':
        case 'needs_revision':
        case 'none':
            return 'destructive';
        default:
            return 'outline';
    }
}

function statusClass(status?: string | null): string {
    switch (status) {
        case 'verified':
        case 'active':
        case 'complete':
            return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'pending':
        case 'draft':
        case 'for_verification':
        case 'partial':
            return 'bg-amber-100 text-amber-700 border-amber-200';
        case 'invalid':
        case 'expired':
        case 'needs_revision':
        case 'none':
            return 'bg-rose-100 text-rose-600 border-rose-200';
        default:
            return 'bg-slate-100 text-slate-500 border-0';
    }
}

function statusDot(status?: string | null): string {
    switch (status) {
        case 'verified':
        case 'active':
        case 'complete':
            return 'bg-emerald-500';
        case 'pending':
        case 'draft':
        case 'for_verification':
        case 'partial':
            return 'bg-amber-500';
        case 'invalid':
        case 'expired':
        case 'needs_revision':
        case 'none':
            return 'bg-rose-500';
        default:
            return 'bg-slate-400';
    }
}

const mapDialogOpen = ref(false);
const parsedRouteGeometry = computed(() => {
    if (!route.value?.route_geometry) return null;

    try {
        const parsed = JSON.parse(route.value.route_geometry);

        if (parsed?.type === 'LineString') return parsed;
        if (
            parsed?.type === 'Feature' &&
            parsed.geometry?.type === 'LineString'
        ) {
            return parsed.geometry;
        }
    } catch {
        return null;
    }

    return null;
});

const routeMapStops = computed(() =>
    sortedStops.value.map((stop) => ({
        id: stop.id,
        stop_name: stop.stop_name,
        stop_order: stop.stop_order,
        stop_type: stop.stop_type,
        address: stop.address,
        latitude: stop.latitude,
        longitude: stop.longitude,
    })),
);


const stopsDialogOpen = ref(false);

const activeTab = ref('details');

// The Documents tab picks the document; the page shows it in the side panel. Held as an id so the
// card follows the fresh data after a document is verified, and only while that tab is open.
const previewedDocumentId = ref<number | null>(null);
const previewedDocument = computed(() => {
    const doc = docs.value.find((document) => document.id === previewedDocumentId.value);

    return doc ? vehicleDocumentPreview(doc) : null;
});

watch(activeTab, (tab) => {
    if (tab !== 'documents') previewedDocumentId.value = null;
});

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
        value: 'documents',
        label: 'Documents',
        icon: RiFolderLine,
        component: Documents,
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
    <Head :title="vehicle.plate_number || `Vehicle #${vehicle.id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <PanelLayout>
            <LeadPanel class="min-w-0 shrink">
                <ArchivedNotice v-if="props.isArchived" entity="vehicle" />
                <LeadingCard
                    :title="vehicle.plate_number || `Vehicle #${vehicle.id}`"
                    description="View vehicle information, documents, and route details."
                    variant="entity-details"
                    :back="VEHICLES_INDEX_URL"
                    entity="Vehicles"
                    :status="vehicle.status"
                >
                    <DropdownMenuItem as-child class="group cursor-pointer">
                        <Button v-if="canArchiveVehicle" variant="dropdown" @click="archiveOpen = true">
                            <RiArchive2Line class="h-4 w-4 shrink-0 text-custom-shadow group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow transition-all duration-200" />
                            <span>Archive {{ vehicle.plate_number || `Vehicle #${vehicle.id}` }}</span>
                        </Button>
                        <!-- <Button
                            as-child
                            variant="outline"
                            class="rounded-lg bg-card border-slate-200 text-slate-600 hover:bg-slate-100 cursor-pointer"
                        >
                            <Link :href="VEHICLES_INDEX_URL">
                                <RiArrowLeftLine class="h-4 w-4" />
                            </Link>
                        </Button> -->
                    </DropdownMenuItem>
                </LeadingCard>
                <Tabs v-model="activeTab">
                    <TabsList>
                        <TabsTrigger v-for="tab in tabs" :key="tab.value" :value="tab.value">
                            <component :is="tab.icon" class="h-4 w-4 shrink-0"/>
                            <span>{{ tab.label }}</span>
                        </TabsTrigger>
                    </TabsList>
                    <TabsContent v-for="tab in tabs" :key="tab.value" :value="tab.value">
                        <Documents v-if="tab.value === 'documents'" v-model:previewed-id="previewedDocumentId" :vehicle="vehicle" />
                        <component :is="tab.component" v-else :vehicle="vehicle" :map-config="mapConfig" />
                    </TabsContent>
                </Tabs>
            </LeadPanel>
            <SidePanel v-if="previewedDocument" class="hidden lg:flex">
                <DocumentPreviewCard :doc="previewedDocument" @close="previewedDocumentId = null" />
            </SidePanel>
        </PanelLayout>

        <RouteMapDialog
            v-if="canViewVehicle"
            v-model:open="mapDialogOpen"
            :route-name="route?.route_name"
            :origin-name="route?.origin_name"
            :destination-name="route?.destination_name"
            :route-geometry="parsedRouteGeometry"
            :stops="routeMapStops"
            :mapbox-token="props.mapConfig.mapboxToken"
            :default-center="{ lng: 120.9842, lat: 14.5995 }"
            :default-zoom="11"
        />


        <RouteStopsDialog v-model:open="stopsDialogOpen" :stops="sortedStops" />



        <VehicleArchiveDialog v-model:open="archiveOpen" :vehicle="vehicle" />


    </AppLayout>
</template>
