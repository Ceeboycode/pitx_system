<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { useVModel } from "@vueuse/core"
import { cn } from "@/lib/utils"
import type { InputVariants } from "."
import { inputVariants } from "."

const props = defineProps<{
  defaultValue?: string | number
  modelValue?: string | number
  variant?: InputVariants["variant"]
  size?: InputVariants["size"]
  class?: HTMLAttributes["class"]
}>()

const emits = defineEmits<{
  (e: "update:modelValue", payload: string | number): void
}>()

const modelValue = useVModel(props, "modelValue", emits, {
  passive: true,
  defaultValue: props.defaultValue,
})
</script>

<template>
  <input
    v-model="modelValue"
    data-slot="input"
    :data-variant="variant"
    :data-size="size"
    :class="cn(inputVariants({ variant, size }), props.class)"
  >
</template>
