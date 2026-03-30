<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'

import CompanyLogo from '@/components/company/CompanyLogo.vue'
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
            return 'border-transparent bg-green-100 text-green-700'
        case 'pending':
        case 'for_verification':
            return 'border-transparent bg-amber-100 text-amber-700'
        case 'departed':
            return 'border-transparent bg-blue-100 text-blue-700'
        case 'invalid':
        case 'expired':
        case 'inactive':
        case 'rejected':
            return 'border-transparent bg-red-100 text-red-700'
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
        title: 'Dispatches Today View',
        value: props.stats.total_dispatches,
        meta: `${props.stats.pending_dispatches} pending`,
        icon: Truck,
    },
    {
        title: 'Fleet Readiness',
        value: `${props.stats.fleet_readiness_rate}%`,
        meta: `${props.stats.active_vehicles} active vehicles`,
        icon: Bus,
    },
    {
        title: 'Route Coverage',
        value: `${props.stats.route_coverage_rate}%`,
        meta: `${props.stats.assigned_vehicles} assigned vehicles`,
        icon: RouteIcon,
    },
    {
        title: 'Needs Attention',
        value: props.stats.attention_count,
        meta: 'open operational items',
        icon: AlertTriangle,
    },
])

const hasAlerts = computed(() => props.stats.attention_count > 0)

const documentChartData = computed(() => {
    const segments = [
        {
            key: 'verified',
            label: 'Verified',
            value: props.stats.verified_documents,
            color: '#22c55e',
        },
        {
            key: 'pending',
            label: 'Pending',
            value: props.stats.pending_documents,
            color: '#f59e0b',
        },
        {
            key: 'invalid',
            label: 'Invalid',
            value: props.stats.invalid_documents,
            color: '#ef4444',
        },
        {
            key: 'expired',
            label: 'Expired',
            value: props.stats.expired_documents,
            color: '#f97316',
        },
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

    return {
        total,
        radius,
        segments: chartSegments,
    }
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
                            <CompanyLogo
                                :src="company.logo_url"
                                :alt="company.company_name"
                                :initials="companyInitials"
                                class="h-16 w-16 rounded-2xl"
                                text-class="select-none text-lg font-semibold"
                            />

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
                                <Link href="/company/routes">
                                    <RouteIcon class="mr-2 h-4 w-4" />
                                    Routes
                                </Link>
                            </Button>

                            <Button as-child>
                                <Link href="/company/documents">
                                    <FileText class="mr-2 h-4 w-4" />
                                    Documents
                                </Link>
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <Card v-for="item in primaryKpis" :key="item.title">
                    <CardContent class="flex items-center justify-between p-5">
                        <div class="space-y-1">
                            <p class="text-sm text-muted-foreground">{{ item.title }}</p>
                            <p class="text-3xl font-semibold tracking-tight">{{ item.value }}</p>
                            <p class="text-xs text-muted-foreground">{{ item.meta }}</p>
                        </div>

                        <div class="rounded-xl border p-3">
                            <component :is="item.icon" class="h-5 w-5 text-muted-foreground" />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card v-if="hasAlerts" class="border-amber-200">
                <CardContent class="flex flex-col gap-4 p-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-start gap-3">
                        <div class="rounded-xl bg-amber-100 p-2 text-amber-700">
                            <AlertTriangle class="h-5 w-5" />
                        </div>

                        <div class="space-y-1">
                            <p class="font-medium">Operational attention needed</p>
                            <p class="text-sm text-muted-foreground">
                                {{ stats.pending_dispatches }} pending dispatches,
                                {{ stats.for_verification_vehicles }} vehicles for verification,
                                {{ stats.unassigned_vehicles }} unassigned vehicles,
                                and {{ stats.invalid_documents + stats.expired_documents }} invalid or expired documents.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Button as-child variant="outline">
                            <Link href="/company/dispatches">Open dispatches</Link>
                        </Button>
                        <Button as-child variant="outline">
                            <Link href="/company/vehicles">Open vehicles</Link>
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <div class="grid gap-4 xl:grid-cols-3">
                <Card class="xl:col-span-2">
                    <CardHeader>
                        <CardTitle>Operations Performance</CardTitle>
                        <CardDescription>
                            Key progress indicators for dispatches, vehicles, and routes.
                        </CardDescription>
                    </CardHeader>

                    <CardContent class="space-y-6">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <span>Dispatch completion</span>
                                <span class="font-medium">{{ stats.dispatch_completion_rate }}%</span>
                            </div>
                            <Progress :model-value="stats.dispatch_completion_rate" />
                            <p class="text-xs text-muted-foreground">
                                {{ stats.arrived_dispatches }} arrived out of {{ stats.total_dispatches }} total dispatches.
                            </p>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <span>Fleet readiness</span>
                                <span class="font-medium">{{ stats.fleet_readiness_rate }}%</span>
                            </div>
                            <Progress :model-value="stats.fleet_readiness_rate" />
                            <p class="text-xs text-muted-foreground">
                                {{ stats.active_vehicles }} active out of {{ stats.total_vehicles }} company vehicles.
                            </p>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <span>Route coverage</span>
                                <span class="font-medium">{{ stats.route_coverage_rate }}%</span>
                            </div>
                            <Progress :model-value="stats.route_coverage_rate" />
                            <p class="text-xs text-muted-foreground">
                                {{ stats.assigned_vehicles }} vehicles already assigned to routes.
                            </p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <div class="rounded-lg border p-4">
                                <p class="text-xs text-muted-foreground">Pending Dispatches</p>
                                <p class="mt-2 text-2xl font-semibold">{{ stats.pending_dispatches }}</p>
                            </div>

                            <div class="rounded-lg border p-4">
                                <p class="text-xs text-muted-foreground">Active Vehicles</p>
                                <p class="mt-2 text-2xl font-semibold">{{ stats.active_vehicles }}</p>
                            </div>

                            <div class="rounded-lg border p-4">
                                <p class="text-xs text-muted-foreground">Active Routes</p>
                                <p class="mt-2 text-2xl font-semibold">{{ stats.active_routes }}</p>
                            </div>

                            <div class="rounded-lg border p-4">
                                <p class="text-xs text-muted-foreground">Unassigned Vehicles</p>
                                <p class="mt-2 text-2xl font-semibold">{{ stats.unassigned_vehicles }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Compliance Snapshot</CardTitle>
                        <CardDescription>
                            Company document status distribution.
                        </CardDescription>
                    </CardHeader>

                    <CardContent class="space-y-5">
                        <div class="flex justify-center">
                            <div class="relative h-40 w-40">
                                <svg viewBox="0 0 120 120" class="h-full w-full -rotate-90">
                                    <circle
                                        cx="60"
                                        cy="60"
                                        r="36"
                                        fill="transparent"
                                        stroke="hsl(var(--muted))"
                                        stroke-width="14"
                                    />

                                    <circle
                                        v-for="segment in documentChartData.segments"
                                        :key="segment.key"
                                        cx="60"
                                        cy="60"
                                        :r="documentChartData.radius"
                                        fill="transparent"
                                        :stroke="segment.color"
                                        stroke-width="14"
                                        :stroke-dasharray="segment.dasharray"
                                        :stroke-dashoffset="segment.dashoffset"
                                    />
                                </svg>

                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <span class="text-2xl font-semibold">{{ stats.compliance_rate }}%</span>
                                    <span class="text-xs text-muted-foreground">verified</span>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div
                                v-for="segment in documentChartData.segments"
                                :key="segment.key"
                                class="flex items-center justify-between rounded-lg border px-3 py-2"
                            >
                                <div class="flex items-center gap-2">
                                    <span
                                        class="h-2.5 w-2.5 rounded-full"
                                        :style="{ backgroundColor: segment.color }"
                                    />
                                    <span class="text-sm">{{ segment.label }}</span>
                                </div>

                                <span class="text-sm font-medium">{{ segment.value }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 xl:grid-cols-3">
                <Card class="xl:col-span-2">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0">
                        <div>
                            <CardTitle>Recent Dispatch Activity</CardTitle>
                            <CardDescription>
                                Latest movement and status changes in your company.
                            </CardDescription>
                        </div>

                        <Button as-child variant="ghost" size="sm">
                            <Link href="/company/dispatches">
                                View all
                                <ArrowRight class="ml-2 h-4 w-4" />
                            </Link>
                        </Button>
                    </CardHeader>

                    <CardContent>
                        <div v-if="recentDispatches.length" class="space-y-3">
                            <div
                                v-for="dispatch in recentDispatches"
                                :key="dispatch.id"
                                class="rounded-xl border p-4"
                            >
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="space-y-2">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="font-medium">
                                                {{ dispatch.plate_number || dispatch.vehicle?.plate_number || 'No plate number' }}
                                            </p>

                                            <Badge :class="statusBadgeClass(dispatch.status)">
                                                {{ humanize(dispatch.status) }}
                                            </Badge>
                                        </div>

                                        <div class="flex flex-wrap gap-3 text-sm text-muted-foreground">
                                            <span>{{ dispatch.vehicle?.vehicle_type ?? 'Vehicle' }}</span>
                                            <span v-if="dispatch.vehicle?.body_number">
                                                Body No. {{ dispatch.vehicle.body_number }}
                                            </span>
                                            <span v-if="dispatch.route?.route_name">
                                                {{ dispatch.route.route_name }}
                                            </span>
                                            <span v-if="dispatch.route?.gate_name">
                                                {{ dispatch.route.gate_name }}
                                            </span>
                                        </div>

                                        <div
                                            v-if="dispatch.route?.origin_name || dispatch.route?.destination_name"
                                            class="text-sm text-muted-foreground"
                                        >
                                            {{ dispatch.route?.origin_name ?? '—' }}
                                            →
                                            {{ dispatch.route?.destination_name ?? '—' }}
                                        </div>
                                    </div>

                                    <p class="text-sm text-muted-foreground">
                                        {{ formatDateTime(dispatch.dispatched_at) }}
                                    </p>
                                </div>

                                <div class="mt-4 grid gap-2 sm:grid-cols-3">
                                    <div class="rounded-lg bg-muted/40 px-3 py-2 text-sm">
                                        <span class="text-muted-foreground">Bay:</span>
                                        <span class="ml-1 font-medium">{{ dispatch.bay_number ?? '—' }}</span>
                                    </div>

                                    <div class="rounded-lg bg-muted/40 px-3 py-2 text-sm">
                                        <span class="text-muted-foreground">Passengers:</span>
                                        <span class="ml-1 font-medium">{{ dispatch.pax_count ?? 0 }}</span>
                                    </div>

                                    <div class="rounded-lg bg-muted/40 px-3 py-2 text-sm">
                                        <span class="text-muted-foreground">Arrived:</span>
                                        <span class="ml-1 font-medium">{{ formatDateTime(dispatch.arrived_at) }}</span>
                                    </div>
                                </div>

                                <div v-if="dispatch.remarks" class="mt-3 text-sm text-muted-foreground">
                                    Remarks: {{ dispatch.remarks }}
                                </div>
                            </div>
                        </div>

                        <div v-else class="rounded-xl border border-dashed p-8 text-center">
                            <Truck class="mx-auto h-8 w-8 text-muted-foreground" />
                            <p class="mt-3 font-medium">No dispatches yet</p>
                            <p class="text-sm text-muted-foreground">
                                Dispatch activity will appear here once records are created.
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <div class="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Fleet Status</CardTitle>
                            <CardDescription>
                                Vehicle readiness and assignment status.
                            </CardDescription>
                        </CardHeader>

                        <CardContent class="space-y-3">
                            <div class="flex items-center justify-between rounded-lg border px-3 py-2">
                                <span class="text-sm">Active</span>
                                <span class="font-medium text-green-700">{{ stats.active_vehicles }}</span>
                            </div>

                            <div class="flex items-center justify-between rounded-lg border px-3 py-2">
                                <span class="text-sm">Inactive</span>
                                <span class="font-medium text-red-700">{{ stats.inactive_vehicles }}</span>
                            </div>

                            <div class="flex items-center justify-between rounded-lg border px-3 py-2">
                                <span class="text-sm">For Verification</span>
                                <span class="font-medium text-amber-700">{{ stats.for_verification_vehicles }}</span>
                            </div>

                            <div class="flex items-center justify-between rounded-lg border px-3 py-2">
                                <span class="text-sm">Assigned to Route</span>
                                <span class="font-medium">{{ stats.assigned_vehicles }}</span>
                            </div>

                            <div class="flex items-center justify-between rounded-lg border px-3 py-2">
                                <span class="text-sm">Unassigned</span>
                                <span class="font-medium">{{ stats.unassigned_vehicles }}</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0">
                            <div>
                                <CardTitle>Route Summary</CardTitle>
                                <CardDescription>
                                    Routes with the most assigned vehicles.
                                </CardDescription>
                            </div>

                            <Button as-child variant="ghost" size="sm">
                                <Link href="/company/routes">
                                    View all
                                    <ArrowRight class="ml-2 h-4 w-4" />
                                </Link>
                            </Button>
                        </CardHeader>

                        <CardContent>
                            <div v-if="topRoutes.length" class="space-y-3">
                                <div
                                    v-for="route in topRoutes"
                                    :key="`${route.route_id}-${route.route_name}`"
                                    class="rounded-xl border p-3"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="space-y-1">
                                            <p class="font-medium">{{ route.route_name ?? 'Unnamed route' }}</p>
                                            <p class="text-xs text-muted-foreground">
                                                {{ route.origin_name ?? '—' }} → {{ route.destination_name ?? '—' }}
                                            </p>
                                            <p class="text-xs text-muted-foreground">
                                                {{ route.gate_name ?? 'No gate assigned' }}
                                            </p>
                                        </div>

                                        <Badge :class="statusBadgeClass(route.status)">
                                            {{ humanize(route.status) }}
                                        </Badge>
                                    </div>

                                    <div class="mt-3 flex items-center justify-between text-sm">
                                        <span class="text-muted-foreground">Assigned vehicles</span>
                                        <span class="font-medium">{{ route.vehicles_count }}</span>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="rounded-xl border border-dashed p-6 text-center">
                                <RouteIcon class="mx-auto h-7 w-7 text-muted-foreground" />
                                <p class="mt-2 text-sm font-medium">No routes assigned yet</p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Quick Access</CardTitle>
                            <CardDescription>
                                Operator shortcuts.
                            </CardDescription>
                        </CardHeader>

                        <CardContent class="space-y-2">
                            <Button as-child variant="outline" class="w-full justify-start">
                                <Link href="/company/dispatches">
                                    <Truck class="mr-2 h-4 w-4" />
                                    Dispatch Records
                                </Link>
                            </Button>

                            <Button as-child variant="outline" class="w-full justify-start">
                                <Link href="/company/vehicles">
                                    <Bus class="mr-2 h-4 w-4" />
                                    Vehicles
                                </Link>
                            </Button>

                            <Button as-child variant="outline" class="w-full justify-start">
                                <Link href="/company/routes">
                                    <RouteIcon class="mr-2 h-4 w-4" />
                                    Routes
                                </Link>
                            </Button>

                            <Button as-child variant="outline" class="w-full justify-start">
                                <Link href="/company/documents">
                                    <FileText class="mr-2 h-4 w-4" />
                                    Company Documents
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </ExternalLayout>
</template>
