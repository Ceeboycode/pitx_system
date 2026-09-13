<script setup lang="ts">
import type { SelectTriggerProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { computed } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { SelectIcon, SelectTrigger, useForwardProps } from "reka-ui"
import { cn } from "@/lib/utils"
import { RiArrowDownSLine } from 'vue-remix-icons';
import type { SelectTriggerVariants } from "."
import { selectTriggerVariants } from "."

const props = withDefaults(
  defineProps<SelectTriggerProps & {
    class?: HTMLAttributes["class"]
    size?: "sm" | "default"
    variant?: SelectTriggerVariants["variant"]
  }>(),
  { size: "default", variant: "default" },
)

const delegatedProps = reactiveOmit(props, "class", "size", "variant")
const forwardedProps = useForwardProps(delegatedProps)

// inline-edit opts out of the base `data-[size=*]:h-*` height so it can match Input's inline-edit (content height)
const dataSize = computed(() => (props.variant === "inline-edit" ? undefined : props.size))

const iconClass = computed(() =>
  props.variant === "inline-edit"
    ? "shrink-0 size-0 overflow-hidden text-custom-shadow/80 opacity-0 transition-all duration-200 ease-out group-hover:size-4 group-hover:ml-2 group-hover:opacity-100 group-focus-within:size-4 group-focus-within:ml-2 group-focus-within:opacity-100 group-data-[state=open]:size-4 group-data-[state=open]:ml-2 group-data-[state=open]:opacity-100 group-data-[state=open]:rotate-180"
    : "size-4 transition-transform duration-200 ease-out group-data-[state=open]:rotate-180",
)
</script>

<template>
  <SelectTrigger
    data-slot="select-trigger"
    :data-size="dataSize"
    :data-variant="variant"
    v-bind="forwardedProps"
    :class="cn('group', selectTriggerVariants({ variant }), props.class)"
  >
    <slot />
    <SelectIcon as-child>
      <RiArrowDownSLine :class="iconClass" />
    </SelectIcon>
  </SelectTrigger>
</template>
