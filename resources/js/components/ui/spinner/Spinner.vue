<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { RiLoader2Line } from "vue-remix-icons"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <RiLoader2Line
    role="status"
    aria-label="Loading"
    :class="cn('size-4 shrink-0 animate-spin', props.class)"
  />
</template>
