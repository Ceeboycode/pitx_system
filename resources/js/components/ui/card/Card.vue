<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <div
    data-slot="card"
    :class="
      cn(
        'bg-custom-bg-light dark:bg-custom-bg text-custom-shadow flex flex-col gap-0 rounded-md dark:inset-shadow-sm dark:inset-shadow-white/5 py-6 shadow-sm',
        props.class,
      )
    "
  >
    <slot />
  </div>
</template>
