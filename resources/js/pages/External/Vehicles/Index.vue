<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

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
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import {
    Table,
    TableCard,
    TableColumn,
    TableContent,
    TableData,
    TableHeader,
    TableMoreButton,
    TableRow,
} from '@/components/ui/_table';
import { MainPanel, PanelLayout, SidePanel } from '@/components/ui/_panels';
import { ToggleVehicleStatusDialog, VehiclePreviewCard } from '@/components/external/vehicle';
import CompanyVehicleController from '@/actions/App/Http/Controllers/CompanyVehicleController';
import {
    RiArrowDownSLine,
    RiArrowUpDownLine,
    RiArrowUpSLine,
    RiAddLine,
    RiEditLine,
    RiExternalLinkLine,
    RiFileTextLine,
    RiFilter2Line,
    RiMore2Line,
    RiShutDownLine,
} from 'vue-remix-icons';

import { can } from '@/lib/can';
import {
    businessCanEdit,
    businessCanToggle,
    isDocExpired,
    toggleLabel,
    vehicleActionNote,
} from '@/lib/company-vehicle';
import {
    humanize,
    operationalStatusClass,
    operationalStatusDot,
    operationalStatusLabel,
} from '@/lib/vehicle-status';

const canCreate = can('external_vehicles.create');
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
    vehicle_type_id?: number | null;
    vehicle_type?: { id: number; type_name: string } | null;
    plate_number: string;
    body_number?: string | null;
    capacity?: number | null;
    color?: string | null;
    make_model?: string | null;
    status: string;
    verification_status?: string | null;
    verification_remark?: string | null;
    operator_remark?: string | null;
    suspension_remark?: string | null;
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

type SortField = 'capacity' | 'created_at' | null;
type SortDir = 'asc' | 'desc';

const props = defineProps<{
    company: Company;
    user: User;
    vehicles: PaginatedVehicles;
    filters: {
        search?: string | null;
        status?: string | null;
        vehicle_type_id?: string | null;
        route_id?: string | null;
        sort_by?: SortField;
        sort_dir?: SortDir;
    };
    routes: { id: number; route_name: string }[];
    vehicleTypes: { id: number; type_name: string }[];
}>();

function documentStatusClass(status?: string | null) {
    if (status === 'verified' || status === 'approved')
        return 'bg-emerald-100 text-emerald-700 border-emerald-200';
    if (status === 'pending' || status === 'draft' || status === 'for_verification')
        return 'bg-amber-100 text-amber-700 border-amber-200';
    if (status === 'invalid' || status === 'rejected' || status === 'expired' || status === 'needs_revision')
        return 'bg-rose-100 text-rose-600 border-rose-200';
    return 'bg-slate-100 text-slate-500 border-0';
}

function documentStatusDot(status?: string | null) {
    if (status === 'verified' || status === 'approved') return 'bg-emerald-500';
    if (status === 'pending' || status === 'draft' || status === 'for_verification') return 'bg-amber-500';
    if (status === 'invalid' || status === 'rejected' || status === 'expired' || status === 'needs_revision') return 'bg-rose-500';
    return 'bg-slate-400';
}

function documentsCount(documents?: VehicleDocument[]) {
    return documents?.length ?? 0;
}

function canEditVehicle(vehicle: VehicleItem) {
    return canUpdate && businessCanEdit(vehicle);
}

function canToggleVehicle(vehicle: VehicleItem) {
    return canToggle && businessCanToggle(vehicle);
}

const statusFilter = ref<string>(props.filters.status ?? 'all');
const vehicleTypeFilter = ref<string | number>(props.filters.vehicle_type_id ?? 'all');
const routeFilter = ref<string>(props.filters.route_id ?? 'all');
const filterOpen = ref(false);

const sortBy = ref<SortField>(
    props.filters.sort_by === 'capacity' || props.filters.sort_by === 'created_at'
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

function currentFilterParams(): Record<string, string | undefined> {
    return {
        status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
        vehicle_type_id: vehicleTypeFilter.value !== 'all' ? String(vehicleTypeFilter.value) : undefined,
        route_id: routeFilter.value !== 'all' ? routeFilter.value : undefined,
        sort_by: sortBy.value ?? undefined,
        sort_dir: sortBy.value ? sortDir.value : undefined,
    };
}

function applyFilters(overrides: Record<string, string | null | undefined> = {}) {
    router.get(
        CompanyVehicleController.index().url,
        {
            search: props.filters.search ?? undefined,
            ...currentFilterParams(),
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

function onStatusChange(val: any) {
    statusFilter.value = val != null ? String(val) : 'all';
}

function onVehicleTypeChange(val: any) {
    vehicleTypeFilter.value = val != null ? String(val) : 'all';
}

function onRouteChange(val: any) {
    routeFilter.value = val != null ? String(val) : 'all';
}

function toggleSort(field: Exclude<SortField, null>) {
    if (sortBy.value === field) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortDir.value = 'asc';
    }
    applyFilters();
}

function sortIcon(field: Exclude<SortField, null>) {
    if (sortBy.value !== field) return RiArrowUpDownLine;
    return sortDir.value === 'asc' ? RiArrowUpSLine : RiArrowDownSLine;
}

function sortIconClass(field: Exclude<SortField, null>) {
    return sortBy.value === field ? 'text-custom-primary' : 'text-custom-shadow/40';
}

function clearFilters() {
    statusFilter.value = 'all';
    vehicleTypeFilter.value = 'all';
    routeFilter.value = 'all';
    applyFilters({
        status: undefined,
        vehicle_type_id: undefined,
        route_id: undefined,
    });
}

const previewedVehicle = ref<VehicleItem | null>(null);
const openMenus = ref<Record<number, boolean>>({});
const toggleOpen = ref(false);
const togglingVehicle = ref<VehicleItem | null>(null);

watch(() => props.vehicles.data, (rows) => {
    if (previewedVehicle.value && !rows.some((row) => row.id === previewedVehicle.value?.id)) {
        previewedVehicle.value = null;
    }
});

function openPreview(vehicle: VehicleItem) {
    previewedVehicle.value = vehicle;
}

function openToggleDialog(vehicle: VehicleItem) {
    if (!canToggleVehicle(vehicle)) return;
    togglingVehicle.value = vehicle;
    toggleOpen.value = true;
}
</script>

<template>
    <Head title="Registered Vehicles" />

    <ExternalLayout :company="company" :user="user">
        <PanelLayout>
            <MainPanel>
                <Card class="min-h-0 min-w-0 flex-1 lg:h-full">
                    <CardHeader class="flex flex-row gap-2">
                        <div class="flex flex-col">
                            <CardTitle class="flex items-center gap-2">
                                <span class="font-semibold">Vehicles</span>
                            </CardTitle>
                            <CardDescription>Create and manage company vehicle units.</CardDescription>
                        </div>
                        <div v-if="canCreate" class="flex flex-1 items-center justify-end gap-2">
                            <Button as-child variant="float-primary" class="hidden lg:flex">
                                <Link :href="CompanyVehicleController.create().url">
                                    <RiAddLine class="h-4 w-4 shrink-0" />
                                    <span>Register Vehicle</span>
                                </Link>
                            </Button>
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button variant="header-actions" size="icon" class="lg:hidden" aria-label="Vehicle actions">
                                        <RiMore2Line class="h-4 w-4 shrink-0" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" class="w-fit">
                                    <DropdownMenuItem as-child class="group">
                                        <Link :href="CompanyVehicleController.create().url" class="flex items-center gap-2">
                                            <RiAddLine class="h-4 w-4 shrink-0 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                                            Register Vehicle
                                        </Link>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </CardHeader>

                    <CardContent class="flex min-h-0 flex-1 flex-col space-y-4 pt-2">
                        <div class="flex flex-row gap-2 lg:items-center lg:justify-between">
                            <div class="w-full">
                                <SearchInput
                                    :route="CompanyVehicleController.index().url"
                                    :initial-value="filters.search"
                                    placeholder="Search vehicles..."
                                    :only="['vehicles', 'filters', 'flash']"
                                    :debounce="350"
                                    :extra-params="currentFilterParams"
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
                                                    ? 'bg-custom-secondary/20 transition-all duration-200 hover:bg-custom-secondary/80 hover:text-custom-bg-light'
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
                                                <Select :model-value="statusFilter" @update:model-value="onStatusChange">
                                                    <SelectTrigger class="w-full">
                                                        <SelectValue placeholder="All Statuses" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="all" class="cursor-pointer">All Statuses</SelectItem>
                                                        <SelectItem value="active" class="cursor-pointer">Active</SelectItem>
                                                        <SelectItem value="inactive" class="cursor-pointer">Inactive</SelectItem>
                                                        <SelectItem value="suspended" class="cursor-pointer">Suspended</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div class="flex flex-col gap-y-1">
                                                <p class="text-sm text-custom-shadow/80">Vehicle Type</p>
                                                <Select :model-value="vehicleTypeFilter" @update:model-value="onVehicleTypeChange">
                                                    <SelectTrigger class="w-full">
                                                        <SelectValue placeholder="All Types" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="all" class="cursor-pointer">All Types</SelectItem>
                                                        <SelectItem v-for="vehicleType in vehicleTypes" :key="vehicleType.id" :value="String(vehicleType.id)" class="cursor-pointer">
                                                            {{ vehicleType.type_name }}
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div class="flex flex-col gap-y-1">
                                                <p class="text-sm text-custom-shadow/80">Route</p>
                                                <Select :model-value="routeFilter" @update:model-value="onRouteChange">
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
                                                <Button v-if="hasActiveFilters" size="sm" variant="destructive" @click="clearFilters">
                                                    Clear
                                                </Button>

                                                <div class="ml-auto flex items-center gap-2">
                                                    <Button variant="ghost-outline" size="sm" @click="filterOpen = false">Cancel</Button>
                                                    <Button size="sm" variant="float-primary" @click="applyFilters()">Apply</Button>
                                                </div>
                                            </div>
                                        </div>
                                    </PopoverContent>
                                </Popover>
                            </div>
                        </div>

                        <TableCard :table-data-length="vehicles.data.length">
                            <Table v-if="vehicles.data.length > 0">
                                <TableHeader>
                                    <TableColumn>Plate No. & Body</TableColumn>

                                    <TableColumn class="p-0">
                                        <button
                                            type="button"
                                            class="flex h-10 w-full cursor-pointer select-none items-center justify-start gap-1.5 pl-3 pr-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
                                            @click="toggleSort('capacity')"
                                        >
                                            Cap.
                                            <component :is="sortIcon('capacity')" class="h-3.5 w-3.5" :class="sortIconClass('capacity')" />
                                        </button>
                                    </TableColumn>

                                    <TableColumn>Route</TableColumn>
                                    <TableColumn>Documents</TableColumn>
                                    <TableColumn>Status</TableColumn>
                                </TableHeader>

                                <TableContent>
                                    <TableRow
                                        v-for="(vehicle, rowIndex) in vehicles.data"
                                        :key="vehicle.id"
                                        :class="[
                                            rowIndex === vehicles.data.length - 1 ? 'rounded-b-md border-b-0' : '',
                                            previewedVehicle?.id === vehicle.id ? 'bg-custom-secondary/10' : '',
                                        ]"
                                        :status="vehicle.status === 'inactive' ? 'inactive' : 'default'"
                                        @click.left="openPreview(vehicle)"
                                        @dblclick="router.visit(CompanyVehicleController.edit(vehicle.id).url)"
                                    >
                                        <TableData class="pl-3">
                                            <div class="flex min-w-0 flex-col gap-1">
                                                <span class="w-fit rounded-md bg-custom-bg px-2 py-0.5 font-mono text-xs font-semibold dark:bg-custom-bg-light">
                                                    {{ vehicle.plate_number }}
                                                </span>
                                                <span class="truncate text-xs text-custom-shadow/70">{{ vehicle.body_number || '—' }}</span>
                                            </div>
                                        </TableData>

                                        <TableData>
                                            <span class="tabular-nums">{{ vehicle.capacity ?? '—' }}</span>
                                        </TableData>

                                        <TableData>
                                            <span class="truncate">{{ vehicle.route?.route_name || '—' }}</span>
                                        </TableData>

                                        <TableData>
                                            <Popover v-if="vehicle.documents?.length">
                                                <PopoverTrigger as-child>
                                                    <Button variant="ghost-outline" size="sm" class="h-7 px-2 text-xs">
                                                        <RiFileTextLine class="h-3.5 w-3.5" />
                                                        {{ documentsCount(vehicle.documents) }} doc{{ documentsCount(vehicle.documents) !== 1 ? 's' : '' }}
                                                    </Button>
                                                </PopoverTrigger>
                                                <PopoverContent class="w-80 p-0">
                                                    <div class="border-b border-custom-bg-dark px-4 py-3 dark:border-custom-bg-light">
                                                        <h4 class="text-sm font-semibold text-custom-shadow">Document Statuses</h4>
                                                        <p class="mt-0.5 text-xs text-custom-shadow/70">Documents attached to this vehicle.</p>
                                                    </div>
                                                    <div class="divide-y divide-custom-bg-dark p-2 dark:divide-custom-bg-light">
                                                        <div
                                                            v-for="doc in vehicle.documents"
                                                            :key="doc.id"
                                                            class="flex items-center justify-between gap-3 rounded-lg px-2 py-2"
                                                        >
                                                            <div class="min-w-0">
                                                                <p class="truncate text-xs font-semibold text-custom-shadow">{{ humanize(doc.document_type) }}</p>
                                                                <p v-if="isDocExpired(doc)" class="text-[11px] font-semibold text-rose-600">Expired</p>
                                                            </div>
                                                            <span :class="['inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[11px] font-semibold', documentStatusClass(doc.status)]">
                                                                <span :class="['h-1.5 w-1.5 rounded-full', documentStatusDot(doc.status)]" />
                                                                {{ humanize(doc.status) }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </PopoverContent>
                                            </Popover>
                                            <span v-else class="text-xs text-custom-shadow/70">No documents</span>
                                        </TableData>

                                        <TableData>
                                            <Badge :class="['gap-1.5', operationalStatusClass(vehicle.status)]">
                                                <span :class="['h-1.5 w-1.5 rounded-full', operationalStatusDot(vehicle.status)]" />
                                                {{ operationalStatusLabel(vehicle.status) }}
                                            </Badge>
                                        </TableData>

                                        <TableMoreButton
                                            :open="openMenus[vehicle.id] ?? false"
                                            @update:open="(value) => (openMenus[vehicle.id] = value)"
                                        >
                                            <DropdownMenuLabel>{{ vehicle.plate_number }}</DropdownMenuLabel>

                                            <DropdownMenuItem as-child class="group cursor-pointer">
                                                <Link :href="CompanyVehicleController.edit(vehicle.id).url" class="flex items-center">
                                                    <RiExternalLinkLine class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg" />
                                                    View
                                                </Link>
                                            </DropdownMenuItem>

                                            <DropdownMenuItem
                                                v-if="canUpdate"
                                                as-child
                                                :disabled="!canEditVehicle(vehicle)"
                                                :class="[
                                                    'group rounded-md',
                                                    canEditVehicle(vehicle) ? 'cursor-pointer text-custom-shadow' : 'cursor-not-allowed text-custom-shadow/40',
                                                ]"
                                            >
                                                <Link :href="CompanyVehicleController.edit(vehicle.id).url" class="flex items-center">
                                                    <RiEditLine class="h-4 w-4 transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg" />
                                                    Update Documents
                                                </Link>
                                            </DropdownMenuItem>

                                            <DropdownMenuItem
                                                :disabled="!canToggleVehicle(vehicle)"
                                                class="group cursor-pointer rounded-md"
                                                @click="openToggleDialog(vehicle)"
                                            >
                                                <RiShutDownLine class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg" />
                                                <span class="text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg">{{ toggleLabel(vehicle.status) }}</span>
                                            </DropdownMenuItem>

                                            <Separator
                                                class="mt-1"
                                                v-if="(canUpdate && !canEditVehicle(vehicle)) || (canToggle && !canToggleVehicle(vehicle))"
                                            />

                                            <DropdownMenuItem
                                                v-if="(canUpdate && !canEditVehicle(vehicle)) || (canToggle && !canToggleVehicle(vehicle))"
                                                class="pointer-events-none text-xs text-custom-shadow/80"
                                            >
                                                {{ vehicleActionNote(vehicle) }}
                                            </DropdownMenuItem>
                                        </TableMoreButton>
                                    </TableRow>
                                </TableContent>
                            </Table>

                            <div v-else class="flex min-h-0 flex-1 items-center justify-center p-6 text-center">
                                <div class="flex w-full max-w-md flex-col items-center justify-center gap-2">
                                    <img :src="emptyRafikiUrl" alt="" class="w-1/3 object-contain opacity-90" aria-hidden="true" />
                                    <div class="space-y-1">
                                        <p class="text-base font-semibold text-custom-shadow">No vehicles found</p>
                                        <p class="text-sm text-custom-shadow/80">
                                            {{ hasActiveFilters ? 'Try adjusting or clearing your filters.' : 'Try adjusting your search or register a new vehicle.' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </TableCard>

                        <InertiaPagination
                            :links="vehicles.links"
                            :meta="{ from: vehicles.from, to: vehicles.to, total: vehicles.total }"
                        />
                    </CardContent>
                </Card>
            </MainPanel>

            <SidePanel v-if="previewedVehicle" class="hidden lg:flex">
                <VehiclePreviewCard
                    :vehicle="previewedVehicle"
                    @close="previewedVehicle = null"
                />
            </SidePanel>
        </PanelLayout>

        <ToggleVehicleStatusDialog v-model:open="toggleOpen" :vehicle="togglingVehicle" />
    </ExternalLayout>
</template>
