<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

import InertiaPagination from '@/components/InertiaPagination.vue';
import InputError from '@/components/InputError.vue';
import SearchInput from '@/components/SearchInput.vue';
import ExternalLayout from '@/layouts/ExternalLayout.vue';
import { can } from '@/lib/can';

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
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
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
import {
    Card,
    CardContent,
    CardHeader,
    CardDescription,
    CardTitle
} from '@/components/ui/card';
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
    ArrowUpRight,
    Building2,
    Bus,
    CalendarClock,
    CalendarDays,
    CheckCircle2,
    Clock3,
    FileText,
    Filter,
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
    route?: {
        id: number;
        gate_id?: number | null;
        route_name?: string | null;
        origin_name?: string | null;
        destination_name?: string | null;
        status?: string | null;
        gate?: {
            id: number;
            gate_name?: string | null;
            status?: string | null;
        } | null;
    } | null;
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
    assigned_driver_ids_today: number[];
    assigned_vehicle_ids_active: number[];
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
        old_value_display?: string | null;
        requested_value: unknown;
        requested_value_display?: string | null;
        reason: string;
        status: string;
        rejection_reason: string | null;
        field_label: string | null;
        created_at: string | null;
    }>;
}>();

const canCreateDispatch = can('external_dispatches.create');
const canUpdateDispatch = can('external_dispatches.update');
const canDepartDispatch = can('external_dispatches.depart');

/* ======================================================
   Date filter
====================================================== */
const df = new DateFormatter('en-US', { dateStyle: 'medium' });
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

/* ======================================================
   Dialog / form state
====================================================== */
const dialogOpen = ref(false);
const editingDispatchId = ref<number | null>(null);
const confirmDepartOpen = ref(false);
const pendingDepartId = ref<number | null>(null);
const pendingDepartDispatch = ref<DispatchItem | null>(null);
const remarksViewOpen = ref(false);
const viewingDispatch = ref<DispatchItem | null>(null);

const form = useForm({
    vehicle_id: '',
    driver_user_id: 'unassigned',
    gate_id: '',
    bay_number: '',
    remarks: '',
});

const departForm = useForm({ pax_count: '' });

const changeRequestOpen = ref(false);
const changeRequestDispatch = ref<DispatchItem | null>(null);
const driverValidationWarning = ref<string | null>(null);
const changeRequestStatusOpen = ref(false);
const changeRequestForm = useForm({
    requested_field: '',
    requested_value: '',
    reason: '',
});
const changeRequestBay = ref<string>('');

type ChangeRequestField =
    | 'driver_user_id'
    | 'pax_count'
    | 'vehicle_id'
    | 'gate_id'
    | 'bay_number';
const changeRequestFields: Array<{ value: ChangeRequestField; label: string }> =
    [
        { value: 'driver_user_id', label: 'Change Driver Assignment' },
        { value: 'pax_count', label: 'Update Passenger Count' },
        { value: 'vehicle_id', label: 'Change Vehicle' },
        { value: 'gate_id', label: 'Change Gate & Bay' },
    ];

/* ======================================================
   Computed
====================================================== */
const selectedVehicle = computed(
    () =>
        props.vehicles.find((v) => String(v.id) === String(form.vehicle_id)) ??
        null,
);
const selectedGate = computed(
    () =>
        props.gates.find((g) => String(g.id) === String(form.gate_id)) ?? null,
);
const selectedDriver = computed(() => {
    if (form.driver_user_id === 'unassigned') return null;
    return (
        props.drivers.find(
            (d) => String(d.id) === String(form.driver_user_id),
        ) ?? null
    );
});
const bayOptions = computed(() => selectedGate.value?.bay_options ?? []);
const isEditing = computed(() => editingDispatchId.value !== null);
const isGateAutoLocked = computed(
    () => !!selectedVehicle.value?.route?.gate_id,
);
const selectedVehicleRouteGateInactive = computed(
    () => selectedVehicle.value?.route?.gate?.status === 'inactive',
);
const selectedVehicleRouteGateName = computed(
    () => selectedVehicle.value?.route?.gate?.gate_name ?? 'this route gate',
);
const assignedDriverIdsToday = computed(
    () => new Set(props.assigned_driver_ids_today ?? []),
);
const assignedVehicleIdsActive = computed(
    () => new Set(props.assigned_vehicle_ids_active ?? []),
);
const editingDispatch = computed(
    () =>
        props.dispatches.data.find((d) => d.id === editingDispatchId.value) ??
        null,
);

const arrivedCount = computed(
    () => props.dispatches.data.filter((d) => d.status === 'arrived').length,
);
const departedCount = computed(
    () => props.dispatches.data.filter((d) => d.status === 'departed').length,
);
const totalPax = computed(() =>
    props.dispatches.data.reduce((s, d) => s + (d.pax_count ?? 0), 0),
);
const pendingChangeRequests = computed(
    () => props.changeRequests?.filter((r) => r.status === 'pending') ?? [],
);
const approvedChangeRequests = computed(
    () => props.changeRequests?.filter((r) => r.status === 'approved') ?? [],
);
const rejectedChangeRequests = computed(
    () => props.changeRequests?.filter((r) => r.status === 'rejected') ?? [],
);

function isDriverDisabledForDispatchForm(driverId: number): boolean {
    if (editingDispatch.value?.driver?.id === driverId) {
        return false;
    }

    return assignedDriverIdsToday.value.has(driverId);
}

function isVehicleDisabledForDispatchForm(vehicleId: number): boolean {
    if (editingDispatch.value?.vehicle?.id === vehicleId) {
        return false;
    }

    return assignedVehicleIdsActive.value.has(vehicleId);
}

function isDriverDisabledForChangeRequest(driverId: number): boolean {
    if (changeRequestDispatch.value?.driver?.id === driverId) {
        return true;
    }

    return assignedDriverIdsToday.value.has(driverId);
}

/* ======================================================
   Methods
====================================================== */
function onGateChange(value: unknown) {
    form.gate_id = String(value ?? '');
    form.bay_number = '';
}

function onVehicleChange(value: string) {
    form.vehicle_id = value;

    const vehicle = props.vehicles.find((v) => String(v.id) === value) ?? null;
    const routeGateId = vehicle?.route?.gate_id
        ? String(vehicle.route.gate_id)
        : '';

    if (vehicle?.route?.gate?.status === 'inactive') {
        form.gate_id = '';
        form.bay_number = '';
        return;
    }

    if (!routeGateId) {
        return;
    }

    if (form.gate_id !== routeGateId) {
        form.gate_id = routeGateId;
        form.bay_number = '';
    }
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
    if (!canCreateDispatch) return;

    editingDispatchId.value = null;
    resetForm();
    dialogOpen.value = true;
}

function openEditDialog(dispatch: DispatchItem) {
    if (!canUpdateDispatch) return;
    if (dispatch.status === 'departed') return;
    editingDispatchId.value = dispatch.id;
    form.transform((d) => d);
    form.clearErrors();
    form.vehicle_id = dispatch.vehicle?.id ? String(dispatch.vehicle.id) : '';
    onVehicleChange(form.vehicle_id);
    form.driver_user_id = dispatch.driver?.id
        ? String(dispatch.driver.id)
        : 'unassigned';
    if (!selectedVehicle.value?.route?.gate_id) {
        form.gate_id = dispatch.gate?.id ? String(dispatch.gate.id) : '';
    }
    form.bay_number = String(dispatch.bay_number ?? '');
    form.remarks = dispatch.remarks ?? '';
    dialogOpen.value = true;
}

function openRemarksDialog(dispatch: DispatchItem) {
    viewingDispatch.value = dispatch;
    remarksViewOpen.value = true;
}

function submit() {
    if (isEditing.value && !canUpdateDispatch) return;
    if (!isEditing.value && !canCreateDispatch) return;

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
    if (!canDepartDispatch) return;

    pendingDepartId.value = dispatch.id;
    pendingDepartDispatch.value = dispatch;
    resetDepartForm();
    departForm.pax_count = String(dispatch.pax_count ?? '');
    confirmDepartOpen.value = true;
}

function confirmDepart() {
    if (!pendingDepartId.value) return;
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

function openChangeRequestModal(dispatch: DispatchItem) {
    changeRequestDispatch.value = dispatch;
    changeRequestForm.reset();
    changeRequestBay.value = '';
    driverValidationWarning.value = null;
    changeRequestOpen.value = true;
}

function closeChangeRequestModal() {
    changeRequestOpen.value = false;
    changeRequestDispatch.value = null;
    changeRequestForm.reset();
    changeRequestBay.value = '';
    driverValidationWarning.value = null;
}

function validateDriverAvailability(driverId: unknown) {
    if (changeRequestForm.requested_field !== 'driver_user_id') return;

    driverValidationWarning.value = null;

    const normalizedDriverId = String(driverId ?? '');

    const driver = props.drivers.find(
        (d) => String(d.id) === normalizedDriverId,
    );
    if (!driver) {
        return;
    }

    if (isDriverDisabledForChangeRequest(driver.id)) {
        driverValidationWarning.value = `Driver ${driver.name} is already assigned today and cannot be selected.`;
    }
}

watch(
    () => [
        changeRequestForm.requested_field,
        changeRequestForm.requested_value,
        changeRequestDispatch.value?.id,
    ],
    () => {
        if (changeRequestForm.requested_field !== 'driver_user_id') {
            driverValidationWarning.value = null;
            return;
        }

        validateDriverAvailability(changeRequestForm.requested_value);
    },
);

function submitChangeRequest() {
    if (!changeRequestDispatch.value) return;
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
    if (driverValidationWarning.value) {
        window.$toast?.error(driverValidationWarning.value);
        return;
    }
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
        if (!gateChanged && bayChanged) {
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
            });
        } else {
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
                },
            );
        }
    } else {
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
            },
        );
    }
}

function statusClass(status?: string | null) {
    if (status === 'arrived')
        return 'bg-emerald-100 text-emerald-700 border-emerald-200';
    if (status === 'departed') return 'bg-slate-100 text-slate-500 border-0';
    if (status === 'pending')
        return 'bg-amber-100 text-amber-700 border-amber-200';
    return 'bg-slate-100 text-slate-500 border-0';
}

function statusDot(status?: string | null) {
    if (status === 'arrived') return 'bg-emerald-500';
    if (status === 'departed') return 'bg-slate-400';
    if (status === 'pending') return 'bg-amber-400';
    return 'bg-slate-400';
}

function statusLabel(status?: string | null) {
    if (!status) return 'Unknown';
    return status.replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function formatFieldLabel(field: string): string {
    return field.replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function formatValue(value: unknown): string {
    if (value === null) return '—';
    if (typeof value === 'boolean') return value ? 'Yes' : 'No';
    if (typeof value === 'object') return JSON.stringify(value);
    return String(value);
}

type ChangeRequestItem = NonNullable<typeof props.changeRequests>[number];

function formatChangeValue(
    request: ChangeRequestItem,
    valueType: 'old' | 'requested',
): string {
    const displayValue =
        valueType === 'old'
            ? request.old_value_display
            : request.requested_value_display;

    if (
        displayValue !== null &&
        displayValue !== undefined &&
        displayValue !== ''
    ) {
        return String(displayValue);
    }

    const rawValue =
        valueType === 'old' ? request.old_value : request.requested_value;

    if (rawValue === null || rawValue === undefined || rawValue === '') {
        return '—';
    }

    const numericId = Number(rawValue);
    if (!Number.isFinite(numericId)) {
        return formatValue(rawValue);
    }

    if (request.requested_field === 'driver_user_id') {
        const driver = props.drivers.find((item) => item.id === numericId);
        return driver?.name ?? `Unknown Driver (#${numericId})`;
    }

    if (request.requested_field === 'vehicle_id') {
        const vehicle = props.vehicles.find((item) => item.id === numericId);
        return vehicle?.label ?? `Unknown Vehicle (#${numericId})`;
    }

    if (request.requested_field === 'gate_id') {
        const gate = props.gates.find((item) => item.id === numericId);
        return gate?.gate_name ?? `Unknown Gate (#${numericId})`;
    }

    return formatValue(rawValue);
}

const changeRequestDetailOpen = ref(false);
const selectedChangeRequest = ref<(typeof props.changeRequests)[0] | null>(
    null,
);

function openChangeRequestDetail(request: (typeof props.changeRequests)[0]) {
    selectedChangeRequest.value = request;
    changeRequestDetailOpen.value = true;
}

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

    <ExternalLayout :company="company">
        <div class="min-h-screen bg-slate-50/60">
            <div class="mx-auto max-w-7xl space-y-6 p-4 md:p-8">
                <!-- ── Page header ───────────────────────────── -->
                <div
                    class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div class="space-y-1">
                        <div
                            class="flex items-center gap-2 text-xs font-semibold tracking-widest text-slate-400 uppercase"
                        >
                            <Building2 class="h-3.5 w-3.5" />
                            {{ company.company_code ?? company.company_name }}
                        </div>
                        <h1
                            class="text-2xl font-bold tracking-tight text-slate-900"
                        >
                            Dispatching Module
                        </h1>
                        <p class="text-sm text-slate-500">
                            Manage and monitor vehicle dispatches for
                            <span class="font-medium text-slate-700">{{
                                company.company_name
                            }}</span
                            >.
                        </p>
                    </div>

                    <Button
                        v-if="canCreateDispatch"
                        @click="openCreateDialog"
                        variant="blue"
                    >
                        <Plus class="h-4 w-4" />
                        Add Dispatch
                    </Button>
                </div>

                <!-- ── Stats ─────────────────────────────────── -->
                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                    <!-- Total -->
                    <div
                        class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:shadow-md"
                    >
                        <div class="flex items-start justify-between">
                            <p
                                class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase"
                            >
                                Total Dispatches
                            </p>
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-700"
                            >
                                <CalendarClock class="h-4 w-4 text-white" />
                            </div>
                        </div>
                        <p
                            class="mt-3 text-3xl font-bold text-slate-900 tabular-nums"
                        >
                            {{ dispatches.total }}
                        </p>
                    </div>

                    <!-- On-site -->
                    <div
                        class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:shadow-md"
                    >
                        <div class="flex items-start justify-between">
                            <p
                                class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase"
                            >
                                On-site
                            </p>
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-600"
                            >
                                <Clock3 class="h-4 w-4 text-white" />
                            </div>
                        </div>
                        <p
                            class="mt-3 text-3xl font-bold text-slate-900 tabular-nums"
                        >
                            {{ arrivedCount }}
                        </p>
                    </div>

                    <!-- Departed -->
                    <div
                        class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:shadow-md"
                    >
                        <div class="flex items-start justify-between">
                            <p
                                class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase"
                            >
                                Departed
                            </p>
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-700"
                            >
                                <LogOut class="h-4 w-4 text-white" />
                            </div>
                        </div>
                        <p
                            class="mt-3 text-3xl font-bold text-slate-900 tabular-nums"
                        >
                            {{ departedCount }}
                        </p>
                    </div>

                    <!-- Passengers -->
                    <div
                        class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:shadow-md"
                    >
                        <div class="flex items-start justify-between">
                            <p
                                class="text-[11px] font-semibold tracking-widest text-slate-400 uppercase"
                            >
                                Passengers
                            </p>
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-600"
                            >
                                <TrendingUp class="h-4 w-4 text-white" />
                            </div>
                        </div>
                        <p
                            class="mt-3 text-3xl font-bold text-slate-900 tabular-nums"
                        >
                            {{ totalPax }}
                        </p>
                    </div>
                </div>

                <!-- ── Table card ─────────────────────────────── -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <!-- <p class="block overflow-hidden text-ellipsis">Dispatches</p> -->
                            <!-- Dispatch Records -->
                             Dispatches
                            <div class="ml-2 flex w-full items-center">
                                <hr class="h-px w-full border border-rose-500" />
                                <div class="rounded-xs border-7 border-rose-500">
                                    <div class="rounded-xs border-3 border-white"></div>
                                </div>
                            </div>
                        </CardTitle>
                        <CardDescription class="mt-1">
                            Arrival time is automatically recorded on dispatch creation.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div
                            class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between"
                        >
                            <!-- Change Requests badge button -->
                            <Button
                                v-if="
                                    props.changeRequests &&
                                    props.changeRequests.length > 0
                                "
                                variant="outline"
                                size="sm"
                                class="relative gap-1.5 rounded-lg border-slate-200 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                @click="changeRequestStatusOpen = true"
                            >
                                <FileText class="h-3.5 w-3.5" />
                                Change Requests
                                <span
                                    class="flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[10px] font-bold text-white"
                                >
                                    {{ props.changeRequests.length }}
                                </span>
                            </Button>

                            <!-- Search -->
                            <div class="w-full sm:w-72">
                                <SearchInput
                                    :route="DispatchController.index().url"
                                    :initial-value="props.filters?.search ?? ''"
                                    placeholder="Search plate, remarks…"
                                    :only="['dispatches', 'filters']"
                                    class="rounded-lg shadow-sm"
                                />
                            </div>

                            <!-- Status filter -->
                            <Select
                                :model-value="props.filters?.status ?? 'all'"
                                @update:model-value="
                                    (value) => {
                                        router.get(
                                            DispatchController.index().url,
                                            {
                                                search:
                                                    props.filters?.search ||
                                                    undefined,
                                                status:
                                                    value === 'all'
                                                        ? undefined
                                                        : value,
                                                date:
                                                    props.filters?.date ||
                                                    undefined,
                                            },
                                            {
                                                preserveState: true,
                                                preserveScroll: true,
                                                replace: true,
                                                only: ['dispatches', 'filters'],
                                            },
                                        );
                                    }
                                "
                            >
                                <SelectTrigger class="w-full sm:w-36">
                                    <SelectValue placeholder="All statuses" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all"
                                        >All Statuses</SelectItem
                                    >
                                    <SelectItem value="arrived"
                                        >Arrived</SelectItem
                                    >
                                    <SelectItem value="departed"
                                        >Departed</SelectItem
                                    >
                                </SelectContent>
                            </Select>

                            <!-- Date picker -->
                            <Popover v-model:open="calendarOpen">
                                <PopoverTrigger as-child>
                                    <Button
                                        variant="outline"
                                        class="w-full justify-start gap-2 rounded-lg border-slate-200 sm:w-44"
                                        :class="
                                            selectedDate
                                                ? 'border-slate-400 bg-slate-50 text-slate-800'
                                                : 'text-slate-400'
                                        "
                                    >
                                        <CalendarDays
                                            class="h-4 w-4 shrink-0"
                                        />
                                        <span class="truncate text-sm">
                                            {{
                                                selectedDateLabel ??
                                                'Pick a date'
                                            }}
                                        </span>
                                        <span
                                            v-if="selectedDate"
                                            class="ml-auto flex h-4 w-4 shrink-0 items-center justify-center rounded-full hover:bg-slate-200"
                                            @click.stop="clearDateFilter"
                                        >
                                            <X class="h-3 w-3" />
                                        </span>
                                    </Button>
                                </PopoverTrigger>

                                <PopoverContent class="w-auto p-0" align="end">
                                    <div class="border-b px-3 py-2.5">
                                        <p
                                            class="text-xs font-semibold tracking-widest text-slate-400 uppercase"
                                        >
                                            Filter by Date
                                        </p>
                                    </div>
                                    <div
                                        class="flex gap-1.5 border-b px-3 py-2"
                                    >
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            class="h-7 flex-1 text-xs"
                                            :class="
                                                selectedDateLabel === 'Today'
                                                    ? 'border-slate-700 bg-slate-100 text-slate-800'
                                                    : ''
                                            "
                                            @click="setToday"
                                            >Today</Button
                                        >
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            class="h-7 flex-1 text-xs"
                                            :class="
                                                selectedDateLabel ===
                                                'Yesterday'
                                                    ? 'border-slate-700 bg-slate-100 text-slate-800'
                                                    : ''
                                            "
                                            @click="setYesterday"
                                            >Yesterday</Button
                                        >
                                        <Button
                                            v-if="selectedDate"
                                            size="sm"
                                            variant="ghost"
                                            class="h-7 flex-1 text-xs text-slate-400"
                                            @click="clearDateFilter"
                                            >Clear</Button
                                        >
                                    </div>
                                    <Calendar
                                        :model-value="selectedDate"
                                        :max-value="today(localTz)"
                                        initial-focus
                                        @update:model-value="
                                            (d) =>
                                                applyDateFilter(
                                                    d as
                                                        | CalendarDate
                                                        | undefined,
                                                )
                                        "
                                    />
                                </PopoverContent>
                            </Popover>
                        </div>
                    </CardContent>

                    <!-- Active date chip -->
                    <div
                        v-if="selectedDate"
                        class="border-b border-slate-100 px-5 py-2.5"
                    >
                        <div
                            class="inline-flex items-center gap-1.5 rounded-full border border-slate-300 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-600"
                        >
                            <CalendarDays class="h-3 w-3" />
                            Showing dispatches for {{ selectedDateLabel }}
                            <button
                                class="ml-1 rounded-full p-0.5 hover:bg-slate-200"
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
                                <TableRow
                                    class="border-slate-100 bg-slate-50/70 hover:bg-slate-50/70"
                                >
                                    <TableHead
                                        class="pl-5 text-[11px] font-bold tracking-widest text-slate-400 uppercase"
                                        >Vehicle</TableHead
                                    >
                                    <TableHead
                                        class="text-[11px] font-bold tracking-widest text-slate-400 uppercase"
                                        >Driver</TableHead
                                    >
                                    <TableHead
                                        class="text-[11px] font-bold tracking-widest text-slate-400 uppercase"
                                        >Gate / Bay</TableHead
                                    >
                                    <TableHead
                                        class="text-[11px] font-bold tracking-widest text-slate-400 uppercase"
                                        >Pax</TableHead
                                    >
                                    <TableHead
                                        class="text-[11px] font-bold tracking-widest text-slate-400 uppercase"
                                        >Status</TableHead
                                    >
                                    <TableHead
                                        class="text-[11px] font-bold tracking-widest text-slate-400 uppercase"
                                        >Arrived</TableHead
                                    >
                                    <TableHead
                                        class="text-[11px] font-bold tracking-widest text-slate-400 uppercase"
                                        >Departed</TableHead
                                    >
                                    <TableHead
                                        class="text-[11px] font-bold tracking-widest text-slate-400 uppercase"
                                        >Dispatcher</TableHead
                                    >
                                    <TableHead
                                        class="text-[11px] font-bold tracking-widest text-slate-400 uppercase"
                                        >Remarks</TableHead
                                    >
                                    <TableHead
                                        class="pr-5 text-right text-[11px] font-bold tracking-widest text-slate-400 uppercase"
                                        >Actions</TableHead
                                    >
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <!-- Empty state -->
                                <TableRow
                                    v-if="dispatches.data.length === 0"
                                    class="hover:bg-transparent"
                                >
                                    <TableCell
                                        colspan="10"
                                        class="py-20 text-center"
                                    >
                                        <div
                                            class="flex flex-col items-center gap-3"
                                        >
                                            <div
                                                class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100"
                                            >
                                                <CalendarDays
                                                    class="h-6 w-6 text-slate-400"
                                                />
                                            </div>
                                            <div>
                                                <p
                                                    class="text-sm font-semibold text-slate-600"
                                                >
                                                    {{
                                                        selectedDate
                                                            ? `No dispatches on ${selectedDateLabel}`
                                                            : 'No dispatch records found'
                                                    }}
                                                </p>
                                                <p
                                                    class="mt-0.5 text-xs text-slate-400"
                                                >
                                                    {{
                                                        selectedDate
                                                            ? 'Try a different date or clear the filter.'
                                                            : 'Try adjusting your search or filter.'
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <TableRow
                                    v-for="dispatch in dispatches.data"
                                    :key="dispatch.id"
                                    class="group border-slate-100 transition-colors hover:bg-slate-50/80"
                                    :class="
                                        dispatch.status === 'departed'
                                            ? 'opacity-60'
                                            : ''
                                    "
                                >
                                    <!-- Vehicle -->
                                    <TableCell class="pl-5">
                                        <div class="flex items-center gap-2.5">
                                            <div
                                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100"
                                            >
                                                <Bus
                                                    class="h-3.5 w-3.5 text-slate-600"
                                                />
                                            </div>
                                            <div>
                                                <p
                                                    class="text-sm font-semibold text-slate-800"
                                                >
                                                    {{ dispatch.plate_number }}
                                                </p>
                                                <p
                                                    class="text-xs text-slate-400"
                                                >
                                                    {{
                                                        dispatch.vehicle
                                                            ?.vehicle_type ??
                                                        '—'
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                                    </TableCell>

                                    <!-- Driver -->
                                    <TableCell>
                                        <div class="flex items-center gap-1.5">
                                            <UserRound
                                                class="h-3.5 w-3.5 text-slate-400"
                                            />
                                            <span
                                                class="text-sm"
                                                :class="
                                                    !dispatch.driver
                                                        ? 'text-slate-400 italic'
                                                        : 'text-slate-700'
                                                "
                                            >
                                                {{
                                                    dispatch.driver?.name ??
                                                    'Unassigned'
                                                }}
                                            </span>
                                        </div>
                                    </TableCell>

                                    <!-- Gate / Bay -->
                                    <TableCell>
                                        <p
                                            class="text-sm font-medium text-slate-700"
                                        >
                                            {{
                                                dispatch.gate?.gate_name ?? '—'
                                            }}
                                        </p>
                                        <p class="text-xs text-slate-400">
                                            Bay {{ dispatch.bay_number }}
                                        </p>
                                    </TableCell>

                                    <!-- Pax -->
                                    <TableCell>
                                        <div
                                            class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700"
                                        >
                                            <Users class="h-3 w-3" />
                                            {{ dispatch.pax_count }}
                                        </div>
                                    </TableCell>

                                    <!-- Status -->
                                    <TableCell>
                                        <span
                                            :class="[
                                                'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium',
                                                statusClass(dispatch.status),
                                            ]"
                                        >
                                            <span
                                                :class="[
                                                    'h-1.5 w-1.5 rounded-full',
                                                    statusDot(dispatch.status),
                                                ]"
                                            />
                                            {{ statusLabel(dispatch.status) }}
                                        </span>
                                    </TableCell>

                                    <!-- Arrived -->
                                    <TableCell class="text-xs text-slate-500">
                                        <div
                                            v-if="dispatch.arrived_at_formatted"
                                            class="flex items-center gap-1.5"
                                        >
                                            <Clock3
                                                class="h-3 w-3 text-slate-400"
                                            />
                                            {{ dispatch.arrived_at_formatted }}
                                        </div>
                                        <span v-else class="text-slate-400"
                                            >—</span
                                        >
                                    </TableCell>

                                    <!-- Departed -->
                                    <TableCell class="text-xs text-slate-500">
                                        <div
                                            v-if="
                                                dispatch.departed_at_formatted
                                            "
                                            class="flex items-center gap-1.5"
                                        >
                                            <CheckCircle2
                                                class="h-3 w-3 text-slate-400"
                                            />
                                            {{ dispatch.departed_at_formatted }}
                                        </div>
                                        <span v-else class="text-slate-400"
                                            >—</span
                                        >
                                    </TableCell>

                                    <!-- Dispatcher -->
                                    <TableCell>
                                        <div class="flex items-center gap-1.5">
                                            <Fingerprint
                                                class="h-3.5 w-3.5 text-slate-400"
                                            />
                                            <span
                                                class="text-sm text-slate-700"
                                            >
                                                {{
                                                    dispatch.dispatcher?.name ||
                                                    '—'
                                                }}
                                            </span>
                                        </div>
                                    </TableCell>

                                    <!-- Remarks -->
                                    <TableCell>
                                        <Button
                                            v-if="dispatch.remarks"
                                            variant="outline"
                                            size="sm"
                                            class="h-7 rounded-lg border-slate-200 text-xs text-slate-600 hover:bg-slate-50 hover:text-slate-800"
                                            @click="openRemarksDialog(dispatch)"
                                        >
                                            <FileText
                                                class="mr-1.5 h-3.5 w-3.5"
                                            />
                                            View
                                        </Button>
                                        <span
                                            v-else
                                            class="text-xs text-slate-400"
                                            >—</span
                                        >
                                    </TableCell>

                                    <!-- Actions -->
                                    <TableCell class="pr-5 text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    class="h-8 w-8 rounded-lg text-slate-400 opacity-0 group-hover:opacity-100 hover:bg-slate-100 hover:text-slate-700"
                                                >
                                                    <MoreHorizontal
                                                        class="h-4 w-4"
                                                    />
                                                </Button>
                                            </DropdownMenuTrigger>

                                            <DropdownMenuContent
                                                align="end"
                                                class="w-52 rounded-xl border-slate-200 shadow-lg"
                                            >
                                                <DropdownMenuLabel
                                                    class="text-xs font-semibold tracking-widest text-slate-400 uppercase"
                                                >
                                                    Actions
                                                </DropdownMenuLabel>
                                                <DropdownMenuSeparator
                                                    class="bg-slate-100"
                                                />

                                                <DropdownMenuItem
                                                    as-child
                                                    class="rounded-lg text-slate-700 focus:bg-slate-50 focus:text-slate-900"
                                                >
                                                    <Link
                                                        :href="
                                                            DispatchController.show(
                                                                dispatch.id,
                                                            ).url
                                                        "
                                                        class="flex items-center"
                                                    >
                                                        <ArrowUpRight
                                                            class="mr-2 h-4 w-4"
                                                        />
                                                        View Details
                                                    </Link>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    v-if="dispatch.remarks"
                                                    class="rounded-lg text-slate-700 focus:bg-slate-50 focus:text-slate-900"
                                                    @click="
                                                        openRemarksDialog(
                                                            dispatch,
                                                        )
                                                    "
                                                >
                                                    <FileText
                                                        class="mr-2 h-4 w-4"
                                                    />
                                                    View Remarks
                                                </DropdownMenuItem>

                                                <template
                                                    v-if="
                                                        dispatch.status ===
                                                            'arrived' &&
                                                        (canUpdateDispatch ||
                                                            canDepartDispatch)
                                                    "
                                                >
                                                    <DropdownMenuSeparator
                                                        class="bg-slate-100"
                                                    />
                                                    <DropdownMenuItem
                                                        v-if="canUpdateDispatch"
                                                        class="rounded-lg text-slate-700 focus:bg-amber-50 focus:text-amber-700"
                                                        @click="
                                                            openEditDialog(
                                                                dispatch,
                                                            )
                                                        "
                                                    >
                                                        <Pencil
                                                            class="mr-2 h-4 w-4"
                                                        />
                                                        Edit
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        v-if="canDepartDispatch"
                                                        class="rounded-lg text-amber-600 focus:bg-amber-50 focus:text-amber-700"
                                                        @click="
                                                            askDepart(dispatch)
                                                        "
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
                                                    <DropdownMenuSeparator
                                                        class="bg-slate-100"
                                                    />
                                                    <DropdownMenuItem
                                                        class="rounded-lg text-slate-700 focus:bg-slate-50 focus:text-slate-900"
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

                    <!-- Pagination -->
                    <div
                        v-if="dispatches.last_page > 1 || dispatches.total > 0"
                        class="border-t border-slate-100 px-5 py-3"
                    >
                        <InertiaPagination
                            :links="dispatches.links"
                            :meta="{
                                from: dispatches.from,
                                to: dispatches.to,
                                total: dispatches.total,
                            }"
                        />
                    </div>
                </Card>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════
             DIALOGS
        ══════════════════════════════════════════════ -->

        <!-- Change Request Status Modal -->
        <Dialog v-model:open="changeRequestStatusOpen">
            <DialogContent class="max-h-[80vh] max-w-2xl overflow-y-auto">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <FileText class="h-5 w-5 text-slate-500" />
                        Change Request Status
                    </DialogTitle>
                    <DialogDescription>
                        Track the status of your dispatch change requests.
                    </DialogDescription>
                </DialogHeader>

                <Separator class="bg-slate-100" />

                <div class="space-y-5">
                    <!-- Summary cards -->
                    <div class="grid grid-cols-3 gap-3">
                        <div
                            class="rounded-lg border border-amber-200 bg-amber-50 p-3"
                        >
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
                        <div
                            class="rounded-lg border border-emerald-200 bg-emerald-50 p-3"
                        >
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
                        <div
                            class="rounded-lg border border-rose-200 bg-rose-50 p-3"
                        >
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-medium text-rose-900">
                                    Rejected
                                </p>
                                <XCircle class="h-4 w-4 text-rose-600" />
                            </div>
                            <p class="mt-2 text-2xl font-bold text-rose-700">
                                {{ rejectedChangeRequests.length }}
                            </p>
                        </div>
                    </div>

                    <!-- List -->
                    <div class="max-h-[380px] space-y-2 overflow-y-auto pr-1">
                        <div
                            v-for="request in props.changeRequests"
                            :key="request.id"
                            class="cursor-pointer rounded-xl border p-4 transition-shadow hover:shadow-md"
                            :class="{
                                'border-amber-200 bg-amber-50':
                                    request.status === 'pending',
                                'border-emerald-200 bg-emerald-50':
                                    request.status === 'approved',
                                'border-rose-200 bg-rose-50':
                                    request.status === 'rejected',
                            }"
                            @click="openChangeRequestDetail(request)"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1 space-y-2">
                                    <div class="flex items-center gap-2">
                                        <p
                                            class="text-sm font-semibold text-slate-800"
                                        >
                                            {{ request.dispatch.plate_number }}
                                        </p>
                                        <span
                                            class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold capitalize"
                                            :class="{
                                                'border-amber-200 bg-amber-100 text-amber-700':
                                                    request.status ===
                                                    'pending',
                                                'border-emerald-200 bg-emerald-100 text-emerald-700':
                                                    request.status ===
                                                    'approved',
                                                'border-rose-200 bg-rose-100 text-rose-700':
                                                    request.status ===
                                                    'rejected',
                                            }"
                                        >
                                            {{ request.status }}
                                        </span>
                                        <span class="text-xs text-slate-400">
                                            {{
                                                new Date(
                                                    request.created_at || '',
                                                ).toLocaleDateString()
                                            }}
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3 text-sm">
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
                                            <p class="font-mono text-xs">
                                                {{
                                                    formatChangeValue(
                                                        request,
                                                        'old',
                                                    )
                                                }}
                                                →
                                                {{
                                                    formatChangeValue(
                                                        request,
                                                        'requested',
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </div>

                                    <p
                                        class="line-clamp-2 text-xs text-slate-500"
                                    >
                                        {{ request.reason }}
                                    </p>

                                    <div
                                        v-if="
                                            request.status === 'rejected' &&
                                            request.rejection_reason
                                        "
                                    >
                                        <Popover>
                                            <PopoverTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    class="h-auto p-0 text-rose-600 hover:bg-transparent hover:text-rose-700"
                                                >
                                                    <XCircle
                                                        class="mr-1 h-3.5 w-3.5"
                                                    />
                                                    <span
                                                        class="text-xs underline"
                                                        >View rejection
                                                        reason</span
                                                    >
                                                </Button>
                                            </PopoverTrigger>
                                            <PopoverContent
                                                class="w-72 p-4"
                                                side="top"
                                            >
                                                <p
                                                    class="text-sm font-semibold text-rose-900"
                                                >
                                                    Rejection Reason
                                                </p>
                                                <p
                                                    class="mt-1 text-sm text-rose-800"
                                                >
                                                    {{
                                                        request.rejection_reason
                                                    }}
                                                </p>
                                            </PopoverContent>
                                        </Popover>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="
                                !props.changeRequests ||
                                props.changeRequests.length === 0
                            "
                            class="py-10 text-center"
                        >
                            <FileText
                                class="mx-auto mb-3 h-10 w-10 text-slate-300"
                            />
                            <p class="text-sm font-medium text-slate-500">
                                No change requests yet
                            </p>
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>

        <!-- Create / Edit Dispatch Dialog -->
        <Dialog v-model:open="dialogOpen">
            <DialogContent class="sm:max-w-md">
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
                        <Select
                            :model-value="form.vehicle_id"
                            @update:model-value="
                                (value) => onVehicleChange(String(value ?? ''))
                            "
                        >
                            <SelectTrigger id="vehicle_id"
                                ><SelectValue placeholder="Select a vehicle"
                            /></SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="vehicle in vehicles"
                                    :key="vehicle.id"
                                    :value="String(vehicle.id)"
                                    :disabled="
                                        isVehicleDisabledForDispatchForm(
                                            vehicle.id,
                                        )
                                    "
                                >
                                    <span
                                        v-if="
                                            isVehicleDisabledForDispatchForm(
                                                vehicle.id,
                                            )
                                        "
                                    >
                                        {{ vehicle.label }} (Already arrived)
                                    </span>
                                    <span v-else>{{ vehicle.label }}</span>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.vehicle_id" />
                    </div>

                    <div class="space-y-2">
                        <Label for="driver_user_id">Driver</Label>
                        <Select v-model="form.driver_user_id">
                            <SelectTrigger id="driver_user_id"
                                ><SelectValue placeholder="Assign a driver"
                            /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="unassigned"
                                    >No driver assigned</SelectItem
                                >
                                <SelectItem
                                    v-for="driver in drivers"
                                    :key="driver.id"
                                    :value="String(driver.id)"
                                    :disabled="
                                        isDriverDisabledForDispatchForm(
                                            driver.id,
                                        )
                                    "
                                >
                                    <span
                                        v-if="
                                            isDriverDisabledForDispatchForm(
                                                driver.id,
                                            )
                                        "
                                    >
                                        {{ driver.label }} (Already assigned
                                        today)
                                    </span>
                                    <span v-else>{{ driver.label }}</span>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.driver_user_id" />
                    </div>

                    <div class="space-y-2">
                        <Label for="gate_id">Gate</Label>
                        <Select
                            :disabled="isGateAutoLocked"
                            :model-value="form.gate_id"
                            @update:model-value="onGateChange"
                        >
                            <SelectTrigger id="gate_id"
                                ><SelectValue placeholder="Select a gate"
                            /></SelectTrigger>
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
                        <p
                            v-if="isGateAutoLocked"
                            class="text-xs text-slate-500"
                        >
                            Gate is automatically selected from the vehicle
                            route.
                        </p>
                        <p
                            v-if="selectedVehicleRouteGateInactive"
                            class="text-xs text-rose-600"
                        >
                            {{ selectedVehicleRouteGateName }} is inactive.
                            Please contact the terminal manager to activate this
                            gate before dispatching this vehicle.
                        </p>
                        <InputError :message="form.errors.gate_id" />
                    </div>

                    <div class="space-y-2">
                        <Label for="bay_number">Bay Number</Label>
                        <Select
                            v-model="form.bay_number"
                            :disabled="!selectedGate"
                        >
                            <SelectTrigger id="bay_number"
                                ><SelectValue placeholder="Select a bay"
                            /></SelectTrigger>
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

                    <!-- Selection summary -->
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
                            <span class="text-slate-400">Gate</span>
                            {{ selectedGate.gate_name }} ·
                            {{ selectedGate.bays }} bays
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="remarks">
                            Remarks
                            <span
                                class="ml-1 text-xs font-normal text-slate-400"
                                >(optional)</span
                            >
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
                            @click="dialogOpen = false"
                            >Cancel</Button
                        >
                        <Button
                            type="submit"
                            :disabled="
                                form.processing ||
                                selectedVehicleRouteGateInactive
                            "
                            class="bg-slate-800 text-white hover:bg-slate-900"
                        >
                            <Send class="mr-2 h-4 w-4" />
                            {{
                                form.processing
                                    ? 'Saving…'
                                    : isEditing
                                      ? 'Save Changes'
                                      : 'Create Dispatch'
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Remarks View Dialog -->
        <Dialog v-model:open="remarksViewOpen">
            <DialogContent class="sm:max-w-sm">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <FileText class="h-4 w-4 text-slate-500" />
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
                    class="min-h-[80px] rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm leading-relaxed"
                    :class="
                        !viewingDispatch?.remarks
                            ? 'text-slate-400 italic'
                            : 'text-slate-700'
                    "
                >
                    {{ viewingDispatch?.remarks || 'No remarks recorded.' }}
                </div>

                <div
                    v-if="viewingDispatch"
                    class="grid grid-cols-2 gap-3 text-xs"
                >
                    <div class="space-y-0.5">
                        <p class="text-slate-400">Driver</p>
                        <p
                            class="font-semibold"
                            :class="
                                !viewingDispatch.driver
                                    ? 'text-slate-400 italic'
                                    : 'text-slate-800'
                            "
                        >
                            {{ viewingDispatch.driver?.name ?? 'Unassigned' }}
                        </p>
                    </div>
                    <div class="space-y-0.5">
                        <p class="text-slate-400">Dispatcher</p>
                        <p class="font-semibold text-slate-800">
                            {{ viewingDispatch.dispatcher?.name ?? '—' }}
                        </p>
                    </div>
                    <div class="space-y-0.5">
                        <p class="text-slate-400">Status</p>
                        <span
                            :class="[
                                'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium',
                                statusClass(viewingDispatch.status),
                            ]"
                        >
                            <span
                                :class="[
                                    'h-1.5 w-1.5 rounded-full',
                                    statusDot(viewingDispatch.status),
                                ]"
                            />
                            {{ statusLabel(viewingDispatch.status) }}
                        </span>
                    </div>
                    <div class="space-y-0.5">
                        <p class="text-slate-400">Arrived</p>
                        <p class="font-semibold text-slate-800">
                            {{ viewingDispatch.arrived_at_formatted ?? '—' }}
                        </p>
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        variant="outline"
                        class="w-full"
                        @click="remarksViewOpen = false"
                        >Close</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Confirm Departure Dialog -->
        <AlertDialog v-model:open="confirmDepartOpen">
            <AlertDialogContent>
                <form class="space-y-4" @submit.prevent="confirmDepart">
                    <AlertDialogHeader>
                        <AlertDialogTitle
                            >Mark dispatch as departed?</AlertDialogTitle
                        >
                        <AlertDialogDescription>
                            <span class="block"
                                >This will record the departure time as
                                <strong>now</strong>.</span
                            >
                            <span class="mt-1 block"
                                >Departed dispatches can no longer be
                                edited.</span
                            >
                        </AlertDialogDescription>
                    </AlertDialogHeader>

                    <div class="space-y-4">
                        <div
                            v-if="pendingDepartDispatch"
                            class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm"
                        >
                            <div class="font-semibold text-slate-800">
                                {{ pendingDepartDispatch.plate_number }}
                            </div>
                            <div class="text-xs text-slate-400">
                                {{
                                    pendingDepartDispatch.gate?.gate_name ?? '—'
                                }}
                                · Bay {{ pendingDepartDispatch.bay_number }}
                            </div>
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
                            :disabled="departForm.processing"
                            >Cancel</AlertDialogCancel
                        >
                        <Button
                            type="submit"
                            :disabled="departForm.processing"
                            class="bg-slate-800 text-white hover:bg-slate-900"
                        >
                            {{
                                departForm.processing
                                    ? 'Saving…'
                                    : 'Confirm Departure'
                            }}
                        </Button>
                    </AlertDialogFooter>
                </form>
            </AlertDialogContent>
        </AlertDialog>

        <!-- Request Change Modal -->
        <Dialog v-model:open="changeRequestOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Send class="h-4 w-4 text-slate-500" />
                        Request Change
                    </DialogTitle>
                    <DialogDescription v-if="changeRequestDispatch">
                        Submit a change request for dispatch
                        {{ changeRequestDispatch.plate_number }}.
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="submitChangeRequest">
                    <!-- Notice -->
                    <div
                        class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700"
                    >
                        <p class="font-medium">Notice</p>
                        <p class="mt-1 text-xs text-slate-500">
                            This dispatch has already departed. Changes require
                            approval from management before they are applied.
                        </p>
                    </div>

                    <div class="rounded-lg border bg-muted/30 p-3 text-sm">
                        <div class="font-medium">
                            {{ changeRequestDispatch?.plate_number }}
                        </div>
                        <div class="text-xs text-muted-foreground">
                            {{ changeRequestDispatch?.gate?.gate_name ?? '—' }}
                            · Bay
                            {{ changeRequestDispatch?.bay_number }}
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="change_field"
                            >What do you want to change?</Label
                        >
                        <Select v-model="changeRequestForm.requested_field">
                            <SelectTrigger id="change_field"
                                ><SelectValue
                                    placeholder="Select a field to change"
                            /></SelectTrigger>
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

                    <div
                        v-if="changeRequestForm.requested_field"
                        class="space-y-3"
                    >
                        <!-- Driver change -->
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
                                    <SelectTrigger id="change_driver"
                                        ><SelectValue
                                            placeholder="Select a driver"
                                    /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="driver in props.drivers"
                                            :key="driver.id"
                                            :value="String(driver.id)"
                                            :disabled="
                                                isDriverDisabledForChangeRequest(
                                                    driver.id,
                                                )
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
                                            <span
                                                v-else-if="
                                                    isDriverDisabledForChangeRequest(
                                                        driver.id,
                                                    )
                                                "
                                            >
                                                {{ driver.label }} (Already
                                                assigned today)
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
                            <div
                                v-if="driverValidationWarning"
                                class="space-y-3 rounded-lg border border-amber-200 bg-amber-50 p-3"
                            >
                                <p class="text-sm text-amber-900">
                                    {{ driverValidationWarning }}
                                </p>
                            </div>
                        </template>

                        <!-- Pax count -->
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
                                            String(
                                                (
                                                    $event.target as HTMLInputElement
                                                ).value,
                                            )
                                    "
                                />
                                <InputError
                                    :message="
                                        changeRequestForm.errors.requested_value
                                    "
                                />
                                <p class="mt-1 text-xs text-muted-foreground">
                                    Current:
                                    {{ changeRequestDispatch?.pax_count ?? 0 }}
                                    passengers
                                </p>
                            </div>
                        </template>

                        <!-- Vehicle change -->
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
                                    <SelectTrigger id="change_vehicle"
                                        ><SelectValue
                                            placeholder="Select a vehicle"
                                    /></SelectTrigger>
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

                        <!-- Gate & Bay -->
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
                                    <SelectTrigger id="change_gate"
                                        ><SelectValue
                                            placeholder="Select a gate"
                                    /></SelectTrigger>
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
                                    <span class="text-xs text-slate-400"
                                        >(optional)</span
                                    >
                                </Label>
                                <Select v-model="changeRequestBay">
                                    <SelectTrigger id="change_bay_optional"
                                        ><SelectValue
                                            placeholder="Select a bay number (optional)"
                                    /></SelectTrigger>
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
                                <p class="mt-1 text-xs text-slate-400">
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
                            placeholder="Explain why this change is needed…"
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
                            :disabled="changeRequestForm.processing"
                            @click="closeChangeRequestModal"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            class="bg-slate-800 text-white hover:bg-slate-900"
                            :disabled="
                                changeRequestForm.processing ||
                                driverValidationWarning !== null ||
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
                        Change Request
                        <span
                            class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold capitalize"
                            :class="{
                                'border-amber-200 bg-amber-100 text-amber-700':
                                    selectedChangeRequest.status === 'pending',
                                'border-emerald-200 bg-emerald-100 text-emerald-700':
                                    selectedChangeRequest.status === 'approved',
                                'border-rose-200 bg-rose-100 text-rose-700':
                                    selectedChangeRequest.status === 'rejected',
                            }"
                        >
                            {{ selectedChangeRequest.status }}
                        </span>
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

                <Separator class="bg-slate-100" />

                <div v-if="selectedChangeRequest" class="space-y-4">
                    <div class="space-y-3">
                        <div>
                            <p
                                class="text-xs font-semibold tracking-widest text-slate-400 uppercase"
                            >
                                Field
                            </p>
                            <p class="mt-1 text-sm font-medium text-slate-800">
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
                                    formatChangeValue(
                                        selectedChangeRequest,
                                        'old',
                                    )
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
                                    formatChangeValue(
                                        selectedChangeRequest,
                                        'requested',
                                    )
                                }}
                            </p>
                        </div>
                        <div>
                            <p
                                class="text-xs font-semibold tracking-widest text-slate-400 uppercase"
                            >
                                Reason
                            </p>
                            <p class="mt-1 text-sm text-slate-700">
                                {{ selectedChangeRequest.reason }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="
                            selectedChangeRequest.status === 'rejected' &&
                            selectedChangeRequest.rejection_reason
                        "
                        class="flex items-start gap-2 rounded-lg border border-rose-200 bg-rose-50 p-4"
                    >
                        <XCircle
                            class="mt-0.5 h-5 w-5 shrink-0 text-rose-600"
                        />
                        <div>
                            <p class="text-sm font-semibold text-rose-900">
                                Rejection Reason
                            </p>
                            <p class="mt-1 text-sm text-rose-800">
                                {{ selectedChangeRequest.rejection_reason }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="selectedChangeRequest.status === 'approved'"
                        class="flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 p-4"
                    >
                        <CheckCircle2
                            class="h-5 w-5 shrink-0 text-emerald-600"
                        />
                        <p class="text-sm font-medium text-emerald-900">
                            This request has been approved.
                        </p>
                    </div>

                    <div
                        v-if="selectedChangeRequest.status === 'pending'"
                        class="flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 p-4"
                    >
                        <Clock3 class="h-5 w-5 shrink-0 text-amber-600" />
                        <p class="text-sm font-medium text-amber-900">
                            Awaiting approval from administrator.
                        </p>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </ExternalLayout>
</template>
