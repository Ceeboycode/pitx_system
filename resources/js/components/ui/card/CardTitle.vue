<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <p
    data-slot="card-title"
    :class="cn('text-custom-shadow text-2xl leading-none font-semibold', props.class)"
  >
    <slot />
  </p>
</template>
