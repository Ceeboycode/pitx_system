<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import PitxLogo from './assets/PITX.png'


defineOptions({
    inheritAttrs: false,
})

interface Props {
    className?: HTMLAttributes['class']
}

defineProps<Props>()
</script>

<template>
    <img
        :src="PitxLogo"
        alt="PITX Logo"
        :class="className"
        v-bind="$attrs"
    />
</template>
