<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

import type { DateValue } from '@internationalized/date'
import { DateFormatter, getLocalTimeZone, parseDate, today } from '@internationalized/date'

import RouteMapDialog from '@/components/routes/RouteMapDialog.vue'
import InertiaPagination from '@/components/InertiaPagination.vue'
import SearchInput from '@/components/SearchInput.vue'

import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Calendar } from '@/components/ui/calendar'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { Progress } from '@/components/ui/progress'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Separator } from '@/components/ui/separator'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { TooltipProvider } from '@/components/ui/tooltip'

import type { ChartConfig } from '@/components/ui/chart'
import { ChartContainer } from '@/components/ui/chart'
import { VisDonut, VisSingleContainer } from '@unovis/vue'

import {
    ArrowLeft,
    Building2,
    Bus,
    CalendarDays,
    ChevronRight,
    ClipboardList,
    Clock,
    Filter,
    Hash,
    LogIn,
    LogOut,
    Mail,
    Map as MapIcon,
    MapPin,
    MapPinned,
    Navigation,
    Phone,
    Search,
    SlidersHorizontal,
    Users,
    X,
} from 'lucide-vue-next'

import InternalDispatchController from '@/actions/App/Http/Controllers/InternalDispatchController'

type RouteGeometry = { type: string; coordinates: [number, number][] }

type RouteStop = {
    id: number
    stop_name: string
    stop_order: number
    stop_type?: string | null
    address?: string | null
    latitude?: number | string | null
    longitude?: number | string | null
}

type RouteItem = {
    route_name: string | null
    origin_name: string | null
    destination_name: string | null
    route_geometry: RouteGeometry | string | null
    stops: RouteStop[]
}

type Company = {
    id: number
    company_name: string
    company_code: string | null
    company_email: string | null
    company_phone: string | null
    company_logo: string | null
    status: string | null
}

type DispatchItem = {
    id: number
    plate_number: string | null
    pax_count: number | null
    bay_number: string | number | null
    remarks: string | null
    status: string
    arrived_at: string | null
    departed_at: string | null
    dispatched_at: string | null
    vehicle: {
        plate_number: string | null
        body_number: string | null
        vehicle_type: string | null
        make_model: string | null
        route: RouteItem | null
    } | null
    gate: { gate_name: string | null } | null
    dispatcher: { name: string } | null
    driver: { name: string } | null
}

type PaginationLink = { url: string | null; label: string; active: boolean }

type PaginatedDispatches = {
    data: DispatchItem[]
    links: PaginationLink[]
    current_page: number
    last_page: number
    from: number | null
    to: number | null
    total: number
}

type SummaryEntry = {
    label: string
    count: number
    pax: number
}

type StatusBreakdownItem = {
    status: string
    count: number
    pct: number
}

type DispatchSummary = {
    filtered_total: number
    total_pax: number
    avg_pax: number
    route_coverage_percent: number
    gate_coverage_percent: number
    status_breakdown: StatusBreakdownItem[]
    route_summary: SummaryEntry[]
    gate_summary: SummaryEntry[]
    bay_summary: SummaryEntry[]
}

const props = defineProps<{
    filters?: { date?: string | null; search?: string | null; status?: string | null }
    company: Company
    dispatches: PaginatedDispatches
    summary: DispatchSummary
    mapConfig?: {
        mapboxToken?: string | null
        defaultCenter?: { lng: number; lat: number } | null
        defaultZoom?: number | null
    }
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dispatches', href: '/dispatches' },
    { title: props.company.company_name, href: `/dispatches/${props.company.id}` },
]

const df = new DateFormatter('en-PH', { dateStyle: 'medium' })
const defaultPlaceholder = today(getLocalTimeZone())

const selectedDate = ref<DateValue | undefined>(
    props.filters?.date ? parseDate(props.filters.date) : undefined,
)
const selectedStatus = ref(props.filters?.status ?? 'all')
const calendarOpen = ref(false)

const selectedDateLabel = computed(() =>
    selectedDate.value ? df.format(selectedDate.value.toDate(getLocalTimeZone())) : null,
)

const filteredTotal = computed(() => props.summary.filtered_total)

const dispatchCountLabel = computed(() => {
    const parts: string[] = []
    if (selectedDate.value) parts.push(selectedDateLabel.value!)
    if (selectedStatus.value && selectedStatus.value !== 'all') parts.push(prettyStatus(selectedStatus.value))
    if (props.filters?.search) parts.push(`"${props.filters.search}"`)
    return parts.length ? parts.join(' · ') : 'All records'
})

const hasActiveFilters = computed(() =>
    !!(props.filters?.search || selectedDate.value || (selectedStatus.value && selectedStatus.value !== 'all')),
)

const activeFilterCount = computed(() => {
    let n = 0
    if (props.filters?.search) n++
    if (selectedDate.value) n++
    if (selectedStatus.value && selectedStatus.value !== 'all') n++
    return n
})

const searchRoute = computed(() => {
    const p = new URLSearchParams()
    if (selectedDate.value) p.set('date', selectedDate.value.toString())
    if (selectedStatus.value && selectedStatus.value !== 'all') p.set('status', selectedStatus.value)
    const q = p.toString()
    return q ? `/dispatches/${props.company.id}?${q}` : `/dispatches/${props.company.id}`
})

const searchKey = computed(() =>
    [props.company.id, props.filters?.search ?? '', selectedStatus.value, selectedDate.value?.toString() ?? ''].join('|'),
)

const totalPax = computed(() => props.summary.total_pax)
const avgPax = computed(() => props.summary.avg_pax)
const routeCoveragePercent = computed(() => props.summary.route_coverage_percent)
const gateCoveragePercent = computed(() => props.summary.gate_coverage_percent)

const statusBreakdown = computed(() => props.summary.status_breakdown)

const donutData = computed(() =>
    statusBreakdown.value.map((s) => ({ value: s.count })),
)

const donutChartConfig = computed((): ChartConfig => {
    const config: ChartConfig = {}
    statusBreakdown.value.forEach((item, i) => {
        config[item.status] = {
            label: prettyStatus(item.status),
            color: `var(--chart-${(i % 5) + 1})`,
        }
    })
    return config
})

const donutColorFn = computed(
    () => (_d: { value: number }, i: number) => `var(--chart-${(i % 5) + 1})`,
)

const routeSummary = computed(() => props.summary.route_summary)
const gateSummary = computed(() => props.summary.gate_summary)
const baySummary = computed(() => props.summary.bay_summary)

function barPct(count: number, list: SummaryEntry[]) {
    const max = list[0]?.count ?? 1
    return Math.round((count / max) * 100)
}

function reload(overrides: { date?: string; status?: string; search?: string }) {
    router.get(
        `/dispatches/${props.company.id}`,
        {
            date: overrides.date ?? (selectedDate.value ? selectedDate.value.toString() : undefined),
            status: overrides.status ?? selectedStatus.value ?? 'all',
            search: overrides.search ?? props.filters?.search ?? undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    )
}

function applyDateFilter(value: DateValue | undefined) {
    selectedDate.value = value
    reload({ date: value?.toString() })
    calendarOpen.value = false
}

function applyStatus(value: string) {
    selectedStatus.value = value
    reload({ status: value })
}

function clearAllFilters() {
    selectedDate.value = undefined
    selectedStatus.value = 'all'
    router.get(`/dispatches/${props.company.id}`, {}, { preserveState: true, preserveScroll: true, replace: true })
}

const routeMapOpen = ref(false)
const selectedRouteMap = ref<{
    route_name: string | null
    origin_name: string | null
    destination_name: string | null
    route_geometry: RouteGeometry | null
    stops: RouteStop[]
} | null>(null)

function normalizeGeometry(raw: RouteGeometry | string | null | undefined): RouteGeometry | null {
    if (!raw) return null
    let parsed: unknown = raw
    if (typeof raw === 'string') {
        try {
            parsed = JSON.parse(raw)
        } catch {
            return null
        }
    }
    const v = parsed as { type?: unknown; coordinates?: unknown }
    if (v?.type === 'LineString' && Array.isArray(v.coordinates)) {
        const coords = (v.coordinates as unknown[])
            .filter((c): c is [number, number] => Array.isArray(c) && c.length >= 2 && isFinite(+c[0]) && isFinite(+c[1]))
            .map((c) => [+c[0], +c[1]]) as [number, number][]
        if (coords.length) return { type: 'LineString', coordinates: coords }
    }
    return null
}

function hasMap(d: DispatchItem) {
    return !!normalizeGeometry(d.vehicle?.route?.route_geometry)
}

function openMap(d: DispatchItem) {
    const route = d.vehicle?.route
    const geometry = normalizeGeometry(route?.route_geometry)
    if (!route || !geometry) return
    selectedRouteMap.value = {
        route_name: route.route_name,
        origin_name: route.origin_name,
        destination_name: route.destination_name,
        route_geometry: geometry,
        stops: route.stops ?? [],
    }
    routeMapOpen.value = true
}

function statusColor(s: string | null | undefined): string {
    switch (s) {
        case 'departed':
            return 'bg-emerald-500/10 text-emerald-700 border-emerald-200 dark:text-emerald-400 dark:border-emerald-800'
        case 'verified':
        case 'active':
            return 'bg-blue-500/10 text-blue-700 border-blue-200 dark:text-blue-400 dark:border-blue-800'
        case 'arrived':
            return 'bg-amber-500/10 text-amber-700 border-amber-200 dark:text-amber-400 dark:border-amber-800'
        default:
            return 'bg-muted text-muted-foreground border-border'
    }
}

function prettyStatus(s: string | null | undefined) {
    return String(s ?? 'Unknown').replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}
</script>

<template>
    <Head :title="`${company.company_name} — Dispatches`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <TooltipProvider>
            <div class="flex flex-col gap-5 p-4 sm:p-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <div class="relative shrink-0">
                            <div class="flex size-12 items-center justify-center overflow-hidden rounded-xl border bg-muted">
                                <img v-if="company.company_logo" :src="company.company_logo" :alt="company.company_name" class="size-full object-cover" />
                                <Building2 v-else class="size-5 text-muted-foreground" />
                            </div>
                            <span
                                class="absolute -right-0.5 -top-0.5 size-3 rounded-full border-2 border-background"
                                :class="company.status === 'active' ? 'bg-emerald-500' : 'bg-muted-foreground'"
                            />
                        </div>

                        <div>
                            <div class="flex items-center gap-2">
                                <h1 class="text-lg font-semibold tracking-tight">{{ company.company_name }}</h1>
                                <Badge variant="outline" class="text-xs">{{ prettyStatus(company.status) }}</Badge>
                            </div>
                            <div class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs text-muted-foreground">
                                <span v-if="company.company_code" class="flex items-center gap-1"><Hash class="size-3" />{{ company.company_code }}</span>
                                <span v-if="company.company_email" class="flex items-center gap-1"><Mail class="size-3" />{{ company.company_email }}</span>
                                <span v-if="company.company_phone" class="flex items-center gap-1"><Phone class="size-3" />{{ company.company_phone }}</span>
                            </div>
                        </div>
                    </div>

                    <Button variant="outline" size="sm" as-child class="w-full sm:w-auto">
                        <Link :href="InternalDispatchController.index().url">
                            <ArrowLeft class="size-3.5" />Back to companies
                        </Link>
                    </Button>
                </div>

                <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
                    <Card>
                        <CardContent class="p-4">
                            <p class="text-xs text-muted-foreground">Dispatches</p>
                            <p class="mt-1 text-2xl font-bold tabular-nums">{{ filteredTotal.toLocaleString() }}</p>
                            <p class="mt-0.5 truncate text-xs text-muted-foreground">{{ dispatchCountLabel }}</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-4">
                            <p class="text-xs text-muted-foreground">Total PAX</p>
                            <p class="mt-1 text-2xl font-bold tabular-nums">{{ totalPax.toLocaleString() }}</p>
                            <p class="mt-0.5 text-xs text-muted-foreground">For the full filtered result</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="p-4">
                            <p class="text-xs text-muted-foreground">Avg PAX / dispatch</p>
                            <p class="mt-1 text-2xl font-bold tabular-nums">{{ avgPax }}</p>
                            <p class="mt-0.5 text-xs text-muted-foreground">For the full filtered result</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent class="space-y-2 p-4">
                            <p class="text-xs text-muted-foreground">Data Coverage</p>
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-muted-foreground">Routes</span>
                                    <span class="font-medium tabular-nums">{{ routeCoveragePercent }}%</span>
                                </div>
                                <Progress :model-value="routeCoveragePercent" class="h-1.5" />
                            </div>
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-muted-foreground">Gates</span>
                                    <span class="font-medium tabular-nums">{{ gateCoveragePercent }}%</span>
                                </div>
                                <Progress :model-value="gateCoveragePercent" class="h-1.5" />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card class="overflow-hidden">
                    <CardHeader class="pb-0">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <CardTitle>Dispatch Records</CardTitle>
                                <CardDescription>
                                    {{ filteredTotal.toLocaleString() }} record{{ filteredTotal !== 1 ? 's' : '' }}
                                    <template v-if="dispatches.from && dispatches.to">
                                        · showing {{ dispatches.from }}–{{ dispatches.to }}
                                    </template>
                                </CardDescription>
                            </div>

                            <div v-if="hasActiveFilters" class="flex flex-wrap items-center gap-1.5">
                                <Badge v-if="selectedDate" variant="secondary" class="gap-1 text-xs">
                                    <CalendarDays class="size-3" />{{ selectedDateLabel }}
                                    <button class="ml-0.5 opacity-60 hover:opacity-100" @click="applyDateFilter(undefined)"><X class="size-3" /></button>
                                </Badge>
                                <Badge v-if="selectedStatus !== 'all'" variant="secondary" class="gap-1 text-xs">
                                    {{ prettyStatus(selectedStatus) }}
                                    <button class="ml-0.5 opacity-60 hover:opacity-100" @click="applyStatus('all')"><X class="size-3" /></button>
                                </Badge>
                                <Badge v-if="filters?.search" variant="secondary" class="text-xs">"{{ filters.search }}"</Badge>
                                <Button variant="ghost" size="sm" class="h-6 gap-1 px-2 text-xs text-muted-foreground" @click="clearAllFilters">
                                    <X class="size-3" />Clear all
                                </Button>
                            </div>
                        </div>

                        <Separator class="mt-4" />

                        <div class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center">
                            <div class="w-full sm:w-64">
                                <SearchInput
                                    :key="searchKey"
                                    :route="searchRoute"
                                    :initial-value="filters?.search ?? ''"
                                    :only="['dispatches', 'filters', 'summary']"
                                    placeholder="Plate, route, driver..."
                                />
                            </div>

                            <Select :model-value="selectedStatus" @update:model-value="applyStatus">
                                <SelectTrigger class="w-full sm:w-40">
                                    <SlidersHorizontal class="size-3.5 text-muted-foreground" />
                                    <SelectValue placeholder="Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All statuses</SelectItem>
                                    <SelectItem value="arrived">Arrived</SelectItem>
                                    <SelectItem value="departed">Departed</SelectItem>
                                </SelectContent>
                            </Select>

                            <Popover v-model:open="calendarOpen">
                                <PopoverTrigger as-child>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        class="w-full justify-start gap-2 sm:w-auto"
                                        :class="selectedDate ? 'border-primary/50 text-primary' : ''"
                                    >
                                        <CalendarDays class="size-3.5" />{{ selectedDateLabel ?? 'Pick a date' }}
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent class="w-auto p-0" align="start">
                                    <Calendar
                                        :model-value="selectedDate"
                                        :default-placeholder="defaultPlaceholder"
                                        layout="month-and-year"
                                        initial-focus
                                        @update:model-value="applyDateFilter"
                                    />
                                    <div v-if="selectedDate" class="border-t p-2">
                                        <Button variant="ghost" size="sm" class="w-full text-muted-foreground" @click="applyDateFilter(undefined)">
                                            <X class="size-3.5" />Clear date
                                        </Button>
                                    </div>
                                </PopoverContent>
                            </Popover>

                            <Badge v-if="hasActiveFilters" variant="outline" class="ml-auto gap-1.5 text-xs">
                                <Filter class="size-3" />{{ activeFilterCount }} active
                            </Badge>
                        </div>
                    </CardHeader>

                    <div v-if="dispatches.data.length > 0" class="border-y bg-muted/20">
                        <div class="grid grid-cols-1 divide-y sm:grid-cols-2 sm:divide-x sm:divide-y-0 xl:grid-cols-4 xl:divide-x xl:divide-y-0">
                            <div class="space-y-3 p-4">
                                <div class="flex items-center gap-1.5">
                                    <Users class="size-3.5 text-muted-foreground" />
                                    <p class="text-xs font-medium text-muted-foreground">Passengers</p>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div class="rounded-lg border bg-background p-3 text-center">
                                        <p class="text-xs text-muted-foreground">Total</p>
                                        <p class="mt-0.5 text-xl font-bold tabular-nums">{{ totalPax.toLocaleString() }}</p>
                                    </div>
                                    <div class="rounded-lg border bg-background p-3 text-center">
                                        <p class="text-xs text-muted-foreground">Average</p>
                                        <p class="mt-0.5 text-xl font-bold tabular-nums">{{ avgPax }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-3 p-4">
                                <div class="flex items-center gap-1.5">
                                    <ClipboardList class="size-3.5 text-muted-foreground" />
                                    <p class="text-xs font-medium text-muted-foreground">Status Breakdown</p>
                                </div>

                                <div v-if="statusBreakdown.length" class="flex items-center gap-4">
                                    <div class="shrink-0">
                                        <ChartContainer :config="donutChartConfig" class="size-[80px]">
                                            <VisSingleContainer>
                                                <VisDonut
                                                    :data="donutData"
                                                    :value="(d: { value: number }) => d.value"
                                                    :color="donutColorFn"
                                                    :arc-width="14"
                                                    :pad-angle="0.02"
                                                    :corner-radius="2"
                                                />
                                            </VisSingleContainer>
                                        </ChartContainer>
                                    </div>

                                    <div class="min-w-0 flex-1 space-y-1.5">
                                        <div
                                            v-for="(item, i) in statusBreakdown"
                                            :key="item.status"
                                            class="flex items-center gap-2"
                                        >
                                            <span
                                                class="size-2 shrink-0 rounded-full"
                                                :style="{ backgroundColor: `var(--chart-${(i % 5) + 1})` }"
                                            />
                                            <span class="min-w-0 flex-1 truncate text-xs">{{ prettyStatus(item.status) }}</span>
                                            <span class="shrink-0 text-xs font-semibold tabular-nums">{{ item.count }}</span>
                                            <span class="w-8 shrink-0 text-right text-[10px] text-muted-foreground tabular-nums">{{ item.pct }}%</span>
                                        </div>
                                    </div>
                                </div>

                                <p v-else class="text-xs text-muted-foreground">No data</p>
                            </div>

                            <div class="space-y-3 p-4">
                                <div class="flex items-center gap-1.5">
                                    <Navigation class="size-3.5 text-muted-foreground" />
                                    <p class="text-xs font-medium text-muted-foreground">Top Routes</p>
                                </div>
                                <div class="space-y-2.5">
                                    <div v-for="entry in routeSummary" :key="entry.label" class="space-y-1">
                                        <div class="flex items-baseline justify-between gap-2">
                                            <span class="min-w-0 flex-1 truncate text-xs font-medium">{{ entry.label }}</span>
                                            <span class="shrink-0 text-xs tabular-nums text-muted-foreground">
                                                {{ entry.count }}x · {{ entry.pax }} pax
                                            </span>
                                        </div>
                                        <Progress :model-value="barPct(entry.count, routeSummary)" class="h-1.5" />
                                    </div>
                                    <p v-if="!routeSummary.length" class="text-xs text-muted-foreground">No route data</p>
                                </div>
                            </div>

                            <div class="space-y-3 p-4">
                                <div class="flex items-center gap-1.5">
                                    <MapPin class="size-3.5 text-muted-foreground" />
                                    <p class="text-xs font-medium text-muted-foreground">Gates & Bays</p>
                                </div>

                                <div v-if="gateSummary.length" class="space-y-2.5">
                                    <p class="text-[10px] font-medium uppercase tracking-wider text-muted-foreground/70">By Gate</p>
                                    <div v-for="entry in gateSummary" :key="entry.label" class="space-y-1">
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="flex min-w-0 items-center gap-1.5">
                                                <MapPinned class="size-3 shrink-0 text-muted-foreground" />
                                                <span class="truncate text-xs font-medium">{{ entry.label }}</span>
                                            </div>
                                            <span class="shrink-0 text-xs tabular-nums text-muted-foreground">{{ entry.count }}</span>
                                        </div>
                                        <Progress :model-value="barPct(entry.count, gateSummary)" class="h-1.5" />
                                    </div>
                                </div>

                                <div v-if="baySummary.some(b => b.label !== 'No Bay')" class="space-y-1.5">
                                    <p class="text-[10px] font-medium uppercase tracking-wider text-muted-foreground/70">By Bay</p>
                                    <div class="flex flex-wrap gap-1.5">
                                        <div
                                            v-for="entry in baySummary.filter(b => b.label !== 'No Bay')"
                                            :key="entry.label"
                                            class="flex items-center gap-1 rounded-md border bg-background px-2 py-1 text-xs"
                                        >
                                            <span class="font-medium">{{ entry.label }}</span>
                                            <Separator orientation="vertical" class="h-3" />
                                            <span class="tabular-nums text-muted-foreground">{{ entry.count }}</span>
                                        </div>
                                    </div>
                                </div>

                                <p v-if="!gateSummary.length && !baySummary.some(b => b.label !== 'No Bay')" class="text-xs text-muted-foreground">
                                    No gate or bay data
                                </p>
                            </div>
                        </div>
                    </div>

                    <CardContent class="hidden p-0 lg:block">
                        <div class="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow class="bg-muted/30 hover:bg-muted/30">
                                        <TableHead class="pl-5">Vehicle</TableHead>
                                        <TableHead>Route</TableHead>
                                        <TableHead>Gate / Bay</TableHead>
                                        <TableHead>Personnel</TableHead>
                                        <TableHead>Timeline</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead class="pr-5">Remarks</TableHead>
                                    </TableRow>
                                </TableHeader>

                                <TableBody>
                                    <TableRow
                                        v-for="dispatch in dispatches.data"
                                        :key="dispatch.id"
                                        class="align-top hover:bg-muted/20"
                                    >
                                        <TableCell class="py-3 pl-5">
                                            <div class="flex items-start gap-2">
                                                <div class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-md bg-muted">
                                                    <Bus class="size-3.5 text-muted-foreground" />
                                                </div>
                                                <div>
                                                    <p class="text-sm font-medium">{{ dispatch.plate_number || dispatch.vehicle?.plate_number || '—' }}</p>
                                                    <p class="text-xs text-muted-foreground">{{ dispatch.vehicle?.vehicle_type || '—' }}</p>
                                                    <p v-if="dispatch.vehicle?.make_model" class="text-xs text-muted-foreground">{{ dispatch.vehicle.make_model }}</p>
                                                    <Badge v-if="dispatch.pax_count !== null" variant="secondary" class="mt-1 gap-1 text-xs">
                                                        <Users class="size-3" />{{ dispatch.pax_count }} pax
                                                    </Badge>
                                                </div>
                                            </div>
                                        </TableCell>

                                        <TableCell class="max-w-52 py-3">
                                            <p class="text-sm font-medium">{{ dispatch.vehicle?.route?.route_name || '—' }}</p>
                                            <p v-if="dispatch.vehicle?.route" class="mt-0.5 flex items-center gap-0.5 text-xs text-muted-foreground">
                                                <span class="truncate">{{ dispatch.vehicle.route.origin_name || '?' }}</span>
                                                <ChevronRight class="size-3 shrink-0 opacity-40" />
                                                <span class="truncate">{{ dispatch.vehicle.route.destination_name || '?' }}</span>
                                            </p>
                                            <Button v-if="hasMap(dispatch)" variant="outline" size="sm" class="mt-1.5 h-6 gap-1 px-2 text-xs" @click="openMap(dispatch)">
                                                <MapIcon class="size-3" />View map
                                            </Button>
                                        </TableCell>

                                        <TableCell class="py-3">
                                            <div class="flex items-center gap-1.5 text-sm">
                                                <MapPinned class="size-3.5 shrink-0 text-muted-foreground" />
                                                <span>{{ dispatch.gate?.gate_name || '—' }}</span>
                                            </div>
                                            <p v-if="dispatch.bay_number" class="mt-0.5 pl-5 text-xs text-muted-foreground">Bay {{ dispatch.bay_number }}</p>
                                        </TableCell>

                                        <TableCell class="py-3">
                                            <div class="space-y-2">
                                                <div>
                                                    <p class="text-xs text-muted-foreground">Dispatcher</p>
                                                    <p class="text-sm">{{ dispatch.dispatcher?.name || '—' }}</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-muted-foreground">Driver</p>
                                                    <p class="text-sm">{{ dispatch.driver?.name || '—' }}</p>
                                                </div>
                                            </div>
                                        </TableCell>

                                        <TableCell class="min-w-44 py-3">
                                            <div class="space-y-1.5">
                                                <div v-if="dispatch.dispatched_at" class="flex items-start gap-1.5">
                                                    <Clock class="mt-0.5 size-3 shrink-0 text-muted-foreground" />
                                                    <div>
                                                        <p class="text-[10px] text-muted-foreground">Dispatched</p>
                                                        <p class="text-xs">{{ dispatch.dispatched_at }}</p>
                                                    </div>
                                                </div>
                                                <div v-if="dispatch.arrived_at" class="flex items-start gap-1.5">
                                                    <LogIn class="mt-0.5 size-3 shrink-0 text-amber-500" />
                                                    <div>
                                                        <p class="text-[10px] text-muted-foreground">Arrived</p>
                                                        <p class="text-xs">{{ dispatch.arrived_at }}</p>
                                                    </div>
                                                </div>
                                                <div v-if="dispatch.departed_at" class="flex items-start gap-1.5">
                                                    <LogOut class="mt-0.5 size-3 shrink-0 text-emerald-500" />
                                                    <div>
                                                        <p class="text-[10px] text-muted-foreground">Departed</p>
                                                        <p class="text-xs">{{ dispatch.departed_at }}</p>
                                                    </div>
                                                </div>
                                                <p v-if="!dispatch.dispatched_at && !dispatch.arrived_at && !dispatch.departed_at" class="text-xs text-muted-foreground/50">
                                                    No timestamps
                                                </p>
                                            </div>
                                        </TableCell>

                                        <TableCell class="py-3">
                                            <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium" :class="statusColor(dispatch.status)">
                                                {{ prettyStatus(dispatch.status) }}
                                            </span>
                                        </TableCell>

                                        <TableCell class="max-w-48 py-3 pr-5">
                                            <p v-if="dispatch.remarks" class="line-clamp-3 text-xs text-muted-foreground">{{ dispatch.remarks }}</p>
                                            <span v-else class="text-xs text-muted-foreground/40">—</span>
                                        </TableCell>
                                    </TableRow>

                                    <TableRow v-if="dispatches.data.length === 0">
                                        <TableCell colspan="7" class="py-16 text-center">
                                            <div class="flex flex-col items-center gap-3">
                                                <div class="rounded-xl border-2 border-dashed bg-muted/30 p-4">
                                                    <ClipboardList class="size-7 text-muted-foreground/50" />
                                                </div>
                                                <div>
                                                    <p class="text-sm font-medium">No records found</p>
                                                    <p class="text-xs text-muted-foreground">{{ hasActiveFilters ? 'Try adjusting your search or filters.' : 'No dispatch records for this company yet.' }}</p>
                                                </div>
                                                <Button v-if="hasActiveFilters" variant="outline" size="sm" @click="clearAllFilters">Clear filters</Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <Separator />
                        <div class="p-4">
                            <InertiaPagination :links="dispatches.links" />
                        </div>
                    </CardContent>

                    <CardContent class="space-y-3 p-4 lg:hidden">
                        <template v-if="dispatches.data.length > 0">
                            <div v-for="dispatch in dispatches.data" :key="dispatch.id" class="overflow-hidden rounded-xl border bg-background">
                                <div class="flex items-center justify-between gap-3 border-b bg-muted/30 px-3 py-2">
                                    <div class="flex items-center gap-2">
                                        <Bus class="size-3.5 text-muted-foreground" />
                                        <span class="text-sm font-semibold">{{ dispatch.plate_number || dispatch.vehicle?.plate_number || '—' }}</span>
                                        <span class="text-xs text-muted-foreground">{{ dispatch.vehicle?.vehicle_type || '' }}</span>
                                    </div>
                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium" :class="statusColor(dispatch.status)">
                                        {{ prettyStatus(dispatch.status) }}
                                    </span>
                                </div>

                                <div class="space-y-3 p-3">
                                    <div>
                                        <p class="text-xs text-muted-foreground">Route</p>
                                        <p class="text-sm font-medium">{{ dispatch.vehicle?.route?.route_name || '—' }}</p>
                                        <p v-if="dispatch.vehicle?.route" class="flex items-center gap-0.5 text-xs text-muted-foreground">
                                            {{ dispatch.vehicle.route.origin_name || '?' }}
                                            <ChevronRight class="size-3 opacity-40" />
                                            {{ dispatch.vehicle.route.destination_name || '?' }}
                                        </p>
                                        <Button v-if="hasMap(dispatch)" variant="outline" size="sm" class="mt-1 h-6 gap-1 px-2 text-xs" @click="openMap(dispatch)">
                                            <MapIcon class="size-3" />View map
                                        </Button>
                                    </div>

                                    <Separator />

                                    <div class="grid grid-cols-3 gap-3 text-sm">
                                        <div>
                                            <p class="text-xs text-muted-foreground">Gate</p>
                                            <p class="font-medium">{{ dispatch.gate?.gate_name || '—' }}</p>
                                            <p class="text-xs text-muted-foreground">{{ dispatch.bay_number ? `Bay ${dispatch.bay_number}` : '—' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-muted-foreground">PAX</p>
                                            <p class="text-lg font-bold tabular-nums">{{ dispatch.pax_count ?? '—' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-muted-foreground">Driver</p>
                                            <p class="font-medium">{{ dispatch.driver?.name || '—' }}</p>
                                        </div>
                                    </div>

                                    <Separator />

                                    <div class="flex flex-wrap gap-x-4 gap-y-1.5">
                                        <div v-if="dispatch.dispatched_at" class="flex items-start gap-1.5">
                                            <Clock class="mt-0.5 size-3 shrink-0 text-muted-foreground" />
                                            <div>
                                                <p class="text-[10px] text-muted-foreground">Dispatched</p>
                                                <p class="text-xs font-medium">{{ dispatch.dispatched_at }}</p>
                                            </div>
                                        </div>
                                        <div v-if="dispatch.arrived_at" class="flex items-start gap-1.5">
                                            <LogIn class="mt-0.5 size-3 shrink-0 text-amber-500" />
                                            <div>
                                                <p class="text-[10px] text-muted-foreground">Arrived</p>
                                                <p class="text-xs font-medium">{{ dispatch.arrived_at }}</p>
                                            </div>
                                        </div>
                                        <div v-if="dispatch.departed_at" class="flex items-start gap-1.5">
                                            <LogOut class="mt-0.5 size-3 shrink-0 text-emerald-500" />
                                            <div>
                                                <p class="text-[10px] text-muted-foreground">Departed</p>
                                                <p class="text-xs font-medium">{{ dispatch.departed_at }}</p>
                                            </div>
                                        </div>
                                        <p v-if="!dispatch.dispatched_at && !dispatch.arrived_at && !dispatch.departed_at" class="text-xs text-muted-foreground/50">
                                            No timestamps
                                        </p>
                                    </div>

                                    <div v-if="dispatch.remarks">
                                        <p class="text-xs text-muted-foreground">Remarks</p>
                                        <p class="text-xs">{{ dispatch.remarks }}</p>
                                    </div>
                                </div>
                            </div>

                            <InertiaPagination :links="dispatches.links" />
                        </template>

                        <div v-else class="flex flex-col items-center gap-3 py-14 text-center">
                            <div class="rounded-xl border-2 border-dashed bg-muted/30 p-5">
                                <Search class="size-7 text-muted-foreground/50" />
                            </div>
                            <div>
                                <p class="text-sm font-medium">No records found</p>
                                <p class="text-xs text-muted-foreground">{{ hasActiveFilters ? 'Try adjusting your search or filters.' : 'No dispatch records for this company yet.' }}</p>
                            </div>
                            <Button v-if="hasActiveFilters" variant="outline" size="sm" @click="clearAllFilters">Clear filters</Button>
                        </div>
                    </CardContent>
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
