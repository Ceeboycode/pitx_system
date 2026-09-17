<script setup lang="ts">

import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';


import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';


import {
    Table,
    TableColumn,
    TableHeader,
    TableContent,
    TableRow,
    TableCard,
    TableData,
} from '@/components/ui/_table';
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
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { PanelLayout } from '@/components/ui/_panels';


import {
    RiArrowDownSLine,
    RiArrowUpDownLine,
    RiArrowUpSLine,
    RiClipboardLine,
    RiCloseLine,
    RiFileInfoLine,
    RiFilter2Line,
    RiMore2Line,
} from 'vue-remix-icons';


import InternalDispatchController from '@/actions/App/Http/Controllers/InternalDispatchController';
import { index as changeRequestsIndex } from '@/actions/App/Http/Controllers/DispatchChangeRequestController';


type DispatchStatus = 'pending' | 'arrived' | 'departed';

type DispatchRoute = {
    route_name: string | null;
    origin_name: string | null;
    destination_name: string | null;
};

type DispatchVehicle = {
    plate_number: string | null;
    vehicle_type: string | null;
    make_model: string | null;
    route: DispatchRoute | null;
};

type DispatchItem = {
    id: number;
    status: DispatchStatus;
    bay_number: string | null;
    pax_count: number | null;
    dispatched_at: string | null;
    arrived_at: string | null;
    departed_at: string | null;
    company: { id: number; company_name: string } | null;
    vehicle: DispatchVehicle | null;
    gate: { gate_name: string } | null;
    dispatcher: { name: string } | null;
    driver: { name: string } | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type SortField = 'company_name' | 'plate_number' | 'status' | 'dispatched_at' | null;

type PaginatedDispatches = {
    data: DispatchItem[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
};


const props = defineProps<{
    filters: {
        search: string;
        status?: string | null;
        sort_by?: SortField;
        sort_dir?: 'asc' | 'desc';
    };
    dispatches: PaginatedDispatches;
}>();

const sortBy  = ref<SortField>(props.filters.sort_by ?? null);
const sortDir = ref<'asc' | 'desc'>(props.filters.sort_dir ?? 'asc');
const filterOpen = ref(false);
const filterStatus = ref(props.filters.status || 'all');
const previewedDispatch = ref<DispatchItem | null>(null);
const activeFilterCount = computed(() => Number(filterStatus.value !== 'all'));

function openPreview(dispatch: DispatchItem) {
    previewedDispatch.value = dispatch;
}

function selectAdjacentDispatch(direction: 1 | -1) {
    if (!previewedDispatch.value) return;

    const list = props.dispatches.data;
    const currentIndex = list.findIndex((d) => d.id === previewedDispatch.value?.id);
    const nextIndex = currentIndex + direction;
    if (currentIndex === -1 || nextIndex < 0 || nextIndex >= list.length) return;

    openPreview(list[nextIndex]);
}

function handleRowNavigationKeydown(event: KeyboardEvent) {
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        selectAdjacentDispatch(1);
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        selectAdjacentDispatch(-1);
    }
}

onMounted(() => window.addEventListener('keydown', handleRowNavigationKeydown));
onUnmounted(() => window.removeEventListener('keydown', handleRowNavigationKeydown));

function currentFilterParams(): Record<string, string | undefined> {
    return {
        status: filterStatus.value === 'all' ? undefined : filterStatus.value,
        sort_by: sortBy.value ?? undefined,
        sort_dir: sortBy.value ? sortDir.value : undefined,
    };
}

function applyFilters() {
    router.get(InternalDispatchController.index().url, {
        search: props.filters.search || undefined,
        ...currentFilterParams(),
    }, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
        only: ['dispatches', 'filters'],
    });
    filterOpen.value = false;
}

function clearFilters() {
    filterStatus.value = 'all';
    sortBy.value = null;
    sortDir.value = 'asc';
    applyFilters();
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

function sortIcon(field: SortField) {
    if (sortBy.value !== field) return RiArrowUpDownLine;
    return sortDir.value === 'asc' ? RiArrowUpSLine : RiArrowDownSLine;
}

function sortIconClass(field: SortField) {
    return sortBy.value === field ? 'text-custom-primary' : 'text-custom-shadow/40';
}


const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dispatches', href: InternalDispatchController.index().url },
];


function prettyStatus(value: string | null | undefined) {
    return String(value ?? 'unknown')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
}

function statusClass(status: string | null | undefined): string {
    switch (status) {
        case 'arrived':
            return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'pending':
            return 'bg-amber-100 text-amber-700 border-amber-200';
        case 'departed':
            return 'bg-slate-100 text-slate-500 border-0';
        default:
            return 'bg-slate-100 text-slate-500 border-0';
    }
}

function statusDot(status: string | null | undefined): string {
    switch (status) {
        case 'arrived':
            return 'bg-emerald-500';
        case 'pending':
            return 'bg-amber-400';
        case 'departed':
            return 'bg-slate-400';
        default:
            return 'bg-slate-400';
    }
}

function routeLabel(vehicle: DispatchVehicle | null): string {
    const route = vehicle?.route;
    if (!route) return '—';
    if (route.route_name) return route.route_name;
    if (route.origin_name || route.destination_name) {
        return `${route.origin_name ?? '—'} to ${route.destination_name ?? '—'}`;
    }
    return '—';
}
</script>

<template>
    <Head title="Dispatches" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <PanelLayout>
            <Card class="min-h-0 min-w-0 flex-1 lg:h-full">
                <CardHeader class="flex flex-row gap-2 items-center">
                    <div class="flex flex-col">
                        <CardTitle class="flex items-center gap-2">
                            <span class="font-semibold">Dispatches</span>
                        </CardTitle>
                        <CardDescription>View individual dispatch records across all companies.</CardDescription>
                    </div>
                    <div class="flex flex-1 justify-end gap-2 items-center">
                        <DropdownMenu class="w-fit">
                            <DropdownMenuTrigger as-child class="m-0">
                                <div class="inline-flex">
                                    <Button variant="header-actions" class="text-custom-shadow" size="icon" aria-label="Open dispatch actions">
                                        <RiMore2Line class="h-4 w-4 shrink-0" />
                                    </Button>
                                </div>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-fit">
                                <DropdownMenuItem as-child class="group cursor-pointer">
                                    <Link :href="changeRequestsIndex().url" class="flex items-center">
                                        <RiFileInfoLine class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                                        Change Requests
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
                                :route="InternalDispatchController.index().url"
                                placeholder="Search by plate, route, gate, dispatcher, driver..."
                                :initial-value="props.filters.search"
                                :only="['dispatches', 'filters']"
                                :debounce="350"
                                :extra-params="currentFilterParams"
                            />
                        </div>
                        <Popover v-model:open="filterOpen">
                            <PopoverTrigger as-child>
                                <Button
                                    variant="header-actions"
                                    size="icon-text"
                                    class="rounded-full"
                                    :class="activeFilterCount ? 'bg-custom-secondary/20 transition-all duration-200 hover:bg-custom-secondary/80 hover:text-custom-bg-light' : ''"
                                >
                                    <RiFilter2Line class="h-3.5 w-3.5" />
                                    <span class="hidden lg:flex">
                                        {{ activeFilterCount ? `${activeFilterCount} filter${activeFilterCount === 1 ? '' : 's'} active` : 'Filter' }}
                                    </span>
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent align="end">
                                <div class="grid gap-y-2">
                                    <div class="flex flex-col gap-y-1">
                                        <p class="text-sm text-custom-shadow/80">Status</p>
                                        <Select v-model="filterStatus">
                                            <SelectTrigger class="w-full"><SelectValue placeholder="Any status" /></SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">Any status</SelectItem>
                                                <SelectItem value="pending">Pending</SelectItem>
                                                <SelectItem value="arrived">Arrived</SelectItem>
                                                <SelectItem value="departed">Departed</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <hr class="my-1 h-px border-0 bg-custom-bg-dark dark:bg-custom-bg-light">
                                    <div class="flex items-center justify-between">
                                        <Button v-if="activeFilterCount" variant="destructive" size="sm" @click="clearFilters">Clear</Button>
                                        <div class="ml-auto flex items-center gap-2">
                                            <Button variant="ghost-outline" size="sm" @click="filterOpen = false">Cancel</Button>
                                            <Button variant="float-primary" size="sm" @click="applyFilters()">Apply</Button>
                                        </div>
                                    </div>
                                </div>
                            </PopoverContent>
                        </Popover>
                    </div>

                    <TableCard :table-data-length="dispatches.data.length">
                        <Table v-if="dispatches.data.length > 0">
                            <TableHeader hide-actions-column>
                                <TableColumn class="p-0">
                                    <button
                                        type="button"
                                        class="flex h-10 w-full cursor-pointer select-none items-center justify-start gap-1.5 pl-3 pr-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
                                        @click="toggleSort('company_name')"
                                    >
                                        Company
                                        <component :is="sortIcon('company_name')" class="h-3.5 w-3.5" :class="sortIconClass('company_name')" />
                                    </button>
                                </TableColumn>

                                <TableColumn class="p-0">
                                    <button
                                        type="button"
                                        class="flex h-10 w-full cursor-pointer select-none items-center justify-start gap-1.5 pl-3 pr-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
                                        @click="toggleSort('plate_number')"
                                    >
                                        Vehicle
                                        <component :is="sortIcon('plate_number')" class="h-3.5 w-3.5" :class="sortIconClass('plate_number')" />
                                    </button>
                                </TableColumn>

                                <TableColumn>Route</TableColumn>
                                <TableColumn>Gate</TableColumn>

                                <TableColumn class="p-0">
                                    <button
                                        type="button"
                                        class="flex h-10 w-full cursor-pointer select-none items-center justify-start gap-1.5 pl-3 pr-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
                                        @click="toggleSort('status')"
                                    >
                                        Status
                                        <component :is="sortIcon('status')" class="h-3.5 w-3.5" :class="sortIconClass('status')" />
                                    </button>
                                </TableColumn>

                                <TableColumn class="p-0">
                                    <button
                                        type="button"
                                        class="flex h-10 w-full cursor-pointer select-none items-center justify-start gap-1.5 pl-3 pr-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
                                        @click="toggleSort('dispatched_at')"
                                    >
                                        Dispatched At
                                        <component :is="sortIcon('dispatched_at')" class="h-3.5 w-3.5" :class="sortIconClass('dispatched_at')" />
                                    </button>
                                </TableColumn>
                            </TableHeader>

                            <TableContent>
                                <TableRow
                                    v-for="(dispatch, rowIndex) in dispatches.data"
                                    :key="dispatch.id"
                                    :class="[
                                        'h-12',
                                        rowIndex === dispatches.data.length - 1 ? 'rounded-b-md border-b-0' : '',
                                        previewedDispatch?.id === dispatch.id ? 'bg-custom-secondary/10' : '',
                                    ]"
                                    :status="dispatch.status === 'departed' ? 'inactive' : 'default'"
                                    @click.left="openPreview(dispatch)"
                                >
                                    <TableData class="pl-3">
                                        <span class="truncate font-semibold">{{ dispatch.company?.company_name || '—' }}</span>
                                    </TableData>

                                    <TableData>
                                        <span
                                            v-if="dispatch.vehicle?.plate_number"
                                            class="rounded bg-custom-bg px-2 py-0.5 font-mono text-xs font-semibold text-custom-shadow dark:bg-custom-bg-light"
                                        >
                                            {{ dispatch.vehicle.plate_number }}
                                        </span>
                                        <span v-else class="text-sm text-custom-shadow/70">—</span>
                                    </TableData>

                                    <TableData>
                                        <span class="truncate">{{ routeLabel(dispatch.vehicle) }}</span>
                                    </TableData>

                                    <TableData>
                                        <span class="truncate">{{ dispatch.gate?.gate_name || '—' }}</span>
                                    </TableData>

                                    <TableData>
                                        <Badge :class="['gap-1.5', statusClass(dispatch.status)]">
                                            <span :class="['h-1.5 w-1.5 rounded-full', statusDot(dispatch.status)]" />
                                            {{ prettyStatus(dispatch.status) }}
                                        </Badge>
                                    </TableData>

                                    <TableData class="text-sm text-custom-shadow/80">
                                        <span class="truncate">{{ dispatch.dispatched_at || '—' }}</span>
                                    </TableData>
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
                                    <p class="text-custom-shadow text-base font-semibold">No dispatches found</p>
                                    <p class="text-custom-shadow/80 text-sm">
                                        {{ activeFilterCount ? 'Try adjusting or clearing your filters.' : 'Try adjusting your search term.' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </TableCard>

                    <InertiaPagination
                        v-if="dispatches.links?.length"
                        :links="dispatches.links"
                        :meta="{ from: dispatches.from, to: dispatches.to, total: dispatches.total }"
                    />
                </CardContent>
            </Card>

            <Card class="hidden min-h-0 lg:flex lg:h-full lg:w-100">
                <CardHeader
                    v-if="previewedDispatch"
                    class="flex flex-row items-start justify-between gap-3"
                >
                    <div class="min-w-0">
                        <CardTitle class="truncate uppercase">
                            {{ previewedDispatch.vehicle?.plate_number || `Dispatch #${previewedDispatch.id}` }}
                        </CardTitle>
                        <CardDescription>Preview</CardDescription>
                    </div>
                    <Button
                        variant="header-actions"
                        size="icon"
                        class="h-8 w-8 shrink-0 rounded-full"
                        aria-label="Close dispatch preview"
                        @click="previewedDispatch = null"
                    >
                        <RiCloseLine class="h-4 w-4" />
                    </Button>
                </CardHeader>

                <CardContent
                    v-if="previewedDispatch"
                    class="no-scrollbar min-h-0 flex-1 space-y-2 overflow-y-auto py-2"
                >
                    <div class="flex aspect-4/3 items-center justify-center overflow-hidden rounded-md border border-dashed border-custom-bg-dark bg-custom-bg text-custom-shadow/70 dark:border-none dark:bg-custom-bg-dark">
                        <RiClipboardLine class="h-16 w-16" />
                    </div>

                    <div class="space-y-2 pt-2">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm font-semibold text-custom-shadow">Status</span>
                            <Badge :class="['gap-1.5', statusClass(previewedDispatch.status)]">
                                <span :class="['h-1.5 w-1.5 rounded-full', statusDot(previewedDispatch.status)]" />
                                {{ prettyStatus(previewedDispatch.status) }}
                            </Badge>
                        </div>

                        <div class="flex items-start justify-between gap-3">
                            <span class="text-sm font-semibold text-custom-shadow">Company</span>
                            <span class="text-right text-sm">{{ previewedDispatch.company?.company_name || '—' }}</span>
                        </div>

                        <div class="flex items-start justify-between gap-3">
                            <span class="text-sm font-semibold text-custom-shadow">Vehicle</span>
                            <span class="text-right text-sm">{{ previewedDispatch.vehicle?.vehicle_type || previewedDispatch.vehicle?.make_model || 'Not recorded' }}</span>
                        </div>

                        <div class="flex items-start justify-between gap-3">
                            <span class="text-sm font-semibold text-custom-shadow">Route</span>
                            <span class="text-right text-sm">{{ routeLabel(previewedDispatch.vehicle) }}</span>
                        </div>

                        <div class="flex items-start justify-between gap-3">
                            <span class="text-sm font-semibold text-custom-shadow">Gate</span>
                            <span class="text-right text-sm">{{ previewedDispatch.gate?.gate_name || 'Not assigned' }}</span>
                        </div>

                        <div class="flex items-start justify-between gap-3">
                            <span class="text-sm font-semibold text-custom-shadow">Bay</span>
                            <span class="text-right text-sm">{{ previewedDispatch.bay_number || 'Not assigned' }}</span>
                        </div>

                        <div class="flex items-start justify-between gap-3">
                            <span class="text-sm font-semibold text-custom-shadow">PAX Count</span>
                            <span class="text-right text-sm">{{ previewedDispatch.pax_count ?? 'Not recorded' }}</span>
                        </div>

                        <div class="flex items-start justify-between gap-3">
                            <span class="text-sm font-semibold text-custom-shadow">Dispatcher</span>
                            <span class="text-right text-sm">{{ previewedDispatch.dispatcher?.name || 'Not recorded' }}</span>
                        </div>

                        <div class="flex items-start justify-between gap-3">
                            <span class="text-sm font-semibold text-custom-shadow">Driver</span>
                            <span class="text-right text-sm">{{ previewedDispatch.driver?.name || 'Not recorded' }}</span>
                        </div>

                        <div class="flex items-start justify-between gap-3">
                            <span class="text-sm font-semibold text-custom-shadow">Dispatched At</span>
                            <span class="text-right text-sm">{{ previewedDispatch.dispatched_at || 'Not recorded' }}</span>
                        </div>

                        <div class="flex items-start justify-between gap-3">
                            <span class="text-sm font-semibold text-custom-shadow">Arrived At</span>
                            <span class="text-right text-sm">{{ previewedDispatch.arrived_at || 'Not recorded' }}</span>
                        </div>

                        <div class="flex items-start justify-between gap-3">
                            <span class="text-sm font-semibold text-custom-shadow">Departed At</span>
                            <span class="text-right text-sm">{{ previewedDispatch.departed_at || 'Not recorded' }}</span>
                        </div>
                    </div>
                </CardContent>

                <CardContent v-else class="flex min-h-0 flex-1 items-center justify-center">
                    <div class="max-w-60 space-y-1 text-center">
                        <p class="text-base font-semibold text-custom-shadow">No dispatch selected</p>
                        <p class="text-sm text-custom-shadow/80">Click on a dispatch to preview.</p>
                    </div>
                </CardContent>
            </Card>
        </PanelLayout>
    </AppLayout>
</template>
