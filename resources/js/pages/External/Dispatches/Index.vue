<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

import InertiaPagination from '@/components/InertiaPagination.vue';
import InputError from '@/components/InputError.vue';
import SearchInput from '@/components/SearchInput.vue';
import ExternalLayout from '@/layouts/ExternalLayout.vue';

import { store as storeChangeRequest } from '@/actions/App/Http/Controllers/DispatchChangeRequestController';
import DispatchController from '@/actions/App/Http/Controllers/DispatchController';

import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Calendar } from '@/components/ui/calendar'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'
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
    DropdownMenuLabel,
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
    Building2,
    Bus,
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
    XCircle,
} from 'lucide-vue-next';

import {
    CalendarDate,
    DateFormatter,
    getLocalTimeZone,
    today,
} from '@internationalized/date';

/* ======================================================
   Types
====================================================== */
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

/* ======================================================
   Props
====================================================== */
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
    changeRequests?: Array<{
        id: number;
        dispatch_id: number;
        dispatch: { id: number; plate_number: string; status: string };
        requested_by: { id: number; name: string; email: string | null };
        requested_field: string;
        old_value: unknown | null;
        requested_value: unknown;
        reason: string;
        status: string;
        rejection_reason: string | null;
        field_label: string | null;
        created_at: string | null;
    }>;
}>();

const df = new DateFormatter('en-US', { dateStyle: 'medium' })
const localTz = getLocalTimeZone()

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

// Auto-filter to today on first visit if no date filter is set
onMounted(() => {
    if (!selectedDate.value && !props.filters?.date) {
        applyDateFilter(today(localTz));
    }
});

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
            status:
                !props.filters?.status || props.filters.status === 'all'
                    ? undefined
                    : props.filters.status,
            date: date
                ? `${date.year}-${String(date.month).padStart(2, '0')}-${String(date.day).padStart(2, '0')}`
                : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['dispatches', 'filters'],
        },
    );
}

function clearDateFilter() {
    applyDateFilter(undefined);
}

function setToday() {
    applyDateFilter(today(localTz));
}

function setYesterday() {
    applyDateFilter(today(localTz).subtract({ days: 1 }));
}

const dialogOpen = ref(false)
const editingDispatchId = ref<number | null>(null)

const confirmDepartOpen = ref(false);
const pendingDepartId = ref<number | null>(null);
const pendingDepartDispatch = ref<DispatchItem | null>(null);

const remarksViewOpen = ref(false);
const viewingDispatch = ref<DispatchItem | null>(null);

/* ======================================================
   Forms
====================================================== */
const form = useForm({
    vehicle_id: '',
    driver_user_id: 'unassigned',
    gate_id: '',
    bay_number: '',
    remarks: '',
});

const departForm = useForm({
    pax_count: '',
})

const selectedVehicle = computed(() =>
    props.vehicles.find((v) => String(v.id) === String(form.vehicle_id)) ?? null,
)

const selectedGate = computed(
    () =>
        props.gates.find((g) => String(g.id) === String(form.gate_id)) ?? null,
);

const selectedDriver = computed(() => {
    if (form.driver_user_id === 'unassigned') return null

    return (
        props.drivers.find(
            (d) => String(d.id) === String(form.driver_user_id),
        ) ?? null
    )
})

const bayOptions = computed(() => selectedGate.value?.bay_options ?? []);
const isEditing = computed(() => editingDispatchId.value !== null);

/* ======================================================
   Handlers
====================================================== */
function onGateChange(value: string) {
    form.gate_id = value;
    form.bay_number = '';
}

function resetForm() {
    form.transform((d) => d);
    form.reset();
    form.driver_user_id = 'unassigned';
    form.clearErrors();
}

function resetDepartForm() {
    departForm.reset();
    departForm.clearErrors();
}

function openCreateDialog() {
    editingDispatchId.value = null;
    resetForm();
    dialogOpen.value = true;
}

function openEditDialog(dispatch: DispatchItem) {
    if (dispatch.status === 'departed') return

    editingDispatchId.value = dispatch.id
    form.transform((d) => d)
    form.clearErrors()
    form.vehicle_id = dispatch.vehicle?.id ? String(dispatch.vehicle.id) : ''
    form.driver_user_id = dispatch.driver?.id
        ? String(dispatch.driver.id)
        : 'unassigned'
    form.gate_id = dispatch.gate?.id ? String(dispatch.gate.id) : ''
    form.bay_number = String(dispatch.bay_number ?? '')
    form.remarks = dispatch.remarks ?? ''
    dialogOpen.value = true
}

function openRemarksDialog(dispatch: DispatchItem) {
    viewingDispatch.value = dispatch;
    remarksViewOpen.value = true;
}

function submit() {
    const payload = {
        ...form.data(),
        driver_user_id:
            form.driver_user_id && form.driver_user_id !== 'unassigned'
                ? form.driver_user_id
                : null,
    };

    const afterRequest = () => {
        form.transform((d) => d);
    };

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            dialogOpen.value = false;
            editingDispatchId.value = null;
            resetForm();
        },
        onError: afterRequest,
        onFinish: afterRequest,
    };

    form.transform(() => payload);

    if (isEditing.value && editingDispatchId.value) {
        form.put(
            DispatchController.update(editingDispatchId.value).url,
            options,
        );
        return;
    }

    form.post(DispatchController.store().url, options);
}

function askDepart(dispatch: DispatchItem) {
    pendingDepartId.value = dispatch.id;
    pendingDepartDispatch.value = dispatch;
    resetDepartForm();
    departForm.pax_count = String(dispatch.pax_count ?? '');
    confirmDepartOpen.value = true;
}

function confirmDepart() {
    if (!pendingDepartId.value) return

    departForm.patch(DispatchController.depart(pendingDepartId.value).url, {
        preserveScroll: true,
        onSuccess: () => {
            confirmDepartOpen.value = false;
            pendingDepartId.value = null;
            pendingDepartDispatch.value = null;
            resetDepartForm();
        },
    });
}

// Change Request Functions
function openChangeRequestModal(dispatch: DispatchItem) {
    changeRequestDispatch.value = dispatch;
    changeRequestForm.reset();
    changeRequestBay.value = '';
    driverValidationWarning.value = null;
    driverConfirmed.value = false;
    changeRequestOpen.value = true;
}

function closeChangeRequestModal() {
    changeRequestOpen.value = false;
    changeRequestDispatch.value = null;
    changeRequestForm.reset();
    changeRequestBay.value = '';
    driverValidationWarning.value = null;
    driverConfirmed.value = false;
}

async function validateDriverAvailability(driverId: string) {
    if (changeRequestForm.requested_field !== 'driver_user_id') return;

    driverValidationLoading.value = true;
    driverValidationWarning.value = null;
    driverConfirmed.value = false;

    try {
        // check if driver is already assigned on this date via the frontend
        const driver = props.drivers.find((d) => String(d.id) === driverId);
        if (driver) {
            const assignedToday = props.dispatches.data.some(
                (d) =>
                    d.driver?.id === driver.id &&
                    d.id !== changeRequestDispatch.value?.id &&
                    d.status !== 'departed',
            );
            if (assignedToday) {
                driverValidationWarning.value = `⚠️ ${driver.name} is already assigned to another dispatch today. Click "Confirm" to proceed with the change request.`;
            }
        }
    } catch (error) {
        console.error('Driver validation error:', error);
    } finally {
        driverValidationLoading.value = false;
    }
}

function submitChangeRequest() {
    if (!changeRequestDispatch.value) return;

    // Validate passenger count is not the same as current
    if (changeRequestForm.requested_field === 'pax_count') {
        const currentPax = changeRequestDispatch.value.pax_count;
        const newPax = parseInt(changeRequestForm.requested_value, 10);
        if (currentPax === newPax) {
            window.$toast?.error(
                'New passenger count must be different from current value',
            );
            return;
        }
    }

    // If there's a driver warning, require confirmation
    if (driverValidationWarning.value && !driverConfirmed.value) {
        window.$toast?.warning(
            'Please confirm the driver change before submitting',
        );
        return;
    }

    // For gate changes, determine which field actually changed
    if (changeRequestForm.requested_field === 'gate_id') {
        const gateChanged =
            changeRequestDispatch.value.gate?.id !==
            parseInt(changeRequestForm.requested_value, 10);
        const bayChanged =
            changeRequestDispatch.value.bay_number !==
            parseInt(changeRequestBay.value, 10);

        if (!gateChanged && !bayChanged) {
            window.$toast?.error(
                'Gate or Bay must be different from current value',
            );
            return;
        }

        // Determine which field to submit based on what changed
        if (gateChanged && !bayChanged) {
            // Only gate changed, submit gate change
            changeRequestForm.post(
                storeChangeRequest(changeRequestDispatch.value.id),
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        closeChangeRequestModal();
                        window.$toast?.success(
                            'Change request submitted successfully',
                        );
                    },
                    onError: (errors) => {
                        console.error(
                            'Error submitting change request:',
                            errors,
                        );
                    },
                },
            );
        } else if (!gateChanged && bayChanged) {
            // Only bay changed, submit bay change
            const bayForm = useForm({
                requested_field: 'bay_number',
                requested_value: changeRequestBay.value,
                reason: changeRequestForm.reason,
            });
            bayForm.post(storeChangeRequest(changeRequestDispatch.value.id), {
                preserveScroll: true,
                onSuccess: () => {
                    closeChangeRequestModal();
                    window.$toast?.success(
                        'Change request submitted successfully',
                    );
                },
                onError: (errors) => {
                    console.error('Error submitting change request:', errors);
                },
            });
        } else {
            // Both changed, submit gate change first (user can request bay change separately if needed)
            changeRequestForm.post(
                storeChangeRequest(changeRequestDispatch.value.id),
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        closeChangeRequestModal();
                        window.$toast?.success(
                            'Change request submitted successfully',
                        );
                    },
                    onError: (errors) => {
                        console.error(
                            'Error submitting change request:',
                            errors,
                        );
                    },
                },
            );
        }
    } else {
        // All other fields
        changeRequestForm.post(
            storeChangeRequest(changeRequestDispatch.value.id),
            {
                preserveScroll: true,
                onSuccess: () => {
                    closeChangeRequestModal();
                    window.$toast?.success(
                        'Change request submitted successfully',
                    );
                },
                onError: (errors) => {
                    console.error('Error submitting change request:', errors);
                },
            },
        );
    }
}

function statusVariant(
    status?: string | null,
): 'default' | 'secondary' | 'outline' | 'destructive' {
    switch (status) {
        case 'departed':
            return 'outline'
        case 'arrived':
            return 'default'
        case 'pending':
            return 'secondary'
        default:
            return 'secondary'
    }
}

function statusLabel(status?: string | null) {
    if (!status) return 'Unknown'

    return status
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (c) => c.toUpperCase())
}

function statusDot(status: string) {
    if (status === 'arrived') return 'bg-emerald-500'
    if (status === 'departed') return 'bg-slate-400'
    if (status === 'pending') return 'bg-amber-400'
    return 'bg-slate-400'
}

function humanize(value?: string | null) {
    if (!value) return '—'
    return value.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}

/* ======================================================
   Computed stats
====================================================== */
const arrivedCount = computed(
    () => props.dispatches.data.filter((d) => d.status === 'arrived').length,
);

const departedCount = computed(
    () => props.dispatches.data.filter((d) => d.status === 'departed').length,
);

const totalPax = computed(() =>
    props.dispatches.data.reduce((s, d) => s + (d.pax_count ?? 0), 0),
);

function formatFieldLabel(field: string): string {
    return field.replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function formatValue(value: unknown): string {
    if (value === null) return '—';
    if (typeof value === 'boolean') return value ? 'Yes' : 'No';
    if (typeof value === 'object') return JSON.stringify(value);
    return String(value);
}

const pendingChangeRequests = computed(
    () => props.changeRequests?.filter((req) => req.status === 'pending') ?? [],
);

const approvedChangeRequests = computed(
    () =>
        props.changeRequests?.filter((req) => req.status === 'approved') ?? [],
);

const rejectedChangeRequests = computed(
    () =>
        props.changeRequests?.filter((req) => req.status === 'rejected') ?? [],
);

// Change Request Detail Modal State
const changeRequestDetailOpen = ref(false);
const selectedChangeRequest = ref<(typeof props.changeRequests)[0] | null>(
    null,
);

function openChangeRequestDetail(request: (typeof props.changeRequests)[0]) {
    selectedChangeRequest.value = request;
    changeRequestDetailOpen.value = true;
}

/* ======================================================
   Watchers
====================================================== */
watch(dialogOpen, (open) => {
    if (!open) {
        editingDispatchId.value = null;
        form.transform((d) => d);
        form.clearErrors();
    }
});

watch(confirmDepartOpen, (open) => {
    if (!open) {
        pendingDepartId.value = null;
        pendingDepartDispatch.value = null;
        resetDepartForm();
    }
});
</script>

<template>
    <Head title="Dispatches" />

        <div class="space-y-5 p-4 md:p-6">
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

                <Button size="sm" class="w-full md:w-auto" variant="blue" @click="openCreateDialog">
                    <Plus class="mr-2 h-4 w-4" />
                    Add Dispatch
                </Button>
            </div>

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

            <Card class="shadow-sm">
                <CardHeader class="pb-4">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <CardTitle class="text-base">Dispatch Records</CardTitle>
                            <CardDescription class="mt-0.5 text-xs">
                                Arrival time is automatically recorded on dispatch creation.
                            </CardDescription>
                        </div>

                        <div class="flex flex-col gap-2 md:flex-row md:items-center">
                            <SearchInput
                                :route="DispatchController.index().url"
                                :initial-value="props.filters?.search ?? ''"
                                placeholder="Search plate, remarks…"
                                :only="['dispatches', 'filters']"
                                class="w-full md:w-56"
                            />

                            <Select
                                :model-value="props.filters?.status ?? 'all'"
                                @update:model-value="(value) => {
                                    router.get(
                                        DispatchController.index().url,
                                        {
                                            search: props.filters?.search || undefined,
                                            status: value === 'all' ? undefined : value,
                                            date: props.filters?.date || undefined,
                                        },
                                        {
                                            preserveState: true,
                                            preserveScroll: true,
                                            replace: true,
                                            only: ['dispatches', 'filters'],
                                        },
                                    )
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

                    <!-- Table -->
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

                                <!-- Data rows -->
                                <TableRow
                                    v-for="dispatch in dispatches.data"
                                    :key="dispatch.id"
                                    class="group transition-colors"
                                    :class="dispatch.status === 'departed' ? 'opacity-60' : ''"
                                >
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
                                        <TooltipProvider
                                            v-if="dispatch.remarks"
                                        >
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        class="h-7 gap-1.5 px-2 text-xs text-muted-foreground hover:text-foreground"
                                                        @click="openRemarksDialog(dispatch)"
                                                    >
                                                        <FileText class="h-3.5 w-3.5" />
                                                        View
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent
                                                    side="top"
                                                    class="max-w-52 text-xs"
                                                >
                                                    {{ dispatch.remarks }}
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                        <span v-else class="text-xs text-muted-foreground">—</span>
                                    </TableCell>

                                    <!-- Actions -->
                                    <TableCell class="pr-5 text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    class="h-8 w-8 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700"
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

                                                <!-- View remarks -->
                                                <DropdownMenuItem
                                                    v-if="dispatch.remarks"
                                                    @click="openRemarksDialog(dispatch)"
                                                >
                                                    <FileText
                                                        class="mr-2 h-4 w-4"
                                                    />
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
                                                        @click="askDepart(dispatch)"
                                                    >
                                                        <LogOut
                                                            class="mr-2 h-4 w-4"
                                                        />
                                                        Mark Departed
                                                    </DropdownMenuItem>
                                                </template>

                                                <template
                                                    v-if="
                                                        dispatch.status ===
                                                        'departed'
                                                    "
                                                >
                                                    <DropdownMenuSeparator />
                                                    <DropdownMenuItem
                                                        @click="
                                                            openChangeRequestModal(
                                                                dispatch,
                                                            )
                                                        "
                                                    >
                                                        <Send
                                                            class="mr-2 h-4 w-4"
                                                        />
                                                        Request Change
                                                    </DropdownMenuItem>
                                                </template>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

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
                            <InertiaPagination
                                v-if="dispatches.last_page > 1"
                                :links="dispatches.links"
                                :meta="{
                                    from: dispatches.from,
                                    to: dispatches.to,
                                    total: dispatches.total,
                                }"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Change Request Status Dialog -->
        <Dialog v-model:open="changeRequestStatusOpen">
            <DialogContent class="max-h-[80vh] max-w-2xl overflow-y-auto">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <FileText class="h-5 w-5" />
                        Change Request Status
                    </DialogTitle>
                    <DialogDescription>
                        Track the status of your dispatch change requests
                    </DialogDescription>
                </DialogHeader>

                <Separator />

                <div class="space-y-6">
                    <!-- Status Cards -->
                    <div class="grid grid-cols-3 gap-3">
                        <div class="rounded-lg border bg-amber-50 p-3">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-medium text-amber-900">
                                    Pending
                                </p>
                                <Clock3 class="h-4 w-4 text-amber-600" />
                            </div>
                            <p class="mt-2 text-2xl font-bold text-amber-700">
                                {{ pendingChangeRequests.length }}
                            </p>
                        </div>

                        <div class="rounded-lg border bg-emerald-50 p-3">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-medium text-emerald-900">
                                    Approved
                                </p>
                                <CheckCircle2
                                    class="h-4 w-4 text-emerald-600"
                                />
                            </div>
                            <p class="mt-2 text-2xl font-bold text-emerald-700">
                                {{ approvedChangeRequests.length }}
                            </p>
                        </div>

                        <div class="rounded-lg border bg-red-50 p-3">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-medium text-red-900">
                                    Rejected
                                </p>
                                <XCircle class="h-4 w-4 text-red-600" />
                            </div>
                            <p class="mt-2 text-2xl font-bold text-red-700">
                                {{ rejectedChangeRequests.length }}
                            </p>
                        </div>
                    </div>

                    <!-- Change Requests List -->
                    <div class="max-h-[400px] space-y-2 overflow-y-auto pr-2">
                        <template
                            v-for="request in props.changeRequests"
                            :key="request.id"
                        >
                            <Card
                                class="cursor-pointer transition-all hover:shadow-md"
                                @click="openChangeRequestDetail(request)"
                                :class="{
                                    'border-amber-200 bg-amber-50':
                                        request.status === 'pending',
                                    'border-emerald-200 bg-emerald-50':
                                        request.status === 'approved',
                                    'border-red-200 bg-red-50':
                                        request.status === 'rejected',
                                }"
                            >
                                <CardContent class="p-4">
                                    <div
                                        class="flex items-start justify-between gap-4"
                                    >
                                        <div class="flex-1">
                                            <div
                                                class="mb-2 flex items-center gap-2"
                                            >
                                                <p
                                                    class="text-sm font-semibold"
                                                >
                                                    {{
                                                        request.dispatch
                                                            .plate_number
                                                    }}
                                                </p>
                                                <Badge
                                                    :variant="
                                                        request.status ===
                                                        'pending'
                                                            ? 'secondary'
                                                            : request.status ===
                                                                'approved'
                                                              ? 'default'
                                                              : 'destructive'
                                                    "
                                                    class="capitalize"
                                                >
                                                    {{ request.status }}
                                                </Badge>
                                                <span
                                                    class="text-xs text-muted-foreground"
                                                >
                                                    {{
                                                        new Date(
                                                            request.created_at ||
                                                                '',
                                                        ).toLocaleDateString()
                                                    }}
                                                </span>
                                            </div>

                                            <div
                                                class="grid grid-cols-2 gap-3 text-sm"
                                            >
                                                <div>
                                                    <p
                                                        class="text-xs font-medium text-muted-foreground"
                                                    >
                                                        Field
                                                    </p>
                                                    <p class="font-medium">
                                                        {{
                                                            request.field_label ||
                                                            formatFieldLabel(
                                                                request.requested_field,
                                                            )
                                                        }}
                                                    </p>
                                                </div>
                                                <div>
                                                    <p
                                                        class="text-xs font-medium text-muted-foreground"
                                                    >
                                                        Change
                                                    </p>
                                                    <p
                                                        class="font-mono text-xs"
                                                    >
                                                        {{
                                                            formatValue(
                                                                request.old_value,
                                                            )
                                                        }}
                                                        →
                                                        {{
                                                            formatValue(
                                                                request.requested_value,
                                                            )
                                                        }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="mt-2">
                                                <p
                                                    class="mb-0.5 text-xs font-medium text-muted-foreground"
                                                >
                                                    Reason
                                                </p>
                                                <p class="line-clamp-2 text-sm">
                                                    {{ request.reason }}
                                                </p>
                                            </div>

                                            <!-- Rejection Reason Badge -->
                                            <div
                                                v-if="
                                                    request.status ===
                                                        'rejected' &&
                                                    request.rejection_reason
                                                "
                                                class="mt-2"
                                            >
                                                <Popover>
                                                    <PopoverTrigger as-child>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            class="h-auto p-0 text-red-600 hover:bg-transparent hover:text-red-700"
                                                        >
                                                            <XCircle
                                                                class="mr-1 h-4 w-4"
                                                            />
                                                            <span
                                                                class="text-xs underline"
                                                                >View rejection
                                                                reason</span
                                                            >
                                                        </Button>
                                                    </PopoverTrigger>
                                                    <PopoverContent
                                                        class="w-80 p-4"
                                                        side="top"
                                                    >
                                                        <div class="space-y-2">
                                                            <p
                                                                class="text-sm font-semibold text-red-900"
                                                            >
                                                                Rejection Reason
                                                            </p>
                                                            <p
                                                                class="text-sm text-red-800"
                                                            >
                                                                {{
                                                                    request.rejection_reason
                                                                }}
                                                            </p>
                                                        </div>
                                                    </PopoverContent>
                                                </Popover>
                                            </div>
                                        </div>

                                        <div class="flex-shrink-0">
                                            <MoreHorizontal
                                                class="h-4 w-4 text-muted-foreground"
                                            />
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </template>
                    </div>

                    <div
                        v-if="
                            !props.changeRequests ||
                            props.changeRequests.length === 0
                        "
                        class="py-8 text-center"
                    >
                        <FileText
                            class="mx-auto mb-3 h-12 w-12 text-muted-foreground opacity-40"
                        />
                        <p class="text-sm font-medium text-muted-foreground">
                            No change requests yet
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Your change requests will appear here
                        </p>
                    </div>
                </div>
            </DialogContent>
        </Dialog>

        <!-- ── Create / Edit Dialog ──────────────────── -->
        <Dialog v-model:open="dialogOpen">
            <DialogContent class="rounded-2xl sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{{
                        isEditing ? 'Edit Dispatch' : 'Create Dispatch'
                    }}</DialogTitle>
                    <DialogDescription>
                        {{
                            isEditing
                                ? 'Update the dispatch details below.'
                                : 'Arrival time is automatically set to now.'
                        }}
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="submit">
                    <div class="space-y-2">
                        <Label for="vehicle_id">Vehicle</Label>
                        <Select v-model="form.vehicle_id">
                            <SelectTrigger id="vehicle_id">
                                <SelectValue placeholder="Select a vehicle" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="vehicle in vehicles"
                                    :key="vehicle.id"
                                    :value="String(vehicle.id)"
                                >
                                    {{ vehicle.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.vehicle_id" />
                    </div>

                    <div class="space-y-2">
                        <Label for="driver_user_id">Driver</Label>
                        <Select v-model="form.driver_user_id">
                            <SelectTrigger id="driver_user_id">
                                <SelectValue placeholder="Assign a driver" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="unassigned"
                                    >No driver assigned</SelectItem
                                >
                                <SelectItem
                                    v-for="driver in drivers"
                                    :key="driver.id"
                                    :value="String(driver.id)"
                                >
                                    {{ driver.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.driver_user_id" />
                    </div>

                    <div class="space-y-2">
                        <Label for="gate_id">Gate</Label>
                        <Select
                            :model-value="form.gate_id"
                            @update:model-value="onGateChange"
                        >
                            <SelectTrigger id="gate_id">
                                <SelectValue placeholder="Select a gate" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="gate in gates"
                                    :key="gate.id"
                                    :value="String(gate.id)"
                                >
                                    {{ gate.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.gate_id" />
                    </div>

                    <div class="space-y-2">
                        <Label for="bay_number">Bay Number</Label>
                        <Select
                            v-model="form.bay_number"
                            :disabled="!selectedGate"
                        >
                            <SelectTrigger id="bay_number">
                                <SelectValue placeholder="Select a bay" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="bay in bayOptions"
                                    :key="bay.value"
                                    :value="String(bay.value)"
                                >
                                    {{ bay.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.bay_number" />
                    </div>

                    <!-- Selection summary chips -->
                    <div
                        v-if="selectedVehicle || selectedGate || selectedDriver"
                        class="flex flex-wrap gap-2 rounded-lg border border-slate-200 bg-slate-50 p-3"
                    >
                        <div
                            v-if="selectedVehicle"
                            class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 shadow-sm"
                        >
                            <Bus class="h-3 w-3 text-slate-400" />
                            {{ selectedVehicle.plate_number }}
                        </div>
                        <div
                            v-if="selectedDriver"
                            class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 shadow-sm"
                        >
                            <UserRound class="h-3 w-3 text-slate-400" />
                            {{ selectedDriver.name }}
                        </div>
                        <div
                            v-if="selectedGate"
                            class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 shadow-sm"
                        >
                            <span class="text-muted-foreground">Gate</span>
                            {{ selectedGate.gate_name }} · {{ selectedGate.bays }} bays
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="remarks">
                            Remarks
                            <span class="ml-1 text-xs font-normal text-muted-foreground">(optional)</span>
                        </Label>
                        <Input
                            id="remarks"
                            v-model="form.remarks"
                            placeholder="Optional remarks or notes"
                        />
                        <InputError :message="form.errors.remarks" />
                    </div>

                    <DialogFooter class="pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            class="rounded-lg"
                            @click="dialogOpen = false"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-lg border-0 bg-blue-700 text-white hover:bg-blue-800"
                        >
                            <Send class="mr-2 h-4 w-4" />
                            {{ form.processing ? 'Saving…' : isEditing ? 'Save Changes' : 'Create Dispatch' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- ── Remarks View Dialog ───────────────────── -->
        <Dialog v-model:open="remarksViewOpen">
            <DialogContent class="rounded-2xl sm:max-w-sm">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <FileText class="h-4 w-4" />
                        Dispatch Remarks
                    </DialogTitle>
                    <DialogDescription v-if="viewingDispatch">
                        {{ viewingDispatch.plate_number }} ·
                        {{ viewingDispatch.gate?.gate_name ?? '—' }} · Bay
                        {{ viewingDispatch.bay_number }}
                    </DialogDescription>
                </DialogHeader>

                <Separator class="bg-slate-100" />

                <div
                    class="min-h-[80px] rounded-lg border bg-muted/30 p-4 text-sm leading-relaxed"
                    :class="!viewingDispatch?.remarks ? 'italic text-muted-foreground' : ''"
                >
                    {{ viewingDispatch?.remarks || 'No remarks recorded.' }}
                </div>

                <div
                    v-if="viewingDispatch"
                    class="grid grid-cols-2 gap-3 text-xs"
                >
                    <div class="space-y-0.5">
                        <p class="font-semibold uppercase tracking-widest text-slate-400">Driver</p>
                        <p
                            class="font-semibold"
                            :class="!viewingDispatch.driver ? 'italic text-muted-foreground' : ''"
                        >
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
                    <Button
                        variant="outline"
                        class="w-full rounded-lg"
                        @click="remarksViewOpen = false"
                    >
                        Close
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- ── Mark Departed Confirmation ───────────── -->
        <AlertDialog v-model:open="confirmDepartOpen">
            <AlertDialogContent class="rounded-2xl">
                <form class="space-y-4" @submit.prevent="confirmDepart">
                    <AlertDialogHeader>
                        <AlertDialogTitle>Mark dispatch as departed?</AlertDialogTitle>
                        <AlertDialogDescription>
                            <span class="block">
                                This will record the departure time as
                                <strong>now</strong>.
                            </span>
                            <span class="mt-1 block">
                                Departed dispatches can no longer be edited.
                            </span>
                        </AlertDialogDescription>
                    </AlertDialogHeader>

                    <div class="space-y-4">
                        <!-- Dispatch summary -->
                        <div
                            v-if="pendingDepartDispatch"
                            class="rounded-lg border border-slate-200 bg-slate-50 p-3"
                        >
                            <p class="text-sm font-semibold text-slate-800">
                                {{ pendingDepartDispatch.plate_number }}
                            </div>
                            <div class="text-xs text-muted-foreground">
                                {{ pendingDepartDispatch.gate?.gate_name ?? '—' }} · Bay
                                {{ pendingDepartDispatch.bay_number }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="depart_pax_count"
                                >Passenger Count</Label
                            >
                            <Input
                                id="depart_pax_count"
                                v-model="departForm.pax_count"
                                type="number"
                                min="0"
                                placeholder="Enter passenger count"
                            />
                            <InputError
                                :message="departForm.errors.pax_count"
                            />
                        </div>
                    </div>

                    <AlertDialogFooter>
                        <AlertDialogCancel
                            type="button"
                            class="rounded-lg"
                            :disabled="departForm.processing"
                        >
                            Cancel
                        </AlertDialogCancel>

                        <Button type="submit" :disabled="departForm.processing">
                            {{ departForm.processing ? 'Saving…' : 'Confirm Departure' }}
                        </Button>
                    </AlertDialogFooter>
                </form>
            </AlertDialogContent>
        </AlertDialog>

        <!-- Change Request Modal -->
        <Dialog v-model:open="changeRequestOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Send class="h-4 w-4" />
                        Request Change
                    </DialogTitle>
                    <DialogDescription v-if="changeRequestDispatch">
                        Submit a request to change dispatch
                        {{ changeRequestDispatch.plate_number }}
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="submitChangeRequest">
                    <div
                        class="rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-200"
                    >
                        <p class="font-medium">ℹ Notice</p>
                        <p class="mt-1 text-xs">
                            This dispatch has already departed. Changes require
                            approval from management before they are applied.
                        </p>
                    </div>

                    <div class="rounded-lg border bg-muted/30 p-3 text-sm">
                        <div class="font-medium">
                            {{ changeRequestDispatch?.plate_number }}
                        </div>
                        <div class="text-xs text-muted-foreground">
                            {{
                                changeRequestDispatch?.gate?.gate_name ?? '—'
                            }}
                            · Bay
                            {{ changeRequestDispatch?.bay_number }}
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="change_field"
                            >What do you want to change?</Label
                        >
                        <Select v-model="changeRequestForm.requested_field">
                            <SelectTrigger id="change_field">
                                <SelectValue
                                    placeholder="Select a field to change"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="field in changeRequestFields"
                                    :key="field.value"
                                    :value="field.value"
                                >
                                    {{ field.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError
                            :message="changeRequestForm.errors.requested_field"
                        />
                    </div>

                    <!-- Field-specific input based on selected field -->
                    <div
                        v-if="changeRequestForm.requested_field"
                        class="space-y-3"
                    >
                        <!-- Driver Change -->
                        <template
                            v-if="
                                changeRequestForm.requested_field ===
                                'driver_user_id'
                            "
                        >
                            <div>
                                <Label for="change_driver"
                                    >Select New Driver</Label
                                >
                                <Select
                                    v-model="changeRequestForm.requested_value"
                                    @update:model-value="
                                        validateDriverAvailability
                                    "
                                >
                                    <SelectTrigger id="change_driver">
                                        <SelectValue
                                            placeholder="Select a driver"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="driver in props.drivers"
                                            :key="driver.id"
                                            :value="String(driver.id)"
                                            :disabled="
                                                changeRequestDispatch &&
                                                changeRequestDispatch.driver
                                                    ?.id === driver.id
                                            "
                                        >
                                            <span
                                                v-if="
                                                    changeRequestDispatch &&
                                                    changeRequestDispatch.driver
                                                        ?.id === driver.id
                                                "
                                            >
                                                ✓ {{ driver.label }} (Currently
                                                Assigned)
                                            </span>
                                            <span v-else>{{
                                                driver.label
                                            }}</span>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError
                                    :message="
                                        changeRequestForm.errors.requested_value
                                    "
                                />
                            </div>

                            <!-- Driver Warning/Confirmation -->
                            <div
                                v-if="driverValidationWarning"
                                class="space-y-3 rounded-lg border border-amber-200 bg-amber-50 p-3"
                            >
                                <p class="text-sm text-amber-900">
                                    {{ driverValidationWarning }}
                                </p>
                                <Button
                                    type="button"
                                    size="sm"
                                    @click="driverConfirmed = true"
                                    class="bg-amber-600 hover:bg-amber-700"
                                >
                                    Confirm Driver Change
                                </Button>
                            </div>

                            <div
                                v-else-if="
                                    changeRequestForm.requested_value &&
                                    !driverValidationLoading
                                "
                                class="rounded-lg border border-emerald-200 bg-emerald-50 p-3"
                            >
                                <p class="text-sm text-emerald-900">
                                    ✓ Driver is available
                                </p>
                            </div>
                        </template>

                        <!-- Passenger Count -->
                        <template
                            v-else-if="
                                changeRequestForm.requested_field ===
                                'pax_count'
                            "
                        >
                            <div>
                                <Label for="change_pax"
                                    >New Passenger Count</Label
                                >
                                <Input
                                    id="change_pax"
                                    v-model="changeRequestForm.requested_value"
                                    type="number"
                                    min="0"
                                    placeholder="Enter new passenger count"
                                    @input="
                                        changeRequestForm.requested_value =
                                            String($event.target.value)
                                    "
                                />
                                <InputError
                                    :message="
                                        changeRequestForm.errors.requested_value
                                    "
                                />
                                <p class="mt-1 text-xs text-muted-foreground">
                                    Current:
                                    {{
                                        changeRequestDispatch?.pax_count ?? 0
                                    }}
                                    passengers
                                </p>
                            </div>
                        </template>

                        <!-- Vehicle Change -->
                        <template
                            v-else-if="
                                changeRequestForm.requested_field ===
                                'vehicle_id'
                            "
                        >
                            <div>
                                <Label for="change_vehicle"
                                    >Select New Vehicle</Label
                                >
                                <Select
                                    v-model="changeRequestForm.requested_value"
                                >
                                    <SelectTrigger id="change_vehicle">
                                        <SelectValue
                                            placeholder="Select a vehicle"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="vehicle in props.vehicles"
                                            :key="vehicle.id"
                                            :value="String(vehicle.id)"
                                            :disabled="
                                                changeRequestDispatch &&
                                                changeRequestDispatch.vehicle
                                                    ?.id === vehicle.id
                                            "
                                        >
                                            <span
                                                v-if="
                                                    changeRequestDispatch &&
                                                    changeRequestDispatch
                                                        .vehicle?.id ===
                                                        vehicle.id
                                                "
                                            >
                                                ✓ {{ vehicle.label }} (Currently
                                                Assigned)
                                            </span>
                                            <span v-else>{{
                                                vehicle.label
                                            }}</span>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError
                                    :message="
                                        changeRequestForm.errors.requested_value
                                    "
                                />
                            </div>
                        </template>

                        <!-- Gate & Bay Combined -->
                        <template
                            v-else-if="
                                changeRequestForm.requested_field === 'gate_id'
                            "
                        >
                            <div>
                                <Label for="change_gate">Select New Gate</Label>
                                <Select
                                    v-model="changeRequestForm.requested_value"
                                >
                                    <SelectTrigger id="change_gate">
                                        <SelectValue
                                            placeholder="Select a gate"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="gate in props.gates"
                                            :key="gate.id"
                                            :value="String(gate.id)"
                                            :disabled="
                                                changeRequestDispatch &&
                                                changeRequestDispatch.gate
                                                    ?.id === gate.id
                                            "
                                        >
                                            <span
                                                v-if="
                                                    changeRequestDispatch &&
                                                    changeRequestDispatch.gate
                                                        ?.id === gate.id
                                                "
                                            >
                                                ✓ {{ gate.label }} (Currently
                                                Assigned)
                                            </span>
                                            <span v-else>{{ gate.label }}</span>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError
                                    :message="
                                        changeRequestForm.errors.requested_value
                                    "
                                />
                            </div>

                            <div>
                                <Label for="change_bay_optional">
                                    Select New Bay
                                    <span class="text-xs text-muted-foreground"
                                        >(optional)</span
                                    >
                                </Label>
                                <Select v-model="changeRequestBay">
                                    <SelectTrigger id="change_bay_optional">
                                        <SelectValue
                                            placeholder="Select a bay number (optional)"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="n in 10"
                                            :key="n"
                                            :value="String(n)"
                                            :disabled="
                                                changeRequestDispatch &&
                                                changeRequestDispatch.bay_number ===
                                                    n
                                            "
                                        >
                                            <span
                                                v-if="
                                                    changeRequestDispatch &&
                                                    changeRequestDispatch.bay_number ===
                                                        n
                                                "
                                            >
                                                ✓ Bay {{ n }} (Currently
                                                Assigned)
                                            </span>
                                            <span v-else>Bay {{ n }}</span>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    Current: Gate
                                    {{
                                        changeRequestDispatch?.gate
                                            ?.gate_name ?? '—'
                                    }}
                                    · Bay
                                    {{ changeRequestDispatch?.bay_number }}
                                </p>
                            </div>
                        </template>
                    </div>

                    <div class="space-y-2">
                        <Label for="change_reason">Reason for Change</Label>
                        <textarea
                            id="change_reason"
                            v-model="changeRequestForm.reason"
                            placeholder="Explain why this change is needed..."
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            rows="3"
                        />
                        <InputError
                            :message="changeRequestForm.errors.reason"
                        />
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            class="w-full md:w-auto"
                            @click="closeChangeRequestModal"
                            :disabled="changeRequestForm.processing"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            class="w-full md:w-auto"
                            :disabled="
                                changeRequestForm.processing ||
                                (driverValidationWarning !== null &&
                                    !driverConfirmed) ||
                                (changeRequestForm.requested_field ===
                                    'pax_count' &&
                                    parseInt(
                                        changeRequestForm.requested_value,
                                    ) === changeRequestDispatch?.pax_count) ||
                                (changeRequestForm.requested_field ===
                                    'gate_id' &&
                                    !changeRequestForm.requested_value &&
                                    !changeRequestBay)
                            "
                        >
                            {{
                                changeRequestForm.processing
                                    ? 'Submitting…'
                                    : 'Submit Request'
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Change Request Detail Dialog -->
        <Dialog v-model:open="changeRequestDetailOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader v-if="selectedChangeRequest">
                    <DialogTitle class="flex items-center gap-2">
                        <span>Change Request</span>
                        <Badge
                            :variant="
                                selectedChangeRequest.status === 'pending'
                                    ? 'secondary'
                                    : selectedChangeRequest.status ===
                                        'approved'
                                      ? 'default'
                                      : 'destructive'
                            "
                            class="capitalize"
                        >
                            {{ selectedChangeRequest.status }}
                        </Badge>
                    </DialogTitle>
                    <DialogDescription>
                        {{ selectedChangeRequest.dispatch.plate_number }} ·
                        {{
                            new Date(
                                selectedChangeRequest.created_at || '',
                            ).toLocaleDateString()
                        }}
                    </DialogDescription>
                </DialogHeader>

                <Separator />

                <div v-if="selectedChangeRequest" class="space-y-4">
                    <!-- Field Details -->
                    <div class="space-y-3">
                        <div>
                            <p
                                class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                Field
                            </p>
                            <p class="mt-1 text-sm font-medium">
                                {{
                                    selectedChangeRequest.field_label ||
                                    formatFieldLabel(
                                        selectedChangeRequest.requested_field,
                                    )
                                }}
                            </p>
                        </div>

                        <div>
                            <p
                                class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                Current Value
                            </p>
                            <p class="mt-1 font-mono text-sm">
                                {{
                                    formatValue(selectedChangeRequest.old_value)
                                }}
                            </p>
                        </div>

                        <div>
                            <p
                                class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                Requested Value
                            </p>
                            <p class="mt-1 font-mono text-sm">
                                {{
                                    formatValue(
                                        selectedChangeRequest.requested_value,
                                    )
                                }}
                            </p>
                        </div>

                        <div>
                            <p
                                class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                Reason
                            </p>
                            <p class="mt-1 text-sm">
                                {{ selectedChangeRequest.reason }}
                            </p>
                        </div>
                    </div>

                    <!-- Rejection Reason -->
                    <div
                        v-if="
                            selectedChangeRequest.status === 'rejected' &&
                            selectedChangeRequest.rejection_reason
                        "
                        class="space-y-2 rounded-lg border border-red-200 bg-red-50 p-4"
                    >
                        <div class="flex items-start gap-2">
                            <XCircle
                                class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-600"
                            />
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-red-900">
                                    Rejection Reason
                                </p>
                                <p class="mt-1 text-sm text-red-800">
                                    {{ selectedChangeRequest.rejection_reason }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Approval Confirmation -->
                    <div
                        v-if="selectedChangeRequest.status === 'approved'"
                        class="flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 p-4"
                    >
                        <CheckCircle2
                            class="h-5 w-5 flex-shrink-0 text-emerald-600"
                        />
                        <p class="text-sm font-medium text-emerald-900">
                            This request has been approved
                        </p>
                    </div>

                    <!-- Pending Status -->
                    <div
                        v-if="selectedChangeRequest.status === 'pending'"
                        class="flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 p-4"
                    >
                        <Clock3 class="h-5 w-5 flex-shrink-0 text-amber-600" />
                        <p class="text-sm font-medium text-amber-900">
                            Awaiting approval from administrator
                        </p>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </ExternalLayout>
</template>