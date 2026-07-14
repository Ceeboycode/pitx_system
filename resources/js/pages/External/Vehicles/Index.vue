<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';
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
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
    RiAddLine as Plus,
    RiArrowDownLine as ArrowDown,
    RiArrowUpLine as ArrowUp,
    RiBuildingLine as Building2,
    RiBus2Line as Bus,
    RiCheckboxCircleLine as CheckCircle2,
    RiCloseLine as X,
    RiEyeLine as Eye,
    RiFileTextLine as FileText,
    RiFilter2Line,
    RiMore2Line as MoreHorizontal,
    RiPencilLine as Pencil,
    RiRouteLine as RouteIcon,
    RiShutDownLine as Power,
    RiSortAsc,
} from 'vue-remix-icons';

import { can } from '@/lib/can';

const canCreate = can('external_vehicles.create');
const canUpdate = can('external_vehicles.update');
const canToggle = can('external_vehicles.toggleStatus');

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
   Note: sort_by type no longer includes 'status'
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
        sort_by?: 'capacity' | 'created_at' | null;
        sort_dir?: 'asc' | 'desc' | null;
    };
    routes: { id: number; route_name: string }[];
}>();

/* ======================================================
   Helpers
====================================================== */
function humanize(value?: string | null) {
    if (!value) return '—';
    return value
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
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
    if (status === 'active')
        return 'bg-emerald-100 text-emerald-700 border-emerald-200';
    if (status === 'inactive')
        return 'bg-rose-100 text-rose-600 border-rose-200';
    if (status === 'suspended')
        return 'bg-orange-100 text-orange-700 border-orange-200';
    if (status === 'pending')
        return 'bg-amber-100 text-amber-700 border-amber-200';
    return 'bg-slate-100 text-slate-500 border-0';
}

function vehicleStatusDot(status?: string | null) {
    if (status === 'active') return 'bg-emerald-500';
    if (status === 'inactive') return 'bg-rose-500';
    if (status === 'suspended') return 'bg-orange-500';
    if (status === 'pending') return 'bg-amber-500';
    return 'bg-slate-400';
}

function documentStatusClass(status?: string | null) {
    if (status === 'approved')
        return 'bg-emerald-100 text-emerald-700 border-emerald-200';
    if (status === 'pending')
        return 'bg-amber-100 text-amber-700 border-amber-200';
    if (status === 'rejected')
        return 'bg-rose-100 text-rose-600 border-rose-200';
    if (status === 'expired')
        return 'bg-rose-100 text-rose-600 border-rose-200';
    return 'bg-slate-100 text-slate-500 border-0';
}

function documentStatusDot(status?: string | null) {
    if (status === 'approved') return 'bg-emerald-500';
    if (status === 'pending') return 'bg-amber-500';
    if (status === 'rejected') return 'bg-rose-500';
    if (status === 'expired') return 'bg-rose-500';
    return 'bg-slate-400';
}

function documentsCount(documents?: VehicleDocument[]) {
    return documents?.length ?? 0;
}

function isDocExpired(doc?: VehicleDocument) {
    if (!doc) return false;
    if (doc.status === 'expired') return true;
    if (!doc.expires_at) return false;
    return new Date(doc.expires_at) < new Date();
}

function needsResubmission(doc?: VehicleDocument) {
    if (!doc) return false;
    return doc.status === 'invalid' || isDocExpired(doc);
}

/* ======================================================
   Business logic guards
====================================================== */
function isSuspended(status?: string | null) {
    return status === 'suspended';
}

const hasDocuments = (vehicle: VehicleItem) => !!vehicle.documents?.length;
const hasPendingOrRejected = (vehicle: VehicleItem) =>
    vehicle.documents?.some((doc) =>
        ['pending', 'rejected'].includes(doc.status),
    ) ?? false;
const hasDocsNeedingResubmission = (vehicle: VehicleItem) =>
    vehicle.documents?.some((doc) => needsResubmission(doc)) ?? false;

function businessCanEdit(vehicle: VehicleItem) {
    return !isSuspended(vehicle.status) && hasDocsNeedingResubmission(vehicle);
}

function businessCanActivate(vehicle: VehicleItem) {
    if (isSuspended(vehicle.status)) return false;
    if (!hasDocuments(vehicle)) return false;
    if (hasPendingOrRejected(vehicle)) return false;
    if (hasDocsNeedingResubmission(vehicle)) return false;
    return true;
}

function businessCanToggle(vehicle: VehicleItem) {
    if (vehicle.status === 'active') return !isSuspended(vehicle.status);
    if (vehicle.status === 'inactive') return businessCanActivate(vehicle);
    return false;
}

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
    if (isSuspended(vehicle.status))
        return 'Suspended vehicles cannot change status.';
    if (!hasDocuments(vehicle)) return 'Upload required documents first.';
    if (hasPendingOrRejected(vehicle))
        return 'Documents must be approved before activation.';
    if (hasDocsNeedingResubmission(vehicle))
        return 'Resubmit invalid or expired documents before activation.';
    return '';
}

function vehicleActionNote(vehicle: VehicleItem) {
    if (
        !businessCanEdit(vehicle) &&
        !isSuspended(vehicle.status) &&
        !hasDocsNeedingResubmission(vehicle)
    ) {
        return 'No invalid or expired documents available for resubmission.';
    }
    return firstBlockingReason(vehicle);
}

/* ======================================================
   Computed stats
====================================================== */
const totalVehicles = computed(() => props.vehicles.total ?? 0);

const activeVehicles = computed(
    () => props.vehicles.data.filter((v) => v.status === 'active').length,
);

const withRouteCount = computed(
    () => props.vehicles.data.filter((v) => v.route).length,
);

/* ======================================================
   Filters & sorting
   SortField: 'status' intentionally removed — only
   'capacity' and 'created_at' are valid sort fields.
====================================================== */
type SortField = 'capacity' | 'created_at' | null;
type SortDir = 'asc' | 'desc';

const statusFilter = ref<string>(props.filters.status ?? 'all');
const vehicleTypeFilter = ref<string>(props.filters.vehicle_type ?? 'all');
const routeFilter = ref<string>(props.filters.route_id ?? 'all');
const filterOpen = ref(false);
const sortOpen = ref(false);

// Guard against a stale 'status' sort_by coming back from the server
const sortBy = ref<SortField>(
    props.filters.sort_by === 'capacity' ||
        props.filters.sort_by === 'created_at'
        ? props.filters.sort_by
        : null,
);
const sortDir = ref<SortDir>(props.filters.sort_dir ?? 'asc');

const activeFilterCount = computed(
    () =>
        Number(statusFilter.value !== 'all') +
        Number(vehicleTypeFilter.value !== 'all') +
        Number(routeFilter.value !== 'all'),
);

const hasActiveFilters = computed(() => activeFilterCount.value > 0);

const hasActiveSort = computed(() => sortBy.value !== null);

const activeSortLabel = computed(
    () =>
        sortBy.value === 'capacity'
            ? 'Capacity'
            : sortBy.value === 'created_at'
              ? 'Created Date'
              : 'Sort',
);

function applyFilters(
    overrides: Record<string, string | null | undefined> = {},
) {
    router.get(
        CompanyVehicleController.index().url,
        {
            search: props.filters.search ?? undefined,
            status:
                statusFilter.value !== 'all' ? statusFilter.value : undefined,
            vehicle_type:
                vehicleTypeFilter.value !== 'all'
                    ? vehicleTypeFilter.value
                    : undefined,
            route_id:
                routeFilter.value !== 'all' ? routeFilter.value : undefined,
            sort_by: sortBy.value ?? undefined,
            sort_dir: sortBy.value ? sortDir.value : undefined,
            ...overrides,
        },
        {
            preserveState: true,
            replace: true,
            only: ['vehicles', 'filters', 'flash'],
        },
    );
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function onStatusChange(val: any) {
    statusFilter.value = val != null ? String(val) : 'all';
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function onVehicleTypeChange(val: any) {
    vehicleTypeFilter.value = val != null ? String(val) : 'all';
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function onRouteChange(val: any) {
    routeFilter.value = val != null ? String(val) : 'all';
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function onSortChange(val: any) {
    const str = val != null ? String(val) : 'none';
    sortBy.value = str === 'none' ? null : (str as SortField);
    if (sortBy.value === null) {
        sortDir.value = 'asc';
    }
}

function toggleSortDir() {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
}

function clearFilters() {
    statusFilter.value = 'all';
    vehicleTypeFilter.value = 'all';
    routeFilter.value = 'all';
    applyFilters({
        status: undefined,
        vehicle_type: undefined,
        route_id: undefined,
    });
}

function applyFilterSelections() {
    filterOpen.value = false;
    applyFilters();
}

function applySortSelections() {
    sortOpen.value = false;
    applyFilters();
}

function clearSort() {
    sortBy.value = null;
    sortDir.value = 'asc';
    applyFilters({
        sort_by: undefined,
        sort_dir: undefined,
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
    set: (val) => {
        inactiveDialog.open = val;
    },
});

const activateDialogOpen = computed({
    get: () => activateDialog.open,
    set: (val) => {
        activateDialog.open = val;
    },
});

function openDeactivate(vehicle: VehicleItem) {
    if (!canToggleVehicle(vehicle) || vehicle.status !== 'active') return;
    inactiveDialog.vehicle = vehicle;
    inactiveDialog.remarks = '';
    inactiveDialog.open = true;
}

function submitDeactivate() {
    if (!inactiveDialog.vehicle) return;
    router.patch(
        CompanyVehicleController.toggleStatus(inactiveDialog.vehicle.id).url,
        { remarks: inactiveDialog.remarks },
        {
            preserveScroll: true,
            onSuccess: () => {
                inactiveDialog.open = false;
                inactiveDialog.vehicle = null;
                inactiveDialog.remarks = '';
            },
        },
    );
}

function openActivate(vehicle: VehicleItem) {
    if (!canToggleVehicle(vehicle) || vehicle.status !== 'inactive') return;
    activateDialog.vehicle = vehicle;
    activateDialog.open = true;
}

function confirmActivate() {
    if (!activateDialog.vehicle) return;
    router.patch(
        CompanyVehicleController.toggleStatus(activateDialog.vehicle.id).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                activateDialog.open = false;
                activateDialog.vehicle = null;
            },
        },
    );
}
</script>

<template>
    <Head title="Registered Vehicles" />

    <ExternalLayout :company="company" :user="user">

        <Card class="min-h-0 min-w-0 flex-1 lg:h-full">
            <CardHeader class="flex flex-row gap-2">
                <div class="flex flex-col">
                    <CardTitle class="flex items-center gap-2">
                        <span class="font-semibold">Vehicle List</span>
                    </CardTitle>
                    <CardDescription>
                        Search by plate number, vehicle type, body number,
                        or model.
                    </CardDescription>
                </div>
                <div class="flex flex-1 justify-end">
                    <Button
                        v-if="canCreate"
                        as-child
                        variant="float-primary"
                        class="shrink-0"
                    >
                        <Link :href="CompanyVehicleController.create().url">
                            <Plus class="h-4 w-4 shrink-0" />
                            <span>Register Vehicle</span>
                        </Link>
                    </Button>
                </div>
            </CardHeader>

            <CardContent class="flex min-h-0 flex-1 flex-col space-y-4 py-2">
                <div class="flex flex-row gap-2 lg:items-center lg:justify-between">
                    <div class="w-full">
                        <SearchInput
                            :route="
                                CompanyVehicleController.index().url
                            "
                            :initial-value="filters.search"
                            placeholder="Search vehicles…"
                            :only="['vehicles', 'filters', 'flash']"
                        />
                    </div>

                    <div class="flex w-fit flex-row gap-2 lg:items-center lg:justify-between">
                        <Popover v-model:open="filterOpen">
                            <PopoverTrigger as-child>
                                <Button
                                    variant="header-actions"
                                    size="icon-text"
                                    class="rounded-full"
                                    :class="
                                        activeFilterCount > 0
                                            ? 'bg-custom-secondary/20 transition-all duration-300 hover:bg-custom-secondary/80 hover:text-custom-bg-light'
                                            : ''
                                    "
                                >
                                    <RiFilter2Line class="h-3.5 w-3.5" />
                                    <span class="hidden lg:flex">
                                        {{
                                            activeFilterCount > 0
                                                ? (activeFilterCount === 1 ? '1 filter active' : `${activeFilterCount} filters active`)
                                                : 'Filter'
                                        }}
                                    </span>
                                </Button>
                            </PopoverTrigger>

                            <PopoverContent align="end">
                                <div class="grid gap-y-2">
                                    <div class="space-y-2">
                                        <p class="text-sm text-custom-shadow/80">Status</p>
                                        <Select
                                            :model-value="statusFilter"
                                            @update:model-value="onStatusChange"
                                        >
                                            <SelectTrigger class="w-full">
                                                <SelectValue placeholder="All Statuses" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all" class="cursor-pointer">All Statuses</SelectItem>
                                                <SelectItem value="active" class="cursor-pointer">Active</SelectItem>
                                                <SelectItem value="inactive" class="cursor-pointer">Inactive</SelectItem>
                                                <SelectItem value="suspended" class="cursor-pointer">Suspended</SelectItem>
                                                <SelectItem value="pending" class="cursor-pointer">Pending</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div class="space-y-2">
                                        <p class="text-sm text-custom-shadow/80">Vehicle Type</p>
                                        <Select
                                            :model-value="vehicleTypeFilter"
                                            @update:model-value="onVehicleTypeChange"
                                        >
                                            <SelectTrigger class="w-full">
                                                <SelectValue placeholder="All Types" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all" class="cursor-pointer">All Types</SelectItem>
                                                <SelectItem value="Bus" class="cursor-pointer">Bus</SelectItem>
                                                <SelectItem value="Modern Jeepney" class="cursor-pointer">Modern Jeepney</SelectItem>
                                                <SelectItem value="Jeepney" class="cursor-pointer">Jeepney</SelectItem>
                                                <SelectItem value="Mini Bus" class="cursor-pointer">Mini Bus</SelectItem>
                                                <SelectItem value="UV Express" class="cursor-pointer">UV Express</SelectItem>
                                                <SelectItem value="Van" class="cursor-pointer">Van</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div class="space-y-2">
                                        <p class="text-sm text-custom-shadow/80">Route</p>
                                        <Select
                                            :model-value="routeFilter"
                                            @update:model-value="onRouteChange"
                                        >
                                            <SelectTrigger class="w-full">
                                                <SelectValue placeholder="All Routes" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all" class="cursor-pointer">All Routes</SelectItem>
                                                <SelectItem
                                                    v-for="route in routes"
                                                    :key="route.id"
                                                    :value="String(route.id)"
                                                    class="cursor-pointer"
                                                >
                                                    {{ route.route_name }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <hr class="my-1 h-px border-0 bg-custom-bg-dark dark:bg-custom-bg-light">

                                    <div class="flex w-full flex-row items-center justify-between">
                                        <Button
                                            v-if="hasActiveFilters"
                                            size="sm"
                                            variant="destructive"
                                            @click="clearFilters"
                                        >
                                            Clear
                                        </Button>

                                        <div class="ml-auto flex items-center gap-2">
                                            <Button
                                                variant="ghost-outline"
                                                size="sm"
                                                @click="filterOpen = false"
                                            >
                                                Cancel
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="float-primary"
                                                @click="applyFilterSelections"
                                            >
                                                Apply
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </PopoverContent>
                        </Popover>

                        <!-- <Popover v-model:open="sortOpen">
                            <PopoverTrigger as-child>
                                <Button
                                    variant="header-actions"
                                    size="icon-text"
                                    class="rounded-full"
                                    :class="
                                        hasActiveSort
                                            ? 'bg-custom-secondary/20 transition-all duration-300 hover:bg-custom-secondary/80 hover:text-custom-bg-light'
                                            : ''
                                    "
                                >
                                    <RiSortAsc class="h-3.5 w-3.5" />
                                    <span class="hidden lg:flex">
                                        {{ hasActiveSort ? `${activeSortLabel} ${sortDir}` : 'Sort' }}
                                    </span>
                                </Button>
                            </PopoverTrigger>

                            <PopoverContent align="end">
                                <div class="grid gap-y-2">
                                    <div class="space-y-2">
                                        <p class="text-sm text-custom-shadow/80">Sort By</p>
                                        <Select
                                            :model-value="sortBy ?? 'none'"
                                            @update:model-value="onSortChange"
                                        >
                                            <SelectTrigger class="w-full">
                                                <SelectValue placeholder="Sort by..." />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="none" class="cursor-pointer">No Sort</SelectItem>
                                                <SelectItem value="capacity" class="cursor-pointer">Capacity</SelectItem>
                                                <SelectItem value="created_at" class="cursor-pointer">Created Date</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div class="space-y-2">
                                        <p class="text-sm text-custom-shadow/80">Direction</p>
                                        <Button
                                            size="sm"
                                            variant="header-actions"
                                            class="w-full justify-start rounded-full"
                                            :disabled="!sortBy"
                                            @click="toggleSortDir"
                                        >
                                            <ArrowUp
                                                v-if="sortDir === 'asc'"
                                                class="h-3.5 w-3.5"
                                            />
                                            <ArrowDown v-else class="h-3.5 w-3.5" />
                                            {{ sortDir === 'asc' ? 'Ascending' : 'Descending' }}
                                        </Button>
                                    </div>

                                    <hr class="my-1 h-px border-0 bg-custom-bg-dark dark:bg-custom-bg-light">

                                    <div class="flex w-full flex-row items-center justify-between">
                                        <Button
                                            v-if="hasActiveSort"
                                            size="sm"
                                            variant="destructive"
                                            @click="clearSort"
                                        >
                                            Clear
                                        </Button>

                                        <div class="ml-auto flex items-center gap-2">
                                            <Button
                                                variant="ghost-outline"
                                                size="sm"
                                                @click="sortOpen = false"
                                            >
                                                Cancel
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="float-primary"
                                                @click="applySortSelections"
                                            >
                                                Apply
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </PopoverContent>
                        </Popover> -->
                    </div>
                </div>

            <!-- Table -->
            <Card
                :class="[
                    'flex min-h-0 flex-1 max-h-fit flex-col overflow-hidden border border-custom-bg-dark dark:border-custom-bg-light py-0 shadow-none dark:inset-shadow-none',
                    vehicles.data.length === 0 ? 'border-dashed' : 'border-solid',
                ]"
            >
            <div class="no-scrollbar min-h-0 flex-1 overflow-auto">
                <Table>
                    <TableHeader v-if="vehicles.data.length > 0" class="border-b border-custom-bg-dark dark:border-custom-bg-light">
                        <TableRow>
                            <TableHead
                                class="pl-5 text-[11px] font-semibold tracking-widest text-custom-shadow/80 uppercase"
                                >Vehicle</TableHead
                            >
                            <TableHead
                                class="text-[11px] font-semibold tracking-widest text-custom-shadow/80 uppercase"
                                >Plate Number</TableHead
                            >
                            <TableHead
                                class="text-[11px] font-semibold tracking-widest text-custom-shadow/80 uppercase"
                                >Route</TableHead
                            >
                            <TableHead
                                class="text-[11px] font-semibold tracking-widest text-custom-shadow/80 uppercase"
                                >Documents</TableHead
                            >
                            <TableHead
                                class="text-[11px] font-semibold tracking-widest text-custom-shadow/80 uppercase"
                                >Status</TableHead
                            >
                            <TableHead
                                class="text-[11px] font-semibold tracking-widest text-custom-shadow/80 uppercase"
                                >Remarks</TableHead
                            >
                            <TableHead
                                class="text-[11px] font-semibold tracking-widest text-custom-shadow/80 uppercase"
                                >Created</TableHead
                            >
                            <TableHead
                                class="pr-5 text-right text-[11px] font-semibold tracking-widest text-custom-shadow/80 uppercase"
                                >Actions</TableHead
                            >
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        <!-- Empty state -->
                        <TableRow
                            v-if="vehicles.data.length === 0"
                            class="hover:bg-transparent"
                        >
                            <TableCell
                                colspan="8"
                                class="py-20 text-center"
                            >
                                <div
                                    class="flex flex-col items-center gap-3"
                                >
                                    <img
                                        :src="emptyRafikiUrl"
                                        alt=""
                                        class="w-32 object-contain opacity-90"
                                        aria-hidden="true"
                                    />
                                    <div>
                                        <p
                                            class="text-sm font-semibold text-slate-600"
                                        >
                                            No vehicles found
                                        </p>
                                        <p
                                            class="mt-0.5 text-xs text-slate-400"
                                        >
                                            Try adjusting your search or
                                            filters.
                                        </p>
                                    </div>
                                </div>
                            </TableCell>
                        </TableRow>

                        <TableRow
                            v-for="vehicle in vehicles.data"
                            :key="vehicle.id"
                            class="group border-b border-custom-bg-dark text-custom-shadow/80 transition-colors hover:bg-custom-secondary/10 hover:text-custom-shadow dark:border-custom-bg-light"
                        >
                            <!-- Vehicle -->
                            <TableCell class="pl-5">
                                <div class="flex items-center gap-2.5">
                                    <div
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100"
                                    >
                                        <Bus
                                            class="h-3.5 w-3.5 text-slate-600"
                                        />
                                    </div>
                                    <div>
                                        <p
                                            class="text-sm font-semibold text-slate-800"
                                        >
                                            {{ vehicle.vehicle_type }}
                                        </p>
                                        <p
                                            class="text-xs text-slate-400"
                                        >
                                            {{
                                                vehicle.make_model ||
                                                '—'
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </TableCell>

                            <!-- Plate number -->
                            <TableCell>
                                <span
                                    class="rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs font-semibold tracking-wide text-slate-700"
                                >
                                    {{ vehicle.plate_number }}
                                </span>
                                <p class="mt-1 text-xs text-slate-400">
                                    Body:
                                    {{ vehicle.body_number || '—' }}
                                </p>
                            </TableCell>

                            <!-- Route -->
                            <TableCell>
                                <div
                                    v-if="vehicle.route"
                                    class="flex items-start gap-1.5"
                                >
                                    <RouteIcon
                                        class="mt-0.5 h-3.5 w-3.5 shrink-0 text-sky-600"
                                    />
                                    <div>
                                        <p
                                            class="text-sm font-medium text-slate-700"
                                        >
                                            {{
                                                vehicle.route.route_name
                                            }}
                                        </p>
                                        <p
                                            class="text-xs text-slate-400"
                                        >
                                            Cap:
                                            {{
                                                vehicle.capacity ?? '—'
                                            }}
                                        </p>
                                    </div>
                                </div>
                                <div
                                    v-else
                                    class="flex items-center gap-1.5"
                                >
                                    <RouteIcon
                                        class="h-3.5 w-3.5 text-slate-300"
                                    />
                                    <span class="text-xs text-slate-400"
                                        >No route</span
                                    >
                                </div>
                            </TableCell>

                            <!-- Documents -->
                            <TableCell>
                                <Popover
                                    v-if="vehicle.documents?.length"
                                >
                                    <PopoverTrigger as-child>
                                        <Button
                                            variant="header-actions"
                                            size="sm"
                                            class="h-7 gap-1.5 rounded-full text-xs"
                                        >
                                            <FileText class="h-3 w-3" />
                                            {{
                                                documentsCount(
                                                    vehicle.documents,
                                                )
                                            }}
                                            doc{{
                                                documentsCount(
                                                    vehicle.documents,
                                                ) !== 1
                                                    ? 's'
                                                    : ''
                                            }}
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent
                                        class="w-80 rounded-xl border-slate-200 p-0 shadow-lg"
                                    >
                                        <div
                                            class="border-b border-slate-100 px-4 py-3"
                                        >
                                            <h4
                                                class="text-sm font-semibold text-slate-800"
                                            >
                                                Document Statuses
                                            </h4>
                                            <p
                                                class="mt-0.5 text-xs text-slate-400"
                                            >
                                                Documents attached to
                                                this vehicle.
                                            </p>
                                        </div>
                                        <div
                                            class="divide-y divide-slate-100 p-2"
                                        >
                                            <div
                                                v-for="doc in vehicle.documents"
                                                :key="doc.id"
                                                class="flex items-center justify-between gap-3 rounded-lg px-2 py-2"
                                            >
                                                <div class="min-w-0">
                                                    <p
                                                        class="truncate text-xs font-medium text-slate-700"
                                                    >
                                                        {{
                                                            humanize(
                                                                doc.document_type,
                                                            )
                                                        }}
                                                    </p>
                                                    <p
                                                        v-if="
                                                            isDocExpired(
                                                                doc,
                                                            )
                                                        "
                                                        class="text-[11px] font-semibold text-rose-600"
                                                    >
                                                        Expired
                                                    </p>
                                                </div>
                                                <span
                                                    :class="[
                                                        'inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[11px] font-medium',
                                                        documentStatusClass(
                                                            doc.status,
                                                        ),
                                                    ]"
                                                >
                                                    <span
                                                        :class="[
                                                            'h-1.5 w-1.5 rounded-full',
                                                            documentStatusDot(
                                                                doc.status,
                                                            ),
                                                        ]"
                                                    />
                                                    {{
                                                        humanize(
                                                            doc.status,
                                                        )
                                                    }}
                                                </span>
                                            </div>
                                        </div>
                                    </PopoverContent>
                                </Popover>
                                <div
                                    v-else
                                    class="flex items-center gap-1.5"
                                >
                                    <FileText
                                        class="h-3.5 w-3.5 text-slate-300"
                                    />
                                    <span class="text-xs text-slate-400"
                                        >No documents</span
                                    >
                                </div>
                            </TableCell>

                            <!-- Status -->
                            <TableCell>
                                <span
                                    :class="[
                                        'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium',
                                        vehicleStatusClass(
                                            vehicle.status,
                                        ),
                                    ]"
                                >
                                    <span
                                        :class="[
                                            'h-1.5 w-1.5 rounded-full',
                                            vehicleStatusDot(
                                                vehicle.status,
                                            ),
                                        ]"
                                    />
                                    {{ humanize(vehicle.status) }}
                                </span>
                            </TableCell>

                            <!-- Remarks -->
                            <TableCell>
                                <Popover v-if="vehicle.remarks">
                                    <PopoverTrigger as-child>
                                        <Button
                                            variant="header-actions"
                                            size="sm"
                                            class="h-7 rounded-full text-xs"
                                        >
                                            <FileText
                                                class="mr-1.5 h-3.5 w-3.5"
                                            />
                                            View
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent
                                        class="w-64 rounded-xl border-slate-200 p-3 text-sm text-slate-700 shadow-lg"
                                    >
                                        {{ vehicle.remarks }}
                                    </PopoverContent>
                                </Popover>
                                <span
                                    v-else
                                    class="text-xs text-slate-400"
                                    >—</span
                                >
                            </TableCell>

                            <!-- Created -->
                            <TableCell class="text-sm text-slate-400">
                                {{ formatDate(vehicle.created_at) }}
                            </TableCell>

                            <!-- Actions -->
                            <TableCell class="pr-5 text-right">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button
                                            variant="table-more"
                                            size="icon-more"
                                        >
                                            <MoreHorizontal
                                                class="h-4 w-4"
                                            />
                                        </Button>
                                    </DropdownMenuTrigger>

                                    <DropdownMenuContent
                                        align="end"
                                        class="w-fit rounded-lg shadow-lg"
                                    >
                                        <DropdownMenuLabel
                                            class="text-xs font-semibold tracking-widest text-custom-shadow/80 uppercase"
                                        >
                                            Actions
                                        </DropdownMenuLabel>

                                        <DropdownMenuSeparator
                                            class="bg-slate-100"
                                        />

                                        <DropdownMenuItem
                                            as-child
                                            class="rounded-lg text-slate-700 focus:bg-slate-50 focus:text-slate-900"
                                        >
                                            <Link
                                                :href="
                                                    CompanyVehicleController.show(
                                                        vehicle.id,
                                                    ).url
                                                "
                                                class="flex items-center"
                                            >
                                                <Eye
                                                    class="mr-2 h-4 w-4"
                                                />
                                                View Details
                                            </Link>
                                        </DropdownMenuItem>

                                        <DropdownMenuItem
                                            v-if="
                                                canEditVehicle(vehicle)
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
                                                class="flex items-center"
                                            >
                                                <Pencil
                                                    class="mr-2 h-4 w-4"
                                                />
                                                Update Invalid/Expired
                                                Docs
                                            </Link>
                                        </DropdownMenuItem>

                                        <DropdownMenuItem
                                            v-else-if="canUpdate"
                                            disabled
                                            class="rounded-lg text-slate-300"
                                        >
                                            <Pencil
                                                class="mr-2 h-4 w-4"
                                            />
                                            Update Invalid/Expired Docs
                                        </DropdownMenuItem>

                                        <DropdownMenuSeparator
                                            class="bg-slate-100"
                                        />

                                        <DropdownMenuItem
                                            v-if="
                                                vehicle.status ===
                                                'active'
                                            "
                                            :disabled="
                                                !canToggleVehicle(
                                                    vehicle,
                                                )
                                            "
                                            :class="[
                                                'rounded-lg',
                                                canToggleVehicle(
                                                    vehicle,
                                                )
                                                    ? toggleStatusClass(
                                                            vehicle.status,
                                                        )
                                                    : 'text-slate-300',
                                            ]"
                                            @click="
                                                openDeactivate(vehicle)
                                            "
                                        >
                                            <Power
                                                class="mr-2 h-4 w-4"
                                            />
                                            Set Inactive
                                        </DropdownMenuItem>

                                        <DropdownMenuItem
                                            v-else
                                            :disabled="
                                                !canToggleVehicle(
                                                    vehicle,
                                                )
                                            "
                                            :class="[
                                                'rounded-lg',
                                                canToggleVehicle(
                                                    vehicle,
                                                )
                                                    ? toggleStatusClass(
                                                            vehicle.status,
                                                        )
                                                    : 'text-slate-300',
                                            ]"
                                            @click="
                                                openActivate(vehicle)
                                            "
                                        >
                                            <Power
                                                class="mr-2 h-4 w-4"
                                            />
                                            {{
                                                toggleLabel(
                                                    vehicle.status,
                                                )
                                            }}
                                        </DropdownMenuItem>

                                        <div
                                            v-if="
                                                (canUpdate &&
                                                    !canEditVehicle(
                                                        vehicle,
                                                    )) ||
                                                (canToggle &&
                                                    !canToggleVehicle(
                                                        vehicle,
                                                    ))
                                            "
                                            class="px-2 pt-1 pb-2 text-left text-[11px] text-slate-400"
                                        >
                                            {{
                                                vehicleActionNote(
                                                    vehicle,
                                                )
                                            }}
                                        </div>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
            </Card>

            <!-- Pagination -->
            <div
                v-if="vehicles.last_page > 1 || vehicles.total > 0"
                class="border-t border-slate-100 px-5 py-3"
            >
                <InertiaPagination
                    :links="vehicles.links"
                    :meta="{
                        from: vehicles.from,
                        to: vehicles.to,
                        total: vehicles.total,
                    }"
                />
            </div>
            </CardContent>
        </Card>

        <AlertDialog v-model:open="inactiveDialogOpen">
            <AlertDialogContent>
                <form class="space-y-4" @submit.prevent="submitDeactivate">
                    <AlertDialogHeader>
                        <AlertDialogTitle
                            >Set Vehicle Inactive</AlertDialogTitle
                        >
                        <AlertDialogDescription>
                            <span class="block">
                                Provide a reason to set
                                <strong class="text-slate-800">{{
                                    inactiveDialog.vehicle?.plate_number ||
                                    'this vehicle'
                                }}</strong>
                                to inactive.
                            </span>
                            <span class="mt-1 block"
                                >This action can be reversed later.</span
                            >
                        </AlertDialogDescription>
                    </AlertDialogHeader>

                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-slate-600">
                            Reason <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            v-model="inactiveDialog.remarks"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            rows="3"
                            placeholder="Brief reason for deactivation…"
                        />
                    </div>

                    <AlertDialogFooter>
                        <AlertDialogCancel
                            type="button"
                            @click="inactiveDialog.vehicle = null"
                        >
                            Cancel
                        </AlertDialogCancel>
                        <AlertDialogAction
                            type="submit"
                            class="border-0 bg-rose-600 text-white hover:bg-rose-700"
                            :disabled="!inactiveDialog.remarks.trim()"
                            @click.prevent="submitDeactivate"
                        >
                            Set Inactive
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </form>
            </AlertDialogContent>
        </AlertDialog>

        <!-- ── Activate dialog ───────────────────────── -->
        <AlertDialog v-model:open="activateDialogOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Activate Vehicle</AlertDialogTitle>
                    <AlertDialogDescription>
                        <span class="block">
                            This will activate
                            <strong class="text-slate-800">{{
                                activateDialog.vehicle?.plate_number ||
                                'this vehicle'
                            }}</strong
                            >.
                        </span>
                        <span class="mt-1 block">
                            All documents must be approved before activation.
                        </span>
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="activateDialog.vehicle = null">
                        Cancel
                    </AlertDialogCancel>
                    <AlertDialogAction
                        class="border-0 bg-slate-800 text-white hover:bg-slate-900"
                        @click="confirmActivate"
                    >
                        Confirm Activation
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </ExternalLayout>
</template>
