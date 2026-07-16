<script lang="ts" setup>
import type { CalendarCellTriggerProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { CalendarCellTrigger, useForwardProps } from "reka-ui"
import { cn } from "@/lib/utils"
import { buttonVariants } from '@/components/ui/button'

const props = withDefaults(defineProps<CalendarCellTriggerProps & { class?: HTMLAttributes["class"] }>(), {
  as: "button",
})

const delegatedProps = reactiveOmit(props, "class")

const forwardedProps = useForwardProps(delegatedProps)
</script>

<template>
  <CalendarCellTrigger
    data-slot="calendar-cell-trigger"
    :class="cn(
      buttonVariants({ variant: 'ghost' }),
      'size-8 cursor-pointer p-0 font-normal hover:bg-custom-accent-3 hover:text-custom-shadow aria-selected:opacity-100',
      '[&[data-today]:not([data-selected])]:border [&[data-today]:not([data-selected])]:border-custom-primary [&[data-today]:not([data-selected])]:bg-transparent [&[data-today]:not([data-selected])]:text-custom-shadow',
      'data-[selected]:bg-custom-primary data-[selected]:text-white data-[selected]:opacity-100 [&[data-selected]:hover]:bg-custom-accent-3 data-[selected]:hover:text-custom-shadow data-[selected]:focus:bg-custom-primary data-[selected]:focus:text-white',
      'data-[disabled]:text-muted-foreground data-[disabled]:opacity-50',
      'data-[unavailable]:text-destructive-foreground data-[unavailable]:line-through',
      'data-[outside-view]:text-muted-foreground',
      props.class,
    )"
    v-bind="forwardedProps"
  >
    <slot />
  </CalendarCellTrigger>
</template>
