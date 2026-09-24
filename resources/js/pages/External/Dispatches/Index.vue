<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

import InertiaPagination from '@/components/InertiaPagination.vue';
import { InputMessage } from '@/components/ui/_input-message';
import SearchInput from '@/components/SearchInput.vue';
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';
import ExternalLayout from '@/layouts/ExternalLayout.vue';
import { can } from '@/lib/can';

import { store as storeChangeRequest } from '@/actions/App/Http/Controllers/DispatchChangeRequestController';
import DispatchController from '@/actions/App/Http/Controllers/DispatchController';

import {
    DispatchFormDialog,
    DispatchPreviewCard,
    type DispatchDriverOption,
    type DispatchGateOption,
    type DispatchItem,
    type DispatchVehicleOption,
} from '@/components/external/dispatch';
import { MainPanel, PanelLayout, SidePanel } from '@/components/ui/_panels';
import {
    Table,
    TableCard,
    TableColumn,
    TableContent,
    TableData,
    TableHeader,
    TableMoreButton,
    TableRow,
} from '@/components/ui/_table';
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
import { Textarea } from '@/components/ui/textarea';

import {
    RiAddLine,
    RiExternalLinkLine,
    RiCheckLine,
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

type Vehicle = DispatchVehicleOption;
type Driver = DispatchDriverOption;
type Gate = DispatchGateOption;

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
const editingDispatch = ref<DispatchItem | null>(null);
const openMenus = ref<Record<number, boolean>>({});
const confirmDepartOpen = ref(false);
const pendingDepartId = ref<number | null>(null);
const pendingDepartDispatch = ref<DispatchItem | null>(null);
const remarksViewOpen = ref(false);
const viewingDispatch = ref<DispatchItem | null>(null);
const previewedDispatch = ref<DispatchItem | null>(null);

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


const assignedDriverIdsToday = computed(
    () => new Set(props.assigned_driver_ids_today ?? []),
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

function isDriverDisabledForChangeRequest(driverId: number): boolean {
    if (changeRequestDispatch.value?.driver?.id === driverId) {
        return true;
    }

    return assignedDriverIdsToday.value.has(driverId);
}


function resetDepartForm() {
    departForm.reset();
    departForm.clearErrors();
}

function openCreateDialog() {
    if (!canCreateDispatch) return;

    editingDispatch.value = null;
    dialogOpen.value = true;
}

function openEditDialog(dispatch: DispatchItem) {
    if (!canUpdateDispatch || dispatch.status === 'departed') return;

    editingDispatch.value = dispatch;
    dialogOpen.value = true;
}

function openPreview(dispatch: DispatchItem) {
    previewedDispatch.value = dispatch;
}

function selectAdjacentDispatch(direction: 1 | -1) {
    if (!previewedDispatch.value) return;

    const list = props.dispatches.data;
    const currentIndex = list.findIndex((d) => d.id === previewedDispatch.value?.id);
    const nextIndex = currentIndex + direction;
    if (currentIndex === -1 || nextIndex < 0 || nextIndex >= list.length) return;

    openPreview(list[nextIndex]);
}

function handleRowNavigationKeydown(event: KeyboardEvent) {
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        selectAdjacentDispatch(1);
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        selectAdjacentDispatch(-1);
    }
}

onMounted(() => window.addEventListener('keydown', handleRowNavigationKeydown));
onUnmounted(() => window.removeEventListener('keydown', handleRowNavigationKeydown));

function openRemarksDialog(dispatch: DispatchItem) {
    viewingDispatch.value = dispatch;
    remarksViewOpen.value = true;
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
        <PanelLayout>
            <MainPanel>
                <Card class="min-h-0 min-w-0 flex-1 lg:h-full">
                    <CardHeader class="flex flex-row gap-2">
                        <div class="flex flex-col">
                            <CardTitle class="flex items-center gap-2">
                                <span class="font-semibold">Dispatches</span>
                            </CardTitle>
                            <CardDescription>List of all dispatches of your company.</CardDescription>
                        </div>
                        <div class="flex flex-1 justify-end gap-2">
                            <div class="items-center gap-2 sm:justify-end lg:flex">
                                <Button
                                    v-if="canCreateDispatch"
                                    variant="float-primary"
                                    class="hidden lg:flex"
                                    @click="openCreateDialog"
                                >
                                    <RiAddLine class="h-4 w-4 shrink-0" />
                                    <span>Add Dispatch</span>
                                </Button>
                                <DropdownMenu v-if="canCreateDispatch || props.changeRequests?.length">
                                    <DropdownMenuTrigger as-child class="m-0">
                                        <div class="inline-flex">
                                            <Button variant="header-actions" class="text-custom-shadow" size="icon">
                                                <RiMore2Line class="h-4 w-4 shrink-0" />
                                            </Button>
                                        </div>
                                    </DropdownMenuTrigger>

                                    <DropdownMenuContent align="end" class="w-fit">
                                        <DropdownMenuItem v-if="canCreateDispatch" as-child class="cursor-pointer lg:hidden">
                                            <button type="button" class="flex items-center" @click="openCreateDialog">
                                                <RiAddLine class="h-4 w-4 hover:text-custom-bg-light" />
                                                Add Dispatch
                                            </button>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            v-if="props.changeRequests?.length"
                                            class="group cursor-pointer"
                                            @click="changeRequestStatusOpen = true"
                                        >
                                            <RiFileTextLine class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                                            Change Requests
                                            <span class="ml-auto flex h-4 min-w-4 items-center justify-center rounded-full bg-custom-primary px-1 text-[10px] text-custom-bg-light dark:text-custom-shadow">
                                                {{ props.changeRequests.length }}
                                            </span>
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent class="flex min-h-0 flex-1 flex-col space-y-4 pt-2">
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
                                                    ? 'bg-custom-secondary/20 transition-all duration-200 hover:bg-custom-secondary/80 hover:text-custom-bg-light'
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

                        <TableCard :table-data-length="dispatches.data.length">
                            <Table v-if="dispatches.data.length > 0">
                                <TableHeader>
                                    <TableColumn>Vehicle</TableColumn>
                                    <TableColumn>Driver</TableColumn>
                                    <TableColumn>Gate / Bay</TableColumn>
                                    <TableColumn>Pax</TableColumn>
                                    <TableColumn>Arrived</TableColumn>
                                    <TableColumn>Departed</TableColumn>
                                    <TableColumn>Dispatcher</TableColumn>
                                </TableHeader>

                                <TableContent>
                                    <TableRow
                                        v-for="(dispatch, rowIndex) in dispatches.data"
                                        :key="dispatch.id"
                                        :class="[
                                            rowIndex === dispatches.data.length - 1 ? 'rounded-b-md border-b-0' : '',
                                            previewedDispatch?.id === dispatch.id ? 'bg-custom-secondary/10' : '',
                                        ]"
                                        :status="dispatch.status === 'departed' ? 'inactive' : 'default'"
                                        @click.left="openPreview(dispatch)"
                                        @dblclick="router.visit(DispatchController.show(dispatch.id).url)"
                                    >
                                        <TableData class="pl-3">
                                            <div class="flex min-w-0 flex-col">
                                                <span class="font-semibold">{{ dispatch.plate_number }}</span>
                                                <span class="text-xs text-custom-shadow/60">{{ dispatch.vehicle?.vehicle_type ?? '—' }}</span>
                                            </div>
                                        </TableData>

                                        <TableData>
                                            <div class="flex items-center gap-1.5">
                                                <RiUserLine class="h-3.5 w-3.5 shrink-0 text-custom-shadow/60" />
                                                <span :class="!dispatch.driver ? 'italic text-custom-shadow/60' : ''">
                                                    {{ dispatch.driver?.name ?? 'Unassigned' }}
                                                </span>
                                            </div>
                                        </TableData>

                                        <TableData>
                                            <div class="flex min-w-0 flex-col">
                                                <span class="font-semibold">{{ dispatch.gate?.gate_name ?? '—' }}</span>
                                                <span class="text-xs text-custom-shadow/60">Bay {{ dispatch.bay_number }}</span>
                                            </div>
                                        </TableData>

                                        <TableData>
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-custom-bg px-2.5 py-1 text-xs font-semibold text-custom-shadow dark:bg-custom-bg-light">
                                                <RiGroupLine class="h-3 w-3" />
                                                {{ dispatch.pax_count }}
                                            </span>
                                        </TableData>

                                        <TableData class="text-xs">
                                            {{ dispatch.arrived_at_formatted ?? '—' }}
                                        </TableData>

                                        <TableData class="text-xs">
                                            {{ dispatch.departed_at_formatted ?? '—' }}
                                        </TableData>

                                        <TableData>
                                            <div class="flex items-center gap-1.5">
                                                <RiFingerprintLine class="h-3.5 w-3.5 shrink-0 text-custom-shadow/60" />
                                                <span>{{ dispatch.dispatcher?.name || '—' }}</span>
                                            </div>
                                        </TableData>

                                        <TableMoreButton
                                            :open="openMenus[dispatch.id] ?? false"
                                            @update:open="(value) => (openMenus[dispatch.id] = value)"
                                        >
                                            <DropdownMenuLabel>{{ dispatch.plate_number }}</DropdownMenuLabel>

                                            <DropdownMenuItem as-child class="group">
                                                <Link :href="DispatchController.show(dispatch.id).url" class="flex items-center">
                                                    <RiExternalLinkLine class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                                                    View
                                                </Link>
                                            </DropdownMenuItem>

                                            <template v-if="dispatch.status === 'arrived'">
                                                <DropdownMenuItem v-if="canUpdateDispatch" class="group" @click="openEditDialog(dispatch)">
                                                    <RiEditLine class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                                                    Edit
                                                </DropdownMenuItem>
                                                <DropdownMenuItem v-if="canDepartDispatch" class="group" @click="askDepart(dispatch)">
                                                    <RiLogoutBoxLine class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                                                    Mark as Departed
                                                </DropdownMenuItem>
                                            </template>

                                            <DropdownMenuItem
                                                v-if="dispatch.status === 'departed' && canRequestDispatchChange"
                                                class="group"
                                                @click="openChangeRequestModal(dispatch)"
                                            >
                                                <RiRoadMapLine class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                                                Update Dispatch
                                            </DropdownMenuItem>
                                        </TableMoreButton>
                                    </TableRow>
                                </TableContent>
                            </Table>

                            <div v-else class="flex min-h-0 flex-1 items-center justify-center p-6 text-center">
                                <div class="flex w-full max-w-md flex-col items-center justify-center gap-2">
                                    <img :src="emptyRafikiUrl" alt="" class="w-1/3 object-contain opacity-90" aria-hidden="true" />
                                    <div class="space-y-1">
                                        <p class="text-base font-semibold text-custom-shadow">
                                            {{ selectedDate ? `No dispatches on ${selectedDateLabel}` : 'No dispatches found' }}
                                        </p>
                                        <p class="text-sm text-custom-shadow/80">
                                            {{ hasActiveFilters ? 'Try adjusting or clearing your filters.' : 'Try adjusting your search or add a new dispatch.' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </TableCard>

                        <InertiaPagination
                            :links="dispatches.links"
                            :meta="{ from: dispatches.from, to: dispatches.to, total: dispatches.total }"
                        />
                    </CardContent>
                </Card>
            </MainPanel>

            <SidePanel v-if="previewedDispatch" class="hidden lg:flex">
                <DispatchPreviewCard
                    :dispatch="previewedDispatch"
                    :status-class="statusClass(previewedDispatch.status)"
                    :status-dot="statusDot(previewedDispatch.status)"
                    :status-label="statusLabel(previewedDispatch.status)"
                    @close="previewedDispatch = null"
                />
            </SidePanel>
        </PanelLayout>

        <DispatchFormDialog
            v-model:open="dialogOpen"
            :dispatch="editingDispatch"
            :vehicles="vehicles"
            :drivers="drivers"
            :gates="gates"
            :assigned-driver-ids-today="assigned_driver_ids_today ?? []"
            :assigned-vehicle-ids-active="assigned_vehicle_ids_active ?? []"
        />

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
                                <p class="text-xs font-semibold text-amber-900">
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
                                <p class="text-xs font-semibold text-emerald-900">
                                    Approved
                                </p>
                                <RiCheckLine
                                    class="h-4 w-4 shrink-0 text-emerald-600"
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
                                <p class="text-xs font-semibold text-rose-900">
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
                                                class="text-xs font-semibold text-muted-foreground"
                                            >
                                                Field
                                            </p>
                                            <p class="font-semibold">
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
                                                class="text-xs font-semibold text-muted-foreground"
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
                            <p class="text-sm font-semibold text-slate-500">
                                No change requests yet
                            </p>
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="confirmDepartOpen">
            <DialogContent class="rounded-md sm:max-w-md">
                <DialogHeader>
                    <DialogTitle
                        >Mark dispatch as departed?</DialogTitle
                    >
                    <DialogDescription>
                        <span class="block">
                            This will record the departure time of 
                            <span class="text-custom-accent-3 font-semibold">
                                {{ pendingDepartDispatch.plate_number }}
                            </span>
                             as now. Departed dispatches can no longer be edited.
                        </span>
                    </DialogDescription>
                </DialogHeader>
                
                <form class="space-y-4 px-6" @submit.prevent="confirmDepart">
                    <div class="space-y-4">
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
                            <InputMessage
                                variant="destructive"
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
                        <!-- <RiRoadMapLine class="h-4 w-4 text-slate-500" /> -->
                        Update Dispatch Request
                    </DialogTitle>
                    <DialogDescription v-if="changeRequestDispatch">
                        This dispatch has already departed. Changes require
                            approval from management before they are applied.
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4 px-6" @submit.prevent="submitChangeRequest">
                    <div class="space-y-2">
                        <Label for="change_field"
                            >What do you want to change?</Label
                        >
                        <Select v-model="changeRequestForm.requested_field">
                            <SelectTrigger id="change_field" clss="w-full"
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
                        <InputMessage
                            variant="destructive"
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
                            <div class="space-y-2">
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
                                <InputMessage
                                    variant="destructive"
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
                            <div class="space-y-2">
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
                                <InputMessage
                                    variant="destructive"
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
                            <div class="space-y-2">
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
                                <InputMessage
                                    variant="destructive"
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
                            <div class="space-y-2">
                                <Label for="change_bay">Select New Bay</Label>
                                <Select
                                    v-model="changeRequestForm.requested_value"
                                >
                                    <SelectTrigger id="change_bay"
                                        ><SelectValue
                                            :placeholder="`Bay ${changeRequestDispatch?.bay_number ?? '—'}`"
                                        />
                                        </SelectTrigger>
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
                                <InputMessage
                                    variant="destructive"
                                    :message="
                                        changeRequestForm.errors.requested_value
                                    "
                                />
                            </div>
                        </template>
                    </div>

                    <div class="space-y-2">
                        <Label for="change_reason">Reason for Change</Label>
                        <Textarea
                            id="change_reason"
                            v-model="changeRequestForm.reason"
                            placeholder="Explain why this change is needed..."
                            rows="3"
                        />
                        <InputMessage
                            variant="destructive"
                            :message="changeRequestForm.errors.reason"
                        />
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="float"
                            :disabled="changeRequestForm.processing"
                            @click="closeChangeRequestModal"
                            size="text"
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
                            variant="float-primary"
                            size="text"
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
                    <DialogTitle>
                        Update Dispatch Request
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
                            <p class="mt-1 text-sm font-semibold text-slate-800">
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
                        <RiCheckLine
                            class="h-5 w-5 shrink-0 text-emerald-600"
                        />
                        <p class="text-sm font-semibold text-emerald-900">
                            This request has been approved.
                        </p>
                    </div>

                    <div
                        v-if="selectedChangeRequest.status === 'pending'"
                        class="flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 p-4"
                    >
                        <RiTimeLine class="h-5 w-5 shrink-0 text-amber-600" />
                        <p class="text-sm font-semibold text-amber-900">
                            Awaiting approval from administrator.
                        </p>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </ExternalLayout>
</template>
