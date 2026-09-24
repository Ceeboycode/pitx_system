<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { PreviewCard } from '@/components/ui/_preview-card';

type Role = {
    id: number;
    name: string;
};

type Employee = {
    id: number;
    username: string;
    name: string;
    avatar?: string | null;
    email?: string | null;
    phone_number?: string | null;
    status: string;
    created_at?: string | null;
    roles?: Role[];
};

const props = defineProps<{
    employee: Employee;
}>();

defineEmits<{ close: [] }>();

function humanize(value?: string | null) {
    if (!value) return '—';
    return value.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
}

function formatDate(value?: string | null) {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
}

function roleName() {
    return props.employee.roles?.[0]?.name ?? '—';
}

function roleClass(role?: string | null) {
    const value = String(role ?? '').toLowerCase();
    if (value === 'driver') return 'border-sky-200 bg-sky-100 text-sky-700';
    if (value === 'dispatcher') return 'border-violet-200 bg-violet-100 text-violet-700';
    if (value === 'conductor') return 'border-teal-200 bg-teal-100 text-teal-700';
    if (value === 'inspector') return 'border-orange-200 bg-orange-100 text-orange-700';
    return 'border-custom-bg-dark bg-custom-bg text-custom-shadow dark:border-custom-bg-light dark:bg-custom-bg-light';
}

function statusVariant(status?: string | null) {
    if (status === 'active') return 'success';
    if (status === 'pending') return 'warning';
    if (status === 'suspended') return 'orange';
    return 'muted';
}

function statusDotClass(status?: string | null) {
    if (status === 'active') return 'bg-emerald-500 animate-pulse';
    if (status === 'pending') return 'bg-amber-500';
    if (status === 'suspended') return 'bg-orange-500';
    return 'bg-slate-400';
}

function initials(name: string) {
    return name.trim().split(/\s+/).slice(0, 2).map((part) => part[0]?.toUpperCase()).join('') || 'E';
}
</script>

<template>
    <PreviewCard :title="props.employee.name" description="Preview" title-class="capitalize" @close="$emit('close')">
        <div class="flex flex-col items-center gap-3 rounded-md border border-dashed border-custom-bg-dark bg-custom-bg p-4 dark:border-custom-bg-light dark:bg-custom-bg-dark">
            <img
                v-if="employee.avatar"
                :src="employee.avatar"
                :alt="`${employee.name} avatar`"
                class="h-20 w-20 rounded-full object-cover"
            />
            <div v-else class="flex h-20 w-20 items-center justify-center rounded-full bg-custom-secondary/20 text-xl font-semibold">
                {{ initials(employee.name) }}
            </div>
            <div class="min-w-0 text-center">
                <p class="truncate font-semibold text-custom-shadow">{{ employee.name }}</p>
                <p class="truncate font-mono text-sm text-custom-shadow/70">{{ employee.username }}</p>
            </div>
        </div>

        <div class="space-y-3">
            <div class="flex items-center justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Status</span>
                <Badge :variant="statusVariant(employee.status)" class="gap-1.5">
                    <span :class="['h-1.5 w-1.5 rounded-full', statusDotClass(employee.status)]" />
                    {{ humanize(employee.status) }}
                </Badge>
            </div>
            <div class="flex items-center justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Role</span>
                <Badge :class="roleClass(roleName())" class="border capitalize">{{ humanize(roleName()) }}</Badge>
            </div>
            <div class="flex items-start justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Email</span>
                <span class="min-w-0 truncate text-right text-sm text-custom-shadow/80">{{ employee.email || '—' }}</span>
            </div>
            <div class="flex items-start justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Phone</span>
                <span class="text-right text-sm text-custom-shadow/80">{{ employee.phone_number || '—' }}</span>
            </div>
            <div class="flex items-start justify-between gap-3">
                <span class="text-sm font-semibold text-custom-shadow">Created</span>
                <span class="text-right text-sm text-custom-shadow/80">{{ formatDate(employee.created_at) }}</span>
            </div>
        </div>
    </PreviewCard>
</template>
