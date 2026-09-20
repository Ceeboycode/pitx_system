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
      class="peer h-4 w-4 cursor-pointer appearance-none rounded border border-custom-shadow/20 transition-all checked:border-custom-primary checked:bg-custom-primary indeterminate:border-custom-primary indeterminate:bg-custom-primary disabled:cursor-default"
      :indeterminate="props.indeterminate"
      :disabled="props.disabled"
      :aria-label="props.ariaLabel"
    />
    <span class="pointer-events-none absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-custom-bg-light opacity-0 peer-checked:opacity-100">
      <RiSquareFill class="h-2 w-2 shrink-0" />
    </span>
    <span class="pointer-events-none absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-custom-bg-light opacity-0 peer-indeterminate:opacity-100">
      <span class="block h-0.5 w-2 rounded-full bg-current" />
    </span>
  </span>
</template>
