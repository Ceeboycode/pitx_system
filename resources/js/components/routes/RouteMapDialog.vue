<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import mapboxgl from 'mapbox-gl'
import 'mapbox-gl/dist/mapbox-gl.css'

import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'

type RouteGeometry = {
    type: string
    coordinates: [number, number][]
}

type RouteStop = {
    id: number
    stop_name: string
    stop_order: number
    stop_type?: string | null
    address?: string | null
    latitude?: number | string | null
    longitude?: number | string | null
}

const open = defineModel<boolean>('open', { default: false })

const props = withDefaults(defineProps<{
    routeName?: string | null
    originName?: string | null
    destinationName?: string | null
    routeGeometry?: RouteGeometry | null
    stops?: RouteStop[]
    mapboxToken?: string | null
    defaultCenter?: {
        lng: number
        lat: number
    } | null
    defaultZoom?: number | null
}>(), {
    routeName: null,
    originName: null,
    destinationName: null,
    routeGeometry: null,
    stops: () => [],
    mapboxToken: '',
    defaultCenter: () => ({
        lng: 120.9842,
        lat: 14.5995,
    }),
    defaultZoom: 11,
})

const mapEl = ref<HTMLElement | null>(null)
let mapInstance: mapboxgl.Map | null = null
let mapMarkers: mapboxgl.Marker[] = []

const hasMapboxToken = computed(() => (props.mapboxToken ?? '').trim().length > 0)

const normalizedStops = computed(() =>
    [...(props.stops ?? [])]
        .sort((a, b) => a.stop_order - b.stop_order)
        .map((stop) => ({
            ...stop,
            latitude: toNullableNumber(stop.latitude),
            longitude: toNullableNumber(stop.longitude),
        })),
)

const mappableStops = computed(() =>
    normalizedStops.value.filter((stop) => isValidLatLng(stop.latitude, stop.longitude)),
)

const hasValidGeometry = computed(() => {
    const geometry = props.routeGeometry

    return (
        !!geometry &&
        geometry.type === 'LineString' &&
        Array.isArray(geometry.coordinates) &&
        geometry.coordinates.length > 0 &&
        geometry.coordinates.every((coordinate) => isValidCoordinate(coordinate))
    )
})

if (hasMapboxToken.value) {
    mapboxgl.accessToken = props.mapboxToken ?? ''
}

function toNullableNumber(value: unknown): number | null {
    if (value === null || value === undefined || value === '') return null

    const parsed = Number(value)
    return Number.isFinite(parsed) ? parsed : null
}

function isValidCoordinate(value: unknown): value is [number, number] {
    return (
        Array.isArray(value) &&
        value.length >= 2 &&
        typeof value[0] === 'number' &&
        Number.isFinite(value[0]) &&
        typeof value[1] === 'number' &&
        Number.isFinite(value[1])
    )
}

function isValidLatLng(lat: unknown, lng: unknown): lat is number {
    return typeof lat === 'number' && Number.isFinite(lat) && typeof lng === 'number' && Number.isFinite(lng)
}

function stopDisplayText(stop: { address?: string | null; stop_name: string }) {
    return stop.address?.trim() || stop.stop_name
}

function humanizeStopType(value?: string | null) {
    if (!value) return 'Stop'

    return value
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase())
}

function formatCoordinate(value: number | null) {
    if (value === null) return '—'
    return Number(value).toFixed(6)
}

function escapeHtml(value: string) {
    return value
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;')
}

function buildMarkerElement(kind: 'origin' | 'stop' | 'destination') {
    const pin = document.createElement('div')
    pin.className =
        'flex h-8 w-8 items-center justify-center rounded-full border-2 border-white text-[11px] font-bold text-white shadow-lg'

    if (kind === 'origin') {
        pin.classList.add('bg-emerald-600')
        pin.textContent = 'O'
    } else if (kind === 'destination') {
        pin.classList.add('bg-rose-600')
        pin.textContent = 'D'
    } else {
        pin.classList.add('bg-amber-500')
        pin.textContent = 'S'
    }

    return pin
}

function clearMarkers() {
    mapMarkers.forEach((marker) => marker.remove())
    mapMarkers = []
}

function destroyMap() {
    clearMarkers()

    if (mapInstance) {
        mapInstance.remove()
        mapInstance = null
    }
}

function addRouteMarkers() {
    if (!mapInstance) return

    clearMarkers()

    const stops = mappableStops.value

    stops.forEach((stop, index) => {
        const totalStops = stops.length
        const kind: 'origin' | 'stop' | 'destination' =
            index === 0 ? 'origin' : index === totalStops - 1 ? 'destination' : 'stop'

        const marker = new mapboxgl.Marker({
            element: buildMarkerElement(kind),
            anchor: 'bottom',
        })
            .setLngLat([stop.longitude, stop.latitude])
            .setPopup(
                new mapboxgl.Popup({ offset: 18 }).setHTML(`
                    <div style="min-width:220px;">
                        <div style="font-weight:600;">${escapeHtml(stopDisplayText(stop))}</div>
                        <div style="font-size:12px;color:#64748b;margin-top:4px;">
                            ${escapeHtml(humanizeStopType(stop.stop_type))}
                        </div>
                        <div style="font-size:12px;color:#64748b;margin-top:4px;">
                            ${escapeHtml(formatCoordinate(stop.latitude))}, ${escapeHtml(formatCoordinate(stop.longitude))}
                        </div>
                    </div>
                `),
            )
            .addTo(mapInstance)

        mapMarkers.push(marker)
    })
}

function fitMapToRoute() {
    if (!mapInstance) return

    const bounds = new mapboxgl.LngLatBounds()
    let hasBounds = false

    const coordinates = props.routeGeometry?.coordinates ?? []

    coordinates.forEach((coordinate) => {
        if (isValidCoordinate(coordinate)) {
            bounds.extend(coordinate)
            hasBounds = true
        }
    })

    mappableStops.value.forEach((stop) => {
        bounds.extend([stop.longitude, stop.latitude])
        hasBounds = true
    })

    if (hasBounds) {
        mapInstance.fitBounds(bounds, {
            padding: 80,
            maxZoom: 14,
        })
    }
}

watch(open, async (value) => {
    if (!value) {
        destroyMap()
        return
    }

    if (!hasMapboxToken.value || !hasValidGeometry.value) {
        open.value = false
        return
    }

    await nextTick()

    if (!mapEl.value) return

    destroyMap()

    mapInstance = new mapboxgl.Map({
        container: mapEl.value,
        style: 'mapbox://styles/mapbox/streets-v12',
        center: [props.defaultCenter!.lng, props.defaultCenter!.lat],
        zoom: props.defaultZoom ?? 11,
    })

    mapInstance.addControl(new mapboxgl.NavigationControl(), 'top-right')

    mapInstance.on('load', () => {
        if (!mapInstance || !props.routeGeometry) return

        const coordinates = props.routeGeometry.coordinates.filter((coordinate) =>
            isValidCoordinate(coordinate),
        )

        if (!coordinates.length) return

        mapInstance.addSource('route-preview', {
            type: 'geojson',
            data: {
                type: 'Feature',
                properties: {},
                geometry: {
                    type: 'LineString',
                    coordinates,
                },
            },
        })

        mapInstance.addLayer({
            id: 'route-preview-layer',
            type: 'line',
            source: 'route-preview',
            paint: {
                'line-width': 5,
                'line-color': '#2563eb',
            },
        })

        addRouteMarkers()
        fitMapToRoute()
    })
})

onBeforeUnmount(() => {
    destroyMap()
})
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="max-w-5xl overflow-hidden p-0">
            <DialogHeader class="border-b px-6 py-4">
                <DialogTitle>
                    {{ routeName ?? 'Route Map' }}
                </DialogTitle>

                <DialogDescription>
                    {{ originName || '—' }} → {{ destinationName || '—' }}
                </DialogDescription>
            </DialogHeader>

            <div class="relative">
                <div ref="mapEl" class="h-[560px] w-full" />

                <div class="absolute bottom-4 left-4 rounded-lg border bg-background/95 px-3 py-2 text-xs shadow-sm">
                    <div class="flex items-center gap-3">
                        <span class="flex items-center gap-1.5">
                            <span class="inline-block h-2 w-2 rounded-full bg-emerald-600" />
                            Origin
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="inline-block h-2 w-2 rounded-full bg-amber-500" />
                            Stop
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="inline-block h-2 w-2 rounded-full bg-rose-600" />
                            Destination
                        </span>
                    </div>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
