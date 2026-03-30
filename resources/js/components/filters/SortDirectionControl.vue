<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { SortOption } from '@/components/filters/types';
import { ArrowDownNarrowWide, ArrowUpNarrowWide } from 'lucide-vue-next';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        options: readonly SortOption[];
        value: string;
        direction: 'asc' | 'desc' | string;
        label?: string;
    }>(),
    {
        label: 'Sort by',
    },
);

const emit = defineEmits<{
    select: [value: string];
    toggleDirection: [];
}>();

const currentLabel = computed(
    () => props.options.find((option) => option.value === props.value)?.label ?? props.label,
);
</script>

<template>
    <div class="inline-flex overflow-hidden rounded-md border bg-background shadow-xs">
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button
                    size="sm"
                    variant="ghost"
                    class="min-w-8 rounded-none border-0 px-2.5 shadow-none"
                >
                    {{ currentLabel }}
                </Button>
            </DropdownMenuTrigger>

            <DropdownMenuContent align="start">
                <DropdownMenuLabel>{{ label }}</DropdownMenuLabel>
                <DropdownMenuSeparator />

                <DropdownMenuItem
                    v-for="option in options"
                    :key="option.value"
                    class="cursor-pointer"
                    :class="option.value === value ? 'font-medium text-foreground' : ''"
                    @click="emit('select', option.value)"
                >
                    {{ option.label }}
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>

        <Button
            size="sm"
            variant="ghost"
            class="rounded-none border-0 border-l px-2.5 shadow-none"
            @click="emit('toggleDirection')"
        >
            <component
                :is="direction === 'asc' ? ArrowUpNarrowWide : ArrowDownNarrowWide"
                class="h-4 w-4"
            />
            <span class="sr-only">
                {{ direction === 'asc' ? 'Ascending' : 'Descending' }}
            </span>
        </Button>
    </div>
</template>
