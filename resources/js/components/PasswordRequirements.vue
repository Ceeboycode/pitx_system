<script setup lang="ts">
import { computed } from 'vue';

import { RiCheckLine, RiRadioButtonLine } from 'vue-remix-icons';

const props = withDefaults(
    defineProps<{
        password: string;
        active: boolean;
        minLength?: number;
    }>(),
    {
        minLength: 12,
    },
);

const isVisible = computed(() => props.active && props.password.length > 0);
const hasMinimumLength = computed(
    () => props.password.length >= props.minLength,
);
const hasUppercaseCharacter = computed(() => /[A-Z]/.test(props.password));
const hasLowercaseCharacter = computed(() => /[a-z]/.test(props.password));
const hasNumber = computed(() => /\d/.test(props.password));
const hasSpecialCharacter = computed(() => /[^A-Za-z\d]/.test(props.password));

const requirements = computed(() => [
    {
        label: `At least ${props.minLength} characters`,
        met: hasMinimumLength.value,
    },
    {
        label: 'One uppercase character',
        met: hasUppercaseCharacter.value,
    },
    {
        label: 'One lowercase character',
        met: hasLowercaseCharacter.value,
    },
    {
        label: 'One number',
        met: hasNumber.value,
    },
    {
        label: 'One special character',
        met: hasSpecialCharacter.value,
    },
]);
</script>

<template>
    <div v-if="isVisible" class="mt-2 text-sm" aria-live="polite">
        <!-- <p class="mb-1 font-medium">Password requirements</p> -->
         <!-- TODO: make it appear only when requirement is still not met, otherwise, keep it hidden -->

        <div
            v-for="requirement in requirements"
            :key="requirement.label"
            class="flex items-center gap-2"
        >
            <RiRadioButtonLine
                class="h-4 w-4 text-destructive shrink-0"
                aria-hidden="true"
                v-if="!requirement.met"
            />
            <span v-if="!requirement.met" :class="requirement.met ? 'text-custom-accent-3' : 'text-custom-shadow/80'">
                {{ requirement.label }}
            </span>
        </div>
    </div>
</template>
