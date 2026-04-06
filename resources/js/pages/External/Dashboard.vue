<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'

import ExternalLayout from '@/layouts/ExternalLayout.vue'

import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Progress } from '@/components/ui/progress'
import { Separator } from '@/components/ui/separator'

import {
    AlertTriangle,
    ArrowRight,
    Bus,
    CheckCircle2,
    FileText,
    Route as RouteIcon,
    ShieldCheck,
    Truck,
    Unplug,
    TrendingUp,
    MapPin,
    Activity,
    Users,       
    Building2,
} from 'lucide-vue-next'

const props = defineProps<{
    company: {
        id: number
        company_name: string
        company_code?: string | null
        company_email?: string | null
        company_phone?: string | null
        status: string
        business_type?: string | null
        authorized_representative_name?: string | null
        logo_url?: string | null
    }
    user: {
        id: number
        name: string
        username: string
        email: string
    }
    stats: {
        total_dispatches: number
        pending_dispatches: number
        arrived_dispatches: number
        departed_dispatches: number

        total_documents: number
        verified_documents: number
        pending_documents: number
        invalid_documents: number
        expired_documents: number

        total_vehicles: number
        active_vehicles: number
        inactive_vehicles: number
        for_verification_vehicles: number
        assigned_vehicles: number
        unassigned_vehicles: number

        total_routes: number
        active_routes: number
        inactive_routes: number

        compliance_rate: number
        dispatch_completion_rate: number
        fleet_readiness_rate: number
        route_coverage_rate: number

        attention_count: number
    }
    recentDispatches: Array<{
        id: number
        plate_number: string | null
        status: string
        bay_number: string | null
        pax_count: number | null
        remarks: string | null
        dispatched_at: string | null
        arrived_at: string | null
        departed_at: string | null
        vehicle: {
            id: number
            plate_number: string | null
            body_number: string | null
            vehicle_type: string | null
        } | null
        gate: {
            id: number
            gate_name: string
        } | null
        route: {
            id: number
            route_name: string | null
            origin_name: string | null
            destination_name: string | null
            status: string | null
            gate_name: string | null
        } | null
    }>
    recentDocuments: Array<{
        id: number
        doc_type: string
        status: string
        issued_at: string | null
        expires_at: string | null
        updated_at: string | null
    }>
    topRoutes: Array<{
        route_id: number | null
        route_name: string | null
        origin_name: string | null
        destination_name: string | null
        status: string | null
        gate_name: string | null
        vehicles_count: number
    }>
}>()

function humanize(text?: string | null) {
    if (!text) return '—'
    return text
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase())
}

function formatDate(value?: string | null) {
    if (!value) return '—'
    return new Date(value).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    })
}

function formatDateTime(value?: string | null) {
    if (!value) return '—'
    return new Date(value).toLocaleString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    })
}

function statusBadgeClass(status?: string | null) {
    switch (status) {
        case 'verified':
        case 'active':
        case 'arrived':
            return 'border-transparent bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'
        case 'pending':
        case 'for_verification':
            return 'border-transparent bg-amber-50 text-amber-700 ring-1 ring-amber-200'
        case 'departed':
            return 'border-transparent bg-blue-50 text-blue-700 ring-1 ring-blue-200'
        case 'invalid':
        case 'expired':
        case 'inactive':
        case 'rejected':
            return 'border-transparent bg-red-50 text-red-700 ring-1 ring-red-200'
        default:
            return 'border-transparent bg-muted text-muted-foreground'
    }
}

const greeting = computed(() => {
    const hour = new Date().getHours()
    if (hour < 12) return 'Good morning'
    if (hour < 17) return 'Good afternoon'
    return 'Good evening'
})

const companyInitials = computed(() =>
    (props.company.company_code ?? props.company.company_name)
        .slice(0, 2)
        .toUpperCase(),
)

const primaryKpis = computed(() => [
    {
        title: 'Dispatches Today',
        value: props.stats.total_dispatches,
        meta: `${props.stats.pending_dispatches} pending`,
        icon: Truck,
        accent: 'blue',
    },
    {
        title: 'Fleet Readiness',
        value: `${props.stats.fleet_readiness_rate}%`,
        meta: `${props.stats.active_vehicles} active vehicles`,
        icon: Bus,
        accent: 'blue',
    },
    {
        title: 'Route Coverage',
        value: `${props.stats.route_coverage_rate}%`,
        meta: `${props.stats.assigned_vehicles} assigned`,
        icon: RouteIcon,
        accent: 'blue',
    },
    {
        title: 'Needs Attention',
        value: props.stats.attention_count,
        meta: 'open items',
        icon: AlertTriangle,
        accent: props.stats.attention_count > 0 ? 'red' : 'blue',
    },
])

const hasAlerts = computed(() => props.stats.attention_count > 0)

const documentChartData = computed(() => {
    const segments = [
        { key: 'verified', label: 'Verified', value: props.stats.verified_documents, color: '#10b981' },
        { key: 'pending', label: 'Pending', value: props.stats.pending_documents, color: '#f59e0b' },
        { key: 'invalid', label: 'Invalid', value: props.stats.invalid_documents, color: '#ef4444' },
        { key: 'expired', label: 'Expired', value: props.stats.expired_documents, color: '#f97316' },
    ]

    const total = segments.reduce((sum, item) => sum + item.value, 0)
    const radius = 36
    const circumference = 2 * Math.PI * radius
    let offset = 0

    const chartSegments = segments.map((segment) => {
        const length = total > 0 ? (segment.value / total) * circumference : 0
        const item = {
            ...segment,
            dasharray: `${length} ${circumference - length}`,
            dashoffset: -offset,
        }
        offset += length
        return item
    })

    return { total, radius, segments: chartSegments }
})
</script>

<template>
    <Head :title="`Dashboard — ${company.company_name}`" />

    <ExternalLayout :company="company" :user="user">
        <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
            <Card>
                <CardContent class="p-6">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-start gap-4">
                            <div class="h-16 w-16 overflow-hidden rounded-2xl border bg-muted">
                                <img
                                    v-if="company.logo_url"
                                    :src="company.logo_url"
                                    :alt="company.company_name"
                                    class="h-full w-full object-cover"
                                />
                                <div
                                    v-else
                                    class="flex h-full w-full items-center justify-center text-lg font-semibold"
                                >
                                    {{ companyInitials }}
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div>
                                    <p class="text-sm text-muted-foreground">
                                        {{ greeting }}, {{ user.name.split(' ')[0] }}
                                    </p>
                                    <h1 class="text-2xl font-semibold tracking-tight">
                                        {{ company.company_name }}
                                    </h1>
                                    <p class="text-sm text-muted-foreground">
                                        Operator dashboard for dispatch, fleet, and route monitoring
                                    </p>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <Badge :class="statusBadgeClass(company.status)">
                                        {{ humanize(company.status) }}
                                    </Badge>
                                    <Badge variant="outline">
                                        {{ company.company_code ?? 'No company code' }}
                                    </Badge>
                                    <Badge variant="outline">
                                        {{ humanize(company.business_type) }}
                                    </Badge>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-2 sm:grid-cols-4">
                            <Button as-child variant="outline">
                                <Link href="/company/dispatches">
                                    <Truck class="mr-2 h-4 w-4" />
                                    Dispatches
                                </Link>
                            </Button>

                            <Button as-child variant="outline">
                                <Link href="/company/vehicles">
                                    <Bus class="mr-2 h-4 w-4" />
                                    Vehicles
                                </Link>
                            </Button>

                            <Button as-child variant="outline">
                                <Link href="/employee-users">
                                    <Users class="mr-2 h-4 w-4" />
                                    Employees
                                </Link>
                            </Button>

                            <Button as-child variant="blue">
                                <Link href="/company/profile">
                                    <Building2 class="mr-2 h-4 w-4" />
                                    Company Profile
                                </Link>
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- ── KPI Cards ── -->
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <Card
                    v-for="item in primaryKpis"
                    :key="item.title"
                    class="relative overflow-hidden border"
                    :class="item.accent === 'red' && hasAlerts ? 'border-red-200 bg-red-50/40' : ''"
                >
                    <CardContent class="p-5">
                        <div class="flex items-start justify-between">
                            <div class="space-y-1">
                                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                    {{ item.title }}
                                </p>
                                <p class="text-3xl font-bold tracking-tight"
                                    :class="item.accent === 'red' && hasAlerts ? 'text-red-600' : 'text-foreground'">
                                    {{ item.value }}
                                </p>
                                <p class="text-xs text-muted-foreground">{{ item.meta }}</p>
                            </div>

                            <div
                                class="rounded-lg p-2.5"
                                :class="item.accent === 'red' && hasAlerts
                                    ? 'bg-red-100 text-red-600'
                                    : 'bg-blue-50 text-blue-600'"
                            >
                                <component :is="item.icon" class="h-5 w-5" />
                            </div>
                        </div>
                    </CardContent>
                    <!-- Bottom accent line -->
                    <div
                        class="absolute bottom-0 left-0 right-0 h-0.5"
                        :class="item.accent === 'red' && hasAlerts ? 'bg-red-400' : 'bg-blue-500'"
                    />
                </Card>
            </div>

            <!-- ── Alert Banner ── -->
            <div
                v-if="hasAlerts"
                class="flex flex-col gap-4 rounded-xl border border-amber-200 bg-amber-50 p-4 lg:flex-row lg:items-center lg:justify-between"
            >
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 shrink-0 rounded-lg bg-amber-100 p-2 text-amber-700">
                        <AlertTriangle class="h-4 w-4" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-amber-900">Operational attention required</p>
                        <p class="mt-0.5 text-sm text-amber-700">
                            {{ stats.pending_dispatches }} pending dispatch{{ stats.pending_dispatches !== 1 ? 'es' : '' }},
                            {{ stats.for_verification_vehicles }} vehicle{{ stats.for_verification_vehicles !== 1 ? 's' : '' }} for verification,
                            {{ stats.unassigned_vehicles }} unassigned,
                            and {{ stats.invalid_documents + stats.expired_documents }} document issue{{ (stats.invalid_documents + stats.expired_documents) !== 1 ? 's' : '' }}.
                        </p>
                    </div>
                </div>
                <div class="flex shrink-0 flex-wrap gap-2">
                    <Button as-child size="sm" variant="outline" class="border-amber-300 bg-white text-amber-800 hover:bg-amber-50">
                        <Link href="/company/dispatches">Dispatches</Link>
                    </Button>
                    <Button as-child size="sm" variant="outline" class="border-amber-300 bg-white text-amber-800 hover:bg-amber-50">
                        <Link href="/company/vehicles">Vehicles</Link>
                    </Button>
                </div>
            </div>

            <!-- ── Main Grid: Operations + Compliance ── -->
            <div class="grid gap-5 xl:grid-cols-3">

                <!-- Operations Performance -->
                <Card class="xl:col-span-2">
                    <CardHeader class="pb-2">
                        <div class="flex items-center gap-2">
                            <div class="rounded-md bg-blue-50 p-1.5 text-blue-600">
                                <Activity class="h-4 w-4" />
                            </div>
                            <div>
                                <CardTitle class="text-base">Operations Performance</CardTitle>
                                <CardDescription class="text-xs">
                                    Key indicators for dispatches, fleet, and routes
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent class="space-y-5">
                        <!-- Progress metrics -->
                        <div class="space-y-4">
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="font-medium">Dispatch Completion</span>
                                    <span class="font-semibold text-blue-700">{{ stats.dispatch_completion_rate }}%</span>
                                </div>
                                <div class="h-2 w-full overflow-hidden rounded-full bg-blue-100">
                                    <div
                                        class="h-full rounded-full bg-blue-600 transition-all"
                                        :style="{ width: `${stats.dispatch_completion_rate}%` }"
                                    />
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    {{ stats.arrived_dispatches }} arrived of {{ stats.total_dispatches }} total
                                </p>
                            </div>

                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="font-medium">Fleet Readiness</span>
                                    <span class="font-semibold"
                                        :class="stats.fleet_readiness_rate >= 70 ? 'text-emerald-700' : 'text-red-700'">
                                        {{ stats.fleet_readiness_rate }}%
                                    </span>
                                </div>
                                <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                    <div
                                        class="h-full rounded-full transition-all"
                                        :class="stats.fleet_readiness_rate >= 70 ? 'bg-emerald-500' : 'bg-red-500'"
                                        :style="{ width: `${stats.fleet_readiness_rate}%` }"
                                    />
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    {{ stats.active_vehicles }} active of {{ stats.total_vehicles }} total vehicles
                                </p>
                            </div>

                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="font-medium">Route Coverage</span>
                                    <span class="font-semibold"
                                        :class="stats.route_coverage_rate >= 70 ? 'text-emerald-700' : 'text-amber-700'">
                                        {{ stats.route_coverage_rate }}%
                                    </span>
                                </div>
                                <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                    <div
                                        class="h-full rounded-full transition-all"
                                        :class="stats.route_coverage_rate >= 70 ? 'bg-emerald-500' : 'bg-amber-500'"
                                        :style="{ width: `${stats.route_coverage_rate}%` }"
                                    />
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    {{ stats.assigned_vehicles }} vehicles assigned to routes
                                </p>
                            </div>
                        </div>

                        <Separator />

                        <!-- Summary counters -->
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div class="rounded-lg border border-slate-100 bg-slate-50 px-4 py-3 text-center">
                                <p class="text-2xl font-bold text-blue-700">{{ stats.pending_dispatches }}</p>
                                <p class="mt-0.5 text-xs text-muted-foreground">Pending</p>
                            </div>
                            <div class="rounded-lg border border-slate-100 bg-slate-50 px-4 py-3 text-center">
                                <p class="text-2xl font-bold text-emerald-700">{{ stats.active_vehicles }}</p>
                                <p class="mt-0.5 text-xs text-muted-foreground">Active Vehicles</p>
                            </div>
                            <div class="rounded-lg border border-slate-100 bg-slate-50 px-4 py-3 text-center">
                                <p class="text-2xl font-bold text-blue-700">{{ stats.active_routes }}</p>
                                <p class="mt-0.5 text-xs text-muted-foreground">Active Routes</p>
                            </div>
                            <div class="rounded-lg border border-slate-100 bg-slate-50 px-4 py-3 text-center">
                                <p class="text-2xl font-bold"
                                    :class="stats.unassigned_vehicles > 0 ? 'text-red-600' : 'text-foreground'">
                                    {{ stats.unassigned_vehicles }}
                                </p>
                                <p class="mt-0.5 text-xs text-muted-foreground">Unassigned</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Compliance Snapshot -->
                <Card>
                    <CardHeader class="pb-2">
                        <div class="flex items-center gap-2">
                            <div class="rounded-md bg-blue-50 p-1.5 text-blue-600">
                                <ShieldCheck class="h-4 w-4" />
                            </div>
                            <div>
                                <CardTitle class="text-base">Compliance</CardTitle>
                                <CardDescription class="text-xs">Document status breakdown</CardDescription>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent class="space-y-4">
                        <!-- Donut chart -->
                        <div class="flex justify-center py-2">
                            <div class="relative h-36 w-36">
                                <svg viewBox="0 0 120 120" class="h-full w-full -rotate-90">
                                    <circle
                                        cx="60" cy="60" r="36"
                                        fill="transparent"
                                        stroke="hsl(var(--muted))"
                                        stroke-width="14"
                                    />
                                    <circle
                                        v-for="segment in documentChartData.segments"
                                        :key="segment.key"
                                        cx="60" cy="60"
                                        :r="documentChartData.radius"
                                        fill="transparent"
                                        :stroke="segment.color"
                                        stroke-width="14"
                                        :stroke-dasharray="segment.dasharray"
                                        :stroke-dashoffset="segment.dashoffset"
                                    />
                                </svg>
                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <span class="text-2xl font-bold">{{ stats.compliance_rate }}%</span>
                                    <span class="text-xs text-muted-foreground">verified</span>
                                </div>
                            </div>
                        </div>

                        <!-- Legend -->
                        <div class="space-y-2">
                            <div
                                v-for="segment in documentChartData.segments"
                                :key="segment.key"
                                class="flex items-center justify-between"
                            >
                                <div class="flex items-center gap-2">
                                    <span
                                        class="h-2.5 w-2.5 shrink-0 rounded-full"
                                        :style="{ backgroundColor: segment.color }"
                                    />
                                    <span class="text-sm text-muted-foreground">{{ segment.label }}</span>
                                </div>
                                <span class="text-sm font-semibold">{{ segment.value }}</span>
                            </div>
                        </div>

                        <Separator />

                        <div class="text-center">
                            <p class="text-xs text-muted-foreground">Total documents</p>
                            <p class="text-lg font-bold">{{ documentChartData.total }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- ── Bottom Grid: Dispatches + Sidebar ── -->
            <div class="grid gap-5 xl:grid-cols-3">

                <!-- Recent Dispatch Activity -->
                <Card class="xl:col-span-2">
                    <CardHeader class="pb-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="rounded-md bg-blue-50 p-1.5 text-blue-600">
                                    <Truck class="h-4 w-4" />
                                </div>
                                <div>
                                    <CardTitle class="text-base">Recent Dispatch Activity</CardTitle>
                                    <CardDescription class="text-xs">
                                        Latest movement and status changes
                                    </CardDescription>
                                </div>
                            </div>
                            <Button as-child variant="ghost" size="sm" class="text-blue-600 hover:text-blue-700 hover:bg-blue-50">
                                <Link href="/company/dispatches">
                                    View all
                                    <ArrowRight class="ml-1.5 h-3.5 w-3.5" />
                                </Link>
                            </Button>
                        </div>
                    </CardHeader>

                    <CardContent>
                        <div v-if="recentDispatches.length" class="space-y-3">
                            <div
                                v-for="dispatch in recentDispatches"
                                :key="dispatch.id"
                                class="rounded-lg border border-slate-100 bg-slate-50/50 p-4 transition-colors hover:border-blue-100 hover:bg-blue-50/20"
                            >
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="space-y-1.5">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="font-semibold">
                                                {{ dispatch.plate_number || dispatch.vehicle?.plate_number || 'No plate' }}
                                            </p>
                                            <Badge :class="statusBadgeClass(dispatch.status)" class="text-xs">
                                                {{ humanize(dispatch.status) }}
                                            </Badge>
                                        </div>

                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                                            <span v-if="dispatch.vehicle?.vehicle_type">
                                                {{ dispatch.vehicle.vehicle_type }}
                                            </span>
                                            <span v-if="dispatch.vehicle?.body_number">
                                                Body #{{ dispatch.vehicle.body_number }}
                                            </span>
                                            <span v-if="dispatch.route?.gate_name" class="flex items-center gap-1">
                                                <MapPin class="h-3 w-3" />
                                                {{ dispatch.route.gate_name }}
                                            </span>
                                        </div>

                                        <div
                                            v-if="dispatch.route?.origin_name || dispatch.route?.destination_name"
                                            class="flex items-center gap-1 text-xs font-medium text-slate-600"
                                        >
                                            <span>{{ dispatch.route?.origin_name ?? '—' }}</span>
                                            <ArrowRight class="h-3 w-3 shrink-0 text-slate-400" />
                                            <span>{{ dispatch.route?.destination_name ?? '—' }}</span>
                                        </div>
                                    </div>

                                    <p class="shrink-0 text-xs text-muted-foreground">
                                        {{ formatDateTime(dispatch.dispatched_at) }}
                                    </p>
                                </div>

                                <!-- Meta row -->
                                <div class="mt-3 flex flex-wrap gap-3 border-t border-slate-100 pt-3">
                                    <div class="flex items-center gap-1.5 text-xs">
                                        <span class="text-muted-foreground">Bay:</span>
                                        <span class="font-medium">{{ dispatch.bay_number ?? '—' }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-xs">
                                        <span class="text-muted-foreground">Pax:</span>
                                        <span class="font-medium">{{ dispatch.pax_count ?? 0 }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-xs">
                                        <span class="text-muted-foreground">Arrived:</span>
                                        <span class="font-medium">{{ formatDateTime(dispatch.arrived_at) }}</span>
                                    </div>
                                </div>

                                <p v-if="dispatch.remarks" class="mt-2 text-xs italic text-muted-foreground">
                                    "{{ dispatch.remarks }}"
                                </p>
                            </div>
                        </div>

                        <div v-else class="rounded-xl border border-dashed p-10 text-center">
                            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-blue-50">
                                <Truck class="h-6 w-6 text-blue-400" />
                            </div>
                            <p class="font-medium text-sm">No dispatches yet</p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Dispatch activity will appear here once records are created.
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Sidebar -->
                <div class="space-y-5">

                    <!-- Fleet Status -->
                    <Card>
                        <CardHeader class="pb-2">
                            <div class="flex items-center gap-2">
                                <div class="rounded-md bg-blue-50 p-1.5 text-blue-600">
                                    <Bus class="h-4 w-4" />
                                </div>
                                <div>
                                    <CardTitle class="text-base">Fleet Status</CardTitle>
                                    <CardDescription class="text-xs">Vehicle readiness overview</CardDescription>
                                </div>
                            </div>
                        </CardHeader>

                        <CardContent class="space-y-2">
                            <div class="flex items-center justify-between rounded-lg px-3 py-2.5 text-sm hover:bg-slate-50">
                                <div class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-emerald-500" />
                                    <span>Active</span>
                                </div>
                                <span class="font-semibold text-emerald-700">{{ stats.active_vehicles }}</span>
                            </div>
                            <div class="flex items-center justify-between rounded-lg px-3 py-2.5 text-sm hover:bg-slate-50">
                                <div class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-red-500" />
                                    <span>Inactive</span>
                                </div>
                                <span class="font-semibold text-red-600">{{ stats.inactive_vehicles }}</span>
                            </div>
                            <div class="flex items-center justify-between rounded-lg px-3 py-2.5 text-sm hover:bg-slate-50">
                                <div class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-amber-500" />
                                    <span>For Verification</span>
                                </div>
                                <span class="font-semibold text-amber-700">{{ stats.for_verification_vehicles }}</span>
                            </div>

                            <Separator class="my-1" />

                            <div class="flex items-center justify-between rounded-lg px-3 py-2.5 text-sm hover:bg-slate-50">
                                <div class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-blue-500" />
                                    <span>Assigned to Route</span>
                                </div>
                                <span class="font-semibold text-blue-700">{{ stats.assigned_vehicles }}</span>
                            </div>
                            <div class="flex items-center justify-between rounded-lg px-3 py-2.5 text-sm hover:bg-slate-50">
                                <div class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-slate-300" />
                                    <span>Unassigned</span>
                                </div>
                                <span class="font-semibold" :class="stats.unassigned_vehicles > 0 ? 'text-red-600' : ''">
                                    {{ stats.unassigned_vehicles }}
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Route Summary -->
                    <Card>
                        <CardHeader class="pb-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="rounded-md bg-blue-50 p-1.5 text-blue-600">
                                        <RouteIcon class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <CardTitle class="text-base">Top Routes</CardTitle>
                                        <CardDescription class="text-xs">Most assigned vehicles</CardDescription>
                                    </div>
                                </div>
                                <Button as-child variant="ghost" size="sm" class="h-7 px-2 text-xs text-blue-600 hover:bg-blue-50">
                                    <Link href="/company/routes">
                                        All
                                        <ArrowRight class="ml-1 h-3 w-3" />
                                    </Link>
                                </Button>
                            </div>
                        </CardHeader>

                        <CardContent>
                            <div v-if="topRoutes.length" class="space-y-2">
                                <div
                                    v-for="route in topRoutes"
                                    :key="`${route.route_id}-${route.route_name}`"
                                    class="rounded-lg border border-slate-100 bg-slate-50/50 p-3 transition-colors hover:border-blue-100 hover:bg-blue-50/20"
                                >
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0 flex-1 space-y-0.5">
                                            <p class="truncate text-sm font-medium">
                                                {{ route.route_name ?? 'Unnamed route' }}
                                            </p>
                                            <p class="truncate text-xs text-muted-foreground">
                                                {{ route.origin_name ?? '—' }} → {{ route.destination_name ?? '—' }}
                                            </p>
                                            <p v-if="route.gate_name" class="flex items-center gap-1 text-xs text-muted-foreground">
                                                <MapPin class="h-2.5 w-2.5" />
                                                {{ route.gate_name }}
                                            </p>
                                        </div>
                                        <Badge :class="statusBadgeClass(route.status)" class="shrink-0 text-xs">
                                            {{ humanize(route.status) }}
                                        </Badge>
                                    </div>

                                    <div class="mt-2 flex items-center justify-between border-t border-slate-100 pt-2">
                                        <span class="text-xs text-muted-foreground">Assigned vehicles</span>
                                        <span class="text-sm font-bold text-blue-700">{{ route.vehicles_count }}</span>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="rounded-xl border border-dashed p-6 text-center">
                                <RouteIcon class="mx-auto mb-2 h-7 w-7 text-muted-foreground" />
                                <p class="text-sm font-medium">No routes assigned</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

        </div>
    </ExternalLayout>
</template>