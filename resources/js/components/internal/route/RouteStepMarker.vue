<script setup lang="ts">
/**
 * The numbered marker of the route sequence (origin, stops, destination), styled like the steps of the
 * company registration stepper: a square with a thick border, the number inside, and a line down to the
 * next marker. The border colour matches the pin of that kind on the map.
 *
 * Put it at the start of a row laid out as `flex items-start gap-3 p-2` in a `space-y-1` list, since the
 * connector is sized to reach the marker of the next row.
 */
const props = defineProps<{
    number: number;
    kind: 'origin' | 'stop' | 'landmark' | 'destination';
    /** Draw the line down to the next marker (every marker except the last). */
    connector?: boolean;
}>();

const borderClass = {
    origin: 'border-custom-primary',
    stop: 'border-custom-bg-dark dark:border-custom-bg-light',
    landmark: 'border-custom-accent-3',
    destination: 'border-custom-accent-1',
} as const;
</script>

<template>
    <div class="relative shrink-0 self-stretch">
        <div
            class="relative z-10 flex h-6 w-6 text-xs items-center justify-center border-[6px] bg-transparent font-semibold text-custom-shadow rounded-xs"
            :class="[borderClass[props.kind]]"
        >
            {{ props.number }}
        </div>
        <div
            v-if="props.connector"
            class="absolute top-6 -bottom-5 left-1/2 w-[2px] -translate-x-1/2 bg-custom-accent-2"
            aria-hidden="true"
        />
    </div>
</template>
