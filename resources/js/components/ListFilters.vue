<script setup lang="ts">
import FilterDropdownButton from '@/components/filters/FilterDropdownButton.vue';
import SearchInput from '@/components/SearchInput.vue';
import { Button } from '@/components/ui/button';
import type { SelectFilter } from '@/components/filters/types';
import { router } from '@inertiajs/vue3';
import { computed, ref, useSlots } from 'vue';
import { SlidersHorizontal } from 'lucide-vue-next';

const props = withDefaults(
    defineProps<{
        route: string;
        search?: string | null;
        searchPlaceholder?: string;
        only?: string[];
        debounce?: number;
        filters?: SelectFilter[];
        query?: Record<string, string | null | undefined>;
        mobileInlineActions?: boolean;
    }>(),
    {
        search: '',
        searchPlaceholder: 'Search...',
        debounce: 350,
        filters: () => [],
        query: () => ({}),
        mobileInlineActions: false,
    },
);

const slots = useSlots();
const mobileExpanded = ref(false);

const hasInlineActions = computed(
    () => Boolean(slots['inline-actions'] || slots.actions),
);
const hasPanelActions = computed(
    () => Boolean(slots['panel-actions'] || slots['collapsible-actions']),
);
const hasCollapsibleControls = computed(
    () => props.filters.length > 0 || hasPanelActions.value,
);
const showInlineActionsOnCompact = computed(
    () => hasInlineActions.value && (props.mobileInlineActions || !hasCollapsibleControls.value),
);

function buildToolbarQuery() {
    const data: Record<string, string | null> = {};

    for (const [key, value] of Object.entries(props.query)) {
        data[key] = value ?? null;
    }

    for (const filter of props.filters) {
        data[filter.key] = filter.value ?? null;
    }

    return data;
}

function buildRequestData(overrides: Record<string, string | null> = {}) {
    return {
        search: props.search ?? '',
        ...buildToolbarQuery(),
        ...overrides,
    };
}

function applyFilter(filter: SelectFilter, rawValue: string) {
    const allValue = filter.allValue ?? null;
    const nextValue = rawValue === 'all' ? allValue : rawValue;

    router.get(props.route, buildRequestData({ [filter.key]: nextValue }), {
        preserveScroll: true,
        only: props.only,
    });
}

const searchRoute = computed(() => {
    const params = new URLSearchParams();

    for (const [key, value] of Object.entries(buildToolbarQuery())) {
        if (!value) continue;
        params.set(key, value);
    }

    const query = params.toString();
    return query ? `${props.route}?${query}` : props.route;
});
</script>

<template>
    <div class="flex flex-col gap-3">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex w-full items-center gap-2">
                <div class="min-w-0 flex-1 lg:max-w-sm">
                    <SearchInput
                        :route="searchRoute"
                        :initial-value="props.search ?? ''"
                        :placeholder="props.searchPlaceholder"
                        :only="props.only"
                        :debounce="props.debounce"
                    />
                </div>

                <div
                    v-if="showInlineActionsOnCompact || hasCollapsibleControls"
                    class="flex shrink-0 items-center gap-2 lg:hidden"
                >
                    <Button
                        v-if="hasCollapsibleControls"
                        type="button"
                        size="icon"
                        variant="outline"
                        class="size-8 shrink-0"
                        @click="mobileExpanded = !mobileExpanded"
                    >
                        <SlidersHorizontal class="size-4" />
                        <span class="sr-only">
                            {{ mobileExpanded ? 'Hide filters and sorting' : 'Show filters and sorting' }}
                        </span>
                    </Button>

                    <div v-if="showInlineActionsOnCompact" class="flex items-center gap-2">
                        <slot name="inline-actions">
                            <slot name="actions" />
                        </slot>
                    </div>
                </div>

                <div class="hidden lg:flex lg:min-w-0 lg:flex-1 lg:items-center lg:gap-2">
                    <div
                        v-for="filter in props.filters"
                        :key="`desktop-${filter.key}`"
                        :class="['shrink-0', filter.desktopWidthClass]"
                        :style="{ maxWidth: filter.desktopMaxWidth }"
                    >
                        <FilterDropdownButton
                            :filter="filter"
                            @select="(value) => applyFilter(filter, value)"
                        />
                    </div>

                    <slot name="panel-actions">
                        <slot name="collapsible-actions" />
                    </slot>
                </div>
            </div>

            <div v-if="hasInlineActions" class="hidden lg:flex lg:shrink-0 lg:items-center lg:gap-2">
                <slot name="inline-actions">
                    <slot name="actions" />
                </slot>
            </div>
        </div>

        <div
            v-if="hasCollapsibleControls || (!props.mobileInlineActions && hasInlineActions)"
            class="flex flex-col gap-3 lg:hidden"
            :class="mobileExpanded ? 'flex' : 'hidden'"
        >
            <div class="flex w-full flex-col gap-3">
                <div
                    v-for="filter in props.filters"
                    :key="filter.key"
                    class="w-full"
                >
                    <FilterDropdownButton
                        :filter="filter"
                        @select="(value) => applyFilter(filter, value)"
                    />
                </div>
            </div>

            <div
                v-if="hasPanelActions || (!props.mobileInlineActions && hasInlineActions)"
                class="flex flex-wrap gap-2"
            >
                <slot name="panel-actions">
                    <slot name="collapsible-actions" />
                </slot>

                <div
                    v-if="!props.mobileInlineActions && hasInlineActions"
                    class="flex flex-wrap gap-2"
                >
                    <slot name="inline-actions">
                        <slot name="actions" />
                    </slot>
                </div>
            </div>
        </div>
    </div>
</template>
