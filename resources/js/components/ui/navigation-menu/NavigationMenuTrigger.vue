<script setup lang="ts">
import type { NavigationMenuTriggerProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { RiArrowDownSLine } from "vue-remix-icons"
import {
  NavigationMenuTrigger,
  useForwardProps,
} from "reka-ui"
import { cn } from "@/lib/utils"
import { navigationMenuTriggerStyle } from "."

const props = defineProps<NavigationMenuTriggerProps & { class?: HTMLAttributes["class"] }>()

const delegatedProps = reactiveOmit(props, "class")

const forwardedProps = useForwardProps(delegatedProps)
</script>

<template>
  <NavigationMenuTrigger
    data-slot="navigation-menu-trigger"
    v-bind="forwardedProps"
    :class="cn(navigationMenuTriggerStyle(), 'group', props.class)"
  >
    <slot />
    <RiArrowDownSLine
      class="relative top-[1px] ml-1 size-3 shrink-0 transition duration-200 group-data-[state=open]:rotate-180"
      aria-hidden="true"
    />
  </NavigationMenuTrigger>
</template>
