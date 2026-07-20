<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';
import type { DateValue } from '@internationalized/date';
import {
    DateFormatter,
    getLocalTimeZone,
    parseDate,
    today,
} from '@internationalized/date';

import RouteMapDialog from '@/components/routes/RouteMapDialog.vue';
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription,
} from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Progress } from '@/components/ui/progress';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { TooltipProvider } from '@/components/ui/tooltip';

import {
    RiArrowLeftLine,
    RiBuilding2Line,
    RiCalendarLine,
    RiCloseLine,
    RiFilter2Line,
    RiHashtag,
    RiMailLine,
    RiPhoneLine,
    RiSearchLine,
} from 'vue-remix-icons';

import InternalDispatchController from '@/actions/App/Http/Controllers/InternalDispatchController';
import { index } from '@/actions/App/Http/Controllers/InternalDispatchController';

type RouteGeometry = { type: string; coordinates: [number, number][] };

type RouteStop = {
    id: number;
    stop_name: string;
    stop_order: number;
    stop_type?: string | null;
    address?: string | null;
    latitude?: number | string | null;
    longitude?: number | string | null;
};

type RouteItem = {
    route_name: string | null;
    origin_name: string | null;
    destination_name: string | null;
    route_geometry: RouteGeometry | string | null;
    stops: RouteStop[];
};

type Company = {
    id: number;
    company_name: string;
    company_code: string | null;
    company_email: string | null;
    company_phone: string | null;
    company_logo: string | null;
    status: string | null;
};

type DispatchItem = {
    id: number;
    plate_number: string | null;
    pax_count: number | null;
    bay_number: string | number | null;
    remarks: string | null;
    status: string;
    arrived_at: string | null;
    departed_at: string | null;
    vehicle: {
        plate_number: string | null;
        body_number: string | null;
        vehicle_type: string | null;
        make_model: string | null;
        route: RouteItem | null;
    } | null;
    gate: { gate_name: string | null } | null;
    dispatcher: { name: string } | null;
    driver: { name: string } | null;
};

type PaginationLink = { url: string | null; label: string; active: boolean };

type PaginatedDispatches = {
    data: DispatchItem[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
};

type SummaryEntry = {
    label: string;
    count: number;
    pax: number;
};

type StatusBreakdownItem = {
    status: string;
    count: number;
    pct: number;
};

type DispatchSummary = {
    filtered_total: number;
    total_pax: number;
    avg_pax: number;
    route_coverage_percent: number;
    gate_coverage_percent: number;
    status_breakdown: StatusBreakdownItem[];
    route_summary: SummaryEntry[];
    gate_summary: SummaryEntry[];
    bay_summary: SummaryEntry[];
};

const props = defineProps<{
    filters?: {
        date?: string | null;
        search?: string | null;
        status?: string | null;
    };
    company: Company;
    dispatches: PaginatedDispatches;
    summary: DispatchSummary;
    mapConfig?: {
        mapboxToken?: string | null;
        defaultCenter?: { lng: number; lat: number } | null;
        defaultZoom?: number | null;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dispatches', href: '/dispatches' },
    {
        title: props.company.company_name,
        href: `/dispatches/${props.company.id}`,
    },
];

const df = new DateFormatter('en-PH', { dateStyle: 'medium' });
const defaultPlaceholder = today(getLocalTimeZone());

const selectedDate = ref<DateValue | undefined>(
    props.filters?.date ? parseDate(props.filters.date) : undefined,
);
const selectedStatus = ref(props.filters?.status ?? 'all');
const filterStatus = ref(selectedStatus.value);
const calendarOpen = ref(false);
const filterOpen = ref(false);
const previewedDispatch = ref<DispatchItem | null>(null);

const selectedDateLabel = computed(() =>
    selectedDate.value
        ? df.format(selectedDate.value.toDate(getLocalTimeZone()))
        : null,
);

const filteredTotal = computed(() => props.summary.filtered_total);

const dispatchCountLabel = computed(() => {
    const parts: string[] = [];
    if (selectedDate.value) parts.push(selectedDateLabel.value!);
    if (selectedStatus.value && selectedStatus.value !== 'all')
        parts.push(prettyStatus(selectedStatus.value));
    if (props.filters?.search) parts.push(`"${props.filters.search}"`);
    return parts.length ? parts.join(' · ') : 'All records';
});

const hasActiveFilters = computed(
    () =>
        !!(
            props.filters?.search ||
            selectedDate.value ||
            (selectedStatus.value && selectedStatus.value !== 'all')
        ),
);

const activeFilterCount = computed(() => {
    let n = 0;
    if (props.filters?.search) n++;
    if (selectedDate.value) n++;
    if (selectedStatus.value && selectedStatus.value !== 'all') n++;
    return n;
});

const searchRoute = computed(() => {
    const p = new URLSearchParams();
    if (selectedDate.value) p.set('date', selectedDate.value.toString());
    if (selectedStatus.value && selectedStatus.value !== 'all')
        p.set('status', selectedStatus.value);
    const q = p.toString();
    return q
        ? `/dispatches/${props.company.id}?${q}`
        : `/dispatches/${props.company.id}`;
});

const searchKey = computed(() =>
    [
        props.company.id,
        props.filters?.search ?? '',
        selectedStatus.value,
        selectedDate.value?.toString() ?? '',
    ].join('|'),
);

const totalPax = computed(() => props.summary.total_pax);
const avgPax = computed(() => props.summary.avg_pax);
const routeCoveragePercent = computed(
    () => props.summary.route_coverage_percent,
);
const gateCoveragePercent = computed(() => props.summary.gate_coverage_percent);
const statusBreakdown = computed(() => props.summary.status_breakdown);
const routeSummary = computed(() => props.summary.route_summary);

function barPct(count: number, list: SummaryEntry[]) {
    const max = list[0]?.count ?? 1;
    return Math.round((count / max) * 100);
}

function reload(overrides: {
    date?: string;
    status?: string;
    search?: string;
}) {
    router.get(
        `/dispatches/${props.company.id}`,
        {
            date:
                overrides.date ??
                (selectedDate.value
                    ? selectedDate.value.toString()
                    : undefined),
            status: overrides.status ?? selectedStatus.value ?? 'all',
            search: overrides.search ?? props.filters?.search ?? undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function applyDateFilter(value: DateValue | undefined) {
    selectedDate.value = value;
    reload({ date: value?.toString() });
    calendarOpen.value = false;
}

function applyStatus(value: string) {
    selectedStatus.value = value;
    reload({ status: value });
    filterOpen.value = false;
}

function applyStatusFilter() {
    applyStatus(filterStatus.value);
}

function closeStatusFilter() {
    filterStatus.value = selectedStatus.value;
    filterOpen.value = false;
}

function clearAllFilters() {
    selectedDate.value = undefined;
    selectedStatus.value = 'all';
    filterStatus.value = 'all';
    router.get(
        `/dispatches/${props.company.id}`,
        {},
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

const routeMapOpen = ref(false);
const selectedRouteMap = ref<{
    route_name: string | null;
    origin_name: string | null;
    destination_name: string | null;
    route_geometry: RouteGeometry | null;
    stops: RouteStop[];
} | null>(null);

function normalizeGeometry(
    raw: RouteGeometry | string | null | undefined,
): RouteGeometry | null {
    if (!raw) return null;
    let parsed: unknown = raw;
    if (typeof raw === 'string') {
        try {
            parsed = JSON.parse(raw);
        } catch {
            return null;
        }
    }
    const v = parsed as { type?: unknown; coordinates?: unknown };
    if (v?.type === 'LineString' && Array.isArray(v.coordinates)) {
        const coords = (v.coordinates as unknown[])
            .filter(
                (c): c is [number, number] =>
                    Array.isArray(c) &&
                    c.length >= 2 &&
                    isFinite(+c[0]) &&
                    isFinite(+c[1]),
            )
            .map((c) => [+c[0], +c[1]]) as [number, number][];
        if (coords.length) return { type: 'LineString', coordinates: coords };
    }
    return null;
}

function hasMap(d: DispatchItem) {
    return !!normalizeGeometry(d.vehicle?.route?.route_geometry);
}

function openMap(d: DispatchItem) {
    const route = d.vehicle?.route;
    const geometry = normalizeGeometry(route?.route_geometry);
    if (!route || !geometry) return;
    selectedRouteMap.value = {
        route_name: route.route_name,
        origin_name: route.origin_name,
        destination_name: route.destination_name,
        route_geometry: geometry,
        stops: route.stops ?? [],
    };
    routeMapOpen.value = true;
}

function statusColor(s: string | null | undefined): string {
    switch (s) {
        case 'departed':
            return 'bg-emerald-500/10 text-emerald-700 border-emerald-200 dark:text-emerald-400 dark:border-emerald-800';
        case 'verified':
        case 'active':
            return 'bg-blue-500/10 text-blue-700 border-blue-200 dark:text-blue-400 dark:border-blue-800';
        case 'arrived':
            return 'bg-amber-500/10 text-amber-700 border-amber-200 dark:text-amber-400 dark:border-amber-800';
        default:
            return 'bg-muted text-muted-foreground border-border';
    }
}

function statusBadgeClass(s: string | null | undefined): string {
    switch (s) {
        case 'departed':
            return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'verified':
        case 'active':
            return 'bg-blue-100 text-blue-700 border-blue-200';
        case 'arrived':
            return 'bg-amber-100 text-amber-700 border-amber-200';
        default:
            return 'bg-slate-100 text-slate-500 border-0';
    }
}

function statusBadgeDot(s: string | null | undefined): string {
    switch (s) {
        case 'departed':
            return 'bg-emerald-500';
        case 'verified':
        case 'active':
            return 'bg-blue-500';
        case 'arrived':
            return 'bg-amber-500';
        default:
            return 'bg-slate-400';
    }
}

function prettyStatus(s: string | null | undefined) {
    return String(s ?? 'Unknown')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (c) => c.toUpperCase());
}

function displayedBadgeStatus(status: string | null | undefined) {
    return status === 'arrived' || status === 'departed' ? status : null;
}

const exporting = ref(false);

function exportCsv() {
    exporting.value = true;

    const params = new URLSearchParams();
    if (selectedDate.value) params.set('date', selectedDate.value.toString());
    if (selectedStatus.value && selectedStatus.value !== 'all') params.set('status', selectedStatus.value);
    if (props.filters?.search) params.set('search', props.filters.search);

    const qs = params.toString();
    const a = document.createElement('a');
    a.href = `/dispatches/${props.company.id}/export${qs ? '?' + qs : ''}`;
    a.style.display = 'none';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);

    setTimeout(() => { exporting.value = false; }, 2000);
}
</script>

<template>
    <Head :title="`${company.company_name} — Dispatches`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <TooltipProvider>
            <div class="flex h-full min-h-0 w-full flex-1 flex-col gap-4 lg:flex-row lg:items-stretch">
                <Card class="flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden lg:h-full">
                    <CardHeader class="shrink-0 flex flex-row flex-wrap items-start gap-3 pb-0">
                        <Button as-child variant="header-actions" size="icon">
                            <Link :href="index().url" aria-label="Back to dispatches">
                                <RiArrowLeftLine class="h-4 w-4" />
                            </Link>
                        </Button>
                        <div class="min-w-0 flex-1">
                            <CardTitle class="truncate font-semibold">{{ company.company_name }}</CardTitle>
                            <CardDescription>Review the company’s individual dispatch records.</CardDescription>
                        </div>
                    </CardHeader>

                    <CardContent class="flex min-h-0 flex-1 flex-col space-y-4 py-2">
                        <div class="flex gap-2 flex-row sm:items-center">
                            <SearchInput
                                :key="searchKey"
                                :route="searchRoute"
                                :initial-value="filters?.search ?? ''"
                                placeholder="Search dispatches..."
                                class="w-full"
                            />
                            <Popover v-model:open="filterOpen">
                                <PopoverTrigger as-child>
                                    <Button
                                        variant="header-actions"
                                        size="icon-text"
                                        class="rounded-full"
                                        :class="activeFilterCount > 0
                                            ? 'bg-custom-secondary/20 transition-all duration-300 hover:bg-custom-secondary/80 hover:text-custom-bg-light'
                                            : ''"
                                    >
                                        <RiFilter2Line class="h-3.5 w-3.5" />
                                        <span class="hidden lg:flex">
                                            {{ activeFilterCount > 0
                                                ? (activeFilterCount === 1 ? '1 filter active' : `${activeFilterCount} filters active`)
                                                : 'Filter' }}
                                        </span>
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent align="end">
                                    <div class="grid gap-y-2">
                                        <div class="flex flex-col gap-y-1">
                                            <p class="text-sm text-custom-shadow/80">Status</p>
                                            <Select v-model="filterStatus">
                                                <SelectTrigger class="w-full">
                                                    <SelectValue placeholder="Any status" class="flex justify-start" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="all" class="cursor-pointer">Any status</SelectItem>
                                                    <SelectItem value="arrived" class="cursor-pointer">Arrived</SelectItem>
                                                    <SelectItem value="departed" class="cursor-pointer">Departed</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <hr class="my-1 h-px border-0 bg-custom-bg-dark dark:bg-custom-bg-light">

                                        <div class="flex w-full flex-row items-center justify-between">
                                            <Button
                                                v-if="activeFilterCount > 0"
                                                size="sm"
                                                variant="destructive"
                                                @click="clearAllFilters"
                                            >
                                                Clear
                                            </Button>
                                            <div class="ml-auto flex items-center gap-2">
                                                <Button variant="ghost-outline" size="sm" @click="closeStatusFilter">
                                                    Cancel
                                                </Button>
                                                <Button size="sm" variant="float-primary" @click="applyStatusFilter">
                                                    Apply
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                </PopoverContent>
                            </Popover>
                            <Popover>
                                <PopoverTrigger as-child>
                                    <Button variant="header-actions" size="icon-text" class="w-full justify-start gap-2 rounded-full sm:w-auto">
                                        <RiCalendarLine class="h-4 w-4 shrink-0" />
                                        <span class="text-sm">{{ selectedDateLabel ?? 'Pick a date' }}</span>
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent align="start">
                                    <p class="mb-2 text-xs font-semibold tracking-widest text-muted-foreground uppercase">Dispatch Date</p>
                                    <Calendar
                                        :model-value="selectedDate"
                                        :default-placeholder="defaultPlaceholder"
                                        layout="month-and-year"
                                        initial-focus
                                        class="px-0 pb-0"
                                        @update:model-value="applyDateFilter"
                                    />
                                    <div v-if="selectedDate" class="border-t p-2">
                                        <Button variant="ghost" size="sm" class="w-full text-muted-foreground" @click="applyDateFilter(undefined)">
                                            <RiCloseLine class="size-3.5" />Clear date
                                        </Button>
                                    </div>
                                </PopoverContent>
                            </Popover>
                        </div>

                    <Card
                        :class="[
                            'flex min-h-0 max-h-fit flex-1 flex-col overflow-hidden border border-custom-bg-dark py-0 shadow-none dark:border-custom-bg-light dark:inset-shadow-none',
                            dispatches.data.length === 0 ? 'border-dashed' : 'border-solid',
                        ]"
                    >
                        <div class="no-scrollbar min-h-0 flex-1 overflow-auto">
                            <div class="flex min-h-0 min-w-0 w-full flex-1 flex-col overflow-hidden">
                                <div class="no-scrollbar min-h-0 flex-1 overflow-y-auto">
                                    <div v-if="dispatches.data.length > 0" class="flex min-h-0 flex-1 flex-col overflow-hidden">
                                        <div class="shrink-0 rounded-t-md bg-custom-bg dark:bg-custom-bg-light">
                                            <div class="grid grid-cols-[.5fr_1fr_.5fr_.25fr_1fr] gap-2 border-b border-custom-bg-dark dark:border-custom-bg-light">
                                                <div class="flex h-10 items-center justify-start pl-3 text-xs font-semibold tracking-widest text-custom-shadow/80 uppercase">Vehicle</div>
                                                <div class="flex h-10 items-center justify-start text-xs font-semibold tracking-widest text-custom-shadow/80 uppercase">Route</div>
                                                <div class="flex h-10 items-center justify-start text-xs font-semibold tracking-widest text-custom-shadow/80 uppercase">Gate / Bay</div>
                                                <div class="flex h-10 items-center justify-start text-xs font-semibold tracking-widest text-custom-shadow/80 uppercase">Cap.</div>
                                                <div class="flex h-10 items-center justify-start text-xs font-semibold tracking-widest text-custom-shadow/80 uppercase">Timestamp</div>
                                            </div>
                                        </div>

                                        <div class="no-scrollbar min-h-0 flex-1 overflow-y-auto">
                                            <div
                                                v-for="(dispatch, dispatchIndex) in dispatches.data"
                                                :key="dispatch.id"
                                                :class="[
                                                    'group grid cursor-pointer grid-cols-[.5fr_1fr_.5fr_.25fr_1fr] items-center gap-2 border-b border-custom-bg-dark text-custom-shadow/80 transition-colors hover:bg-custom-secondary/10 hover:text-custom-shadow dark:border-custom-bg-light bg-transparent',
                                                    dispatchIndex === dispatches.data.length - 1 ? 'rounded-b-md border-b-0' : '',
                                                    previewedDispatch?.id === dispatch.id ? 'bg-custom-secondary/10 text-custom-shadow' : '',
                                                ]"
                                                @click="previewedDispatch = dispatch"
                                            >
                                                <div class="py-1.5 pl-3">
                                                    <p class="text-sm font-semibold">{{ dispatch.plate_number || dispatch.vehicle?.plate_number || '—' }}</p>
                                                    <p class="text-xs text-custom-shadow/80">{{ dispatch.vehicle?.vehicle_type || '—' }}</p>
                                                </div>

                                                <div class="min-w-0 py-1.5">
                                                    <p class="text-sm font-semibold">{{ dispatch.vehicle?.route?.route_name || '—' }}</p>
                                                </div>

                                                <div class="min-w-0 py-1.5">
                                                    <p class="flex items-center gap-1.5 text-sm font-semibold text-custom-shadow/80">{{ dispatch.gate?.gate_name || '—' }}</p>
                                                    <p v-if="dispatch.bay_number" class="mt-0.5 text-custom-shadow/80 text-xs">Bay {{ dispatch.bay_number }}</p>
                                                </div>
                                                
                                                <div class="min-w-0 py-1.5">
                                                    <p class="flex items-center gap-1.5 text-sm">{{ dispatch.pax_count || '—' }}</p>
                                                </div>

                                                <div class="min-w-0 py-1.5">
                                                    <div v-if="dispatch.arrived_at" class="flex gap-1.5"><div><p class="text-custom-shadow/80 text-sm font-semibold">Arrived</p><p class="text-custom-shadow/80 text-xs">{{ dispatch.arrived_at }}</p></div></div>
                                                    <div v-if="dispatch.departed_at" class="flex gap-1.5"><div><p class="text-custom-shadow/80 text-sm font-semibold">Departed</p><p class="text-custom-shadow/80 text-xs">{{ dispatch.departed_at }}</p></div></div>
                                                    <p v-if="!dispatch.arrived_at && !dispatch.departed_at" class="text-custom-shadow/80 text-sm">No timestamps</p>
                                                </div>
                                                
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="flex min-h-0 flex-1 items-center justify-center p-6 text-center">
                                        <div class="flex w-full max-w-md flex-col items-center justify-center gap-2">
                                            <img
                                                :src="emptyRafikiUrl"
                                                alt=""
                                                class="w-1/3 object-contain opacity-90"
                                                aria-hidden="true"
                                            />
                                            <div class="space-y-1">
                                                <p class="text-base font-semibold text-custom-shadow">{{ selectedDate ? `No dispatches on ${selectedDateLabel}` : 'No dispatch records found' }}</p>
                                                <p class="text-sm text-custom-shadow/80">
                                                    {{ selectedDate ? 'Try a different date or clear the filter.' : 'Try adjusting your search or filter.' }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Card>

                    <Separator/>

                    <InertiaPagination
                        :links="dispatches.links"
                        :meta="{ from: dispatches.from, to: dispatches.to, total: dispatches.total }"
                    />
                    </CardContent>

                    <CardContent class="no-scrollbar min-h-0 flex-1 space-y-3 overflow-y-auto border-t border-slate-100 p-4 dark:border-custom-bg-light lg:hidden">
                        <template v-if="dispatches.data.length > 0">
                            <div
                                v-for="dispatch in dispatches.data"
                                :key="dispatch.id"
                                class="overflow-hidden rounded-xl border bg-background"
                            >
                                <div
                                    class="flex items-center justify-between gap-3 border-b bg-muted/30 px-3 py-2"
                                >
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-semibold">{{
                                            dispatch.plate_number ||
                                            dispatch.vehicle?.plate_number ||
                                            '—'
                                        }}</span>
                                        <span
                                            class="text-xs text-muted-foreground"
                                            >{{
                                                dispatch.vehicle
                                                    ?.vehicle_type || ''
                                            }}</span
                                        >
                                    </div>
                                </div>

                                <div class="space-y-3 p-3">
                                    <div>
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            Route
                                        </p>
                                        <p class="text-sm font-medium">
                                            {{
                                                dispatch.vehicle?.route
                                                    ?.route_name || '—'
                                            }}
                                        </p>
                                    </div>

                                    <Separator />

                                    <div class="grid grid-cols-3 gap-3 text-sm">
                                        <div>
                                            <p
                                                class="text-xs text-muted-foreground"
                                            >
                                                Gate
                                            </p>
                                            <p class="font-medium">
                                                {{
                                                    dispatch.gate?.gate_name ||
                                                    '—'
                                                }}
                                            </p>
                                            <p
                                                class="text-xs text-muted-foreground"
                                            >
                                                {{
                                                    dispatch.bay_number
                                                        ? `Bay ${dispatch.bay_number}`
                                                        : '—'
                                                }}
                                            </p>
                                        </div>
                                        <div>
                                            <p
                                                class="text-xs text-muted-foreground"
                                            >
                                                PAX
                                            </p>
                                            <p
                                                class="text-lg font-bold tabular-nums"
                                            >
                                                {{ dispatch.pax_count ?? '—' }}
                                            </p>
                                        </div>
                                        <div>
                                            <p
                                                class="text-xs text-muted-foreground"
                                            >
                                                Driver
                                            </p>
                                            <p class="font-medium">
                                                {{
                                                    dispatch.driver?.name || '—'
                                                }}
                                            </p>
                                        </div>
                                    </div>

                                    <Separator />

                                    <div
                                        class="flex flex-wrap gap-x-4 gap-y-1.5"
                                    >
                                        <div
                                            v-if="dispatch.arrived_at"
                                            class="flex items-start gap-1.5"
                                        >
                                            <div>
                                                <p
                                                    class="text-[10px] text-muted-foreground"
                                                >
                                                    Arrived
                                                </p>
                                                <p class="text-xs font-medium">
                                                    {{ dispatch.arrived_at }}
                                                </p>
                                            </div>
                                        </div>
                                        <div
                                            v-if="dispatch.departed_at"
                                            class="flex items-start gap-1.5"
                                        >
                                            <div>
                                                <p
                                                    class="text-[10px] text-muted-foreground"
                                                >
                                                    Departed
                                                </p>
                                                <p class="text-xs font-medium">
                                                    {{ dispatch.departed_at }}
                                                </p>
                                            </div>
                                        </div>
                                        <p
                                            v-if="!dispatch.arrived_at && !dispatch.departed_at"
                                            class="text-xs text-muted-foreground/50"
                                        >
                                            No timestamps
                                        </p>
                                    </div>

                                    <div v-if="dispatch.remarks">
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            Remarks
                                        </p>
                                        <p class="text-xs">
                                            {{ dispatch.remarks }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <InertiaPagination :links="dispatches.links" />
                        </template>

                        <div
                            v-else
                            class="flex flex-col items-center gap-3 py-14 text-center"
                        >
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-muted">
                                <RiSearchLine class="h-6 w-6 text-muted-foreground/40" />
                            </div>
                            <div>
                                <p class="text-sm font-medium">
                                    No records found
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{
                                        hasActiveFilters
                                            ? 'Try adjusting your search or filters.'
                                            : 'No dispatch records for this company yet.'
                                    }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="hidden min-h-0 lg:flex lg:h-full lg:w-100">
                    <CardHeader v-if="previewedDispatch" class="flex flex-row items-start justify-between gap-3">
                        <div class="min-w-0">
                            <CardTitle class="truncate uppercase">
                                {{ previewedDispatch.plate_number || previewedDispatch.vehicle?.plate_number || 'Dispatch' }}
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

                    <CardContent v-if="previewedDispatch" class="no-scrollbar min-h-0 flex-1 space-y-4 overflow-y-auto py-2">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm font-semibold text-custom-shadow">Status</span>
                            <Badge v-if="displayedBadgeStatus(previewedDispatch.status)" :class="['gap-1.5', statusBadgeClass(displayedBadgeStatus(previewedDispatch.status))]">
                                <span :class="['h-1.5 w-1.5 rounded-full', statusBadgeDot(displayedBadgeStatus(previewedDispatch.status))]" />
                                {{ prettyStatus(displayedBadgeStatus(previewedDispatch.status)) }}
                            </Badge>
                        </div>

                        <div class="space-y-2 rounded-md bg-custom-bg px-3 py-3 dark:bg-custom-bg-dark">
                            <div class="flex justify-between gap-3 text-sm">
                                <span class="font-semibold text-custom-shadow">Vehicle</span>
                                <span class="text-right text-custom-shadow/80">{{ previewedDispatch.vehicle?.make_model || previewedDispatch.vehicle?.vehicle_type || '—' }}</span>
                            </div>
                            <div class="flex justify-between gap-3 text-sm">
                                <span class="font-semibold text-custom-shadow">Passengers</span>
                                <span class="text-custom-shadow/80">{{ previewedDispatch.pax_count ?? '—' }}</span>
                            </div>
                            <div class="flex justify-between gap-3 text-sm">
                                <span class="font-semibold text-custom-shadow">Gate / Bay</span>
                                <span class="text-right text-custom-shadow/80">{{ previewedDispatch.gate?.gate_name || '—' }}<template v-if="previewedDispatch.bay_number"> · Bay {{ previewedDispatch.bay_number }}</template></span>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <p class="text-sm font-semibold text-custom-shadow">Route</p>
                            <div class="rounded-md bg-custom-bg px-3 py-2 dark:bg-custom-bg-dark">
                                <p class="text-sm font-medium text-custom-shadow">{{ previewedDispatch.vehicle?.route?.route_name || '—' }}</p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <p class="text-sm font-semibold text-custom-shadow">Personnel</p>
                            <div class="space-y-2 rounded-md bg-custom-bg px-3 py-2 dark:bg-custom-bg-dark">
                                <div class="flex justify-between gap-3 text-sm"><span class="text-custom-shadow/70">Dispatcher</span><span class="text-right text-custom-shadow">{{ previewedDispatch.dispatcher?.name || '—' }}</span></div>
                                <div class="flex justify-between gap-3 text-sm"><span class="text-custom-shadow/70">Driver</span><span class="text-right text-custom-shadow">{{ previewedDispatch.driver?.name || '—' }}</span></div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <p class="text-sm font-semibold text-custom-shadow">Timeline</p>
                            <div class="space-y-2 rounded-md bg-custom-bg px-3 py-2 text-sm dark:bg-custom-bg-dark">
                                <div class="flex justify-between gap-3"><span class="text-custom-shadow/70">Arrived</span><span class="text-right">{{ previewedDispatch.arrived_at || '—' }}</span></div>
                                <div class="flex justify-between gap-3"><span class="text-custom-shadow/70">Departed</span><span class="text-right">{{ previewedDispatch.departed_at || '—' }}</span></div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <p class="text-sm font-semibold text-custom-shadow">Remarks</p>
                            <p class="break-words rounded-md bg-custom-bg px-3 py-2 text-sm text-custom-shadow/80 dark:bg-custom-bg-dark">{{ previewedDispatch.remarks || 'No remarks.' }}</p>
                        </div>
                    </CardContent>

                    <template v-else>
                        <CardHeader>
                            <CardTitle>Statistics</CardTitle>
                            <CardDescription>{{ dispatchCountLabel }}</CardDescription>
                        </CardHeader>
                        <CardContent class="no-scrollbar min-h-0 flex-1 space-y-6 overflow-y-auto py-2">
                            <div class="space-y-2">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-semibold text-custom-shadow">Passengers</p>
                                    <span class="text-xs text-custom-shadow/70">{{ filteredTotal.toLocaleString() }} dispatches</span>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div class="rounded-md bg-custom-bg px-3 py-3 text-center dark:bg-custom-bg-dark">
                                        <p class="text-xs text-custom-shadow/70">Total PAX</p>
                                        <p class="mt-1 text-xl font-semibold text-custom-shadow tabular-nums">{{ totalPax.toLocaleString() }}</p>
                                    </div>
                                    <div class="rounded-md bg-custom-bg px-3 py-3 text-center dark:bg-custom-bg-dark">
                                        <p class="text-xs text-custom-shadow/70">Average</p>
                                        <p class="mt-1 text-xl font-semibold text-custom-shadow tabular-nums">{{ avgPax }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-semibold text-custom-shadow">Top Routes</p>
                                    <span class="text-xs text-custom-shadow/70">{{ routeSummary.length }}</span>
                                </div>
                                <div v-if="routeSummary.length" class="space-y-2">
                                    <div
                                        v-for="entry in routeSummary"
                                        :key="entry.label"
                                        class="rounded-md bg-custom-bg px-3 py-2 dark:bg-custom-bg-dark"
                                    >
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="min-w-0 truncate text-sm font-medium text-custom-shadow">{{ entry.label }}</span>
                                            <span class="shrink-0 text-xs text-custom-shadow/70">{{ entry.count }}x · {{ entry.pax }} pax</span>
                                        </div>
                                        <Progress :model-value="barPct(entry.count, routeSummary)" class="mt-2 h-1.5" />
                                    </div>
                                </div>
                                <p v-else class="rounded-md bg-custom-bg px-3 py-2 text-sm text-custom-shadow/70 dark:bg-custom-bg-dark">No route data.</p>
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-semibold text-custom-shadow">Status Breakdown</p>
                                    <span class="text-xs text-custom-shadow/70">{{ statusBreakdown.length }}</span>
                                </div>
                                <div v-if="statusBreakdown.length" class="space-y-2">
                                    <div
                                        v-for="(item, index) in statusBreakdown"
                                        :key="item.status"
                                        class="flex items-center justify-between gap-3 rounded-md bg-custom-bg px-3 py-2 dark:bg-custom-bg-dark"
                                    >
                                        <div class="flex min-w-0 items-center gap-2">
                                            <span class="h-2 w-2 shrink-0 rounded-full" :style="{ backgroundColor: `var(--chart-${(index % 5) + 1})` }" />
                                            <span class="truncate text-sm">{{ prettyStatus(item.status) }}</span>
                                        </div>
                                        <span class="shrink-0 text-xs text-custom-shadow/70">{{ item.count }} · {{ item.pct }}%</span>
                                    </div>
                                </div>
                                <p v-else class="rounded-md bg-custom-bg px-3 py-2 text-sm text-custom-shadow/70 dark:bg-custom-bg-dark">No status data.</p>
                            </div>
                        </CardContent>
                    </template>
                </Card>
            </div>
        </TooltipProvider>

        <RouteMapDialog
            v-if="selectedRouteMap"
            v-model:open="routeMapOpen"
            :route-name="selectedRouteMap.route_name"
            :origin-name="selectedRouteMap.origin_name"
            :destination-name="selectedRouteMap.destination_name"
            :route-geometry="selectedRouteMap.route_geometry"
            :stops="selectedRouteMap.stops"
            :mapbox-token="mapConfig?.mapboxToken ?? ''"
            :default-center="mapConfig?.defaultCenter ?? null"
            :default-zoom="mapConfig?.defaultZoom ?? 11"
        />
    </AppLayout>
</template>
