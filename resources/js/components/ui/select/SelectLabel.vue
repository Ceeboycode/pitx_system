<script setup lang="ts">
import type { SelectLabelProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { SelectLabel } from "reka-ui"
import { cn } from "@/lib/utils"
import { Separator } from '@/components/ui/separator';

const props = defineProps<SelectLabelProps & { class?: HTMLAttributes["class"] }>()
</script>

<template>
  <div class="flex items-center gap-3 px-2">
    <SelectLabel
      data-slot="select-label"
      :class="cn('text-custom-shadow/80 py-1.5 text-sm', props.class)"
    >
      <slot />
    </SelectLabel>
    <Separator class="flex-1 text-custom-accent-3" />
  </div>
</template>
