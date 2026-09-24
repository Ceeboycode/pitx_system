<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { AcceptableValue } from 'reka-ui'

import { InputMessage } from '@/components/ui/_input-message'
import { Label } from '@/components/ui/label'
import SearchInput from '@/components/SearchInput.vue'
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { RiRouteLine } from 'vue-remix-icons'

import RouteDetailsDialog from './RouteDetailsDialog.vue'

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
    /** When false, the Gate field is locked and only follows the selected route instead of filtering it. */
    gateSelectable?: boolean
}>()

const emit = defineEmits<{
    'update:modelValue': [value: string]
}>()

const gateIsSelectable = computed(() => props.gateSelectable !== false)

const selectedGateId = ref<'all' | string>('all')
const routeSearch = ref('')
const routeDialogOpen = ref(false)

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
            !gateIsSelectable.value ||
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

function onRouteSelected(value: AcceptableValue) {
    const routeId = value != null ? String(value) : ''
    emit('update:modelValue', routeId)

    const route = props.routes.find((item) => String(item.id) === routeId)
    if (route?.gate_id) {
        selectedGateId.value = String(route.gate_id)
    }
}

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
</script>

<template>
    <div class="">
        <div :class="gateIsSelectable ? 'grid gap-4 md:grid-cols-[220px_1fr]' : 'grid gap-4'">
            <!-- <div v-if="gateIsSelectable" class="space-y-2">
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
            </div> -->

            <div class="space-y-2">
                <Label for="route_id">Route</Label>

                <Select :model-value="modelValue" :disabled="readonly" @update:model-value="onRouteSelected">
                    <SelectTrigger id="route_id" class="w-full">
                        <SelectValue placeholder="Select route" />
                    </SelectTrigger>

                    <SelectContent class="w-fit">
                        <SelectGroup>
                            <!-- <div class="p-2"> -->
                            <SearchInput v-model="routeSearch" placeholder="Search route..." @keydown.stop class="mb-2"/>
                            <!-- </div> -->

                            <SelectItem
                                v-for="route in filteredRoutes"
                                :key="route.id"
                                :value="String(route.id)"
                                :title="`${route.origin_name || '—'} → ${route.destination_name || '—'}${route.gate?.gate_name ? ' • ' + route.gate.gate_name : ''}`"
                            >
                                {{ route.route_name }}
                            </SelectItem>

                            <p
                                v-if="filteredRoutes.length === 0"
                                class="px-2 py-1 text-sm text-custom-shadow/80"
                            >
                                No route found.
                            </p>
                        </SelectGroup>
                    </SelectContent>
                </Select>

                <InputMessage variant="destructive" :message="error ?? undefined" />

                <p v-if="gateIsSelectable" class="text-xs text-muted-foreground">
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

        <button
            v-if="selectedRoute"
            type="button"
            class="flex w-full cursor-pointer items-center justify-between gap-2 rounded-md bg-custom-secondary/10 px-3 py-2 text-left transition-colors hover:bg-custom-secondary/20 dark:bg-custom-secondary/20 dark:hover:bg-custom-secondary/30"
            @click="routeDialogOpen = true"
        >
            <div class="flex min-w-0 flex-row items-center gap-2">
                <RiRouteLine class="h-4 w-4 shrink-0 text-custom-shadow" />

                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold">
                        {{ selectedRoute.route_name }}
                    </p>
                </div>
            </div>
        </button>

        <RouteDetailsDialog v-model:open="routeDialogOpen" :route="selectedRoute" :map-config="mapConfig" />
    </div>
</template>
