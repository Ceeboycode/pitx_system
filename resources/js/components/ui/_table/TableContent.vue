<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <div :class="cn('no-scrollbar min-h-0 flex-1 overflow-y-auto', props.class)">
  <!-- <div data-slot="table-container" class="no-scrollbar relative w-full overflow-auto"> -->
    <!-- <table data-slot="table" :class="cn('w-full caption-bottom text-sm', props.class)"> -->
      <slot />
  </div>
</template>
