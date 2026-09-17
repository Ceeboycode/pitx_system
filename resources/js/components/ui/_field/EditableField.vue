<script setup lang="ts">
defineProps<{
    editable: boolean;
}>();
</script>

<template>
    <!-- `contents` keeps this wrapper out of the parent flex/grid box tree,
         so switching slots doesn't change surrounding layout. -->
    <span class="contents">
        <slot v-if="editable" name="edit" />
        <slot v-else />
    </span>
</template>
