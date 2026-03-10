<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

import ExternalLayout from '@/layouts/ExternalLayout.vue';

import VehicleBasicInfoForm from '@/components/company/vehicles/VehicleBasicInfoForm.vue';
import VehicleRouteAssignment from '@/components/company/vehicles/VehicleRouteAssignment.vue';
import VehicleSummaryCard from '@/components/company/vehicles/VehicleSummaryCard.vue';

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
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';

import {
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
    ShieldCheck,
    UserCircle2,
} from 'lucide-vue-next';

import CompanyVehicleController from '@/actions/App/Http/Controllers/CompanyVehicleController';

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
    gate?: {
        id: number;
        gate_name: string;
    } | null;
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
        defaultCenter: {
            lng: number;
            lat: number;
        };
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

const selectedRoute = computed(() => {
    return (
        props.routes.find(
            (route) => String(route.id) === String(form.route_id),
        ) ??
        props.vehicle.route ??
        null
    );
});

const requiredDocumentsCount = computed(
    () => Object.keys(props.docTypes).length,
);
const uploadedDocumentsCount = computed(() => props.vehicle.documents.length);

const previewOpen = ref(false);
const previewDoc = ref<VehicleDocument | null>(null);

const orderedDocuments = computed(() => {
    return Object.keys(props.docTypes).map((docKey) => {
        const existing = props.vehicle.documents.find(
            (item) => item.document_type === docKey,
        );

        return {
            document_type: docKey,
            label: props.docTypes[docKey],
            item: existing ?? null,
        };
    });
});

const approvedDocumentsCount = computed(() => {
    return props.vehicle.documents.filter((doc) => doc.status === 'approved')
        .length;
});

const pendingDocumentsCount = computed(() => {
    return props.vehicle.documents.filter((doc) => doc.status === 'pending')
        .length;
});

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

function statusVariant(status?: string | null) {
    switch (status) {
        case 'active':
        case 'approved':
        case 'verified':
            return 'default';
        case 'pending':
        case 'for_verification':
            return 'secondary';
        case 'rejected':
        case 'inactive':
            return 'destructive';
        default:
            return 'outline';
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
        fileName.endsWith('.jpg') ||
        fileName.endsWith('.jpeg') ||
        fileName.endsWith('.png') ||
        fileName.endsWith('.webp')
    );
}

function documentDownloadUrl(doc?: VehicleDocument | null) {
    return doc?.download_url || doc?.file_url || '#';
}
</script>

<template>
    <Head :title="`Vehicle ${vehicle.plate_number}`" />

    <ExternalLayout :company="company" :user="user">
        <div class="mx-auto max-w-7xl space-y-6 p-4 md:p-6">
            <div
                class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between"
            >
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge variant="secondary">Vehicles</Badge>
                        <Badge variant="outline">Details</Badge>
                        <Badge :variant="statusVariant(vehicle.status)">
                            {{ humanize(vehicle.status) }}
                        </Badge>
                    </div>

                    <div class="space-y-1">
                        <h1
                            class="text-2xl font-semibold tracking-tight md:text-3xl"
                        >
                            {{ vehicle.plate_number }}
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            Registered vehicle profile, assigned route, and
                            submitted documents.
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Button as-child variant="outline">
                        <Link :href="CompanyVehicleController.index().url">
                            Back to Vehicles
                        </Link>
                    </Button>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <Card class="border-border/60 shadow-sm">
                    <CardContent class="flex items-start gap-3 p-5">
                        <div
                            class="rounded-xl border p-2.5 text-muted-foreground"
                        >
                            <CarFront class="h-5 w-5" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-muted-foreground">
                                Vehicle Type
                            </p>
                            <p class="truncate text-sm font-semibold">
                                {{ vehicle.vehicle_type || '—' }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-border/60 shadow-sm">
                    <CardContent class="flex items-start gap-3 p-5">
                        <div
                            class="rounded-xl border p-2.5 text-muted-foreground"
                        >
                            <MapPinned class="h-5 w-5" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-muted-foreground">
                                Assigned Route
                            </p>
                            <p class="truncate text-sm font-semibold">
                                {{
                                    selectedRoute?.route_name ||
                                    'No route assigned'
                                }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-border/60 shadow-sm">
                    <CardContent class="flex items-start gap-3 p-5">
                        <div
                            class="rounded-xl border p-2.5 text-muted-foreground"
                        >
                            <FileText class="h-5 w-5" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-muted-foreground">
                                Documents Uploaded
                            </p>
                            <p class="truncate text-sm font-semibold">
                                {{ uploadedDocumentsCount }} /
                                {{ requiredDocumentsCount }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-border/60 shadow-sm">
                    <CardContent class="flex items-start gap-3 p-5">
                        <div
                            class="rounded-xl border p-2.5 text-muted-foreground"
                        >
                            <CalendarDays class="h-5 w-5" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs text-muted-foreground">Created</p>
                            <p class="truncate text-sm font-semibold">
                                {{ formatDate(vehicle.created_at) }}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-6 xl:grid-cols-[1fr_330px]">
                <div class="space-y-6">
                    <Card class="shadow-sm">
                        <CardHeader>
                            <CardTitle>Company Information</CardTitle>
                            <CardDescription>
                                Registration ownership details for this vehicle.
                            </CardDescription>
                        </CardHeader>

                        <CardContent class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <p class="text-sm font-medium">Company</p>
                                <div
                                    class="rounded-lg border bg-muted/20 px-3 py-2.5 text-sm"
                                >
                                    {{ company.company_name }}
                                </div>
                            </div>

                            <div class="space-y-2">
                                <p class="text-sm font-medium">Company Code</p>
                                <div
                                    class="rounded-lg border bg-muted/20 px-3 py-2.5 text-sm"
                                >
                                    {{ company.company_code ?? '—' }}
                                </div>
                            </div>

                            <div class="space-y-2">
                                <p class="text-sm font-medium">
                                    Representative
                                </p>
                                <div
                                    class="rounded-lg border bg-muted/20 px-3 py-2.5 text-sm"
                                >
                                    {{ user.name }}
                                </div>
                            </div>

                            <div class="space-y-2">
                                <p class="text-sm font-medium">Account Email</p>
                                <div
                                    class="rounded-lg border bg-muted/20 px-3 py-2.5 text-sm"
                                >
                                    {{ user.email }}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="shadow-sm">
                        <CardHeader>
                            <CardTitle>Route Assignment</CardTitle>
                            <CardDescription>
                                Operating route and stop details for this
                                vehicle.
                            </CardDescription>
                        </CardHeader>

                        <CardContent>
                            <VehicleRouteAssignment
                                v-model="form.route_id"
                                :routes="routes"
                                :gates="gates"
                                :map-config="mapConfig"
                                readonly
                            />
                        </CardContent>
                    </Card>

                    <Card class="shadow-sm">
                        <CardHeader>
                            <CardTitle>Vehicle Information</CardTitle>
                            <CardDescription>
                                Main registration and identification details.
                            </CardDescription>
                        </CardHeader>

                        <CardContent>
                            <VehicleBasicInfoForm
                                :form="form"
                                :vehicle-types="vehicleTypes"
                                readonly
                            />
                        </CardContent>
                    </Card>

                    <Card class="shadow-sm">
                        <CardHeader>
                            <CardTitle>Required Documents</CardTitle>
                            <CardDescription>
                                Open each file in a preview dialog without
                                leaving this page.
                            </CardDescription>
                        </CardHeader>

                        <CardContent class="space-y-4">
                            <div
                                v-for="document in orderedDocuments"
                                :key="document.document_type"
                                class="overflow-hidden rounded-xl border"
                            >
                                <div
                                    class="flex flex-col gap-4 p-4 md:flex-row md:items-start md:justify-between"
                                >
                                    <div class="flex min-w-0 items-start gap-3">
                                        <div
                                            class="rounded-xl border p-2.5 text-muted-foreground"
                                        >
                                            <component
                                                :is="
                                                    isImage(document.item)
                                                        ? FileImage
                                                        : FileText
                                                "
                                                class="h-5 w-5"
                                            />
                                        </div>

                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold">
                                                {{ document.label }}
                                            </p>
                                            <p
                                                class="truncate text-xs text-muted-foreground"
                                            >
                                                {{
                                                    document.item?.file_name ??
                                                    'No file uploaded yet'
                                                }}
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <Badge
                                            :variant="
                                                statusVariant(
                                                    document.item?.status ??
                                                        'pending',
                                                )
                                            "
                                        >
                                            {{
                                                humanize(
                                                    document.item?.status ??
                                                        'pending',
                                                )
                                            }}
                                        </Badge>

                                        <Button
                                            v-if="document.item?.file_url"
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            @click="openPreview(document.item)"
                                        >
                                            <Eye class="mr-2 h-4 w-4" />
                                            Preview
                                        </Button>

                                        <Button
                                            v-if="document.item?.file_url"
                                            as-child
                                            variant="outline"
                                            size="sm"
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
                                                    class="mr-2 h-4 w-4"
                                                />
                                                Download
                                            </a>
                                        </Button>
                                    </div>
                                </div>

                                <Separator />

                                <div
                                    class="grid gap-4 p-4 md:grid-cols-2 xl:grid-cols-4"
                                >
                                    <div class="space-y-1">
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            Issued At
                                        </p>
                                        <p class="text-sm font-medium">
                                            {{
                                                formatDate(
                                                    document.item?.issued_at,
                                                )
                                            }}
                                        </p>
                                    </div>

                                    <div class="space-y-1">
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            Expires At
                                        </p>
                                        <p class="text-sm font-medium">
                                            {{
                                                formatDate(
                                                    document.item?.expires_at,
                                                )
                                            }}
                                        </p>
                                    </div>

                                    <div class="space-y-1">
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            File Size
                                        </p>
                                        <p class="text-sm font-medium">
                                            {{
                                                formatBytes(
                                                    document.item?.file_size,
                                                )
                                            }}
                                        </p>
                                    </div>

                                    <div class="space-y-1">
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            Uploaded
                                        </p>
                                        <p class="text-sm font-medium">
                                            {{
                                                formatDateTime(
                                                    document.item?.created_at,
                                                )
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div class="space-y-6">
                    <Card class="shadow-sm">
                        <CardHeader>
                            <CardTitle>Vehicle Summary</CardTitle>
                            <CardDescription>
                                Quick overview of the selected vehicle record.
                            </CardDescription>
                        </CardHeader>

                        <CardContent>
                            <VehicleSummaryCard
                                :form="form"
                                :selected-route-name="selectedRoute?.route_name"
                                :required-documents-count="
                                    requiredDocumentsCount
                                "
                                :user-name="user.name"
                                readonly
                            />
                        </CardContent>
                    </Card>

                    <Card class="shadow-sm">
                        <CardHeader>
                            <CardTitle>Document Status</CardTitle>
                            <CardDescription>
                                Snapshot of the current document review
                                progress.
                            </CardDescription>
                        </CardHeader>

                        <CardContent class="space-y-3">
                            <div class="rounded-xl border p-4">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="rounded-xl border p-2 text-muted-foreground"
                                    >
                                        <CheckCircle2 class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            Approved
                                        </p>
                                        <p class="text-lg font-semibold">
                                            {{ approvedDocumentsCount }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-xl border p-4">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="rounded-xl border p-2 text-muted-foreground"
                                    >
                                        <Clock3 class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            Pending
                                        </p>
                                        <p class="text-lg font-semibold">
                                            {{ pendingDocumentsCount }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-xl border p-4">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="rounded-xl border p-2 text-muted-foreground"
                                    >
                                        <Bus class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            Plate Number
                                        </p>
                                        <p class="text-sm font-semibold">
                                            {{ vehicle.plate_number }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-xl border p-4">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="rounded-xl border p-2 text-muted-foreground"
                                    >
                                        <ShieldCheck class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            Vehicle Status
                                        </p>
                                        <p class="text-sm font-semibold">
                                            {{ humanize(vehicle.status) }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-xl border p-4">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="rounded-xl border p-2 text-muted-foreground"
                                    >
                                        <UserCircle2 class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            Submitted By
                                        </p>
                                        <p class="text-sm font-semibold">
                                            {{ user.name }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>

        <Dialog v-model:open="previewOpen">
            <DialogContent
                class="flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden p-0"
            >
                <DialogHeader class="border-b px-6 py-4">
                    <DialogTitle class="truncate">
                        {{
                            previewDoc?.file_name ??
                            humanize(previewDoc?.document_type)
                        }}
                    </DialogTitle>
                    <DialogDescription
                        class="flex flex-wrap items-center gap-2"
                    >
                        <Badge :variant="statusVariant(previewDoc?.status)">
                            {{ humanize(previewDoc?.status) }}
                        </Badge>
                        <span>{{ humanize(previewDoc?.document_type) }}</span>
                        <span v-if="previewDoc?.file_size">
                            • {{ formatBytes(previewDoc?.file_size) }}
                        </span>
                    </DialogDescription>
                </DialogHeader>

                <div class="flex-1 overflow-auto bg-muted/20 p-4">
                    <div
                        v-if="previewDoc?.file_url && isImage(previewDoc)"
                        class="flex min-h-[65vh] items-center justify-center"
                    >
                        <img
                            :src="previewDoc.file_url"
                            :alt="previewDoc.file_name ?? 'Document preview'"
                            class="max-h-[75vh] w-auto max-w-full rounded-lg border bg-background object-contain shadow-sm"
                        />
                    </div>

                    <div
                        v-else-if="previewDoc?.file_url && isPdf(previewDoc)"
                        class="h-[75vh] overflow-hidden rounded-lg border bg-background"
                    >
                        <iframe
                            :src="previewDoc.file_url"
                            class="h-full w-full"
                            title="PDF Preview"
                        />
                    </div>

                    <div
                        v-else
                        class="flex min-h-[50vh] flex-col items-center justify-center gap-3 rounded-lg border bg-background p-6 text-center"
                    >
                        <FileText class="h-10 w-10 text-muted-foreground" />
                        <div class="space-y-1">
                            <p class="text-sm font-medium">
                                Preview not available
                            </p>
                            <p class="text-sm text-muted-foreground">
                                This file type cannot be previewed directly in
                                the dialog.
                            </p>
                        </div>

                        <Button
                            v-if="previewDoc?.file_url"
                            as-child
                            variant="outline"
                        >
                            <a :href="documentDownloadUrl(previewDoc)" download>
                                <Download class="mr-2 h-4 w-4" />
                                Download File
                            </a>
                        </Button>
                    </div>
                </div>

                <div
                    class="flex items-center justify-end gap-2 border-t px-6 py-4"
                >
                    <Button
                        v-if="previewDoc?.file_url"
                        as-child
                        variant="outline"
                    >
                        <a :href="documentDownloadUrl(previewDoc)" download>
                            <Download class="mr-2 h-4 w-4" />
                            Download
                        </a>
                    </Button>

                    <Button type="button" @click="previewOpen = false">
                        Close
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    </ExternalLayout>
</template>
