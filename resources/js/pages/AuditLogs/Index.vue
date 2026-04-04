<script setup lang="ts">
import { index } from '@/actions/App/Http/Controllers/AuditLogController';
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import {
    FileSearch,
    Filter,
    ShieldCheck,
    SlidersHorizontal,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

type SortDir = 'asc' | 'desc';

interface AuditLogUser {
    id: number;
    name: string;
    email: string;
}

interface ChangeLine {
    field: string;
    label: string;
    old: unknown;
    new: unknown;
}

interface AuditLogRow {
    id: number;
    user: AuditLogUser | null;
    action: string;
    action_label: string;
    entity_type: string | null;
    entity_label: string;
    entity_name: string | null;
    changes: ChangeLine[];
    metadata: Record<string, unknown> | null;
    ip_address: string | null;
    request_method: string | null;
    request_url: string | null;
    created_at: string | null;
    created_at_human: string | null;
}

interface EntityTypeOption {
    value: string;
    label: string;
}

interface ActionOption {
    value: string;
    label: string;
}

const props = defineProps<{
    auditLogs: {
        data: AuditLogRow[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: {
        search: string | null;
        action: string | null;
        user_id: number | null;
        entity_type: string | null;
        date_from: string | null;
        date_to: string | null;
    };
    actions: ActionOption[];
    users: Array<{ id: number; name: string }>;
    entityTypes: EntityTypeOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Audit Logs', href: index().url },
];

const actionFilter = ref<string>(props.filters.action ?? 'all');
const userFilter = ref<string>(
    props.filters.user_id ? String(props.filters.user_id) : 'all',
);
const entityTypeFilter = ref<string>(props.filters.entity_type ?? 'all');
const dateFrom = ref<string>(props.filters.date_from ?? '');
const dateTo = ref<string>(props.filters.date_to ?? '');
const sortDir = ref<SortDir>('desc');

const hasActiveFilters = computed(
    () =>
        actionFilter.value !== 'all' ||
        userFilter.value !== 'all' ||
        entityTypeFilter.value !== 'all' ||
        !!dateFrom.value ||
        !!dateTo.value ||
        !!props.filters.search,
);

function applyFilters(
    overrides: Record<string, string | number | undefined> = {},
) {
    const payload: Record<string, string | number | undefined> = {
        search: props.filters.search ?? undefined,
        action: actionFilter.value !== 'all' ? actionFilter.value : undefined,
        user_id:
            userFilter.value !== 'all' ? Number(userFilter.value) : undefined,
        entity_type:
            entityTypeFilter.value !== 'all'
                ? entityTypeFilter.value
                : undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
        sort_dir: sortDir.value,
        ...overrides,
    };

    router.get(index().url, payload, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: [
            'auditLogs',
            'filters',
            'actions',
            'entityTypes',
            'users',
            'flash',
        ],
    });
}

function clearFilters() {
    actionFilter.value = 'all';
    userFilter.value = 'all';
    entityTypeFilter.value = 'all';
    dateFrom.value = '';
    dateTo.value = '';

    applyFilters({
        action: undefined,
        user_id: undefined,
        entity_type: undefined,
        date_from: undefined,
        date_to: undefined,
    });
}

function onActionChange(value: string) {
    actionFilter.value = value;
    applyFilters();
}

function onUserChange(value: string) {
    userFilter.value = value;
    applyFilters();
}

function onEntityTypeChange(value: string) {
    entityTypeFilter.value = value;
    applyFilters();
}

function formatValue(value: unknown): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    if (typeof value === 'boolean') {
        return value ? 'Yes' : 'No';
    }

    if (Array.isArray(value) || typeof value === 'object') {
        return JSON.stringify(value);
    }

    return String(value);
}

function hasDisplayValue(value: unknown): boolean {
    const formatted = formatValue(value);
    return formatted !== '—';
}

function actionBadgeClass(action: string): string {
    if (action === 'created')
        return 'border-emerald-200 bg-emerald-100 text-emerald-700';
    if (action === 'updated')
        return 'border-blue-200 bg-blue-100 text-blue-700';
    if (action === 'deleted')
        return 'border-rose-200 bg-rose-100 text-rose-700';
    if (action.startsWith('auth.'))
        return 'border-amber-200 bg-amber-100 text-amber-700';

    return 'border-slate-200 bg-slate-100 text-slate-600';
}
</script>

<template>
    <Head title="Audit Logs" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Card class="mx-5">
                <CardHeader>
                    <div>
                        <CardTitle class="flex items-center gap-2">
                            <ShieldCheck class="h-5 w-5 text-blue-700" />
                            Audit Logs
                        </CardTitle>

                        <CardDescription class="mt-1">
                            Track internal actions, approvals, and
                            authentication events.
                        </CardDescription>
                    </div>

                    <CardAction>
                        <div
                            class="flex items-center gap-2 rounded-lg border px-3 py-2 text-xs text-muted-foreground"
                        >
                            <SlidersHorizontal class="h-4 w-4" />
                            {{
                                hasActiveFilters
                                    ? 'Filtered view'
                                    : 'Live activity feed'
                            }}
                        </div>
                    </CardAction>
                </CardHeader>

                <CardContent class="space-y-4">
                    <div class="grid grid-cols-1 gap-3 lg:grid-cols-6">
                        <div class="lg:col-span-2">
                            <SearchInput
                                :route="index().url"
                                :initial-value="filters.search"
                                placeholder="Search action, user, or entity"
                                :only="[
                                    'auditLogs',
                                    'filters',
                                    'actions',
                                    'entityTypes',
                                    'users',
                                    'flash',
                                ]"
                            />
                        </div>

                        <Select
                            :model-value="actionFilter"
                            @update:model-value="onActionChange"
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Action" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All actions</SelectItem>
                                <SelectItem
                                    v-for="action in actions"
                                    :key="action.value"
                                    :value="action.value"
                                >
                                    {{ action.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <Select
                            :model-value="userFilter"
                            @update:model-value="onUserChange"
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="User" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All users</SelectItem>
                                <SelectItem
                                    v-for="user in users"
                                    :key="user.id"
                                    :value="String(user.id)"
                                >
                                    {{ user.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <Select
                            :model-value="entityTypeFilter"
                            @update:model-value="onEntityTypeChange"
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Entity" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all"
                                    >All entities</SelectItem
                                >
                                <SelectItem
                                    v-for="entity in entityTypes"
                                    :key="entity.value"
                                    :value="entity.value"
                                >
                                    {{ entity.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <div class="flex items-center gap-2">
                            <Input
                                v-model="dateFrom"
                                type="date"
                                class="w-full"
                                @change="applyFilters()"
                            />
                            <Input
                                v-model="dateTo"
                                type="date"
                                class="w-full"
                                @change="applyFilters()"
                            />
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div
                            class="flex items-center gap-2 text-xs text-muted-foreground"
                        >
                            <Filter class="h-4 w-4" />
                            <span v-if="hasActiveFilters"
                                >Filters are active</span
                            >
                            <span v-else>Showing latest audit activity</span>
                        </div>

                        <Button
                            v-if="hasActiveFilters"
                            variant="outline"
                            size="sm"
                            @click="clearFilters"
                        >
                            <X class="mr-1 h-4 w-4" />
                            Clear filters
                        </Button>
                    </div>

                    <div class="overflow-x-auto rounded-xl border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>User</TableHead>
                                    <TableHead>Action</TableHead>
                                    <TableHead>Entity</TableHead>
                                    <TableHead>Changes</TableHead>
                                    <TableHead>Timestamp</TableHead>
                                    <TableHead class="w-30 text-right"
                                        >Details</TableHead
                                    >
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <TableRow
                                    v-if="auditLogs.data.length === 0"
                                    class="hover:bg-transparent"
                                >
                                    <TableCell
                                        colspan="6"
                                        class="py-14 text-center"
                                    >
                                        <div
                                            class="flex flex-col items-center gap-2 text-muted-foreground"
                                        >
                                            <FileSearch class="h-6 w-6" />
                                            <p
                                                class="text-sm font-medium text-foreground"
                                            >
                                                No audit logs found
                                            </p>
                                            <p class="text-xs">
                                                Try adjusting filters or check
                                                activity later.
                                            </p>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <TableRow
                                    v-for="log in auditLogs.data"
                                    :key="log.id"
                                >
                                    <TableCell>
                                        <div class="text-sm font-medium">
                                            {{ log.user?.name ?? 'System' }}
                                        </div>
                                        <div
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ log.user?.email ?? '—' }}
                                        </div>
                                    </TableCell>

                                    <TableCell>
                                        <Badge
                                            :class="
                                                actionBadgeClass(log.action)
                                            "
                                        >
                                            {{ log.action_label }}
                                        </Badge>
                                    </TableCell>

                                    <TableCell>
                                        <div class="text-sm font-medium">
                                            {{ log.entity_label }}
                                        </div>
                                        <div
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ log.entity_name ?? '—' }}
                                        </div>
                                    </TableCell>

                                    <TableCell>
                                        <div class="space-y-1">
                                            <div
                                                v-for="change in log.changes.slice(
                                                    0,
                                                    2,
                                                )"
                                                :key="`${log.id}-${change.field}`"
                                                class="text-xs text-muted-foreground"
                                            >
                                                <span
                                                    class="font-medium text-foreground"
                                                    >{{ change.label }}:</span
                                                >
                                                {{ formatValue(change.old) }} to
                                                {{ formatValue(change.new) }}
                                            </div>
                                            <div
                                                v-if="log.changes.length > 2"
                                                class="text-xs text-muted-foreground"
                                            >
                                                +{{
                                                    log.changes.length - 2
                                                }}
                                                more
                                            </div>
                                        </div>
                                    </TableCell>

                                    <TableCell>
                                        <div class="text-sm">
                                            {{ log.created_at_human ?? '—' }}
                                        </div>
                                        <div
                                            class="text-xs text-muted-foreground uppercase"
                                        >
                                            {{ log.request_method ?? 'N/A' }}
                                        </div>
                                    </TableCell>

                                    <TableCell class="text-right">
                                        <Dialog>
                                            <DialogTrigger as-child>
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    >View</Button
                                                >
                                            </DialogTrigger>

                                            <DialogContent class="max-w-2xl">
                                                <DialogHeader>
                                                    <DialogTitle>{{
                                                        log.action_label
                                                    }}</DialogTitle>
                                                    <DialogDescription>
                                                        {{
                                                            log.entity_label
                                                        }}
                                                        activity record
                                                    </DialogDescription>
                                                </DialogHeader>

                                                <div class="space-y-4 text-sm">
                                                    <div
                                                        class="grid grid-cols-2 gap-4 text-xs text-muted-foreground"
                                                    >
                                                        <div>
                                                            <span
                                                                class="font-medium text-foreground"
                                                                >IP:</span
                                                            >
                                                            {{
                                                                log.ip_address ??
                                                                '—'
                                                            }}
                                                        </div>
                                                        <div>
                                                            <span
                                                                class="font-medium text-foreground"
                                                                >Method:</span
                                                            >
                                                            {{
                                                                log.request_method ??
                                                                '—'
                                                            }}
                                                        </div>
                                                        <div
                                                            class="col-span-2 break-all"
                                                        >
                                                            <span
                                                                class="font-medium text-foreground"
                                                                >URL:</span
                                                            >
                                                            {{
                                                                log.request_url ??
                                                                '—'
                                                            }}
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <p
                                                            class="mb-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                                                        >
                                                            Changed fields
                                                        </p>
                                                        <div
                                                            class="space-y-2 rounded-lg border p-3"
                                                        >
                                                            <div
                                                                v-if="
                                                                    log.changes
                                                                        .length ===
                                                                    0
                                                                "
                                                                class="text-xs text-muted-foreground"
                                                            >
                                                                No field-level
                                                                changes
                                                                recorded.
                                                            </div>
                                                            <div
                                                                v-for="change in log.changes"
                                                                :key="`${log.id}-detail-${change.field}`"
                                                                class="text-xs"
                                                            >
                                                                <p
                                                                    class="font-medium text-foreground"
                                                                >
                                                                    {{
                                                                        change.label
                                                                    }}
                                                                </p>
                                                                <p
                                                                    v-if="
                                                                        hasDisplayValue(
                                                                            change.old,
                                                                        )
                                                                    "
                                                                    class="text-muted-foreground"
                                                                >
                                                                    Old:
                                                                    {{
                                                                        formatValue(
                                                                            change.old,
                                                                        )
                                                                    }}
                                                                </p>
                                                                <p
                                                                    v-if="
                                                                        hasDisplayValue(
                                                                            change.new,
                                                                        )
                                                                    "
                                                                    class="text-muted-foreground"
                                                                >
                                                                    New:
                                                                    {{
                                                                        formatValue(
                                                                            change.new,
                                                                        )
                                                                    }}
                                                                </p>
                                                                <p
                                                                    v-if="
                                                                        !hasDisplayValue(
                                                                            change.old,
                                                                        ) &&
                                                                        !hasDisplayValue(
                                                                            change.new,
                                                                        )
                                                                    "
                                                                    class="text-muted-foreground"
                                                                >
                                                                    No visible
                                                                    value
                                                                    changes
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </DialogContent>
                                        </Dialog>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <InertiaPagination
                        :links="auditLogs.links"
                        :meta="{
                            from: auditLogs.from,
                            to: auditLogs.to,
                            total: auditLogs.total,
                        }"
                    />
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
