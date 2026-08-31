<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"
import { Card } from "@/components/ui/card"
import { tableVariants } from "."
import { computed } from "vue";

const props = withDefaults(defineProps<{
    tableDataLength: number | string
    class?: HTMLAttributes["class"]
  }>(),
  {
    tableDataLength: 0,
  },
)

const variant = computed(() => {
  const length = Number.parseInt(String(props.tableDataLength ?? 0), 10)
  return length === 0 ? "empty" : "default"
})
</script>

<template>
  <Card
    :data-length="String(props.tableDataLength)"
    :data-variant="variant"
    :class="cn(tableVariants({ variant }), props.class)"
  >
    <slot />
  </Card>
</template>
