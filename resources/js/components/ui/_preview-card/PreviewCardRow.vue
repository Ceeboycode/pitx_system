<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  label: string
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <div :class="cn('flex items-start justify-between gap-3', props.class)">
    <span class="text-sm font-semibold text-custom-shadow">{{ props.label }}</span>
    <div class="min-w-0 text-right text-sm">
      <slot />
    </div>
  </div>
</template>
