<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"
import { TableColumn } from "."

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <div class="shrink-0 rounded-t-md bg-custom-bg dark:bg-custom-bg-light">
      <div :class="cn('grid gap-2 border-b border-custom-bg-dark dark:border-custom-bg-light', props.class)">
        <slot />
        <TableColumn class="pr-3 justify-end">
          Actions
        </TableColumn>
    </div>
  </div>
</template>
