<script lang="ts" setup>
import type { ToasterProps } from "vue-sonner"
import { CircleCheckIcon } from "lucide-vue-next"
import { RiAlertLine, RiCloseCircleLine, RiCloseLine, RiInformationLine, RiLoader2Line } from "vue-remix-icons"
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
      <CircleCheckIcon class="size-4 shrink-0 text-emerald-600 dark:text-emerald-400" />
    </template>
    <template #info-icon>
      <RiInformationLine class="size-4 shrink-0 text-sky-600 dark:text-sky-400" />
    </template>
    <template #warning-icon>
      <RiAlertLine class="size-4 shrink-0 text-amber-600 dark:text-amber-400" />
    </template>
    <template #error-icon>
      <RiCloseCircleLine class="size-4 shrink-0 text-rose-600 dark:text-rose-400" />
    </template>
    <template #loading-icon>
      <div>
        <RiLoader2Line class="size-4 shrink-0 animate-spin" />
      </div>
    </template>
    <template #close-icon>
      <RiCloseLine class="size-4 shrink-0" />
    </template>
  </Sonner>
</template>
