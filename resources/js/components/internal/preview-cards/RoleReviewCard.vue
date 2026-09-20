<script setup lang="ts">
import { InputMessage } from '@/components/ui/_input-message';
import { computed } from 'vue';

import CardSeparator from '@/components/ui/_card-separator/CardSeparator.vue';
import { PreviewCard, ReviewCardRow } from '@/components/ui/_preview-card';

import { actionLabel, groupPermissions, moduleLabel, type Permission } from '@/lib/permissions';

type RoleValues = {
    name: string;
    type: string;
};

/**
 * Always-open review of the role being created, shown in the SidePanel of Roles/Create.vue
 * so the values can be checked (and copied) while the form is filled in.
 */
const props = defineProps<{
    values: RoleValues;
    /** The permissions ticked so far. */
    permissions: Permission[];
    /** How many permissions the selected role type has in total. */
    total: number;
}>();

const groupedPermissions = computed(() => groupPermissions(props.permissions));
</script>

<template>
    <PreviewCard
        title="Review"
        description="Review new role details before confirming."
        :closable="false"
    >
        <CardSeparator title="Role Info" />

        <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
            <ReviewCardRow label="Name" :value="props.values.name" value-class="capitalize" />
            <ReviewCardRow label="Role Type" :value="props.values.type" value-class="capitalize" />
        </div>

        <CardSeparator title="Permissions" />

        <div class="my-2 flex flex-col gap-0.5 text-sm text-custom-shadow">
            <ReviewCardRow label="Selected" :value="`${props.permissions.length} of ${props.total}`" />
        </div>

        <div v-if="groupedPermissions.length" class="flex flex-col gap-3 text-sm text-custom-shadow">
            <div v-for="[moduleKey, perms] in groupedPermissions" :key="moduleKey" class="space-y-1">
                <p class="font-semibold">
                    {{ moduleLabel(moduleKey) }}
                    <span class="font-normal text-custom-shadow/70">{{ perms.length }}</span>
                </p>
                <div class="flex flex-wrap gap-1.5">
                    <span
                        v-for="permission in perms"
                        :key="permission.id"
                        class="rounded-md bg-custom-bg px-2 py-1 text-xs dark:bg-custom-bg-light"
                    >
                        {{ actionLabel(permission.name) }}
                    </span>
                </div>
            </div>
        </div>
        <InputMessage v-else class="mt-0" message="No permissions selected yet." />

        <p class="mt-4 text-sm text-custom-shadow/80">
            A role only holds permissions of its own type, so changing the role type clears the selection.
        </p>
    </PreviewCard>
</template>
