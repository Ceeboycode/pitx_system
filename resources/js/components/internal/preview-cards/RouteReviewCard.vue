<script setup lang="ts">
import { computed } from 'vue';
import CardSeparator from '@/components/ui/_card-separator/CardSeparator.vue';
import { PreviewCard, ReviewCardRow } from '@/components/ui/_preview-card';
import { fmtDistance, fmtDuration } from '@/lib/format';

type RouteValues = {
    route_name: string;
    origin_name: string;
    destination_name: string;
    distance_meters: number | null;
    duration_seconds: number | null;
    stops: unknown[];
};

/**
 * Always-open review of the route being created, shown in the SidePanel of Route/Create.vue
 * so the values can be checked (and copied) while the form is filled in. The stops are only
 * summarized here; the full sequence lives in the Details card.
 */
const props = defineProps<{
    values: RouteValues;
    gate?: { gate_name: string } | null;
}>();

const hasDestination = computed(() => !!props.values.destination_name);

const intermediateStops = computed(() => props.values.stops.length);

const stopsSummary = computed(() => {
    if (!hasDestination.value) return '';

    const total = intermediateStops.value + 2;
    const between =
        intermediateStops.value === 0
            ? 'no stops in between'
            : `${intermediateStops.value} in between`;

    return `${total} (${between})`;
});
</script>

<template>
    <PreviewCard
        title="Review"
        description="Review new route details before confirming."
        :closable="false"
    >
        <CardSeparator title="Route Info" />

        <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
            <ReviewCardRow label="Name" :value="props.values.route_name" />
            <ReviewCardRow label="Gate" :value="props.gate?.gate_name" />
            <ReviewCardRow label="Distance" :value="props.values.distance_meters ? fmtDistance(props.values.distance_meters) : ''" />
            <ReviewCardRow label="Est. Travel Duration" :value="props.values.duration_seconds ? fmtDuration(props.values.duration_seconds) : ''" />
        </div>

        <CardSeparator title="Path" />

        <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
            <ReviewCardRow label="Origin" :value="props.values.origin_name" />
            <ReviewCardRow label="Destination" :value="props.values.destination_name" />
            <ReviewCardRow label="Total Stops" :value="stopsSummary" />
        </div>

        <p class="mt-4 flex flex-col text-sm text-custom-shadow/80">
            <span>
                New routes are created with <span class="font-semibold">active</span> status.
            </span>
        </p>
    </PreviewCard>
</template>
