<script setup lang="ts">
import { CalendarDate } from '@internationalized/date';
import { index } from '@/actions/App/Http/Controllers/AuditLogController';
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Calendar as CalendarPicker } from '@/components/ui/calendar';
import {
    Card,
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Popover,
    PopoverTrigger,
    PopoverContent
} from '@/components/ui/popover';
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
    Calendar,
    Eye,
    Filter,
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

const popoverFromOpen = ref(false);
const popoverToOpen = ref(false);

function parseDateString(str: string): CalendarDate | undefined {
    if (!str) return undefined;
    const [year, month, day] = str.split('-').map(Number);
    if (isNaN(year) || isNaN(month) || isNaN(day)) return undefined;
    return new CalendarDate(year, month, day);
}

function formatDateDisplay(str: string): string {
    if (!str) return '';
    const d = new Date(str + 'T00:00:00');
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

const calendarDateFrom = ref<CalendarDate | undefined>(
    parseDateString(props.filters.date_from ?? ''),
);
const calendarDateTo = ref<CalendarDate | undefined>(
    parseDateString(props.filters.date_to ?? ''),
);

function onCalendarFromChange(val: CalendarDate | undefined) {
    calendarDateFrom.value = val;
    dateFrom.value = val
        ? `${val.year}-${String(val.month).padStart(2, '0')}-${String(val.day).padStart(2, '0')}`
        : '';
    popoverFromOpen.value = false;
    applyFilters();
}

function onCalendarToChange(val: CalendarDate | undefined) {
    calendarDateTo.value = val;
    dateTo.value = val
        ? `${val.year}-${String(val.month).padStart(2, '0')}-${String(val.day).padStart(2, '0')}`
        : '';
    popoverToOpen.value = false;
    applyFilters();
}

function onDateFromTextChange() {
    calendarDateFrom.value = parseDateString(dateFrom.value);
    applyFilters();
}

const hasCategoryFilters = computed(
    () =>
        (actionFilter.value && actionFilter.value !== 'all') ||
        (userFilter.value && userFilter.value !== 'all') ||
        (entityTypeFilter.value && entityTypeFilter.value !== 'all')
);

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
        <div class="flex h-full min-h-0 w-full flex-1 flex-col gap-4">
            <Card class="min-h-0 min-w-0 flex-1 lg:h-full">
                <CardHeader class="flex flex-row gap-2">
                    <div class="flex flex-col">
                    <CardTitle class="flex items-center gap-2">
                        <span class="font-semibold">Audit Logs</span>
                    </CardTitle>
                    <CardDescription>
                        Track internal actions, approvals, and authentication events.
                    </CardDescription>
                    </div>
                </CardHeader>
                <CardContent class="flex min-h-0 flex-1 flex-col space-y-4 py-2">
                    <div class="flex flex-row gap-2 lg:items-center lg:justify-between">
                        <div class="w-full">
                            <SearchInput
                                :route="index().url"
                                :initial-value="filters.search"
                                placeholder="Search my activity…"
                                :only="[
                                    'auditLogs',
                                    'filters',
                                    'actions',
                                    'entityTypes',
                                    'flash',
                                ]"
                                :debounce="350"
                            />
                        </div>
                        <div class="flex w-fit flex-row gap-2 lg:items-center lg:justify-between">
                            <div class="flex flex-row items-center gap-2">
                                <Popover>
                                    <PopoverTrigger as-child>
                                        <Button
                                            variant="header-actions"
                                            size="icon-text"
                                            class="rounded-full"
                                            :class="
                                                hasCategoryFilters
                                                    ? 'bg-custom-secondary/20 hover:bg-custom-secondary/80 hover:text-custom-bg-light transition-all duration-300 dark:hover:text-custom-shadow'
                                                    : ''
                                            "
                                        >
                                            <Filter class="h-3.5 w-3.5" />
                                            {{
                                                hasCategoryFilters
                                                    ? 'Filters Active'
                                                    : 'Filters'
                                            }}
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent
                                        align="start"
                                    >
                                        <div class="grid gap-y-2">
                                            <div class="space-y-2">
                                                <p
                                                    class="text-sm text-custom-shadow/80"
                                                >
                                                    Action
                                                </p>
                                                <Select
                                                    :model-value="actionFilter"
                                                    @update:model-value="onActionChange"
                                                >
                                                    <SelectTrigger
                                                        class="w-full"
                                                    >
                                                        <SelectValue
                                                            placeholder="All Actions"
                                                            class="flex justify-start"
                                                        />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="all" class="cursor-pointer text-sm">
                                                            All Actions
                                                        </SelectItem>
                                                        <SelectItem
                                                            v-for="action in actions"
                                                            :key="action.value"
                                                            :value="action.value"
                                                            class="cursor-pointer text-sm">
                                                            {{ action.label }}
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div class="space-y-2">
                                                <p
                                                    class="text-sm text-custom-shadow/80"
                                                >
                                                    User
                                                </p>
                                                <Select
                                                    :model-value="userFilter"
                                                    @update:model-value="onUserChange"
                                                >
                                                    <SelectTrigger
                                                        class="w-full"
                                                    >
                                                        <SelectValue
                                                            placeholder="All Users"
                                                            class="flex justify-start"
                                                        />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="all" class="cursor-pointer text-sm">
                                                            All Users
                                                        </SelectItem>
                                                        <SelectItem
                                                            v-for="user in users"
                                                            :key="user.id"
                                                            :value="String(user.id)"
                                                            class="cursor-pointer text-sm"
                                                        >
                                                            {{ user.name }}
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div class="space-y-2">
                                                <p
                                                    class="text-sm text-custom-shadow/80"
                                                >
                                                    Entity Type
                                                </p>
                                                <Select
                                                    :model-value="entityTypeFilter"
                                                    @update:model-value="onEntityTypeChange"
                                                >
                                                    <SelectTrigger
                                                        class="w-full"
                                                    >
                                                        <SelectValue
                                                            placeholder="All Types"
                                                            class="flex justify-start"
                                                        />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="all" class="cursor-pointer text-sm">
                                                            All Entities
                                                        </SelectItem>
                                                        <SelectItem
                                                            v-for="entity in entityTypes"
                                                            :key="entity.value"
                                                            :value="entity.value"
                                                        >
                                                            {{ entity.label }}
                                                        </SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                            <div class="flex justify-end">
                                                <Button
                                                    v-if="hasCategoryFilters"
                                                    size="sm"
                                                    variant="destructive"
                                                    @click="clearFilters"
                                                >
                                                    <X class="mr-1 h-3.5 w-3.5" />
                                                    Clear filters
                                                </Button>
                                            </div>
                                        </div>
                                    </PopoverContent>
                                </Popover>
                                <Popover v-model:open="popoverFromOpen">
                                    <PopoverTrigger as-child class="h-full">
                                        <Button
                                            variant="header-actions"
                                            size="icon-text"
                                            class="rounded-full gap-2"
                                        >
                                            <Calendar class="h-4 w-4 shrink-0" />
                                            <span class="text-sm">
                                                {{ dateFrom ? formatDateDisplay(dateFrom) : 'From date' }}
                                            </span>
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent
                                        align="start"
                                    >
                                        <p class="mb-2 text-xs font-semibold tracking-widest text-muted-foreground uppercase">
                                            From Date
                                        </p>
                                        <CalendarPicker
                                            v-model="calendarDateFrom"
                                            @update:model-value="onCalendarFromChange"
                                            class="px-0 pb-0"
                                        />
                                    </PopoverContent>
                                </Popover>
                                <Popover v-model:open="popoverToOpen">
                                    <PopoverTrigger as-child class="h-full">
                                        <Button
                                            variant="header-actions"
                                            size="icon-text"
                                            class="rounded-full gap-2"
                                        >
                                            <Calendar class="h-4 w-4 shrink-0" />
                                            <span class="text-sm">
                                                {{ dateTo ? formatDateDisplay(dateTo) : 'To date' }}
                                            </span>
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent
                                        align="start"
                                    >
                                        <p class="mb-2 text-xs font-semibold tracking-widest text-muted-foreground uppercase">
                                            To Date
                                        </p>
                                        <CalendarPicker
                                            v-model="calendarDateTo"
                                            @update:model-value="onCalendarToChange"
                                            class="px-0 pb-0"
                                        />
                                    </PopoverContent>
                                </Popover>
                            </div>
                        </div>
                    </div>
                    <Card
                        :class="[
                            'flex min-h-0 flex-1 max-h-fit flex-col overflow-hidden border border-custom-bg-dark py-0 shadow-none dark:border-custom-bg-light dark:inset-shadow-none',
                            auditLogs.data.length === 0 ? 'border-dashed' : 'border-solid',
                        ]"
                    >
                    <div class="no-scrollbar min-h-0 flex-1 overflow-auto">
                        <Table>
                            <TableHeader
                                v-if="auditLogs.data.length > 0"
                                class="border-b border-custom-bg-dark dark:border-custom-bg-light"
                            >
                                <TableRow>
                                    <TableHead class="px-0 text-[11px] font-semibold tracking-widest text-custom-shadow/80 uppercase">User</TableHead>
                                    <TableHead class="px-0 text-[11px] font-semibold tracking-widest text-custom-shadow/80 uppercase">Action</TableHead>
                                    <TableHead class="px-0 text-[11px] font-semibold tracking-widest text-custom-shadow/80 uppercase">Entity</TableHead>
                                    <TableHead class="px-0 w-56 text-[11px] font-semibold tracking-widest text-custom-shadow/80 uppercase">Changes</TableHead>
                                    <TableHead class="px-0 text-[11px] font-semibold tracking-widest text-custom-shadow/80 uppercase">Timestamp</TableHead>
                                    <TableHead class="px-0 w-30 text-right text-[11px] font-semibold tracking-widest text-custom-shadow/80 uppercase"
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
                                        class="py-20 text-center"
                                    >
                                        <div
                                            class="flex flex-col items-center gap-3"
                                        >
                                            <img
                                                :src="emptyRafikiUrl"
                                                alt=""
                                                class="w-32 object-contain opacity-90"
                                                aria-hidden="true"
                                            />
                                            <div>
                                                <p
                                                    class="text-sm font-semibold text-foreground"
                                                >
                                                    No audit logs found
                                                </p>
                                                <p
                                                    class="mt-0.5 text-xs text-muted-foreground"
                                                >
                                                    {{
                                                        hasActiveFilters
                                                            ? 'Try adjusting your filters or search.'
                                                            : 'Try adjusting your search.'
                                                    }}
                                                </p>
                                            </div>
                                            <Button
                                                v-if="hasActiveFilters"
                                                size="sm"
                                                variant="destructive"
                                                @click="clearFilters"
                                            >
                                                <X class="mr-1.5 h-3.5 w-3.5" />
                                                Clear filters
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <TableRow
                                    v-for="log in auditLogs.data"
                                    :key="log.id"
                                    class="group border-b border-custom-bg-dark text-custom-shadow/80 transition-colors hover:bg-custom-secondary/10 hover:text-custom-shadow dark:border-custom-bg-light"
                                >
                                    <TableCell class="px-0">
                                        <div class="text-sm font-medium">
                                            {{ log.user?.name ?? 'System' }}
                                        </div>
                                        <div
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ log.user?.email ?? '—' }}
                                        </div>
                                    </TableCell>

                                    <TableCell class="px-0">
                                        <Badge
                                            :class="
                                                actionBadgeClass(log.action)
                                            "
                                        >
                                            {{ log.action_label }}
                                        </Badge>
                                    </TableCell>

                                    <TableCell class="px-0">
                                        <div class="text-sm font-medium">
                                            {{ log.entity_label }}
                                        </div>
                                        <div
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ log.entity_name ?? '—' }}
                                        </div>
                                    </TableCell>

                                    <TableCell class="px-0 w-56 max-w-56">
                                        <div class="space-y-1">
                                            <div
                                                v-for="change in log.changes.slice(
                                                    0,
                                                    1,
                                                )"
                                                :key="`${log.id}-${change.field}`"
                                                class="text-xs text-muted-foreground break-words whitespace-normal"
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
                                                    log.changes.length - 1
                                                }}
                                                more
                                            </div>
                                        </div>
                                    </TableCell>

                                    <TableCell class="px-0">
                                        <div class="text-sm">
                                            {{ log.created_at_human ?? '—' }}
                                        </div>
                                        <div
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ log.created_at ?? '—' }}
                                        </div>
                                    </TableCell>

                                    <TableCell class="text-right px-0">
                                        <Dialog>
                                            <DialogTrigger as-child>
                                                <Button
                                                    variant="table-more"
                                                    size="icon-more"
                                                >
                                                    <Eye class="h-4 w-4" />
                                                </Button>
                                            </DialogTrigger>

                                            <DialogContent class="max-w-2xl max-h-70vh rounded-lg p-4">
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
                                                                class="font-semibold text-foreground"
                                                                >IP:</span
                                                            >
                                                            {{
                                                                log.ip_address ??
                                                                '—'
                                                            }}
                                                        </div>
                                                        <div>
                                                            <span
                                                                class="font-semibold text-foreground"
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
                                                                class="font-semibold text-foreground"
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
                                                            class="text-xs font-semibold tracking-wide uppercase"
                                                        >
                                                            Changed fields:
                                                        </p>
                                                        <div
                                                            class="space-y-2 p-2 mt-2 border-t border-slate-200 overflow-y-auto h-60"
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
                    </Card>

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
