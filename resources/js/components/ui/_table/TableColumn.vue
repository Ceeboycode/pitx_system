<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <div :class="cn('col-span-1 flex h-10 items-center justify-start px-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80', props.class)">
      <slot />
  </div>
</template>
