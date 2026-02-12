<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <div class="flex items-center w-full
            group-data-[state=expanded]:flex-1"
    >
        <AppLogoIcon />
    </div>

    <!-- <div class="grid flex-1 text-left text-sm"> -->
    <div class="grid flex-2 text-left text-sm
            transition-all duration-100 ease-linear
             opacity-100 overflow-hidden
            group-data-[state=collapsed]:opacity-0
            inline-block">
        <span class="mb-0.5 truncate leading-tight font-semibold">
            Parañaque Integrated <br> Terminal Exchange
        </span>
    </div>

    <!-- <div
      class="grid min-w-0 overflow-hidden
             transition-[grid-template-columns,opacity] duration-200 ease-linear
             [grid-template-columns:1fr] opacity-100
             group-data-[state=collapsed]:[grid-template-columns:0fr]
             group-data-[state=collapsed]:opacity-0"
    >

      <div class="min-w-0">
        <span class="block truncate leading-tight font-semibold text-sm">
          Parañaque Integrated
        </span>
        <span class="block truncate leading-tight font-semibold text-sm">
          Terminal Exchange
        </span>
      </div>
    </div> -->
</template>
