<script setup lang="ts">
import { computed } from 'vue'
import { Badge } from '@/components/ui/badge'
import { MapPinned } from 'lucide-vue-next'

type RouteStop = {
    id: number
    stop_name: string
    stop_order: number
    stop_type?: string | null
    address?: string | null
    latitude?: number | string | null
    longitude?: number | string | null
}

const props = defineProps<{
    stops: RouteStop[]
    title?: string
}>()

const sortedStops = computed(() =>
    [...props.stops].sort((a, b) => a.stop_order - b.stop_order),
)

function stopDisplayText(stop: { address?: string | null; stop_name: string }) {
    return stop.address?.trim() || stop.stop_name
}

function humanizeStopType(value?: string | null) {
    if (!value) return 'Stop'

    return value
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase())
}

function stopTypeBadgeClass(type?: string | null) {
    switch (type) {
        case 'origin':
            return 'border-green-200 bg-green-50 text-green-700'
        case 'destination':
            return 'border-red-200 bg-red-50 text-red-700'
        default:
            return 'border-amber-200 bg-amber-50 text-amber-700'
    }
}

function toNullableNumber(value: unknown): number | null {
    if (value === null || value === undefined || value === '') return null
    const parsed = Number(value)
    return Number.isFinite(parsed) ? parsed : null
}

function formatCoordinate(value: unknown) {
    const parsed = toNullableNumber(value)
    return parsed === null ? '—' : parsed.toFixed(6)
}
</script>

<template>
    <div class="space-y-3">
        <div class="flex items-center gap-2">
            <MapPinned class="h-4 w-4 text-muted-foreground" />
            <p class="font-medium">{{ title ?? 'Route Stops' }}</p>
            <Badge variant="secondary">{{ sortedStops.length }}</Badge>
        </div>

        <div v-if="sortedStops.length" class="relative">
            <div class="absolute bottom-4 left-[14px] top-4 w-px bg-border" />

            <div class="space-y-1">
                <div
                    v-for="(stop, index) in sortedStops"
                    :key="stop.id"
                    class="relative flex items-start gap-3 rounded-xl p-3 hover:bg-muted/40"
                >
                    <div
                        :class="[
                            'relative z-10 flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold text-white',
                            stop.stop_type === 'origin'
                                ? 'bg-green-600'
                                : stop.stop_type === 'destination'
                                  ? 'bg-red-600'
                                  : 'bg-amber-500',
                        ]"
                    >
                        {{ index + 1 }}
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-sm font-medium">
                                {{ stop.stop_name }}
                            </p>
                            <span
                                :class="[
                                    'inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-medium',
                                    stopTypeBadgeClass(stop.stop_type),
                                ]"
                            >
                                {{ humanizeStopType(stop.stop_type) }}
                            </span>
                        </div>

                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ stopDisplayText(stop) }}
                        </p>

                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ formatCoordinate(stop.latitude) }}, {{ formatCoordinate(stop.longitude) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <p v-else class="text-sm text-muted-foreground">
            No route stops available.
        </p>
    </div>
</template>
