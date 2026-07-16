<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import mapboxgl from 'mapbox-gl'
import 'mapbox-gl/dist/mapbox-gl.css'

import InputError from '@/components/InputError.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { Separator } from '@/components/ui/separator'
import {
    Check,
    ChevronsUpDown,
    ListOrdered,
    MapPin,
    MapPinned,
    Navigation,
    Route as RouteIcon,
    Search,
    X,
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

const props = defineProps<{
    modelValue: string
    routes: RouteItem[]
    gates: GateItem[]
    error?: string | null
    mapConfig: {
        mapboxToken?: string | null
        defaultCenter: { lng: number; lat: number }
        defaultZoom: number
    }
    readonly?: boolean
}>()

const emit = defineEmits<{
    'update:modelValue': [value: string]
}>()

const selectedGateId = ref<'all' | string>('all')
const routeSearch = ref('')
const routeDropdownOpen = ref(false)
const mapDialogOpen = ref(false)
const stopsDialogOpen = ref(false)
const mapEl = ref<HTMLElement | null>(null)

let mapInstance: mapboxgl.Map | null = null

const selectedRoute = computed(() =>
    props.routes.find((r) => String(r.id) === String(props.modelValue)) ?? null,
)

const selectedGate = computed(() => {
    if (selectedGateId.value === 'all') return null
    return props.gates.find((g) => String(g.id) === String(selectedGateId.value)) ?? null
})

const filteredRoutes = computed(() => {
    const keyword = routeSearch.value.trim().toLowerCase()
    return props.routes.filter((route) => {
        const matchesGate =
            selectedGateId.value === 'all' ||
            String(route.gate_id ?? '') === String(selectedGateId.value)
        if (!matchesGate) return false
        if (!keyword) return true
        return [route.route_name, route.origin_name ?? '', route.destination_name ?? '', route.gate?.gate_name ?? '']
            .join(' ')
            .toLowerCase()
            .includes(keyword)
    })
})

const sortedStops = computed(() =>
    [...(selectedRoute.value?.stops ?? [])].sort((a, b) => a.stop_order - b.stop_order),
)

const routeSummary = computed(() => {
    if (!selectedRoute.value) return null
    return {
        name: selectedRoute.value.route_name,
        origin: selectedRoute.value.origin_name || '—',
        destination: selectedRoute.value.destination_name || '—',
        totalStops: sortedStops.value.length,
        gateName: selectedRoute.value.gate?.gate_name || '—',
    }
})

const routeInputDisplay = computed(() => {
    if (routeDropdownOpen.value && !props.readonly) return routeSearch.value
    return selectedRoute.value?.route_name ?? ''
})



function markerColor(type: string) {
    switch (type) {
        case 'origin':      return '#16a34a'
        case 'destination': return '#dc2626'
        case 'landmark':    return '#8b5cf6'
        default:            return '#f59e0b'
    }
}

function stopTypeDot(type: string) {
    switch (type) {
        case 'origin':      return 'bg-emerald-500'
        case 'destination': return 'bg-red-500'
        case 'landmark':    return 'bg-violet-500'
        default:            return 'bg-amber-400'
    }
}

function stopTypeLabel(type: string) {
    switch (type) {
        case 'origin':      return 'Origin'
        case 'destination': return 'Destination'
        case 'landmark':    return 'Landmark'
        default:            return 'Stop'
    }
}

function humanize(value?: string | null) {
    if (!value) return '—'
    return value.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}



function openRouteDropdown() {
    if (props.readonly) return
    routeDropdownOpen.value = true
    routeSearch.value = selectedRoute.value?.route_name ?? ''
}

function closeRouteDropdown() {
    routeDropdownOpen.value = false
    routeSearch.value = ''
}

function selectRoute(route: RouteItem) {
    if (props.readonly) return
    emit('update:modelValue', String(route.id))
    routeDropdownOpen.value = false
    routeSearch.value = ''
    if (route.gate_id) selectedGateId.value = String(route.gate_id)
}

function clearRouteSearch() {
    if (props.readonly) return
    routeSearch.value = ''
    routeDropdownOpen.value = true
}

function clearSelectedRoute() {
    if (props.readonly) return
    emit('update:modelValue', '')
    routeSearch.value = ''
    routeDropdownOpen.value = true
}

function onRouteInputBlur() {
    window.setTimeout(() => closeRouteDropdown(), 150)
}



function destroyMap() {
    mapInstance?.remove()
    mapInstance = null
}

function initMap() {
    if (!mapEl.value || !selectedRoute.value) return
    const token = props.mapConfig.mapboxToken
    if (!token) return

    mapboxgl.accessToken = token

    const validStops = sortedStops.value.filter(
        (s) => s.longitude != null && s.latitude != null,
    )

    const center: [number, number] = validStops.length
        ? [Number(validStops[0].longitude), Number(validStops[0].latitude)]
        : [props.mapConfig.defaultCenter.lng, props.mapConfig.defaultCenter.lat]

    mapInstance = new mapboxgl.Map({
        container: mapEl.value,
        style: 'mapbox://styles/mapbox/streets-v12',
        center,
        zoom: props.mapConfig.defaultZoom,
        interactive: true,
    })

    mapInstance.addControl(new mapboxgl.NavigationControl(), 'top-right')

    mapInstance.on('load', () => {
        if (!mapInstance) return

        const geom = selectedRoute.value?.route_geometry
        if (geom) {
            try {
                const geometry = typeof geom === 'string' ? JSON.parse(geom) : geom
                mapInstance.addSource('route-line', {
                    type: 'geojson',
                    data: { type: 'FeatureCollection', features: [{ type: 'Feature', properties: {}, geometry }] },
                })
                mapInstance.addLayer({
                    id: 'route-line-layer',
                    type: 'line',
                    source: 'route-line',
                    paint: { 'line-width': 5, 'line-color': '#2563eb' },
                })
            } catch {  }
        }

        validStops.forEach((stop) => {
            new mapboxgl.Marker({ color: markerColor(stop.stop_type) })
                .setLngLat([Number(stop.longitude), Number(stop.latitude)])
                .setPopup(
                    new mapboxgl.Popup({ offset: 20 }).setHTML(`
                        <div style="min-width:180px;font-family:system-ui">
                            <div style="font-weight:600;font-size:14px">${stop.stop_order}. ${stop.stop_name}</div>
                            <div style="font-size:12px;color:#6b7280;margin-top:4px">${stopTypeLabel(stop.stop_type)}</div>
                            ${stop.address ? `<div style="font-size:12px;margin-top:6px;color:#374151">${stop.address}</div>` : ''}
                        </div>
                    `),
                )
                .addTo(mapInstance)
        })

        if (validStops.length >= 2) {
            const bounds = new mapboxgl.LngLatBounds()
            validStops.forEach((s) => bounds.extend([Number(s.longitude), Number(s.latitude)]))
            mapInstance.fitBounds(bounds, { padding: 60, maxZoom: 14 })
        }
    })
}

watch(mapDialogOpen, async (open) => {
    if (open) { await nextTick(); initMap() }
    else destroyMap()
})

watch(selectedGateId, () => {
    if (!props.modelValue || props.readonly) return
    const stillExists = filteredRoutes.value.some((r) => String(r.id) === String(props.modelValue))
    if (!stillExists) emit('update:modelValue', '')
})

watch(() => props.modelValue, (value) => {
    if (!value) return
    const route = props.routes.find((r) => String(r.id) === String(value))
    if (route?.gate_id) selectedGateId.value = String(route.gate_id)
}, { immediate: true })

onBeforeUnmount(() => destroyMap())
</script>

<template>
    <div class="space-y-4">

        
        <div class="grid gap-4 md:grid-cols-[200px_1fr]">

            
            <div class="space-y-2">
                <Label for="gate_id" class="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                    Gate
                </Label>
                <Select v-model="selectedGateId" :disabled="readonly">
                    <SelectTrigger id="gate_id" class="w-full">
                        <SelectValue placeholder="All gates" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All gates</SelectItem>
                        <SelectItem v-for="gate in gates" :key="gate.id" :value="String(gate.id)">
                            {{ gate.gate_name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            
            <div class="space-y-2">
                <Label for="route_id" class="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                    Route
                </Label>

                <div class="relative">
                    <div class="relative">
                        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            id="route_id"
                            :model-value="routeInputDisplay"
                            :disabled="readonly"
                            class="pl-9 pr-16"
                            placeholder="Search route by name, origin, destination..."
                            @focus="openRouteDropdown"
                            @input="routeSearch = ($event.target as HTMLInputElement).value"
                            @blur="onRouteInputBlur"
                        />
                        <button
                            v-if="routeSearch && routeDropdownOpen && !readonly"
                            type="button"
                            class="absolute right-8 top-1/2 inline-flex h-5 w-5 -translate-y-1/2 items-center justify-center rounded-sm text-muted-foreground hover:text-foreground"
                            @mousedown.prevent="clearRouteSearch"
                        >
                            <X class="h-4 w-4" />
                        </button>
                        <button
                            v-else-if="selectedRoute && !readonly"
                            type="button"
                            class="absolute right-8 top-1/2 inline-flex h-5 w-5 -translate-y-1/2 items-center justify-center rounded-sm text-muted-foreground hover:text-foreground"
                            @mousedown.prevent="clearSelectedRoute"
                        >
                            <X class="h-4 w-4" />
                        </button>
                        <ChevronsUpDown class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    </div>

                    
                    <div
                        v-if="routeDropdownOpen && !readonly"
                        class="absolute z-50 mt-1.5 max-h-72 w-full overflow-y-auto rounded-lg border bg-background shadow-lg"
                    >
                        <div v-if="filteredRoutes.length" class="p-1">
                            <button
                                v-for="route in filteredRoutes"
                                :key="route.id"
                                type="button"
                                class="flex w-full items-start gap-2.5 rounded-md px-3 py-2.5 text-left transition-colors hover:bg-accent hover:text-accent-foreground"
                                @mousedown.prevent="selectRoute(route)"
                            >
                                <Check
                                    class="mt-0.5 h-4 w-4 shrink-0 text-primary"
                                    :class="String(modelValue) === String(route.id) ? 'opacity-100' : 'opacity-0'"
                                />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium">{{ route.route_name }}</p>
                                    <p class="mt-0.5 truncate text-xs text-muted-foreground">
                                        <span class="text-emerald-600">{{ route.origin_name || '—' }}</span>
                                        <span class="mx-1">→</span>
                                        <span class="text-red-500">{{ route.destination_name || '—' }}</span>
                                        <span v-if="route.gate?.gate_name" class="ml-1.5 text-muted-foreground">
                                            · {{ route.gate.gate_name }}
                                        </span>
                                    </p>
                                </div>
                                <Badge v-if="route.stops?.length" variant="secondary" class="shrink-0 text-xs">
                                    {{ route.stops.length }}
                                </Badge>
                            </button>
                        </div>
                        <div v-else class="flex flex-col items-center gap-1.5 px-3 py-6 text-center text-muted-foreground">
                            <Search class="h-5 w-5 opacity-40" />
                            <p class="text-sm">No routes found.</p>
                            <p class="text-xs">Try adjusting your search or gate filter.</p>
                        </div>
                    </div>
                </div>

                <InputError :message="error" />

                <p class="text-xs text-muted-foreground">
                    <template v-if="selectedGate">
                        Filtering for <span class="font-medium text-foreground">{{ selectedGate.gate_name }}</span>.
                    </template>
                    <template v-else>Showing all gates.</template>
                    <span class="ml-1">{{ filteredRoutes.length }} route{{ filteredRoutes.length === 1 ? '' : 's' }} available.</span>
                </p>
            </div>
        </div>

        
        <div v-if="routeSummary" class="overflow-hidden rounded-xl border shadow-sm">

            
            <div class="flex flex-col gap-4 bg-muted/30 p-4 md:flex-row md:items-center md:justify-between">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border bg-background shadow-sm">
                        <RouteIcon class="h-5 w-5 text-muted-foreground" />
                    </div>
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-sm">{{ routeSummary.name }}</p>
                        <p class="flex items-center gap-1 text-xs text-muted-foreground mt-0.5">
                            <span class="text-emerald-600 font-medium">{{ routeSummary.origin }}</span>
                            <Navigation class="h-3 w-3 shrink-0 rotate-90" />
                            <span class="text-red-500 font-medium">{{ routeSummary.destination }}</span>
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <Badge variant="secondary" class="gap-1.5">
                        <ListOrdered class="h-3 w-3" />
                        {{ routeSummary.totalStops }} stops
                    </Badge>
                    <Badge variant="outline" class="gap-1.5">
                        <MapPin class="h-3 w-3" />
                        {{ routeSummary.gateName }}
                    </Badge>

                    
                    <Dialog v-model:open="stopsDialogOpen">
                        <DialogTrigger as-child>
                            <Button type="button" variant="outline" size="sm" class="h-7 gap-1.5 text-xs">
                                <ListOrdered class="h-3.5 w-3.5" />
                                View Stops
                            </Button>
                        </DialogTrigger>
                        <DialogContent class="max-w-xl">
                            <DialogHeader>
                                <DialogTitle>{{ selectedRoute?.route_name || 'Route Stops' }}</DialogTitle>
                                <DialogDescription>Ordered list of stops for this route.</DialogDescription>
                            </DialogHeader>

                            <div v-if="selectedRoute" class="space-y-4">
                                
                                <div class="grid grid-cols-3 gap-3">
                                    <Card>
                                        <CardContent class="p-3">
                                            <p class="text-xs text-muted-foreground">Origin</p>
                                            <p class="mt-1 text-sm font-semibold text-emerald-600">
                                                {{ selectedRoute.origin_name || '—' }}
                                            </p>
                                        </CardContent>
                                    </Card>
                                    <Card>
                                        <CardContent class="p-3">
                                            <p class="text-xs text-muted-foreground">Destination</p>
                                            <p class="mt-1 text-sm font-semibold text-red-500">
                                                {{ selectedRoute.destination_name || '—' }}
                                            </p>
                                        </CardContent>
                                    </Card>
                                    <Card>
                                        <CardContent class="p-3">
                                            <p class="text-xs text-muted-foreground">Total Stops</p>
                                            <p class="mt-1 text-sm font-semibold">{{ sortedStops.length }}</p>
                                        </CardContent>
                                    </Card>
                                </div>

                                
                                <div
                                    v-if="sortedStops.length"
                                    class="max-h-[400px] space-y-1.5 overflow-y-auto rounded-lg border p-2"
                                >
                                    <div
                                        v-for="(stop, idx) in sortedStops"
                                        :key="stop.id"
                                        class="flex items-start gap-3 rounded-lg border bg-card p-3 transition-colors hover:bg-accent/30"
                                    >
                                        
                                        <div class="flex flex-col items-center gap-1">
                                            <div
                                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white"
                                                :class="stopTypeDot(stop.stop_type)"
                                            >
                                                {{ stop.stop_order }}
                                            </div>
                                            <div
                                                v-if="idx < sortedStops.length - 1"
                                                class="w-px flex-1 bg-border"
                                                style="min-height: 12px"
                                            />
                                        </div>
                                        <div class="min-w-0 flex-1 pb-1">
                                            <div class="flex items-start justify-between gap-2">
                                                <p class="text-sm font-semibold leading-snug">{{ stop.stop_name }}</p>
                                                <Badge variant="outline" class="shrink-0 text-xs">
                                                    {{ humanize(stop.stop_type) }}
                                                </Badge>
                                            </div>
                                            <p v-if="stop.address" class="mt-0.5 text-xs text-muted-foreground">
                                                {{ stop.address }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <p v-else class="text-sm text-muted-foreground">No stops available for this route.</p>
                            </div>
                            <div v-else class="rounded-lg bg-muted/30 p-4 text-sm text-muted-foreground">
                                Select a route first.
                            </div>
                        </DialogContent>
                    </Dialog>

                    
                    <Dialog v-model:open="mapDialogOpen">
                        <DialogTrigger as-child>
                            <Button type="button" variant="outline" size="sm" class="h-7 gap-1.5 text-xs">
                                <MapPinned class="h-3.5 w-3.5" />
                                View Map
                            </Button>
                        </DialogTrigger>
                        <DialogContent class="max-w-5xl">
                            <DialogHeader>
                                <DialogTitle>{{ selectedRoute?.route_name || 'Route Map' }}</DialogTitle>
                                <DialogDescription>
                                    Interactive map preview. Click markers to see stop details.
                                </DialogDescription>
                            </DialogHeader>

                            
                            <div v-if="selectedRoute" class="flex flex-wrap gap-3 text-xs text-muted-foreground">
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-500" />
                                    Origin
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="h-2.5 w-2.5 rounded-full bg-red-500" />
                                    Destination
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="h-2.5 w-2.5 rounded-full bg-amber-400" />
                                    Stop
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="h-2.5 w-2.5 rounded-full bg-violet-500" />
                                    Landmark
                                </span>
                            </div>

                            <div
                                v-if="selectedRoute"
                                ref="mapEl"
                                class="h-[520px] w-full overflow-hidden rounded-xl border shadow-sm"
                            />
                            <div v-else class="rounded-lg bg-muted/30 p-4 text-sm text-muted-foreground">
                                Select a route first.
                            </div>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            
            <div v-if="sortedStops.length" class="flex items-center gap-0 overflow-x-auto px-4 py-3">
                <template v-for="(stop, idx) in sortedStops.slice(0, 5)" :key="stop.id">
                    <div class="flex shrink-0 items-center gap-1.5">
                        <div
                            class="h-2.5 w-2.5 rounded-full"
                            :class="stopTypeDot(stop.stop_type)"
                        />
                        <span class="text-xs font-medium whitespace-nowrap">{{ stop.stop_name }}</span>
                    </div>
                    <div
                        v-if="idx < Math.min(sortedStops.length, 5) - 1"
                        class="mx-2 h-px w-6 shrink-0 bg-border"
                    />
                </template>
                <template v-if="sortedStops.length > 5">
                    <div class="mx-2 h-px w-4 shrink-0 bg-border" />
                    <span class="shrink-0 text-xs text-muted-foreground">
                        +{{ sortedStops.length - 5 }} more
                    </span>
                </template>
            </div>

            <Separator v-if="routeSummary" />
        </div>
    </div>
</template>
