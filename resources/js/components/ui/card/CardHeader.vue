<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <div
    data-slot="card-header"
    :class="cn('@container/card-header px-6 has-data-[slot=card-action]:grid-cols-[1fr_auto]', props.class)"
  >
    <slot />
  </div>
</template>
