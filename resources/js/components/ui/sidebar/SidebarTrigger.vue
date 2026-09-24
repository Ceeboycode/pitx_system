<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { RiSidebarFoldLine, RiSidebarUnfoldLine } from "vue-remix-icons"
import { cn } from "@/lib/utils"
import { Button } from '@/components/ui/button'
import { useSidebar } from "./utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()

const { isMobile, state, toggleSidebar } = useSidebar()
</script>

<template>
  <Button
    data-sidebar="trigger"
    data-slot="sidebar-trigger"
    variant="float"
    size="icon"
    :class="cn(props.class)"
    @click="toggleSidebar"
  >
    <RiSidebarUnfoldLine v-if="isMobile || state === 'collapsed'" class="shrink-0" />
    <RiSidebarFoldLine v-else class="shrink-0" />

  </Button>
</template>
