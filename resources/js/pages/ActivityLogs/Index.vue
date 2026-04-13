<script setup lang="ts">
// TODO: BAKIT AUDIT LOGS T_T
import { CalendarDate } from '@internationalized/date';
import { myActivity } from '@/actions/App/Http/Controllers/AuditLogController';
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Calendar as CalendarPicker } from '@/components/ui/calendar';
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
import {
    Popover,
    PopoverTrigger,
    PopoverContent
} from '@/components/ui/popover';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import {
    Calendar,
    Eye,
    FileSearch,
    Filter,
    History,
    SlidersHorizontal,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface ChangeLine {
    field: string;
    label: string;
    old: unknown;
    new: unknown;
}

interface AuditLogRow {
    id: number;
    user: {
        id: number;
        name: string;
        email: string;
    } | null;
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

interface SelectOption {
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
        entity_type: string | null;
        date_from: string | null;
        date_to: string | null;
    };
    actions: SelectOption[];
    entityTypes: SelectOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'My Activity Logs', href: myActivity().url },
];

const actionFilter = ref<string>(props.filters.action ?? 'all');
const entityTypeFilter = ref<string>(props.filters.entity_type ?? 'all');
const dateFrom = ref<string>(props.filters.date_from ?? '');
const dateTo = ref<string>(props.filters.date_to ?? '');

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

const hasActiveFilters = computed(
    () =>
        actionFilter.value !== 'all' ||
        entityTypeFilter.value !== 'all' ||
        !!dateFrom.value ||
        !!dateTo.value ||
        !!props.filters.search,
);

const hasCategoryFilters = computed(
    () =>
        (actionFilter.value && actionFilter.value !== 'all') ||
        (entityTypeFilter.value && entityTypeFilter.value !== 'all')
);

function onActionChange(value: unknown) {
    actionFilter.value = typeof value === 'string' ? value : 'all';
    applyFilters();
}

function onEntityTypeChange(value: unknown) {
    entityTypeFilter.value = typeof value === 'string' ? value : 'all';
    applyFilters();
}

function applyFilters(overrides: Record<string, string | undefined> = {}) {
    router.get(
        myActivity().url,
        {
            search: props.filters.search ?? undefined,
            action:
                actionFilter.value !== 'all' ? actionFilter.value : undefined,
            entity_type:
                entityTypeFilter.value !== 'all'
                    ? entityTypeFilter.value
                    : undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
            ...overrides,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['auditLogs', 'filters', 'actions', 'entityTypes', 'flash'],
        },
    );
}

function clearFilters() {
    actionFilter.value = 'all';
    entityTypeFilter.value = 'all';
    dateFrom.value = '';
    dateTo.value = '';

    applyFilters({
        action: undefined,
        entity_type: undefined,
        date_from: undefined,
        date_to: undefined,
    });
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
    return formatValue(value) !== '—';
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
    <Head title="Activity Logs" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        Activity Logs
                        <div class="ml-2 flex flex-1 w-full items-center">
                            <hr
                                class="h-px w-full border border-rose-500"
                            />
                            <div
                                class="rounded-xs border-7 border-rose-500"
                            >
                                <div
                                    class="rounded-xs border-3 border-white"
                                ></div>
                            </div>
                        </div>
                    </CardTitle>
                    <CardDescription class="mt-1">
                        Review recent actions and account activity.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div
                        class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="w-50/100">
                            <SearchInput
                                :route="myActivity().url"
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
                                class="rounded-lg shadow-sm"
                            />
                        </div>
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between min-w-50/100">
                            <div class="flex flex-row items-center gap-2">
                                <Popover>
                                    <PopoverTrigger as-child class="cursor-pointer h-full w-fit rounded-lg border-slate-200 shadow-sm">
                                        <Button
                                            variant="outline"
                                            class="rounded-lg border-slate-200 px-3 text-slate-600 shadow-sm hover:bg-slate-100"
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
                                        class="w-80 rounded-lg border-slate-200 p-4 shadow-lg"
                                    >
                                        <div class="grid gap-y-4">
                                            <div class="space-y-2">
                                                <p
                                                    class="text-xs font-semibold tracking-widest text-muted-foreground uppercase"
                                                >
                                                    Action
                                                </p>
                                                <Select
                                                    :model-value="actionFilter"
                                                    @update:model-value="onActionChange"
                                                >
                                                    <SelectTrigger
                                                        class="cursor-pointer h-8 w-full rounded-lg border-slate-200 shadow-sm"
                                                    >
                                                        <SelectValue
                                                            placeholder="All Actions"
                                                            class="flex justify-start"
                                                        />
                                                    </SelectTrigger>
                                                    <SelectContent class="rounded-lg shadow-lg">
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
                                                    class="text-xs font-semibold tracking-widest text-muted-foreground uppercase"
                                                >
                                                    Entity Type
                                                </p>
                                                <Select
                                                    :model-value="entityTypeFilter"
                                                    @update:model-value="onEntityTypeChange"
                                                >
                                                    <SelectTrigger
                                                        class="cursor-pointer h-8 w-full rounded-lg border-slate-200 shadow-sm"
                                                    >
                                                        <SelectValue
                                                            placeholder="All Types"
                                                            class="flex justify-start"
                                                        />
                                                    </SelectTrigger>
                                                    <SelectContent class="rounded-lg shadow-lg">
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
                                                    variant="ghost"
                                                    class="h-8 rounded-lg px-2 text-xs text-muted-foreground hover:text-rose-600"
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
                                            variant="outline"
                                            class="rounded-lg border-slate-200 px-3 text-slate-600 shadow-sm hover:bg-slate-100 gap-2"
                                        >
                                            <Calendar class="h-4 w-4 shrink-0" />
                                            <span class="text-sm">
                                                {{ dateFrom ? formatDateDisplay(dateFrom) : 'From date' }}
                                            </span>
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent
                                        align="start"
                                        class="w-auto rounded-lg border-slate-200 shadow-lg"
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
                                            variant="outline"
                                            class="rounded-lg border-slate-200 px-3 text-slate-600 shadow-sm hover:bg-slate-100 gap-2"
                                        >
                                            <Calendar class="h-4 w-4 shrink-0" />
                                            <span class="text-sm">
                                                {{ dateTo ? formatDateDisplay(dateTo) : 'To date' }}
                                            </span>
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent
                                        align="start"
                                        class="w-auto rounded-lg border-slate-200 shadow-lg"
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
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader class="border-y border-slate-200">
                                <TableRow>
                                    <TableHead class="px-0 text-[11px] font-bold tracking-widest text-muted-foreground uppercase">Action</TableHead>
                                    <TableHead class="px-0 text-[11px] font-bold tracking-widest text-muted-foreground uppercase">Entity</TableHead>
                                    <TableHead class="px-0 w-56 text-[11px] font-bold tracking-widest text-muted-foreground uppercase">Changes</TableHead>
                                    <TableHead class="px-0 text-[11px] font-bold tracking-widest text-muted-foreground uppercase">Timestamp</TableHead>
                                    <TableHead class="px-0 w-30 text-right text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Details</TableHead
                                    >
                                </TableRow>
                            </TableHeader>

                            <TableBody class="border-y border-slate-200">
                                <TableRow
                                    v-if="auditLogs.data.length === 0"
                                    class="hover:bg-transparent"
                                >
                                    <TableCell
                                        colspan="5"
                                        class="py-20 text-center"
                                    >
                                        <div
                                            class="flex flex-col items-center gap-3"
                                        >
                                            <div
                                                class="flex h-14 w-14 items-center justify-center rounded-2xl bg-muted"
                                            >
                                                <History
                                                    class="h-6 w-6 text-muted-foreground/40"
                                                />
                                            </div>
                                            <div>
                                                <p
                                                    class="text-sm font-semibold text-foreground"
                                                >
                                                    No activity logs found
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
                                                variant="outline"
                                                class="mt-1 h-8 rounded-lg text-xs"
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
                                    class="group transition-colors hover:bg-muted/30"
                                >
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
                                                    2,
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
                                                    log.changes.length - 2
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
                                                    variant="outline"
                                                    class="cursor-pointer hover:bg-slate-100"
                                                >
                                                    <Eye class="h-4 w-4" />
                                                </Button>
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
