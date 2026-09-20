<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"
import { useClipboard } from "@vueuse/core"
import { toast } from "vue-sonner"
import { RiFileCheckLine } from "vue-remix-icons"

const props = defineProps<{
  label: string
  /** The value shown, and copied when it is clicked. Empty values show an em dash and copy nothing. */
  value?: string | null
  valueClass?: HTMLAttributes["class"]
  class?: HTMLAttributes["class"]
}>()

const { copy } = useClipboard({ legacy: true })

async function copyValue() {
  const text = (props.value ?? "").trim()
  if (!text) return

  try {
    await copy(text)
    toast.success(`${props.label} copied to clipboard.`)
  } catch {
    toast.error(`Could not copy ${props.label.toLowerCase()}.`)
  }
}
</script>

<template>
  <div :class="cn('flex flex-row items-center justify-between', props.class)">
    <div class="inline-flex items-center gap-2">
      <RiFileCheckLine class="h-4 w-4 shrink-0 text-custom-shadow/80" />
      <span>{{ props.label }}</span>
    </div>
    <span class="inline-flex items-center gap-2 overflow-hidden">
      <span
        role="button"
        tabindex="0"
        title="Copy to clipboard"
        :class="cn('line-clamp-1 cursor-pointer text-ellipsis', props.valueClass)"
        @click="copyValue"
        @keydown.enter.prevent="copyValue"
        @keydown.space.prevent="copyValue"
      >
        <slot>{{ props.value || "—" }}</slot>
      </span>
    </span>
  </div>
</template>
