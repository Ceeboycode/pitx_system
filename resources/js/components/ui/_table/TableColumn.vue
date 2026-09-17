<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <th :class="cn('sticky top-0 z-10 h-10 whitespace-nowrap bg-custom-bg pl-3 pr-0 text-left align-middle text-xs font-semibold uppercase tracking-widest text-custom-shadow dark:bg-custom-bg-light', props.class)">
      <slot />
  </th>
</template>
