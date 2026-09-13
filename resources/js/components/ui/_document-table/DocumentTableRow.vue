<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"
import { documentTableRowVariants } from  "."

const props = defineProps<{
  class?: HTMLAttributes["class"]
  status?: "default" | "inactive" | null
}>()
</script>

<template>
  <div
    :data-status="status"
    :class="cn(documentTableRowVariants({ status }), props.class)"
  >
    <slot />
  </div>
</template>
