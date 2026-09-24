<script setup lang="ts">
/**
 * The numbered marker of the dispatch timeline (dispatched, arrived, departed), styled like the
 * RouteStepMarker of the route sequence: a square with a thick border, the number inside, and a line
 * down to the next marker. Reached steps are filled with the primary colour, the final step with the
 * accent, and steps not reached yet stay muted.
 *
 * Put it at the start of a row laid out as `flex items-start gap-3 p-2` in a `space-y-1` list, since the
 * connector is sized to reach the marker of the next row.
 */
const props = defineProps<{
    number: number;
    done: boolean;
    /** Fill a reached step with the accent instead of the primary colour (the last step). */
    final?: boolean;
    /** Draw the line down to the next marker (every marker except the last). */
    connector?: boolean;
    /** Whether the next step is reached, which colours the connector. */
    nextDone?: boolean;
}>();

function markerClass(): string {
    if (!props.done) {
        return 'border-custom-bg-dark bg-custom-bg-dark text-custom-shadow/70 dark:border-custom-bg-light dark:bg-custom-bg-light';
    }

    return props.final
        ? 'border-custom-accent-1 bg-custom-accent-1 text-custom-bg-light dark:text-custom-shadow'
        : 'border-custom-primary bg-custom-primary text-custom-bg-light dark:text-custom-shadow';
}
</script>

<template>
    <div class="relative ml-0.5 shrink-0 self-stretch">
        <div
            class="relative z-10 flex h-4.5 w-4.5 items-center justify-center rounded border border-[5.5px] text-xs font-semibold"
            :class="markerClass()"
        >
            {{ props.number }}
        </div>
        <div
            v-if="props.connector"
            class="absolute top-4.5 -bottom-5 left-1/2 w-[2px] -translate-x-1/2"
            :class="props.nextDone ? 'bg-custom-accent-2' : 'bg-custom-bg-dark dark:bg-custom-bg-light'"
            aria-hidden="true"
        />
    </div>
</template>
