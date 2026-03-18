<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'

import ExternalLayout from '@/layouts/ExternalLayout.vue'
import DispatchController from '@/actions/App/Http/Controllers/DispatchController'
import RouteSelectorWithPreview from '@/components/routes/RouteSelectorWithPreview.vue'

import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'

import {
    ArrowLeft,
    Bus,
    CalendarCheck2,
    CalendarClock,
    CalendarX2,
    CheckCircle2,
    Clock3,
    FileText,
    Fingerprint,
    LogIn,
    LogOut,
    MapPin,
    Route as RouteIcon,
    UserRound,
    Users,
} from 'lucide-vue-next'

type GateItem = {
    id: number
    gate_name: string
    bays?: number | null
}

type RouteStop = {
    id: number
    route_id: number
    stop_name: string
    stop_order: number
    stop_type: string
    address?: string | null
    latitude?: number | null
    longitude?: number | null
}

type RouteItem = {
    id: number
    gate_id?: number | null
    route_name: string
    origin_name?: string | null
    destination_name?: string | null
    route_geometry?: unknown
    stops?: RouteStop[]
    gate?: { id: number; gate_name: string } | null
}

type Dispatch = {
    id: number
    plate_number: string
    pax_count: number
    bay_number: string | number | null
    remarks?: string | null
    status: string
    arrived_at_formatted?: string | null
    departed_at_formatted?: string | null
    dispatched_at_formatted?: string | null
    vehicle?: {
        id: number
        route_id?: number | null
        plate_number: string
        body_number?: string | null
        vehicle_type?: string | null
        make_model?: string | null
        status?: string | null
        route?: {
            id: number
            route_name: string
            origin_name?: string | null
            destination_name?: string | null
            status?: string | null
        } | null
    } | null
    dispatcher?: {
        id: number
        name: string
        username?: string | null
        email?: string | null
    } | null
    driver?: {
        id: number
        name: string
        username?: string | null
        email?: string | null
    } | null
    gate?: {
        id: number
        gate_name: string
        bays?: number | null
    } | null
}

const props = defineProps<{
    dispatch: Dispatch
    routes: RouteItem[]
    gates: GateItem[]
    mapConfig: {
        mapboxToken?: string | null
        defaultCenter: { lng: number; lat: number }
        defaultZoom: number
    }
}>()

type BadgeVariant = 'default' | 'secondary' | 'outline' | 'destructive'

function statusVariant(status?: string | null): BadgeVariant {
    switch (status) {
        case 'departed': return 'outline'
        case 'arrived':  return 'default'
        case 'pending':  return 'secondary'
        default:         return 'secondary'
    }
}

function statusLabel(status?: string | null) {
    if (!status) return 'Unknown'
    return status.replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}

const isDeparted = computed(() => props.dispatch.status === 'departed')
const isArrived  = computed(() => props.dispatch.status === 'arrived')

// Timeline steps
const timeline = computed(() => [
    {
        label: 'Dispatched',
        time: props.dispatch.dispatched_at_formatted,
        icon: CalendarClock,
        done: !!props.dispatch.dispatched_at_formatted,
    },
    {
        label: 'Arrived',
        time: props.dispatch.arrived_at_formatted,
        icon: LogIn,
        done: !!props.dispatch.arrived_at_formatted,
    },
    {
        label: 'Departed',
        time: props.dispatch.departed_at_formatted,
        icon: LogOut,
        done: !!props.dispatch.departed_at_formatted,
    },
])
</script>

<template>
    <ExternalLayout>
        <Head :title="`Dispatch — ${dispatch.plate_number}`" />

        <div class="space-y-6 p-4 md:p-6">

            <!-- ── Page Header ── -->
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div class="space-y-1.5">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-semibold tracking-tight">
                            {{ dispatch.plate_number }}
                        </h1>
                        <Badge :variant="statusVariant(dispatch.status)" class="text-sm">
                            <span
                                class="mr-1.5 inline-block h-1.5 w-1.5 rounded-full"
                                :class="{
                                    'bg-emerald-500': isArrived,
                                    'bg-slate-400':   isDeparted,
                                    'bg-amber-400':   dispatch.status === 'pending',
                                }"
                            />
                            {{ statusLabel(dispatch.status) }}
                        </Badge>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        Dispatch record
                        <span class="font-mono text-xs text-foreground">#{{ dispatch.id }}</span>
                        &nbsp;·&nbsp;
                        {{ dispatch.gate?.gate_name ?? '—' }}, Bay {{ dispatch.bay_number ?? '—' }}
                    </p>
                </div>

                <Button variant="outline" size="sm" as-child>
                    <Link :href="DispatchController.index().url">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back to Dispatches
                    </Link>
                </Button>
            </div>

            <!-- ── Timeline Strip ── -->
            <div class="grid grid-cols-3 gap-3">
                <div
                    v-for="(step, i) in timeline"
                    :key="i"
                    class="relative flex flex-col gap-1.5 rounded-xl border p-4 transition-colors"
                    :class="step.done ? 'bg-card shadow-sm' : 'bg-muted/30 opacity-60'"
                >
                    <div class="flex items-center justify-between">
                        <component
                            :is="step.icon"
                            class="h-4 w-4"
                            :class="step.done ? 'text-foreground' : 'text-muted-foreground'"
                        />
                        <CheckCircle2
                            v-if="step.done"
                            class="h-3.5 w-3.5 text-emerald-500"
                        />
                        <div v-else class="h-3.5 w-3.5 rounded-full border-2 border-muted-foreground/30" />
                    </div>
                    <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                        {{ step.label }}
                    </p>
                    <p class="text-sm font-semibold leading-snug">
                        {{ step.time ?? '—' }}
                    </p>

                    <!-- connector line -->
                    <div
                        v-if="i < timeline.length - 1"
                        class="absolute -right-1.5 top-1/2 z-10 hidden h-px w-3 -translate-y-1/2 bg-border md:block"
                    />
                </div>
            </div>

            <!-- ── Main Grid ── -->
            <div class="grid gap-4 lg:grid-cols-3">

                <!-- Left column: Vehicle + Dispatch info -->
                <div class="space-y-4 lg:col-span-1">

                    <!-- Vehicle -->
                    <Card class="shadow-sm">
                        <CardHeader class="pb-3">
                            <CardTitle class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                <Bus class="h-4 w-4" />
                                Vehicle
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-0 divide-y">
                            <div class="flex items-center justify-between py-2.5 text-sm">
                                <span class="text-muted-foreground">Plate</span>
                                <span class="font-semibold">{{ dispatch.plate_number }}</span>
                            </div>
                            <div class="flex items-center justify-between py-2.5 text-sm">
                                <span class="text-muted-foreground">Body No.</span>
                                <span class="font-medium">{{ dispatch.vehicle?.body_number || '—' }}</span>
                            </div>
                            <div class="flex items-center justify-between py-2.5 text-sm">
                                <span class="text-muted-foreground">Type</span>
                                <span class="font-medium">{{ dispatch.vehicle?.vehicle_type || '—' }}</span>
                            </div>
                            <div class="flex items-center justify-between py-2.5 text-sm">
                                <span class="text-muted-foreground">Make / Model</span>
                                <span class="font-medium">{{ dispatch.vehicle?.make_model || '—' }}</span>
                            </div>
                            <div class="flex items-center justify-between py-2.5 text-sm">
                                <span class="text-muted-foreground">Status</span>
                                <span class="font-medium capitalize">{{ dispatch.vehicle?.status || '—' }}</span>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Dispatch Specifics -->
                    <Card class="shadow-sm">
                        <CardHeader class="pb-3">
                            <CardTitle class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                <MapPin class="h-4 w-4" />
                                Dispatch Info
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-0 divide-y">
                            <div class="flex items-center justify-between py-2.5 text-sm">
                                <span class="text-muted-foreground">Gate</span>
                                <span class="font-medium">{{ dispatch.gate?.gate_name || '—' }}</span>
                            </div>
                            <div class="flex items-center justify-between py-2.5 text-sm">
                                <span class="text-muted-foreground">Bay</span>
                                <span class="font-medium">{{ dispatch.bay_number || '—' }}</span>
                            </div>
                            <div class="flex items-center justify-between py-2.5 text-sm">
                                <span class="text-muted-foreground">Passengers</span>
                                <span class="inline-flex items-center gap-1.5 font-semibold">
                                    <Users class="h-3.5 w-3.5 text-muted-foreground" />
                                    {{ dispatch.pax_count }}
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Personnel -->
                    <Card class="shadow-sm">
                        <CardHeader class="pb-3">
                            <CardTitle class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                <Users class="h-4 w-4" />
                                Personnel
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <!-- Driver -->
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border bg-muted">
                                    <UserRound class="h-4 w-4 text-muted-foreground" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs text-muted-foreground">Driver</p>
                                    <p class="truncate text-sm font-semibold">
                                        {{ dispatch.driver?.name || 'Unassigned' }}
                                    </p>
                                    <p v-if="dispatch.driver?.username" class="text-xs text-muted-foreground">
                                        @{{ dispatch.driver.username }}
                                    </p>
                                </div>
                            </div>

                            <Separator />

                            <!-- Dispatcher -->
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border bg-muted">
                                    <Fingerprint class="h-4 w-4 text-muted-foreground" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs text-muted-foreground">Dispatcher</p>
                                    <p class="truncate text-sm font-semibold">
                                        {{ dispatch.dispatcher?.name || '—' }}
                                    </p>
                                    <p v-if="dispatch.dispatcher?.username" class="text-xs text-muted-foreground">
                                        @{{ dispatch.dispatcher.username }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Right column: Route + Map + Remarks -->
                <div class="space-y-4 lg:col-span-2">

                    <!-- Route summary -->
                    <Card class="shadow-sm">
                        <CardHeader class="pb-3">
                            <CardTitle class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                <RouteIcon class="h-4 w-4" />
                                Route
                            </CardTitle>
                            <CardDescription class="text-xs">
                                Route linked to this vehicle
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="dispatch.vehicle?.route" class="grid grid-cols-2 gap-4 text-sm md:grid-cols-4">
                                <div class="space-y-1">
                                    <p class="text-xs text-muted-foreground">Route Name</p>
                                    <p class="font-semibold">{{ dispatch.vehicle.route.route_name }}</p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-xs text-muted-foreground">Origin</p>
                                    <p class="font-medium">{{ dispatch.vehicle.route.origin_name || '—' }}</p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-xs text-muted-foreground">Destination</p>
                                    <p class="font-medium">{{ dispatch.vehicle.route.destination_name || '—' }}</p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-xs text-muted-foreground">Status</p>
                                    <p class="font-medium capitalize">{{ dispatch.vehicle.route.status || '—' }}</p>
                                </div>
                            </div>
                            <div v-else class="rounded-lg bg-muted/30 px-4 py-6 text-center text-sm text-muted-foreground">
                                No route assigned to this vehicle.
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Route map preview -->
                    <Card class="shadow-sm">
                        <CardHeader class="pb-3">
                            <CardTitle class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                <MapPin class="h-4 w-4" />
                                Route Map
                            </CardTitle>
                            <CardDescription class="text-xs">
                                Stops and geometry for the assigned route
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <RouteSelectorWithPreview
                                :model-value="String(dispatch.vehicle?.route_id ?? '')"
                                :routes="routes"
                                :gates="gates"
                                :map-config="mapConfig"
                                :readonly="true"
                            />
                        </CardContent>
                    </Card>

                    <!-- Remarks -->
                    <Card class="shadow-sm">
                        <CardHeader class="pb-3">
                            <CardTitle class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                <FileText class="h-4 w-4" />
                                Remarks
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div
                                class="min-h-[72px] rounded-lg border bg-muted/30 p-4 text-sm leading-relaxed"
                                :class="!dispatch.remarks ? 'text-muted-foreground italic' : 'text-foreground'"
                            >
                                {{ dispatch.remarks || 'No remarks recorded for this dispatch.' }}
                            </div>
                        </CardContent>
                    </Card>

                </div>
            </div>
        </div>
    </ExternalLayout>
</template>
