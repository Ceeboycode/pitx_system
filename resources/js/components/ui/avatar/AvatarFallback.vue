<script setup lang="ts">
import type { AvatarFallbackProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { AvatarFallback } from "reka-ui"
import { cn } from "@/lib/utils"
import { RiUser3Fill } from "vue-remix-icons"

const props = withDefaults(defineProps<AvatarFallbackProps & {
  class?: HTMLAttributes["class"]
  variant?: "default" | "current-user" | null
}>(), {
    variant: "default",
})

const delegatedProps = reactiveOmit(props, "class")
</script>

<template>
  <AvatarFallback
    data-slot="avatar-fallback"
    v-bind="delegatedProps"
    :class="cn('flex size-full items-center justify-center rounded-md bg-custom-bg dark:bg-custom-bg-light text-sm font-semibold text-custom-primary dark:text-custom-shadow', props.class)"
    :data-variant="props.variant"
  >
    <slot v-if="props.variant ==='current-user'"/>
    <RiUser3Fill v-else class="text-custom-shadow/20 h-[60%] w-[60%]"/>
  </AvatarFallback>
</template>
