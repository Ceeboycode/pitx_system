<script lang="ts" setup>
import type { ToasterProps } from "vue-sonner"
import { CircleCheckIcon, InfoIcon, Loader2Icon, OctagonXIcon, TriangleAlertIcon, XIcon } from "lucide-vue-next"
import { Toaster as Sonner } from "vue-sonner"
import { cn } from "@/lib/utils"

const props = defineProps<ToasterProps>()
</script>

<template>
  <Sonner
    :class="cn('toaster group', props.class)"
    :style="{
      '--normal-bg': 'var(--popover)',
      '--normal-text': 'var(--popover-foreground)',
      '--normal-border': 'var(--border)',
      '--border-radius': 'var(--radius)',
    }"
    v-bind="props"
    :toastOptions="{
      class: 'rounded-lg shadow-sm',
      success: { class: 'border-l-4 border-emerald-500 dark:border-emerald-400' },
      info: { class: 'border-l-4 border-sky-500 dark:border-sky-400' },
      warning: { class: 'border-l-4 border-amber-500 dark:border-amber-400' },
      error: { class: 'border-l-4 border-rose-500 dark:border-rose-400' }
    }"
  >
    <template #success-icon>
      <CircleCheckIcon class="size-4 text-emerald-600 dark:text-emerald-400" />
    </template>
    <template #info-icon>
      <InfoIcon class="size-4 text-sky-600 dark:text-sky-400" />
    </template>
    <template #warning-icon>
      <TriangleAlertIcon class="size-4 text-amber-600 dark:text-amber-400" />
    </template>
    <template #error-icon>
      <OctagonXIcon class="size-4 text-rose-600 dark:text-rose-400" />
    </template>
    <template #loading-icon>
      <div>
        <Loader2Icon class="size-4 animate-spin" />
      </div>
    </template>
    <template #close-icon>
      <XIcon class="size-4" />
    </template>
  </Sonner>
</template>
