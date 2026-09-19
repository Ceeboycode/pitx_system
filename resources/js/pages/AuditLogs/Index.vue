<script setup lang="ts">
import { CalendarDate } from '@internationalized/date';
import { index } from '@/actions/App/Http/Controllers/AuditLogController';
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Calendar as CalendarPicker } from '@/components/ui/calendar';
import { Input } from '@/components/ui/input';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import {
    RiCalendarLine,
    RiCloseLine,
    RiFilter2Line,
} from 'vue-remix-icons';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { PanelLayout, MainPanel, SidePanel } from '@/components/ui/_panels';
import {
    Table,
    TableColumn,
    TableHeader,
    TableContent,
    TableRow,
    TableCard,
    TableData,
} from '@/components/ui/_table';

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
const pendingActionFilter = ref(actionFilter.value);
const pendingUserFilter = ref(userFilter.value);
const pendingEntityTypeFilter = ref(entityTypeFilter.value);
const filterOpen = ref(false);
const dateFrom = ref<string>(props.filters.date_from ?? '');
const dateTo = ref<string>(props.filters.date_to ?? '');
const sortDir = ref<SortDir>('desc');
const previewedLog = ref<AuditLogRow | null>(null);

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
    popoverFromOpen.value = false;
    applyFilters();
}

function onDateToTextChange() {
    calendarDateTo.value = parseDateString(dateTo.value);
    popoverToOpen.value = false;
    applyFilters();
}

const hasCategoryFilters = computed(
    () =>
        (actionFilter.value && actionFilter.value !== 'all') ||
        (userFilter.value && userFilter.value !== 'all') ||
        (entityTypeFilter.value && entityTypeFilter.value !== 'all')
);

const activeFilterCount = computed(() => {
    let count = 0;
    if (actionFilter.value !== 'all') count++;
    if (userFilter.value !== 'all') count++;
    if (entityTypeFilter.value !== 'all') count++;
    return count;
});

const hasActiveFilters = computed(
    () =>
        actionFilter.value !== 'all' ||
        userFilter.value !== 'all' ||
        entityTypeFilter.value !== 'all' ||
        !!dateFrom.value ||
        !!dateTo.value ||
        !!props.filters.search,
);

function currentFilterParams(): Record<string, string | undefined> {
    return {
        action: actionFilter.value !== 'all' ? actionFilter.value : undefined,
        user_id:
            userFilter.value !== 'all' ? userFilter.value : undefined,
        entity_type:
            entityTypeFilter.value !== 'all'
                ? entityTypeFilter.value
                : undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
        sort_dir: sortDir.value,
    };
}

function applyFilters(
    overrides: Record<string, string | number | undefined> = {},
) {
    const payload: Record<string, string | number | undefined> = {
        search: props.filters.search ?? undefined,
        ...currentFilterParams(),
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
    pendingActionFilter.value = 'all';
    pendingUserFilter.value = 'all';
    pendingEntityTypeFilter.value = 'all';
    dateFrom.value = '';
    dateTo.value = '';

    applyFilters({
        action: undefined,
        user_id: undefined,
        entity_type: undefined,
        date_from: undefined,
        date_to: undefined,
    });
    filterOpen.value = false;
}

function applyFilterPopover() {
    actionFilter.value = pendingActionFilter.value;
    userFilter.value = pendingUserFilter.value;
    entityTypeFilter.value = pendingEntityTypeFilter.value;
    applyFilters();
    filterOpen.value = false;
}

function cancelFilterPopover() {
    pendingActionFilter.value = actionFilter.value;
    pendingUserFilter.value = userFilter.value;
    pendingEntityTypeFilter.value = entityTypeFilter.value;
    filterOpen.value = false;
}

function openPreview(log: AuditLogRow) {
    previewedLog.value = log;
}

function selectAdjacentLog(direction: 1 | -1) {
    if (!previewedLog.value) return;

    const list = props.auditLogs.data;
    const currentIndex = list.findIndex((l) => l.id === previewedLog.value?.id);
    const nextIndex = currentIndex + direction;
    if (currentIndex === -1 || nextIndex < 0 || nextIndex >= list.length) return;

    openPreview(list[nextIndex]);
}

function handleRowNavigationKeydown(event: KeyboardEvent) {
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        selectAdjacentLog(1);
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        selectAdjacentLog(-1);
    }
}

onMounted(() => window.addEventListener('keydown', handleRowNavigationKeydown));
onUnmounted(() => window.removeEventListener('keydown', handleRowNavigationKeydown));

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
        <PanelLayout>
            <MainPanel>
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
                    <CardContent class="flex min-h-0 flex-1 flex-col space-y-4 pt-2">
                        <div class="flex flex-row gap-2 lg:items-center lg:justify-between">
                            <div class="w-full">
                                <SearchInput
                                    :route="index().url"
                                    :initial-value="filters.search"
                                    placeholder="Search audit logs..."
                                    :only="[
                                        'auditLogs',
                                        'filters',
                                        'actions',
                                        'entityTypes',
                                        'flash',
                                    ]"
                                    :debounce="350"
                                    :extra-params="currentFilterParams"
                                />
                            </div>
                            <div class="flex w-fit flex-row gap-2 lg:items-center lg:justify-between">
                                <div class="flex flex-row items-center gap-2">
                                    <Popover v-model:open="filterOpen">
                                        <PopoverTrigger as-child>
                                            <Button
                                                variant="header-actions"
                                                size="icon-text"
                                                class="rounded-full"
                                                :class="
                                                    activeFilterCount > 0
                                                        ? 'bg-custom-secondary/20 hover:bg-custom-secondary/80 hover:text-custom-bg-light transition-all duration-200 dark:hover:text-custom-shadow'
                                                        : ''
                                                "
                                            >
                                                <RiFilter2Line class="h-3.5 w-3.5" />
                                                <span class="hidden lg:flex">
                                                    {{ activeFilterCount > 0
                                                        ? (activeFilterCount === 1 ? '1 filter active' : `${activeFilterCount} filters active`)
                                                        : 'Filter' }}
                                                </span>
                                            </Button>
                                        </PopoverTrigger>
                                        <PopoverContent
                                            align="end"
                                        >
                                            <div class="grid gap-y-2">
                                                <div class="flex flex-col gap-y-1">
                                                    <p
                                                        class="text-sm text-custom-shadow/80"
                                                    >
                                                        Action
                                                    </p>
                                                    <Select v-model="pendingActionFilter">
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

                                                <div class="flex flex-col gap-y-1">
                                                    <p
                                                        class="text-sm text-custom-shadow/80"
                                                    >
                                                        Entity Type
                                                    </p>
                                                    <Select v-model="pendingEntityTypeFilter">
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
                                                <hr class="my-1 h-px border-0 bg-custom-bg-dark dark:bg-custom-bg-light">
                                                <div class="flex w-full items-center justify-between">
                                                    <Button
                                                        v-if="hasCategoryFilters"
                                                        size="sm"
                                                        variant="destructive"
                                                        @click="clearFilters"
                                                    >
                                                        Clear
                                                    </Button>
                                                    <div class="ml-auto flex items-center gap-2">
                                                        <Button variant="ghost-outline" size="sm" @click="cancelFilterPopover">
                                                            Cancel
                                                        </Button>
                                                        <Button variant="float-primary" size="sm" @click="applyFilterPopover">
                                                            Apply
                                                        </Button>
                                                    </div>
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
                                                <RiCalendarLine class="h-4 w-4 shrink-0" />
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
                                            <Input
                                                v-model="dateFrom"
                                                type="text"
                                                placeholder="YYYY-MM-DD"
                                                class="mb-2"
                                                @keydown.enter="onDateFromTextChange"
                                            />
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
                                                <RiCalendarLine class="h-4 w-4 shrink-0" />
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
                                            <Input
                                                v-model="dateTo"
                                                type="text"
                                                placeholder="YYYY-MM-DD"
                                                class="mb-2"
                                                @keydown.enter="onDateToTextChange"
                                            />
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
                        <TableCard :table-data-length="auditLogs.data.length">
                            <Table v-if="auditLogs.data.length > 0">
                                <TableHeader hide-actions-column>
                                    <TableColumn>User</TableColumn>
                                    <TableColumn>Action</TableColumn>
                                    <TableColumn>Entity</TableColumn>
                                    <TableColumn>Timestamp</TableColumn>
                                </TableHeader>

                                <TableContent>
                                    <TableRow
                                        v-for="(log, rowIndex) in auditLogs.data"
                                        :key="log.id"
                                        :class="[
                                            rowIndex === auditLogs.data.length - 1 ? 'rounded-b-md border-b-0' : '',
                                            previewedLog?.id === log.id ? 'bg-custom-secondary/10' : '',
                                        ]"
                                        @click.left="openPreview(log)"
                                    >
                                        <TableData class="pl-3">
                                            <div class="min-w-0">
                                                <div class="truncate text-sm font-medium">
                                                    {{ log.user?.name ?? 'System' }}
                                                </div>
                                                <div class="truncate text-xs text-muted-foreground">
                                                    {{ log.user?.email ?? '—' }}
                                                </div>
                                            </div>
                                        </TableData>

                                        <TableData>
                                            <Badge
                                                :class="
                                                    actionBadgeClass(log.action)
                                                "
                                            >
                                                {{ log.action_label }}
                                            </Badge>
                                        </TableData>

                                        <TableData>
                                            <div class="min-w-0">
                                                <div class="truncate text-sm font-medium">
                                                    {{ log.entity_label }}
                                                </div>
                                                <div class="truncate text-xs text-muted-foreground">
                                                    {{ log.entity_name ?? '—' }}
                                                </div>
                                            </div>
                                        </TableData>

                                        <TableData>
                                            <div class="min-w-0">
                                                <div class="truncate text-sm">
                                                    {{ log.created_at_human ?? '—' }}
                                                </div>
                                                <div class="truncate text-xs text-muted-foreground">
                                                    {{ log.created_at ?? '—' }}
                                                </div>
                                            </div>
                                        </TableData>
                                    </TableRow>
                                </TableContent>
                            </Table>

                            <div v-else class="flex min-h-0 flex-1 items-center justify-center p-6 text-center">
                                <div class="flex w-full max-w-md flex-col items-center justify-center gap-2">
                                    <img
                                        :src="emptyRafikiUrl"
                                        alt=""
                                        class="w-1/3 object-contain opacity-90"
                                        aria-hidden="true"
                                    />
                                    <div class="space-y-1">
                                        <p class="text-base font-semibold text-custom-shadow">No audit logs found</p>
                                        <p class="text-sm text-custom-shadow/80">
                                            {{ hasActiveFilters ? 'Try adjusting your filters or search.' : 'Try adjusting your search.' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </TableCard>

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
            </MainPanel>
            
            <SidePanel>
                <Card class="hidden min-h-0 lg:flex lg:h-full lg:w-full">
                    <CardHeader
                        v-if="previewedLog"
                        class="flex flex-row items-start justify-between gap-3"
                    >
                        <div class="min-w-0">
                            <CardTitle class="truncate">
                                {{ previewedLog.action_label }}
                            </CardTitle>
                            <CardDescription>Preview</CardDescription>
                        </div>
                        <Button
                            variant="header-actions"
                            size="icon"
                            class="h-8 w-8 shrink-0 rounded-full"
                            aria-label="Close audit log preview"
                            @click="previewedLog = null"
                        >
                            <RiCloseLine class="h-4 w-4" />
                        </Button>
                    </CardHeader>

                    <CardContent
                        v-if="previewedLog"
                        class="no-scrollbar min-h-0 flex-1 space-y-4 overflow-y-auto py-2"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm font-semibold text-custom-shadow">Action</span>
                            <Badge :class="actionBadgeClass(previewedLog.action)">
                                {{ previewedLog.action_label }}
                            </Badge>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <span class="text-sm font-semibold text-custom-shadow">Entity</span>
                            <div class="min-w-0 text-right">
                                <p class="truncate text-sm text-custom-shadow/80">{{ previewedLog.entity_label }}</p>
                                <p class="truncate text-xs text-custom-shadow/60">{{ previewedLog.entity_name ?? '—' }}</p>
                            </div>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <span class="text-sm font-semibold text-custom-shadow">Timestamp</span>
                            <div class="text-right">
                                <p class="text-sm text-custom-shadow/80">{{ previewedLog.created_at_human ?? '—' }}</p>
                                <p class="text-xs text-custom-shadow/60">{{ previewedLog.created_at ?? '—' }}</p>
                            </div>
                        </div>

                        <hr class="my-4 h-px border-0 bg-custom-bg-dark dark:bg-custom-bg-light">

                        <div class="space-y-2">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-sm font-semibold text-custom-shadow">Changed fields</span>
                                <span class="text-sm text-custom-shadow/80">{{ previewedLog.changes.length }}</span>
                            </div>
                            <div v-if="previewedLog.changes.length" class="space-y-2">
                                <div
                                    v-for="change in previewedLog.changes"
                                    :key="`${previewedLog.id}-preview-${change.field}`"
                                    class="rounded-md bg-custom-bg px-3 py-2 dark:bg-custom-bg-dark"
                                >
                                    <p class="text-sm font-medium text-custom-shadow">{{ change.label }}</p>
                                    <p class="break-words text-xs text-custom-shadow/70">
                                        {{ formatValue(change.old) }} → {{ formatValue(change.new) }}
                                    </p>
                                </div>
                            </div>
                            <p v-else class="rounded-md bg-custom-bg px-3 py-2 text-sm text-custom-shadow/70 dark:bg-custom-bg-dark">
                                No field-level changes recorded.
                            </p>
                        </div>

                        <hr class="my-4 h-px border-0 bg-custom-bg-dark dark:bg-custom-bg-light">

                        <div class="space-y-2 text-sm text-custom-shadow/80">
                            <div class="flex justify-between gap-3">
                                <span class="font-semibold text-custom-shadow">IP</span>
                                <span>{{ previewedLog.ip_address ?? '—' }}</span>
                            </div>
                            <div class="flex justify-between gap-3">
                                <span class="font-semibold text-custom-shadow">Method</span>
                                <span>{{ previewedLog.request_method ?? '—' }}</span>
                            </div>
                            <div class="space-y-1">
                                <span class="font-semibold text-custom-shadow">URL</span>
                                <p class="break-all text-xs">{{ previewedLog.request_url ?? '—' }}</p>
                            </div>
                        </div>
                    </CardContent>

                    <CardContent v-else class="flex min-h-0 flex-1 items-center justify-center">
                        <div class="max-w-60 space-y-1 text-center">
                            <p class="text-base font-semibold text-custom-shadow">No audit log selected</p>
                            <p class="text-sm text-custom-shadow/80">Click on a log to preview.</p>
                        </div>
                    </CardContent>
                </Card>
            </SidePanel>
        </PanelLayout>
    </AppLayout>
</template>
