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
    kind: 'origin' | 'stop' | 'landmark' | 'destination' | 'detour';
    /** Draw the line down to the next marker (every marker except the last). */
    connector?: boolean;
}>();

const borderClass = {
    origin: 'border border-custom-primary border-[5.5px] bg-custom-primary text-custom-bg-light dark:text-custom-shadow',
    stop: 'border border-custom-bg-dark dark:border-custom-bg-light border-[5.5px] bg-custom-bg-dark dark:bg-custom-bg-light text-custom-shadow',
    landmark: 'border border-custom-shadow border-[5.5px] bg-custom-shadow text-custom-bg-light dark:text-custom-shadow',
    destination: 'border border-custom-accent-1 border-[5.5px] bg-custom-accent-1 text-custom-bg-light dark:text-custom-shadow',
    detour: 'border border-custom-accent-3 border-[5.5px] bg-transparent',
} as const;
</script>

<template>
    <div class="ml-0.5 relative shrink-0 self-stretch">
        <div
            class="relative z-10 flex h-4.5 w-4.5 text-xs items-center justify-center font-semibold rounded"
            :class="[borderClass[props.kind]]"
        >
            {{ props.number }}
        </div>
        <div
            v-if="props.connector"
            class="absolute top-4.5 -bottom-5 left-1/2 w-[2px] -translate-x-1/2 bg-custom-accent-2"
            aria-hidden="true"
        />
    </div>
</template>
