<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"
import { Card } from "@/components/ui/card"
import { documentTableVariants } from "."
import { computed } from "vue";

const props = withDefaults(defineProps<{
    docsDataLength: number | string
    class?: HTMLAttributes["class"]
  }>(),
  {
    docsDataLength: 0,
  },
)

const variant = computed(() => {
  const length = Number.parseInt(String(props.docsDataLength ?? 0), 10)
  return length === 0 ? "empty" : "default"
})
</script>

<template>
  <Card
    :data-length="String(props.docsDataLength)"
    :data-variant="variant"
    :class="cn(documentTableVariants({ variant }), props.class)"
  >
    <slot />
  </Card>
</template>
