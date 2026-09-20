<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { PreviewCard, PreviewCardRow } from '@/components/ui/_preview-card';

type Role = {
    id?: number;
    name: string;
    type: string;
};

type User = {
    id: number;
    username?: string | null;
    name: string;
    email?: string | null;
    email_verified_at?: string | null;
    phone_number?: string | null;
    avatar_url?: string | null;
    status?: 'active' | 'inactive' | string;
    company?: { company_name: string } | null;
    roles?: Role[];
    deleted_at_human?: string | null;
    deleter?: { name?: string | null } | null;
};

const props = defineProps<{
    user: User;
    archived?: boolean;
    /** Whether the viewer may see the super-admin role in the roles list. */
    canSeeSuperAdmin?: boolean;
}>();

defineEmits<{ close: [] }>();

function initials(name: string) {
    const parts = name.trim().split(/\s+/).filter(Boolean).slice(0, 2);

    return parts.map((part) => part.charAt(0).toUpperCase()).join('') || 'U';
}

function statusBadgeClass(status: string | undefined) {
    switch (status) {
        case 'active':
            return 'border-emerald-200 bg-emerald-100 text-emerald-700';
        case 'inactive':
            return 'border-red-200 bg-red-100 text-red-700';
        default:
            return 'bg-muted text-muted-foreground';
    }
}

function emailVerificationBadgeClass(emailVerifiedAt: string | null | undefined) {
    return emailVerifiedAt
        ? 'border-blue-200 bg-blue-100 text-blue-700'
        : 'border-amber-200 bg-amber-100 text-amber-700';
}

function emailVerificationLabel(emailVerifiedAt: string | null | undefined) {
    return emailVerifiedAt ? 'Verified' : 'Not Verified';
}

function roleBadgeClass(role: Role) {
    switch (role.type) {
        case 'internal':
            return 'border-blue-200 bg-blue-100 text-blue-700';
        case 'external':
            return 'border-emerald-200 bg-emerald-100 text-emerald-700';
        default:
            return 'bg-muted text-muted-foreground';
    }
}

const visibleRoles = computed(() => {
    const roles = props.user.roles ?? [];

    return props.canSeeSuperAdmin ? roles : roles.filter((role) => role.name !== 'super-admin');
});
</script>

<template>
    <PreviewCard
        :title="props.user.name"
        :description="props.archived ? 'Archived preview' : 'Preview'"
        title-class="capitalize"
        @close="$emit('close')"
    >
        <div class="flex flex-col items-center gap-3 rounded-md border border-dashed border-custom-bg-dark bg-custom-bg p-4 dark:border-custom-bg-light dark:bg-custom-bg-dark">
            <img
                v-if="user.avatar_url"
                :src="user.avatar_url"
                :alt="`${user.name} avatar`"
                class="h-20 w-20 rounded-full object-cover"
            />
            <div
                v-else
                class="flex h-20 w-20 items-center justify-center rounded-full bg-custom-primary text-xl font-semibold text-white"
            >
                {{ initials(user.name) }}
            </div>
            <div class="min-w-0 text-center">
                <p class="truncate font-semibold text-custom-shadow">
                    {{ user.name }}
                </p>
                <p class="truncate text-sm text-custom-shadow/70">
                    @{{ user.username }}
                </p>
            </div>
        </div>

        <div class="space-y-3">
            <div class="flex items-center justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Status</span>
                <Badge :class="statusBadgeClass(user.status)" class="border capitalize">
                    {{ user.status }}
                </Badge>
            </div>
            <div class="flex items-center justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Verification</span>
                <Badge :class="emailVerificationBadgeClass(user.email_verified_at)" class="border">
                    {{ emailVerificationLabel(user.email_verified_at) }}
                </Badge>
            </div>
            <div class="flex items-start justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Email</span>
                <span class="min-w-0 truncate text-right text-sm text-custom-shadow/80">
                    {{ user.email }}
                </span>
            </div>
            <div class="flex items-start justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Phone</span>
                <span class="text-right text-sm text-custom-shadow/80">
                    {{ user.phone_number ?? 'Not provided' }}
                </span>
            </div>
            <div class="flex items-start justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Company</span>
                <span class="min-w-0 truncate text-right text-sm text-custom-shadow/80">
                    {{ user.company?.company_name ?? 'Not assigned' }}
                </span>
            </div>

            <div v-if="props.user.roles" class="space-y-2">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm font-semibold text-custom-shadow">Roles</span>
                    <span class="text-sm text-custom-shadow/80">
                        {{ visibleRoles.length }}
                    </span>
                </div>
                <div v-if="visibleRoles.length" class="flex flex-wrap gap-1.5">
                    <Badge
                        v-for="role in visibleRoles"
                        :key="role.id"
                        :class="roleBadgeClass(role)"
                        class="border capitalize"
                    >
                        {{ role.name }}
                    </Badge>
                </div>
                <p v-else class="rounded-md bg-custom-bg px-3 py-2 text-sm text-custom-shadow/70 dark:bg-custom-bg-dark">
                    No roles assigned.
                </p>
            </div>
        </div>

        <div v-if="props.archived" class="space-y-3 pt-1">
            <PreviewCardRow label="Archived">{{ props.user.deleted_at_human || '—' }}</PreviewCardRow>
            <PreviewCardRow v-if="props.user.deleter !== undefined" label="Archived By">{{ props.user.deleter?.name || '—' }}</PreviewCardRow>
        </div>
    </PreviewCard>
</template>
