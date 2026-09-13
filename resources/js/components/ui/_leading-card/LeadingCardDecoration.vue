<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"
import { CardContent } from "@/components/ui/card"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()

</script>

<template>
  <CardContent
    :class="cn('h-6 mt-6 bg-[repeating-linear-gradient(135deg,var(--custom-bg-light),var(--custom-bg-light)_12px,var(--custom-bg-dark)_12px,var(--custom-bg-dark)_24px)] dark:bg-[repeating-linear-gradient(135deg,var(--custom-bg-light),var(--custom-bg-light)_12px,var(--custom-bg)_12px,var(--custom-bg)_24px)] rounded-b-md', props.class)"
  >
    <slot/>
  </CardContent>
</template>