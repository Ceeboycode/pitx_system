<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { destroy, index } from '@/routes/vehicles';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

import InputError from '@/components/InputError.vue';
import RouteMapDialog from '@/components/routes/RouteMapDialog.vue';
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
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import { Separator } from '@/components/ui/separator';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { can } from '@/lib/can';

import {
    Archive,
    ArrowLeft,
    CheckCircle2,
    CircleHelp,
    Download,
    Eye,
    FileText,
    Map,
    MoreHorizontal,
    RotateCcw,
    Route as RouteIcon,
    Truck,
    XCircle,
} from 'lucide-vue-next';

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
    docs_status?: string | null;
    remarks?: string | null;
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
        helper: vehicle.value.remarks || null,
    },
]);

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
            return 'success';
        case 'pending':
        case 'draft':
        case 'for_verification':
        case 'partial':
            return 'warning';
        case 'invalid':
        case 'expired':
        case 'needs_revision':
        case 'none':
            return 'destructive';
        default:
            return 'outline';
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

function stopDotClass(type: RouteStop['stop_type']) {
    switch (type) {
        case 'origin':
            return 'bg-green-600';
        case 'destination':
            return 'bg-red-600';
        case 'landmark':
            return 'bg-violet-500';
        default:
            return 'bg-amber-500';
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

/* stops dialog */
const stopsDialogOpen = ref(false);

/* preview dialog */
const previewOpen = ref(false);
const previewDoc = ref<VehicleDocument | null>(null);
const pdfLoadError = ref(false);

function openPreview(doc: VehicleDocument) {
    if (!canPreview(doc)) return;
    previewDoc.value = doc;
    pdfLoadError.value = false;
    previewOpen.value = true;
}

function closePreview() {
    previewOpen.value = false;
    previewDoc.value = null;
    pdfLoadError.value = false;
}

/* verify / unverify only from preview */
const actionForm = useForm({});
const confirmOpen = ref(false);
const actionType = ref<'verify' | 'unverify'>('verify');
const actionDoc = ref<VehicleDocument | null>(null);

function openConfirm(type: 'verify' | 'unverify', doc: VehicleDocument) {
    actionType.value = type;
    actionDoc.value = doc;
    confirmOpen.value = true;
}

function openConfirmFromPreview(type: 'verify' | 'unverify') {
    if (!previewDoc.value) return;
    openConfirm(type, previewDoc.value);
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

/* invalidate dialog with stackable checkbox remarks */
const invalidPresets = [
    {
        value: 'blurred',
        label: 'Blurred or unreadable file',
        text: 'The uploaded document is blurred or unreadable. Please upload a clearer copy.',
    },
    {
        value: 'expired',
        label: 'Expired document',
        text: 'The uploaded document is already expired. Please upload a valid updated copy.',
    },
    {
        value: 'wrong_document',
        label: 'Wrong document uploaded',
        text: 'The uploaded file is the wrong document. Please upload the correct requirement.',
    },
    {
        value: 'missing_pages',
        label: 'Missing pages or incomplete scan',
        text: 'The uploaded document is incomplete or has missing pages. Please upload the full copy.',
    },
    {
        value: 'mismatch',
        label: 'Vehicle details do not match',
        text: 'The document details do not match the assigned vehicle information. Please review and re-upload.',
    },
    {
        value: 'reupload_pdf',
        label: 'Please upload as PDF',
        text: 'Please re-upload this requirement as a PDF file.',
    },
] as const;

type InvalidPresetValue = (typeof invalidPresets)[number]['value'];

const selectedInvalidPresets = ref<InvalidPresetValue[]>([]);

const invalidateForm = useForm<{
    remarks: string;
}>({
    remarks: '',
});
const invalidateOpen = ref(false);

watch(
    selectedInvalidPresets,
    (values) => {
        const lines = values
            .map(
                (value) =>
                    invalidPresets.find((preset) => preset.value === value)
                        ?.text ?? '',
            )
            .filter(Boolean);

        invalidateForm.remarks = lines.join('\n');
    },
    { deep: true },
);

function toggleInvalidPreset(value: InvalidPresetValue) {
    const exists = selectedInvalidPresets.value.includes(value);

    if (exists) {
        selectedInvalidPresets.value = selectedInvalidPresets.value.filter(
            (item) => item !== value,
        );
        return;
    }

    selectedInvalidPresets.value = [...selectedInvalidPresets.value, value];
}

function openInvalidate(doc: VehicleDocument) {
    actionDoc.value = doc;
    selectedInvalidPresets.value = [];
    invalidateForm.reset();
    invalidateForm.clearErrors();
    invalidateForm.remarks = doc.remarks ?? '';
    invalidateOpen.value = true;
}

function openInvalidateFromPreview() {
    if (!previewDoc.value) return;
    openInvalidate(previewDoc.value);
    closePreview();
}

function submitInvalidate() {
    if (!actionDoc.value || invalidateForm.processing) return;

    invalidateForm.patch(
        `/vehicles/${vehicle.value.id}/documents/${actionDoc.value.id}/invalidate`,
        {
            preserveScroll: true,
            onSuccess: () => {
                invalidateOpen.value = false;
                actionDoc.value = null;
                selectedInvalidPresets.value = [];
                invalidateForm.reset();
            },
        },
    );
}
</script>

<template>
    <Head :title="vehicle.plate_number || `Vehicle #${vehicle.id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="sticky top-0 z-20 border-b bg-background/95 backdrop-blur supports-backdrop-filter:bg-background/80"
        >
            <div
                class="flex min-h-16 items-center justify-between gap-4 px-4 py-3 sm:px-6"
            >
                <div class="flex min-w-0 items-center gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary text-primary-foreground"
                    >
                        <Truck class="h-5 w-5" />
                    </div>

                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1
                                class="truncate text-base font-semibold sm:text-lg"
                            >
                                {{
                                    vehicle.plate_number ||
                                    `Vehicle #${vehicle.id}`
                                }}
                            </h1>
                            <Badge
                                :variant="statusVariant(vehicle.status)"
                                class="text-[10px]"
                            >
                                {{ humanize(vehicle.status) }}
                            </Badge>
                        </div>

                        <p class="truncate text-sm text-muted-foreground">
                            {{ company?.company_name ?? 'No company assigned' }}
                        </p>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <Button variant="outline" size="sm" as-child>
                        <Link :href="VEHICLES_INDEX_URL">
                            <ArrowLeft class="mr-1.5 h-4 w-4" />
                            Back
                        </Link>
                    </Button>

                    <Button
                        v-if="canArchiveVehicle"
                        variant="outline"
                        size="sm"
                        class="rounded-lg border-rose-200 text-rose-600 hover:bg-rose-50 hover:text-rose-700"
                        @click="archiveOpen = true"
                    >
                        <Archive class="mr-2 h-4 w-4" />
                        Archive
                    </Button>
                </div>
            </div>
        </div>

        <div class="space-y-5 p-4 sm:p-6">
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border bg-card p-4">
                    <p class="text-xs text-muted-foreground">
                        Documents Verified
                    </p>
                    <p class="mt-1 text-lg font-semibold">
                        {{ verifiedCount }}/{{ docs.length }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ docsCompletionRate }}% completion
                    </p>
                </div>
                <div class="rounded-xl border bg-card p-4">
                    <p class="text-xs text-muted-foreground">Needs Attention</p>
                    <p class="mt-1 text-lg font-semibold">
                        {{ actionRequiredCount }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        Pending, invalid, or expired docs
                    </p>
                </div>
                <div class="rounded-xl border bg-card p-4">
                    <p class="text-xs text-muted-foreground">Route Stops</p>
                    <p class="mt-1 text-lg font-semibold">
                        {{ sortedStops.length }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{
                            route
                                ? 'Assigned route available'
                                : 'No route assigned'
                        }}
                    </p>
                </div>
                <div class="rounded-xl border bg-card p-4">
                    <p class="text-xs text-muted-foreground">Last Updated</p>
                    <p class="mt-1 text-sm font-semibold">
                        {{ formatDateTime(vehicle.updated_at) }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        Created {{ formatDate(vehicle.created_at) }}
                    </p>
                </div>
            </div>

            <div class="grid gap-4 xl:grid-cols-[2fr_1fr]">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base"
                            >Vehicle Snapshot</CardTitle
                        >
                    </CardHeader>
                    <CardContent
                        class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                    >
                        <div
                            v-for="item in vehicleMeta"
                            :key="item.label"
                            class="rounded-lg border bg-muted/20 p-3"
                        >
                            <p
                                class="text-[11px] font-semibold tracking-widest text-muted-foreground uppercase"
                            >
                                {{ item.label }}
                            </p>
                            <p class="mt-1 text-sm font-medium text-foreground">
                                {{ item.value }}
                            </p>
                            <p
                                v-if="item.helper"
                                class="mt-0.5 text-xs text-muted-foreground"
                            >
                                {{ item.helper }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardTitle class="text-base"
                            >Operational State</CardTitle
                        >
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div class="flex items-center gap-2">
                            <Badge :variant="statusVariant(vehicle.status)">
                                {{ humanize(vehicle.status) }}
                            </Badge>
                            <span class="text-xs text-muted-foreground">
                                {{ formatDateTime(vehicle.updated_at) }}
                            </span>
                        </div>
                        <div class="rounded-lg border p-3">
                            <p
                                class="text-[11px] font-semibold tracking-widest text-muted-foreground uppercase"
                            >
                                Documents
                            </p>
                            <div class="mt-2 flex flex-wrap gap-2 text-sm">
                                <Badge variant="outline"
                                    >{{ verifiedCount }} verified</Badge
                                >
                                <Badge v-if="pendingCount" variant="secondary"
                                    >{{ pendingCount }} pending</Badge
                                >
                                <Badge v-if="invalidCount" variant="destructive"
                                    >{{ invalidCount }} invalid</Badge
                                >
                                <Badge v-if="expiredCount" variant="destructive"
                                    >{{ expiredCount }} expired</Badge
                                >
                            </div>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{
                                    docHealthSummary ||
                                    'No documents uploaded yet.'
                                }}
                            </p>
                        </div>

                        <div class="rounded-lg border p-3">
                            <p
                                class="text-[11px] font-semibold tracking-widest text-muted-foreground uppercase"
                            >
                                Remarks
                            </p>
                            <p
                                class="mt-1 text-sm text-foreground"
                                v-if="vehicle.remarks"
                            >
                                {{ vehicle.remarks }}
                            </p>
                            <p v-else class="text-sm text-muted-foreground">
                                —
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
                <div class="space-y-5">
                    <div class="grid gap-4 lg:grid-cols-2">
                        <Card>
                            <CardHeader>
                                <CardTitle class="text-base"
                                    >Vehicle Details</CardTitle
                                >
                            </CardHeader>

                            <CardContent class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <Label>Vehicle Type</Label>
                                    <p class="mt-1 text-sm">
                                        {{ humanize(vehicle.vehicle_type) }}
                                    </p>
                                </div>
                                <div>
                                    <Label>Plate Number</Label>
                                    <p class="mt-1 text-sm">
                                        {{ vehicle.plate_number ?? '—' }}
                                    </p>
                                </div>
                                <div>
                                    <Label>Body Number</Label>
                                    <p class="mt-1 text-sm">
                                        {{ vehicle.body_number ?? '—' }}
                                    </p>
                                </div>
                                <div>
                                    <Label>Capacity</Label>
                                    <p class="mt-1 text-sm">
                                        {{ vehicle.capacity ?? '—' }}
                                    </p>
                                </div>
                                <div>
                                    <Label>Color</Label>
                                    <p class="mt-1 text-sm">
                                        {{ vehicle.color ?? '—' }}
                                    </p>
                                </div>
                                <div>
                                    <Label>Make / Model</Label>
                                    <p class="mt-1 text-sm">
                                        {{ vehicle.make_model ?? '—' }}
                                    </p>
                                </div>
                                <div>
                                    <Label>Engine Number</Label>
                                    <p class="mt-1 text-sm break-all">
                                        {{ vehicle.engine_number ?? '—' }}
                                    </p>
                                </div>
                                <div>
                                    <Label>Chassis Number</Label>
                                    <p class="mt-1 text-sm break-all">
                                        {{ vehicle.chassis_number ?? '—' }}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle class="text-base"
                                    >Company Details</CardTitle
                                >
                            </CardHeader>

                            <CardContent class="space-y-4">
                                <div>
                                    <Label>Company Name</Label>
                                    <p class="mt-1 text-sm font-medium">
                                        {{ company?.company_name ?? '—' }}
                                    </p>
                                </div>

                                <div>
                                    <Label>Company Code</Label>
                                    <p class="mt-1 text-sm">
                                        {{ company?.company_code ?? '—' }}
                                    </p>
                                </div>

                                <div>
                                    <Label>Email</Label>
                                    <p class="mt-1 text-sm">
                                        {{ company?.company_email ?? '—' }}
                                    </p>
                                </div>

                                <div>
                                    <Label>Phone</Label>
                                    <p class="mt-1 text-sm">
                                        {{ company?.company_phone ?? '—' }}
                                    </p>
                                </div>

                                <div v-if="company?.company_address">
                                    <Label>Address</Label>
                                    <p class="mt-1 text-sm">
                                        {{ company.company_address }}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <Card v-if="route">
                        <CardHeader>
                            <div
                                class="flex flex-wrap items-center justify-between gap-3"
                            >
                                <div>
                                    <CardTitle class="text-base"
                                        >Route Overview</CardTitle
                                    >
                                    <p class="text-sm text-muted-foreground">
                                        {{ sortedStops.length }} stops
                                        configured. Open the map or stops list
                                        for a clearer route view.
                                    </p>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <Button
                                        v-if="canViewVehicle"
                                        variant="outline"
                                        size="sm"
                                        @click="mapDialogOpen = true"
                                    >
                                        <Map class="mr-1.5 h-4 w-4" />
                                        Open Map
                                    </Button>

                                    <Button
                                        v-if="canViewVehicle"
                                        variant="outline"
                                        size="sm"
                                        @click="stopsDialogOpen = true"
                                    >
                                        <RouteIcon class="mr-1.5 h-4 w-4" />
                                        View Stops
                                    </Button>
                                </div>
                            </div>
                        </CardHeader>

                        <CardContent
                            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
                        >
                            <div class="rounded-lg border p-4">
                                <p class="text-xs text-muted-foreground">
                                    Route
                                </p>
                                <p class="mt-1 text-sm font-medium">
                                    {{ route.route_name }}
                                </p>
                            </div>

                            <div class="rounded-lg border p-4">
                                <p class="text-xs text-muted-foreground">
                                    Gate
                                </p>
                                <p class="mt-1 text-sm font-medium">
                                    {{ route.gate?.gate_name ?? '—' }}
                                </p>
                            </div>

                            <div class="rounded-lg border p-4">
                                <p class="text-xs text-muted-foreground">
                                    Distance
                                </p>
                                <p class="mt-1 text-sm font-medium">
                                    {{ fmtDistance(route.distance_meters) }}
                                </p>
                            </div>

                            <div class="rounded-lg border p-4">
                                <p class="text-xs text-muted-foreground">
                                    Duration
                                </p>
                                <p class="mt-1 text-sm font-medium">
                                    {{ fmtDuration(route.duration_seconds) }}
                                </p>
                            </div>

                            <div class="rounded-lg border p-4 sm:col-span-2">
                                <p class="text-xs text-muted-foreground">
                                    Origin
                                </p>
                                <p class="mt-1 text-sm font-medium">
                                    {{ route.origin_name }}
                                </p>
                            </div>

                            <div class="rounded-lg border p-4 sm:col-span-2">
                                <p class="text-xs text-muted-foreground">
                                    Destination
                                </p>
                                <p class="mt-1 text-sm font-medium">
                                    {{ route.destination_name }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div
                                class="flex flex-wrap items-center justify-between gap-3"
                            >
                                <div>
                                    <CardTitle class="text-base"
                                        >Documents</CardTitle
                                    >
                                    <p class="text-sm text-muted-foreground">
                                        Review every document and update its
                                        status.
                                    </p>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <Badge variant="outline">
                                        {{ verifiedCount }}/{{
                                            docs.length
                                        }}
                                        verified
                                    </Badge>
                                    <Badge
                                        v-if="actionRequiredCount > 0"
                                        variant="secondary"
                                    >
                                        {{ actionRequiredCount }} need action
                                    </Badge>
                                    <Badge
                                        v-if="expiredCount > 0"
                                        variant="destructive"
                                    >
                                        {{ expiredCount }} expired
                                    </Badge>
                                </div>
                            </div>
                        </CardHeader>

                        <CardContent class="p-0">
                            <div class="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead class="w-[28%] pl-6"
                                                >Document</TableHead
                                            >
                                            <TableHead class="w-[14%]"
                                                >Status</TableHead
                                            >
                                            <TableHead class="w-[20%]"
                                                >Dates</TableHead
                                            >
                                            <TableHead class="w-[30%]"
                                                >File</TableHead
                                            >
                                            <TableHead
                                                class="w-[8%] pr-4 text-right"
                                                >Actions</TableHead
                                            >
                                        </TableRow>
                                    </TableHeader>

                                    <TableBody>
                                        <TableRow
                                            v-for="doc in docs"
                                            :key="doc.id"
                                        >
                                            <TableCell class="pl-6">
                                                <div class="space-y-1">
                                                    <p class="font-medium">
                                                        {{
                                                            requiredLabel(
                                                                doc.document_type,
                                                            )
                                                        }}
                                                    </p>
                                                    <p
                                                        v-if="doc.remarks"
                                                        class="max-w-60 truncate text-xs text-muted-foreground"
                                                    >
                                                        {{ doc.remarks }}
                                                    </p>
                                                </div>
                                            </TableCell>

                                            <TableCell>
                                                <div class="space-y-1.5">
                                                    <div
                                                        class="flex flex-wrap gap-1.5"
                                                    >
                                                        <Badge
                                                            :variant="
                                                                statusVariant(
                                                                    doc.status,
                                                                )
                                                            "
                                                        >
                                                            {{
                                                                humanize(
                                                                    doc.status,
                                                                )
                                                            }}
                                                        </Badge>
                                                        <Badge
                                                            v-if="
                                                                isExpired(
                                                                    doc.expires_at,
                                                                )
                                                            "
                                                            variant="destructive"
                                                        >
                                                            Expired
                                                        </Badge>
                                                    </div>

                                                    <p
                                                        v-if="
                                                            doc.status ===
                                                            'pending'
                                                        "
                                                        class="text-xs text-muted-foreground"
                                                    >
                                                        Waiting for review.
                                                    </p>

                                                    <p
                                                        v-else-if="
                                                            doc.status ===
                                                            'invalid'
                                                        "
                                                        class="text-xs text-destructive"
                                                    >
                                                        Needs correction before
                                                        approval.
                                                    </p>

                                                    <Popover
                                                        v-if="
                                                            doc.status ===
                                                                'invalid' &&
                                                            doc.remarks
                                                        "
                                                    >
                                                        <PopoverTrigger
                                                            as-child
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                class="h-auto px-0 py-0 text-xs text-destructive hover:bg-transparent"
                                                            >
                                                                <CircleHelp
                                                                    class="mr-1 h-3.5 w-3.5"
                                                                />
                                                                Why invalid?
                                                            </Button>
                                                        </PopoverTrigger>
                                                        <PopoverContent
                                                            align="start"
                                                            class="max-w-72 text-xs"
                                                        >
                                                            <p
                                                                class="font-medium text-foreground"
                                                            >
                                                                Invalid reason
                                                            </p>
                                                            <p
                                                                class="mt-1 whitespace-pre-wrap text-muted-foreground"
                                                            >
                                                                {{
                                                                    doc.remarks
                                                                }}
                                                            </p>
                                                        </PopoverContent>
                                                    </Popover>
                                                </div>
                                            </TableCell>

                                            <TableCell
                                                class="text-xs text-muted-foreground"
                                            >
                                                <div>
                                                    Issued:
                                                    {{
                                                        formatDate(
                                                            doc.issued_at,
                                                        )
                                                    }}
                                                </div>
                                                <div>
                                                    Expires:
                                                    {{
                                                        formatDate(
                                                            doc.expires_at,
                                                        )
                                                    }}
                                                </div>
                                            </TableCell>

                                            <TableCell class="max-w-0">
                                                <div class="space-y-1">
                                                    <button
                                                        v-if="canPreview(doc)"
                                                        class="flex w-full items-center gap-1.5 text-left text-sm text-primary hover:underline"
                                                        @click="
                                                            openPreview(doc)
                                                        "
                                                    >
                                                        <Eye
                                                            class="h-3.5 w-3.5 shrink-0"
                                                        />
                                                        <span
                                                            class="min-w-0 flex-1 truncate"
                                                        >
                                                            {{
                                                                doc.file_name ??
                                                                '—'
                                                            }}
                                                        </span>
                                                    </button>
                                                    <span
                                                        v-else
                                                        class="block truncate text-sm text-muted-foreground"
                                                        :title="
                                                            doc.file_name ?? '—'
                                                        "
                                                    >
                                                        {{
                                                            doc.file_name ?? '—'
                                                        }}
                                                    </span>
                                                    <p
                                                        class="text-xs text-muted-foreground"
                                                    >
                                                        {{
                                                            formatBytes(
                                                                doc.file_size,
                                                            )
                                                        }}
                                                    </p>
                                                    <p
                                                        v-if="!fileUrl(doc)"
                                                        class="text-xs text-destructive"
                                                    >
                                                        File link is
                                                        unavailable.
                                                    </p>
                                                </div>
                                            </TableCell>

                                            <TableCell class="pr-4 text-right">
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger
                                                        as-child
                                                    >
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            class="h-8 w-8 p-0"
                                                        >
                                                            <MoreHorizontal
                                                                class="h-4 w-4"
                                                            />
                                                            <span
                                                                class="sr-only"
                                                                >Open
                                                                actions</span
                                                            >
                                                        </Button>
                                                    </DropdownMenuTrigger>

                                                    <DropdownMenuContent
                                                        align="end"
                                                        class="w-52"
                                                    >
                                                        <DropdownMenuLabel
                                                            >Actions</DropdownMenuLabel
                                                        >
                                                        <DropdownMenuSeparator />

                                                        <DropdownMenuItem
                                                            v-if="
                                                                canPreview(doc)
                                                            "
                                                            @click="
                                                                openPreview(doc)
                                                            "
                                                        >
                                                            <Eye
                                                                class="mr-2 h-4 w-4"
                                                            />
                                                            Review Document
                                                        </DropdownMenuItem>

                                                        <DropdownMenuItem
                                                            v-if="fileUrl(doc)"
                                                            as-child
                                                        >
                                                            <a
                                                                :href="
                                                                    fileUrl(doc)
                                                                "
                                                                target="_blank"
                                                                rel="noopener noreferrer"
                                                                download
                                                            >
                                                                <Download
                                                                    class="mr-2 h-4 w-4"
                                                                />
                                                                Download
                                                            </a>
                                                        </DropdownMenuItem>
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </TableCell>
                                        </TableRow>

                                        <TableRow v-if="docs.length === 0">
                                            <TableCell
                                                colspan="5"
                                                class="py-10 text-center text-muted-foreground"
                                            >
                                                No documents uploaded yet.
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div class="space-y-5">
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base"
                                >Quick Summary</CardTitle
                            >
                        </CardHeader>

                        <CardContent class="space-y-4">
                            <div class="rounded-lg border p-4">
                                <p class="text-xs text-muted-foreground">
                                    Vehicle Status
                                </p>
                                <div class="mt-2">
                                    <Badge
                                        :variant="statusVariant(vehicle.status)"
                                    >
                                        {{ humanize(vehicle.status) }}
                                    </Badge>
                                </div>
                            </div>

                            <div class="rounded-lg border p-4">
                                <p class="text-xs text-muted-foreground">
                                    Document Health
                                </p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <Badge variant="outline"
                                        >{{ verifiedCount }} verified</Badge
                                    >
                                    <Badge
                                        v-if="pendingCount > 0"
                                        variant="secondary"
                                    >
                                        {{ pendingCount }} pending
                                    </Badge>
                                    <Badge
                                        v-if="invalidCount > 0"
                                        variant="destructive"
                                    >
                                        {{ invalidCount }} invalid
                                    </Badge>
                                    <Badge
                                        v-if="expiredCount > 0"
                                        variant="destructive"
                                    >
                                        {{ expiredCount }} expired
                                    </Badge>
                                </div>
                            </div>

                            <div
                                class="grid gap-3 sm:grid-cols-3 xl:grid-cols-1"
                            >
                                <div class="rounded-lg border p-4">
                                    <p class="text-xs text-muted-foreground">
                                        Verification Rate
                                    </p>
                                    <p class="mt-1 text-lg font-semibold">
                                        {{ docsCompletionRate }}%
                                    </p>
                                </div>

                                <div class="rounded-lg border p-4">
                                    <p class="text-xs text-muted-foreground">
                                        Assigned Company
                                    </p>
                                    <p class="mt-1 text-sm font-semibold">
                                        {{
                                            company?.company_name ??
                                            'No company'
                                        }}
                                    </p>
                                </div>

                                <div class="rounded-lg border p-4">
                                    <p class="text-xs text-muted-foreground">
                                        Last Updated By
                                    </p>
                                    <p class="mt-1 text-sm font-semibold">
                                        {{
                                            vehicle.updater?.name ??
                                            vehicle.creator?.name ??
                                            'System'
                                        }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>

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

        <Dialog v-model:open="stopsDialogOpen">
            <DialogContent class="max-w-3xl">
                <DialogHeader>
                    <DialogTitle>Route Stops</DialogTitle>
                    <DialogDescription>
                        Ordered list of all route stops.
                    </DialogDescription>
                </DialogHeader>

                <div
                    v-if="sortedStops.length"
                    class="max-h-[65vh] overflow-auto pr-1"
                >
                    <div class="space-y-3">
                        <div
                            v-for="(stop, i) in sortedStops"
                            :key="stop.id"
                            class="flex items-start gap-3 rounded-lg border p-4"
                        >
                            <div
                                :class="[
                                    'mt-1 h-3 w-3 shrink-0 rounded-full',
                                    stopDotClass(stop.stop_type),
                                ]"
                            />
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-medium">
                                        {{ i + 1 }}. {{ stop.stop_name }}
                                    </p>
                                    <Badge variant="outline">
                                        {{ stopTypeLabel(stop.stop_type) }}
                                    </Badge>
                                </div>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    {{ stop.address || 'No address provided' }}
                                </p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ Number(stop.latitude).toFixed(5) }},
                                    {{ Number(stop.longitude).toFixed(5) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="py-8 text-center text-sm text-muted-foreground"
                >
                    No stops available.
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="stopsDialogOpen = false">
                        Close
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="previewOpen">
            <DialogContent
                class="flex max-h-[90vh] max-w-4xl flex-col gap-0 overflow-hidden p-0"
            >
                <DialogHeader class="shrink-0 border-b px-6 py-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <DialogTitle class="truncate text-base">
                                {{
                                    previewDoc?.file_name ??
                                    humanize(previewDoc?.document_type)
                                }}
                            </DialogTitle>
                            <DialogDescription
                                class="mt-1 flex flex-wrap items-center gap-2 text-xs"
                            >
                                <Badge
                                    :variant="statusVariant(previewDoc?.status)"
                                >
                                    {{ humanize(previewDoc?.status) }}
                                </Badge>
                                <span>{{
                                    humanize(previewDoc?.document_type)
                                }}</span>
                                <span v-if="previewDoc?.expires_at">
                                    · Expires
                                    {{ formatDate(previewDoc.expires_at) }}
                                </span>
                            </DialogDescription>
                        </div>

                        <Button
                            v-if="previewDoc"
                            variant="outline"
                            size="sm"
                            as-child
                        >
                            <a
                                :href="fileUrl(previewDoc)"
                                target="_blank"
                                rel="noopener noreferrer"
                                download
                            >
                                <Download class="mr-1.5 h-4 w-4" />
                                Download
                            </a>
                        </Button>
                    </div>
                </DialogHeader>

                <div class="relative flex-1 overflow-auto bg-muted/30">
                    <div
                        v-if="previewDoc && isImage(previewDoc)"
                        class="flex min-h-[50vh] items-center justify-center p-6"
                    >
                        <img
                            :src="fileUrl(previewDoc)"
                            :alt="
                                previewDoc.file_name ?? previewDoc.document_type
                            "
                            class="max-h-[70vh] max-w-full rounded-lg object-contain shadow-md"
                            @error="
                                (e) => ((e.target as HTMLImageElement).src = '')
                            "
                        />
                    </div>

                    <div
                        v-else-if="previewDoc && isPdf(previewDoc)"
                        class="h-[70vh] w-full"
                    >
                        <iframe
                            v-if="!pdfLoadError"
                            :src="fileUrl(previewDoc)"
                            class="h-full w-full border-0"
                            @error="pdfLoadError = true"
                        />
                        <div
                            v-else
                            class="flex h-full flex-col items-center justify-center gap-3 text-muted-foreground"
                        >
                            <FileText class="h-12 w-12 opacity-30" />
                            <p class="text-sm">
                                Cannot preview this PDF inline.
                            </p>
                            <Button as-child variant="secondary" size="sm">
                                <a
                                    :href="fileUrl(previewDoc)"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <Eye class="mr-1.5 h-4 w-4" />
                                    Open in new tab
                                </a>
                            </Button>
                        </div>
                    </div>

                    <div
                        v-else
                        class="flex h-[50vh] items-center justify-center text-sm text-muted-foreground"
                    >
                        Preview not available.
                    </div>
                </div>

                <DialogFooter class="shrink-0 border-t px-6 py-3">
                    <div class="flex flex-1 flex-wrap items-center gap-2">
                        <Button
                            v-if="
                                previewDoc &&
                                previewDoc.status !== 'verified' &&
                                canVerifyVehicleDocument
                            "
                            size="sm"
                            class="bg-emerald-600 text-white hover:bg-emerald-700"
                            :disabled="actionForm.processing"
                            @click="openConfirmFromPreview('verify')"
                        >
                            <CheckCircle2 class="mr-1.5 h-4 w-4" />
                            Verify
                        </Button>

                        <Button
                            v-if="
                                previewDoc &&
                                previewDoc.status === 'verified' &&
                                canUnverifyVehicleDocument
                            "
                            variant="outline"
                            size="sm"
                            :disabled="actionForm.processing"
                            @click="openConfirmFromPreview('unverify')"
                        >
                            <RotateCcw class="mr-1.5 h-4 w-4" />
                            Move to Pending
                        </Button>

                        <Button
                            v-if="previewDoc && canInvalidateVehicleDocument"
                            variant="destructive"
                            size="sm"
                            :disabled="invalidateForm.processing"
                            @click="openInvalidateFromPreview"
                        >
                            <XCircle class="mr-1.5 h-4 w-4" />
                            Mark Invalid
                        </Button>
                    </div>

                    <Button variant="outline" size="sm" @click="closePreview">
                        Close
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

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
                        {{ actionForm.processing ? 'Processing…' : 'Confirm' }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <Dialog v-model:open="invalidateOpen">
            <DialogContent class="rounded-2xl sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Mark as Invalid</DialogTitle>
                    <DialogDescription>
                        Select one or more reasons below. The remarks field will
                        be built automatically — you can still edit it before
                        submitting for
                        <span class="font-medium text-foreground">
                            {{ humanize(actionDoc?.document_type) }} </span
                        >.
                    </DialogDescription>
                </DialogHeader>

                <Separator />

                <div class="space-y-4">
                    <div>
                        <p
                            class="mb-2.5 text-[11px] font-semibold tracking-widest text-muted-foreground uppercase"
                        >
                            Reasons
                        </p>

                        <div class="grid grid-cols-1 gap-1.5 sm:grid-cols-2">
                            <label
                                v-for="preset in invalidPresets"
                                :key="preset.value"
                                :class="[
                                    'flex cursor-pointer items-center gap-3 rounded-lg border px-3 py-2.5 text-sm transition-colors',
                                    selectedInvalidPresets.includes(
                                        preset.value,
                                    )
                                        ? 'border-rose-300 bg-rose-50 text-rose-700'
                                        : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50',
                                ]"
                                @click="toggleInvalidPreset(preset.value)"
                            >
                                <Checkbox
                                    :checked="
                                        selectedInvalidPresets.includes(
                                            preset.value,
                                        )
                                    "
                                    :class="
                                        selectedInvalidPresets.includes(
                                            preset.value,
                                        )
                                            ? 'border-rose-400 data-[state=checked]:border-rose-600 data-[state=checked]:bg-rose-600'
                                            : ''
                                    "
                                    @click.stop
                                    @update:checked="
                                        toggleInvalidPreset(preset.value)
                                    "
                                />
                                <span class="leading-snug">{{
                                    preset.label
                                }}</span>
                            </label>
                        </div>

                        <p
                            v-if="selectedInvalidPresets.length > 0"
                            class="mt-2 text-xs font-medium text-rose-600"
                        >
                            {{ selectedInvalidPresets.length }}
                            reason{{
                                selectedInvalidPresets.length > 1 ? 's' : ''
                            }}
                            selected
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <Label
                            for="inv-remarks"
                            class="text-[11px] font-semibold tracking-widest text-muted-foreground uppercase"
                        >
                            Remarks <span class="text-destructive">*</span>
                        </Label>

                        <Textarea
                            id="inv-remarks"
                            v-model="invalidateForm.remarks"
                            placeholder="Select reasons above or write your own…"
                            class="min-h-[100px] rounded-lg text-sm"
                        />

                        <InputError
                            class="mt-1"
                            :message="invalidateForm.errors.remarks"
                        />

                        <p class="text-[11px] text-muted-foreground">
                            You can edit the auto-generated text or write your
                            own remarks.
                        </p>
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <Button
                        variant="outline"
                        class="rounded-lg"
                        :disabled="invalidateForm.processing"
                        @click="invalidateOpen = false"
                    >
                        Cancel
                    </Button>

                    <Button
                        variant="destructive"
                        class="rounded-lg border-0 bg-rose-600 text-white hover:bg-rose-700"
                        :disabled="
                            invalidateForm.processing ||
                            !invalidateForm.remarks.trim()
                        "
                        @click="submitInvalidate"
                    >
                        {{
                            invalidateForm.processing
                                ? 'Submitting…'
                                : 'Mark Invalid'
                        }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
