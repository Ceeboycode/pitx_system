<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <div class="no-scrollbar min-h-0 flex-1 overflow-auto">
    <table :class="cn('min-w-full border-collapse text-sm', props.class)">
      <slot />
    </table>
  </div>
</template>
