<script setup lang="ts">
import { InputMessage } from '@/components/ui/_input-message';
import { Badge } from '@/components/ui/badge';
import { PreviewCard, PreviewCardRow } from '@/components/ui/_preview-card';
import { RiShieldCheckLine } from 'vue-remix-icons';

type Role = {
    id: number;
    name: string;
    type: 'internal' | 'external' | string;
    permissions?: { id?: number; name: string }[];
    created_at_human?: string | null;
    updated_at_human?: string | null;
    creator?: { name?: string | null } | null;
    updater?: { name?: string | null } | null;
    deleted_at_human?: string | null;
    deleter?: { name?: string | null } | null;
};

const props = defineProps<{
    role: Role;
    archived?: boolean;
}>();

defineEmits<{ close: [] }>();

function typeClass(type: Role['type']): string {
    return type === 'internal'
        ? 'bg-blue-100 text-blue-700 border-blue-200'
        : 'bg-violet-100 text-violet-700 border-violet-200';
}
</script>

<template>
    <PreviewCard
        :title="props.role.name"
        :description="props.archived ? 'Archived preview' : 'Preview'"
        title-class="capitalize"
        @close="$emit('close')"
    >
        <div class="flex items-center justify-center rounded-md border border-dashed border-custom-bg-dark bg-custom-bg p-6 dark:border-custom-bg-light dark:bg-custom-bg-dark">
            <div class="flex h-20 w-20 items-center justify-center rounded-full bg-custom-primary/15 text-custom-primary">
                <RiShieldCheckLine class="h-9 w-9" />
            </div>
        </div>

        <div class="space-y-3">
            <div class="flex items-center justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Name</span>
                <span class="truncate text-right text-sm font-semibold capitalize text-custom-shadow/80">
                    {{ role.name }}
                </span>
            </div>
            <div class="flex items-center justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Type</span>
                <Badge :class="typeClass(role.type)" class="border capitalize">
                    {{ role.type }}
                </Badge>
            </div>

            <div class="space-y-2">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm font-semibold text-custom-shadow">Permissions</span>
                    <span class="text-sm text-custom-shadow/80">
                        {{ (role.permissions ?? []).length }}
                    </span>
                </div>
                <div v-if="(role.permissions ?? []).length" class="flex flex-wrap gap-1.5">
                    <span
                        v-for="permission in (role.permissions ?? [])"
                        :key="permission.id"
                        class="rounded-md bg-custom-bg px-2 py-1 font-mono text-xs text-custom-shadow/70 dark:bg-custom-bg-dark"
                    >
                        {{ permission.name }}
                    </span>
                </div>
                <InputMessage v-else class="mt-0" message="No permissions assigned." />
            </div>

            <div class="flex items-center justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Created</span>
                <span class="truncate text-right text-sm text-custom-shadow/80">
                    {{ role.created_at_human ?? '—' }}
                    <span class="text-custom-accent-3"> • </span>
                    {{ role.creator?.name ?? '—' }}
                </span>
            </div>
            <div class="flex items-center justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Updated</span>
                <span class="truncate text-right text-sm text-custom-shadow/80">
                    {{ role.updated_at_human ?? '—' }}
                    <span class="text-custom-accent-3"> • </span>
                    {{ role.updater?.name ?? '—' }}
                </span>
            </div>
        </div>

        <div v-if="props.archived" class="space-y-3 pt-1">
            <PreviewCardRow label="Archived">{{ props.role.deleted_at_human || '—' }}</PreviewCardRow>
            <PreviewCardRow v-if="props.role.deleter !== undefined" label="Archived By">{{ props.role.deleter?.name || '—' }}</PreviewCardRow>
        </div>
    </PreviewCard>
</template>
