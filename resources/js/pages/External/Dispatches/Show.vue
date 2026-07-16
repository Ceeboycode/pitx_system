<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'

import ExternalLayout from '@/layouts/ExternalLayout.vue'
import DispatchController from '@/actions/App/Http/Controllers/DispatchController'
import RouteSelectorWithPreview from '@/components/routes/RouteSelectorWithPreview.vue'

import { Button } from '@/components/ui/button'

import {
    ArrowLeft,
    Building2,
    Bus,
    CalendarClock,
    CheckCircle2,
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


function humanize(value?: string | null) {
    if (!value) return '—'
    return value.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}

function dispatchStatusClass(status?: string | null) {
    if (status === 'arrived')  return 'bg-emerald-100 text-emerald-700 border-emerald-200'
    if (status === 'departed') return 'bg-slate-100 text-slate-500 border-slate-200'
    if (status === 'pending')  return 'bg-amber-100 text-amber-700 border-amber-200'
    return 'bg-slate-100 text-slate-500 border-0'
}

function dispatchStatusDot(status?: string | null) {
    if (status === 'arrived')  return 'bg-emerald-500'
    if (status === 'departed') return 'bg-slate-400'
    if (status === 'pending')  return 'bg-amber-400'
    return 'bg-slate-400'
}

function vehicleStatusClass(status?: string | null) {
    if (status === 'active')   return 'bg-emerald-100 text-emerald-700 border-emerald-200'
    if (status === 'inactive') return 'bg-rose-100 text-rose-600 border-rose-200'
    if (status === 'pending')  return 'bg-amber-100 text-amber-700 border-amber-200'
    return 'bg-slate-100 text-slate-500 border-0'
}

function vehicleStatusDot(status?: string | null) {
    if (status === 'active')   return 'bg-emerald-500'
    if (status === 'inactive') return 'bg-rose-500'
    if (status === 'pending')  return 'bg-amber-500'
    return 'bg-slate-400'
}

function initials(name?: string | null) {
    if (!name) return '?'
    return name
        .split(' ')
        .slice(0, 2)
        .map((p) => p.charAt(0).toUpperCase())
        .join('')
}


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
    <Head :title="`Dispatch — ${dispatch.plate_number}`" />

    <ExternalLayout>
        <div class="min-h-screen bg-slate-50/60">
            <div class="mx-auto max-w-7xl space-y-6 p-4 md:p-8">

                
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-slate-400">
                            <Building2 class="h-3.5 w-3.5" />
                            Dispatches
                            <span class="text-slate-300">·</span>
                            <span class="font-mono">{{ dispatch.plate_number }}</span>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                                {{ dispatch.plate_number }}
                            </h1>
                            
                            <span
                                :class="[
                                    'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium',
                                    dispatchStatusClass(dispatch.status),
                                ]"
                            >
                                <span :class="['h-1.5 w-1.5 rounded-full', dispatchStatusDot(dispatch.status)]" />
                                {{ humanize(dispatch.status) }}
                            </span>
                        </div>
                        <p class="text-sm text-slate-500">
                            Dispatch record
                            <span class="font-mono text-xs text-slate-700">#{{ dispatch.id }}</span>
                            &nbsp;·&nbsp;
                            {{ dispatch.gate?.gate_name ?? '—' }}, Bay {{ dispatch.bay_number ?? '—' }}
                        </p>
                    </div>

                    <Button
                        as-child
                        variant="outline"
                        class="shrink-0 self-start rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100 hover:text-slate-800"
                    >
                        <Link :href="DispatchController.index().url">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Back to Dispatches
                        </Link>
                    </Button>
                </div>

                
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="relative grid grid-cols-3">
                        
                        <div class="absolute left-[33.33%] right-[33.33%] top-8 h-px bg-slate-200" />

                        <div
                            v-for="(step, i) in timeline"
                            :key="i"
                            class="flex flex-col items-center gap-2 px-4 py-5 text-center"
                        >
                            
                            <div
                                :class="[
                                    'relative z-10 flex h-10 w-10 items-center justify-center rounded-full border-2 transition-colors',
                                    step.done
                                        ? 'border-emerald-500 bg-emerald-50'
                                        : 'border-slate-200 bg-slate-50',
                                ]"
                            >
                                <component
                                    :is="step.icon"
                                    class="h-4 w-4"
                                    :class="step.done ? 'text-emerald-600' : 'text-slate-400'"
                                />
                                
                                <CheckCircle2
                                    v-if="step.done"
                                    class="absolute -right-1 -top-1 h-4 w-4 rounded-full bg-white text-emerald-500"
                                />
                            </div>

                            <p
                                class="text-[11px] font-semibold uppercase tracking-widest"
                                :class="step.done ? 'text-slate-600' : 'text-slate-400'"
                            >
                                {{ step.label }}
                            </p>
                            <p
                                class="text-sm font-semibold tabular-nums"
                                :class="step.done ? 'text-slate-900' : 'text-slate-400'"
                            >
                                {{ step.time ?? '—' }}
                            </p>
                        </div>
                    </div>
                </div>

                
                <div class="grid gap-6 lg:grid-cols-3">

                    
                    <div class="space-y-6 lg:col-span-1">

                        
                        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                            <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-4">
                                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-100">
                                    <Bus class="h-3.5 w-3.5 text-blue-700" />
                                </div>
                                <h2 class="text-sm font-semibold text-slate-800">Vehicle</h2>
                            </div>

                            <div class="divide-y divide-slate-100 px-5">
                                <div class="flex items-center justify-between py-3">
                                    <span class="text-xs font-semibold uppercase tracking-widest text-slate-400">Plate</span>
                                    <span class="rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs font-semibold text-slate-700">
                                        {{ dispatch.plate_number }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between py-3">
                                    <span class="text-xs font-semibold uppercase tracking-widest text-slate-400">Body No.</span>
                                    <span class="text-sm font-medium text-slate-700">{{ dispatch.vehicle?.body_number || '—' }}</span>
                                </div>
                                <div class="flex items-center justify-between py-3">
                                    <span class="text-xs font-semibold uppercase tracking-widest text-slate-400">Type</span>
                                    <span class="text-sm font-medium text-slate-700">{{ dispatch.vehicle?.vehicle_type || '—' }}</span>
                                </div>
                                <div class="flex items-center justify-between py-3">
                                    <span class="text-xs font-semibold uppercase tracking-widest text-slate-400">Make / Model</span>
                                    <span class="text-sm font-medium text-slate-700">{{ dispatch.vehicle?.make_model || '—' }}</span>
                                </div>
                                <div class="flex items-center justify-between py-3">
                                    <span class="text-xs font-semibold uppercase tracking-widest text-slate-400">Status</span>
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium',
                                            vehicleStatusClass(dispatch.vehicle?.status),
                                        ]"
                                    >
                                        <span :class="['h-1.5 w-1.5 rounded-full', vehicleStatusDot(dispatch.vehicle?.status)]" />
                                        {{ humanize(dispatch.vehicle?.status) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        
                        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                            <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-4">
                                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-sky-100">
                                    <MapPin class="h-3.5 w-3.5 text-sky-700" />
                                </div>
                                <h2 class="text-sm font-semibold text-slate-800">Dispatch Info</h2>
                            </div>

                            <div class="divide-y divide-slate-100 px-5">
                                <div class="flex items-center justify-between py-3">
                                    <span class="text-xs font-semibold uppercase tracking-widest text-slate-400">Gate</span>
                                    <span class="text-sm font-medium text-slate-700">{{ dispatch.gate?.gate_name || '—' }}</span>
                                </div>
                                <div class="flex items-center justify-between py-3">
                                    <span class="text-xs font-semibold uppercase tracking-widest text-slate-400">Bay</span>
                                    <span class="text-sm font-medium text-slate-700">{{ dispatch.bay_number || '—' }}</span>
                                </div>
                                <div class="flex items-center justify-between py-3">
                                    <span class="text-xs font-semibold uppercase tracking-widest text-slate-400">Passengers</span>
                                    <div class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                        <Users class="h-3 w-3" />
                                        {{ dispatch.pax_count }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        
                        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                            <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-4">
                                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-violet-100">
                                    <Users class="h-3.5 w-3.5 text-violet-700" />
                                </div>
                                <h2 class="text-sm font-semibold text-slate-800">Personnel</h2>
                            </div>

                            <div class="divide-y divide-slate-100 px-5">
                                
                                <div class="flex items-center gap-3 py-4">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-sky-100 text-xs font-bold text-sky-700">
                                        <template v-if="dispatch.driver">
                                            {{ initials(dispatch.driver.name) }}
                                        </template>
                                        <UserRound v-else class="h-4 w-4 text-slate-400" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">Driver</p>
                                        <p
                                            class="truncate text-sm font-semibold"
                                            :class="dispatch.driver ? 'text-slate-800' : 'italic text-slate-400'"
                                        >
                                            {{ dispatch.driver?.name || 'Unassigned' }}
                                        </p>
                                        <p v-if="dispatch.driver?.username" class="text-xs text-slate-400">
                                            @{{ dispatch.driver.username }}
                                        </p>
                                    </div>
                                </div>

                                
                                <div class="flex items-center gap-3 py-4">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-violet-100 text-xs font-bold text-violet-700">
                                        <template v-if="dispatch.dispatcher">
                                            {{ initials(dispatch.dispatcher.name) }}
                                        </template>
                                        <Fingerprint v-else class="h-4 w-4 text-slate-400" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">Dispatcher</p>
                                        <p class="truncate text-sm font-semibold text-slate-800">
                                            {{ dispatch.dispatcher?.name || '—' }}
                                        </p>
                                        <p v-if="dispatch.dispatcher?.username" class="text-xs text-slate-400">
                                            @{{ dispatch.dispatcher.username }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    
                    <div class="space-y-6 lg:col-span-2">

                        
                        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                            <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-4">
                                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-100">
                                    <RouteIcon class="h-3.5 w-3.5 text-emerald-700" />
                                </div>
                                <div>
                                    <h2 class="text-sm font-semibold text-slate-800">Route</h2>
                                    <p class="text-xs text-slate-400">Route linked to this vehicle.</p>
                                </div>
                            </div>

                            <div class="p-5">
                                <div v-if="dispatch.vehicle?.route" class="grid grid-cols-2 gap-4 md:grid-cols-4">
                                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-3">
                                        <p class="mb-1 text-[11px] font-semibold uppercase tracking-widest text-slate-400">
                                            Route Name
                                        </p>
                                        <p class="text-sm font-semibold text-slate-800">
                                            {{ dispatch.vehicle.route.route_name }}
                                        </p>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-3">
                                        <p class="mb-1 text-[11px] font-semibold uppercase tracking-widest text-slate-400">
                                            Origin
                                        </p>
                                        <p class="text-sm font-medium text-slate-700">
                                            {{ dispatch.vehicle.route.origin_name || '—' }}
                                        </p>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-3">
                                        <p class="mb-1 text-[11px] font-semibold uppercase tracking-widest text-slate-400">
                                            Destination
                                        </p>
                                        <p class="text-sm font-medium text-slate-700">
                                            {{ dispatch.vehicle.route.destination_name || '—' }}
                                        </p>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-3">
                                        <p class="mb-1 text-[11px] font-semibold uppercase tracking-widest text-slate-400">
                                            Status
                                        </p>
                                        <span
                                            :class="[
                                                'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium',
                                                vehicleStatusClass(dispatch.vehicle.route.status),
                                            ]"
                                        >
                                            <span :class="['h-1.5 w-1.5 rounded-full', vehicleStatusDot(dispatch.vehicle.route.status)]" />
                                            {{ humanize(dispatch.vehicle.route.status) }}
                                        </span>
                                    </div>
                                </div>

                                <div
                                    v-else
                                    class="flex flex-col items-center gap-2 rounded-xl border border-slate-200 bg-slate-50/70 py-8 text-center"
                                >
                                    <RouteIcon class="h-6 w-6 text-slate-300" />
                                    <p class="text-sm font-medium text-slate-500">No route assigned to this vehicle.</p>
                                </div>
                            </div>
                        </div>

                        
                        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                            <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-4">
                                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100">
                                    <MapPin class="h-3.5 w-3.5 text-slate-600" />
                                </div>
                                <div>
                                    <h2 class="text-sm font-semibold text-slate-800">Route Map</h2>
                                    <p class="text-xs text-slate-400">Stops and geometry for the assigned route.</p>
                                </div>
                            </div>

                            <div class="p-5">
                                <RouteSelectorWithPreview
                                    :model-value="String(dispatch.vehicle?.route_id ?? '')"
                                    :routes="routes"
                                    :gates="gates"
                                    :map-config="mapConfig"
                                    :readonly="true"
                                />
                            </div>
                        </div>

                        
                        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                            <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-4">
                                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-100">
                                    <FileText class="h-3.5 w-3.5 text-amber-700" />
                                </div>
                                <h2 class="text-sm font-semibold text-slate-800">Remarks</h2>
                            </div>

                            <div class="p-5">
                                <div
                                    class="min-h-[72px] rounded-xl border border-slate-200 bg-slate-50/70 p-4 text-sm leading-relaxed"
                                    :class="dispatch.remarks ? 'text-slate-700' : 'italic text-slate-400'"
                                >
                                    {{ dispatch.remarks || 'No remarks recorded for this dispatch.' }}
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </ExternalLayout>
</template>