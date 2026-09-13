<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()

</script>

<template>
  <div :class="cn('flex h-full min-h-0 w-full flex-1 flex-row gap-6 p-6 overflow-y-auto no-scrollbar', props.class)">
    <slot/>
  </div>
</template>
