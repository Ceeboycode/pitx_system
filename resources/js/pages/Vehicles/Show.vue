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
    Ellipsis,
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


const stopsDialogOpen = ref(false);


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
</script>

<template>
    <Head :title="vehicle.plate_number || `Vehicle #${vehicle.id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Card class="">
                <CardHeader class="py-0">
                    <div class="flex items-center gap-4">
                        <div
                            class="relative h-32 w-32 shrink-0 overflow-hidden rounded-lg border-2 bg-white shadow-sm"
                        >
                            <div class="flex h-full w-full items-center justify-center">
                                <Truck class="h-10 w-10 text-primary" />
                            </div>
                        </div>

                        <div class="gap-2 w-full">
                            <div class="flex flex-row gap-2 pb-2 w-full items-center">
                                <h1 class="text-2xl leading-tight font-bold tracking-tight">
                                    {{ vehicle.plate_number || `Vehicle #${vehicle.id}` }}
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
                                    <Badge class="border-0 bg-muted font-mono text-foreground">
                                        {{ vehicle.vehicle_type ? humanize(vehicle.vehicle_type) : '—' }}
                                    </Badge>
                                    <Badge :class="['', statusClass(vehicle.status)]">
                                        <span
                                            :class="[
                                                'h-2 w-2 rounded-full',
                                                statusDot(vehicle.status),
                                            ]"
                                        />
                                        {{ humanize(vehicle.status) }}
                                    </Badge>
                                    <Badge class="border-0 bg-slate-100 text-slate-600">
                                        {{ company?.company_code ?? '—' }}
                                    </Badge>
                                </div>
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex shrink-0 items-center gap-2">
                                        <Button
                                            as-child
                                            variant="outline"
                                            class="rounded-lg bg-card border-slate-200 text-slate-600 hover:bg-slate-100 cursor-pointer"
                                        >
                                            <Link :href="VEHICLES_INDEX_URL">
                                                <ArrowLeft class="h-4 w-4" />
                                            </Link>
                                        </Button>

                                        <Button
                                            v-if="canArchiveVehicle"
                                            variant="outline"
                                            class="group/segment rounded-lg bg-card border-rose-200 text-rose-600 hover:bg-rose-50 hover:text-rose-700 gap-0 cursor-pointer"
                                            @click="archiveOpen = true"
                                        >
                                            <Archive class="h-4 w-4 shrink-0" />
                                            <span class="max-w-0 overflow-hidden whitespace-nowrap opacity-0 transition-all duration-300 group-hover/segment:ml-2 group-hover/segment:max-w-32 group-hover/segment:opacity-100">
                                                Archive Vehicle
                                            </span>
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardHeader>
            </Card>

            <div class="grid gap-4 lg:grid-cols-3 h-fit">
                <div class="grid gap-4 col-span-1 h-fit">
                    <Card class="py-6">
                        <CardHeader class="flex items-center justify-between">
                            <div>
                                <CardTitle>Vehicle Details</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent class="px-6 grid divide-y gap-y-2 pt-4 border-t border-slate-100">
                            <div class="py-2">
                                <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">
                                    Plate Number
                                </span>
                                <span class="text-sm">{{ vehicle.plate_number ?? '—' }}</span>
                            </div>
                            <div class="py-2">
                                <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">
                                    Vehicle Type
                                </span>
                                <span class="text-sm">{{ humanize(vehicle.vehicle_type) }}</span>
                            </div>
                            <div class="py-2">
                                <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">
                                    Body Number
                                </span>
                                <span class="text-sm">{{ vehicle.body_number ?? '—' }}</span>
                            </div>
                            <div class="py-2">
                                <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">
                                    Capacity
                                </span>
                                <span class="text-sm">{{ vehicle.capacity ?? '—' }}</span>
                            </div>
                            <div class="py-2">
                                <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">
                                    Color
                                </span>
                                <span class="text-sm">{{ vehicle.color ?? '—' }}</span>
                            </div>
                            <div class="py-2">
                                <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">
                                    Make / Model
                                </span>
                                <span class="text-sm">{{ vehicle.make_model ?? '—' }}</span>
                            </div>
                            <div class="py-2">
                                <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">
                                    Engine Number
                                </span>
                                <span class="text-sm break-all">{{ vehicle.engine_number ?? '—' }}</span>
                            </div>
                            <div class="py-2">
                                <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">
                                    Chassis Number
                                </span>
                                <span class="text-sm break-all">{{ vehicle.chassis_number ?? '—' }}</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="py-6">
                        <CardHeader class="flex items-center justify-between">
                            <div>
                                <CardTitle>Company Details</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent class="px-6 grid divide-y gap-y-2 pt-2 border-t border-slate-100">
                            <div class="py-2">
                                <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">
                                    Company Name
                                </span>
                                <span class="text-sm">{{ company?.company_name ?? '—' }}</span>
                            </div>
                            <div class="py-2">
                                <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">
                                    Company Code
                                </span>
                                <span class="font-mono text-sm">{{ company?.company_code ?? '—' }}</span>
                            </div>
                            <div class="grid gap-y-2 pt-2">
                                <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">
                                    Contacts
                                </span>
                                <div class="items-center flex">
                                    <div class="h-full mr-4">
                                        <Mail class="h-4 w-4 inline-block text-primary" />
                                    </div>
                                    <a
                                        v-if="company?.company_email"
                                        :href="`mailto:${company.company_email}`"
                                        class="text-sm hover:underline underline-offset-2"
                                    >{{ company.company_email }}</a>
                                    <span v-else class="text-sm">—</span>
                                </div>
                                <div class="items-center flex">
                                    <div class="h-full mr-4">
                                        <Phone class="h-4 w-4 inline-block text-primary" />
                                    </div>
                                    <a
                                        v-if="company?.company_phone"
                                        :href="`tel:${company.company_phone}`"
                                        class="text-sm hover:underline underline-offset-2"
                                    >{{ company.company_phone }}</a>
                                    <span v-else class="text-sm">—</span>
                                </div>
                                <div class="items-center flex">
                                    <div class="h-full mr-4">
                                        <MapPin class="h-4 w-4 inline-block text-primary" />
                                    </div>
                                    <span class="text-sm">{{ company?.company_address ?? '—' }}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div class="grid gap-4 col-span-2 h-fit">
                    <Card class="py-6">
                        <CardHeader class="">
                            <div class="items-center flex justify-between">
                                <div>
                                    <CardTitle>Route Overview</CardTitle>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent class="px-6 grid flex-col gap-4 pt-4 border-t border-slate-100">
                            <div class="flex flex-wrap gap-2 col-span-2 w-full">
                                <Button
                                    v-if="canViewVehicle"
                                    variant="outline"
                                    size="sm"
                                    class="flex items-center gap-1"
                                    @click="mapDialogOpen = true"
                                >
                                    <Map class="h-4 w-4" />
                                    Open Map
                                </Button>
                                <Button
                                    v-if="canViewVehicle"
                                    variant="outline"
                                    size="sm"
                                    class="flex items-center gap-1"
                                    @click="stopsDialogOpen = true"
                                >
                                    <RouteIcon class="-4 w-4" />
                                    View Stops
                                </Button>
                            </div>
                            <div class="rounded-lg border p-4 col-span-1">
                                <p class="text-xs text-muted-foreground">Route</p>
                                <p class="mt-1 text-sm font-medium">{{ route.route_name }}</p>
                            </div>
                            <div class="rounded-lg border p-4 col-span-1">
                                <p class="text-xs text-muted-foreground">Gate</p>
                                <p class="mt-1 text-sm font-medium">{{ route.gate?.gate_name ?? '—' }}</p>
                            </div>
                            <div class="rounded-lg border p-4 col-span-1">
                                <p class="text-xs text-muted-foreground">Distance</p>
                                <p class="mt-1 text-sm font-medium">{{ fmtDistance(route.distance_meters) }}</p>
                            </div>
                            <div class="rounded-lg border p-4 col-span-1">
                                <p class="text-xs text-muted-foreground">Duration</p>
                                <p class="mt-1 text-sm font-medium">{{ fmtDuration(route.duration_seconds) }}</p>
                            </div>
                            <div class="rounded-lg border p-4 col-span-1">
                                <p class="text-xs text-muted-foreground">Origin</p>
                                <p class="mt-1 text-sm font-medium">{{ route.origin_name }}</p>
                            </div>
                            <div class="rounded-lg border p-4 col-span-1">
                                <p class="text-xs text-muted-foreground">Destination</p>
                                <p class="mt-1 text-sm font-medium">{{ route.destination_name }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
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
                                            <span v-if="!selectMode" class="max-w-0 overflow-hidden whitespace-nowrap opacity-0 transition-all duration-300 group-hover/segment:ml-2 group-hover/segment:max-w-16 group-hover/segment:opacity-100">
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
                                        <span class="max-w-0 overflow-hidden whitespace-nowrap opacity-0 transition-all duration-300 group-hover/segment:ml-2 group-hover/segment:max-w-48 group-hover/segment:opacity-100">
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
                                            class="flex items-start pt-1 overflow-hidden transition-all duration-300"
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

                                            <div class="overflow-hidden max-h-0 opacity-0 group-hover/row:max-h-96 group-hover/row:opacity-100 transition-all delay-200 duration-300 flex-col">
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
                    </Card>

                    <Card class="py-6">
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
                class="flex max-h-[90vh] w-full flex-col gap-0 rounded-lg py-4 px-6"
                className="[&>button:last-child]:hidden"
            >
                <DialogHeader class="shrink-0">
                    <div class="flex items-center justify-between gap-4">
                        <div class="min-w-0 space-y-1">
                            <DialogTitle class="truncate text-base">
                                {{
                                    previewDoc?.file_name ??
                                    humanize(previewDoc?.document_type)
                                }}
                            </DialogTitle>
                            <DialogDescription
                                class="flex flex-wrap items-center gap-2"
                            >
                                <span class="text-xs text-muted-foreground">{{
                                    humanize(previewDoc?.document_type)
                                }}</span>
                                <Badge
                                    :class="[
                                        'gap-1.5',
                                        statusClass(previewDoc?.status),
                                    ]"
                                >
                                    <span
                                        :class="[
                                            'h-1.5 w-1.5 rounded-full',
                                            statusDot(previewDoc?.status),
                                        ]"
                                    />
                                    {{ humanize(previewDoc?.status) }}
                                </Badge>
                            </DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <div class="relative flex-1 overflow-auto py-4">
                    <div
                        v-if="previewDoc && isImage(previewDoc)"
                        class="flex min-h-[50vh] items-center justify-center"
                    >
                        <img
                            :src="fileUrl(previewDoc)"
                            :alt="
                                previewDoc.file_name ?? previewDoc.document_type
                            "
                            class="max-h-[70vh] max-w-full rounded-lg object-contain"
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
                            class="flex h-full flex-col items-center justify-center"
                        >
                            <FileText class="h-12 w-12 opacity-30" />
                            <p class="text-sm">
                                Your browser cannot preview this PDF inline.
                            </p>
                            <Button as-child variant="outline" class="rounded-lg">
                                <a
                                    :href="fileUrl(previewDoc)"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <Eye class="mr-2 h-4 w-4" />Open in new tab
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

                <DialogFooter
                    v-if="previewDoc"
                    class="shrink-0 flex flex-row items-center"
                >
                    <p class="flex-1 text-xs text-muted-foreground">
                        Issued: {{ formatDate(previewDoc?.issued_at ?? null) }}<br>
                        Expires: {{ formatDate(previewDoc?.expires_at ?? null) }}
                    </p>
                    <div class="flex flex-1 flex-row gap-x-2 justify-end">
                        <Popover>
                            <PopoverTrigger as-child>
                                <Button
                                    variant="outline"
                                    class="rounded-lg cursor-pointer hover:bg-slate-100"
                                >
                                    <Ellipsis class="h-4 w-4" />
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent
                                align="end"
                                class="w-fit rounded-lg border-slate-200 shadow-lg p-0 gap-2"
                            >
                                <div
                                    v-if="canVerifyVehicleDocument && previewDoc.status !== 'verified'"
                                    class="cursor-pointer flex items-center gap-2 rounded-lg px-4 py-2 text-sm hover:bg-slate-100"
                                    @click="openConfirmFromPreview('verify')"
                                >
                                    <CheckCircle2 class="h-4 w-4" />
                                    Verify
                                </div>
                                <div
                                    v-if="canUnverifyVehicleDocument && previewDoc.status === 'verified'"
                                    class="cursor-pointer flex items-center gap-2 rounded-lg px-4 py-2 text-sm hover:bg-slate-100"
                                    @click="openConfirmFromPreview('unverify')"
                                >
                                    <RotateCcw class="h-4 w-4" />
                                    Move to Pending
                                </div>
                                <div
                                    v-if="canInvalidateVehicleDocument"
                                    class="cursor-pointer flex items-center gap-2 rounded-lg px-4 py-2 text-sm hover:bg-slate-100"
                                    @click="openInvalidateFromPreview"
                                >
                                    <XCircle class="h-4 w-4" />
                                    Mark Invalid
                                </div>
                                <a
                                    v-if="fileUrl(previewDoc)"
                                    :href="fileUrl(previewDoc)"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    download
                                    class="cursor-pointer flex items-center gap-2 rounded-lg px-4 py-2 text-sm hover:bg-slate-100"
                                >
                                    <Download class="h-4 w-4" />
                                    Download
                                </a>
                            </PopoverContent>
                        </Popover>
                        <Button
                            variant="outline"
                            class="rounded-lg cursor-pointer hover:bg-slate-100"
                            @click="closePreview"
                        >Close</Button>
                    </div>
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
                        {{ actionForm.processing ? 'Processing...' : 'Confirm' }}
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
                            placeholder="Select reasons above or write your own..."
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
                                ? 'Submitting...'
                                : 'Mark Invalid'
                        }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
