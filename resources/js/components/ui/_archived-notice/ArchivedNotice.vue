<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { RiArchive2Line } from "vue-remix-icons"

const props = defineProps<{
  /** What was archived, in lowercase: "user", "gate", "vehicle type"... */
  entity: string
  class?: HTMLAttributes["class"]
  variant?: "default" | "destructive" | "warning" | null | undefined
}>()
</script>

<template>
  <Alert :class="cn('mb-3', props.class)" variant="warning">
    <RiArchive2Line class="h-4 w-4 shrink-0" />
    <AlertTitle>This {{ props.entity }} is archived</AlertTitle>
    <AlertDescription>
      It is shown read-only. Restore it from the archived list to make changes.
    </AlertDescription>
  </Alert>
</template>
