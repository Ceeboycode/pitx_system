<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';
import ExternalLayout from '@/layouts/ExternalLayout.vue';

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
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import CompanyVehicleController from '@/actions/App/Http/Controllers/CompanyVehicleController';
import {
    RiAddLine,
    RiArrowDownLine,
    RiArrowUpLine,
    RiBus2Line,
    RiCloseLine,
    RiExternalLinkLine,
    RiFileCheckLine,
    RiFileTextLine,
    RiFilter2Line,
    RiMore2Line,
    RiEditLine,
    RiShutDownLine,
    RiSortAsc,
} from 'vue-remix-icons';

import { can } from '@/lib/can';

const canCreate = can('external_vehicles.create');
const canUpdate = can('external_vehicles.update');
const canToggle = can('external_vehicles.toggleStatus');

const previewedVehicle = ref<VehicleItem | null>(null);


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


function humanize(value?: string | null) {
    if (!value) return '—';
    return value
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
}

function openPreview(vehicle: VehicleItem) {
    previewedVehicle.value = vehicle;
}


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
    return status === 'active' ? 'Set as Inactive' : 'Set as Active';
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


const totalVehicles = computed(() => props.vehicles.total ?? 0);

const activeVehicles = computed(
    () => props.vehicles.data.filter((v) => v.status === 'active').length,
);

const withRouteCount = computed(
    () => props.vehicles.data.filter((v) => v.route).length,
);


type SortField = 'capacity' | 'created_at' | null;
type SortDir = 'asc' | 'desc';

const statusFilter = ref<string>(props.filters.status ?? 'all');
const vehicleTypeFilter = ref<string>(props.filters.vehicle_type ?? 'all');
const routeFilter = ref<string>(props.filters.route_id ?? 'all');
const filterOpen = ref(false);
const sortOpen = ref(false);


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


function onStatusChange(val: any) {
    statusFilter.value = val != null ? String(val) : 'all';
}


function onVehicleTypeChange(val: any) {
    vehicleTypeFilter.value = val != null ? String(val) : 'all';
}


function onRouteChange(val: any) {
    routeFilter.value = val != null ? String(val) : 'all';
}


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


const statusDialog = reactive({
    open: false,
    vehicle: null as VehicleItem | null,
    remarks: '',
});

const statusDialogOpen = computed({
    get: () => statusDialog.open,
    set: (val) => {
        statusDialog.open = val;
        if (!val) {
            statusDialog.vehicle = null;
            statusDialog.remarks = '';
        }
    },
});

const isInactivating = computed(() => statusDialog.vehicle?.status === 'active');

function openDeactivate(vehicle: VehicleItem) {
    if (!canToggleVehicle(vehicle) || vehicle.status !== 'active') return;
    statusDialog.vehicle = vehicle;
    statusDialog.remarks = '';
    statusDialog.open = true;
}

function submitStatusChange() {
    if (!statusDialog.vehicle) return;
    const isInactivating = statusDialog.vehicle.status === 'active';
    if (isInactivating && !statusDialog.remarks.trim()) return;
    router.patch(
        CompanyVehicleController.toggleStatus(statusDialog.vehicle.id).url,
        isInactivating ? { remarks: statusDialog.remarks } : {},
        {
            preserveScroll: true,
            onSuccess: () => {
                statusDialogOpen.value = false;
            },
        },
    );
}

function openActivate(vehicle: VehicleItem) {
    if (!canToggleVehicle(vehicle) || vehicle.status !== 'inactive') return;
    statusDialog.vehicle = vehicle;
    statusDialog.remarks = '';
    statusDialog.open = true;
}
</script>

<template>
    <Head title="Registered Vehicles" />

    <ExternalLayout :company="company" :user="user">
        <div class="flex min-h-0 min-w-0 flex-1 flex-col gap-4 lg:flex-row lg:items-stretch">
            <Card class="flex min-h-0 min-w-0 flex-1 flex-col lg:h-full">
                <CardHeader class="flex flex-row gap-2">
                    <div class="flex flex-col">
                        <CardTitle class="flex items-center gap-2">
                            <span class="font-semibold">Vehicles</span>
                        </CardTitle>
                        <CardDescription>
                            Create and manage company vehicle units.
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
                                <RiAddLine class="h-4 w-4 shrink-0" />
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
                                placeholder="Search vehicles..."
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
                                        <div class="flex flex-col gap-y-1">
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

                                        <div class="flex flex-col gap-y-1">
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

                                        <div class="flex flex-col gap-y-1">
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

                            <!-- CODE: <Popover v-model:open="sortOpen">
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
                                                <RiArrowUpLine
                                                    v-if="sortDir === 'asc'"
                                                    class="h-3.5 w-3.5"
                                                />
                                                <RiArrowDownLine v-else class="h-3.5 w-3.5" />
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

                
                <Card
                    :class="[
                        'flex min-h-0 flex-1 max-h-fit flex-col overflow-hidden border border-custom-bg-dark dark:border-custom-bg-light py-0 shadow-none dark:inset-shadow-none',
                        vehicles.data.length === 0 ? 'border-dashed' : 'border-solid',
                    ]"
                >
                <div class="no-scrollbar min-h-0 flex-1 overflow-auto">
                    <div class="w-full min-w-210 lg:min-w-0">
                        <div v-if="vehicles.data.length > 0" class="border-b border-custom-bg-dark bg-custom-bg dark:border-custom-bg-light dark:bg-custom-bg-light">
                            <div class="grid grid-cols-[1fr_0.25fr_1fr_0.5fr_0.25fr_0.5fr] items-center">
                                <div
                                    class="col-span-1 flex h-10 items-center justify-start px-0 pl-3 text-left text-xs font-semibold tracking-widest text-custom-shadow/80 uppercase"
                                    >Plate No. & Body</div
                                >
                                <div
                                    class="col-span-1 flex h-10 items-center justify-start px-0 text-left text-xs font-semibold tracking-widest text-custom-shadow/80 uppercase"
                                    >Cap.</div
                                >
                                <div
                                    class="col-span-1 flex h-10 items-center justify-start px-0 text-left text-xs font-semibold tracking-widest text-custom-shadow/80 uppercase"
                                    >Route</div
                                >
                                <div
                                    class="col-span-1 flex h-10 items-center justify-start px-0 text-left text-xs font-semibold tracking-widest text-custom-shadow/80 uppercase"
                                    >Documents</div
                                >
                                <div
                                    class="col-span-1 flex h-10 items-center justify-start px-0 text-left text-xs font-semibold tracking-widest text-custom-shadow/80 uppercase"
                                    >Status</div
                                >
                                <div
                                    class="col-span-1 flex h-10 items-center justify-end px-0 pr-3 text-right text-xs font-semibold tracking-widest text-custom-shadow/80 uppercase"
                                    >Actions</div
                                >
                            </div>
                        </div>

                        <div>
                            
                            <div
                                v-if="vehicles.data.length === 0"
                                class="flex min-h-72 items-center justify-center border-0 hover:bg-transparent"
                            >
                                <div class="py-20 text-center">
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
                                </div>
                            </div>

                            <div
                                v-for="(vehicle, rowIndex) in vehicles.data"
                                :key="vehicle.id"
                                :class="[
                                    'group cursor-pointer grid grid-cols-[1fr_0.25fr_1fr_0.5fr_0.25fr_0.5fr] items-center border-b border-custom-bg-dark text-custom-shadow/80 transition-colors hover:bg-custom-secondary/10 hover:text-custom-shadow dark:border-custom-bg-light',
                                    rowIndex === vehicles.data.length - 1 ? 'rounded-b-md border-b-0' : '',
                                    previewedVehicle?.id === vehicle.id ? 'bg-custom-secondary/10 text-custom-shadow' : '',
                                ]"
                                @click="openPreview(vehicle)"
                            >
                                
                                <div class="col-span-1 min-w-0 py-1.5 pl-3">
                                    <div class="flex min-w-0 flex-col gap-1">
                                        <span class="w-fit rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs font-semibold tracking-wide text-slate-700">
                                            {{ vehicle.plate_number }}
                                        </span>
                                        <span class="truncate text-xs text-custom-shadow/70">
                                            {{ vehicle.body_number || '—' }}
                                        </span>
                                    </div>
                                </div>

                                <div class="col-span-1 min-w-0 py-1.5 text-sm text-custom-shadow/80">
                                    {{ vehicle.capacity ?? '—' }}
                                </div>

                                
                                <div class="col-span-1 min-w-0 py-1.5">
                                    <div
                                        v-if="vehicle.route"
                                        class="min-w-0"
                                    >
                                        <p class="truncate text-sm font-medium text-slate-700">
                                            {{ vehicle.route.route_name }}
                                        </p>
                                    </div>
                                    <div
                                        v-else
                                        class="flex items-center"
                                    >
                                        <span class="text-xs text-slate-400"
                                            >No route</span
                                        >
                                    </div>
                                </div>

                                
                                <div class="col-span-1 min-w-0 py-1.5">
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
                                </div>

                                
                                <div class="col-span-1 min-w-0 py-1.5">
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
                                </div>

                                
                                <div class="col-span-1 min-w-0 py-1.5 pr-3 text-right" @click.stop>
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button
                                                variant="table-more"
                                                size="icon-more"
                                            >
                                                <RiMore2Line
                                                    class="h-4 w-4"
                                                />
                                                
                                            </Button>
                                        </DropdownMenuTrigger>

                                        <DropdownMenuContent align="end" class="max-w-fit">
                                            <DropdownMenuLabel>
                                                {{ vehicle.plate_number || 'Vehicle' }}
                                            </DropdownMenuLabel>

                                            <DropdownMenuItem
                                                as-child
                                                class="group cursor-pointer rounded-md"
                                            >
                                                <Link
                                                    :href="
                                                        CompanyVehicleController.show(
                                                            vehicle.id,
                                                        ).url
                                                    "
                                                    class="flex items-center"
                                                >
                                                    <RiExternalLinkLine class="h-4 w-4 text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg" />
                                                    View
                                                </Link>
                                            </DropdownMenuItem>

                                            <DropdownMenuItem
                                                v-if="canUpdate"
                                                as-child
                                                :disabled="
                                                    !canEditVehicle(vehicle)
                                                "
                                                :class="[
                                                    'group rounded-md',
                                                    canEditVehicle(vehicle)
                                                        ? 'cursor-pointer text-custom-shadow'
                                                        : 'cursor-not-allowed text-slate-300',
                                                ]"
                                            >
                                                <Link
                                                    :href="
                                                        CompanyVehicleController.edit(
                                                            vehicle.id,
                                                        ).url
                                                    "
                                                    class="flex items-center"
                                                >
                                                    <RiEditLine class="h-4 w-4 transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg" />
                                                    Update Documents
                                                </Link>
                                            </DropdownMenuItem>

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
                                                class="group cursor-pointer rounded-md"
                                                @click="
                                                    openDeactivate(vehicle)
                                                "
                                            >
                                                <RiShutDownLine class="h-4 w-4 text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg" />
                                                <span class="text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg">Set as Inactive</span>
                                            </DropdownMenuItem>

                                            <DropdownMenuItem
                                                v-else
                                                :disabled="
                                                    !canToggleVehicle(
                                                        vehicle,
                                                    )
                                                "
                                                class="group cursor-pointer rounded-md"
                                                @click="
                                                    openActivate(vehicle)
                                                "
                                            >
                                                <RiShutDownLine class="h-4 w-4 text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg" />
                                                <span class="text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg">{{ toggleLabel(vehicle.status) }}</span>
                                            </DropdownMenuItem>

                                            <Separator
                                                class="mt-4"
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
                                            />

                                            <DropdownMenuItem
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
                                                class="pointer-events-none text-custom-shadow/80 text-xs"
                                            >
                                                {{
                                                    vehicleActionNote(
                                                        vehicle,
                                                    )
                                                }}
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                </Card>

                <InertiaPagination
                    :links="vehicles.links"
                    :meta="{
                        from: vehicles.from,
                        to: vehicles.to,
                        total: vehicles.total,
                    }"
                />
                </CardContent>
            </Card>

            <Card class="hidden min-h-0 lg:flex lg:h-full lg:w-100">
                <CardHeader v-if="previewedVehicle" class="flex flex-row items-start justify-between gap-3">
                    <div class="min-w-0">
                        <CardTitle class="truncate uppercase">{{ previewedVehicle.plate_number || 'Vehicle' }}</CardTitle>
                        <CardDescription>Preview</CardDescription>
                    </div>
                    <Button variant="header-actions" size="icon" class="h-8 w-8 shrink-0 rounded-full" @click="previewedVehicle = null">
                        <RiCloseLine class="h-4 w-4" />
                    </Button>
                </CardHeader>

                <CardContent v-if="previewedVehicle" class="no-scrollbar min-h-0 flex-1 space-y-2 overflow-y-auto py-2">
                    <div class="flex aspect-4/3 items-center justify-center rounded-md border border-dashed border-custom-bg-dark bg-custom-bg text-custom-shadow/70 dark:border-none dark:bg-custom-bg-dark">
                        <RiBus2Line class="h-16 w-16" />
                    </div>
                    <div class="space-y-3 pt-2">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm font-semibold text-custom-shadow">Status</span>
                            <span :class="['inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium', vehicleStatusClass(previewedVehicle.status)]">
                                <span :class="['h-1.5 w-1.5 rounded-full', vehicleStatusDot(previewedVehicle.status)]" />
                                {{ humanize(previewedVehicle.status) }}
                            </span>
                        </div>
                        <div class="flex items-start justify-between gap-3"><span class="text-sm font-semibold text-custom-shadow">Route</span><span class="text-right text-sm">{{ previewedVehicle.route?.route_name || 'Not assigned' }}</span></div>
                        <div class="flex items-start justify-between gap-3"><span class="text-sm font-semibold text-custom-shadow">Vehicle Type</span><span class="text-right text-sm">{{ humanize(previewedVehicle.vehicle_type) }}</span></div>
                        <div class="flex items-start justify-between gap-3"><span class="text-sm font-semibold text-custom-shadow">Make / Model</span><span class="text-right text-sm">{{ previewedVehicle.make_model || 'Not recorded' }}</span></div>
                        <div class="flex items-start justify-between gap-3"><span class="text-sm font-semibold text-custom-shadow">Body Number</span><span class="text-right text-sm">{{ previewedVehicle.body_number || 'Not recorded' }}</span></div>
                        <div class="flex items-start justify-between gap-3"><span class="text-sm font-semibold text-custom-shadow">Capacity</span><span class="text-right text-sm">{{ previewedVehicle.capacity ?? 'Not recorded' }}</span></div>
                        <div class="flex items-start justify-between gap-3"><span class="text-sm font-semibold text-custom-shadow">Documents</span><span class="text-right text-sm">{{ documentsCount(previewedVehicle.documents || []) }}</span></div>
                        <div v-if="previewedVehicle.remarks" class="space-y-1"><span class="text-sm font-semibold text-custom-shadow">Remarks</span><p class="rounded-md bg-custom-bg p-3 text-sm text-custom-shadow/80 dark:bg-custom-bg-dark">{{ previewedVehicle.remarks }}</p></div>
                    </div>
                    <hr class="my-4 h-px border-0 bg-custom-bg-dark dark:bg-custom-bg-light">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <Button v-if="canToggleVehicle(previewedVehicle)" variant="ghost-outline" size="icon-text" @click="previewedVehicle.status === 'active' ? openDeactivate(previewedVehicle) : openActivate(previewedVehicle)">
                            <RiShutDownLine class="h-4 w-4" />{{ previewedVehicle.status === 'active' ? 'Set as Inactive' : toggleLabel(previewedVehicle.status) }}
                        </Button>
                        <Button as-child variant="float-primary" size="icon-text"><Link :href="CompanyVehicleController.show(previewedVehicle.id).url"><RiFileCheckLine class="h-4 w-4" />Review</Link></Button>
                    </div>
                </CardContent>
                <CardContent v-else class="flex min-h-0 flex-1 items-center justify-center">
                    <div class="max-w-60 space-y-1 text-center"><p class="text-base font-semibold text-custom-shadow">No vehicle selected</p><p class="text-sm text-custom-shadow/80">Click on a vehicle to preview.</p></div>
                </CardContent>
            </Card>
        </div>

        <Dialog v-model:open="statusDialogOpen">
            <DialogContent class="max-w-md px-6">
                <DialogHeader class="px-0">
                    <DialogTitle>{{ isInactivating ? 'Inactivate vehicle' : 'Activate vehicle' }}</DialogTitle>
                    <DialogDescription>
                        <span class="block">
                            {{ isInactivating ? 'Provide a reason to inactivate' : 'This will activate' }}
                            <span class="font-semibold text-custom-accent-3">{{ statusDialog.vehicle?.plate_number || 'this vehicle' }}</span>.
                            {{ isInactivating ? 'You can activate it again later.' : 'All documents must be approved before activation.' }}
                        </span>
                    </DialogDescription>
                </DialogHeader>
                <form class="space-y-3" @submit.prevent="submitStatusChange">
                    <div v-if="isInactivating" class="flex flex-col gap-y-2">
                        <Textarea
                            v-model="statusDialog.remarks"
                            class="min-h-24 border-custom-bg-dark bg-custom-bg p-3 text-sm text-custom-shadow placeholder:text-custom-shadow/50 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:pointer-events-none disabled:bg-white dark:border-none dark:bg-custom-bg-dark dark:shadow-sm dark:shadow-white/5"
                            rows="3"
                            placeholder="Brief reason for inactivation..."
                        />
                    </div>

                    <Separator />
                    <DialogFooter class="gap-2 sm:justify-end">
                        <DialogClose as-child>
                            <Button type="button" variant="ghost-outline">Cancel</Button>
                        </DialogClose>
                        <Button
                            type="submit"
                            :variant="isInactivating ? 'destructive' : 'float-primary'"
                            :disabled="isInactivating && !statusDialog.remarks.trim()"
                        >
                            <RiShutDownLine class="h-4 w-4" />
                            {{ isInactivating ? 'Inactivate' : 'Activate' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </ExternalLayout>
</template>
