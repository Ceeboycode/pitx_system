<script lang="ts" setup>
import type { HTMLAttributes } from "vue"
import { RiArrowRightSLine } from "vue-remix-icons"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <li
    data-slot="breadcrumb-separator"
    role="presentation"
    aria-hidden="true"
    :class="cn('[&>svg]:size-3.5', props.class)"
  >
    <slot>
      <RiArrowRightSLine class="shrink-0" />
    </slot>
  </li>
</template>
