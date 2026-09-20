<script setup lang="ts">
import { AppDialog } from '@/components/ui/_app-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type RouteStop = {
    id: number;
    stop_name: string;
    stop_type: 'origin' | 'stop' | 'destination' | 'landmark' | string;
    address: string | null;
    latitude: number;
    longitude: number;
    stop_order: number;
};

const open = defineModel<boolean>('open');

defineProps<{
    stops: RouteStop[];
}>();

function stopTypeLabel(type: RouteStop['stop_type']) {
    switch (type) {
        case 'origin':
            return 'Origin';
        case 'destination':
            return 'Destination';
        case 'landmark':
            return 'Landmark';
        default:
            return 'Stop';
    }
}

function stopDotClass(type: RouteStop['stop_type']) {
    switch (type) {
        case 'origin':
            return 'bg-green-600';
        case 'destination':
            return 'bg-red-600';
        case 'landmark':
            return 'bg-violet-500';
        default:
            return 'bg-amber-500';
    }
}
</script>

<template>
    <AppDialog
        v-model:open="open"
        title="Route Stops"
        description="Ordered list of all route stops."
        size="xl"
    >
        <div v-if="stops.length" class="max-h-[65vh] overflow-auto pr-1">
            <div class="space-y-3">
                <div
                    v-for="(stop, i) in stops"
                    :key="stop.id"
                    class="flex items-start gap-3 rounded-lg border p-4"
                >
                    <div
                        :class="[
                            'mt-1 h-3 w-3 shrink-0 rounded-full',
                            stopDotClass(stop.stop_type),
                        ]"
                    />
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-sm font-medium">
                                {{ i + 1 }}. {{ stop.stop_name }}
                            </p>
                            <Badge variant="outline">
                                {{ stopTypeLabel(stop.stop_type) }}
                            </Badge>
                        </div>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ stop.address || 'No address provided' }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ Number(stop.latitude).toFixed(5) }},
                            {{ Number(stop.longitude).toFixed(5) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="py-8 text-center text-sm text-muted-foreground">
            No stops available.
        </div>

        <template #footer>
            <Button variant="float" @click="open = false">Close</Button>
        </template>
    </AppDialog>
</template>
