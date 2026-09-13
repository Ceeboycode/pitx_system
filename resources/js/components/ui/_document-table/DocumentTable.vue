<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <div :class="cn('flex min-h-0 flex-1 flex-col overflow-hidden', props.class)">
      <slot />
  </div>
</template>
