<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import ExternalLayout from '@/layouts/ExternalLayout.vue';

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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

import CompanyVehicleController from '@/actions/App/Http/Controllers/CompanyVehicleController';
import {
    Building2,
    Bus,
    CheckCircle2,
    Eye,
    Filter,
    FileText,
    MoreHorizontal,
    Pencil,
    Plus,
    Power,
    Route as RouteIcon,
    ArrowUpDown,
    ArrowUp,
    ArrowDown,
    X,
} from 'lucide-vue-next';

/* ======================================================
   Permissions
====================================================== */
import { can } from '@/lib/can';

const canCreate      = can('external_vehicles.create');
const canUpdate      = can('external_vehicles.update');
const canToggle      = can('external_vehicles.toggleStatus');

/* ======================================================
   Types
====================================================== */
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

type VehicleRoute = {
    id: number;
    route_name: string;
};

type VehicleDocument = {
    id: number;
    vehicle_id: number;
    document_type: string;
    status: string;
    expires_at?: string | null;
};

type VehicleItem = {
    id: number;
    company_id: number;
    route_id?: number | null;
    vehicle_type: string;
    plate_number: string;
    body_number?: string | null;
    capacity?: number | null;
    color?: string | null;
    make_model?: string | null;
    status: string;
    remarks?: string | null;
    created_at?: string | null;
    route?: VehicleRoute | null;
    documents?: VehicleDocument[];
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedVehicles = {
    data: VehicleItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
};

/* ======================================================
   Props
====================================================== */
const props = defineProps<{
    company: Company;
    user: User;
    vehicles: PaginatedVehicles;
    filters: {
        search?: string | null;
        status?: string | null;
        vehicle_type?: string | null;
        route_id?: string | null;
        sort_by?: 'capacity' | 'created_at' | 'status' | null;
        sort_dir?: 'asc' | 'desc' | null;
    };
    routes: { id: number; route_name: string }[];
}>();

/* ======================================================
   Helpers
====================================================== */
function humanize(value?: string | null) {
    if (!value) return '—';
    return value.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
}

function formatDate(value?: string | null) {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

/* ======================================================
   Badge helpers
====================================================== */
function vehicleStatusClass(status?: string | null) {
    if (status === 'active')    return 'bg-emerald-100 text-emerald-700 border-emerald-200';
    if (status === 'inactive')  return 'bg-rose-100 text-rose-600 border-rose-200';
    if (status === 'suspended') return 'bg-orange-100 text-orange-700 border-orange-200';
    if (status === 'pending')   return 'bg-amber-100 text-amber-700 border-amber-200';
    return 'bg-slate-100 text-slate-500 border-0';
}

function vehicleStatusDot(status?: string | null) {
    if (status === 'active')    return 'bg-emerald-500';
    if (status === 'inactive')  return 'bg-rose-500';
    if (status === 'suspended') return 'bg-orange-500';
    if (status === 'pending')   return 'bg-amber-500';
    return 'bg-slate-400';
}

function documentStatusClass(status?: string | null) {
    if (status === 'approved') return 'bg-emerald-100 text-emerald-700 border-emerald-200';
    if (status === 'pending')  return 'bg-amber-100 text-amber-700 border-amber-200';
    if (status === 'rejected') return 'bg-rose-100 text-rose-600 border-rose-200';
    if (status === 'expired')  return 'bg-rose-100 text-rose-600 border-rose-200';
    return 'bg-slate-100 text-slate-500 border-0';
}

function documentStatusDot(status?: string | null) {
    if (status === 'approved') return 'bg-emerald-500';
    if (status === 'pending')  return 'bg-amber-500';
    if (status === 'rejected') return 'bg-rose-500';
    if (status === 'expired')  return 'bg-rose-500';
    return 'bg-slate-400';
}

function documentsCount(documents?: VehicleDocument[]) {
    return documents?.length ?? 0;
}

function isDocExpired(doc?: VehicleDocument) {
    if (!doc?.expires_at) return false;
    return new Date(doc.expires_at) < new Date();
}

/* ======================================================
   Business logic guards
====================================================== */
function isSuspended(status?: string | null) {
    return status === 'suspended';
}

const hasDocuments = (vehicle: VehicleItem) => !!vehicle.documents?.length;
const hasPendingOrRejected = (vehicle: VehicleItem) =>
    vehicle.documents?.some((doc) => ['pending', 'rejected', 'invalid'].includes(doc.status)) ?? false;
const hasExpiredDocs = (vehicle: VehicleItem) =>
    vehicle.documents?.some((doc) => isDocExpired(doc)) ?? false;

function businessCanEdit(vehicle: VehicleItem) {
    // Editing allowed only when there is at least one expired document
    return !isSuspended(vehicle.status) && hasExpiredDocs(vehicle);
}

function businessCanActivate(vehicle: VehicleItem) {
    if (isSuspended(vehicle.status)) return false;
    if (!hasDocuments(vehicle)) return false;
    if (hasPendingOrRejected(vehicle)) return false;
    if (hasExpiredDocs(vehicle)) return false;
    return true;
}

function businessCanToggle(vehicle: VehicleItem) {
    if (vehicle.status === 'active') return !isSuspended(vehicle.status);
    if (vehicle.status === 'inactive') return businessCanActivate(vehicle);
    return false;
}

// Combined: permission AND business rule
function canEditVehicle(vehicle: VehicleItem) {
    return canUpdate && businessCanEdit(vehicle);
}

function canToggleVehicle(vehicle: VehicleItem) {
    return canToggle && businessCanToggle(vehicle);
}

function toggleLabel(status?: string | null) {
    return status === 'active' ? 'Set Inactive' : 'Set Active';
}

function toggleStatusClass(status?: string | null) {
    return status === 'active'
        ? 'text-rose-600 focus:text-rose-600 focus:bg-rose-50'
        : 'text-emerald-700 focus:text-emerald-700 focus:bg-emerald-50';
}

function firstBlockingReason(vehicle: VehicleItem) {
    if (isSuspended(vehicle.status)) return 'Suspended vehicles cannot change status.';
    if (!hasDocuments(vehicle)) return 'Upload required documents first.';
    if (hasPendingOrRejected(vehicle)) return 'Documents must be approved before activation.';
    if (hasExpiredDocs(vehicle)) return 'Renew expired documents before activation.';
    return '';
}

function vehicleActionNote(vehicle: VehicleItem) {
    return firstBlockingReason(vehicle);
}

/* ======================================================
   Computed stats
====================================================== */
const totalVehicles = computed(() => props.vehicles.total ?? 0);

const activeVehicles = computed(() =>
    props.vehicles.data.filter((v) => v.status === 'active').length,
);

const resultsLabel = computed(() => {
    const from = props.vehicles.from ?? 0;
    const to   = props.vehicles.to ?? props.vehicles.data.length;
    const total = props.vehicles.total ?? props.vehicles.data.length;
    if (!total) return 'No results';
    return `${from}-${to} of ${total} vehicles`;
});

/* ======================================================
   Filters & sorting
====================================================== */
type SortField = 'capacity' | 'created_at' | 'status' | null;
type SortDir   = 'asc' | 'desc';

const statusFilter      = ref<string>(props.filters.status ?? 'all');
const vehicleTypeFilter = ref<string>(props.filters.vehicle_type ?? 'all');
const routeFilter       = ref<string>(props.filters.route_id ?? 'all');
const sortBy            = ref<SortField>(props.filters.sort_by ?? null);
const sortDir           = ref<SortDir>(props.filters.sort_dir ?? 'asc');

const hasActiveFilters = computed(() =>
    (statusFilter.value && statusFilter.value !== 'all') ||
    (vehicleTypeFilter.value && vehicleTypeFilter.value !== 'all') ||
    (routeFilter.value && routeFilter.value !== 'all') ||
    sortBy.value !== null,
);

function applyFilters(overrides: Record<string, string | null | undefined> = {}) {
    router.get(
        CompanyVehicleController.index().url,
        {
            search:       props.filters.search ?? undefined,
            status:       statusFilter.value !== 'all'      ? statusFilter.value      : undefined,
            vehicle_type: vehicleTypeFilter.value !== 'all' ? vehicleTypeFilter.value : undefined,
            route_id:     routeFilter.value !== 'all'       ? routeFilter.value       : undefined,
            sort_by:      sortBy.value ?? undefined,
            sort_dir:     sortBy.value ? sortDir.value : undefined,
            ...overrides,
        },
        { preserveState: true, replace: true, only: ['vehicles', 'filters', 'flash'] },
    );
}

function onStatusChange(val: string) {
    statusFilter.value = val;
    applyFilters();
}

function onVehicleTypeChange(val: string) {
    vehicleTypeFilter.value = val;
    applyFilters();
}

function onRouteChange(val: string) {
    routeFilter.value = val;
    applyFilters();
}

function clearFilters() {
    statusFilter.value      = 'all';
    vehicleTypeFilter.value = 'all';
    routeFilter.value       = 'all';
    sortBy.value            = null;
    sortDir.value           = 'asc';
    applyFilters({
        status: undefined, vehicle_type: undefined,
        route_id: undefined, sort_by: undefined, sort_dir: undefined,
    });
}

/* ======================================================
   Toggle status dialogs
====================================================== */
const inactiveDialog = reactive({
    open: false,
    vehicle: null as VehicleItem | null,
    remarks: '',
});

const activateDialog = reactive({
    open: false,
    vehicle: null as VehicleItem | null,
});

const inactiveDialogOpen = computed({
    get: () => inactiveDialog.open,
    set: (val) => { inactiveDialog.open = val; },
});

const activateDialogOpen = computed({
    get: () => activateDialog.open,
    set: (val) => { activateDialog.open = val; },
});

function openDeactivate(vehicle: VehicleItem) {
    if (!canToggleVehicle(vehicle) || vehicle.status !== 'active') return;
    inactiveDialog.vehicle = vehicle;
    inactiveDialog.remarks = '';
    inactiveDialog.open    = true;
}

function submitDeactivate() {
    if (!inactiveDialog.vehicle) return;
    router.patch(
        CompanyVehicleController.toggleStatus(inactiveDialog.vehicle.id).url,
        { remarks: inactiveDialog.remarks },
        {
            preserveScroll: true,
            onSuccess: () => {
                inactiveDialog.open    = false;
                inactiveDialog.vehicle = null;
                inactiveDialog.remarks = '';
            },
        },
    );
}

function openActivate(vehicle: VehicleItem) {
    if (!canToggleVehicle(vehicle) || vehicle.status !== 'inactive') return;
    activateDialog.vehicle = vehicle;
    activateDialog.open    = true;
}

function confirmActivate() {
    if (!activateDialog.vehicle) return;
    router.patch(
        CompanyVehicleController.toggleStatus(activateDialog.vehicle.id).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                activateDialog.open    = false;
                activateDialog.vehicle = null;
            },
        },
    );
}
</script>

<template>
    <Head title="Registered Vehicles" />

    <ExternalLayout :company="company" :user="user">
        <div class="min-h-screen bg-slate-50/60">
            <div class="mx-auto max-w-7xl space-y-6 p-4 md:p-8">

                <!-- -- Page header ----------------------------- -->
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-slate-400">
                            <Building2 class="h-3.5 w-3.5" />
                            {{ company.company_code ?? company.company_name }}
                            </div>
                            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                                Registered Vehicles
                            </h1>
                            <p class="text-sm text-slate-500">
                                View and manage your registered vehicles.
                            </p>
                        </div>

                    <div class="flex items-center gap-2">
                        <Badge variant="outline" class="hidden sm:inline-flex rounded-lg border-slate-200 bg-slate-50 text-[11px] font-semibold text-slate-600">
                            {{ resultsLabel }}
                        </Badge>

                        <Button
                            v-if="canCreate"
                            as-child
                            class="shrink-0 self-start gap-2 rounded-lg border-0 bg-blue-700 text-sm font-semibold text-white shadow-sm hover:bg-blue-800"
                        >
                            <Link :href="CompanyVehicleController.create().url">
                                <Plus class="h-4 w-4" />
                                Register Vehicle
                            </Link>
                        </Button>
                    </div>
                </div>

                <!-- -- Stats ----------------------------------- -->
                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-blue-700">
                            <Bus class="h-4 w-4 text-white" />
                        </div>
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">
                            Total Vehicles
                        </p>
                        <p class="mt-0.5 text-3xl font-bold tabular-nums text-slate-900">
                            {{ totalVehicles }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-600">
                            <CheckCircle2 class="h-4 w-4 text-white" />
                        </div>
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">
                            Active
                        </p>
                        <p class="mt-0.5 text-3xl font-bold tabular-nums text-slate-900">
                            {{ activeVehicles }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-sky-600">
                            <RouteIcon class="h-4 w-4 text-white" />
                        </div>
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">
                            With Route
                        </p>
                        <p class="mt-0.5 text-3xl font-bold tabular-nums text-slate-900">
                            {{ vehicles.data.filter((v) => v.route).length }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-slate-600">
                            <Building2 class="h-4 w-4 text-white" />
                        </div>
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">
                            Company
                        </p>
                        <p class="mt-0.5 truncate text-sm font-bold text-slate-900">
                            {{ company.company_code ?? company.company_name }}
                        </p>
                    </div>
                </div>

                <!-- -- Table card ------------------------------- -->
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-base font-semibold text-slate-800">Vehicle List</h2>
                            <p class="mt-0.5 text-xs text-slate-400">
                                Search by plate number, vehicle type, body number, or model.
                            </p>
                        </div>

                        <div class="sm:w-72">
                            <SearchInput
                                :route="CompanyVehicleController.index().url"
                                :initial-value="filters.search"
                                placeholder="Search vehicles…"
                                :only="['vehicles', 'filters', 'flash']"
                            />
                        </div>
                    </div>

                    <!-- Filters & Sort -->
                    <div class="flex flex-wrap items-center gap-2 px-5 pb-4">
                        <div class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                            <Filter class="h-3.5 w-3.5" />
                            Filter
                        </div>

                        <Select :model-value="statusFilter" @update:model-value="onStatusChange">
                            <SelectTrigger class="h-8 w-40 rounded-lg border-slate-200 text-xs">
                                <SelectValue placeholder="All Statuses" />
                            </SelectTrigger>
                            <SelectContent class="rounded-xl">
                                <SelectItem value="all" class="text-xs">All Statuses</SelectItem>
                                <SelectItem value="active" class="text-xs">Active</SelectItem>
                                <SelectItem value="inactive" class="text-xs">Inactive</SelectItem>
                                <SelectItem value="suspended" class="text-xs">Suspended</SelectItem>
                                <SelectItem value="pending" class="text-xs">Pending</SelectItem>
                            </SelectContent>
                        </Select>

                        <Select :model-value="vehicleTypeFilter" @update:model-value="onVehicleTypeChange">
                            <SelectTrigger class="h-8 w-44 rounded-lg border-slate-200 text-xs">
                                <SelectValue placeholder="All Types" />
                            </SelectTrigger>
                            <SelectContent class="rounded-xl">
                                <SelectItem value="all" class="text-xs">All Types</SelectItem>
                                <SelectItem value="Bus" class="text-xs">Bus</SelectItem>
                                <SelectItem value="Modern Jeepney" class="text-xs">Modern Jeepney</SelectItem>
                                <SelectItem value="Jeepney" class="text-xs">Jeepney</SelectItem>
                                <SelectItem value="Mini Bus" class="text-xs">Mini Bus</SelectItem>
                                <SelectItem value="UV Express" class="text-xs">UV Express</SelectItem>
                                <SelectItem value="Van" class="text-xs">Van</SelectItem>
                            </SelectContent>
                        </Select>

                        <Select :model-value="routeFilter" @update:model-value="onRouteChange">
                            <SelectTrigger class="h-8 w-44 rounded-lg border-slate-200 text-xs">
                                <SelectValue placeholder="All Routes" />
                            </SelectTrigger>
                            <SelectContent class="rounded-xl">
                                <SelectItem value="all" class="text-xs">All Routes</SelectItem>
                                <SelectItem
                                    v-for="route in routes"
                                    :key="route.id"
                                    :value="String(route.id)"
                                    class="text-xs"
                                >
                                    {{ route.route_name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <div class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-widest text-muted-foreground ml-2">
                            <ArrowUpDown class="h-3.5 w-3.5" />
                            Sort
                        </div>

                        <Select
                            :model-value="sortBy ?? 'none'"
                            @update:model-value="(val) => { sortBy = val === 'none' ? null : (val as SortField); applyFilters(); }"
                        >
                            <SelectTrigger class="h-8 w-40 rounded-lg border-slate-200 text-xs">
                                <SelectValue placeholder="Sort by…" />
                            </SelectTrigger>
                            <SelectContent class="rounded-xl">
                                <SelectItem value="none" class="text-xs">No Sort</SelectItem>
                                <SelectItem value="status" class="text-xs">Status</SelectItem>
                                <SelectItem value="capacity" class="text-xs">Capacity</SelectItem>
                                <SelectItem value="created_at" class="text-xs">Created Date</SelectItem>
                            </SelectContent>
                        </Select>

                        <Button
                            v-if="sortBy"
                            size="sm"
                            variant="outline"
                            class="h-8 rounded-lg border-slate-200 px-3 text-xs text-slate-600 hover:bg-slate-100"
                            @click="sortDir = sortDir === 'asc' ? 'desc' : 'asc'; applyFilters()"
                        >
                            <ArrowUp v-if="sortDir === 'asc'" class="mr-1.5 h-3.5 w-3.5 text-blue-600" />
                            <ArrowDown v-else class="mr-1.5 h-3.5 w-3.5 text-blue-600" />
                            {{ sortDir === 'asc' ? 'Ascending' : 'Descending' }}
                        </Button>

                        <div v-if="hasActiveFilters" class="ml-auto flex items-center gap-2">
                            <Badge class="gap-1 rounded-lg bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-blue-700 border border-blue-200 hover:bg-blue-50">
                                <Filter class="h-3 w-3" />
                                Filters active
                            </Badge>
                            <Button
                                size="sm"
                                variant="ghost"
                                class="h-7 rounded-lg px-2 text-xs text-muted-foreground hover:text-rose-600"
                                @click="clearFilters"
                            >
                                <X class="mr-1 h-3.5 w-3.5" />
                                Clear
                            </Button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow class="border-slate-100 bg-slate-50/70 hover:bg-slate-50/70">
                                    <TableHead class="pl-5 text-[11px] font-bold uppercase tracking-widest text-slate-400">
                                        Plate Number
                                    </TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-slate-400">
                                        Vehicle Info
                                    </TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-slate-400">
                                        Route
                                    </TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-slate-400">
                                        Documents
                                    </TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-slate-400">
                                        Status
                                    </TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-slate-400">
                                        Remarks
                                    </TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-slate-400">
                                        Created
                                    </TableHead>
                                    <TableHead class="pr-5 text-right text-[11px] font-bold uppercase tracking-widest text-slate-400">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <TableRow v-if="vehicles.data.length === 0" class="hover:bg-transparent">
                                    <TableCell colspan="7" class="py-20 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100">
                                                <Bus class="h-6 w-6 text-slate-400" />
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-slate-600">
                                                    No vehicles found
                                                </p>
                                                <p class="mt-0.5 text-xs text-slate-400">
                                                    Try adjusting your search.
                                                </p>
                                            </div>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <TableRow
                                    v-for="vehicle in vehicles.data"
                                    :key="vehicle.id"
                                    class="border-slate-100 transition-colors hover:bg-slate-50/80"
                                >
                                    <TableCell class="pl-5">
                                        <span class="rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs font-semibold tracking-wide text-slate-700">
                                            {{ vehicle.plate_number }}
                                        </span>
                                    </TableCell>

                                    <TableCell>
                                        <div class="flex items-start gap-2.5">
                                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-100">
                                                <Bus class="h-3.5 w-3.5 text-blue-700" />
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-slate-800">
                                                    {{ vehicle.vehicle_type }}
                                                </p>
                                                <p class="text-xs text-slate-400">
                                                    Body: {{ vehicle.body_number || '—' }}
                                                </p>
                                                <p class="text-xs text-slate-400">
                                                    {{ vehicle.make_model || '—' }}
                                                </p>
                                            </div>
                                        </div>
                                    </TableCell>

                                    <TableCell>
                                        <div v-if="vehicle.route" class="flex items-start gap-1.5">
                                            <RouteIcon class="mt-0.5 h-3.5 w-3.5 shrink-0 text-sky-600" />
                                            <div>
                                                <p class="text-sm font-medium text-slate-700">
                                                    {{ vehicle.route.route_name }}
                                                </p>
                                                <p class="text-xs text-slate-400">
                                                    Cap: {{ vehicle.capacity ?? '—' }}
                                                </p>
                                            </div>
                                        </div>

                                        <div v-else class="flex items-center gap-1.5">
                                            <RouteIcon class="h-3.5 w-3.5 text-slate-300" />
                                            <span class="text-xs text-slate-400">No route assigned</span>
                                        </div>
                                    </TableCell>

                                    <TableCell>
                                        <Popover v-if="vehicle.documents?.length">
                                            <PopoverTrigger as-child>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    class="h-7 gap-1.5 rounded-lg border-slate-200 text-xs text-slate-600 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700"
                                                >
                                                    <FileText class="h-3 w-3" />
                                                    {{ documentsCount(vehicle.documents) }}
                                                    doc{{ documentsCount(vehicle.documents) !== 1 ? 's' : '' }}
                                                </Button>
                                            </PopoverTrigger>

                                            <PopoverContent class="w-80 rounded-xl border-slate-200 p-0 shadow-lg">
                                                <div class="border-b border-slate-100 px-4 py-3">
                                                    <h4 class="text-sm font-semibold text-slate-800">
                                                        Document Statuses
                                                    </h4>
                                                    <p class="mt-0.5 text-xs text-slate-400">
                                                        Documents attached to this vehicle.
                                                    </p>
                                                </div>

                                                <div class="divide-y divide-slate-100 p-2">
                                                    <div
                                                        v-for="doc in vehicle.documents"
                                                        :key="doc.id"
                                                        class="flex items-center justify-between gap-3 rounded-lg px-2 py-2"
                                                    >
                                                        <div class="min-w-0">
                                                            <p class="truncate text-xs font-medium text-slate-700">
                                                                {{ humanize(doc.document_type) }}
                                                            </p>
                                                            <p
                                                                v-if="isDocExpired(doc)"
                                                                class="text-[11px] font-semibold text-rose-600"
                                                            >
                                                                Expired
                                                            </p>
                                                        </div>

                                                        <span
                                                            :class="[
                                                                'inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[11px] font-medium',
                                                                documentStatusClass(doc.status),
                                                            ]"
                                                        >
                                                            <span
                                                                :class="[
                                                                    'h-1.5 w-1.5 rounded-full',
                                                                    documentStatusDot(doc.status),
                                                                ]"
                                                            />
                                                            {{ humanize(doc.status) }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </PopoverContent>
                                        </Popover>

                                        <div v-else class="flex items-center gap-1.5">
                                            <FileText class="h-3.5 w-3.5 text-slate-300" />
                                            <span class="text-xs text-slate-400">No documents</span>
                                        </div>
                                    </TableCell>

                                    <TableCell>
                                        <span
                                            :class="[
                                                'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium',
                                                vehicleStatusClass(vehicle.status),
                                            ]"
                                        >
                                            <span
                                                :class="[
                                                    'h-1.5 w-1.5 rounded-full',
                                                    vehicleStatusDot(vehicle.status),
                                                ]"
                                            />
                                            {{ humanize(vehicle.status) }}
                                        </span>
                                    </TableCell>

                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <Popover v-if="vehicle.remarks">
                                                <PopoverTrigger as-child>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        class="h-7 rounded-lg border-slate-200 text-xs text-slate-600 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700"
                                                    >
                                                        <FileText class="mr-1.5 h-3.5 w-3.5" />
                                                        View
                                                    </Button>
                                                </PopoverTrigger>
                                                <PopoverContent class="w-64 rounded-xl border-slate-200 p-3 text-sm text-slate-700 shadow-lg">
                                                    {{ vehicle.remarks }}
                                                </PopoverContent>
                                            </Popover>
                                            <Badge v-if="vehicle.remarks" variant="secondary" class="rounded-full bg-slate-100 text-[11px] font-semibold text-slate-700">
                                                Has remarks
                                            </Badge>
                                            <span v-else class="text-xs text-muted-foreground">&mdash;</span>
                                        </div>
                                    </TableCell>

                                    <TableCell class="text-sm text-slate-400">
                                        {{ formatDate(vehicle.created_at) }}
                                    </TableCell>

                                    <TableCell class="pr-5 text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    class="h-8 w-8 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                                                >
                                                    <MoreHorizontal class="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>

                                            <DropdownMenuContent
                                                align="end"
                                                class="w-56 rounded-xl border-slate-200 shadow-lg"
                                            >
                                                <DropdownMenuLabel class="text-xs font-semibold uppercase tracking-widest text-slate-400">
                                                    Actions
                                                </DropdownMenuLabel>

                                                <DropdownMenuSeparator class="bg-slate-100" />

                                                <!-- View — always visible -->
                                                <DropdownMenuItem
                                                    as-child
                                                    class="rounded-lg text-slate-700 focus:bg-blue-50 focus:text-blue-700"
                                                >
                                                    <Link :href="CompanyVehicleController.show(vehicle.id).url">
                                                        <Eye class="mr-2 h-4 w-4" />
                                                        View Details
                                                    </Link>
                                                </DropdownMenuItem>

                                                <!-- Edit — permission + business rule -->
                                                <DropdownMenuItem
                                                    v-if="canEditVehicle(vehicle)"
                                                    as-child
                                                    class="rounded-lg text-slate-700 focus:bg-amber-50 focus:text-amber-700"
                                                >
                                                    <Link :href="CompanyVehicleController.edit(vehicle.id).url">
                                                        <Pencil class="mr-2 h-4 w-4" />
                                                        Update Expired Docs
                                                    </Link>
                                                </DropdownMenuItem>

                                                <!-- Edit disabled — has permission but business rule blocks -->
                                                <DropdownMenuItem
                                                    v-else-if="canUpdate"
                                                    disabled
                                                    class="rounded-lg text-slate-300"
                                                >
                                                    <Pencil class="mr-2 h-4 w-4" />
                                                    Update Expired Docs
                                                </DropdownMenuItem>

                                                <DropdownMenuSeparator class="bg-slate-100" />

                                                <!-- Toggle — split flows -->
                                                <DropdownMenuItem
                                                    v-if="vehicle.status === 'active'"
                                                    :disabled="!canToggleVehicle(vehicle)"
                                                    :class="['rounded-lg', canToggleVehicle(vehicle) ? toggleStatusClass(vehicle.status) : 'text-slate-300']"
                                                    @click="openDeactivate(vehicle)"
                                                >
                                                    <Power class="mr-2 h-4 w-4" />
                                                    Set Inactive
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    v-else
                                                    :disabled="!canToggleVehicle(vehicle)"
                                                    :class="['rounded-lg', canToggleVehicle(vehicle) ? toggleStatusClass(vehicle.status) : 'text-slate-300']"
                                                    @click="openActivate(vehicle)"
                                                >
                                                    <Power class="mr-2 h-4 w-4" />
                                                    Set Active
                                                </DropdownMenuItem>

                                                <!-- Contextual note -->
                                                <div
                                                    v-if="(canUpdate && !canEditVehicle(vehicle)) || (canToggle && !canToggleVehicle(vehicle))"
                                                    class="px-2 pb-2 pt-1 text-left text-[11px] text-slate-400"
                                                >
                                                    {{ vehicleActionNote(vehicle) }}
                                                </div>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <div v-if="vehicles.last_page > 1" class="border-t border-slate-100 px-5 py-3">
                        <InertiaPagination
                            :links="vehicles.links"
                            :meta="{
                                from: vehicles.from,
                                to: vehicles.to,
                                total: vehicles.total,
                            }"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Deactivate dialog (requires reason) -->
        <AlertDialog v-model:open="inactiveDialogOpen">
            <AlertDialogContent class="rounded-2xl">
                <AlertDialogHeader>
                    <AlertDialogTitle>Set Vehicle Inactive</AlertDialogTitle>
                    <AlertDialogDescription>
                        Provide a reason to set
                        <span class="font-semibold text-slate-800">
                            {{ inactiveDialog.vehicle?.plate_number || 'this vehicle' }}
                        </span>
                        to inactive.
                    </AlertDialogDescription>
                </AlertDialogHeader>

                <div class="space-y-2">
                    <label class="text-xs font-semibold text-slate-600">Reason</label>
                    <textarea
                        v-model="inactiveDialog.remarks"
                        class="w-full rounded-lg border border-slate-200 bg-white p-2 text-sm focus:border-blue-500 focus:outline-none"
                        rows="3"
                        placeholder="Brief reason (required)"
                    />
                </div>

                <AlertDialogFooter>
                    <AlertDialogCancel class="rounded-lg" @click="inactiveDialog.vehicle = null">
                        Cancel
                    </AlertDialogCancel>
                    <AlertDialogAction
                        class="rounded-lg border-0 bg-rose-600 text-white hover:bg-rose-700"
                        :disabled="!inactiveDialog.remarks.trim()"
                        @click="submitDeactivate"
                    >
                        Set Inactive
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <!-- Activate dialog -->
        <AlertDialog v-model:open="activateDialogOpen">
            <AlertDialogContent class="rounded-2xl">
                <AlertDialogHeader>
                    <AlertDialogTitle>Activate Vehicle</AlertDialogTitle>
                    <AlertDialogDescription>
                        This will activate
                        <span class="font-semibold text-slate-800">
                            {{ activateDialog.vehicle?.plate_number || 'this vehicle' }}
                        </span>.
                    </AlertDialogDescription>
                </AlertDialogHeader>

                <AlertDialogFooter>
                    <AlertDialogCancel class="rounded-lg" @click="activateDialog.vehicle = null">
                        Cancel
                    </AlertDialogCancel>
                    <AlertDialogAction
                        class="rounded-lg border-0 bg-blue-700 text-white hover:bg-blue-800"
                        @click="confirmActivate"
                    >
                        Confirm
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </ExternalLayout>
</template>

