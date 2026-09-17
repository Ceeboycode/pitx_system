<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"
import { TableColumn } from "."

const props = defineProps<{
  class?: HTMLAttributes["class"]
  hideActionsColumn?: boolean
}>()
</script>

<template>
  <thead class="bg-custom-bg dark:bg-custom-bg-light">
    <tr :class="cn('border-b border-custom-bg-dark dark:border-custom-bg-light', props.class)">
      <slot />
      <TableColumn v-if="!hideActionsColumn" class="pr-3 text-right">
        Actions
      </TableColumn>
    </tr>
  </thead>
</template>
