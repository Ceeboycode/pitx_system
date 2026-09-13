<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { destroy, index, toggleStatus } from '@/routes/vehicles';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';

import RouteMapDialog from '@/components/routes/RouteMapDialog.vue';
import {
    DocumentPreviewDialog,
    InvalidateDocumentDialog,
    RouteStopsDialog,
} from '@/components/vehicles/show';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Textarea } from '@/components/ui/textarea';
import { can } from '@/lib/can';

import {
    Archive,
    ArrowLeft,
    CheckCircle2,
    CircleHelp,
    Download,
    Eye,
    File,
    FileText,
    ListChecks,
    Mail,
    Map,
    MapPin,
    MessageSquareText,
    MoreHorizontal,
    Phone,
    RotateCcw,
    Route as RouteIcon,
    Truck,
    X,
    XCircle,
} from 'lucide-vue-next';
import {
    RiArchive2Line,
    RiCloseLine,
    RiDashboardHorizontalLine,
    RiFileListLine,
    RiRoadMapLine,
    RiFolderLine,
    RiAlertLine,
} from "vue-remix-icons";
import { PanelLayout, LeadPanel } from '@/components/ui/_panels';
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
const canVerifyVehicleDocument = can('vehicle_documents.verify');
const canUnverifyVehicleDocument = can('vehicle_documents.unverify');
const canInvalidateVehicleDocument = can('vehicle_documents.invalidate');

function archiveVehicle() {
    router.delete(destroy(vehicle.value.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            archiveOpen.value = false;
            toast.success('Vehicle archived successfully.');
        },
        onError: () => toast.error('Failed to archive vehicle.'),
    });
}

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
    { label: 'Vehicle Type', value: humanize(vehicle.value.vehicle_type) },
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

function fileUrl(doc: VehicleDocument) {
    return doc.file_url ?? '';
}

function isImage(doc: VehicleDocument) {
    if (doc.file_mime_type) return doc.file_mime_type.startsWith('image/');
    return ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(
        (doc.file_name ?? '').split('.').pop()?.toLowerCase() ?? '',
    );
}

function isPdf(doc: VehicleDocument) {
    if (doc.file_mime_type) return doc.file_mime_type === 'application/pdf';
    return (doc.file_name ?? '').split('.').pop()?.toLowerCase() === 'pdf';
}

function canPreview(doc: VehicleDocument) {
    return Boolean(fileUrl(doc)) && (isImage(doc) || isPdf(doc));
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


const previewOpen = ref(false);
const previewDoc = ref<VehicleDocument | null>(null);

function openPreview(doc: VehicleDocument) {
    if (!canPreview(doc)) return;
    previewDoc.value = doc;
    previewOpen.value = true;
}

function closePreview() {
    previewOpen.value = false;
    previewDoc.value = null;
}


const actionForm = useForm({});
const confirmOpen = ref(false);
const actionType = ref<'verify' | 'unverify'>('verify');
const actionDoc = ref<VehicleDocument | null>(null);

function openConfirm(type: 'verify' | 'unverify', doc: VehicleDocument) {
    actionType.value = type;
    actionDoc.value = doc;
    confirmOpen.value = true;
}

function submitConfirm() {
    if (!actionDoc.value) return;

    const url =
        actionType.value === 'verify'
            ? `/vehicles/${vehicle.value.id}/documents/${actionDoc.value.id}/verify`
            : `/vehicles/${vehicle.value.id}/documents/${actionDoc.value.id}/unverify`;

    actionForm.patch(url, {
        preserveScroll: true,
        onSuccess: () => {
            confirmOpen.value = false;
            actionDoc.value = null;
            closePreview();
        },
    });
}


const invalidateOpen = ref(false);

function openInvalidate(doc: VehicleDocument) {
    actionDoc.value = doc;
    invalidateOpen.value = true;
}

const selectMode = ref(false);
const selectedDocIds = ref<number[]>([]);

function toggleSelectMode() {
    selectMode.value = !selectMode.value;
    if (!selectMode.value) selectedDocIds.value = [];
}

function setDoc(id: number, checked: boolean) {
    const idx = selectedDocIds.value.indexOf(id);
    if (checked && idx === -1) selectedDocIds.value = [...selectedDocIds.value, id];
    else if (!checked && idx !== -1) selectedDocIds.value = selectedDocIds.value.filter((x) => x !== id);
}

const allSelected = computed(
    () => docs.value.length > 0 && selectedDocIds.value.length === docs.value.length,
);

function selectAll() {
    if (allSelected.value) {
        selectedDocIds.value = [];
    } else {
        selectedDocIds.value = docs.value.map((d) => d.id);
    }
}

function downloadSelected() {
    if (selectedDocIds.value.length === 0) return;
    for (const id of selectedDocIds.value) {
        const doc = docs.value.find((d) => d.id === id);
        if (!doc || !fileUrl(doc)) continue;
        const a = document.createElement('a');
        a.href = fileUrl(doc);
        a.setAttribute('download', doc.file_name ?? '');
        a.style.display = 'none';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }
    selectMode.value = false;
    selectedDocIds.value = [];
}

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
] as const;
</script>

<template>
    <Head :title="vehicle.plate_number || `Vehicle #${vehicle.id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <LeadPanel>
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
                        <RiArchive2Line class="h-4 w-4 text-custom-shadow group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow transition-all duration-200" />
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
            <Tabs default-value="details">
                <TabsList>
                    <TabsTrigger v-for="tab in tabs" :key="tab.value" :value="tab.value">
                        <component :is="tab.icon" class="h-4 w-4"/>
                        <span>{{ tab.label }}</span>
                    </TabsTrigger>
                </TabsList>
                <TabsContent v-for="tab in tabs" :key="tab.value" :value="tab.value">
                    <component :is="tab.component" :vehicle="vehicle" :map-config="mapConfig" />
                </TabsContent>
            </Tabs>
        <!-- </LeadPanel> -->

            <div class="grid gap-4 lg:grid-cols-3 h-fit">

                <div class="grid gap-4 col-span-2 h-fit">
                    <!-- <Card>
                        <CardHeader class="flex items-center justify-between">
                            <div>
                                <CardTitle>Documents</CardTitle>
                                <p class="text-sm text-muted-foreground">
                                    Review every document and update its status.
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <Badge :class="statusClass('verified')">{{ verifiedCount }}/{{ docs.length }} verified</Badge>
                                <Badge v-if="actionRequiredCount > 0" :class="statusClass('pending')">{{ actionRequiredCount }} need action</Badge>
                                <Badge v-if="expiredCount > 0" :class="statusClass('expired')">{{ expiredCount }} expired</Badge>
                            </div>
                        </CardHeader>

                        <CardContent class="border-t border-slate-100">
                            
                            <div
                                v-if="docs.length === 0"
                                class="flex flex-col items-center gap-3 py-20 text-center"
                            >
                                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-muted">
                                    <FileText class="h-6 w-6 text-muted-foreground/40" />
                                </div>
                                <div>
                                    <p class="text-sm font-semibold">No documents uploaded yet.</p>
                                    <p class="mt-0.5 text-xs text-muted-foreground">
                                        Documents submitted by the company will appear here.
                                    </p>
                                </div>
                            </div>

                            
                            <div v-else>
                                <div class="flex justify-between py-4">
                                    <div class="flex gap-2">
                                        <Button
                                            variant="outline"
                                            class="group/segment shrink-0 rounded-lg cursor-pointer gap-0 hover:bg-slate-100 text-slate-600"
                                            @click="toggleSelectMode"
                                        >
                                            <X v-if="selectMode" class="h-4 w-4 shrink-0" />
                                            <ListChecks v-else class="h-4 w-4 shrink-0" />
                                            <span v-if="!selectMode" class="max-w-0 overflow-hidden whitespace-nowrap opacity-0 transition-all duration-200 group-hover/segment:ml-2 group-hover/segment:max-w-16 group-hover/segment:opacity-100">
                                                Select
                                            </span>
                                        </Button>
                                        <Transition
                                            enter-active-class="transition-all duration-200"
                                            enter-from-class="opacity-0 scale-95"
                                            enter-to-class="opacity-100 scale-100"
                                            leave-active-class="transition-all duration-150"
                                            leave-from-class="opacity-100 scale-100"
                                            leave-to-class="opacity-0 scale-95"
                                        >
                                            <Button
                                                v-if="selectMode"
                                                variant="outline"
                                                class="shrink-0 rounded-lg text-slate-600 hover:bg-slate-100 cursor-pointer"
                                                @click="selectAll"
                                            >
                                                {{ allSelected ? 'Deselect All' : 'Select All' }}
                                            </Button>
                                        </Transition>
                                    </div>
                                    <Button
                                        v-if="docs.length > 0"
                                        variant="outline"
                                        class="group/segment shrink-0 rounded-lg text-slate-600 hover:bg-slate-100 cursor-pointer gap-0"
                                        :disabled="selectMode && selectedDocIds.length === 0"
                                        @click="selectMode ? downloadSelected() : undefined"
                                    >
                                        <Download class="h-4 w-4 shrink-0" />
                                        <span class="max-w-0 overflow-hidden whitespace-nowrap opacity-0 transition-all duration-200 group-hover/segment:ml-2 group-hover/segment:max-w-48 group-hover/segment:opacity-100">
                                            {{ selectMode ? `Download Selected (${selectedDocIds.length})` : 'Download All' }}
                                        </span>
                                    </Button>
                                </div>
                                <div class="divide-y divide-slate-100">
                                    <div
                                        v-for="doc in docs"
                                        :key="doc.id"
                                        class="grid grid-cols-[auto_1fr_auto] py-2 transition-colors"
                                        :class="!selectMode ? 'group/row' : ''"
                                    >
                                        
                                        <div
                                            class="flex items-start pt-1 overflow-hidden transition-all duration-200"
                                            :class="selectMode ? 'w-5 opacity-100 me-2' : 'w-0 opacity-0'"
                                        >
                                            <input
                                                type="checkbox"
                                                class="h-4 w-4 cursor-pointer rounded-lg accent-primary"
                                                :checked="selectedDocIds.includes(doc.id)"
                                                @change="setDoc(doc.id, ($event.target as HTMLInputElement).checked)"
                                            />
                                        </div>

                                        
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="text-sm font-semibold text-foreground">
                                                    {{ requiredLabel(doc.document_type) }}
                                                </p>
                                                <Badge :class="['gap-1.5', statusClass(doc.status)]">
                                                    <span :class="['h-1.5 w-1.5 rounded-full', statusDot(doc.status)]" />
                                                    {{ humanize(doc.status) }}
                                                </Badge>
                                                <Badge
                                                    v-if="isExpired(doc.expires_at) && doc.status !== 'expired'"
                                                    class="gap-1.5 border-rose-200 bg-rose-100 text-rose-600"
                                                >
                                                    <span class="h-1.5 w-1.5 rounded-full bg-rose-500" />
                                                    Expired
                                                </Badge>
                                            </div>

                                            <div>
                                                <button
                                                    v-if="canPreview(doc)"
                                                    class="cursor-pointer flex items-center gap-2 text-sm text-muted-foreground underline-offset-2 hover:underline"
                                                    :title="doc.file_name ?? ''"
                                                    @click="openPreview(doc)"
                                                >
                                                    <File class="h-4 w-4 shrink-0" />
                                                    <span class="truncate">{{ doc.file_name ?? '—' }}</span>
                                                </button>
                                                <span v-else class="text-sm text-muted-foreground">{{ doc.file_name ?? '—' }}</span>
                                            </div>

                                            <div class="overflow-hidden max-h-0 opacity-0 group-hover/row:max-h-96 group-hover/row:opacity-100 transition-all delay-200 duration-200 flex-col">
                                                <div class="flex flex-row items-center gap-x-10 text-xs text-muted-foreground">
                                                    <div class="flex flex-col w-40 gap-y-1">
                                                        <span v-if="doc.issued_at">
                                                            Issued: <span class="font-medium text-foreground">{{ formatDate(doc.issued_at) }}</span>
                                                        </span>
                                                        <span v-if="doc.expires_at">
                                                            Expires:
                                                            <span :class="['font-medium', isExpired(doc.expires_at) ? 'text-rose-600' : 'text-foreground']">
                                                                {{ formatDate(doc.expires_at) }}
                                                            </span>
                                                        </span>
                                                        <span class="text-muted-foreground">{{ formatBytes(doc.file_size) }}</span>
                                                    </div>
                                                </div>

                                                <div v-if="doc.remarks" class="pt-2">
                                                    <Popover>
                                                        <PopoverTrigger as-child>
                                                            <Button
                                                                variant="outline"
                                                                class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100"
                                                            >
                                                                <MessageSquareText class="h-4 w-4" />
                                                                View Remarks
                                                            </Button>
                                                        </PopoverTrigger>
                                                        <PopoverContent
                                                            align="start"
                                                            class="w-80 rounded-lg border-slate-200 bg-white shadow-lg"
                                                        >
                                                            <div>
                                                                <p class="text-sm font-semibold pb-2">Remarks</p>
                                                                <p class="text-sm whitespace-pre-wrap text-muted-foreground">{{ doc.remarks }}</p>
                                                            </div>
                                                        </PopoverContent>
                                                    </Popover>
                                                </div>
                                            </div>
                                        </div>

                                        
                                        <div class="flex items-start">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger as-child>
                                                    <Button
                                                        variant="outline"
                                                        class="rounded-lg border text-muted-foreground hover:bg-slate-100 hover:text-foreground cursor-pointer"
                                                        :disabled="actionForm.processing"
                                                    >
                                                        <MoreHorizontal class="h-4 w-4" />
                                                        
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end" class="w-fit rounded-xl border-slate-200 shadow-lg">
                                                    <DropdownMenuLabel class="text-xs font-semibold tracking-widest text-muted-foreground uppercase">
                                                        {{ requiredLabel(doc.document_type) }}
                                                    </DropdownMenuLabel>
                                                    <DropdownMenuSeparator />
                                                    <DropdownMenuItem
                                                        v-if="canPreview(doc)"
                                                        class="rounded-lg cursor-pointer hover:bg-slate-100"
                                                        @click="openPreview(doc)"
                                                    >
                                                        <Eye class="h-4 w-4" /> Preview
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        v-else
                                                        class="cursor-not-allowed rounded-lg text-slate-500 opacity-50 focus:bg-slate-50 focus:text-slate-500"
                                                        disabled
                                                    >
                                                        <Eye class="mr-2 h-4 w-4" /> No preview available
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        v-if="canVerifyVehicleDocument && doc.status !== 'verified'"
                                                        class="rounded-lg cursor-pointer hover:bg-slate-100"
                                                        @click="openConfirm('verify', doc)"
                                                    >
                                                        <CheckCircle2 class="h-4 w-4" /> Verify
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        v-if="canUnverifyVehicleDocument && doc.status === 'verified'"
                                                        class="rounded-lg cursor-pointer hover:bg-slate-100"
                                                        @click="openConfirm('unverify', doc)"
                                                    >
                                                        <RotateCcw class="h-4 w-4" /> Move to Pending
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        v-if="canInvalidateVehicleDocument"
                                                        class="rounded-lg cursor-pointer text-rose-600 hover:bg-rose-50 focus:text-rose-600"
                                                        @click="openInvalidate(doc)"
                                                    >
                                                        <XCircle class="h-4 w-4" /> Mark Invalid
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        v-if="fileUrl(doc)"
                                                        class="rounded-lg cursor-pointer hover:bg-slate-100"
                                                        as-child
                                                    >
                                                        <a :href="fileUrl(doc)" target="_blank" rel="noopener noreferrer" download>
                                                            <Download class="h-4 w-4" /> Download
                                                        </a>
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card> -->

                    <!-- <Card class="py-6">
                        <CardHeader>
                            <CardTitle class="text-base">Quick Summary</CardTitle>
                        </CardHeader>
                        <CardContent class="border-t border-slate-100 space-y-4 pt-4">
                            <div class="rounded-lg border p-4">
                                <p class="text-xs text-muted-foreground">Vehicle Status</p>
                                <div class="mt-2">
                                    <Badge :class="statusClass(vehicle.status)">{{ humanize(vehicle.status) }}</Badge>
                                </div>
                            </div>
                            <div class="rounded-lg border p-4">
                                <p class="text-xs text-muted-foreground">Document Health</p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <Badge :class="statusClass('verified')">{{ verifiedCount }} verified</Badge>
                                    <Badge v-if="pendingCount > 0" :class="statusClass('pending')">{{ pendingCount }} pending</Badge>
                                    <Badge v-if="invalidCount > 0" :class="statusClass('invalid')">{{ invalidCount }} invalid</Badge>
                                    <Badge v-if="expiredCount > 0" :class="statusClass('expired')">{{ expiredCount }} expired</Badge>
                                </div>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-3 xl:grid-cols-1">
                                <div class="rounded-lg border p-4">
                                    <p class="text-xs text-muted-foreground">Verification Rate</p>
                                    <p class="mt-1 text-lg font-semibold">{{ docsCompletionRate }}%</p>
                                </div>
                                <div class="rounded-lg border p-4">
                                    <p class="text-xs text-muted-foreground">Assigned Company</p>
                                    <p class="mt-1 text-sm font-semibold">{{ company?.company_name ?? 'No company' }}</p>
                                </div>
                                <div class="rounded-lg border p-4">
                                    <p class="text-xs text-muted-foreground">Last Updated By</p>
                                    <p class="mt-1 text-sm font-semibold">{{ vehicle.updater?.name ?? vehicle.creator?.name ?? 'System' }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card> -->
                </div>
            </div>
        </LeadPanel>

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

        <DocumentPreviewDialog
            v-model:open="previewOpen"
            :doc="previewDoc"
            :can-verify="canVerifyVehicleDocument"
            :can-unverify="canUnverifyVehicleDocument"
            :can-invalidate="canInvalidateVehicleDocument"
            @verify="(d) => openConfirm('verify', d)"
            @unverify="(d) => openConfirm('unverify', d)"
            @invalidate="(d) => openInvalidate(d)"
        />


        <AlertDialog v-model:open="archiveOpen">
            <AlertDialogContent class="rounded-2xl">
                <AlertDialogHeader>
                    <AlertDialogTitle>Archive Vehicle</AlertDialogTitle>
                    <AlertDialogDescription>
                        Are you sure you want to archive
                        <span class="font-semibold text-foreground">{{
                            vehicle.plate_number || `Vehicle #${vehicle.id}`
                        }}</span
                        >? You can restore it later from Archived Vehicles.
                    </AlertDialogDescription>
                </AlertDialogHeader>

                <AlertDialogFooter>
                    <AlertDialogCancel class="rounded-lg"
                        >Cancel</AlertDialogCancel
                    >
                    <AlertDialogAction
                        class="rounded-lg border-0 bg-rose-600 text-white hover:bg-rose-700"
                        @click="archiveVehicle"
                    >
                        Archive
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <AlertDialog v-model:open="confirmOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        {{
                            actionType === 'verify'
                                ? 'Verify document?'
                                : 'Move back to pending?'
                        }}
                    </AlertDialogTitle>

                    <AlertDialogDescription>
                        {{
                            actionType === 'verify'
                                ? `This will mark "${humanize(actionDoc?.document_type)}" as verified.`
                                : `This will revert "${humanize(actionDoc?.document_type)}" back to pending review.`
                        }}
                    </AlertDialogDescription>
                </AlertDialogHeader>

                <AlertDialogFooter>
                    <AlertDialogCancel :disabled="actionForm.processing">
                        Cancel
                    </AlertDialogCancel>
                    <AlertDialogAction
                        :disabled="actionForm.processing"
                        @click="submitConfirm"
                    >
                        {{ actionForm.processing ? 'Processing...' : 'Confirm' }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <InvalidateDocumentDialog
            v-model:open="invalidateOpen"
            :doc="actionDoc"
            :vehicle-id="vehicle.id"
        />
    </AppLayout>
</template>
