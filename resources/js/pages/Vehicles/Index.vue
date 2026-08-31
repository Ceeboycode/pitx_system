<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import ImportVehicleDialog from '@/components/vehicle/ImportVehicleDialog.vue';
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';

import {
    Table,
    TableColumn,
    // TableSortColumn,
    TableHeader,
    TableContent,
    TableRow,
    TableCard,
    TableData,
    TableMoreButton,
} from '@/components/ui/_table';
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
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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

import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    RiArchive2Line,
    RiArrowDownSLine,
    RiArrowUpDownLine,
    RiArrowUpSLine,
    RiFileAddLine,
    RiFileCheckLine,
    RiFileUploadLine,
    RiFileSearchLine,
    RiFileTextLine,
    RiFilter2Line,
    RiLoaderLine,
    RiMore2Line,
    RiOctagonLine,
    RiCloseLine,
    RiBusLine,
    RiShutDownLine,
    RiSpam2Line,
} from 'vue-remix-icons';
import { computed, ref } from 'vue';

import { destroy, index, show, toggleStatus, trash } from '@/routes/vehicles';
import { type BreadcrumbItem } from '@/types';



type SortField = 'capacity' | 'created_at' | 'status' | null;
type SortDir = 'asc' | 'desc';

type VehicleItem = {
    id: number;
    status?: string | null;
    verification_status?: string | null;
    vehicle_type?: string | null;
    plate_number?: string | null;
    body_number?: string | null;
    capacity?: string | number | null;
    created_at?: string | null;
    operator_remark?: string | null;
    suspension_remark?: string | null;
    company?: { company_name?: string | null } | null;
    route?: { id?: number; route_name?: string | null } | null;
};



const props = defineProps<{
    vehicles: {
        data: VehicleItem[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: {
        search: string | null;
        status: string | null;
        vehicle_type: string | null;
        route_id: string | null;
        sort_by: SortField;
        sort_dir: SortDir;
    };
    routes: { id: number; route_name: string }[];
}>();



const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Vehicles', href: index().url },
];



const importOpen = ref(false);
const exporting = ref(false);
const previewedVehicle = ref<VehicleItem | null>(null);

function openPreview(vehicle: VehicleItem) {
    previewedVehicle.value = vehicle;
}

// const openMenus = ref<Record<number, { open: boolean; x: number; y: number }>>({})

// function handleRowContextMenu(event: MouseEvent, vehicle: VehicleItem) {
//     event.preventDefault()

//     openMenus.value[vehicle.id] = {
//         open: true,
//         x: event.clientX,
//         y: event.clientY,
//     }
// }

const openMenus = ref<Record<number, {
  open: boolean
  x: number
  y: number
  mode: 'trigger' | 'context'
}>>({})

function setMenuState(vehicleId: number, next: { open: boolean; x: number; y: number; mode: 'trigger' | 'context' }) {
    openMenus.value = {
        ...openMenus.value,
        [vehicleId]: next,
    }
}

function openRowMenu(event: MouseEvent, vehicle: VehicleItem) {
    event.preventDefault()

    setMenuState(vehicle.id, {
        open: true,
        x: event.clientX,
        y: event.clientY,
        mode: 'context',
    })
}

function triggerExport() {
    exporting.value = true;
    const a = document.createElement('a');
    a.href = '/vehicles/export';
    a.style.display = 'none';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    setTimeout(() => {
        exporting.value = false;
    }, 2000);
}

function onImportDone() {
    router.reload({ only: ['vehicles'] });
}


const archiveDialogOpen = ref(false);
const selectedVehicle = ref<VehicleItem | null>(null);
const statusDialogOpen = ref(false);
const statusVehicle = ref<VehicleItem | null>(null);
const targetStatus = ref<'active' | 'inactive' | 'suspended'>('suspended');
const suspendRemarks = ref('');
const isSuspending = computed(() => targetStatus.value === 'suspended');



const statusFilter = ref<string>(props.filters.status ?? 'all');
const vehicleTypeFilter = ref<string>(props.filters.vehicle_type ?? 'all');
const routeFilter = ref<string>(props.filters.route_id ?? 'all');
const sortBy = ref<SortField>(props.filters.sort_by ?? null);
const sortDir = ref<SortDir>(props.filters.sort_dir ?? 'asc');
const filterOpen = ref(false);

const hasActiveFilters = computed(
    () =>
        (statusFilter.value && statusFilter.value !== 'all') ||
        (vehicleTypeFilter.value && vehicleTypeFilter.value !== 'all') ||
        (routeFilter.value && routeFilter.value !== 'all') ||
        sortBy.value !== null,
);

const activeFilterCount = computed(() => {
    let count = 0;
    if (statusFilter.value && statusFilter.value !== 'all') count++;
    if (vehicleTypeFilter.value && vehicleTypeFilter.value !== 'all') count++;
    if (routeFilter.value && routeFilter.value !== 'all') count++;
    return count;
});

function applyFilters(
    overrides: Record<string, string | null | undefined> = {},
) {
    router.get(
        index().url,
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

    filterOpen.value = false;
}

function onStatusChange(val: string) {
    statusFilter.value = val;
}

function onVehicleTypeChange(val: string) {
    vehicleTypeFilter.value = val;
}

function onRouteChange(val: string) {
    routeFilter.value = val;
}

function toggleSort(field: SortField) {
    if (sortBy.value === field) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortDir.value = 'asc';
    }
    applyFilters();
}

function clearFilters() {
    statusFilter.value = 'all';
    vehicleTypeFilter.value = 'all';
    routeFilter.value = 'all';
    sortBy.value = null;
    sortDir.value = 'asc';
    applyFilters({
        status: undefined,
        vehicle_type: undefined,
        route_id: undefined,
        sort_by: undefined,
        sort_dir: undefined,
    });
}



function sortIcon(field: SortField) {
    if (sortBy.value !== field) return RiArrowUpDownLine;
    return sortDir.value === 'asc' ? RiArrowUpSLine : RiArrowDownSLine;
}

function sortIconClass(field: SortField) {
    return sortBy.value === field
        ? 'text-custom-primary'
        : 'text-custom-shadow/40';
}



const humanize = (text?: string | null) => {
    if (!text) return '—';
    return text.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
};

function statusClass(status?: string | null): string {
    switch (status) {
        case 'active':
        case 'verified':
            return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'suspended':
            return 'bg-orange-100 text-orange-700 border-orange-200';
        case 'for_verification':
            return 'bg-violet-100 text-violet-700 border-violet-200';
        case 'draft':
        case 'pending':
            return 'bg-amber-100 text-amber-700 border-amber-200';
        case 'invalid':
        case 'inactive':
        case 'needs_revision':
            return 'bg-rose-100 text-rose-600 border-rose-200';
        default:
            return 'bg-slate-100 text-slate-500 border-0';
    }
}

function statusDot(status?: string | null): string {
    switch (status) {
        case 'active':
        case 'verified':
            return 'bg-emerald-500';
        case 'suspended':
            return 'bg-orange-500';
        case 'for_verification':
            return 'bg-violet-500';
        case 'draft':
        case 'pending':
            return 'bg-amber-500';
        case 'invalid':
        case 'inactive':
        case 'needs_revision':
            return 'bg-rose-500';
        default:
            return 'bg-slate-400';
    }
}

const statusActionLabel = (status: string) =>
    status === 'active'
        ? 'Set Active'
        : status === 'inactive'
          ? 'Set Inactive'
          : 'Suspend';

const canToggle = (_vehicle: VehicleItem) => true;



const openArchiveDialog = (vehicle: VehicleItem) => {
    selectedVehicle.value = vehicle;
    archiveDialogOpen.value = true;
};

const openStatusDialog = (
    vehicle: VehicleItem,
    status: 'active' | 'inactive' | 'suspended',
) => {
    statusVehicle.value = vehicle;
    targetStatus.value = status;
    suspendRemarks.value = '';
    statusDialogOpen.value = true;
};

const confirmStatusChange = () => {
    if (!statusVehicle.value) return;
    router.patch(
        toggleStatus(statusVehicle.value.id).url,
        {
            status: targetStatus.value,
            suspension_remark: isSuspending.value
                ? suspendRemarks.value
                : undefined,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                statusDialogOpen.value = false;
                statusVehicle.value = null;
                suspendRemarks.value = '';
            },
        },
    );
};

const archiveVehicle = (vehicle: VehicleItem) => {
    router.delete(destroy({ vehicle: vehicle.id }).url, {
        preserveScroll: true,
        onSuccess: () => {
            archiveDialogOpen.value = false;
            selectedVehicle.value = null;
        },
    });
};
</script>

<template>
    <Head title="Vehicles" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full min-h-0 w-full flex-1 flex-col gap-4 lg:flex-row lg:items-stretch">
            <Card class="min-h-0 min-w-0 flex-1 lg:h-full">
                <CardHeader class="flex flex-row gap-2">
                    <div class="flex flex-col">
                        <CardTitle class="flex items-center gap-2">
                            <span class="font-semibold">Vehicles</span>
                        </CardTitle>
                        <CardDescription>List of all vehicles in the system.</CardDescription>
                    </div>
                    <div class="flex flex-1 justify-end gap-2">
                        <div class="lg:flex items-center gap-2 sm:justify-end">
                            <DropdownMenu class="w-fit">
                                <DropdownMenuTrigger as-child class="m-0">
                                    <div class="inline-flex">
                                        <Button
                                            variant="header-actions"
                                            class="text-custom-shadow"
                                            size="icon"
                                            aria-label="Open vehicle actions"
                                        >
                                            <RiMore2Line class="h-4 w-4 shrink-0" />
                                        </Button>
                                    </div>
                                </DropdownMenuTrigger>

                                <DropdownMenuContent align="end" class="w-fit">
                                    <DropdownMenuItem
                                        class="group cursor-pointer"
                                        @click="importOpen = true"
                                    >
                                        <RiFileAddLine class="h-4 w-4 text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                                        Import
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        class="group cursor-pointer"
                                        :disabled="exporting"
                                        @click="triggerExport"
                                    >
                                        <RiLoaderLine v-if="exporting" class="h-4 w-4 animate-spin text-custom-shadow" />
                                        <RiFileUploadLine v-else class="h-4 w-4 text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                                        {{ exporting ? 'Exporting...' : 'Export' }}
                                    </DropdownMenuItem>
                                    <DropdownMenuItem as-child class="group cursor-pointer">
                                        <Link :href="trash().url" class="flex items-center">
                                            <RiArchive2Line class="h-4 w-4 text-custom-shadow group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow transition-all duration-300" />
                                            Archives
                                        </Link>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>
                </CardHeader>

                <CardContent class="flex min-h-0 flex-1 flex-col space-y-4 py-2">
                    <div class="flex flex-row gap-2 lg:items-center lg:justify-between">
                        <div class="w-full">
                            <SearchInput
                                :route="index().url"
                                :initial-value="filters.search"
                                placeholder="Search vehicles..."
                                :only="['vehicles', 'filters', 'flash']"
                                :debounce="350"
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
                                                ? 'bg-custom-secondary/20 hover:bg-custom-secondary/80 hover:text-custom-bg-light transition-all duration-300 dark:hover:text-custom-shadow'
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

                                <PopoverContent align="start">
                                    <div class="grid gap-y-2">
                                            <div class="flex flex-col gap-y-1">
                                                <p class="text-sm text-custom-shadow/80">
                                                    Status
                                                </p>
                                                <Select
                                                    :model-value="statusFilter"
                                                    @update:model-value="
                                                        onStatusChange
                                                    "
                                                >
                                                    <SelectTrigger
                                                        class="w-full"
                                                    >
                                                        <SelectValue
                                                            placeholder="All Statuses"
                                                            class="flex justify-start"
                                                        />
                                                    </SelectTrigger>
                                                    <SelectContent
                                                    >
                                                        <SelectItem
                                                            value="all"
                                                            class="cursor-pointer text-sm"
                                                        >
                                                            All Statuses
                                                        </SelectItem>
                                                        <SelectItem
                                                            value="active"
                                                            class="cursor-pointer text-sm"
                                                        >
                                                            Active
                                                        </SelectItem>
                                                        <SelectItem
                                                            value="inactive"
                                                            class="cursor-pointer text-sm"
                                                        >
                                                            Inactive
                                                        </SelectItem>
                                                        <SelectItem
                                                            value="suspended"
                                                            class="cursor-pointer text-sm"
                                                        >
                                                            Suspended
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div class="flex flex-col gap-y-1">
                                                <p class="text-sm text-custom-shadow/80">
                                                    Vehicle Type
                                                </p>
                                                <Select
                                                    :model-value="
                                                        vehicleTypeFilter
                                                    "
                                                    @update:model-value="
                                                        onVehicleTypeChange
                                                    "
                                                >
                                                    <SelectTrigger
                                                        class="w-full"
                                                    >
                                                        <SelectValue
                                                            placeholder="All Types"
                                                            class="flex justify-start"
                                                        />
                                                    </SelectTrigger>
                                                    <SelectContent
                                                    >
                                                        <SelectItem
                                                            value="all"
                                                            class="cursor-pointer text-sm"
                                                        >
                                                            All Types
                                                        </SelectItem>
                                                        <SelectItem
                                                            value="bus"
                                                            class="cursor-pointer text-sm"
                                                        >
                                                            Bus
                                                        </SelectItem>
                                                        <SelectItem
                                                            value="minibus"
                                                            class="cursor-pointer text-sm"
                                                        >
                                                            Minibus
                                                        </SelectItem>
                                                        <SelectItem
                                                            value="jeepney"
                                                            class="cursor-pointer text-sm"
                                                        >
                                                            Jeepney
                                                        </SelectItem>
                                                        <SelectItem
                                                            value="van"
                                                            class="cursor-pointer text-sm"
                                                        >
                                                            Van
                                                        </SelectItem>
                                                        <SelectItem
                                                            value="uv_express"
                                                            class="cursor-pointer text-sm"
                                                        >
                                                            UV Express
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div class="flex flex-col gap-y-1">
                                                <p class="text-sm text-custom-shadow/80">
                                                    Route
                                                </p>
                                                <Select
                                                    :model-value="routeFilter"
                                                    @update:model-value="
                                                        onRouteChange
                                                    "
                                                >
                                                    <SelectTrigger
                                                        class="w-full"
                                                    >
                                                        <SelectValue
                                                            placeholder="All Routes"
                                                            class="flex justify-start"
                                                        />
                                                    </SelectTrigger>
                                                    <SelectContent
                                                    >
                                                        <SelectItem
                                                            value="all"
                                                            class="cursor-pointer text-sm"
                                                        >
                                                            All Routes
                                                        </SelectItem>
                                                        <SelectItem
                                                            v-for="route in props.routes"
                                                            :key="route.id"
                                                            :value="
                                                                String(route.id)
                                                            "
                                                            class="cursor-pointer text-sm"
                                                        >
                                                            {{
                                                                route.route_name
                                                            }}
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <hr class="my-1 h-px border-0 bg-custom-bg-dark">

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
                                                        @click="applyFilters"
                                                    >
                                                        Apply
                                                    </Button>
                                                </div>
                                            </div>
                                        </div>
                                    </PopoverContent>
                                </Popover>
                        </div>
                    </div>

                    <TableCard :table-data-length="vehicles.data.length">
                        <!-- my attempt at making this into a custom component -->
                        <Table v-if="vehicles.data.length > 0">
                            <TableHeader class="grid-cols-9">
                                <TableColumn class="pl-3">
                                    Company
                                </TableColumn>
                                <TableColumn>
                                    Route
                                </TableColumn>
                                <TableColumn>
                                    Vehicle
                                </TableColumn>
                                <TableColumn>
                                    Plate
                                </TableColumn>

                                <!-- _TableSortColumn -->
                                <!-- TODO: make this component work -->
                                <button
                                    type="button"
                                    class="col-span-1 flex h-10 cursor-pointer select-none items-center justify-start gap-1.5 px-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
                                    @click="toggleSort('capacity')"
                                >
                                    Cap.
                                    <component
                                        :is="sortIcon('capacity')"
                                        class="h-3.5 w-3.5"
                                        :class="sortIconClass('capacity')"
                                    />
                                </button>

                                <button
                                    type="button"
                                    class="col-span-1 flex h-10 cursor-pointer select-none items-center justify-start gap-1.5 px-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
                                    @click="toggleSort('status')"
                                >
                                    Status
                                    <component
                                        :is="sortIcon('status')"
                                        class="h-3.5 w-3.5"
                                        :class="sortIconClass('status')"
                                    />
                                </button>

                                <TableColumn>
                                    Operator Remark
                                </TableColumn>
                                <TableColumn>
                                    Admin Remark
                                </TableColumn>
                            </TableHeader>

                            <TableContent>
                                <TableRow
                                    v-for="(vehicle, rowIndex) in vehicles.data"
                                    :key="vehicle.id"
                                    :class="[
                                        'grid-cols-9',
                                        rowIndex === vehicles.data.length - 1 ? 'rounded-b-md border-b-0' : '',
                                        previewedVehicle?.id === vehicle.id ? 'bg-custom-secondary/10 text-custom-shadow' : '',
                                    ]"
                                    @click.left="openPreview(vehicle)"
                                    @contextmenu.prevent="openRowMenu($event, vehicle)"
                                >
                                    <TableData class="pl-3 font-semibold">
                                        <span class="truncate">{{ vehicle.company?.company_name || '—' }}</span>
                                    </TableData>
                                    
                                    <TableData>
                                        <span class="truncate">{{ vehicle.route?.route_name || '—' }}</span>
                                    </TableData>

                                    <TableData class="justify-center flex-col">
                                        <p class="truncate text-sm font-medium">{{ humanize(vehicle.vehicle_type) }}</p>
                                        <p class="truncate text-xs text-custom-shadow/70">{{ vehicle.body_number || '—' }}</p>
                                    </TableData>

                                    <TableData>
                                        <span class="rounded bg-custom-bg px-2 py-0.5 font-mono text-xs font-semibold text-custom-shadow dark:bg-custom-bg-light">
                                            {{ vehicle.plate_number || '—' }}
                                        </span>
                                    </TableData>

                                    <TableData>
                                        <span class="tabular-nums">
                                            {{ vehicle.capacity || '—' }}
                                        </span>
                                    </TableData>

                                    <TableData>
                                        <Badge :class="['gap-1.5', statusClass(vehicle.status)]">
                                            <span :class="['h-1.5 w-1.5 rounded-full', statusDot(vehicle.status)]" />
                                            {{ humanize(vehicle.status) }}
                                        </Badge>
                                    </TableData>

                                    <TableData>
                                        <Popover v-if="vehicle.operator_remark">
                                            <PopoverTrigger as-child>
                                                <Button
                                                    variant="ghost-outline"
                                                    size="sm"
                                                    class="h-7 px-2 text-xs"
                                                >
                                                    <RiFileTextLine class="h-3.5 w-3.5" />
                                                    View
                                                </Button>
                                            </PopoverTrigger>
                                            <PopoverContent class="w-64 p-3 text-sm text-custom-shadow">
                                                {{ vehicle.operator_remark }}
                                            </PopoverContent>
                                        </Popover>
                                        <span v-else class="text-xs text-custom-shadow/70">—</span>
                                    </TableData>

                                    <TableData>
                                        <Popover v-if="vehicle.suspension_remark">
                                            <PopoverTrigger as-child>
                                                <Button
                                                    variant="ghost-outline"
                                                    size="sm"
                                                    class="h-7 px-2 text-xs"
                                                >
                                                    <RiFileTextLine class="h-3.5 w-3.5" />
                                                    View
                                                </Button>
                                            </PopoverTrigger>
                                            <PopoverContent class="w-64 p-3 text-sm text-custom-shadow">
                                                {{ vehicle.suspension_remark }}
                                            </PopoverContent>
                                        </Popover>
                                        <span v-else class="text-xs text-custom-shadow/70">—</span>
                                    </TableData>

                                    <TableMoreButton
                                        :open="openMenus[vehicle.id]?.open ?? false"
                                        :x="openMenus[vehicle.id]?.x ?? 0"
                                        :y="openMenus[vehicle.id]?.y ?? 0"
                                        :mode="openMenus[vehicle.id]?.mode ?? 'trigger'"
                                        @update:open="(value) => {
                                            const current = openMenus[vehicle.id] ?? { open: false, x: 0, y: 0, mode: 'trigger' }

                                            setMenuState(vehicle.id, {
                                                ...current,
                                                open: value,
                                                mode: value ? current.mode : 'trigger',
                                            })
                                        }"
                                    >
                                        <DropdownMenuLabel>
                                            <span>{{ vehicle.plate_number }}</span>
                                        </DropdownMenuLabel>
                                        <DropdownMenuItem as-child class="group cursor-pointer rounded-md">
                                            <Link
                                                :href="show({ vehicle: vehicle.id }).url"
                                                class="flex items-center"
                                            >
                                                <RiFileCheckLine class="h-4 w-4 text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg" />
                                                Review
                                            </Link>
                                        </DropdownMenuItem>

                                        <DropdownMenuItem
                                            v-if="vehicle.status !== 'active'"
                                            :disabled="!canToggle(vehicle)"
                                            class="group cursor-pointer rounded-md"
                                            @click="canToggle(vehicle) && openStatusDialog(vehicle, 'active')"
                                        >
                                            <RiOctagonLine class="h-4 w-4 text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg" />
                                            <span class="text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg">Set Active</span>
                                        </DropdownMenuItem>

                                        <DropdownMenuItem
                                            v-if="vehicle.status !== 'inactive'"
                                            :disabled="!canToggle(vehicle)"
                                            class="group cursor-pointer rounded-md"
                                            @click="canToggle(vehicle) && openStatusDialog(vehicle, 'inactive')"
                                        >
                                            <RiShutDownLine class="h-4 w-4 text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg" />
                                            <span class="text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg">Set Inactive</span>
                                        </DropdownMenuItem>

                                        <DropdownMenuItem
                                            v-if="vehicle.status !== 'suspended'"
                                            :disabled="!canToggle(vehicle)"
                                            class="group cursor-pointer rounded-md"
                                            @click="canToggle(vehicle) && openStatusDialog(vehicle, 'suspended')"
                                        >
                                            <RiSpam2Line class="h-4 w-4 text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg" />
                                            <span class="text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg">Suspend</span>
                                        </DropdownMenuItem>
                                    </TableMoreButton>

                                    <!-- <div class="col-span-1 flex justify-end py-1.5 pr-3 text-right" @click.stop>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="table-more"
                                                    size="icon-more"
                                                >
                                                    <RiMore2Line class="h-4 w-4" />

                                                </Button>
                                            </DropdownMenuTrigger>

                                            <DropdownMenuContent align="end" class="">
                                                <DropdownMenuLabel>
                                                    {{ vehicle.plate_number || 'Vehicle' }}
                                                </DropdownMenuLabel>
                                                <DropdownMenuItem as-child class="group cursor-pointer rounded-md">
                                                    <Link
                                                        :href="show({ vehicle: vehicle.id }).url"
                                                        class="flex items-center"
                                                    >
                                                        <RiFileCheckLine class="h-4 w-4 text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg" />
                                                        Review
                                                    </Link>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    v-if="vehicle.status !== 'active'"
                                                    :disabled="!canToggle(vehicle)"
                                                    class="group cursor-pointer rounded-md"
                                                    @click="canToggle(vehicle) && openStatusDialog(vehicle, 'active')"
                                                >
                                                    <RiOctagonLine class="h-4 w-4 text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg" />
                                                    <span class="text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg">Set Active</span>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    v-if="vehicle.status !== 'inactive'"
                                                    :disabled="!canToggle(vehicle)"
                                                    class="group cursor-pointer rounded-md"
                                                    @click="canToggle(vehicle) && openStatusDialog(vehicle, 'inactive')"
                                                >
                                                    <RiShutDownLine class="h-4 w-4 text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg" />
                                                    <span class="text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg">Set Inactive</span>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    v-if="vehicle.status !== 'suspended'"
                                                    :disabled="!canToggle(vehicle)"
                                                    class="group cursor-pointer rounded-md"
                                                    @click="canToggle(vehicle) && openStatusDialog(vehicle, 'suspended')"
                                                >
                                                    <RiSpam2Line class="h-4 w-4 text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg" />
                                                    <span class="text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg">Suspend</span>
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div> -->
                                </TableRow>
                            </TableContent>
                        </Table>

                        <div v-else class="flex min-h-0 flex-1 items-center justify-center p-6 text-center">
                            <div class="flex w-full max-w-md flex-col items-center justify-center gap-2">
                                <img
                                    :src="emptyRafikiUrl"
                                    alt=""
                                    class="w-1/3 object-contain opacity-90"
                                    aria-hidden="true"
                                />
                                <div class="space-y-1">
                                    <p class="text-custom-shadow text-base font-semibold">No vehicles found</p>
                                    <p class="text-custom-shadow/80 text-sm">
                                        {{ hasActiveFilters ? 'Try adjusting or clearing your filters.' : 'Try adjusting your search.' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    <!-- </Card> -->
                    </TableCard>

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
                        <RiBusLine class="h-16 w-16" />
                    </div>
                    <div class="space-y-3 pt-2">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm font-semibold text-custom-shadow">Status</span>
                            <Badge :class="['gap-1.5', statusClass(previewedVehicle.status)]"><span :class="['h-1.5 w-1.5 rounded-full', statusDot(previewedVehicle.status)]" />{{ humanize(previewedVehicle.status) }}</Badge>
                        </div>
                        <div class="flex items-start justify-between gap-3"><span class="text-sm font-semibold text-custom-shadow">Company</span><span class="text-right text-sm">{{ previewedVehicle.company?.company_name || 'Not assigned' }}</span></div>
                        <div class="flex items-start justify-between gap-3"><span class="text-sm font-semibold text-custom-shadow">Route</span><span class="text-right text-sm">{{ previewedVehicle.route?.route_name || 'Not assigned' }}</span></div>
                        <div class="flex items-start justify-between gap-3"><span class="text-sm font-semibold text-custom-shadow">Vehicle Type</span><span class="text-right text-sm">{{ humanize(previewedVehicle.vehicle_type) }}</span></div>
                        <div class="flex items-start justify-between gap-3"><span class="text-sm font-semibold text-custom-shadow">Body Number</span><span class="text-right text-sm">{{ previewedVehicle.body_number || 'Not recorded' }}</span></div>
                        <div class="flex items-start justify-between gap-3"><span class="text-sm font-semibold text-custom-shadow">Capacity</span><span class="text-right text-sm">{{ previewedVehicle.capacity || 'Not recorded' }}</span></div>
                        <div v-if="previewedVehicle.operator_remark" class="space-y-1"><span class="text-sm font-semibold text-custom-shadow">Operator Remark</span><p class="rounded-md bg-custom-bg p-3 text-sm text-custom-shadow/80 dark:bg-custom-bg-dark">{{ previewedVehicle.operator_remark }}</p></div>
                        <div v-if="previewedVehicle.suspension_remark" class="space-y-1"><span class="text-sm font-semibold text-custom-shadow">Admin Remark</span><p class="rounded-md bg-custom-bg p-3 text-sm text-custom-shadow/80 dark:bg-custom-bg-dark">{{ previewedVehicle.suspension_remark }}</p></div>
                    </div>
                    <hr class="my-4 h-px border-0 bg-custom-bg-dark dark:bg-custom-bg-light">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex flex-wrap gap-2">
                            <Button v-if="canToggle(previewedVehicle) && previewedVehicle.status !== 'active'" variant="ghost-outline" size="icon-text" @click="openStatusDialog(previewedVehicle, 'active')">
                                <RiOctagonLine class="h-4 w-4" />Set Active
                            </Button>
                            <Button v-if="canToggle(previewedVehicle) && previewedVehicle.status !== 'inactive'" variant="ghost-outline" size="icon-text" @click="openStatusDialog(previewedVehicle, 'inactive')">
                                <RiShutDownLine class="h-4 w-4" />Set Inactive
                            </Button>
                            <Button v-if="canToggle(previewedVehicle) && previewedVehicle.status !== 'suspended'" variant="ghost-outline" size="icon-text" @click="openStatusDialog(previewedVehicle, 'suspended')">
                                <RiSpam2Line class="h-4 w-4" />Suspend
                            </Button>
                            <Button variant="destructive" size="icon-text" @click="openArchiveDialog(previewedVehicle)">
                                <RiArchive2Line class="h-4 w-4" />Archive
                            </Button>
                        </div>
                        <Button as-child variant="float-primary" size="icon-text"><Link :href="show({ vehicle: previewedVehicle.id }).url"><RiFileSearchLine class="h-4 w-4" />Review</Link></Button>
                    </div>
                </CardContent>
                <CardContent v-else class="flex min-h-0 flex-1 items-center justify-center">
                    <div class="max-w-60 space-y-1 text-center"><p class="text-base font-semibold text-custom-shadow">No vehicle selected</p><p class="text-sm text-custom-shadow/80">Click on a vehicle to preview.</p></div>
                </CardContent>
            </Card>
        </div>

        <!-- TODO: use a dialog component here instead -->
        <AlertDialog v-model:open="archiveDialogOpen">
            <AlertDialogContent class="rounded-2xl">
                <AlertDialogHeader>
                    <AlertDialogTitle>Archive Vehicle</AlertDialogTitle>
                    <AlertDialogDescription>
                        You are about to archive
                        <span class="font-semibold text-foreground">{{
                            selectedVehicle?.plate_number || 'this vehicle'
                        }}</span
                        >. You can restore it later from Archived Vehicles.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel
                        class="rounded-lg"
                        @click="selectedVehicle = null"
                        >Cancel</AlertDialogCancel
                    >
                    <AlertDialogAction
                        class="rounded-lg border-0 bg-rose-600 text-white hover:bg-rose-700"
                        @click="
                            selectedVehicle && archiveVehicle(selectedVehicle)
                        "
                    >
                        Archive
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <Dialog v-model:open="statusDialogOpen">
            <DialogContent class="max-w-md px-6">
                <DialogHeader class="px-0">
                    <DialogTitle>{{ statusActionLabel(targetStatus) }}</DialogTitle>
                    <DialogDescription>
                        <span class="block">
                            {{ isSuspending ? 'Provide a reason to suspend' : 'This will set' }}
                            <span class="font-semibold text-custom-accent-3">{{ statusVehicle?.plate_number || 'this vehicle' }}</span>.
                            {{ isSuspending ? 'The suspension reason is stored separately.' : `New status: ${humanize(targetStatus)}.` }}
                        </span>
                    </DialogDescription>
                </DialogHeader>
                <form class="space-y-3" @submit.prevent="confirmStatusChange">
                    <div v-if="isSuspending" class="flex flex-col gap-y-2">
                        <Textarea
                            v-model="suspendRemarks"
                            class="min-h-24 border-custom-bg-dark bg-custom-bg p-3 text-sm text-custom-shadow placeholder:text-custom-shadow/50 focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:pointer-events-none disabled:bg-white dark:border-none dark:bg-custom-bg-dark dark:shadow-sm dark:shadow-white/5"
                            rows="3"
                            placeholder="Brief reason for suspension..."
                        />
                    </div>

                    <Separator />
                    <DialogFooter class="gap-2 sm:justify-end">
                        <DialogClose as-child>
                            <Button type="button" variant="ghost-outline">Cancel</Button>
                        </DialogClose>
                        <Button
                            type="submit"
                            :variant="isSuspending ? 'destructive' : 'float-primary'"
                            :disabled="isSuspending && !suspendRemarks.trim()"
                        >
                            <RiSpam2Line v-if="isSuspending" class="h-4 w-4" />
                            <RiOctagonLine v-else class="h-4 w-4" />
                            {{ statusActionLabel(targetStatus) }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>


        <ImportVehicleDialog v-model:open="importOpen" @done="onImportDone" />
    </AppLayout>
</template>
