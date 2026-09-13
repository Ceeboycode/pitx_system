<script setup lang="ts">
import type { TabsTriggerProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { TabsTrigger, useForwardProps } from "reka-ui"
import { cn } from "@/lib/utils"

const props = defineProps<TabsTriggerProps & { class?: HTMLAttributes["class"] }>()

const delegatedProps = reactiveOmit(props, "class")

const forwardedProps = useForwardProps(delegatedProps)
</script>

<template>
  <!-- <TabsTrigger
    data-slot="tabs-trigger"
    :class="cn(
      'data-[state=active]:bg-custom-light dark:data-[state=active]:text-custom-shadow focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:outline-ring dark:data-[state=active]:border-input dark:data-[state=active]:bg-input/30 text-foreground dark:text-muted-foreground inline-flex h-[calc(100%-1px)] flex-1 items-center justify-center gap-1.5 rounded-md border border-transparent px-2 py-1 text-sm font-medium whitespace-nowrap transition-[color,box-shadow] focus-visible:ring-[3px] focus-visible:outline-1 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:shadow-sm [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*=\'size-\'])]:size-4',
      props.class,
    )"
    v-bind="forwardedProps"
  > -->
  <TabsTrigger
    data-slot="tabs-trigger"
    :class="cn(
      'data-[state=active]:text-custom-shadow data-[state=inactive]:border-b-transparent data-[state=active]:border-b-custom-primary data-[state=active]:dark:border-b-custom-secondary data-[state=inactive]:hover:border-custom-shadow/60 data-[state=inactive]:hover:text-custom-shadow/80 border-b-4 inline-flex w-fit items-center justify-start gap-2 items-center pt-3 pb-2.5 px-3 text-sm whitespace-nowrap transition-all [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*=\'size-\'])]:size-4 cursor-pointer',
      props.class,
    )"
    v-bind="forwardedProps"
  >
    <slot />
  </TabsTrigger>
</template>
