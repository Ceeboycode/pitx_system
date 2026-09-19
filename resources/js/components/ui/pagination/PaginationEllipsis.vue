<script setup lang="ts">
import type { PaginationEllipsisProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { RiMore2Line } from "vue-remix-icons"
import { PaginationEllipsis } from "reka-ui"
import { cn } from "@/lib/utils"

const props = defineProps<PaginationEllipsisProps & { class?: HTMLAttributes["class"] }>()

const delegatedProps = reactiveOmit(props, "class")
</script>

<template>
  <PaginationEllipsis
    data-slot="pagination-ellipsis"
    v-bind="delegatedProps"
    :class="cn('flex size-9 items-center justify-center', props.class)"
  >
    <slot>
      <RiMore2Line class="size-4 shrink-0" />

    </slot>
  </PaginationEllipsis>
</template>
