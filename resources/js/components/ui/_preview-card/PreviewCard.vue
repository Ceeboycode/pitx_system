<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { RiCloseLine } from "vue-remix-icons"

const props = withDefaults(
  defineProps<{
    title: string
    description?: string
    titleClass?: HTMLAttributes["class"]
    /** Show the X in the top-right corner. Review cards on Create pages are always open, so they turn it off. */
    closable?: boolean
    class?: HTMLAttributes["class"]
  }>(),
  {
    description: "Preview",
    closable: true,
  },
)

const emit = defineEmits<{
  close: []
}>()
</script>

<template>
  <Card :class="cn('relative flex h-full min-h-0 w-full', props.class)">
    <button
      v-if="props.closable"
      type="button"
      aria-label="Close preview"
      class="cursor-pointer ring-offset-background focus:ring-ring absolute top-4 right-4 rounded-md opacity-50 transition-opacity hover:opacity-100 focus:ring-2 focus:ring-offset-2 focus:outline-hidden [&_svg]:pointer-events-none [&_svg]:shrink-0"
      @click="emit('close')"
    >
      <RiCloseLine class="size-4" />
    </button>

    <CardHeader :class="props.closable ? 'pr-12' : undefined">
      <CardTitle :class="cn('truncate', props.titleClass)">
        <slot name="title">{{ props.title }}</slot>
      </CardTitle>
      <CardDescription>{{ props.description }}</CardDescription>
    </CardHeader>

    <CardContent class="no-scrollbar min-h-0 flex-1 space-y-2 overflow-y-auto pt-2">
      <slot />
    </CardContent>
  </Card>
</template>
