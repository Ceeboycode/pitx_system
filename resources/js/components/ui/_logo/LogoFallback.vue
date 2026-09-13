<script setup lang="ts">
import type { AvatarFallbackProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { AvatarFallback } from "reka-ui"
import { cn } from "@/lib/utils"

const props = defineProps<AvatarFallbackProps & { class?: HTMLAttributes["class"] }>()

const delegatedProps = reactiveOmit(props, "class")
</script>

<template>
  <AvatarFallback
    data-slot="avatar-fallback"
    v-bind="delegatedProps"
    :class="cn('flex size-full items-center justify-center rounded-md bg-custom-primary/10 dark:bg-custom-secondary/10 text-custom-shadow text-sm border border-dashed border-custom-primary/60 dark:border-custom-secondary/60', props.class)"
  >
    <slot />
  </AvatarFallback>
</template>
