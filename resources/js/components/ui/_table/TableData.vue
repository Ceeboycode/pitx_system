<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <div :class="cn('col-span-1 flex min-w-0 justify-start py-1.5 pl-3 text-sm font-medium text-custom-shadow', props.class)">
    <slot />
  </div>
</template>
