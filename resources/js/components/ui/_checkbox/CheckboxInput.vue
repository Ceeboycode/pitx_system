<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"
import { RiSquareFill } from "vue-remix-icons"

/**
 * The app's checkbox: a native input drawn as a small box, with the check mark laid over it.
 * Use it with v-model; `indeterminate` marks a partly selected group. Clicks always land on the
 * input (the mark ignores the pointer), so a surrounding <label> or clickable row works as usual.
 */
const model = defineModel<boolean>({ default: false })

const props = defineProps<{
  indeterminate?: boolean
  disabled?: boolean
  ariaLabel?: string
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <span :class="cn('relative inline-flex items-center', props.class)">
    <input
      v-model="model"
      type="checkbox"
      class="peer h-4 w-4 cursor-pointer appearance-none rounded border border-custom-bg-dark dark:border-custom-bg-light bg-custom-bg-dark dark:bg-custom-bg-light transition-all checked:border-custom-accent-3 checked:bg-custom-accent-3 indeterminate:border-custom-accent-3 indeterminate:bg-custom-accent-3 dark:checked:border-custom-secondary dark:checked:bg-custom-secondary dark:indeterminate:border-custom-secondary dark:indeterminate:bg-custom-secondary disabled:cursor-default"
      :indeterminate="props.indeterminate"
      :disabled="props.disabled"
      :aria-label="props.ariaLabel"
    />
    <span class="pointer-events-none absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-custom-bg-light dark:text-custom-shadow opacity-0 peer-checked:opacity-100">
      <RiSquareFill class="h-2 w-2 shrink-0" />
    </span>
    <span class="pointer-events-none absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-custom-bg-light dark:text-custom-shadow opacity-0 peer-indeterminate:opacity-100">
      <span class="block h-0.5 w-2 rounded-full bg-current" />
    </span>
  </span>
</template>
