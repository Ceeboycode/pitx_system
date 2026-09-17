<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <td :class="cn('py-1.5 pl-3 text-left align-middle text-sm', props.class)">
    <slot />
  </td>
</template>
