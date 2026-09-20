<script setup lang="ts">
import { Separator } from '@/components/ui/separator';

const props = defineProps<{
  title?: string
}>()
</script>

<template>
  <div class="flex items-center gap-3 pt-4">
    <p class="font-semibold text-custom-primary dark:text-custom-accent-3 text-sm uppercase">
      {{ props.title }}
      <!-- <Badge variant="accent-3">{{
        totalStops
      }}</Badge> -->
    </p>
    <Separator class="flex-1 text-custom-accent-3" />
  </div>
</template>