<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Building2 } from 'lucide-vue-next';

const props = withDefaults(
    defineProps<{
        src?: string | null;
        alt: string;
        initials?: string | null;
        imageClass?: string;
        fallbackClass?: string;
        iconClass?: string;
        textClass?: string;
    }>(),
    {
        src: null,
        initials: null,
        imageClass: 'block h-full w-full object-contain p-1.5',
        fallbackClass:
            'flex h-full w-full items-center justify-center bg-white text-muted-foreground',
        iconClass: 'h-4 w-4 text-muted-foreground/80',
        textClass: 'select-none text-sm font-bold tracking-wide',
    },
);

const imgError = ref(false);

watch(
    () => props.src,
    () => {
        imgError.value = false;
    },
);

const showImage = computed(() => !!props.src && !imgError.value);
</script>

<template>
    <div
        class="flex items-center justify-center overflow-hidden border border-border bg-white"
    >
        <img
            v-if="showImage"
            :src="src!"
            :alt="alt"
            :class="imageClass"
            @error="imgError = true"
        />

        <div v-else :class="fallbackClass">
            <span v-if="initials" :class="textClass">
                {{ initials }}
            </span>

            <Building2 v-else :class="iconClass" />
        </div>
    </div>
</template>
