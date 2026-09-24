<script setup lang="ts">
import RouteSelectorWithPreview from '@/components/routes/RouteSelectorWithPreview.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { RiRouteLine } from 'vue-remix-icons';

import { dispatchStatusClass, dispatchStatusDot, dispatchStatusLabel } from '../status';
import type { DispatchGate, DispatchMapConfig, DispatchModel, DispatchRoute } from './types';

defineProps<{
    dispatch: DispatchModel;
    routes: DispatchRoute[];
    gates: DispatchGate[];
    mapConfig: DispatchMapConfig;
}>();
</script>

<template>
    <div class="space-y-4">
        <!-- <Card>
            <CardHeader>
                <CardTitle>Route</CardTitle>
                <CardDescription>The route of the dispatched vehicle, with its stops.</CardDescription>
            </CardHeader>
            <CardContent class="space-y-3">
                <div
                    v-if="dispatch.vehicle?.route"
                    class="flex items-center gap-2 rounded-md bg-custom-secondary/10 px-3 py-2 dark:bg-custom-secondary/20"
                >
                    <RiRouteLine class="h-4 w-4 shrink-0 text-custom-shadow" />
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold">{{ dispatch.vehicle.route.route_name }}</p>
                        <p class="truncate text-xs text-custom-shadow/80">
                            {{ dispatch.vehicle.route.origin_name || '—' }} → {{ dispatch.vehicle.route.destination_name || '—' }}
                        </p>
                    </div>
                </div>
                <p v-else class="text-sm text-custom-shadow/80">No route assigned to this vehicle.</p>

                <RouteSelectorWithPreview
                    v-if="dispatch.vehicle?.route"
                    :model-value="String(dispatch.vehicle?.route_id ?? '')"
                    :routes="routes"
                    :gates="gates"
                    :map-config="mapConfig"
                    :readonly="true"
                />
            </CardContent>
        </Card> -->
        OVERVIEW:PP
    </div>
</template>
