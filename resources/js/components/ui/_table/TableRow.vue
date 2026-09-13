<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"
import { tableRowVariants } from "@/components/ui/_table"

const props = defineProps<{
  class?: HTMLAttributes["class"]
  status?: "default" | "inactive" | null
}>()
</script>

<template>
  <div
    :data-status="status"
    :class="cn(tableRowVariants({ status }), props.class)"
  >
    <slot />
  </div>
</template>
