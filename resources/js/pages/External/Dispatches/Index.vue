<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

import InertiaPagination from '@/components/InertiaPagination.vue';
import InputError from '@/components/InputError.vue';
import SearchInput from '@/components/SearchInput.vue';
import ExternalLayout from '@/layouts/ExternalLayout.vue';

import DispatchController from '@/actions/App/Http/Controllers/DispatchController';

import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
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
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

import {
    ArrowUpRight,
    Bus,
    CalendarClock,
    CalendarDays,
    CheckCircle2,
    Clock3,
    FileText,
    Fingerprint,
    LogOut,
    MoreHorizontal,
    Pencil,
    Plus,
    Send,
    TrendingUp,
    UserRound,
    Users,
    X,
} from 'lucide-vue-next';

import {
    CalendarDate,
    DateFormatter,
    getLocalTimeZone,
    today,
} from '@internationalized/date';

// ── Types ─────────────────────────────────────────────────────────────────────

type Company = {
    id: number;
    company_name: string;
    company_code?: string | null;
};

type Vehicle = {
    id: number;
    plate_number: string;
    body_number?: string | null;
    vehicle_type?: string | null;
    make_model?: string | null;
    status?: string | null;
    label: string;
};

type Driver = {
    id: number;
    name: string;
    username?: string | null;
    email?: string | null;
    label: string;
};

type Gate = {
    id: number;
    gate_name: string;
    bays: number;
    status?: string | null;
    label: string;
    bay_options: Array<{ value: number; label: string }>;
};

type DispatchItem = {
    id: number;
    plate_number: string;
    pax_count: number;
    bay_number: string | number;
    remarks?: string | null;
    status: string;
    arrived_at_formatted?: string | null;
    departed_at_formatted?: string | null;
    dispatched_at_formatted?: string | null;
    vehicle?: {
        id: number;
        plate_number: string;
        body_number?: string | null;
        vehicle_type?: string | null;
        make_model?: string | null;
    } | null;
    dispatcher?: { id: number; name: string; username?: string | null } | null;
    driver?: { id: number; name: string; username?: string | null } | null;
    gate?: { id: number; gate_name: string } | null;
};

type Paginated<T> = {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

// ── Props ─────────────────────────────────────────────────────────────────────

const props = defineProps<{
    company: Company;
    vehicles: Vehicle[];
    drivers: Driver[];
    gates: Gate[];
    dispatches: Paginated<DispatchItem>;
    filters?: {
        search?: string | null;
        status?: string | null;
        date?: string | null;
    };
}>();

// ── Date filter ───────────────────────────────────────────────────────────────

const df = new DateFormatter('en-US', { dateStyle: 'medium' });

// BUG FIX: resolve the timezone once so it is stable and reusable
// in both script and template without re-calling getLocalTimeZone() each time.
const localTz = getLocalTimeZone();

function parseDateFilter(dateStr?: string | null): CalendarDate | undefined {
    if (!dateStr) return undefined;
    const [y, m, d] = dateStr.split('-').map(Number);
    if (!y || !m || !d) return undefined;
    return new CalendarDate(y, m, d);
}

const selectedDate = ref<CalendarDate | undefined>(
    parseDateFilter(props.filters?.date),
);
const calendarOpen = ref(false);

const selectedDateLabel = computed(() => {
    if (!selectedDate.value) return null;
    const t = today(localTz);
    if (selectedDate.value.compare(t) === 0) return 'Today';
    const yesterday = t.subtract({ days: 1 });
    if (selectedDate.value.compare(yesterday) === 0) return 'Yesterday';
    return df.format(selectedDate.value.toDate(localTz));
});

function applyDateFilter(date: CalendarDate | undefined) {
    selectedDate.value = date;
    calendarOpen.value = false;
    router.get(
        DispatchController.index().url,
        {
            search: props.filters?.search || undefined,
            // BUG FIX: preserve the current status filter correctly —
            // 'all' should be sent as undefined so it doesn't pollute the URL.
            status:
                !props.filters?.status || props.filters.status === 'all'
                    ? undefined
                    : props.filters.status,
            date: date
                ? `${date.year}-${String(date.month).padStart(2, '0')}-${String(date.day).padStart(2, '0')}`
                : undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true, only: ['dispatches', 'filters'] },
    );
}

function clearDateFilter() {
    applyDateFilter(undefined);
}

function setToday()     { applyDateFilter(today(localTz)); }
function setYesterday() { applyDateFilter(today(localTz).subtract({ days: 1 })); }

// ── Dialog / modal state ──────────────────────────────────────────────────────

const dialogOpen        = ref(false);
const editingDispatchId = ref<number | null>(null);
const confirmDepartOpen = ref(false);
const pendingDepartId   = ref<number | null>(null);
const remarksViewOpen   = ref(false);
const viewingDispatch   = ref<DispatchItem | null>(null);

// ── Form ──────────────────────────────────────────────────────────────────────

const form = useForm({
    vehicle_id:     '',
    driver_user_id: 'unassigned',
    gate_id:        '',
    bay_number:     '',
    pax_count:      '',
    remarks:        '',
});

const selectedVehicle = computed(() =>
    props.vehicles.find((v) => String(v.id) === String(form.vehicle_id)) ?? null,
);
const selectedGate = computed(() =>
    props.gates.find((g) => String(g.id) === String(form.gate_id)) ?? null,
);
const selectedDriver = computed(() => {
    if (form.driver_user_id === 'unassigned') return null;
    return props.drivers.find((d) => String(d.id) === String(form.driver_user_id)) ?? null;
});
const bayOptions = computed(() => selectedGate.value?.bay_options ?? []);
const isEditing  = computed(() => editingDispatchId.value !== null);

// ── Dialog helpers ────────────────────────────────────────────────────────────

function onGateChange(value: string) {
    form.gate_id    = value;
    form.bay_number = '';
}

function resetForm() {
    // BUG FIX: always reset the transform FIRST before resetting field values,
    // so a failed previous submit's stale transform can never leak into the next one.
    form.transform((d) => d);
    form.reset();
    form.driver_user_id = 'unassigned';
    form.clearErrors();
}

function openCreateDialog() {
    editingDispatchId.value = null;
    resetForm();
    dialogOpen.value = true;
}

function openEditDialog(dispatch: DispatchItem) {
    if (dispatch.status === 'departed') return;
    editingDispatchId.value = dispatch.id;
    // BUG FIX: reset transform before populating so any previous submit's
    // transform doesn't affect this edit session.
    form.transform((d) => d);
    form.clearErrors();
    form.vehicle_id     = dispatch.vehicle?.id ? String(dispatch.vehicle.id) : '';
    form.driver_user_id = dispatch.driver?.id  ? String(dispatch.driver.id)  : 'unassigned';
    form.gate_id        = dispatch.gate?.id    ? String(dispatch.gate.id)    : '';
    form.bay_number     = String(dispatch.bay_number ?? '');
    form.pax_count      = String(dispatch.pax_count  ?? '');
    form.remarks        = dispatch.remarks ?? '';
    dialogOpen.value = true;
}

function openRemarksDialog(dispatch: DispatchItem) {
    viewingDispatch.value = dispatch;
    remarksViewOpen.value = true;
}

// ── Submit ────────────────────────────────────────────────────────────────────

function submit() {
    // BUG FIX: build the payload from current form data *before* calling
    // transform(), then reset the transform immediately after the request
    // completes (success OR error) so it never leaks into the next submit.
    const payload = {
        ...form.data(),
        driver_user_id:
            form.driver_user_id && form.driver_user_id !== 'unassigned'
                ? form.driver_user_id
                : null,
    };

    const afterRequest = () => {
        // Always reset the transform after the request so future submits
        // start clean, regardless of success or failure.
        form.transform((d) => d);
    };

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            dialogOpen.value        = false;
            editingDispatchId.value = null;
            resetForm();
        },
        onError: afterRequest,
        // BUG FIX: reset the transform even on finish (covers cancel/network errors).
        onFinish: afterRequest,
    };

    form.transform(() => payload);

    if (isEditing.value && editingDispatchId.value) {
        form.put(DispatchController.update(editingDispatchId.value).url, options);
        return;
    }
    form.post(DispatchController.store().url, options);
}

// ── Depart ────────────────────────────────────────────────────────────────────

function askDepart(dispatchId: number) {
    pendingDepartId.value   = dispatchId;
    confirmDepartOpen.value = true;
}

function confirmDepart() {
    if (!pendingDepartId.value) return;
    router.patch(DispatchController.depart(pendingDepartId.value).url, {}, {
        preserveScroll: true,
        onSuccess: () => {
            confirmDepartOpen.value = false;
            pendingDepartId.value   = null;
        },
    });
}

// ── Utilities ─────────────────────────────────────────────────────────────────

function statusVariant(status?: string | null): 'default' | 'secondary' | 'outline' | 'destructive' {
    switch (status) {
        case 'departed': return 'outline';
        case 'arrived':  return 'default';
        case 'pending':  return 'secondary';
        default:         return 'secondary';
    }
}

function statusLabel(status?: string | null) {
    if (!status) return 'Unknown';
    return status.replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function statusDot(status: string) {
    if (status === 'arrived')  return 'bg-emerald-500';
    if (status === 'departed') return 'bg-slate-400';
    if (status === 'pending')  return 'bg-amber-400';
    return 'bg-slate-400';
}

// ── Stats ─────────────────────────────────────────────────────────────────────

const arrivedCount  = computed(() => props.dispatches.data.filter((d) => d.status === 'arrived').length);
const departedCount = computed(() => props.dispatches.data.filter((d) => d.status === 'departed').length);
const totalPax      = computed(() => props.dispatches.data.reduce((s, d) => s + (d.pax_count ?? 0), 0));

// ── Watchers ──────────────────────────────────────────────────────────────────

watch(dialogOpen, (open) => {
    if (!open) {
        // BUG FIX: reset transform on close so a dialog closed mid-flight
        // (e.g. user presses Escape) doesn't leave a stale transform.
        editingDispatchId.value = null;
        form.transform((d) => d);
        form.clearErrors();
    }
});

watch(confirmDepartOpen, (open) => {
    if (!open) pendingDepartId.value = null;
});
</script>

<template>
    <ExternalLayout>
        <Head title="Dispatches" />

        <div class="space-y-5 p-4 md:p-6">

            <!-- ── Page Header ── -->
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-semibold tracking-tight">Dispatching Module</h1>
                        <Badge variant="outline" class="hidden font-mono text-xs md:inline-flex">
                            {{ company.company_code || 'No Code' }}
                        </Badge>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        Manage and monitor vehicle dispatches for
                        <span class="font-medium text-foreground">{{ company.company_name }}</span>
                    </p>
                </div>

                <Button size="sm" class="w-full md:w-auto" @click="openCreateDialog">
                    <Plus class="mr-2 h-4 w-4" />
                    Add Dispatch
                </Button>
            </div>

            <!-- ── Stat Cards ── -->
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                <div class="rounded-xl border bg-card p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Total</p>
                        <CalendarClock class="h-4 w-4 text-muted-foreground" />
                    </div>
                    <p class="mt-2 text-2xl font-bold">{{ dispatches.total }}</p>
                    <p class="text-xs text-muted-foreground">dispatches</p>
                </div>

                <div class="rounded-xl border bg-card p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">On-site</p>
                        <Clock3 class="h-4 w-4 text-emerald-500" />
                    </div>
                    <p class="mt-2 text-2xl font-bold text-emerald-600">{{ arrivedCount }}</p>
                    <p class="text-xs text-muted-foreground">arrived</p>
                </div>

                <div class="rounded-xl border bg-card p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Departed</p>
                        <LogOut class="h-4 w-4 text-slate-400" />
                    </div>
                    <p class="mt-2 text-2xl font-bold text-slate-500">{{ departedCount }}</p>
                    <p class="text-xs text-muted-foreground">this page</p>
                </div>

                <div class="rounded-xl border bg-card p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Passengers</p>
                        <TrendingUp class="h-4 w-4 text-muted-foreground" />
                    </div>
                    <p class="mt-2 text-2xl font-bold">{{ totalPax }}</p>
                    <p class="text-xs text-muted-foreground">this page</p>
                </div>
            </div>

            <!-- ── Records Card ── -->
            <Card class="shadow-sm">
                <CardHeader class="pb-4">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <CardTitle class="text-base">Dispatch Records</CardTitle>
                            <CardDescription class="mt-0.5 text-xs">
                                Arrival time is automatically recorded on dispatch creation.
                            </CardDescription>
                        </div>

                        <!-- ── Filters ── -->
                        <div class="flex flex-col gap-2 md:flex-row md:items-center">

                            <!-- Search -->
                            <SearchInput
                                :route="DispatchController.index().url"
                                :initial-value="props.filters?.search ?? ''"
                                placeholder="Search plate, remarks…"
                                :only="['dispatches', 'filters']"
                                class="w-full md:w-56"
                            />

                            <!-- Status filter -->
                            <!-- BUG FIX: preserve date when changing status -->
                            <Select
                                :model-value="props.filters?.status ?? 'all'"
                                @update:model-value="(value) => {
                                    router.get(
                                        DispatchController.index().url,
                                        {
                                            search: props.filters?.search || undefined,
                                            status: value === 'all' ? undefined : value,
                                            date:   props.filters?.date  || undefined,
                                        },
                                        { preserveState: true, preserveScroll: true, replace: true, only: ['dispatches', 'filters'] },
                                    );
                                }"
                            >
                                <SelectTrigger class="w-full md:w-36">
                                    <SelectValue placeholder="All statuses" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Statuses</SelectItem>
                                    <SelectItem value="arrived">Arrived</SelectItem>
                                    <SelectItem value="departed">Departed</SelectItem>
                                </SelectContent>
                            </Select>

                            <!-- Date picker -->
                            <Popover v-model:open="calendarOpen">
                                <PopoverTrigger as-child>
                                    <Button
                                        variant="outline"
                                        class="w-full justify-start gap-2 md:w-44"
                                        :class="selectedDate ? 'border-primary/50 bg-primary/5 text-foreground' : 'text-muted-foreground'"
                                    >
                                        <CalendarDays class="h-4 w-4 shrink-0" :class="selectedDate ? 'text-primary' : ''" />
                                        <span class="truncate text-sm">
                                            {{ selectedDateLabel ?? 'Pick a date' }}
                                        </span>
                                        <span
                                            v-if="selectedDate"
                                            class="ml-auto flex h-4 w-4 shrink-0 items-center justify-center rounded-full hover:bg-muted"
                                            @click.stop="clearDateFilter"
                                        >
                                            <X class="h-3 w-3" />
                                        </span>
                                    </Button>
                                </PopoverTrigger>

                                <PopoverContent class="w-auto p-0" align="end">
                                    <div class="border-b px-3 py-2.5">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                            Filter by Date
                                        </p>
                                    </div>

                                    <!-- Quick shortcuts -->
                                    <div class="flex gap-1.5 border-b px-3 py-2">
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            class="h-7 flex-1 text-xs"
                                            :class="selectedDateLabel === 'Today' ? 'border-primary bg-primary/10 text-primary' : ''"
                                            @click="setToday"
                                        >
                                            Today
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            class="h-7 flex-1 text-xs"
                                            :class="selectedDateLabel === 'Yesterday' ? 'border-primary bg-primary/10 text-primary' : ''"
                                            @click="setYesterday"
                                        >
                                            Yesterday
                                        </Button>
                                        <Button
                                            v-if="selectedDate"
                                            size="sm"
                                            variant="ghost"
                                            class="h-7 flex-1 text-xs text-muted-foreground"
                                            @click="clearDateFilter"
                                        >
                                            Clear
                                        </Button>
                                    </div>

                                    <!-- BUG FIX: use the stable localTz ref instead of
                                         calling getLocalTimeZone() directly in the template. -->
                                    <Calendar
                                        :model-value="selectedDate"
                                        :max-value="today(localTz)"
                                        initial-focus
                                        @update:model-value="(d) => applyDateFilter(d as CalendarDate | undefined)"
                                    />
                                </PopoverContent>
                            </Popover>
                        </div>
                    </div>

                    <!-- Active date filter pill -->
                    <div v-if="selectedDate" class="flex items-center gap-2 pt-1">
                        <div class="inline-flex items-center gap-1.5 rounded-full border border-primary/30 bg-primary/5 px-3 py-1 text-xs font-medium text-primary">
                            <CalendarDays class="h-3 w-3" />
                            Showing dispatches for {{ selectedDateLabel }}
                            <button
                                class="ml-1 rounded-full p-0.5 hover:bg-primary/10"
                                @click="clearDateFilter"
                            >
                                <X class="h-2.5 w-2.5" />
                            </button>
                        </div>
                    </div>
                </CardHeader>

                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-muted/40 hover:bg-muted/40">
                                    <TableHead class="pl-6 font-semibold">Vehicle</TableHead>
                                    <TableHead class="font-semibold">Driver</TableHead>
                                    <TableHead class="font-semibold">Gate / Bay</TableHead>
                                    <TableHead class="font-semibold">Pax</TableHead>
                                    <TableHead class="font-semibold">Status</TableHead>
                                    <TableHead class="font-semibold">Arrived</TableHead>
                                    <TableHead class="font-semibold">Departed</TableHead>
                                    <TableHead class="font-semibold">Dispatcher</TableHead>
                                    <TableHead class="font-semibold">Remarks</TableHead>
                                    <TableHead class="pr-6 text-right font-semibold">Action</TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <!-- Empty state -->
                                <TableRow v-if="dispatches.data.length === 0">
                                    <TableCell colspan="10" class="py-20 text-center">
                                        <div class="flex flex-col items-center gap-2 text-muted-foreground">
                                            <div class="flex h-12 w-12 items-center justify-center rounded-full border bg-muted">
                                                <CalendarDays class="h-5 w-5 opacity-50" />
                                            </div>
                                            <p class="text-sm font-medium">
                                                {{ selectedDate ? `No dispatches on ${selectedDateLabel}` : 'No dispatch records found' }}
                                            </p>
                                            <p class="text-xs">
                                                {{ selectedDate ? 'Try a different date or clear the filter.' : 'Try adjusting your search or filter, or add a new dispatch.' }}
                                            </p>
                                            <div class="mt-2 flex gap-2">
                                                <Button v-if="selectedDate" size="sm" variant="outline" @click="clearDateFilter">
                                                    <X class="mr-1.5 h-3.5 w-3.5" />
                                                    Clear Date Filter
                                                </Button>
                                                <Button size="sm" variant="outline" @click="openCreateDialog">
                                                    <Plus class="mr-1.5 h-3.5 w-3.5" />
                                                    Add Dispatch
                                                </Button>
                                            </div>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <TableRow
                                    v-for="dispatch in dispatches.data"
                                    :key="dispatch.id"
                                    class="group transition-colors"
                                    :class="dispatch.status === 'departed' ? 'opacity-60' : ''"
                                >
                                    <!-- Vehicle -->
                                    <TableCell class="pl-6">
                                        <div class="flex items-center gap-2.5">
                                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border bg-muted">
                                                <Bus class="h-3.5 w-3.5 text-muted-foreground" />
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold leading-tight">{{ dispatch.plate_number }}</p>
                                                <p v-if="dispatch.vehicle?.vehicle_type" class="text-xs text-muted-foreground">
                                                    {{ dispatch.vehicle.vehicle_type }}
                                                </p>
                                            </div>
                                        </div>
                                    </TableCell>

                                    <!-- Driver -->
                                    <TableCell>
                                        <div class="flex items-center gap-1.5">
                                            <UserRound class="h-3.5 w-3.5 shrink-0 text-muted-foreground" />
                                            <span class="text-sm" :class="!dispatch.driver ? 'italic text-muted-foreground' : ''">
                                                {{ dispatch.driver?.name ?? 'Unassigned' }}
                                            </span>
                                        </div>
                                    </TableCell>

                                    <!-- Gate / Bay -->
                                    <TableCell>
                                        <p class="text-sm font-medium">{{ dispatch.gate?.gate_name ?? '—' }}</p>
                                        <p class="text-xs text-muted-foreground">Bay {{ dispatch.bay_number }}</p>
                                    </TableCell>

                                    <!-- Pax -->
                                    <TableCell>
                                        <div class="inline-flex items-center gap-1.5 rounded-full bg-muted px-2.5 py-1 text-xs font-medium">
                                            <Users class="h-3 w-3" />
                                            {{ dispatch.pax_count }}
                                        </div>
                                    </TableCell>

                                    <!-- Status -->
                                    <TableCell>
                                        <div class="flex items-center gap-1.5">
                                            <span class="inline-block h-1.5 w-1.5 rounded-full" :class="statusDot(dispatch.status)" />
                                            <Badge :variant="statusVariant(dispatch.status)" class="text-xs">
                                                {{ statusLabel(dispatch.status) }}
                                            </Badge>
                                        </div>
                                    </TableCell>

                                    <!-- Arrived -->
                                    <TableCell>
                                        <div v-if="dispatch.arrived_at_formatted" class="flex items-center gap-1.5 text-xs">
                                            <Clock3 class="h-3 w-3 shrink-0 text-muted-foreground" />
                                            {{ dispatch.arrived_at_formatted }}
                                        </div>
                                        <span v-else class="text-xs text-muted-foreground">—</span>
                                    </TableCell>

                                    <!-- Departed -->
                                    <TableCell>
                                        <div v-if="dispatch.departed_at_formatted" class="flex items-center gap-1.5 text-xs">
                                            <CheckCircle2 class="h-3 w-3 shrink-0 text-muted-foreground" />
                                            {{ dispatch.departed_at_formatted }}
                                        </div>
                                        <span v-else class="text-xs text-muted-foreground">—</span>
                                    </TableCell>

                                    <!-- Dispatcher -->
                                    <TableCell>
                                        <div class="flex items-center gap-1.5">
                                            <Fingerprint class="h-3.5 w-3.5 shrink-0 text-muted-foreground" />
                                            <span class="text-sm">{{ dispatch.dispatcher?.name || '—' }}</span>
                                        </div>
                                    </TableCell>

                                    <!-- Remarks -->
                                    <TableCell>
                                        <TooltipProvider v-if="dispatch.remarks">
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        class="h-7 gap-1.5 px-2 text-xs text-muted-foreground hover:text-foreground"
                                                        @click="openRemarksDialog(dispatch)"
                                                    >
                                                        <FileText class="h-3.5 w-3.5" />
                                                        View
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent side="top" class="max-w-52 text-xs">
                                                    {{ dispatch.remarks }}
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                        <span v-else class="text-xs text-muted-foreground">—</span>
                                    </TableCell>

                                    <!-- Actions -->
                                    <TableCell class="pr-6 text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    class="h-8 w-8 opacity-0 transition-opacity group-hover:opacity-100 data-[state=open]:opacity-100"
                                                >
                                                    <MoreHorizontal class="h-4 w-4" />
                                                    <span class="sr-only">Actions</span>
                                                </Button>
                                            </DropdownMenuTrigger>

                                            <DropdownMenuContent align="end" class="w-44">
                                                <DropdownMenuItem as-child>
                                                    <Link :href="DispatchController.show(dispatch.id).url" class="flex items-center">
                                                        <ArrowUpRight class="mr-2 h-4 w-4" />
                                                        View Details
                                                    </Link>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    v-if="dispatch.remarks"
                                                    @click="openRemarksDialog(dispatch)"
                                                >
                                                    <FileText class="mr-2 h-4 w-4" />
                                                    View Remarks
                                                </DropdownMenuItem>

                                                <template v-if="dispatch.status === 'arrived'">
                                                    <DropdownMenuSeparator />
                                                    <DropdownMenuItem @click="openEditDialog(dispatch)">
                                                        <Pencil class="mr-2 h-4 w-4" />
                                                        Edit
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        class="text-amber-600 focus:text-amber-600"
                                                        @click="askDepart(dispatch.id)"
                                                    >
                                                        <LogOut class="mr-2 h-4 w-4" />
                                                        Mark Departed
                                                    </DropdownMenuItem>
                                                </template>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Pagination footer -->
                    <div class="border-t px-6 py-4">
                        <div class="flex flex-col items-center justify-between gap-2 md:flex-row">
                            <p class="text-xs text-muted-foreground">
                                Showing
                                <span class="font-medium">{{ dispatches.from ?? 0 }}</span>–<span class="font-medium">{{ dispatches.to ?? 0 }}</span>
                                of <span class="font-medium">{{ dispatches.total }}</span> records
                                <template v-if="selectedDate">
                                    for <span class="font-medium text-primary">{{ selectedDateLabel }}</span>
                                </template>
                            </p>
                            <InertiaPagination :links="dispatches.links" />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- ── Create / Edit Dialog ── -->
        <Dialog v-model:open="dialogOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ isEditing ? 'Edit Dispatch' : 'Create Dispatch' }}</DialogTitle>
                    <DialogDescription>
                        {{ isEditing ? 'Update the dispatch details below.' : 'Arrival time is automatically set to now.' }}
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="submit">
                    <div class="space-y-2">
                        <Label for="vehicle_id">Vehicle</Label>
                        <Select v-model="form.vehicle_id">
                            <SelectTrigger id="vehicle_id"><SelectValue placeholder="Select a vehicle" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="vehicle in vehicles" :key="vehicle.id" :value="String(vehicle.id)">
                                    {{ vehicle.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.vehicle_id" />
                    </div>

                    <div class="space-y-2">
                        <Label for="driver_user_id">Driver</Label>
                        <Select v-model="form.driver_user_id">
                            <SelectTrigger id="driver_user_id"><SelectValue placeholder="Assign a driver" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="unassigned">No driver assigned</SelectItem>
                                <SelectItem v-for="driver in drivers" :key="driver.id" :value="String(driver.id)">
                                    {{ driver.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.driver_user_id" />
                    </div>

                    <div class="space-y-2">
                        <Label for="gate_id">Gate</Label>
                        <Select :model-value="form.gate_id" @update:model-value="onGateChange">
                            <SelectTrigger id="gate_id"><SelectValue placeholder="Select a gate" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="gate in gates" :key="gate.id" :value="String(gate.id)">
                                    {{ gate.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.gate_id" />
                    </div>

                    <div class="space-y-2">
                        <Label for="bay_number">Bay Number</Label>
                        <Select v-model="form.bay_number" :disabled="!selectedGate">
                            <SelectTrigger id="bay_number"><SelectValue placeholder="Select a bay" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="bay in bayOptions" :key="bay.value" :value="String(bay.value)">
                                    {{ bay.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.bay_number" />
                    </div>

                    <div
                        v-if="selectedVehicle || selectedGate || selectedDriver"
                        class="flex flex-wrap gap-2 rounded-lg border bg-muted/30 p-3"
                    >
                        <div v-if="selectedVehicle" class="inline-flex items-center gap-1.5 rounded-full border bg-background px-2.5 py-1 text-xs font-medium shadow-sm">
                            <Bus class="h-3 w-3 text-muted-foreground" />
                            {{ selectedVehicle.plate_number }}
                        </div>
                        <div v-if="selectedDriver" class="inline-flex items-center gap-1.5 rounded-full border bg-background px-2.5 py-1 text-xs font-medium shadow-sm">
                            <UserRound class="h-3 w-3 text-muted-foreground" />
                            {{ selectedDriver.name }}
                        </div>
                        <div v-if="selectedGate" class="inline-flex items-center gap-1.5 rounded-full border bg-background px-2.5 py-1 text-xs font-medium shadow-sm">
                            <span class="text-muted-foreground">Gate</span>
                            {{ selectedGate.gate_name }} · {{ selectedGate.bays }} bays
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="pax_count">Passenger Count</Label>
                        <Input id="pax_count" v-model="form.pax_count" type="number" min="0" placeholder="0" />
                        <InputError :message="form.errors.pax_count" />
                    </div>

                    <div class="space-y-2">
                        <Label for="remarks">
                            Remarks
                            <span class="ml-1 text-xs font-normal text-muted-foreground">(optional)</span>
                        </Label>
                        <Input id="remarks" v-model="form.remarks" placeholder="Optional remarks or notes" />
                        <InputError :message="form.errors.remarks" />
                    </div>

                    <DialogFooter class="pt-2">
                        <Button type="button" variant="outline" @click="dialogOpen = false">Cancel</Button>
                        <Button type="submit" :disabled="form.processing">
                            <Send class="mr-2 h-4 w-4" />
                            {{ form.processing ? 'Saving…' : isEditing ? 'Save Changes' : 'Create Dispatch' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- ── Remarks Dialog ── -->
        <Dialog v-model:open="remarksViewOpen">
            <DialogContent class="sm:max-w-sm">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <FileText class="h-4 w-4" />
                        Dispatch Remarks
                    </DialogTitle>
                    <DialogDescription v-if="viewingDispatch">
                        {{ viewingDispatch.plate_number }} · {{ viewingDispatch.gate?.gate_name ?? '—' }} · Bay {{ viewingDispatch.bay_number }}
                    </DialogDescription>
                </DialogHeader>

                <Separator />

                <div
                    class="min-h-[80px] rounded-lg border bg-muted/30 p-4 text-sm leading-relaxed"
                    :class="!viewingDispatch?.remarks ? 'italic text-muted-foreground' : ''"
                >
                    {{ viewingDispatch?.remarks || 'No remarks recorded.' }}
                </div>

                <div v-if="viewingDispatch" class="grid grid-cols-2 gap-3 text-xs">
                    <div class="space-y-0.5">
                        <p class="font-medium text-muted-foreground">Driver</p>
                        <p class="font-semibold" :class="!viewingDispatch.driver ? 'italic text-muted-foreground' : ''">
                            {{ viewingDispatch.driver?.name ?? 'Unassigned' }}
                        </p>
                    </div>
                    <div class="space-y-0.5">
                        <p class="font-medium text-muted-foreground">Dispatcher</p>
                        <p class="font-semibold">{{ viewingDispatch.dispatcher?.name ?? '—' }}</p>
                    </div>
                    <div class="space-y-0.5">
                        <p class="font-medium text-muted-foreground">Status</p>
                        <Badge :variant="statusVariant(viewingDispatch.status)" class="text-xs">
                            {{ statusLabel(viewingDispatch.status) }}
                        </Badge>
                    </div>
                    <div class="space-y-0.5">
                        <p class="font-medium text-muted-foreground">Arrived</p>
                        <p class="font-semibold">{{ viewingDispatch.arrived_at_formatted ?? '—' }}</p>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" class="w-full" @click="remarksViewOpen = false">Close</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- ── Confirm Depart ── -->
        <AlertDialog v-model:open="confirmDepartOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Mark dispatch as departed?</AlertDialogTitle>
                    <AlertDialogDescription>
                        This will record the departure time as <strong>now</strong>.
                        Departed dispatches can no longer be edited.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction @click="confirmDepart">Confirm Departure</AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </ExternalLayout>
</template>
