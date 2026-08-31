<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"
import { computed } from "vue"

import { Button } from "@/components/ui/button"
import { RiMore2Line } from "vue-remix-icons"

import {
  DropdownMenu,
  DropdownMenuTrigger,
  DropdownMenuContent,
  DropdownMenuLabel,
} from "@/components/ui/dropdown-menu"

const props = defineProps<{
  class?: HTMLAttributes["class"]
  open?: boolean
  x?: number
  y?: number
  mode?: 'trigger' | 'context'
}>()

const emit = defineEmits<{
  "update:open": [value: boolean]
}>()

const isContextMenu = computed(() => props.mode === 'context')

const menuStyle = computed(() => {
  if (!isContextMenu.value) return {}

  const padding = 12
  const menuWidth = 240
  const menuHeight = 220

  const left = Math.min(
    Math.max(props.x ?? 0, padding),
    window.innerWidth - menuWidth - padding,
  )

  const top = Math.min(
    Math.max(props.y ?? 0, padding),
    window.innerHeight - menuHeight - padding,
  )

  return {
    position: 'fixed',
    left: `${left}px`,
    top: `${top}px`,
    transform: 'none',
  }
})
</script>

<template>
  <div
    :class="cn('col-span-1 flex min-w-0 justify-start py-1.5 pr-3 text-right justify-end text-sm font-medium text-custom-shadow', props.class)"
    @click.stop
    @contextmenu.prevent.stop
  >
    <DropdownMenu
      :open="props.open ?? false"
      @update:open="emit('update:open', $event)"
    >
      <DropdownMenuTrigger as-child>
          <Button
              variant="table-more"
              size="icon-more"
              class="cursor-pointer"
              @click.stop="emit('update:open', true)"
              @contextmenu.prevent.stop
          >
              <RiMore2Line class="h-4 w-4" />
          </Button>
      </DropdownMenuTrigger>

      <DropdownMenuContent
        v-if="isContextMenu"
        align="start"
        :style="menuStyle"
      >
        <slot />
      </DropdownMenuContent>

      <DropdownMenuContent
        v-else
        align="end"
      >
        <slot />
      </DropdownMenuContent>
    </DropdownMenu>
  </div>
</template>