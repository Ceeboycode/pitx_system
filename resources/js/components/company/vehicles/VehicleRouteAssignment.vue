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
    MapPinned,
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
    gate?: {
        id: number
        gate_name: string
    } | null
}

const props = defineProps<{
    modelValue: string
    routes: RouteItem[]
    gates: GateItem[]
    error?: string | null
    mapConfig: {
        mapboxToken?: string | null
        defaultCenter: {
            lng: number
            lat: number
        }
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

const selectedRoute = computed(() => {
    return (
        props.routes.find(
            (route) => String(route.id) === String(props.modelValue),
        ) ?? null
    )
})

const selectedGate = computed(() => {
    if (selectedGateId.value === 'all') return null

    return (
        props.gates.find(
            (gate) => String(gate.id) === String(selectedGateId.value),
        ) ?? null
    )
})

const filteredRoutes = computed(() => {
    const keyword = routeSearch.value.trim().toLowerCase()

    return props.routes.filter((route) => {
        const matchesGate =
            selectedGateId.value === 'all' ||
            String(route.gate_id ?? '') === String(selectedGateId.value)

        if (!matchesGate) return false

        if (!keyword) return true

        const haystack = [
            route.route_name,
            route.origin_name ?? '',
            route.destination_name ?? '',
            route.gate?.gate_name ?? '',
        ]
            .join(' ')
            .toLowerCase()

        return haystack.includes(keyword)
    })
})

const sortedStops = computed(() => {
    if (!selectedRoute.value?.stops) return []

    return [...selectedRoute.value.stops].sort(
        (a, b) => a.stop_order - b.stop_order,
    )
})

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
    if (routeDropdownOpen.value) {
        return routeSearch.value
    }

    return selectedRoute.value?.route_name ?? ''
})

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
    emit('update:modelValue', String(route.id))
    routeDropdownOpen.value = false
    routeSearch.value = ''

    if (route.gate_id) {
        selectedGateId.value = String(route.gate_id)
    }
}

function clearRouteSearch() {
    routeSearch.value = ''
    routeDropdownOpen.value = true
}

function clearSelectedRoute() {
    emit('update:modelValue', '')
    routeSearch.value = ''
    routeDropdownOpen.value = true
}

function onRouteInputBlur() {
    window.setTimeout(() => {
        closeRouteDropdown()
    }, 150)
}

function markerColor(type: string) {
    switch (type) {
        case 'origin':
            return '#16a34a'
        case 'destination':
            return '#dc2626'
        case 'landmark':
            return '#8b5cf6'
        default:
            return '#f59e0b'
    }
}

function stopTypeLabel(type: string) {
    switch (type) {
        case 'origin':
            return 'Origin'
        case 'destination':
            return 'Destination'
        case 'landmark':
            return 'Landmark'
        default:
            return 'Stop'
    }
}

function humanize(value?: string | null) {
    if (!value) return '—'

    return value
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase())
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
        (stop) =>
            stop.longitude !== null &&
            stop.longitude !== undefined &&
            stop.latitude !== null &&
            stop.latitude !== undefined,
    )

    const center: [number, number] = validStops.length
        ? [Number(validStops[0].longitude), Number(validStops[0].latitude)]
        : [
              props.mapConfig.defaultCenter.lng,
              props.mapConfig.defaultCenter.lat,
          ]

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
                const geometry =
                    typeof geom === 'string' ? JSON.parse(geom) : geom

                mapInstance.addSource('route-line', {
                    type: 'geojson',
                    data: {
                        type: 'FeatureCollection',
                        features: [
                            { type: 'Feature', properties: {}, geometry },
                        ],
                    },
                })

                mapInstance.addLayer({
                    id: 'route-line-layer',
                    type: 'line',
                    source: 'route-line',
                    paint: { 'line-width': 5, 'line-color': '#2563eb' },
                })
            } catch {
                
            }
        }

        validStops.forEach((stop) => {
            new mapboxgl.Marker({ color: markerColor(stop.stop_type) })
                .setLngLat([Number(stop.longitude), Number(stop.latitude)])
                .setPopup(
                    new mapboxgl.Popup({ offset: 20 }).setHTML(`
                        <div style="min-width:180px">
                            <div style="font-weight:600;font-size:14px">
                                ${stop.stop_order}. ${stop.stop_name}
                            </div>
                            <div style="font-size:12px;color:#6b7280;margin-top:4px">
                                ${stopTypeLabel(stop.stop_type)}
                            </div>
                            ${stop.address ? `<div style="font-size:12px;margin-top:6px">${stop.address}</div>` : ''}
                        </div>
                    `),
                )
                .addTo(mapInstance)
        })

        if (validStops.length >= 2) {
            const bounds = new mapboxgl.LngLatBounds()

            validStops.forEach((stop) => {
                bounds.extend([Number(stop.longitude), Number(stop.latitude)])
            })

            mapInstance.fitBounds(bounds, { padding: 60, maxZoom: 14 })
        }
    })
}

watch(mapDialogOpen, async (open) => {
    if (open) {
        await nextTick()
        initMap()
    } else {
        destroyMap()
    }
})

watch(selectedGateId, () => {
    if (!props.modelValue) return

    const stillExists = filteredRoutes.value.some(
        (route) => String(route.id) === String(props.modelValue),
    )

    if (!stillExists) {
        emit('update:modelValue', '')
    }
})

watch(
    () => props.modelValue,
    (value) => {
        if (!value) return

        const route = props.routes.find(
            (item) => String(item.id) === String(value),
        )

        if (route?.gate_id) {
            selectedGateId.value = String(route.gate_id)
        }
    },
    { immediate: true },
)

onBeforeUnmount(() => destroyMap())
</script>

<template>
    <div class="space-y-4">
        <div class="grid gap-4 md:grid-cols-[220px_1fr]">
            <div class="space-y-2">
                <Label for="gate_id">Gate</Label>

                <Select v-model="selectedGateId" :disabled="readonly">
                    <SelectTrigger id="gate_id" class="w-full">
                        <SelectValue placeholder="Select gate" />
                    </SelectTrigger>

                    <SelectContent>
                        <SelectItem value="all">All gates</SelectItem>

                        <SelectItem
                            v-for="gate in gates"
                            :key="gate.id"
                            :value="String(gate.id)"
                        >
                            {{ gate.gate_name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div class="space-y-2">
                <Label for="route_id">Route</Label>

                <div class="relative">
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                        />

                        <Input
                            id="route_id"
                            :model-value="routeInputDisplay"
                            :disabled="readonly"
                            class="pl-9 pr-16"
                            placeholder="Search and select route"
                            @focus="openRouteDropdown"
                            @input="
                                routeSearch = ($event.target as HTMLInputElement).value
                            "
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

                        <ChevronsUpDown
                            class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                        />
                    </div>

                    <div
                        v-if="routeDropdownOpen && !readonly"
                        class="absolute z-50 mt-2 max-h-72 w-full overflow-y-auto rounded-md border bg-background shadow-md"
                    >
                        <div v-if="filteredRoutes.length" class="p-1">
                            <button
                                v-for="route in filteredRoutes"
                                :key="route.id"
                                type="button"
                                class="flex w-full items-start gap-2 rounded-sm px-3 py-2 text-left hover:bg-accent hover:text-accent-foreground"
                                @mousedown.prevent="selectRoute(route)"
                            >
                                <Check
                                    class="mt-0.5 h-4 w-4 shrink-0"
                                    :class="
                                        String(modelValue) === String(route.id)
                                            ? 'opacity-100'
                                            : 'opacity-0'
                                    "
                                />

                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium">
                                        {{ route.route_name }}
                                    </p>
                                    <p class="truncate text-xs text-muted-foreground">
                                        {{ route.origin_name || '—' }} →
                                        {{ route.destination_name || '—' }}
                                        <span v-if="route.gate?.gate_name">
                                            • {{ route.gate.gate_name }}
                                        </span>
                                    </p>
                                </div>
                            </button>
                        </div>

                        <div
                            v-else
                            class="px-3 py-4 text-sm text-muted-foreground"
                        >
                            No route found.
                        </div>
                    </div>
                </div>

                <InputError :message="error" />

                <p class="text-xs text-muted-foreground">
                    <span v-if="selectedGate">
                        Filtering routes for {{ selectedGate.gate_name }}.
                    </span>
                    <span v-else>
                        Showing routes from all gates.
                    </span>
                    {{ filteredRoutes.length }} route(s) available.
                </p>
            </div>
        </div>

        <div v-if="routeSummary" class="rounded-lg border">
            <div
                class="flex flex-col gap-4 p-4 md:flex-row md:items-center md:justify-between"
            >
                <div class="min-w-0 space-y-2">
                    <div class="flex items-center gap-2">
                        <div
                            class="rounded-md border p-2 text-muted-foreground"
                        >
                            <RouteIcon class="h-4 w-4" />
                        </div>

                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">
                                {{ routeSummary.name }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ routeSummary.origin }} →
                                {{ routeSummary.destination }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Badge variant="secondary">
                            {{ routeSummary.totalStops }} stops
                        </Badge>

                        <Badge variant="outline">
                            {{ routeSummary.gateName }}
                        </Badge>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Dialog v-model:open="mapDialogOpen">
                        <DialogTrigger as-child>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                            >
                                <MapPinned class="mr-2 h-4 w-4" />
                                Map
                            </Button>
                        </DialogTrigger>

                        <DialogContent class="max-w-5xl">
                            <DialogHeader>
                                <DialogTitle>
                                    {{
                                        selectedRoute?.route_name ||
                                        'Route Map'
                                    }}
                                </DialogTitle>
                                <DialogDescription>
                                    Route map preview.
                                </DialogDescription>
                            </DialogHeader>

                            <div
                                v-if="selectedRoute"
                                ref="mapEl"
                                class="h-[520px] w-full rounded-lg border"
                            />

                            <div
                                v-else
                                class="rounded-lg border p-4 text-sm text-muted-foreground"
                            >
                                Select a route first.
                            </div>
                        </DialogContent>
                    </Dialog>

                    <Dialog v-model:open="stopsDialogOpen">
                        <DialogTrigger as-child>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                            >
                                <ListOrdered class="mr-2 h-4 w-4" />
                                Stops
                            </Button>
                        </DialogTrigger>

                        <DialogContent class="max-w-2xl">
                            <DialogHeader>
                                <DialogTitle>
                                    {{
                                        selectedRoute?.route_name ||
                                        'Route Stops'
                                    }}
                                </DialogTitle>
                                <DialogDescription>
                                    Ordered list of route stops.
                                </DialogDescription>
                            </DialogHeader>

                            <div v-if="selectedRoute" class="space-y-4">
                                <div class="grid gap-3 md:grid-cols-3">
                                    <Card>
                                        <CardContent class="p-4">
                                            <p
                                                class="text-xs text-muted-foreground"
                                            >
                                                Origin
                                            </p>
                                            <p class="mt-1 text-sm font-medium">
                                                {{
                                                    selectedRoute.origin_name ||
                                                    '—'
                                                }}
                                            </p>
                                        </CardContent>
                                    </Card>

                                    <Card>
                                        <CardContent class="p-4">
                                            <p
                                                class="text-xs text-muted-foreground"
                                            >
                                                Destination
                                            </p>
                                            <p class="mt-1 text-sm font-medium">
                                                {{
                                                    selectedRoute.destination_name ||
                                                    '—'
                                                }}
                                            </p>
                                        </CardContent>
                                    </Card>

                                    <Card>
                                        <CardContent class="p-4">
                                            <p
                                                class="text-xs text-muted-foreground"
                                            >
                                                Total Stops
                                            </p>
                                            <p class="mt-1 text-sm font-medium">
                                                {{ sortedStops.length }}
                                            </p>
                                        </CardContent>
                                    </Card>
                                </div>

                                <div
                                    v-if="sortedStops.length"
                                    class="max-h-[420px] space-y-2 overflow-y-auto rounded-lg border p-3"
                                >
                                    <div
                                        v-for="stop in sortedStops"
                                        :key="stop.id"
                                        class="flex items-start justify-between gap-3 rounded-md border p-3"
                                    >
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium">
                                                {{ stop.stop_order }}.
                                                {{ stop.stop_name }}
                                            </p>
                                            <p
                                                class="text-xs text-muted-foreground"
                                            >
                                                {{
                                                    stop.address ||
                                                    'No address available'
                                                }}
                                            </p>
                                        </div>

                                        <Badge variant="outline">
                                            {{
                                                humanize(stop.stop_type)
                                            }}
                                        </Badge>
                                    </div>
                                </div>

                                <p
                                    v-else
                                    class="text-sm text-muted-foreground"
                                >
                                    No stops available for this route.
                                </p>
                            </div>

                            <div
                                v-else
                                class="rounded-lg border p-4 text-sm text-muted-foreground"
                            >
                                Select a route first.
                            </div>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            <Separator />
        </div>
    </div>
</template>
