<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"],
  toggleSort?: string,
  sortIcon?: string,
  sortIconClass?: string,
}>()

const sortBy = ref<SortField>(props.filters.sort_by ?? null);
const sortDir = ref<SortDir>(props.filters.sort_dir ?? 'asc');

function toggleSort(field: SortField) {
    if (sortBy.value === field) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = field;
        sortDir.value = 'asc';
    }
    applyFilters();
}

function sortIcon(field: SortField) {
    if (sortBy.value !== field) return RiArrowUpDownLine;
    return sortDir.value === 'asc' ? RiArrowUpSLine : RiArrowDownSLine;
}

function sortIconClass(field: SortField) {
    return sortBy.value === field
        ? 'text-custom-primary'
        : 'text-custom-shadow/40';
}

</script>

<template>
  <div :class="cn('col-span-1 flex h-10 items-center justify-start px-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80', props.class)">
  <!-- <div data-slot="table-container" class="no-scrollbar relative w-full overflow-auto"> -->
  <!-- <table data-slot="table" :class="cn('w-full caption-bottom text-sm', props.class)"> -->
      <slot />
      <button
        type="button"
        class="col-span-1 flex h-10 cursor-pointer select-none items-center justify-start gap-1.5 px-0 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80 transition-colors hover:text-custom-shadow"
        @click="toggleSort(props.toggleSort)"
      >
        Cap.
        <component
            :is="sortIcon(props.sortIcon)"
            class="h-3.5 w-3.5"
            :class="sortIconClass(props.sortIconClass)"
        />
      </button>
    <!-- </table> -->
  </div>
</template>
