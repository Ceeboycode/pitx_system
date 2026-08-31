<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <!-- <div :class="cn('col-span-1 flex h-10 items-center justify-start px-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80', props.class)"> -->
  <div :class="cn('grid cursor-pointer grid-cols-9 items-center border-b border-custom-bg-dark text-custom-shadow/80 transition-colors hover:bg-custom-secondary/10 hover:text-custom-shadow dark:border-custom-bg-light', props.class)">
      <slot />
  </div>
</template>
