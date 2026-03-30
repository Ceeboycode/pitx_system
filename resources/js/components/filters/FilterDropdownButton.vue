<script setup lang="ts">
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import type { SelectFilter } from '@/components/filters/types';
import { ChevronDown } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    filter: SelectFilter;
}>();

const emit = defineEmits<{
    select: [value: string];
}>();

const selectedOption = computed(() =>
    props.filter.options.find((option) => option.value === props.filter.value),
);

const selectedLabel = computed(() => {
    if (!props.filter.value || props.filter.value === props.filter.allValue) {
        return props.filter.allLabel ?? 'All';
    }

    return selectedOption.value?.label ?? props.filter.placeholder;
});

function handleSelect(value: string) {
    emit('select', value);
}
</script>

<template>
    <div class="inline-flex w-full overflow-hidden rounded-md border bg-background shadow-xs">
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button
                    variant="ghost"
                    size="sm"
                    class="h-8 w-full justify-between rounded-none border-0 bg-transparent shadow-none"
                >
                    <span class="flex min-w-0 items-center gap-2">
                        <component
                            :is="selectedOption?.icon"
                            v-if="selectedOption?.icon"
                            class="size-4 shrink-0 text-muted-foreground"
                        />
                        <span class="truncate">{{ selectedLabel }}</span>
                    </span>
                    <ChevronDown class="size-4 shrink-0 text-muted-foreground" />
                </Button>
            </DropdownMenuTrigger>

            <DropdownMenuContent
                align="start"
                class="w-[var(--reka-dropdown-menu-trigger-width)] min-w-44"
            >
                <DropdownMenuLabel>{{ filter.placeholder }}</DropdownMenuLabel>
                <DropdownMenuSeparator />

                <DropdownMenuRadioGroup
                    :model-value="filter.value ?? 'all'"
                    @update:model-value="(value) => handleSelect(String(value))"
                >
                    <DropdownMenuRadioItem
                        v-if="filter.includeAllOption !== false"
                        value="all"
                    >
                        {{ filter.allLabel ?? 'All' }}
                    </DropdownMenuRadioItem>

                    <DropdownMenuRadioItem
                        v-for="option in filter.options"
                        :key="`${filter.key}-${option.value}`"
                        :value="option.value"
                    >
                        <div class="flex items-center gap-2">
                            <component
                                :is="option.icon"
                                v-if="option.icon"
                                class="size-4 shrink-0 text-muted-foreground"
                            />
                            <span>{{ option.label }}</span>
                        </div>
                    </DropdownMenuRadioItem>
                </DropdownMenuRadioGroup>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>
