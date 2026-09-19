<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()

</script>

<template>
  <div :class="cn('flex h-full min-h-0 min-w-1/3 max-w-1/3 flex-1 flex-col gap-0 p-6 pr-3 pl-3 overflow-y-auto no-scrollbar', props.class)">
    <slot/>
  </div>
</template>
