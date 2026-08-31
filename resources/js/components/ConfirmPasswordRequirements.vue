<script setup lang="ts">
import { computed } from 'vue';

import { RiRadioButtonLine } from 'vue-remix-icons';

const props = withDefaults(
    defineProps<{
        password: string;
        confirmation: string;
        active?: boolean;
    }>(),
    {
        active: true,
    },
);

const isVisible = computed(
    () =>
        props.active &&
        props.confirmation.length > 0 &&
        props.password !== props.confirmation,
);
</script>

<template>
    <div v-if="isVisible" class="mt-2 text-sm" aria-live="polite">

        <div
            class="flex items-center gap-2"
        >
            <RiRadioButtonLine
                class="h-4 w-4 text-destructive shrink-0"
                aria-hidden="true"
            />
            <span class="text-custom-shadow/80">
                Passwords do not match
            </span>
        </div>
    </div>
</template>
