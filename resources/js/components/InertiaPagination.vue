<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';

defineProps<{
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
}>();
</script>

<template>
    <div class="flex items-center gap-1">
        <template v-for="(link, i) in links" :key="i">
            <Button
                v-if="link.url"
                as-child
                size="sm"
                :variant="link.active ? 'default' : 'outline'"
            >
                <Link :href="link.url" v-html="link.label" />
            </Button>

            <span
                v-else
                class="px-2 text-sm text-muted-foreground"
                v-html="link.label"
            />
        </template>
    </div>
</template>
