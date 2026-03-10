<script setup lang="ts">
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { CheckCircle2, Map, Route as RouteIcon } from 'lucide-vue-next'

type RouteOption = {
    id: number
    route_name: string
    origin_name?: string | null
    destination_name?: string | null
    route_geometry?: {
        type: string
        coordinates: [number, number][]
    } | null
}

defineProps<{
    route: RouteOption
    canViewMap?: boolean
}>()

const emit = defineEmits<{
    viewMap: []
}>()
</script>

<template>
    <div class="space-y-5 rounded-2xl border bg-card p-4 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="flex min-w-0 items-start gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-green-50 text-green-600">
                    <CheckCircle2 class="h-4 w-4" />
                </div>

                <div class="min-w-0 space-y-3">
                    <div>
                        <p class="truncate text-sm font-semibold">{{ route.route_name }}</p>
                        <p class="truncate text-xs text-muted-foreground">
                            {{ route.origin_name || '—' }} → {{ route.destination_name || '—' }}
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-xl border p-3">
                            <p class="text-[11px] uppercase tracking-wide text-muted-foreground">Origin</p>
                            <p class="mt-1 text-sm font-medium">
                                {{ route.origin_name || '—' }}
                            </p>
                        </div>

                        <div class="rounded-xl border p-3">
                            <p class="text-[11px] uppercase tracking-wide text-muted-foreground">Destination</p>
                            <p class="mt-1 text-sm font-medium">
                                {{ route.destination_name || '—' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <Button
                type="button"
                variant="outline"
                :disabled="!canViewMap"
                @click="emit('viewMap')"
            >
                <Map class="mr-2 h-4 w-4" />
                View Map
            </Button>
        </div>
    </div>
</template>
