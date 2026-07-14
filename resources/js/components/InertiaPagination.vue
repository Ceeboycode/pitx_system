<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { RiArrowLeftDoubleLine, RiArrowRightDoubleLine } from 'vue-remix-icons';

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

const previousLink = computed(() => props.links[0] ?? null);
const nextLink = computed(() => props.links[props.links.length - 1] ?? null);
const pageLinks = computed(() => props.links.slice(1, -1));
</script>

<template>
    <div
        class="flex flex-col justify-center gap-2 lg:flex-row lg:items-center lg:justify-between"
    >
        <p v-if="summary" class="text-sm text-custom-shadow flex justify-center">
            {{ summary }}
        </p>

        <div class="inline-flex w-fit overflow-hidden rounded-full bg-custom-bg dark:bg-custom-bg-light mx-auto lg:mr-0">
            <Button
                as-child
                size="icon"
                :variant="previousLink?.url ? 'segmented' : 'disabled'"
                class="rounded-none"
            >
                <Link
                    :href="previousLink?.url ?? '#'"
                    preserve-scroll
                    @click="!previousLink?.url && $event.preventDefault()"
                >
                    <RiArrowLeftDoubleLine class="h-4 w-4" />
                </Link>
            </Button>

            <template v-for="(link, i) in pageLinks" :key="i">
                <Button
                    v-if="link.url"
                    as-child
                    size="default"
                    variant="segmented"
                    :data-active="link.active"
                    class="px-0"
                >
                    <Link
                        :href="link.url"
                        preserve-scroll
                        v-html="link.label"
                    />
                </Button>

                <span
                    v-else
                    class="inline-flex size-9 items-center justify-center text-sm text-custom-shadow/80"
                    v-html="link.label"
                />
            </template>

            <Button
                as-child
                size="icon"
                :variant="nextLink?.url ? 'segmented' : 'disabled'"
                class="rounded-none"
            >
                <Link
                    :href="nextLink?.url ?? '#'"
                    preserve-scroll
                    @click="!nextLink?.url && $event.preventDefault()"
                >
                    <RiArrowRightDoubleLine class="h-4 w-4" />
                </Link>
            </Button>
        </div>
    </div>
</template>
