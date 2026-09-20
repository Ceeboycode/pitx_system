<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { computed } from "vue"
import { cn } from "@/lib/utils"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import { Separator } from "@/components/ui/separator"

const open = defineModel<boolean>("open")

const props = withDefaults(
  defineProps<{
    title: string
    description?: string
    /** md = confirmations and small forms, lg = reason forms, xl = larger forms, viewer = previews and maps. */
    size?: "md" | "lg" | "xl" | "viewer"
    /** Show the X in the corner. Off by default: dialogs close through their Cancel/Close button. */
    closable?: boolean
    /** Wrap the body and footer in a <form>, so the footer's submit button submits it. */
    form?: boolean
    class?: HTMLAttributes["class"]
    bodyClass?: HTMLAttributes["class"]
  }>(),
  {
    size: "md",
    closable: false,
    form: false,
  },
)

const emit = defineEmits<{
  submit: []
}>()

const sizeClass = computed(
  () =>
    ({
      md: "max-w-md",
      lg: "max-w-lg",
      xl: "max-w-2xl",
      viewer: "max-w-5xl",
    })[props.size],
)
</script>

<template>
  <Dialog v-model:open="open">
    <DialogContent
      :show-close-button="props.closable"
      :class="cn('rounded-md p-6 [&_[data-slot=button]]:rounded-full', sizeClass, props.class)"
    >
      <DialogHeader class="px-0">
        <DialogTitle>
          <slot name="title">{{ props.title }}</slot>
        </DialogTitle>
        <DialogDescription v-if="props.description || $slots.description">
          <slot name="description">{{ props.description }}</slot>
        </DialogDescription>
      </DialogHeader>

      <component :is="props.form ? 'form' : 'div'" class="min-w-0" @submit.prevent="emit('submit')">
        <template v-if="$slots.default">
          <Separator />
          <div :class="cn('py-4', props.bodyClass)">
            <slot />
          </div>
        </template>

        <Separator />
        <DialogFooter class="gap-2 pt-4 sm:justify-end">
          <slot name="footer" />
        </DialogFooter>
      </component>
    </DialogContent>
  </Dialog>
</template>
