<script setup lang="ts">
import { RiErrorWarningLine } from 'vue-remix-icons';

defineProps<{
    message?: string;
}>();
</script>

<template>
    <div v-show="message" class="mt-2 w-full flex flex-row items-start p-3 gap-x-2 rounded-md border border-2 border-destructive/50 bg-destructive/20 text-destructive">
        <!-- CODE: <div class="w-full flex flex-row items-center"> -->
        <RiErrorWarningLine class="shrink-0 h-4 w-4 mt-0.5"/>
            <!-- LABEL: where the title will go -->
        
        <p class="text-sm">
            {{ message }}
        </p>
    </div>
</template>
