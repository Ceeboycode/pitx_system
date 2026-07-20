<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

import InertiaPagination from '@/components/InertiaPagination.vue';
import InputError from '@/components/InputError.vue';
import SearchInput from '@/components/SearchInput.vue';
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';
import ExternalLayout from '@/layouts/ExternalLayout.vue';
import { can } from '@/lib/can';

import { store as storeChangeRequest } from '@/actions/App/Http/Controllers/DispatchChangeRequestController';
import DispatchController from '@/actions/App/Http/Controllers/DispatchController';

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
    RiAddLine,
    RiExternalLinkLine,
    RiBus2Line,
    RiCheckboxCircleLine,
    RiCloseLine,
    RiCloseCircleLine,
    RiFileTextLine,
    RiFilter2Line,
    RiFingerprintLine,
    RiGroupLine,
    RiLogoutBoxLine,
    RiMore2Line,
    RiEditLine,
    RiRoadMapLine,
    RiTimeLine,
    RiUserLine,
} from 'vue-remix-icons';

import {
    CalendarDate,
    DateFormatter,
    getLocalTimeZone,
    today,
} from '@internationalized/date';

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
const canRequestDispatchChange = can('external_dispatches.requestChange');


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
const filterOpen = ref(false);
const statusFilter = ref<string>(props.filters?.status ?? 'all');

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

const activeFilterCount = computed(
    () => Number(statusFilter.value !== 'all') + Number(!!selectedDate.value),
);

const hasActiveFilters = computed(() => activeFilterCount.value > 0);

function applyFilters() {
    filterOpen.value = false;
    router.get(
        DispatchController.index().url,
        {
            search: props.filters?.search || undefined,
            status:
                statusFilter.value === 'all' ? undefined : statusFilter.value,
            date: selectedDate.value
                ? `${selectedDate.value.year}-${String(selectedDate.value.month).padStart(2, '0')}-${String(selectedDate.value.day).padStart(2, '0')}`
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

function applyDateFilter(date: CalendarDate | undefined) {
    selectedDate.value = date;
    calendarOpen.value = false;
    applyFilters();
}

function clearDateFilter() {
    selectedDate.value = undefined;
    applyFilters();
}
function setToday() {
    selectedDate.value = today(localTz);
}
function setYesterday() {
    selectedDate.value = today(localTz).subtract({ days: 1 });
}

function clearFilters() {
    statusFilter.value = 'all';
    selectedDate.value = undefined;
    applyFilters();
}


const dialogOpen = ref(false);
const editingDispatchId = ref<number | null>(null);
const confirmDepartOpen = ref(false);
const pendingDepartId = ref<number | null>(null);
const pendingDepartDispatch = ref<DispatchItem | null>(null);
const remarksViewOpen = ref(false);
const viewingDispatch = ref<DispatchItem | null>(null);
const previewedDispatch = ref<DispatchItem | null>(null);

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

type ChangeRequestField =
    | 'driver_user_id'
    | 'pax_count'
    | 'vehicle_id'
    | 'bay_number';
const changeRequestFields: Array<{ value: ChangeRequestField; label: string }> =
    [
        { value: 'driver_user_id', label: 'Change Driver Assignment' },
        { value: 'pax_count', label: 'Update Passenger Count' },
        { value: 'vehicle_id', label: 'Change Vehicle' },
        { value: 'bay_number', label: 'Change Bay Number' },
    ];


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
const changeRequestGate = computed(() => {
    const gateId = changeRequestDispatch.value?.gate?.id;
    if (!gateId) return null;
    return props.gates.find((gate) => gate.id === gateId) ?? null;
});
const changeRequestBayOptions = computed(() => {
    if (changeRequestGate.value?.bay_options?.length) {
        return changeRequestGate.value.bay_options;
    }

    const fallbackBays =
        changeRequestGate.value?.bays && changeRequestGate.value.bays > 0
            ? changeRequestGate.value.bays
            : 10;

    return Array.from({ length: fallbackBays }, (_, index) => ({
        value: index + 1,
        label: `Bay ${index + 1}`,
    }));
});

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
    driverValidationWarning.value = null;
    changeRequestOpen.value = true;
}

function closeChangeRequestModal() {
    changeRequestOpen.value = false;
    changeRequestDispatch.value = null;
    changeRequestForm.reset();
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
    if (changeRequestForm.requested_field === 'bay_number') {
        const requestedBay = parseInt(changeRequestForm.requested_value, 10);
        const currentBay = Number(changeRequestDispatch.value.bay_number);

        if (!Number.isFinite(requestedBay)) {
            window.$toast?.error('Please select a new bay number');
            return;
        }

        if (requestedBay === currentBay) {
            window.$toast?.error(
                'New bay number must be different from current value',
            );
            return;
        }
    }

    changeRequestForm.post(
        storeChangeRequest(changeRequestDispatch.value.id).url,
        {
            preserveScroll: true,
            onSuccess: () => {
                closeChangeRequestModal();
                window.$toast?.success('Change request submitted successfully');
            },
        },
    );
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
        <div class="flex h-full min-h-0 w-full flex-1 flex-col gap-4 lg:flex-row lg:items-stretch">
        <Card class="min-h-0 min-w-0 flex-1 lg:h-full">
            <CardHeader class="flex flex-row gap-2">
                <div class="flex flex-col">
                    <CardTitle class="flex items-center gap-2">
                        <span class="font-semibold">Dispatches</span>
                    </CardTitle>
                    <CardDescription>
                        Arrival time is automatically recorded on dispatch
                        creation.
                    </CardDescription>
                </div>
                <div class="flex flex-1 items-center justify-end gap-2">
                    <Button
                        v-if="
                            props.changeRequests &&
                            props.changeRequests.length > 0
                        "
                        variant="header-actions"
                        size="icon-text"
                        class="relative rounded-full"
                        @click="changeRequestStatusOpen = true"
                    >
                        <RiFileTextLine class="h-3.5 w-3.5" />
                        <span class="hidden lg:flex">Change Requests</span>
                        <span
                            class="flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[10px] font-bold text-white"
                        >
                            {{ props.changeRequests.length }}
                        </span>
                    </Button>

                    <Button
                        v-if="canCreateDispatch"
                        variant="float-primary"
                        @click="openCreateDialog"
                    >
                        <RiAddLine class="h-4 w-4 shrink-0" />
                        <span>Add Dispatch</span>
                    </Button>
                </div>
            </CardHeader>

            <CardContent class="flex min-h-0 flex-1 flex-col space-y-4 py-2">
                <div class="flex flex-row gap-2 lg:items-center lg:justify-between">
                    <div class="w-full">
                        <SearchInput
                            :route="DispatchController.index().url"
                            :initial-value="props.filters?.search ?? ''"
                            placeholder="Search plate, remarks..."
                            :only="['dispatches', 'filters']"
                        />
                    </div>

                    <div class="flex w-fit flex-row gap-2 lg:items-center lg:justify-between">
                        <Popover v-model:open="filterOpen">
                            <PopoverTrigger as-child>
                                <Button
                                    variant="header-actions"
                                    size="icon-text"
                                    class="rounded-full"
                                    :class="
                                        activeFilterCount > 0
                                            ? 'bg-custom-secondary/20 transition-all duration-300 hover:bg-custom-secondary/80 hover:text-custom-bg-light'
                                            : ''
                                    "
                                >
                                    <RiFilter2Line class="h-3.5 w-3.5" />
                                    <span class="hidden lg:flex">
                                        {{
                                            activeFilterCount > 0
                                                ? (activeFilterCount === 1 ? '1 filter active' : `${activeFilterCount} filters active`)
                                                : 'Filter'
                                        }}
                                    </span>
                                </Button>
                            </PopoverTrigger>

                            <PopoverContent align="end">
                                <div class="grid gap-y-2">
                                    <div class="flex flex-col gap-y-1">
                                        <p class="text-sm text-custom-shadow/80">Status</p>
                                        <Select
                                            :model-value="statusFilter"
                                            @update:model-value="(value) => statusFilter = value != null ? String(value) : 'all'"
                                        >
                                            <SelectTrigger class="w-full">
                                                <SelectValue placeholder="All statuses" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all" class="cursor-pointer">All Statuses</SelectItem>
                                                <SelectItem value="arrived" class="cursor-pointer">Arrived</SelectItem>
                                                <SelectItem value="departed" class="cursor-pointer">Departed</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div class="flex flex-col gap-y-1">
                                        <p class="text-sm text-custom-shadow/80">Date</p>
                                        <div class="flex gap-1.5">
                                            <Button
                                                size="sm"
                                                variant="header-actions"
                                                class="h-7 flex-1 rounded-full text-xs"
                                                :class="selectedDateLabel === 'Today' ? 'bg-custom-secondary/20' : ''"
                                                @click="setToday"
                                            >
                                                Today
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="header-actions"
                                                class="h-7 flex-1 rounded-full text-xs"
                                                :class="selectedDateLabel === 'Yesterday' ? 'bg-custom-secondary/20' : ''"
                                                @click="setYesterday"
                                            >
                                                Yesterday
                                            </Button>
                                        </div>

                                        <Calendar
                                            :model-value="selectedDate"
                                            :max-value="today(localTz)"
                                            initial-focus
                                            @update:model-value="(d) => selectedDate = d as CalendarDate | undefined"
                                        />
                                    </div>

                                    <hr class="my-1 h-px border-0 bg-custom-bg-dark dark:bg-custom-bg-light">

                                    <div class="flex w-full flex-row items-center justify-between">
                                        <Button
                                            v-if="hasActiveFilters"
                                            size="sm"
                                            variant="destructive"
                                            @click="clearFilters"
                                        >
                                            Clear
                                        </Button>

                                        <div class="ml-auto flex items-center gap-2">
                                            <Button
                                                variant="ghost-outline"
                                                size="sm"
                                                @click="filterOpen = false"
                                            >
                                                Cancel
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="float-primary"
                                                @click="applyFilters"
                                            >
                                                Apply
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </PopoverContent>
                        </Popover>
                    </div>
                </div>

                
                <Card
                    :class="[
                        'flex min-h-0 max-h-fit flex-1 flex-col overflow-hidden border border-custom-bg-dark py-0 shadow-none dark:border-custom-bg-light dark:inset-shadow-none',
                        dispatches.data.length === 0 ? 'border-dashed' : 'border-solid',
                    ]"
                >
                    <div class="no-scrollbar min-h-0 flex-1 overflow-auto">
                        <div class="flex min-h-0 min-w-[1000px] w-full flex-1 flex-col overflow-hidden">
                            <div v-if="dispatches.data.length > 0" class="shrink-0 rounded-t-md bg-custom-bg dark:bg-custom-bg-light">
                                <div class="grid grid-cols-[1fr_1fr_.65fr_.55fr_1fr_1fr_1fr_4rem] gap-2 border-b border-custom-bg-dark dark:border-custom-bg-light">
                                    <div class="flex h-10 items-center justify-start pl-3 text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Vehicle</div>
                                    <div class="flex h-10 items-center justify-start text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Driver</div>
                                    <div class="flex h-10 items-center justify-start text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Gate / Bay</div>
                                    <div class="flex h-10 items-center justify-start text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Pax</div>
                                    <div class="flex h-10 items-center justify-start text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Arrived</div>
                                    <div class="flex h-10 items-center justify-start text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Departed</div>
                                    <div class="flex h-10 items-center justify-start text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Dispatcher</div>
                                    <div class="flex h-10 items-center justify-end pr-3 text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Actions</div>
                                </div>
                            </div>

                            <div class="no-scrollbar min-h-0 flex-1 overflow-y-auto">
                                
                                <div
                                    v-if="dispatches.data.length === 0"
                                    class="flex min-h-64 items-center justify-center p-6 text-center"
                                >
                                    <div>
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
                                    </div>
                                </div>

                                <div
                                    v-for="(dispatch, dispatchIndex) in dispatches.data"
                                    :key="dispatch.id"
                                    :class="[
                                        'group grid cursor-pointer grid-cols-[1fr_1fr_.65fr_.55fr_1fr_1fr_1fr_4rem] items-center gap-2 border-b border-custom-bg-dark bg-transparent text-custom-shadow/80 transition-colors hover:bg-custom-secondary/10 hover:text-custom-shadow dark:border-custom-bg-light',
                                        dispatch.status === 'departed' ? 'opacity-60' : '',
                                        dispatchIndex === dispatches.data.length - 1 ? 'rounded-b-md border-b-0' : '',
                                        previewedDispatch?.id === dispatch.id ? 'bg-custom-secondary/10 text-custom-shadow' : '',
                                    ]"
                                    @click="previewedDispatch = dispatch"
                                >
                                    
                                    <div class="flex min-w-0 justify-start py-1.5 pl-3">
                                        <div>
                                                <p
                                                    class="text-sm font-semibold"
                                                >
                                                    {{ dispatch.plate_number }}
                                                </p>
                                                <p
                                                    class="text-xs text-custom-shadow/60"
                                                >
                                                    {{
                                                        dispatch.vehicle
                                                            ?.vehicle_type ??
                                                        '—'
                                                    }}
                                                </p>
                                        </div>
                                    </div>

                                    
                                    <div class="flex min-w-0 justify-start py-1.5">
                                        <div class="flex items-center gap-1.5">
                                            <RiUserLine
                                                class="h-3.5 w-3.5 text-custom-shadow/60"
                                            />
                                            <span
                                                class="text-sm"
                                                :class="
                                                    !dispatch.driver
                                                        ? 'text-custom-shadow/60 italic'
                                                        : ''
                                                "
                                            >
                                                {{
                                                    dispatch.driver?.name ??
                                                    'Unassigned'
                                                }}
                                            </span>
                                        </div>
                                    </div>

                                    
                                    <div class="flex min-w-0 flex-col justify-center py-1.5">
                                        <p
                                            class="text-sm font-medium"
                                        >
                                            {{
                                                dispatch.gate?.gate_name ?? '—'
                                            }}
                                        </p>
                                        <p class="text-xs text-custom-shadow/60">
                                            Bay {{ dispatch.bay_number }}
                                        </p>
                                    </div>

                                    
                                    <div class="flex justify-start py-1.5">
                                        <div
                                            class="inline-flex items-center gap-1.5 rounded-full bg-custom-bg px-2.5 py-1 text-xs font-medium text-custom-shadow dark:bg-custom-bg-light"
                                        >
                                            <RiGroupLine class="h-3 w-3" />
                                            {{ dispatch.pax_count }}
                                        </div>
                                    </div>

                                    
                                    <div class="flex justify-start py-1.5 text-xs text-custom-shadow/70">
                                        <div
                                            v-if="dispatch.arrived_at_formatted"
                                        >
                                            {{ dispatch.arrived_at_formatted }}
                                        </div>
                                        <span v-else class="text-custom-shadow/60"
                                            >—</span
                                        >
                                    </div>

                                    
                                    <div class="flex justify-start py-1.5 text-xs text-custom-shadow/70">
                                        <div
                                            v-if="
                                                dispatch.departed_at_formatted
                                            "
                                        >
                                            {{ dispatch.departed_at_formatted }}
                                        </div>
                                        <span v-else class="text-custom-shadow/60"
                                            >—</span
                                        >
                                    </div>

                                    
                                    <div class="flex min-w-0 justify-start py-1.5">
                                        <div class="flex items-center gap-1.5">
                                            <RiFingerprintLine
                                                class="h-3.5 w-3.5 text-custom-shadow/60"
                                            />
                                            <span
                                                class="text-sm"
                                            >
                                                {{
                                                    dispatch.dispatcher?.name ||
                                                    '—'
                                                }}
                                            </span>
                                        </div>
                                    </div>

                                    
                                    <div class="flex justify-end py-1.5 pr-3 text-right" @click.stop>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="table-more"
                                                    size="icon-more"
                                                >
                                                    <RiMore2Line
                                                        class="h-4 w-4"
                                                    />
                                                </Button>
                                            </DropdownMenuTrigger>

                                            <DropdownMenuContent align="end" class="">
                                                <DropdownMenuItem
                                                    as-child
                                                    class="group"
                                                >
                                                    <Link
                                                        :href="
                                                            DispatchController.show(
                                                                dispatch.id,
                                                            ).url
                                                        "
                                                        class="flex items-center"
                                                    >
                                                        <RiExternalLinkLine
                                                            class="h-4 w-4 text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow"
                                                        />
                                                        View
                                                    </Link>
                                                </DropdownMenuItem>

                                                <template
                                                    v-if="
                                                        dispatch.status ===
                                                            'arrived' &&
                                                        (canUpdateDispatch ||
                                                            canDepartDispatch)
                                                    "
                                                >
                                                    <DropdownMenuItem
                                                        v-if="canUpdateDispatch"
                                                        class="group"
                                                        @click="
                                                            openEditDialog(
                                                                dispatch,
                                                            )
                                                        "
                                                    >
                                                        <RiEditLine
                                                            class="h-4 w-4 text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow"
                                                        />
                                                        Edit
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        v-if="canDepartDispatch"
                                                        class="group"
                                                        @click="
                                                            askDepart(dispatch)
                                                        "
                                                    >
                                                        <RiLogoutBoxLine
                                                            class="h-4 w-4 text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow"
                                                        />
                                                        Mark as Departed
                                                    </DropdownMenuItem>
                                                </template>

                                                <template
                                                    v-if="
                                                        dispatch.status ===
                                                            'departed' &&
                                                        canRequestDispatchChange
                                                    "
                                                >
                                                    <DropdownMenuSeparator
                                                        class=""
                                                    />
                                                    <DropdownMenuItem
                                                        class="group"
                                                        @click="
                                                            openChangeRequestModal(
                                                                dispatch,
                                                            )
                                                        "
                                                    >
                                                        <RiRoadMapLine
                                                            class="h-4 w-4 text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow"
                                                        />
                                                        Request Change
                                                    </DropdownMenuItem>
                                                </template>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </Card>

                
                <div
                    v-if="dispatches.last_page > 1 || dispatches.total > 0"
                    class="border-t border-custom-bg-dark px-5 py-3 dark:border-custom-bg-light"
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
            </CardContent>
        </Card>

            <Card class="hidden min-h-0 lg:flex lg:h-full lg:w-100">
                <CardHeader
                    v-if="previewedDispatch"
                    class="flex flex-row items-start justify-between gap-3"
                >
                    <div class="min-w-0">
                        <CardTitle class="truncate uppercase">
                            {{ previewedDispatch.plate_number }}
                        </CardTitle>
                        <CardDescription>Preview</CardDescription>
                    </div>
                    <Button
                        variant="header-actions"
                        size="icon"
                        class="h-8 w-8 shrink-0 rounded-full"
                        aria-label="Close dispatch preview"
                        @click="previewedDispatch = null"
                    >
                        <RiCloseLine class="h-4 w-4" />
                    </Button>
                </CardHeader>

                <CardContent
                    v-if="previewedDispatch"
                    class="no-scrollbar min-h-0 flex-1 space-y-2 overflow-y-auto py-2"
                >
                    <div class="flex aspect-4/3 items-center justify-center overflow-hidden rounded-md border border-dashed border-custom-bg-dark bg-custom-bg text-custom-shadow/70 dark:border-none dark:bg-custom-bg-dark">
                        <RiBus2Line class="h-16 w-16" />
                    </div>

                    <div class="space-y-2 pt-2">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm font-semibold text-custom-shadow">Status</span>
                            <Badge :class="['gap-1.5', statusClass(previewedDispatch.status)]">
                                <span :class="['h-1.5 w-1.5 rounded-full', statusDot(previewedDispatch.status)]" />
                                {{ statusLabel(previewedDispatch.status) }}
                            </Badge>
                        </div>

                        <div class="flex items-start justify-between gap-3">
                            <span class="text-sm font-semibold text-custom-shadow">Vehicle</span>
                            <div class="min-w-0 text-right text-sm">
                                <p>{{ previewedDispatch.vehicle?.make_model ?? previewedDispatch.vehicle?.vehicle_type ?? '—' }}</p>
                                <p v-if="previewedDispatch.vehicle?.body_number" class="text-xs text-custom-shadow/70">Body #{{ previewedDispatch.vehicle.body_number }}</p>
                            </div>
                        </div>

                        <div class="flex items-start justify-between gap-3">
                            <span class="text-sm font-semibold text-custom-shadow">Driver</span>
                            <span class="text-right text-sm">{{ previewedDispatch.driver?.name ?? 'Unassigned' }}</span>
                        </div>

                        <div class="flex items-start justify-between gap-3">
                            <span class="text-sm font-semibold text-custom-shadow">Gate / Bay</span>
                            <span class="text-right text-sm">{{ previewedDispatch.gate?.gate_name ?? '—' }} / Bay {{ previewedDispatch.bay_number }}</span>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm font-semibold text-custom-shadow">Passengers</span>
                            <span class="text-sm text-custom-shadow">{{ previewedDispatch.pax_count }}</span>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-semibold text-custom-shadow">Dispatch Timeline</p>
                            </div>
                            <div class="space-y-2">
                                <div class="flex items-center justify-between gap-3 rounded-md bg-custom-bg px-3 py-2 dark:bg-custom-bg-dark">
                                    <span class="text-sm font-medium">Arrived</span>
                                    <span class="shrink-0 text-xs text-custom-shadow/70">{{ previewedDispatch.arrived_at_formatted ?? '—' }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-3 rounded-md bg-custom-bg px-3 py-2 dark:bg-custom-bg-dark">
                                    <span class="text-sm font-medium">Departed</span>
                                    <span class="shrink-0 text-xs text-custom-shadow/70">{{ previewedDispatch.departed_at_formatted ?? '—' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-start justify-between gap-3">
                            <span class="text-sm font-semibold text-custom-shadow">Dispatcher</span>
                            <span class="text-right text-sm">{{ previewedDispatch.dispatcher?.name ?? '—' }}</span>
                        </div>

                        <div v-if="previewedDispatch.remarks" class="space-y-1">
                            <p class="text-sm font-semibold text-custom-shadow">Remarks</p>
                            <p class="rounded-md bg-custom-bg px-3 py-2 text-sm text-custom-shadow/70 dark:bg-custom-bg-dark">{{ previewedDispatch.remarks }}</p>
                        </div>
                    </div>

                    <hr class="my-4 h-px border-0 bg-custom-bg-dark dark:bg-custom-bg-light">

                    <div class="flex items-center justify-between gap-2">
                        <Button
                            v-if="canUpdateDispatch && previewedDispatch.status === 'arrived'"
                            variant="ghost-outline"
                            size="icon-text"
                            @click="openEditDialog(previewedDispatch)"
                        >
                            <RiEditLine class="h-4 w-4" />
                            Edit
                        </Button>
                        <span v-else />
                        <Button as-child variant="float-primary" size="icon">
                            <Link :href="DispatchController.show(previewedDispatch.id).url">
                                <RiExternalLinkLine class="h-4 w-4" />
                            </Link>
                        </Button>
                    </div>
                </CardContent>

                <CardContent v-else class="flex min-h-0 flex-1 items-center justify-center">
                    <div class="max-w-60 space-y-1 text-center">
                        <p class="text-base font-semibold text-custom-shadow">No dispatch selected</p>
                        <p class="text-sm text-custom-shadow/80">Click on a dispatch to preview.</p>
                    </div>
                </CardContent>
            </Card>
        </div>

        
        <Dialog v-model:open="changeRequestStatusOpen">
            <DialogContent class="max-h-[80vh] max-w-2xl overflow-y-auto">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <RiFileTextLine class="h-5 w-5 text-slate-500" />
                        Change Request Status
                    </DialogTitle>
                    <DialogDescription>
                        Track the status of your dispatch change requests.
                    </DialogDescription>
                </DialogHeader>

                <Separator class="bg-slate-100" />

                <div class="space-y-5">
                    
                    <div class="grid grid-cols-3 gap-3">
                        <div
                            class="rounded-lg border border-amber-200 bg-amber-50 p-3"
                        >
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-medium text-amber-900">
                                    Pending
                                </p>
                                <RiTimeLine class="h-4 w-4 text-amber-600" />
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
                                <RiCheckboxCircleLine
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
                                <RiCloseCircleLine class="h-4 w-4 text-rose-600" />
                            </div>
                            <p class="mt-2 text-2xl font-bold text-rose-700">
                                {{ rejectedChangeRequests.length }}
                            </p>
                        </div>
                    </div>

                    
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
                                                    <RiCloseCircleLine
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
                            <RiFileTextLine
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

        <!-- TODO: make the other dialog elements within the file follow the layout and design of this one -->
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

                <form class="flex flex-col px-6" @submit.prevent="submit">
                    <div class="space-y-1 pb-2">
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

                    <div class="space-y-1 pb-2">
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

                    <div class="space-y-1 pb-2">
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

                    <div class="space-y-1 pb-2">
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

                    <div class="space-y-1 pb-2">
                        <Label for="remarks">
                            Remarks
                            <span
                                class="text-xs text-custom-shadow/80"
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

                    <Separator class="mb-4"/>

                    <DialogFooter class="gap-2 sm:justify-end">
                        <Button
                            type="button"
                            variant="ghost-outline"
                            @click="dialogOpen = false"
                            >Cancel</Button
                        >
                        <Button
                            type="submit"
                            variant="float-primary"
                            :disabled="
                                form.processing ||
                                selectedVehicleRouteGateInactive
                            "
                        >
                            <RiRoadMapLine class="h-4 w-4" />
                            {{
                                form.processing
                                    ? 'Saving...'
                                    : isEditing
                                      ? 'Save Changes'
                                      : 'Create Dispatch'
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
        
        <Dialog v-model:open="confirmDepartOpen">
            <DialogContent class="rounded-lg p-4 sm:max-w-md">
                <form class="space-y-4" @submit.prevent="confirmDepart">
                    <DialogHeader>
                        <DialogTitle
                            >Mark dispatch as departed?</DialogTitle
                        >
                        <DialogDescription>
                            <span class="block"
                                >This will record the departure time as
                                <strong>now</strong>.</span
                            >
                            <span class="mt-1 block"
                                >Departed dispatches can no longer be
                                edited.</span
                            >
                        </DialogDescription>
                    </DialogHeader>

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

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="ghost-outline"
                            :disabled="departForm.processing"
                            @click="confirmDepartOpen = false"
                            >Cancel</Button
                        >
                        <Button type="submit" variant="float-primary" :disabled="departForm.processing">
                            {{
                                departForm.processing
                                    ? 'Saving...'
                                    : 'Confirm Departure'
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        
        <Dialog v-model:open="changeRequestOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <RiRoadMapLine class="h-4 w-4 text-slate-500" />
                        Request Change
                    </DialogTitle>
                    <DialogDescription v-if="changeRequestDispatch">
                        Submit a change request for dispatch
                        {{ changeRequestDispatch.plate_number }}.
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="submitChangeRequest">
                    
                    <div
                        class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700"
                    >
                        <p class="font-medium">Notice</p>
                        <p class="mt-1 text-xs text-slate-500">
                            This dispatch has already departed. Changes require
                            approval from management before they are applied.
                        </p>
                    </div>

                    <div
                        class="rounded-lg border border-slate-200 bg-slate-50/70 p-3 text-sm"
                    >
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
                                                Boolean(
                                                    changeRequestDispatch &&
                                                    changeRequestDispatch
                                                        .vehicle?.id ===
                                                        vehicle.id,
                                                )
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

                        
                        <template
                            v-else-if="
                                changeRequestForm.requested_field ===
                                'bay_number'
                            "
                        >
                            <div>
                                <Label>Current Gate (Locked)</Label>
                                <div
                                    class="mt-1 rounded-md border border-slate-200 bg-slate-100 px-3 py-2 text-sm text-slate-600"
                                >
                                    {{
                                        changeRequestDispatch?.gate
                                            ?.gate_name ?? '—'
                                    }}
                                    <span class="text-slate-400">
                                        ·
                                        {{ changeRequestGate?.bays ?? 0 }} bays
                                    </span>
                                </div>
                            </div>
                            <div>
                                <Label for="change_bay">Select New Bay</Label>
                                <Select
                                    v-model="changeRequestForm.requested_value"
                                >
                                    <SelectTrigger id="change_bay"
                                        ><SelectValue
                                            placeholder="Select a bay number"
                                    /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="bay in changeRequestBayOptions"
                                            :key="bay.value"
                                            :value="String(bay.value)"
                                            :disabled="
                                                Boolean(
                                                    changeRequestDispatch &&
                                                    changeRequestDispatch.bay_number ===
                                                        bay.value,
                                                )
                                            "
                                        >
                                            <span
                                                v-if="
                                                    changeRequestDispatch &&
                                                    changeRequestDispatch.bay_number ===
                                                        bay.value
                                                "
                                            >
                                                ✓ {{ bay.label }} (Currently
                                                Assigned)
                                            </span>
                                            <span v-else>{{ bay.label }}</span>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError
                                    :message="
                                        changeRequestForm.errors.requested_value
                                    "
                                />
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
                            :disabled="changeRequestForm.processing"
                            @click="closeChangeRequestModal"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            :disabled="
                                changeRequestForm.processing ||
                                driverValidationWarning !== null ||
                                (changeRequestForm.requested_field ===
                                    'pax_count' &&
                                    parseInt(
                                        changeRequestForm.requested_value,
                                    ) === changeRequestDispatch?.pax_count) ||
                                (changeRequestForm.requested_field ===
                                    'bay_number' &&
                                    (!changeRequestForm.requested_value ||
                                        parseInt(
                                            changeRequestForm.requested_value,
                                        ) ===
                                            changeRequestDispatch?.bay_number))
                            "
                        >
                            {{
                                changeRequestForm.processing
                                    ? 'Submitting...'
                                    : 'Submit Request'
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        
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
                        <RiCloseCircleLine
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
                        <RiCheckboxCircleLine
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
                        <RiTimeLine class="h-5 w-5 shrink-0 text-amber-600" />
                        <p class="text-sm font-medium text-amber-900">
                            Awaiting approval from administrator.
                        </p>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </ExternalLayout>
</template>
