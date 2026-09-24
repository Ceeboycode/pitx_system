<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import mapboxgl from 'mapbox-gl'
import 'mapbox-gl/dist/mapbox-gl.css'

import RouteStepMarker from '@/components/internal/route/RouteStepMarker.vue'
import { CardSeparator } from '@/components/ui/_card-separator'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import { Separator } from '@/components/ui/separator'
import { themeColor } from '@/lib/theme-color'

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

type RouteDetails = {
    id: number
    route_name: string
    route_geometry?: unknown
    stops?: RouteStop[]
}

/**
 * A route's map and numbered stop list in a dialog. Hovering a stop enlarges its pin on the map. The
 * caller owns the open state (`v-model:open`), so any button can open it.
 */
const props = defineProps<{
    route: RouteDetails | null
    mapConfig: {
        mapboxToken?: string | null
        defaultCenter: {
            lng: number
            lat: number
        }
        defaultZoom: number
    }
}>()

const open = defineModel<boolean>('open', { default: false })

const mapEl = ref<HTMLElement | null>(null)

let mapInstance: mapboxgl.Map | null = null

const sortedStops = computed(() => {
    if (!props.route?.stops) return []

    return [...props.route.stops].sort((a, b) => a.stop_order - b.stop_order)
})

type PinKind = 'origin' | 'stop' | 'landmark' | 'destination'

// Pins and the route line use the same tokens as Route/Edit's DetailsTab (and the RouteStepMarker of each
// row), so the map and the stop list read alike.
function pinColor(kind: PinKind): string {
    if (kind === 'stop') {
        const isDark = document.documentElement.classList.contains('dark')

        return themeColor(isDark ? '--custom-bg-light' : '--custom-bg-dark')
    }

    const tokens = {
        origin: '--custom-primary',
        landmark: '--custom-shadow',
        destination: '--custom-accent-1',
    } as const

    return themeColor(tokens[kind])
}

function outlineColor(): string {
    const color = themeColor('--custom-shadow')

    return document.documentElement.classList.contains('dark') ? color : `${color}99`
}

const routeLineColor = () => themeColor('--custom-accent-2')

function createPin(kind: PinKind): mapboxgl.Marker {
    const marker = new mapboxgl.Marker({ color: pinColor(kind) })

    // The default Mapbox pin: the shape is the only <path> that carries a `fill`.
    const svg = marker.getElement().querySelector('svg')
    const shape = svg?.querySelector('path[fill]')

    if (svg && shape) {
        svg.style.overflow = 'visible'
        shape.setAttribute('stroke', outlineColor())
        shape.setAttribute('stroke-width', '1')
        shape.setAttribute('stroke-linejoin', 'round')
    }

    return marker
}

function setPinHighlight(marker: mapboxgl.Marker, active: boolean) {
    const el = marker.getElement()
    const svg = el.querySelector('svg')

    if (!svg) return

    // Mapbox positions the marker element with its own transform, so only the drawn part is scaled.
    svg.style.transition = 'transform 120ms ease, filter 120ms ease'
    svg.style.transformOrigin = '50% 85%'
    svg.style.transform = active ? 'scale(1.15)' : ''
    svg.style.filter = active ? `drop-shadow(0 0 2px ${outlineColor()})` : ''
    el.style.zIndex = active ? '2' : ''
}

const stopMarkers = new Map<number, mapboxgl.Marker>()
const hoveredStopId = ref<number | null>(null)

watch(hoveredStopId, (hovered) => {
    stopMarkers.forEach((marker, id) => setPinHighlight(marker, id === hovered))
})

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

function stopKind(type: string): PinKind {
    return type === 'origin' || type === 'destination' || type === 'landmark' ? type : 'stop'
}

function destroyMap() {
    hoveredStopId.value = null
    stopMarkers.clear()
    mapInstance?.remove()
    mapInstance = null
}

function initMap() {
    if (!mapEl.value || !props.route) return

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
        : [props.mapConfig.defaultCenter.lng, props.mapConfig.defaultCenter.lat]

    const map = new mapboxgl.Map({
        container: mapEl.value,
        style: 'mapbox://styles/mapbox/streets-v12',
        center,
        zoom: props.mapConfig.defaultZoom,
        interactive: true,
    })

    mapInstance = map

    map.addControl(new mapboxgl.NavigationControl(), 'top-right')

    map.on('load', () => {
        const geom = props.route?.route_geometry

        if (geom) {
            try {
                const geometry = typeof geom === 'string' ? JSON.parse(geom) : geom

                map.addSource('route-line', {
                    type: 'geojson',
                    data: {
                        type: 'FeatureCollection',
                        features: [{ type: 'Feature', properties: {}, geometry }],
                    },
                })

                map.addLayer({
                    id: 'route-line-layer',
                    type: 'line',
                    source: 'route-line',
                    paint: {
                        'line-width': 5,
                        'line-color': routeLineColor(),
                        'line-emissive-strength': 1,
                    },
                })
            } catch {
                // A route whose geometry cannot be read is still shown by its stops.
            }
        }

        validStops.forEach((stop) => {
            const marker = createPin(stopKind(stop.stop_type))
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
                .addTo(map)

            stopMarkers.set(stop.id, marker)
        })

        if (validStops.length >= 2) {
            const bounds = new mapboxgl.LngLatBounds()

            validStops.forEach((stop) => {
                bounds.extend([Number(stop.longitude), Number(stop.latitude)])
            })

            map.fitBounds(bounds, { padding: 60, maxZoom: 14 })
        }
    })
}

watch(open, async (isOpen) => {
    if (isOpen) {
        await nextTick()
        initMap()
    } else {
        destroyMap()
    }
})

onBeforeUnmount(() => destroyMap())
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="flex max-h-[85vh] w-full max-w-4xl flex-col overflow-scroll no-scrollbar p-0">
            <DialogHeader class="border-b border-custom-bg-dark p-6 pb-4 dark:border-custom-bg-light">
                <DialogTitle class="truncate">
                    {{ route?.route_name || 'Route' }}
                </DialogTitle>
                <DialogDescription class="truncate">
                    Route map and stops.
                </DialogDescription>
            </DialogHeader>

            <div class="flex h-full w-full flex-col gap-6 overflow-hidden px-6 py-6 lg:flex-row">
                <div class="w-full">
                    <div
                        v-if="route"
                        ref="mapEl"
                        class="h-full min-h-72 w-full rounded-md border"
                    />
                </div>

                <Separator orientation="vertical" />

                <div v-if="route" class="no-scrollbar w-full overflow-scroll">
                    <CardSeparator title="Stops Info" />

                    <div class="flex flex-col gap-0.5 text-sm text-custom-shadow">
                        <div v-if="sortedStops.length" class="space-y-1">
                            <div
                                v-for="(stop, index) in sortedStops"
                                :key="stop.id"
                                class="flex items-start gap-2 rounded-md p-2 transition-colors hover:bg-custom-secondary/10"
                                @mouseenter="hoveredStopId = stop.id"
                                @mouseleave="hoveredStopId === stop.id && (hoveredStopId = null)"
                            >
                                <RouteStepMarker
                                    :number="index + 1"
                                    :kind="stopKind(stop.stop_type)"
                                    :connector="index < sortedStops.length - 1"
                                />

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <p class="truncate text-sm leading-tight font-semibold">
                                            {{ stop.stop_name }}
                                        </p>
                                        <span
                                            v-if="stop.stop_type === 'landmark'"
                                            class="mr-1 rounded-md bg-custom-bg px-2 text-sm dark:bg-custom-bg-light"
                                        >
                                            Landmark
                                        </span>
                                    </div>

                                    <p
                                        v-if="stop.stop_type === 'origin' || stop.stop_type === 'destination'"
                                        class="text-xs tracking-wide text-custom-shadow/80 uppercase"
                                    >
                                        {{ stopTypeLabel(stop.stop_type) }}
                                    </p>
                                    <p v-else class="text-xs text-custom-shadow/80">
                                        {{ stop.address || 'No address' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <p v-else class="py-2 text-sm text-custom-shadow/80">
                            No stops available for this route.
                        </p>
                    </div>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
