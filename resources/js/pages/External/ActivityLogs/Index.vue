<script setup lang="ts">
import { externalMyActivity } from '@/actions/App/Http/Controllers/AuditLogController';
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
import { Separator } from '@/components/ui/separator';
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
import ExternalLayout from '@/layouts/ExternalLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import {
    ArrowRight,
    CalendarDays,
    FileSearch,
    Filter,
    History,
    ShieldCheck,
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

const actionFilter     = ref<string>(props.filters.action      ?? 'all');
const entityTypeFilter = ref<string>(props.filters.entity_type ?? 'all');
const dateFrom         = ref<string>(props.filters.date_from   ?? '');
const dateTo           = ref<string>(props.filters.date_to     ?? '');

const hasActiveFilters = computed(() =>
    actionFilter.value !== 'all' ||
    entityTypeFilter.value !== 'all' ||
    !!dateFrom.value ||
    !!dateTo.value ||
    !!props.filters.search,
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
            search:      props.filters.search ?? undefined,
            action:      actionFilter.value !== 'all' ? actionFilter.value : undefined,
            entity_type: entityTypeFilter.value !== 'all' ? entityTypeFilter.value : undefined,
            date_from:   dateFrom.value || undefined,
            date_to:     dateTo.value   || undefined,
            ...overrides,
        },
        {
            preserveState:  true,
            preserveScroll: true,
            replace:        true,
            only: ['auditLogs', 'filters', 'actions', 'entityTypes', 'flash'],
        },
    );
}

function clearFilters() {
    actionFilter.value     = 'all';
    entityTypeFilter.value = 'all';
    dateFrom.value         = '';
    dateTo.value           = '';

    applyFilters({
        action:      undefined,
        entity_type: undefined,
        date_from:   undefined,
        date_to:     undefined,
    });
}

function formatValue(value: unknown): string {
    if (value === null || value === undefined || value === '') return '—';
    if (typeof value === 'boolean') return value ? 'Yes' : 'No';
    if (Array.isArray(value) || typeof value === 'object') return JSON.stringify(value);
    return String(value);
}

function actionBadgeClass(action: string): string {
    if (action === 'created')       return 'border-emerald-200 bg-emerald-50 text-emerald-700';
    if (action === 'updated')       return 'border-sky-200 bg-sky-50 text-sky-700';
    if (action === 'deleted')       return 'border-rose-200 bg-rose-50 text-rose-700';
    if (action.startsWith('auth.')) return 'border-amber-200 bg-amber-50 text-amber-700';
    return 'border-slate-200 bg-slate-50 text-slate-600';
}

function actionDot(action: string): string {
    if (action === 'created')       return 'bg-emerald-500';
    if (action === 'updated')       return 'bg-sky-500';
    if (action === 'deleted')       return 'bg-rose-500';
    if (action.startsWith('auth.')) return 'bg-amber-400';
    return 'bg-slate-400';
}
</script>

<template>
    <Head title="My Activity Logs" />

    <ExternalLayout>
        <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
            <Card>
                <CardHeader>
                    <div>
                        <CardTitle class="flex items-center gap-2 text-xl">
                            <History class="h-5 w-5 text-slate-600" />
                            My Activity
                        </CardTitle>
                        <CardDescription class="mt-1">
                            A full record of your recent account and operational actions.
                        </CardDescription>
                    </div>

                    <CardAction>
                        <div class="flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-500">
                            <ShieldCheck class="h-3.5 w-3.5" />
                            {{ auditLogs.total }} total records
                        </div>
                    </CardAction>
                </CardHeader>

                <CardContent class="space-y-4">

                    <!-- Filters row -->
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-5">
                        <!-- Search -->
                        <div class="lg:col-span-2">
                            <SearchInput
                                :route="externalMyActivity().url"
                                :initial-value="filters.search"
                                placeholder="Search activity…"
                                :only="['auditLogs', 'filters', 'actions', 'entityTypes', 'flash']"
                            />
                        </div>

                        <!-- Action filter -->
                        <Select :model-value="actionFilter" @update:model-value="onActionChange">
                            <SelectTrigger class="h-9 rounded-lg border-slate-200 text-sm cursor-pointer">
                                <SelectValue placeholder="All actions" />
                            </SelectTrigger>
                            <SelectContent class="rounded-xl">
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

                        <!-- Entity filter -->
                        <Select :model-value="entityTypeFilter" @update:model-value="onEntityTypeChange">
                                <SelectTrigger class="h-9 rounded-lg border-slate-200 text-sm cursor-pointer">
                                    <SelectValue placeholder="All entities" />
                                </SelectTrigger>
                            <SelectContent class="rounded-xl cursor-pointer">
                                <SelectItem value="all">All entities</SelectItem>
                                <SelectItem
                                    v-for="entity in entityTypes"
                                    :key="entity.value"
                                    :value="entity.value"
                                >
                                    {{ entity.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <!-- Date range -->
                        <div class="flex flex-col gap-1.5 lg:col-span-1">
                            <div class="relative">
                                <CalendarDays class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    v-model="dateFrom"
                                    type="date"
                                    class="h-9 w-full rounded-lg border-slate-200 pl-8 text-xs"
                                    @change="applyFilters()"
                                />
                            </div>
                            <div class="relative">
                                <CalendarDays class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    v-model="dateTo"
                                    type="date"
                                    class="h-9 w-full rounded-lg border-slate-200 pl-8 text-xs"
                                    @change="applyFilters()"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Filter status bar -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                            <Filter class="h-3.5 w-3.5" />
                            <span v-if="hasActiveFilters" class="font-medium text-slate-700">
                                Filters active
                            </span>
                            <span v-else>Showing all activity</span>
                        </div>

                        <Button
                            v-if="hasActiveFilters"
                            variant="ghost"
                            size="sm"
                            class="h-7 rounded-lg px-2 text-xs text-muted-foreground hover:text-foreground"
                            @click="clearFilters"
                        >
                            <X class="mr-1 h-3.5 w-3.5" />
                            Clear filters
                        </Button>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto rounded-lg border border-slate-200">
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-slate-50/80 hover:bg-slate-50/80">
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">
                                        Action
                                    </TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">
                                        Entity
                                    </TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">
                                        Summary
                                    </TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">
                                        When
                                    </TableHead>
                                    <TableHead class="text-right text-[11px] font-bold uppercase tracking-widest text-muted-foreground">
                                        Details
                                    </TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <!-- Empty state -->
                                <TableRow v-if="auditLogs.data.length === 0" class="hover:bg-transparent">
                                    <TableCell colspan="5" class="py-20 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100">
                                                <FileSearch class="h-6 w-6 text-slate-400" />
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-foreground">No activity found</p>
                                                <p class="mt-0.5 text-xs text-muted-foreground">
                                                    {{ hasActiveFilters ? 'Try adjusting your filters.' : 'No activity has been recorded yet.' }}
                                                </p>
                                            </div>
                                            <Button
                                                v-if="hasActiveFilters"
                                                variant="outline"
                                                size="sm"
                                                class="rounded-lg text-xs"
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
                                    class="transition-colors hover:bg-slate-50/60"
                                >
                                    <!-- Action badge -->
                                    <TableCell>
                                        <Badge :class="['gap-1.5 text-xs', actionBadgeClass(log.action)]">
                                            <span :class="['h-1.5 w-1.5 rounded-full', actionDot(log.action)]" />
                                            {{ log.action_label }}
                                        </Badge>
                                    </TableCell>

                                    <!-- Entity -->
                                    <TableCell>
                                        <div class="text-sm font-medium">{{ log.entity_label }}</div>
                                        <div class="mt-0.5 text-xs text-muted-foreground">
                                            {{ log.entity_name ?? '—' }}
                                        </div>
                                    </TableCell>

                                    <!-- Summary -->
                                    <TableCell class="max-w-xs">
                                        <div v-if="log.changes.length > 0" class="space-y-1">
                                            <div
                                                v-for="change in log.changes.slice(0, 2)"
                                                :key="`${log.id}-${change.field}`"
                                                class="flex flex-wrap items-center gap-1 text-xs text-muted-foreground"
                                            >
                                                <span class="font-medium text-slate-700">{{ change.label }}:</span>
                                                <span class="line-through opacity-60">{{ formatValue(change.old) }}</span>
                                                <ArrowRight class="h-3 w-3 opacity-40" />
                                                <span class="font-medium text-slate-700">{{ formatValue(change.new) }}</span>
                                            </div>
                                            <div v-if="log.changes.length > 2" class="text-[11px] text-muted-foreground">
                                                +{{ log.changes.length - 2 }} more changes
                                            </div>
                                        </div>
                                        <span v-else class="text-xs text-muted-foreground">No field details</span>
                                    </TableCell>

                                    <!-- Timestamp -->
                                    <TableCell>
                                        <div class="text-sm text-slate-700">{{ log.created_at_human ?? '—' }}</div>
                                    </TableCell>

                                    <!-- Details dialog -->
                                    <TableCell class="text-right">
                                        <Dialog>
                                            <DialogTrigger as-child>
                                                <Button
                                                    size="icon"
                                                    variant="outline"
                                                    class="h-8 w-8 rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100 hover:text-slate-900 cursor-pointer"
                                                >
                                                    <FileSearch class="h-3.5 w-3.5" />
                                                </Button>
                                            </DialogTrigger>

                                            <DialogContent class="max-w-lg rounded-2xl p-0">
                                                <DialogHeader class="border-b border-slate-100 px-5 py-4">
                                                    <div class="flex items-center gap-2">
                                                        <Badge :class="['gap-1.5', actionBadgeClass(log.action)]">
                                                            <span :class="['h-1.5 w-1.5 rounded-full', actionDot(log.action)]" />
                                                            {{ log.action_label }}
                                                        </Badge>
                                                    </div>
                                                    <DialogTitle class="mt-1 text-base">
                                                        {{ log.entity_label }}
                                                        <span v-if="log.entity_name" class="font-normal text-muted-foreground">
                                                            · {{ log.entity_name }}
                                                        </span>
                                                    </DialogTitle>
                                                    <DialogDescription>
                                                        {{ log.created_at_human ?? '—' }}
                                                    </DialogDescription>
                                                </DialogHeader>

                                                <div class="px-5 py-4 space-y-4">
                                                    <!-- Meta info -->
                                                    <div class="grid grid-cols-2 gap-3 rounded-lg bg-slate-50 p-3 text-xs">
                                                        <div>
                                                            <p class="font-semibold uppercase tracking-widest text-muted-foreground">Entity</p>
                                                            <p class="mt-0.5 text-slate-700">{{ log.entity_label }}</p>
                                                        </div>
                                                        <div>
                                                            <p class="font-semibold uppercase tracking-widest text-muted-foreground">When</p>
                                                            <p class="mt-0.5 text-slate-700">{{ log.created_at_human ?? '—' }}</p>
                                                        </div>
                                                        <div class="col-span-2">
                                                            <p class="font-semibold uppercase tracking-widest text-muted-foreground">Record</p>
                                                            <p class="mt-0.5 text-slate-700">{{ log.entity_name ?? '—' }}</p>
                                                        </div>
                                                    </div>

                                                    <Separator />

                                                    <!-- Changes -->
                                                    <div>
                                                        <p class="mb-2 text-xs font-bold uppercase tracking-widest text-muted-foreground">
                                                            What changed
                                                        </p>

                                                        <div v-if="log.changes.length === 0" class="rounded-lg border border-dashed border-slate-200 p-4 text-center text-xs text-muted-foreground">
                                                            No field-level changes were recorded for this activity.
                                                        </div>

                                                        <div v-else class="space-y-2">
                                                            <div
                                                                v-for="change in log.changes"
                                                                :key="`${log.id}-detail-${change.field}`"
                                                                class="rounded-lg border border-slate-100 bg-slate-50 p-3 text-xs"
                                                            >
                                                                <p class="mb-1.5 font-semibold text-slate-800">{{ change.label }}</p>
                                                                <div class="flex items-center gap-2">
                                                                    <div class="flex-1 rounded border border-slate-200 bg-white px-2 py-1 text-muted-foreground line-through">
                                                                        {{ formatValue(change.old) }}
                                                                    </div>
                                                                    <ArrowRight class="h-3.5 w-3.5 shrink-0 text-slate-400" />
                                                                    <div class="flex-1 rounded border border-slate-200 bg-white px-2 py-1 font-medium text-slate-800">
                                                                        {{ formatValue(change.new) }}
                                                                    </div>
                                                                </div>
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
    </ExternalLayout>
</template>