<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

import ExternalLayout from '@/layouts/ExternalLayout.vue';

import VehicleBasicInfoForm from '@/components/company/vehicles/VehicleBasicInfoForm.vue';
import VehicleRouteAssignment from '@/components/company/vehicles/VehicleRouteAssignment.vue';
import VehicleSummaryCard from '@/components/company/vehicles/VehicleSummaryCard.vue';

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
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
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

import {
    ArrowLeft,
    Bus,
    CalendarDays,
    CarFront,
    CheckCircle2,
    Clock3,
    Download,
    Eye,
    FileImage,
    FileText,
    MapPinned,
    MoreHorizontal,
    Pencil,
    Power,
    ShieldCheck,
    UserCircle2,
} from 'lucide-vue-next';

import CompanyVehicleController from '@/actions/App/Http/Controllers/CompanyVehicleController';


import { can } from '@/lib/can';

const canUpdate = can('external_vehicles.update');
const canToggle = can('external_vehicles.toggleStatus');


type Company = {
    id: number;
    company_name: string;
    company_code?: string | null;
    status: string;
    logo_url?: string | null;
};

type User = {
    id: number;
    name: string;
    username: string;
    email: string;
};

type GateItem = {
    id: number;
    gate_name: string;
    bays?: number | null;
};

type RouteStop = {
    id: number;
    route_id: number;
    stop_name: string;
    stop_order: number;
    stop_type: string;
    address?: string | null;
    latitude?: number | null;
    longitude?: number | null;
};

type RouteItem = {
    id: number;
    gate_id?: number | null;
    route_name: string;
    origin_name?: string | null;
    destination_name?: string | null;
    route_geometry?: unknown;
    stops?: RouteStop[];
    gate?: { id: number; gate_name: string } | null;
};

type VehicleDocument = {
    id: number;
    document_type: string;
    file_name?: string | null;
    file_url?: string | null;
    download_url?: string | null;
    file_mime_type?: string | null;
    file_size?: number | null;
    status: string;
    issued_at?: string | null;
    expires_at?: string | null;
    created_at?: string | null;
};

type Vehicle = {
    id: number;
    route_id: number | string | null;
    vehicle_type: string;
    plate_number: string;
    body_number: string;
    capacity: string | number;
    color: string;
    engine_number: string;
    chassis_number: string;
    make_model: string;
    status: string;
    remarks?: string | null;
    created_at?: string | null;
    route?: RouteItem | null;
    documents: VehicleDocument[];
};

type DocTypes = Record<string, string>;


const props = defineProps<{
    company: Company;
    user: User;
    vehicle: Vehicle;
    gates: GateItem[];
    routes: RouteItem[];
    docTypes: DocTypes;
    mapConfig: {
        mapboxToken?: string | null;
        defaultCenter: { lng: number; lat: number };
        defaultZoom: number;
    };
}>();


const vehicleTypes = [
    'Bus',
    'Modern Jeepney',
    'Jeepney',
    'Mini Bus',
    'UV Express',
    'Van',
];

const form = reactive({
    vehicle_type: props.vehicle.vehicle_type ?? '',
    plate_number: props.vehicle.plate_number ?? '',
    body_number: props.vehicle.body_number ?? '',
    capacity: props.vehicle.capacity ?? '',
    color: props.vehicle.color ?? '',
    engine_number: props.vehicle.engine_number ?? '',
    chassis_number: props.vehicle.chassis_number ?? '',
    make_model: props.vehicle.make_model ?? '',
    route_id: props.vehicle.route_id ? String(props.vehicle.route_id) : '',
    processing: false,
    errors: {} as Record<string, string>,
    documents: props.vehicle.documents.map((doc) => ({
        document_type: doc.document_type,
        file: doc.file_name ? new File([], doc.file_name) : null,
        issued_at: doc.issued_at ?? '',
        expires_at: doc.expires_at ?? '',
    })),
});


const selectedRoute = computed(
    () =>
        props.routes.find(
            (route) => String(route.id) === String(form.route_id),
        ) ??
        props.vehicle.route ??
        null,
);

const requiredDocumentsCount = computed(
    () => Object.keys(props.docTypes).length,
);
const uploadedDocumentsCount = computed(() => props.vehicle.documents.length);

const approvedDocumentsCount = computed(
    () =>
        props.vehicle.documents.filter((doc) => doc.status === 'approved')
            .length,
);

const pendingDocumentsCount = computed(
    () =>
        props.vehicle.documents.filter((doc) => doc.status === 'pending')
            .length,
);

const orderedDocuments = computed(() =>
    Object.keys(props.docTypes).map((docKey) => ({
        document_type: docKey,
        label: props.docTypes[docKey],
        item:
            props.vehicle.documents.find(
                (item) => item.document_type === docKey,
            ) ?? null,
    })),
);


function isSuspended(status?: string | null) {
    return status === 'suspended';
}

function isDocumentExpired(doc: VehicleDocument) {
    if (doc.status === 'expired') return true;
    if (!doc.expires_at) return false;
    return new Date(doc.expires_at) < new Date();
}

function needsResubmission(doc: VehicleDocument) {
    return doc.status === 'invalid' || isDocumentExpired(doc);
}

function hasDocumentsNeedingResubmission(vehicle: Vehicle) {
    return vehicle.documents?.some((doc) => needsResubmission(doc)) ?? false;
}

function businessCanToggle(vehicle: Vehicle) {
    if (isSuspended(vehicle.status)) return false;
    if (!vehicle.documents?.length) return false;
    if (hasDocumentsNeedingResubmission(vehicle)) return false;
    return !vehicle.documents.some(
        (doc) => doc.status === 'pending' || doc.status === 'rejected',
    );
}

function canEditDocumentsForResubmission(vehicle: Vehicle) {
    return (
        canUpdate &&
        !isSuspended(vehicle.status) &&
        hasDocumentsNeedingResubmission(vehicle)
    );
}

const canToggleVehicle = computed(
    () => canToggle && businessCanToggle(props.vehicle),
);

function toggleLabel(status?: string | null) {
    return status === 'active' ? 'Set Inactive' : 'Set Active';
}

function toggleStatusClass(status?: string | null) {
    return status === 'active'
        ? 'text-rose-600 focus:text-rose-600 focus:bg-rose-50'
        : 'text-emerald-700 focus:text-emerald-700 focus:bg-emerald-50';
}

function vehicleActionNote(vehicle: Vehicle) {
    if (isSuspended(vehicle.status))
        return 'Suspended vehicles cannot be edited or updated.';
    if (!vehicle.documents?.length) return 'Upload required documents first.';
    if (
        vehicle.documents.some(
            (doc) => doc.status === 'pending' || doc.status === 'rejected',
        )
    ) {
        return 'Documents must be approved before changing status.';
    }
    if (hasDocumentsNeedingResubmission(vehicle)) {
        return 'Resubmit invalid or expired documents before activation.';
    }
    if (!hasDocumentsNeedingResubmission(vehicle))
        return 'No invalid or expired documents available for resubmission.';
    return '';
}


const statusDialog = reactive({ open: false });

function confirmToggleStatus() {
    router.patch(
        CompanyVehicleController.toggleStatus(props.vehicle.id).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                statusDialog.open = false;
            },
        },
    );
}


const previewOpen = ref(false);
const previewDoc = ref<VehicleDocument | null>(null);

function openPreview(doc: VehicleDocument) {
    if (!doc.file_url) return;
    previewDoc.value = doc;
    previewOpen.value = true;
}


function humanize(value?: string | null) {
    if (!value) return '—';
    return value
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
}

function statusClass(status?: string | null) {
    switch (status) {
        case 'active':
        case 'approved':
        case 'verified':
            return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'pending':
        case 'for_verification':
            return 'bg-amber-100 text-amber-700 border-amber-200';
        case 'rejected':
        case 'inactive':
            return 'bg-rose-100 text-rose-600 border-rose-200';
        case 'expired':
            return 'bg-rose-100 text-rose-600 border-rose-200';
        default:
            return 'bg-slate-100 text-slate-600 border-0';
    }
}

function statusDot(status?: string | null) {
    switch (status) {
        case 'active':
        case 'approved':
        case 'verified':
            return 'bg-emerald-500';
        case 'pending':
        case 'for_verification':
            return 'bg-amber-500';
        case 'rejected':
        case 'inactive':
            return 'bg-rose-500';
        case 'expired':
            return 'bg-rose-500';
        default:
            return 'bg-slate-400';
    }
}

function formatDate(value?: string | null) {
    if (!value) return '—';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return new Intl.DateTimeFormat('en-PH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    }).format(date);
}

function formatDateTime(value?: string | null) {
    if (!value) return '—';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return new Intl.DateTimeFormat('en-PH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(date);
}

function formatBytes(bytes?: number | null) {
    if (!bytes || bytes <= 0) return '—';
    const units = ['B', 'KB', 'MB', 'GB'];
    let value = bytes;
    let unitIndex = 0;
    while (value >= 1024 && unitIndex < units.length - 1) {
        value /= 1024;
        unitIndex++;
    }
    return `${value.toFixed(value >= 10 || unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
}

function isPdf(doc?: VehicleDocument | null) {
    const mime = String(doc?.file_mime_type ?? '').toLowerCase();
    const fileName = String(doc?.file_name ?? '').toLowerCase();
    return mime.includes('pdf') || fileName.endsWith('.pdf');
}

function isImage(doc?: VehicleDocument | null) {
    const mime = String(doc?.file_mime_type ?? '').toLowerCase();
    const fileName = String(doc?.file_name ?? '').toLowerCase();
    return (
        mime.startsWith('image/') ||
        ['.jpg', '.jpeg', '.png', '.webp'].some((ext) => fileName.endsWith(ext))
    );
}

function documentDownloadUrl(doc?: VehicleDocument | null) {
    return doc?.download_url || doc?.file_url || '#';
}
</script>

<template>
    <Head :title="`Vehicle ${vehicle.plate_number}`" />

    <ExternalLayout :company="company" :user="user">
        <div class="min-h-screen bg-slate-50/60">
            <div class="mx-auto max-w-7xl space-y-6 p-4 md:p-8">
                
                <div
                    class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div class="space-y-1">
                        <div
                            class="flex flex-wrap items-center gap-2 text-xs font-semibold tracking-widest text-slate-400 uppercase"
                        >
                            <span>{{
                                company.company_code ?? company.company_name
                            }}</span>
                            <span class="text-slate-300">·</span>
                            <span>Vehicles</span>
                            <span class="text-slate-300">·</span>
                            <span>Details</span>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <h1
                                class="text-2xl font-bold tracking-tight text-slate-900"
                            >
                                {{ vehicle.plate_number }}
                            </h1>
                            <span
                                :class="[
                                    'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium',
                                    statusClass(vehicle.status),
                                ]"
                            >
                                <span
                                    :class="[
                                        'h-1.5 w-1.5 rounded-full',
                                        statusDot(vehicle.status),
                                    ]"
                                />
                                {{ humanize(vehicle.status) }}
                            </span>
                        </div>
                        <p class="text-sm text-slate-500">
                            Registered vehicle profile, assigned route, and
                            submitted documents.
                        </p>
                        <p
                            v-if="vehicle.remarks"
                            class="text-xs font-medium text-amber-700"
                        >
                            {{ vehicle.remarks }}
                        </p>
                    </div>

                    
                    <div class="flex shrink-0 items-center gap-2 self-start">
                        <Button
                            as-child
                            variant="outline"
                            class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100 hover:text-slate-800"
                        >
                            <Link :href="CompanyVehicleController.index().url">
                                <ArrowLeft class="mr-2 h-4 w-4" />
                                Back
                            </Link>
                        </Button>

                        
                        <DropdownMenu v-if="canUpdate || canToggle">
                            <DropdownMenuTrigger as-child>
                                <Button
                                    class="gap-2 rounded-lg border-0 bg-blue-700 text-sm font-semibold text-white shadow-sm hover:bg-blue-800"
                                >
                                    <MoreHorizontal class="h-4 w-4" />
                                    Actions
                                </Button>
                            </DropdownMenuTrigger>

                            <DropdownMenuContent
                                align="end"
                                class="w-56 rounded-xl border-slate-200 shadow-lg"
                            >
                                <DropdownMenuLabel
                                    class="text-xs font-semibold tracking-widest text-slate-400 uppercase"
                                >
                                    Manage Vehicle
                                </DropdownMenuLabel>

                                <DropdownMenuSeparator class="bg-slate-100" />

                                
                                <DropdownMenuItem
                                    v-if="
                                        canEditDocumentsForResubmission(vehicle)
                                    "
                                    as-child
                                    class="rounded-lg text-slate-700 focus:bg-amber-50 focus:text-amber-700"
                                >
                                    <Link
                                        :href="
                                            CompanyVehicleController.edit(
                                                vehicle.id,
                                            ).url
                                        "
                                    >
                                        <Pencil class="mr-2 h-4 w-4" />
                                        Resubmit Invalid/Expired Documents
                                    </Link>
                                </DropdownMenuItem>

                                
                                <DropdownMenuItem
                                    v-else-if="canUpdate"
                                    disabled
                                    class="rounded-lg text-slate-300"
                                >
                                    <Pencil class="mr-2 h-4 w-4" />
                                    Resubmit Invalid/Expired Documents
                                </DropdownMenuItem>

                                <DropdownMenuSeparator
                                    v-if="canUpdate && canToggle"
                                    class="bg-slate-100"
                                />

                                
                                <DropdownMenuItem
                                    v-if="canToggle && canToggleVehicle"
                                    :class="[
                                        'rounded-lg',
                                        toggleStatusClass(vehicle.status),
                                    ]"
                                    @click="statusDialog.open = true"
                                >
                                    <Power class="mr-2 h-4 w-4" />
                                    {{ toggleLabel(vehicle.status) }}
                                </DropdownMenuItem>

                                
                                <DropdownMenuItem
                                    v-else-if="canToggle"
                                    disabled
                                    class="rounded-lg text-slate-300"
                                >
                                    <Power class="mr-2 h-4 w-4" />
                                    {{ toggleLabel(vehicle.status) }}
                                </DropdownMenuItem>

                                
                                <div
                                    v-if="canToggle && !canToggleVehicle"
                                    class="px-2 pt-1 pb-2 text-left text-[11px] text-slate-400"
                                >
                                    {{ vehicleActionNote(vehicle) }}
                                </div>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>

                
                <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
                    <div
                        class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
                    >
                        <div
                            class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-blue-700"
                        >
                            <CarFront class="h-4 w-4 text-white" />
                        </div>
                        <p
                            class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase"
                        >
                            Vehicle Type
                        </p>
                        <p
                            class="mt-0.5 truncate text-sm font-bold text-slate-900"
                        >
                            {{ vehicle.vehicle_type || '—' }}
                        </p>
                    </div>

                    <div
                        class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
                    >
                        <div
                            class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-sky-600"
                        >
                            <MapPinned class="h-4 w-4 text-white" />
                        </div>
                        <p
                            class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase"
                        >
                            Assigned Route
                        </p>
                        <p
                            class="mt-0.5 truncate text-sm font-bold text-slate-900"
                        >
                            {{
                                selectedRoute?.route_name || 'No route assigned'
                            }}
                        </p>
                    </div>

                    <div
                        class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
                    >
                        <div
                            class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-600"
                        >
                            <FileText class="h-4 w-4 text-white" />
                        </div>
                        <p
                            class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase"
                        >
                            Documents
                        </p>
                        <p
                            class="mt-0.5 text-3xl font-bold text-slate-900 tabular-nums"
                        >
                            {{ uploadedDocumentsCount }}
                            <span class="text-lg font-medium text-slate-400"
                                >/ {{ requiredDocumentsCount }}</span
                            >
                        </p>
                    </div>

                    <div
                        class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
                    >
                        <div
                            class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-slate-600"
                        >
                            <CalendarDays class="h-4 w-4 text-white" />
                        </div>
                        <p
                            class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase"
                        >
                            Created
                        </p>
                        <p class="mt-0.5 text-sm font-bold text-slate-900">
                            {{ formatDate(vehicle.created_at) }}
                        </p>
                    </div>
                </div>

                
                <div class="grid gap-6 xl:grid-cols-[1fr_330px]">
                    
                    <div class="space-y-6">
                        
                        <div
                            class="rounded-xl border border-slate-200 bg-white shadow-sm"
                        >
                            <div class="border-b border-slate-100 px-6 py-4">
                                <h2
                                    class="text-base font-semibold text-slate-800"
                                >
                                    Company Information
                                </h2>
                                <p class="mt-0.5 text-xs text-slate-400">
                                    Registration ownership details for this
                                    vehicle.
                                </p>
                            </div>
                            <div class="grid gap-4 p-6 md:grid-cols-2">
                                <div class="space-y-1.5">
                                    <p
                                        class="text-xs font-semibold tracking-widest text-slate-400 uppercase"
                                    >
                                        Company
                                    </p>
                                    <div
                                        class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-700"
                                    >
                                        {{ company.company_name }}
                                    </div>
                                </div>
                                <div class="space-y-1.5">
                                    <p
                                        class="text-xs font-semibold tracking-widest text-slate-400 uppercase"
                                    >
                                        Company Code
                                    </p>
                                    <div
                                        class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 font-mono text-sm font-semibold text-slate-700"
                                    >
                                        {{ company.company_code ?? '—' }}
                                    </div>
                                </div>
                                <div class="space-y-1.5">
                                    <p
                                        class="text-xs font-semibold tracking-widest text-slate-400 uppercase"
                                    >
                                        Representative
                                    </p>
                                    <div
                                        class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-700"
                                    >
                                        {{ user.name }}
                                    </div>
                                </div>
                                <div class="space-y-1.5">
                                    <p
                                        class="text-xs font-semibold tracking-widest text-slate-400 uppercase"
                                    >
                                        Account Email
                                    </p>
                                    <div
                                        class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-medium text-slate-700"
                                    >
                                        {{ user.email }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        
                        <div
                            class="rounded-xl border border-slate-200 bg-white shadow-sm"
                        >
                            <div class="border-b border-slate-100 px-6 py-4">
                                <h2
                                    class="text-base font-semibold text-slate-800"
                                >
                                    Route Assignment
                                </h2>
                                <p class="mt-0.5 text-xs text-slate-400">
                                    Operating route and stop details for this
                                    vehicle.
                                </p>
                            </div>
                            <div class="p-6">
                                <VehicleRouteAssignment
                                    v-model="form.route_id"
                                    :routes="routes"
                                    :gates="gates"
                                    :map-config="mapConfig"
                                    readonly
                                />
                            </div>
                        </div>

                        
                        <div
                            class="rounded-xl border border-slate-200 bg-white shadow-sm"
                        >
                            <div class="border-b border-slate-100 px-6 py-4">
                                <h2
                                    class="text-base font-semibold text-slate-800"
                                >
                                    Vehicle Information
                                </h2>
                                <p class="mt-0.5 text-xs text-slate-400">
                                    Main registration and identification
                                    details.
                                </p>
                            </div>
                            <div class="p-6">
                                <VehicleBasicInfoForm
                                    :form="form"
                                    :vehicle-types="vehicleTypes"
                                    readonly
                                />
                            </div>
                        </div>

                        
                        <div
                            class="rounded-xl border border-slate-200 bg-white shadow-sm"
                        >
                            <div class="border-b border-slate-100 px-6 py-4">
                                <h2
                                    class="text-base font-semibold text-slate-800"
                                >
                                    Required Documents
                                </h2>
                                <p class="mt-0.5 text-xs text-slate-400">
                                    Open each file in a preview dialog without
                                    leaving this page.
                                </p>
                            </div>
                            <div
                                class="space-y-0 divide-y divide-slate-100 p-4"
                            >
                                <div
                                    v-for="document in orderedDocuments"
                                    :key="document.document_type"
                                    class="mb-3 overflow-hidden rounded-xl border border-slate-200 bg-white transition-colors last:mb-0 hover:border-blue-200"
                                >
                                    
                                    <div
                                        class="flex flex-col gap-4 p-4 md:flex-row md:items-start md:justify-between"
                                    >
                                        <div
                                            class="flex min-w-0 items-start gap-3"
                                        >
                                            <div
                                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                                                :class="
                                                    document.item
                                                        ? 'bg-blue-700'
                                                        : 'bg-slate-200'
                                                "
                                            >
                                                <component
                                                    :is="
                                                        isImage(document.item)
                                                            ? FileImage
                                                            : FileText
                                                    "
                                                    class="h-4 w-4"
                                                    :class="
                                                        document.item
                                                            ? 'text-white'
                                                            : 'text-slate-400'
                                                    "
                                                />
                                            </div>
                                            <div class="min-w-0">
                                                <p
                                                    class="text-sm font-semibold text-slate-800"
                                                >
                                                    {{ document.label }}
                                                </p>
                                                <p
                                                    class="truncate text-xs text-slate-400"
                                                >
                                                    {{
                                                        document.item
                                                            ?.file_name ??
                                                        'No file uploaded yet'
                                                    }}
                                                </p>
                                            </div>
                                        </div>

                                        <div
                                            class="flex flex-wrap items-center gap-2"
                                        >
                                            <span
                                                :class="[
                                                    'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium',
                                                    statusClass(
                                                        document.item?.status ??
                                                            'pending',
                                                    ),
                                                ]"
                                            >
                                                <span
                                                    :class="[
                                                        'h-1.5 w-1.5 rounded-full',
                                                        statusDot(
                                                            document.item
                                                                ?.status ??
                                                                'pending',
                                                        ),
                                                    ]"
                                                />
                                                {{
                                                    humanize(
                                                        document.item?.status ??
                                                            'pending',
                                                    )
                                                }}
                                            </span>

                                            <Button
                                                v-if="document.item?.file_url"
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                class="rounded-lg border-slate-200 text-slate-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-800"
                                                @click="
                                                    openPreview(document.item)
                                                "
                                            >
                                                <Eye
                                                    class="mr-1.5 h-3.5 w-3.5"
                                                />
                                                Preview
                                            </Button>

                                            <Button
                                                v-if="document.item?.file_url"
                                                as-child
                                                variant="outline"
                                                size="sm"
                                                class="rounded-lg border-slate-200 text-slate-700 hover:bg-slate-50"
                                            >
                                                <a
                                                    :href="
                                                        documentDownloadUrl(
                                                            document.item,
                                                        )
                                                    "
                                                    download
                                                >
                                                    <Download
                                                        class="mr-1.5 h-3.5 w-3.5"
                                                    />
                                                    Download
                                                </a>
                                            </Button>
                                        </div>
                                    </div>

                                    
                                    <div
                                        class="grid gap-4 border-t border-slate-100 bg-slate-50/60 px-4 py-3 md:grid-cols-2 xl:grid-cols-4"
                                    >
                                        <div>
                                            <p
                                                class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase"
                                            >
                                                Issued At
                                            </p>
                                            <p
                                                class="mt-0.5 text-sm font-medium text-slate-700"
                                            >
                                                {{
                                                    formatDate(
                                                        document.item
                                                            ?.issued_at,
                                                    )
                                                }}
                                            </p>
                                        </div>
                                        <div>
                                            <p
                                                class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase"
                                            >
                                                Expires At
                                            </p>
                                            <p
                                                class="mt-0.5 text-sm font-medium text-slate-700"
                                            >
                                                {{
                                                    formatDate(
                                                        document.item
                                                            ?.expires_at,
                                                    )
                                                }}
                                            </p>
                                        </div>
                                        <div>
                                            <p
                                                class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase"
                                            >
                                                File Size
                                            </p>
                                            <p
                                                class="mt-0.5 text-sm font-medium text-slate-700"
                                            >
                                                {{
                                                    formatBytes(
                                                        document.item
                                                            ?.file_size,
                                                    )
                                                }}
                                            </p>
                                        </div>
                                        <div>
                                            <p
                                                class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase"
                                            >
                                                Uploaded
                                            </p>
                                            <p
                                                class="mt-0.5 text-sm font-medium text-slate-700"
                                            >
                                                {{
                                                    formatDateTime(
                                                        document.item
                                                            ?.created_at,
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    
                    <div class="space-y-4">
                        
                        <div
                            class="rounded-xl border border-slate-200 bg-white shadow-sm"
                        >
                            <div class="border-b border-slate-100 px-5 py-4">
                                <h3
                                    class="text-sm font-semibold text-slate-800"
                                >
                                    Vehicle Summary
                                </h3>
                                <p class="mt-0.5 text-xs text-slate-400">
                                    Quick overview of the selected vehicle
                                    record.
                                </p>
                            </div>
                            <div class="p-5">
                                <VehicleSummaryCard
                                    :form="form"
                                    :selected-route-name="
                                        selectedRoute?.route_name
                                    "
                                    :required-documents-count="
                                        requiredDocumentsCount
                                    "
                                    :user-name="user.name"
                                    readonly
                                />
                            </div>
                        </div>

                        
                        <div
                            class="rounded-xl border border-slate-200 bg-white shadow-sm"
                        >
                            <div class="border-b border-slate-100 px-5 py-4">
                                <h3
                                    class="text-sm font-semibold text-slate-800"
                                >
                                    Document Status
                                </h3>
                                <p class="mt-0.5 text-xs text-slate-400">
                                    Snapshot of the current document review
                                    progress.
                                </p>
                            </div>
                            <div class="divide-y divide-slate-100">
                                <div
                                    class="flex items-center justify-between px-5 py-3"
                                >
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="flex h-7 w-7 items-center justify-center rounded-md bg-emerald-100"
                                        >
                                            <CheckCircle2
                                                class="h-3.5 w-3.5 text-emerald-700"
                                            />
                                        </div>
                                        <p
                                            class="text-sm font-medium text-slate-700"
                                        >
                                            Approved
                                        </p>
                                    </div>
                                    <span
                                        class="text-lg font-bold text-emerald-700 tabular-nums"
                                        >{{ approvedDocumentsCount }}</span
                                    >
                                </div>

                                <div
                                    class="flex items-center justify-between px-5 py-3"
                                >
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="flex h-7 w-7 items-center justify-center rounded-md bg-amber-100"
                                        >
                                            <Clock3
                                                class="h-3.5 w-3.5 text-amber-700"
                                            />
                                        </div>
                                        <p
                                            class="text-sm font-medium text-slate-700"
                                        >
                                            Pending
                                        </p>
                                    </div>
                                    <span
                                        class="text-lg font-bold text-amber-700 tabular-nums"
                                        >{{ pendingDocumentsCount }}</span
                                    >
                                </div>

                                <div
                                    class="flex items-center justify-between px-5 py-3"
                                >
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="flex h-7 w-7 items-center justify-center rounded-md bg-blue-100"
                                        >
                                            <Bus
                                                class="h-3.5 w-3.5 text-blue-700"
                                            />
                                        </div>
                                        <p
                                            class="text-sm font-medium text-slate-700"
                                        >
                                            Plate Number
                                        </p>
                                    </div>
                                    <span
                                        class="rounded bg-slate-100 px-2 py-0.5 font-mono text-xs font-semibold text-slate-700"
                                    >
                                        {{ vehicle.plate_number }}
                                    </span>
                                </div>

                                <div
                                    class="flex items-center justify-between px-5 py-3"
                                >
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="flex h-7 w-7 items-center justify-center rounded-md bg-slate-100"
                                        >
                                            <ShieldCheck
                                                class="h-3.5 w-3.5 text-slate-600"
                                            />
                                        </div>
                                        <p
                                            class="text-sm font-medium text-slate-700"
                                        >
                                            Vehicle Status
                                        </p>
                                    </div>
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium',
                                            statusClass(vehicle.status),
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                'h-1.5 w-1.5 rounded-full',
                                                statusDot(vehicle.status),
                                            ]"
                                        />
                                        {{ humanize(vehicle.status) }}
                                    </span>
                                </div>

                                <div
                                    class="flex items-center justify-between px-5 py-3"
                                >
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="flex h-7 w-7 items-center justify-center rounded-md bg-slate-100"
                                        >
                                            <UserCircle2
                                                class="h-3.5 w-3.5 text-slate-600"
                                            />
                                        </div>
                                        <p
                                            class="text-sm font-medium text-slate-700"
                                        >
                                            Submitted By
                                        </p>
                                    </div>
                                    <p
                                        class="text-sm font-semibold text-slate-700"
                                    >
                                        {{ user.name }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <AlertDialog v-model:open="statusDialog.open">
            <AlertDialogContent class="rounded-2xl">
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        {{ toggleLabel(vehicle.status) }}
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        This will update the status of
                        <span class="font-semibold text-slate-800">{{
                            vehicle.plate_number
                        }}</span
                        >. Are you sure you want to continue?
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel class="rounded-lg"
                        >Cancel</AlertDialogCancel
                    >
                    <AlertDialogAction
                        class="rounded-lg border-0 bg-blue-700 text-white hover:bg-blue-800"
                        @click="confirmToggleStatus"
                    >
                        Confirm
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        
        <Dialog v-model:open="previewOpen">
            <DialogContent
                class="flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl p-0"
            >
                <DialogHeader
                    class="border-b border-slate-100 bg-slate-50 px-6 py-4"
                >
                    <DialogTitle class="truncate text-slate-800">
                        {{
                            previewDoc?.file_name ??
                            humanize(previewDoc?.document_type)
                        }}
                    </DialogTitle>
                    <DialogDescription
                        class="flex flex-wrap items-center gap-2"
                    >
                        <span
                            :class="[
                                'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium',
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
                        </span>
                        <span class="text-slate-400">·</span>
                        <span class="text-xs text-slate-500">{{
                            humanize(previewDoc?.document_type)
                        }}</span>
                        <template v-if="previewDoc?.file_size">
                            <span class="text-slate-400">·</span>
                            <span class="text-xs text-slate-500">{{
                                formatBytes(previewDoc.file_size)
                            }}</span>
                        </template>
                    </DialogDescription>
                </DialogHeader>

                <div class="flex-1 overflow-auto bg-slate-100/70 p-4">
                    <div
                        v-if="previewDoc?.file_url && isImage(previewDoc)"
                        class="flex min-h-[65vh] items-center justify-center"
                    >
                        <img
                            :src="previewDoc.file_url"
                            :alt="previewDoc.file_name ?? 'Document preview'"
                            class="max-h-[75vh] w-auto max-w-full rounded-lg border bg-white object-contain shadow-sm"
                        />
                    </div>

                    <div
                        v-else-if="previewDoc?.file_url && isPdf(previewDoc)"
                        class="h-[75vh] overflow-hidden rounded-lg border bg-white"
                    >
                        <iframe
                            :src="previewDoc.file_url"
                            class="h-full w-full"
                            title="PDF Preview"
                        />
                    </div>

                    <div
                        v-else
                        class="flex min-h-[50vh] flex-col items-center justify-center gap-3 rounded-lg border bg-white p-6 text-center"
                    >
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100"
                        >
                            <FileText class="h-6 w-6 text-slate-400" />
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-700">
                                Preview not available
                            </p>
                            <p class="mt-0.5 text-xs text-slate-400">
                                This file type cannot be previewed directly in
                                the dialog.
                            </p>
                        </div>
                        <Button
                            v-if="previewDoc?.file_url"
                            as-child
                            variant="outline"
                            class="rounded-lg"
                        >
                            <a :href="documentDownloadUrl(previewDoc)" download>
                                <Download class="mr-2 h-4 w-4" />
                                Download File
                            </a>
                        </Button>
                    </div>
                </div>

                <div
                    class="flex items-center justify-end gap-2 border-t border-slate-100 bg-white px-6 py-4"
                >
                    <Button
                        v-if="previewDoc?.file_url"
                        as-child
                        variant="outline"
                        class="rounded-lg"
                    >
                        <a :href="documentDownloadUrl(previewDoc)" download>
                            <Download class="mr-2 h-4 w-4" />
                            Download
                        </a>
                    </Button>
                    <Button
                        type="button"
                        class="rounded-lg border-0 bg-blue-700 font-semibold text-white hover:bg-blue-800"
                        @click="previewOpen = false"
                    >
                        Close
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    </ExternalLayout>
</template>
