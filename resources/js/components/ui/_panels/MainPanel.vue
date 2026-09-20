<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()

</script>

<template>
  <div :class="cn('relative flex h-full min-h-0 w-full flex-1 flex-col gap-4 overflow-y-auto p-6 pr-3 no-scrollbar', props.class)">
    <slot/>
  </div>
</template>
