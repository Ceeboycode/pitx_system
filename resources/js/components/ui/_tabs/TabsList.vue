<script setup lang="ts">
import type { TabsListProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { TabsList } from "reka-ui"
import { cn } from "@/lib/utils"

const props = defineProps<TabsListProps & { class?: HTMLAttributes["class"] }>()

const delegatedProps = reactiveOmit(props, "class")
</script>

<template>
  <TabsList
    data-slot="tabs-list"
    v-bind="delegatedProps"
    :class="cn(
      'shrink-0 bg-custom-bg-light dark:bg-custom-bg text-custom-shadow/60 inline-flex gap-0 w-full items-center justify-start px-3 py-0 rounded-b-md shadow-sm overflow-x-scroll no-scrollbar',
      props.class,
    )"
  >
    <!-- TODO: add overlay to indicate that there is more sa gilid-->
    <!-- <div class="absolute inset-0 top-6 z-50 h-16 w-24 bg-pink-300"></div> -->
    <slot />
  </TabsList>
</template>
