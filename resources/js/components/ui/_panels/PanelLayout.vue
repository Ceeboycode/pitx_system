<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()

</script>

<template>
  <div :class="cn('relative flex h-full min-h-0 w-full flex-1 flex-row p-0 pr-3 overflow-y-auto no-scrollbar', props.class)">
    <slot/>
    <div class="absolute inset-x-0 top-0 w-full h-6 bg-gradient-to-b from-custom-bg dark:from-custom-bg-dark to-transparent rounded-t-3xl inset-shadow-sm dark:inset-shadow-none"></div>
    <div class="absolute inset-x-0 bottom-0 w-full h-6 bg-gradient-to-t from-custom-bg dark:from-custom-bg-dark to-transparent rounded-b-3xl"></div>
  </div>
</template>
