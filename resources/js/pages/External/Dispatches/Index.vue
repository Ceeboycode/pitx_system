<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

import InertiaPagination from '@/components/InertiaPagination.vue'
import InputError from '@/components/InputError.vue'
import SearchInput from '@/components/SearchInput.vue'
import ExternalLayout from '@/layouts/ExternalLayout.vue'

import DispatchController from '@/actions/App/Http/Controllers/DispatchController'

import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import { Button } from '@/components/ui/button'
import { Calendar } from '@/components/ui/calendar'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { Separator } from '@/components/ui/separator'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip'

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
} from 'lucide-vue-next'

import {
    CalendarDate,
    DateFormatter,
    getLocalTimeZone,
    today,
} from '@internationalized/date'

/* ======================================================
   Types
====================================================== */
type Company = {
    id: number
    company_name: string
    company_code?: string | null
}

type Vehicle = {
    id: number
    plate_number: string
    body_number?: string | null
    vehicle_type?: string | null
    make_model?: string | null
    status?: string | null
    label: string
}

type Driver = {
    id: number
    name: string
    username?: string | null
    email?: string | null
    label: string
}

type Gate = {
    id: number
    gate_name: string
    bays: number
    status?: string | null
    label: string
    bay_options: Array<{ value: number; label: string }>
}

type DispatchItem = {
    id: number
    plate_number: string
    pax_count: number
    bay_number: string | number
    remarks?: string | null
    status: string
    arrived_at_formatted?: string | null
    departed_at_formatted?: string | null
    dispatched_at_formatted?: string | null
    vehicle?: {
        id: number
        plate_number: string
        body_number?: string | null
        vehicle_type?: string | null
        make_model?: string | null
    } | null
    dispatcher?: { id: number; name: string; username?: string | null } | null
    driver?: { id: number; name: string; username?: string | null } | null
    gate?: { id: number; gate_name: string } | null
}

type Paginated<T> = {
    data: T[]
    links: Array<{ url: string | null; label: string; active: boolean }>
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
}

/* ======================================================
   Props
====================================================== */
const props = defineProps<{
    company: Company
    vehicles: Vehicle[]
    drivers: Driver[]
    gates: Gate[]
    dispatches: Paginated<DispatchItem>
    filters?: {
        search?: string | null
        status?: string | null
        date?: string | null
    }
}>()

/* ======================================================
   Date filter
====================================================== */
const df = new DateFormatter('en-US', { dateStyle: 'medium' })
const localTz = getLocalTimeZone()

function parseDateFilter(dateStr?: string | null): CalendarDate | undefined {
    if (!dateStr) return undefined
    const [y, m, d] = dateStr.split('-').map(Number)
    if (!y || !m || !d) return undefined
    return new CalendarDate(y, m, d)
}

const selectedDate = ref<CalendarDate | undefined>(
    parseDateFilter(props.filters?.date),
)
const calendarOpen = ref(false)

const selectedDateLabel = computed(() => {
    if (!selectedDate.value) return null
    const t = today(localTz)
    if (selectedDate.value.compare(t) === 0) return 'Today'
    const yesterday = t.subtract({ days: 1 })
    if (selectedDate.value.compare(yesterday) === 0) return 'Yesterday'
    return df.format(selectedDate.value.toDate(localTz))
})

function applyDateFilter(date: CalendarDate | undefined) {
    selectedDate.value = date
    calendarOpen.value = false

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
    )
}

function clearDateFilter() {
    applyDateFilter(undefined)
}

function setToday() {
    applyDateFilter(today(localTz))
}

function setYesterday() {
    applyDateFilter(today(localTz).subtract({ days: 1 }))
}

/* ======================================================
   Dialog state
====================================================== */
const dialogOpen = ref(false)
const editingDispatchId = ref<number | null>(null)

const confirmDepartOpen = ref(false)
const pendingDepartId = ref<number | null>(null)
const pendingDepartDispatch = ref<DispatchItem | null>(null)

const remarksViewOpen = ref(false)
const viewingDispatch = ref<DispatchItem | null>(null)

/* ======================================================
   Forms
====================================================== */
const form = useForm({
    vehicle_id: '',
    driver_user_id: 'unassigned',
    gate_id: '',
    bay_number: '',
    remarks: '',
})

const departForm = useForm({
    pax_count: '',
})

/* ======================================================
   Computed selections
====================================================== */
const selectedVehicle = computed(() =>
    props.vehicles.find((v) => String(v.id) === String(form.vehicle_id)) ?? null,
)

const selectedGate = computed(() =>
    props.gates.find((g) => String(g.id) === String(form.gate_id)) ?? null,
)

const selectedDriver = computed(() => {
    if (form.driver_user_id === 'unassigned') return null
    return props.drivers.find((d) => String(d.id) === String(form.driver_user_id)) ?? null
})

const bayOptions = computed(() => selectedGate.value?.bay_options ?? [])
const isEditing = computed(() => editingDispatchId.value !== null)

/* ======================================================
   Handlers
====================================================== */
function onGateChange(value: string) {
    form.gate_id = value
    form.bay_number = ''
}

function resetForm() {
    form.transform((d) => d)
    form.reset()
    form.driver_user_id = 'unassigned'
    form.clearErrors()
}

function resetDepartForm() {
    departForm.reset()
    departForm.clearErrors()
}

function openCreateDialog() {
    editingDispatchId.value = null
    resetForm()
    dialogOpen.value = true
}

function openEditDialog(dispatch: DispatchItem) {
    if (dispatch.status === 'departed') return
    editingDispatchId.value = dispatch.id
    form.transform((d) => d)
    form.clearErrors()
    form.vehicle_id = dispatch.vehicle?.id ? String(dispatch.vehicle.id) : ''
    form.driver_user_id = dispatch.driver?.id ? String(dispatch.driver.id) : 'unassigned'
    form.gate_id = dispatch.gate?.id ? String(dispatch.gate.id) : ''
    form.bay_number = String(dispatch.bay_number ?? '')
    form.remarks = dispatch.remarks ?? ''
    dialogOpen.value = true
}

function openRemarksDialog(dispatch: DispatchItem) {
    viewingDispatch.value = dispatch
    remarksViewOpen.value = true
}

function submit() {
    const payload = {
        ...form.data(),
        driver_user_id:
            form.driver_user_id && form.driver_user_id !== 'unassigned'
                ? form.driver_user_id
                : null,
    }

    const afterRequest = () => {
        form.transform((d) => d)
    }

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            dialogOpen.value = false
            editingDispatchId.value = null
            resetForm()
        },
        onError: afterRequest,
        onFinish: afterRequest,
    }

    form.transform(() => payload)

    if (isEditing.value && editingDispatchId.value) {
        form.put(DispatchController.update(editingDispatchId.value).url, options)
        return
    }

    form.post(DispatchController.store().url, options)
}

function askDepart(dispatch: DispatchItem) {
    pendingDepartId.value = dispatch.id
    pendingDepartDispatch.value = dispatch
    resetDepartForm()
    departForm.pax_count = String(dispatch.pax_count ?? '')
    confirmDepartOpen.value = true
}

function confirmDepart() {
    if (!pendingDepartId.value) return
    departForm.patch(DispatchController.depart(pendingDepartId.value).url, {
        preserveScroll: true,
        onSuccess: () => {
            confirmDepartOpen.value = false
            pendingDepartId.value = null
            pendingDepartDispatch.value = null
            resetDepartForm()
        },
    })
}

/* ======================================================
   Badge / status helpers
====================================================== */
function dispatchStatusClass(status?: string | null) {
    if (status === 'arrived')  return 'bg-emerald-100 text-emerald-700 border-emerald-200'
    if (status === 'departed') return 'bg-slate-100 text-slate-500 border-slate-200'
    if (status === 'pending')  return 'bg-amber-100 text-amber-700 border-amber-200'
    return 'bg-slate-100 text-slate-500 border-0'
}

function dispatchStatusDot(status?: string | null) {
    if (status === 'arrived')  return 'bg-emerald-500'
    if (status === 'departed') return 'bg-slate-400'
    if (status === 'pending')  return 'bg-amber-400'
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
)

const departedCount = computed(
    () => props.dispatches.data.filter((d) => d.status === 'departed').length,
)

const totalPax = computed(() =>
    props.dispatches.data.reduce((s, d) => s + (d.pax_count ?? 0), 0),
)

/* ======================================================
   Watchers
====================================================== */
watch(dialogOpen, (open) => {
    if (!open) {
        editingDispatchId.value = null
        form.transform((d) => d)
        form.clearErrors()
    }
})

watch(confirmDepartOpen, (open) => {
    if (!open) {
        pendingDepartId.value = null
        pendingDepartDispatch.value = null
        resetDepartForm()
    }
})
</script>

<template>
    <Head title="Dispatches" />

    <ExternalLayout :company="company">
        <div class="min-h-screen bg-slate-50/60">
            <div class="mx-auto max-w-7xl space-y-6 p-4 md:p-8">

                <!-- ── Page header ───────────────────────────── -->
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-slate-400">
                            <Building2 class="h-3.5 w-3.5" />
                            {{ company.company_code ?? company.company_name }}
                        </div>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                            Dispatching Module
                        </h1>
                        <p class="text-sm text-slate-500">
                            Manage and monitor vehicle dispatches for {{ company.company_name }}.
                        </p>
                    </div>

                <Button size="sm" class="w-full md:w-auto" @click="openCreateDialog">
                    <Plus class="mr-2 h-4 w-4" />
                    Add Dispatch
                </Button>
            </div>

                <!-- ── Stats ─────────────────────────────────── -->
                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-blue-700">
                            <Bus class="h-4 w-4 text-white" />
                        </div>
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">
                            Total Dispatches
                        </p>
                        <p class="mt-0.5 text-3xl font-bold tabular-nums text-slate-900">
                            {{ dispatches.total }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-600">
                            <Clock3 class="h-4 w-4 text-white" />
                        </div>
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">
                            On-site
                        </p>
                        <p class="mt-0.5 text-3xl font-bold tabular-nums text-slate-900">
                            {{ arrivedCount }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-slate-500">
                            <LogOut class="h-4 w-4 text-white" />
                        </div>
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">
                            Departed
                        </p>
                        <p class="mt-0.5 text-3xl font-bold tabular-nums text-slate-900">
                            {{ departedCount }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-sky-600">
                            <TrendingUp class="h-4 w-4 text-white" />
                        </div>
                        <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">
                            Passengers
                        </p>
                        <p class="mt-0.5 text-3xl font-bold tabular-nums text-slate-900">
                            {{ totalPax }}
                        </p>
                    </div>
                </div>

                <!-- ── Table card ─────────────────────────────── -->
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm">

                    <!-- Card header with filters -->
                    <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-4">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h2 class="text-base font-semibold text-slate-800">Dispatch Records</h2>
                                <p class="mt-0.5 text-xs text-slate-400">
                                    Arrival time is automatically recorded on dispatch creation.
                                </p>
                            </div>

                            <!-- Filters row -->
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                <!-- Search -->
                                <SearchInput
                                    :route="DispatchController.index().url"
                                    :initial-value="props.filters?.search ?? ''"
                                    placeholder="Search plate, remarks…"
                                    :only="['dispatches', 'filters']"
                                    class="w-full sm:w-56"
                                />

                                <!-- Status filter -->
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
                                    <SelectTrigger class="w-full sm:w-36">
                                        <SelectValue placeholder="All statuses" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Statuses</SelectItem>
                                        <SelectItem value="arrived">Arrived</SelectItem>
                                        <SelectItem value="departed">Departed</SelectItem>
                                    </SelectContent>
                                </Select>

                                <!-- Date filter -->
                                <Popover v-model:open="calendarOpen">
                                    <PopoverTrigger as-child>
                                        <Button
                                            variant="outline"
                                            :class="[
                                                'w-full justify-start gap-2 sm:w-44',
                                                selectedDate
                                                    ? 'border-blue-200 bg-blue-50 text-blue-700'
                                                    : 'text-slate-500 hover:text-slate-700',
                                            ]"
                                        >
                                            <CalendarDays
                                                class="h-4 w-4 shrink-0"
                                                :class="selectedDate ? 'text-blue-600' : ''"
                                            />
                                            <span class="truncate text-sm">
                                                {{ selectedDateLabel ?? 'Pick a date' }}
                                            </span>
                                            <span
                                                v-if="selectedDate"
                                                class="ml-auto flex h-4 w-4 shrink-0 items-center justify-center rounded-full hover:bg-blue-100"
                                                @click.stop="clearDateFilter"
                                            >
                                                <X class="h-3 w-3" />
                                            </span>
                                        </Button>
                                    </PopoverTrigger>

                                    <PopoverContent class="w-auto rounded-xl border-slate-200 p-0 shadow-lg" align="end">
                                        <div class="border-b border-slate-100 px-4 py-3">
                                            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">
                                                Filter by Date
                                            </p>
                                        </div>

                                        <div class="flex gap-1.5 border-b border-slate-100 px-3 py-2">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                class="h-7 flex-1 text-xs"
                                                :class="selectedDateLabel === 'Today' ? 'border-blue-300 bg-blue-50 text-blue-700' : ''"
                                                @click="setToday"
                                            >
                                                Today
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                class="h-7 flex-1 text-xs"
                                                :class="selectedDateLabel === 'Yesterday' ? 'border-blue-300 bg-blue-50 text-blue-700' : ''"
                                                @click="setYesterday"
                                            >
                                                Yesterday
                                            </Button>
                                            <Button
                                                v-if="selectedDate"
                                                size="sm"
                                                variant="ghost"
                                                class="h-7 flex-1 text-xs text-slate-400"
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

                        <!-- Active date pill -->
                        <div v-if="selectedDate" class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">
                                <CalendarDays class="h-3 w-3" />
                                Showing dispatches for {{ selectedDateLabel }}
                                <button
                                    class="ml-1 rounded-full p-0.5 hover:bg-blue-100"
                                    @click="clearDateFilter"
                                >
                                    <X class="h-2.5 w-2.5" />
                                </button>
                            </span>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow class="border-slate-100 bg-slate-50/70 hover:bg-slate-50/70">
                                    <TableHead class="pl-5 text-[11px] font-bold uppercase tracking-widest text-slate-400">
                                        Vehicle
                                    </TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-slate-400">
                                        Driver
                                    </TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-slate-400">
                                        Gate / Bay
                                    </TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-slate-400">
                                        Pax
                                    </TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-slate-400">
                                        Status
                                    </TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-slate-400">
                                        Arrived
                                    </TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-slate-400">
                                        Departed
                                    </TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-slate-400">
                                        Dispatcher
                                    </TableHead>
                                    <TableHead class="text-[11px] font-bold uppercase tracking-widest text-slate-400">
                                        Remarks
                                    </TableHead>
                                    <TableHead class="pr-5 text-right text-[11px] font-bold uppercase tracking-widest text-slate-400">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <!-- Empty state -->
                                <TableRow v-if="dispatches.data.length === 0" class="hover:bg-transparent">
                                    <TableCell colspan="10" class="py-20 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100">
                                                <Bus class="h-6 w-6 text-slate-400" />
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-slate-600">
                                                    {{ selectedDate ? `No dispatches on ${selectedDateLabel}` : 'No dispatch records found' }}
                                                </p>
                                                <p class="mt-0.5 text-xs text-slate-400">
                                                    {{ selectedDate ? 'Try a different date or clear the filter.' : 'Try adjusting your search or add a new dispatch.' }}
                                                </p>
                                            </div>
                                            <div class="flex gap-2">
                                                <Button
                                                    v-if="selectedDate"
                                                    size="sm"
                                                    variant="outline"
                                                    class="h-8 rounded-lg text-xs"
                                                    @click="clearDateFilter"
                                                >
                                                    <X class="mr-1.5 h-3.5 w-3.5" />
                                                    Clear Date
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    class="h-8 rounded-lg border-0 bg-blue-700 text-xs text-white hover:bg-blue-800"
                                                    @click="openCreateDialog"
                                                >
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
                                    class="border-slate-100 transition-colors hover:bg-slate-50/80"
                                    :class="dispatch.status === 'departed' ? 'opacity-60' : ''"
                                >
                                    <!-- Vehicle -->
                                    <TableCell class="pl-5">
                                        <div class="flex items-start gap-2.5">
                                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-100">
                                                <Bus class="h-3.5 w-3.5 text-blue-700" />
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-slate-800">
                                                    {{ dispatch.plate_number }}
                                                </p>
                                                <p v-if="dispatch.vehicle?.vehicle_type" class="text-xs text-slate-400">
                                                    {{ dispatch.vehicle.vehicle_type }}
                                                </p>
                                            </div>
                                        </div>
                                    </TableCell>

                                    <!-- Driver -->
                                    <TableCell>
                                        <div class="flex items-center gap-1.5">
                                            <UserRound class="h-3.5 w-3.5 shrink-0 text-slate-400" />
                                            <span
                                                class="text-sm"
                                                :class="!dispatch.driver ? 'italic text-slate-400' : 'text-slate-700'"
                                            >
                                                {{ dispatch.driver?.name ?? 'Unassigned' }}
                                            </span>
                                        </div>
                                    </TableCell>

                                    <!-- Gate / Bay -->
                                    <TableCell>
                                        <p class="text-sm font-medium text-slate-700">
                                            {{ dispatch.gate?.gate_name ?? '—' }}
                                        </p>
                                        <p class="text-xs text-slate-400">Bay {{ dispatch.bay_number }}</p>
                                    </TableCell>

                                    <!-- Pax -->
                                    <TableCell>
                                        <div class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                                            <Users class="h-3 w-3" />
                                            {{ dispatch.pax_count }}
                                        </div>
                                    </TableCell>

                                    <!-- Status -->
                                    <TableCell>
                                        <span
                                            :class="[
                                                'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium',
                                                dispatchStatusClass(dispatch.status),
                                            ]"
                                        >
                                            <span
                                                :class="[
                                                    'h-1.5 w-1.5 rounded-full',
                                                    dispatchStatusDot(dispatch.status),
                                                ]"
                                            />
                                            {{ humanize(dispatch.status) }}
                                        </span>
                                    </TableCell>

                                    <!-- Arrived -->
                                    <TableCell>
                                        <div v-if="dispatch.arrived_at_formatted" class="flex items-center gap-1.5">
                                            <Clock3 class="h-3.5 w-3.5 shrink-0 text-slate-400" />
                                            <span class="text-sm text-slate-600">{{ dispatch.arrived_at_formatted }}</span>
                                        </div>
                                        <span v-else class="text-sm text-slate-400">—</span>
                                    </TableCell>

                                    <!-- Departed -->
                                    <TableCell>
                                        <div v-if="dispatch.departed_at_formatted" class="flex items-center gap-1.5">
                                            <CheckCircle2 class="h-3.5 w-3.5 shrink-0 text-slate-400" />
                                            <span class="text-sm text-slate-600">{{ dispatch.departed_at_formatted }}</span>
                                        </div>
                                        <span v-else class="text-sm text-slate-400">—</span>
                                    </TableCell>

                                    <!-- Dispatcher -->
                                    <TableCell>
                                        <div class="flex items-center gap-1.5">
                                            <Fingerprint class="h-3.5 w-3.5 shrink-0 text-slate-400" />
                                            <span class="text-sm text-slate-700">{{ dispatch.dispatcher?.name || '—' }}</span>
                                        </div>
                                    </TableCell>

                                    <!-- Remarks -->
                                    <TableCell>
                                        <TooltipProvider v-if="dispatch.remarks">
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        class="h-7 gap-1.5 rounded-lg border-slate-200 text-xs text-slate-600 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700"
                                                        @click="openRemarksDialog(dispatch)"
                                                    >
                                                        <FileText class="h-3 w-3" />
                                                        View
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent side="top" class="max-w-52 text-xs">
                                                    {{ dispatch.remarks }}
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                        <div v-else class="flex items-center gap-1.5">
                                            <FileText class="h-3.5 w-3.5 text-slate-300" />
                                            <span class="text-xs text-slate-400">No remarks</span>
                                        </div>
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
                                                </Button>
                                            </DropdownMenuTrigger>

                                            <DropdownMenuContent
                                                align="end"
                                                class="w-56 rounded-xl border-slate-200 shadow-lg"
                                            >
                                                <DropdownMenuLabel class="text-xs font-semibold uppercase tracking-widest text-slate-400">
                                                    Actions
                                                </DropdownMenuLabel>

                                                <DropdownMenuSeparator class="bg-slate-100" />

                                                <!-- View details -->
                                                <DropdownMenuItem
                                                    as-child
                                                    class="rounded-lg text-slate-700 focus:bg-blue-50 focus:text-blue-700"
                                                >
                                                    <Link :href="DispatchController.show(dispatch.id).url">
                                                        <ArrowUpRight class="mr-2 h-4 w-4" />
                                                        View Details
                                                    </Link>
                                                </DropdownMenuItem>

                                                <!-- View remarks -->
                                                <DropdownMenuItem
                                                    v-if="dispatch.remarks"
                                                    class="rounded-lg text-slate-700 focus:bg-slate-50 focus:text-slate-800"
                                                    @click="openRemarksDialog(dispatch)"
                                                >
                                                    <FileText class="mr-2 h-4 w-4" />
                                                    View Remarks
                                                </DropdownMenuItem>

                                                <!-- Arrived-only actions -->
                                                <template v-if="dispatch.status === 'arrived'">
                                                    <DropdownMenuSeparator class="bg-slate-100" />

                                                    <DropdownMenuItem
                                                        class="rounded-lg text-slate-700 focus:bg-amber-50 focus:text-amber-700"
                                                        @click="openEditDialog(dispatch)"
                                                    >
                                                        <Pencil class="mr-2 h-4 w-4" />
                                                        Edit Dispatch
                                                    </DropdownMenuItem>

                                                    <DropdownMenuItem
                                                        class="rounded-lg text-amber-600 focus:bg-amber-50 focus:text-amber-700"
                                                        @click="askDepart(dispatch)"
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

                    <!-- Pagination -->
                    <div v-if="dispatches.last_page > 1 || dispatches.total > 0" class="border-t border-slate-100 px-5 py-3">
                        <div class="flex flex-col items-center justify-between gap-2 sm:flex-row">
                            <p class="text-xs text-slate-400">
                                Showing
                                <span class="font-medium text-slate-600">{{ dispatches.from ?? 0 }}</span>–<span class="font-medium text-slate-600">{{ dispatches.to ?? 0 }}</span>
                                of <span class="font-medium text-slate-600">{{ dispatches.total }}</span> records
                                <template v-if="selectedDate">
                                    for <span class="font-medium text-blue-700">{{ selectedDateLabel }}</span>
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

        <!-- ── Create / Edit Dialog ──────────────────── -->
        <Dialog v-model:open="dialogOpen">
            <DialogContent class="rounded-2xl sm:max-w-md">
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
                                <SelectItem value="unassigned">No driver assigned</SelectItem>
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
                        <Select v-model="form.bay_number" :disabled="!selectedGate">
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
                            <span class="text-slate-400">Gate</span>
                            {{ selectedGate.gate_name }} · {{ selectedGate.bays }} bays
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="remarks">
                            Remarks
                            <span class="ml-1 text-xs font-normal text-slate-400">(optional)</span>
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
                    class="min-h-[80px] rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm leading-relaxed text-slate-700"
                    :class="!viewingDispatch?.remarks ? 'italic text-slate-400' : ''"
                >
                    {{ viewingDispatch?.remarks || 'No remarks recorded.' }}
                </div>

                <div v-if="viewingDispatch" class="grid grid-cols-2 gap-3 text-xs">
                    <div class="space-y-0.5">
                        <p class="font-semibold uppercase tracking-widest text-slate-400">Driver</p>
                        <p
                            class="font-semibold text-slate-800"
                            :class="!viewingDispatch.driver ? 'italic text-slate-400' : ''"
                        >
                            {{ viewingDispatch.driver?.name ?? 'Unassigned' }}
                        </p>
                    </div>
                    <div class="space-y-0.5">
                        <p class="font-semibold uppercase tracking-widest text-slate-400">Dispatcher</p>
                        <p class="font-semibold text-slate-800">{{ viewingDispatch.dispatcher?.name ?? '—' }}</p>
                    </div>
                    <div class="space-y-0.5">
                        <p class="font-semibold uppercase tracking-widest text-slate-400">Status</p>
                        <span
                            :class="[
                                'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium',
                                dispatchStatusClass(viewingDispatch.status),
                            ]"
                        >
                            <span :class="['h-1.5 w-1.5 rounded-full', dispatchStatusDot(viewingDispatch.status)]" />
                            {{ humanize(viewingDispatch.status) }}
                        </span>
                    </div>
                    <div class="space-y-0.5">
                        <p class="font-semibold uppercase tracking-widest text-slate-400">Arrived</p>
                        <p class="font-semibold text-slate-800">{{ viewingDispatch.arrived_at_formatted ?? '—' }}</p>
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
                        <AlertDialogTitle>Mark as Departed?</AlertDialogTitle>
                        <AlertDialogDescription>
                            <span class="block">
                                This will record the departure time as <strong>now</strong>.
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
                            </p>
                            <p class="text-xs text-slate-400">
                                {{ pendingDepartDispatch.gate?.gate_name ?? '—' }} · Bay
                                {{ pendingDepartDispatch.bay_number }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="depart_pax_count">Passenger Count</Label>
                            <Input
                                id="depart_pax_count"
                                v-model="departForm.pax_count"
                                type="number"
                                min="0"
                                placeholder="Enter passenger count"
                            />
                            <InputError :message="departForm.errors.pax_count" />
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
                        <Button
                            type="submit"
                            :disabled="departForm.processing"
                            class="rounded-lg border-0 bg-blue-700 text-white hover:bg-blue-800"
                        >
                            {{ departForm.processing ? 'Saving…' : 'Confirm Departure' }}
                        </Button>
                    </AlertDialogFooter>
                </form>
            </AlertDialogContent>
        </AlertDialog>
    </ExternalLayout>
</template>