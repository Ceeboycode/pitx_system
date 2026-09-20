<script setup lang="ts">
import type { Component, HTMLAttributes } from "vue"
import { computed, useSlots } from "vue"
import { cn } from "@/lib/utils"
import { RiCheckboxCircleLine, RiErrorWarningLine } from "vue-remix-icons"
import { inputMessageVariants, type InputMessageVariants } from "."

const props = withDefaults(
  defineProps<{
    /** One line of text, e.g. a validation error. */
    message?: string
    /** Bold first line, for messages that carry more than one line of content. */
    title?: string
    variant?: NonNullable<InputMessageVariants["variant"]>
    /** Replaces the variant's default icon. */
    icon?: Component
    hideIcon?: boolean
    class?: HTMLAttributes["class"]
  }>(),
  { variant: "default" },
)

const slots = useSlots()

const icons = {
  default: null,
  success: RiCheckboxCircleLine,
  warning: RiErrorWarningLine,
  info: RiErrorWarningLine,
  destructive: RiErrorWarningLine,
} as const

// Every variant shows its own icon unless one is passed or `hideIcon` is set.
const icon = computed(() => (props.hideIcon ? null : (props.icon ?? icons[props.variant])))
const role = computed(() => (props.variant === "destructive" || props.variant === "warning" ? "alert" : "status"))
const visible = computed(() => Boolean(props.message || props.title || slots.default || slots.actions))
</script>

<template>
  <div v-show="visible" :role="role" :class="cn(inputMessageVariants({ variant: props.variant }), props.class)">
    <component :is="icon" v-if="icon" class="mt-0.5 h-4 w-4 shrink-0" />
    <div class="min-w-0 text-sm">
      <p v-if="props.title" class="font-semibold">{{ props.title }}</p>
      <p v-if="props.message" class="text-sm">{{ props.message }}</p>
      <slot />
      <div v-if="slots.actions" class="mt-2 flex flex-wrap items-center gap-2">
        <slot name="actions" />
      </div>
    </div>
  </div>
</template>
