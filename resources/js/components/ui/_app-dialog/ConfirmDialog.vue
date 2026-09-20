<script setup lang="ts">
import type { Component } from "vue"
import { computed, ref, watch } from "vue"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import AppDialog from "./AppDialog.vue"

const open = defineModel<boolean>("open")

const props = withDefaults(
  defineProps<{
    title: string
    description?: string
    /** primary = restore, activate, verify... (float-primary). negative = archive, delete, reject... (float-red). */
    tone?: "primary" | "negative"
    confirmLabel: string
    /** Shown on the confirm button while `processing` is true. */
    processingLabel?: string
    cancelLabel?: string
    icon?: Component
    processing?: boolean
    confirmDisabled?: boolean
    /** Type-to-confirm: this exact text must be typed before the confirm button unlocks. */
    confirmText?: string
    size?: "md" | "lg" | "xl"
  }>(),
  {
    tone: "primary",
    cancelLabel: "Cancel",
    size: "md",
  },
)

const emit = defineEmits<{
  confirm: []
}>()

const typed = ref("")

watch(open, (isOpen) => {
  if (isOpen) typed.value = ""
})

const locked = computed(
  () =>
    props.confirmDisabled ||
    props.processing ||
    (props.confirmText !== undefined && typed.value.trim() !== props.confirmText),
)

function confirm() {
  if (!locked.value) emit("confirm")
}
</script>

<template>
  <AppDialog v-model:open="open" :title="props.title" :description="props.description" :size="props.size">
    <template v-if="$slots.description" #description>
      <slot name="description" />
    </template>

    <template v-if="$slots.default || props.confirmText" #default>
      <slot />
      <div v-if="props.confirmText" class="space-y-2">
        <p class="text-sm text-custom-shadow/80">
          To confirm, type
          <span class="mx-1 font-mono font-semibold text-custom-accent-1">{{ props.confirmText }}</span>
          below.
        </p>
        <Input v-model="typed" :placeholder="`Type ${props.confirmText} to confirm`" @keydown.enter.prevent="confirm" />
      </div>
    </template>

    <template #footer>
      <Button variant="float" :disabled="props.processing" @click="open = false">
        {{ props.cancelLabel }}
      </Button>
      <Button :variant="props.tone === 'negative' ? 'float-red' : 'float-primary'" :disabled="locked" @click="confirm">
        <component :is="props.icon" v-if="props.icon" class="h-4 w-4 shrink-0" />
        {{ props.processing && props.processingLabel ? props.processingLabel : props.confirmLabel }}
      </Button>
    </template>
  </AppDialog>
</template>
