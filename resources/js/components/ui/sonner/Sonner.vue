<script lang="ts" setup>
import type { ToasterProps } from "vue-sonner"
import { RiAlertLine, RiCheckboxCircleLine, RiCloseCircleLine, RiCloseLine, RiInformationLine, RiLoader2Line } from "vue-remix-icons"
import { Toaster as Sonner } from "vue-sonner"
import { cn } from "@/lib/utils"

const props = defineProps<ToasterProps>()
</script>

<template>
  <Sonner
    :class="cn('toaster group', props.class)"
    v-bind="props"
    :toastOptions="{
      unstyled: true,
      classes: {
        toast: 'relative flex w-(--width) items-start gap-2 rounded-md border border-custom-bg-dark p-4 text-sm text-custom-shadow shadow-md/5',
        content: 'flex min-w-0 flex-1 flex-col gap-0.5',
        title: 'font-semibold leading-snug',
        description: 'text-xs leading-snug text-custom-shadow',
        icon: 'mt-0.5 flex shrink-0 items-center',
        actionButton: 'ml-auto inline-flex shrink-0 items-center rounded-full bg-custom-primary px-3 py-2 text-xs text-custom-bg-light dark:text-custom-shadow transition-colors duration-200 hover:bg-custom-primary/60 cursor-pointer',
        cancelButton: 'ml-auto inline-flex shrink-0 items-center rounded-full border border-custom-shadow/20 px-3 py-2 text-xs text-custom-shadow transition-colors duration-200 hover:bg-custom-secondary/20 hover:border-transparent cursor-pointer',
        closeButton: 'absolute top-2 right-2 rounded-sm p-0.5 text-custom-body transition-colors hover:bg-custom-bg-dark',
        default: 'border-l-4 border-l-custom-bg-dark border-y-transparent border-r-transparent bg-custom-bg-light dark:bg-custom-light dark:border-l-[color-mix(in_oklab,var(--custom-shadow)_40%,var(--custom-bg))]',
        loading: 'border-l-4 border-l-custom-bg-dark border-y-transparent border-r-transparent bg-custom-bg-light dark:bg-custom-light dark:border-l-[color-mix(in_oklab,var(--custom-shadow)_40%,var(--custom-bg))]',
        success: 'border-l-4 border-l-success border-y-transparent border-r-transparent bg-[color-mix(in_oklab,var(--success)_40%,var(--custom-bg-light))] dark:bg-[color-mix(in_oklab,var(--success)_40%,var(--custom-bg))]',
        info: 'border-l-4 border-l-info border-y-transparent border-r-transparent bg-[color-mix(in_oklab,var(--info)_40%,var(--custom-bg-light))] dark:bg-[color-mix(in_oklab,var(--info)_40%,var(--custom-bg))]',
        warning: 'border-l-4 border-l-warning border-y-transparent border-r-transparent bg-[color-mix(in_oklab,var(--warning)_40%,var(--custom-bg-light))] dark:bg-[color-mix(in_oklab,var(--warning)_40%,var(--custom-bg))]',
        error: 'border-l-4 border-l-custom-bg-destructive border-y-transparent border-r-transparent bg-[color-mix(in_oklab,var(--custom-bg-destructive)_40%,var(--custom-bg-light))] dark:bg-[color-mix(in_oklab,var(--custom-bg-destructive)_40%,var(--custom-bg))]',
      },
    }"
  >
    <template #success-icon>
      <RiCheckboxCircleLine class="size-4 shrink-0" />
    </template>
    <template #info-icon>
      <RiInformationLine class="size-4 shrink-0" />
    </template>
    <template #warning-icon>
      <RiAlertLine class="size-4 shrink-0" />
    </template>
    <template #error-icon>
      <RiCloseCircleLine class="size-4 shrink-0" />
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
