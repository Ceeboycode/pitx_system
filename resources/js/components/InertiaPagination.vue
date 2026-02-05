<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
    meta?: {
        from: number | null;
        to: number | null;
        total: number;
    };
}>();

const summary = computed(() => {
    if (!props.meta) return null;
    const from = props.meta.from ?? 0;
    const to = props.meta.to ?? 0;
    const total = props.meta.total ?? 0;
    return `Showing ${from} to ${to} of ${total} entries`;
});
</script>

<template>
    <div
        class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
    >
        <p v-if="summary" class="text-sm text-muted-foreground">
            {{ summary }}
        </p>

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
    </div>
</template>
