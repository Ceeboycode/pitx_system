<script setup lang="ts">
import { computed, ref } from 'vue';

import { InputMessage } from '@/components/ui/_input-message';
import { CheckboxInput } from '@/components/ui/_checkbox';
import { Button } from '@/components/ui/button';
import {
    RiArrowDownSLine,
    RiArrowRightSLine,
    RiCheckboxMultipleBlankLine,
    RiCheckboxMultipleLine,
} from 'vue-remix-icons';

import { actionLabel, groupPermissions, moduleLabel, permissionsOfType, type Permission } from '@/lib/permissions';

/**
 * The permission checkboxes of a role, grouped by module. Only the permissions of the given role
 * type are listed, so the parent keeps the selection clean when the type changes.
 * Used by the role Details tab and the Create page: `v-model` is the array of selected permission ids.
 */
const selectedIds = defineModel<number[]>({ default: () => [] });

const props = defineProps<{
    permissions: Permission[];
    type: string;
    /** View-only: the boxes show the selection but cannot be changed. */
    disabled?: boolean;
    error?: string;
}>();

const typePermissions = computed(() => permissionsOfType(props.permissions, props.type));
const groupedPermissions = computed(() => groupPermissions(typePermissions.value));
const allIds = computed(() => typePermissions.value.map((p) => p.id));

const allChecked = computed(() => allIds.value.length > 0 && allIds.value.every((id) => selectedIds.value.includes(id)));

const collapsedModules = ref<Record<string, boolean>>({});

function toggleCollapse(moduleKey: string) {
    collapsedModules.value[moduleKey] = !collapsedModules.value[moduleKey];
}

function isCollapsed(moduleKey: string) {
    return collapsedModules.value[moduleKey] ?? false;
}

function toggleAll(checked: boolean) {
    selectedIds.value = checked ? [...allIds.value] : [];
}

function togglePermission(permissionId: number, checked: boolean) {
    selectedIds.value = checked
        ? [...new Set([...selectedIds.value, permissionId])]
        : selectedIds.value.filter((id) => id !== permissionId);
}

function moduleIds(moduleKey: string) {
    const list = groupedPermissions.value.find(([key]) => key === moduleKey)?.[1] ?? [];

    return list.map((p) => p.id);
}

function moduleChecked(moduleKey: string) {
    const ids = moduleIds(moduleKey);

    return ids.length > 0 && ids.every((id) => selectedIds.value.includes(id));
}

function moduleSomeChecked(moduleKey: string) {
    return moduleIds(moduleKey).some((id) => selectedIds.value.includes(id)) && !moduleChecked(moduleKey);
}

function moduleSelectedCount(moduleKey: string) {
    return moduleIds(moduleKey).filter((id) => selectedIds.value.includes(id)).length;
}

function toggleModule(moduleKey: string, checked: boolean) {
    const ids = moduleIds(moduleKey);

    selectedIds.value = checked
        ? [...new Set([...selectedIds.value, ...ids])]
        : selectedIds.value.filter((id) => !ids.includes(id));
}
</script>

<template>
    <div class="my-2 flex flex-col gap-4">
        <div v-if="!props.disabled" class="flex items-center justify-between">
            <Button type="button" variant="float" @click="toggleAll(!allChecked)">
                <RiCheckboxMultipleBlankLine v-if="allChecked" class="h-4 w-4 shrink-0" />
                <RiCheckboxMultipleLine v-else class="h-4 w-4 shrink-0" />
                <span>{{ allChecked ? 'Deselect all' : 'Select all' }}</span>
            </Button>
        </div>

        <InputMessage variant="destructive" v-if="!props.disabled" :message="props.error" />

        <div class="flex flex-col gap-2">
            <p v-if="groupedPermissions.length === 0" class="py-6 text-center text-sm text-custom-shadow">
                No {{ props.type }} permissions found.
            </p>

            <div v-for="[moduleKey, perms] in groupedPermissions" :key="moduleKey" class="overflow-hidden rounded-md">
                <div
                    class="flex w-full cursor-pointer items-center justify-between gap-3 rounded-md bg-custom-bg dark:bg-custom-bg-light/60 px-3 py-2 transition-colors hover:bg-custom-secondary/10"
                    @click="toggleCollapse(moduleKey)"
                >
                    <div class="flex items-center gap-2">
                        <CheckboxInput
                            :model-value="moduleChecked(moduleKey)"
                            :indeterminate="moduleSomeChecked(moduleKey)"
                            :disabled="props.disabled"
                            :aria-label="`Select all ${moduleLabel(moduleKey)} permissions`"
                            @click.stop
                            @update:model-value="toggleModule(moduleKey, $event)"
                        />
                        <button
                            type="button"
                            class="flex cursor-pointer items-start gap-2 text-left"
                            :aria-expanded="!isCollapsed(moduleKey)"
                        >
                            <span class="text-sm font-semibold">
                                {{ moduleLabel(moduleKey) }}
                            </span>
                            <span class="text-sm">
                                {{ moduleSelectedCount(moduleKey) }}/{{ perms.length }} selected
                            </span>
                        </button>
                    </div>

                    <RiArrowDownSLine v-if="!isCollapsed(moduleKey)" class="h-4 w-4 shrink-0 text-custom-shadow" />
                    <RiArrowRightSLine v-else class="h-4 w-4 shrink-0 text-custom-shadow" />
                </div>

                <div v-if="!isCollapsed(moduleKey)" class="grid gap-2 pt-2 sm:grid-cols-2 lg:grid-cols-4 pb-2">
                    <label
                        v-for="p in perms"
                        :key="p.id"
                        class="flex items-start gap-2 rounded-md border px-3 py-2 transition-colors"
                        :class="[
                            props.disabled ? 'cursor-default' : 'cursor-pointer hover:bg-custom-secondary/10',
                            selectedIds.includes(p.id) ? 'border-transparent bg-custom-secondary/10 dark:bg-custom-secondary/20' : 'hover:border-transparent border-custom-bg-dark dark:border-custom-bg-light',
                        ]"
                    >
                        <div class="inline-flex h-full items-start">
                            <CheckboxInput
                                :model-value="selectedIds.includes(p.id)"
                                :disabled="props.disabled"
                                @update:model-value="togglePermission(p.id, $event)"
                                class="mt-0.5"
                            />
                        </div>
                        <div class="flex min-w-0 flex-1 flex-col gap-0">
                            <span class="text-sm break-words">
                                {{ actionLabel(p.name) }}
                            </span>
                            <!-- <span class="break-words text-sm text-custom-shadow/80">
                                {{ p.name }}
                            </span> -->
                        </div>
                    </label>
                </div>
            </div>
        </div>
    </div>
</template>
