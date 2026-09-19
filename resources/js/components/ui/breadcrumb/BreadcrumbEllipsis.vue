<script lang="ts" setup>
import type { HTMLAttributes } from "vue"
import { RiMore2Line } from "vue-remix-icons"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <span
    data-slot="breadcrumb-ellipsis"
    role="presentation"
    aria-hidden="true"
    :class="cn('flex size-9 items-center justify-center', props.class)"
  >
    <slot>
      <RiMore2Line class="size-4 shrink-0" />
    </slot>
    
  </span>
</template>
