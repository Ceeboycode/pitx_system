<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <thead
    data-slot="table-header"
    :class="cn('w-full bg-custom-bg border-0 border-b border-custom-bg-dark', props.class)"
  >
    <slot />
  </thead>
</template>
