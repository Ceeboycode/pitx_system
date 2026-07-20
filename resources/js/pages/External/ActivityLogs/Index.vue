<script setup lang="ts">
import { CalendarDate } from '@internationalized/date';
import { externalMyActivity } from '@/actions/App/Http/Controllers/AuditLogController';
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
import ExternalLayout from '@/layouts/ExternalLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import {
    RiCalendarLine as Calendar,
    RiCloseLine as X,
    RiFilter2Line as Filter,
} from 'vue-remix-icons';
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


const actionFilter = ref<string>(props.filters.action ?? 'all');
const entityTypeFilter = ref<string>(props.filters.entity_type ?? 'all');
const dateFrom = ref<string>(props.filters.date_from ?? '');
const dateTo = ref<string>(props.filters.date_to ?? '');
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
        externalMyActivity().url,
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

function openPreview(log: AuditLogRow) {
    previewedLog.value = log;
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
    
    <ExternalLayout>
        <div class="flex h-full min-h-0 w-full flex-1 flex-col gap-4 lg:flex-row lg:items-stretch">
            <Card class="min-h-0 min-w-0 flex-1 lg:h-full">
                <CardHeader class="flex flex-row gap-2">
                    <div class="flex flex-col">
                    <CardTitle class="flex items-center gap-2">
                        <span class="font-semibold">Activity Logs</span>
                    </CardTitle>
                    <CardDescription>
                        Review recent actions and account activity.
                    </CardDescription>
                    </div>
                </CardHeader>
                <CardContent class="flex min-h-0 flex-1 flex-col space-y-4 py-2">
                    <div class="flex flex-row gap-2 lg:items-center lg:justify-between">
                        <div class="w-full">
                            <SearchInput
                                :route="externalMyActivity().url"
                                :initial-value="filters.search"
                                placeholder="Search my activity..."
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
                                            <div class="flex flex-col gap-y-1">
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

                                            <div class="flex flex-col gap-y-1">
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
                    <div v-if="auditLogs.data.length > 0" class="flex min-h-0 flex-1 flex-col overflow-hidden">
                        <div class="shrink-0 rounded-t-md bg-custom-bg dark:bg-custom-bg-light">
                            <div class="grid grid-cols-4 gap-2 border-b border-custom-bg-dark dark:border-custom-bg-light">
                                <div class="flex h-10 items-center justify-start pl-3 text-xs font-semibold tracking-widest text-custom-shadow/80 uppercase">Action</div>
                                <div class="flex h-10 items-center justify-start text-xs font-semibold tracking-widest text-custom-shadow/80 uppercase">Entity</div>
                                <div class="flex h-10 items-center justify-start text-xs font-semibold tracking-widest text-custom-shadow/80 uppercase">Changes</div>
                                <div class="flex h-10 items-center justify-start pr-3 text-xs font-semibold tracking-widest text-custom-shadow/80 uppercase">Timestamp</div>
                            </div>
                        </div>

                        <div class="no-scrollbar min-h-0 flex-1 overflow-y-auto">
                                <div
                                    v-for="(log, index) in auditLogs.data"
                                    :key="log.id"
                                    :class="[
                                        'group grid cursor-pointer grid-cols-4 items-center gap-2 border-b border-custom-bg-dark text-custom-shadow/80 transition-colors hover:bg-custom-secondary/10 hover:text-custom-shadow dark:border-custom-bg-light',
                                        index === auditLogs.data.length - 1 ? 'rounded-b-md border-b-0' : '',
                                        previewedLog?.id === log.id ? 'bg-custom-secondary/10 text-custom-shadow' : '',
                                    ]"
                                    @click="openPreview(log)"
                                >
                                    <div class="py-1.5 pl-3">
                                        <Badge
                                            :class="
                                                actionBadgeClass(log.action)
                                            "
                                        >
                                            {{ log.action_label }}
                                        </Badge>
                                    </div>

                                    <div class="min-w-0 py-1.5">
                                        <div class="text-sm font-medium">
                                            {{ log.entity_label }}
                                        </div>
                                        <div
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ log.entity_name ?? '—' }}
                                        </div>
                                    </div>

                                    <div class="min-w-0 py-1.5">
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
                                    </div>

                                    <div class="min-w-0 py-1.5 pr-3">
                                        <div class="text-sm">
                                            {{ log.created_at_human ?? '—' }}
                                        </div>
                                        <div
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ log.created_at ?? '—' }}
                                        </div>
                                    </div>

                                </div>
                        </div>
                    </div>

                    <div v-else class="flex min-h-0 flex-1 items-center justify-center p-6 text-center">
                        <div class="flex w-full max-w-md flex-col items-center justify-center gap-2">
                            <img
                                :src="emptyRafikiUrl"
                                alt=""
                                class="w-1/3 object-contain opacity-90"
                                aria-hidden="true"
                            />
                            <div class="space-y-1">
                                <p class="text-base font-semibold text-custom-shadow">No activity logs found</p>
                                <p class="text-sm text-custom-shadow/80">
                                    {{ hasActiveFilters ? 'Try adjusting your filters or search.' : 'Try adjusting your search.' }}
                                </p>
                            </div>
                        </div>
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

            <Card class="hidden min-h-0 lg:flex lg:h-full lg:w-100">
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
                        aria-label="Close activity log preview"
                        @click="previewedLog = null"
                    >
                        <X class="h-4 w-4" />
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
                </CardContent>

                <CardContent v-else class="flex min-h-0 flex-1 items-center justify-center">
                    <div class="max-w-60 space-y-1 text-center">
                        <p class="text-base font-semibold text-custom-shadow">No activity log selected</p>
                        <p class="text-sm text-custom-shadow/80">Click on a log to preview.</p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </ExternalLayout>
</template>
