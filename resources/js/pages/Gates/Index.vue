<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';
import InputError from '@/components/InputError.vue';
import SearchInput from '@/components/SearchInput.vue';
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

import AppLayout from '@/layouts/AppLayout.vue';
import { destroy, index, show, store, trash, update } from '@/routes/gates';
import { type BreadcrumbItem, type User } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

import {
    Eye,
    Loader2,
    Pencil,
    Save,
} from 'lucide-vue-next';

import {
    RiArchive2Line,
    RiArrowRightSLine,
    RiEyeLine,
    RiFilter2Line,
    RiLoaderLine,
    RiMore2Line,
    RiEditLine,
    RiAddLine,
    RiSaveLine,
    RiCloseLine,
    RiImageAddLine,
    RiExternalLinkLine,
} from 'vue-remix-icons';

import { computed, ref } from 'vue';

interface Gate {
    id: number;
    gate_name: string;
    status: 'active' | 'inactive';
    bays: number;
    creator: User | null;
    location: {
        label: string;
        is_placeholder: boolean;
    };
    picture_url: string | null;
    assigned_routes: {
        id: number;
        route_name: string;
        status: string;
    }[];
    bay_statuses: {
        bay_number: number;
        status: 'empty' | 'occupied';
        vehicle: {
            plate_number: string | null;
            body_number: string | null;
        } | null;
        company: {
            company_name: string;
        } | null;
    }[];
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Gates', href: index().url }];

const props = withDefaults(
    defineProps<{
        gates: {
            data: Gate[];
            links: any[];
            from: number | null;
            to: number | null;
            total: number;
        };
        filters?: {
            search: string | null;
            status: string | null;
            bays: string | null;
        };
    }>(),
    { filters: () => ({ search: null, status: null, bays: null }) },
);

const gateSuggestions = Array.from({ length: 20 }, (_, i) => `Gate ${i + 1}`);
const baySuggestions  = Array.from({ length: 20 }, (_, i) => String(i + 1));

const filterStatus = ref<string>(
    props.filters?.status ? String(props.filters.status) : 'all'
);
const filterBays   = ref<string>(props.filters?.bays ?? '');
const filterOpen   = ref(false);

const activeFilterCount = computed(() => {
    let count = 0;
    if (filterStatus.value && filterStatus.value !== 'all') count++;
    if (filterBays.value)                                    count++;
    return count;
});

function applyFilters() {
    router.get(
        index().url,
        {
            search: props.filters?.search || undefined,
            status: filterStatus.value === 'all' ? undefined : filterStatus.value,
            bays: filterBays.value || undefined,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['gates', 'filters'],
        },
    );
    filterOpen.value = false;
}

function clearFilters() {
    filterStatus.value = 'all';
    filterBays.value = '';

    router.get(
        index().url,
        {},
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['gates', 'filters'],
        },
    );

    filterOpen.value = false;
}

const form = useForm({
    gate_name: '',
    status: 'active' as 'active' | 'inactive',
    bays: '' as number | string,
});

const createOpen   = ref(false);
const editOpen     = ref(false);
const archiveOpen  = ref(false);
const selectedGate = ref<Gate | null>(null);
const previewedGate = ref<Gate | null>(null);

function statusClass(status: Gate['status']): string {
    return status === 'active'
        ? 'bg-emerald-100 text-emerald-700 border-emerald-200'
        : 'bg-slate-100 text-slate-500 border-0';
}

function statusDot(status: Gate['status']): string {
    return status === 'active' ? 'bg-emerald-500' : 'bg-slate-400';
}

function openEdit(gate: Gate) {
    selectedGate.value = gate;
    form.gate_name     = gate.gate_name;
    form.status        = gate.status;
    form.bays          = gate.bays;
    editOpen.value     = true;
}

function closeEdit() {
    editOpen.value     = false;
    selectedGate.value = null;
    form.reset();
    form.gate_name = '';
    form.status    = 'active';
    form.bays      = '';
}

function openPreview(gate: Gate) {
    previewedGate.value = gate;
}

function openArchive(gate: Gate) {
    selectedGate.value = gate;
    archiveOpen.value  = true;
}

const createGate = () => {
    form.post(store().url, {
        preserveScroll: true,
        onSuccess: () => {
            createOpen.value = false;
            form.reset();
            form.gate_name = '';
            form.status    = 'active';
            form.bays      = '';
        },
    });
};

const editGate = () => {
    if (!selectedGate.value) return;
    form.put(update(selectedGate.value.id).url, {
        preserveScroll: true,
        onSuccess: () => closeEdit(),
    });
};

const archiveGate = () => {
    if (!selectedGate.value) return;
    router.delete(destroy(selectedGate.value.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            archiveOpen.value  = false;
            selectedGate.value = null;
        },
    });
};
</script>

<template>
    <Head title="Gates" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full min-h-0 w-full flex-1 flex-col gap-4 lg:flex-row lg:items-stretch">
            <Card class="min-h-0 min-w-0 flex-1 lg:h-full">
                <CardHeader class="flex flex-row gap-2">
                    <div class="flex flex-col">
                        <CardTitle class="flex items-center gap-2">
                            <span class="font-semibold">Gates</span>
                        </CardTitle>
                        <CardDescription class="">List of all gates in the system.</CardDescription>
                    </div>
                    <div class="flex flex-1 gap-2 justify-end">
                        <!-- move this in the app topbar -->
                        <!-- <div class="ml-2 flex flex-1 items-center">
                            <hr class="h-px w-full border border-custom-primary" />
                            cant decide which one looks better -->
                            <!-- <div class="border-12 border-custom-primary">
                                <div class="border-6 border-custom-bg-light dark:border-custom-bg"></div>
                            </div>
                            <div class="border-7 border-custom-primary">
                                <div class="border-3 border-custom-bg-light dark:border-custom-bg"></div>
                            </div>
                        </div> -->
                        <div class="lg:flex items-center gap-2 sm:justify-end">
                            <Button
                                variant="float-primary"
                                @click="createOpen = true"
                                class="hidden lg:flex"
                            >
                                <RiAddLine class="h-4 w-4 shrink-0" />
                                <span>Add Gate</span>
                            </Button>
                            <DropdownMenu class="w-fit">
                                <DropdownMenuTrigger as-child class="m-0">
                                    <div class="inline-flex">
                                        <Button
                                            variant="header-actions"
                                            class="text-custom-shadow"
                                            size="icon"
                                        >
                                            <RiMore2Line class="h-4 w-4 shrink-0" />
                                        </Button>
                                    </div>
                                </DropdownMenuTrigger>

                                <DropdownMenuContent align="end" class="w-fit">
                                    <DropdownMenuItem
                                        as-child
                                        class="cursor-pointer lg:hidden"
                                    >
                                        <Link :href="trash().url" class="flex items-center">
                                            <RiAddLine class="h-4 w-4 hover:text-custom-bg-light" />
                                            Add Gate
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        as-child
                                        class="cursor-pointer"
                                    >
                                        <Link :href="trash().url" class="flex items-center">
                                            <RiArchive2Line class="h-4 w-4 hover:text-custom-bg-light" />
                                            Archives
                                        </Link>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>
                </CardHeader>

                <CardContent class="flex min-h-0 flex-1 flex-col space-y-4 py-2">
                    <div class="flex flex-row gap-2 lg:items-center lg:justify-between">
                        <div class="w-full">
                            <SearchInput
                                :route="index().url"
                                :initial-value="props.filters?.search"
                                placeholder="Search gates…"
                                :only="['gates', 'filters']"
                                :debounce="350"
                            />
                        </div>

                        <div class="w-fit flex gap-2 flex-row lg:items-center lg:justify-between">
                            <Popover v-model:open="filterOpen">
                                <PopoverTrigger
                                    as-child
                                >
                                    <Button
                                        variant="header-actions"
                                        size="icon-text"
                                        class="rounded-full "
                                        :class="
                                            activeFilterCount > 0
                                                ? ' bg-custom-secondary/20 hover:text-custom-bg-light hover:bg-custom-secondary/80 transition-all duration-300'
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

                                <PopoverContent
                                    align="end"
                                >
                                    <div class="grid gap-y-2">
                                        <div class="space-y-2">
                                            <p class="text-sm text-custom-shadow/80">
                                                Status
                                            </p>
                                            <Select v-model="filterStatus">
                                                <SelectTrigger class="w-full">
                                                    <SelectValue placeholder="Any status" class="flex justify-start" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="all" class="cursor-pointer">
                                                        Any status
                                                    </SelectItem>
                                                    <SelectItem value="active" class="cursor-pointer">
                                                        Active
                                                    </SelectItem>
                                                    <SelectItem value="inactive" class="cursor-pointer">
                                                        Inactive
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div class="space-y-2">
                                            <p class="text-sm text-custom-shadow/80">
                                                No. of Bays
                                            </p>
                                            <Input
                                                v-model="filterBays"
                                                type="number"
                                                min="0"
                                                placeholder="e.g. 5"
                                                class="bg-custom-bg"
                                            />
                                        </div>

                                        <hr class="my-1 h-px border-0 bg-custom-bg-dark dark:bg-custom-bg-light">

                                        <div class="w-full justify-between items-center flex flex-row">
                                            <Button
                                                v-if="activeFilterCount > 0"
                                                size="sm"
                                                variant="destructive"
                                                class=""
                                                @click="clearFilters"
                                            >
                                                Clear
                                            </Button>

                                            <div class="flex ml-auto items-center gap-2">
                                                <Button
                                                    variant="ghost-outline"
                                                    size="sm"
                                                    class=""
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
                            'flex min-h-0 flex-1 max-h-fit flex-col overflow-hidden border border-custom-bg-dark dark:border-custom-bg-light py-0 shadow-none dark:inset-shadow-none',
                            props.gates.data.length === 0 ? 'border-dashed' : 'border-solid',
                        ]"
                    >
                        <div v-if="props.gates.data.length > 0" class="flex min-h-0 flex-1 flex-col overflow-hidden">
                            <div class="shrink-0 rounded-t-md bg-custom-bg dark:bg-custom-bg-light">
                                <div class="grid grid-cols-4 gap-2 border-b border-custom-bg-dark dark:border-custom-bg-light">
                                    <div class="col-span-1 flex h-10 font-semibold items-center justify-start px-0 pl-3 text-left text-xs uppercase tracking-widest text-custom-shadow/80">Name</div>
                                    <div class="col-span-1 flex h-10 font-semibold items-center justify-start px-0 text-left text-xs uppercase tracking-widest text-custom-shadow/80">Status</div>
                                    <div class="col-span-1 flex h-10 font-semibold items-center justify-start px-0 text-left text-xs uppercase tracking-widest text-custom-shadow/80">Bays</div>
                                    <!-- <TableHead class="uppercase tracking-widest">Created By</TableHead> -->
                                    <div class="col-span-1 flex h-10 font-semibold items-center justify-end px-0 pr-3 text-left text-xs uppercase tracking-widest text-custom-shadow/80">Actions</div>
                                </div>
                                <!-- <TableRow class="border-b-0 hover:bg-transparent">
                                    <TableHead colspan="5" class="h-auto p-0">
                                        <hr class="mx-3 border-0 border-t border-custom-bg-dark" />
                                    </TableHead>
                                </TableRow> -->
                            </div>

                            <div class="no-scrollbar min-h-0 flex-1 overflow-y-auto">
                                <template
                                    v-for="(gate, index) in props.gates.data"
                                    :key="gate.id"
                                >
                                <div
                                    :class="[
                                        'text-custom-shadow/80 grid grid-cols-4 border-b border-custom-bg-dark dark:border-custom-bg-light transition-colors cursor-pointer hover:bg-custom-secondary/10 hover:text-custom-shadow items-center',
                                        index === props.gates.data.length - 1 ? 'rounded-b-md border-b-0' : '',
                                        previewedGate?.id === gate.id ? 'bg-custom-secondary/10 text-custom-shadow' : '',
                                    ]"
                                    @click="openPreview(gate)"
                                >
                                    <div class="col-span-1 flex justify-start py-1.5 pl-3 font-semibold capitalize">{{ gate.gate_name }}</div>

                                    <div class="col-span-1 flex justify-start py-1.5">
                                        <Badge :class="['gap-1.5', statusClass(gate.status)]">
                                            <span :class="['h-1.5 w-1.5 rounded-full', statusDot(gate.status)]" />
                                            {{ gate.status === 'active' ? 'Active' : 'Inactive' }}
                                        </Badge>
                                    </div>

                                    <div class="col-span-1 flex justify-start py-1.5 tabular-nums">{{ gate.bays }}</div>

                                    <!-- <TableCell class="text-sm text-muted-foreground">{{ gate.creator?.name ?? '—' }}</TableCell> -->

                                    <div class="col-span-1 flex justify-end py-1.5 pr-3 text-right" @click.stop>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="table-more"
                                                    size="icon-more"
                                                    class=""
                                                >
                                                    <RiMore2Line class="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>

                                            <DropdownMenuContent align="end" class="w-fit rounded-lg border-slate-200 shadow-lg">
                                                <DropdownMenuLabel class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                                                    {{ gate.gate_name }}
                                                </DropdownMenuLabel>
                                                <DropdownMenuSeparator />

                                                <DropdownMenuItem
                                                    class="hidden rounded-lg hover:bg-slate-100 cursor-pointer lg:flex"
                                                    @click="openPreview(gate)"
                                                >
                                                    <Eye class="h-4 w-4" />
                                                    View
                                                </DropdownMenuItem>

                                                <DropdownMenuItem as-child class="rounded-lg hover:bg-slate-100 cursor-pointer lg:hidden">
                                                    <Link :href="show(gate.id).url" class="flex items-center">
                                                        <Eye class="h-4 w-4" />
                                                        View
                                                    </Link>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem class="rounded-lg hover:bg-slate-100 cursor-pointer" @click="openEdit(gate)">
                                                    <Pencil class="h-4 w-4" />
                                                    Edit
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </div>

                                <!-- <TableRow
                                    v-if="index < props.gates.data.length - 1"
                                    class="border-b-0 hover:bg-transparent"
                                >
                                    <TableCell colspan="5" class="py-0">
                                        <hr class="mx-3 border-0 border-t border-custom-bg-dark" />
                                    </TableCell>
                                </TableRow> -->
                                </template>
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
                                    <p class="text-custom-shadow text-base font-semibold">No gates found</p>
                                    <p class="text-custom-shadow/80 text-sm">
                                        {{ activeFilterCount > 0 ? 'Try adjusting or clearing your filters.' : 'Try adjusting your search or add a new gate.' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </Card>

                    <InertiaPagination
                        :links="props.gates.links"
                        :meta="{ from: props.gates.from, to: props.gates.to, total: props.gates.total }"
                    />
                </CardContent>
            </Card>

            <Card
                class="hidden min-h-0 lg:flex lg:h-full lg:w-100"
            >
                <CardHeader
                    v-if="previewedGate"
                    class="flex flex-row items-start justify-between gap-3"
                >
                    <div class="min-w-0">
                        <CardTitle class="truncate capitalize">
                            {{ previewedGate.gate_name }}
                        </CardTitle>
                        <CardDescription>Preview</CardDescription>
                    </div>
                    <Button
                        variant="header-actions"
                        size="icon"
                        class="h-8 w-8 shrink-0 rounded-full"
                        @click="previewedGate = null"
                    >
                        <RiCloseLine class="h-4 w-4" />
                    </Button>
                </CardHeader>

                <CardContent
                    v-if="previewedGate"
                    class="no-scrollbar min-h-0 flex-1 space-y-2 overflow-y-auto py-2"
                >
                    <div class="flex aspect-4/3 items-center justify-center overflow-hidden rounded-md border border-dashed border-custom-bg-dark dark:border-none bg-custom-bg dark:bg-custom-bg-dark text-custom-shadow/70">
                        <img
                            v-if="previewedGate.picture_url"
                            :src="previewedGate.picture_url"
                            :alt="`${previewedGate.gate_name} photo`"
                            class="h-full w-full object-cover"
                        />
                        <div v-else class="flex flex-col items-center gap-1 text-center">
                            <RiImageAddLine class="h-6 w-6" />
                        </div>
                    </div>

                    <div class="space-y-2 pt-2">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm text-custom-shadow font-semibold">Status</span>
                            <Badge :class="['gap-1.5', statusClass(previewedGate.status)]">
                                <span :class="['h-1.5 w-1.5 rounded-full', statusDot(previewedGate.status)]" />
                                {{ previewedGate.status === 'active' ? 'Active' : 'Inactive' }}
                            </Badge>
                        </div>

                        <div class="flex items-start justify-between gap-3">
                            <span class="text-sm text-custom-shadow font-semibold">Location</span>
                            <span class="text-right text-sm">
                                {{ previewedGate.location.label }}
                            </span>
                        </div>

                        <!-- <div class="flex items-center justify-between gap-3 border-b border-custom-bg-dark pb-3">
                            <span class="text-sm text-custom-shadow/70">Created By</span>
                            <span class="truncate text-sm font-medium">{{ previewedGate.creator?.name ?? 'Not recorded' }}</span>
                        </div> -->

                        <div class="space-y-2">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-semibold text-custom-shadow">Bay Status</p>
                                <span class="text-sm text-custom-shadow">
                                    {{ previewedGate.bay_statuses.filter((bay) => bay.status === 'occupied').length }} occupied out of {{ previewedGate.bays }}
                                </span>
                            </div>

                            <!-- <div
                                v-if="previewedGate.bay_statuses.length > 0"
                                class="space-y-2"
                            >
                                <div
                                    v-for="bay in previewedGate.bay_statuses"
                                    :key="bay.bay_number"
                                    class="rounded-md bg-custom-bg px-3 py-2"
                                >
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-sm font-medium">Bay {{ bay.bay_number }}</span>
                                        <Badge
                                            :class="bay.status === 'occupied'
                                                ? 'bg-custom-secondary/20 text-custom-shadow'
                                                : 'bg-emerald-100 text-emerald-700'"
                                        >
                                            {{ bay.status === 'occupied' ? 'Occupied' : 'Empty' }}
                                        </Badge>
                                    </div>
                                    <p
                                        v-if="bay.status === 'occupied'"
                                        class="mt-1 text-xs text-custom-shadow/70"
                                    >
                                        {{ bay.vehicle?.plate_number ?? 'Unknown unit' }}
                                        <span v-if="bay.vehicle?.body_number">/ Body #{{ bay.vehicle.body_number }}</span>
                                        - {{ bay.company?.company_name ?? 'Unknown company' }}
                                    </p>
                                </div>
                            </div>
                            <p
                                v-else
                                class="rounded-md bg-custom-bg px-3 py-2 text-sm text-custom-shadow/70"
                            >
                                No bays configured.
                            </p> -->
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-semibold text-custom-shadow">Assigned Routes</p>
                                <span class="text-sm text-custom-shadow">
                                    {{ previewedGate.assigned_routes.length }}
                                </span>
                            </div>
                            <!-- TODO: redesign the routes list, i dont like it, and the image part too -->

                            <div
                                v-if="previewedGate.assigned_routes.length > 0"
                                class="space-y-2"
                            >
                                <div
                                    v-for="route in previewedGate.assigned_routes"
                                    :key="route.id"
                                    class="flex items-center justify-between gap-3 rounded-md bg-custom-bg dark:bg-custom-bg-dark px-3 py-2"
                                >
                                    <span class="truncate text-sm font-medium">{{ route.route_name }}</span>
                                    <span class="shrink-0 text-xs capitalize text-custom-shadow/70">{{ route.status }}</span>
                                </div>
                            </div>
                            <p
                                v-else
                                class="rounded-md bg-custom-bg dark:bg-custom-bg-dark px-3 py-2 text-sm text-custom-shadow/70"
                            >
                                No routes assigned.
                            </p>
                        </div>
                    </div>

                    <!-- <hr class="border-custom-bg-dark dark:border-custom-bg-light my-4"> -->
                    <hr class="my-4 h-px border-0 bg-custom-bg-dark dark:bg-custom-bg-light">

                    <div class="flex items-center justify-between gap-2">
                        <Button
                            variant="ghost-outline"
                            size="icon-text"
                            @click="openEdit(previewedGate)"
                        >
                            <RiEditLine class="h-4 w-4" />
                            Edit
                        </Button>
                        <Button
                            as-child
                            variant="float-primary"
                            size="icon"
                        >
                            <Link :href="show(previewedGate.id).url">
                                <RiExternalLinkLine class="h-4 w-4" />
                            </Link>
                        </Button>
                    </div>
                </CardContent>

                <CardContent
                    v-else
                    class="flex min-h-0 flex-1 items-center justify-center"
                >
                    <div class="max-w-60 text-center space-y-1">
                        <!-- <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-custom-bg text-custom-shadow/70">
                            <RiEyeLine class="h-6 w-6" />
                        </div> -->
                        <p class="text-custom-shadow text-base font-semibold">No gate selected</p>
                        <p class="text-custom-shadow/80 text-sm">
                            Click on a gate to preview.
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- ── Create dialog ──────────────────────────────────────── -->
        <Dialog v-model:open="createOpen">
            <DialogContent class="rounded-lg sm:max-w-md p-4">
                <DialogHeader>
                    <DialogTitle>Add New Gate</DialogTitle>
                    <DialogDescription>Create a new gate with status and number of bays.</DialogDescription>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="createGate">
                    <div class="space-y-1.5">
                        <Label for="create_gate_name" class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Gate Name</Label>
                        <Input id="create_gate_name" v-model="form.gate_name" list="gate-name-suggestions" placeholder="Type gate name or select Gate 1–20" class="rounded-lg border-slate-200 hover:bg-slate-100 cursor-pointer" />
                        <datalist id="gate-name-suggestions">
                            <option v-for="opt in gateSuggestions" :key="opt" :value="opt" />
                        </datalist>
                        <InputError :message="form.errors.gate_name" />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="create_status" class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Status</Label>
                        <Select v-model="form.status">
                            <SelectTrigger id="create_status" class="w-full rounded-lg border-slate-200 hover:bg-slate-100 cursor-pointer">
                                <SelectValue placeholder="Select status" />
                            </SelectTrigger>
                            <SelectContent class="rounded-lg">
                                <SelectItem value="active" class="rounded-lg hover:bg-slate-100 cursor-pointer">Active</SelectItem>
                                <SelectItem value="inactive" class="rounded-lg hover:bg-slate-100 cursor-pointer">Inactive</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.status" />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="create_bays" class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Bays</Label>
                        <Input id="create_bays" v-model="form.bays" list="bay-suggestions" type="number" min="0" placeholder="Number of bays" class="rounded-lg border-slate-200 hover:bg-slate-100 cursor-pointer" />
                        <datalist id="bay-suggestions">
                            <option v-for="opt in baySuggestions" :key="opt" :value="opt" />
                        </datalist>
                        <InputError :message="form.errors.bays" />
                    </div>
                    <DialogFooter class="gap-2">
                        <Button type="button" variant="outline" class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100 cursor-pointer" @click="createOpen = false">Cancel</Button>
                        <Button type="submit" :disabled="form.processing" class="rounded-lg bg-primary cursor-pointer border-0 font-semibold disabled:opacity-60">
                            <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                            <Save v-else class="h-4 w-4" />
                            {{ form.processing ? 'Saving…' : 'Save Gate' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- ── Edit dialog ────────────────────────────────────────── -->
        <Dialog v-model:open="editOpen">
            <DialogContent class="rounded-lg sm:max-w-md p-4">
                <DialogHeader>
                    <DialogTitle>Edit Gate</DialogTitle>
                    <DialogDescription>Update the gate details.</DialogDescription>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="editGate">
                    <div class="space-y-1.5">
                        <Label for="edit_gate_name" class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Gate Name</Label>
                        <Input id="edit_gate_name" v-model="form.gate_name" list="gate-name-suggestions" placeholder="Type gate name or select Gate 1–20" class="rounded-lg border-slate-200 hover:bg-slate-100" />
                        <InputError :message="form.errors.gate_name" />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="edit_status" class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Status</Label>
                        <Select v-model="form.status">
                            <SelectTrigger id="edit_status" class="w-full rounded-lg border-slate-200 cursor-pointer hover:bg-slate-100">
                                <SelectValue placeholder="Select status" />
                            </SelectTrigger>
                            <SelectContent class="rounded-lg">
                                <SelectItem value="active" class="rounded-lg cursor-pointer hover:bg-slate-100">Active</SelectItem>
                                <SelectItem value="inactive" class="rounded-lg cursor-pointer hover:bg-slate-100">Inactive</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.status" />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="edit_bays" class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Bays</Label>
                        <Input id="edit_bays" v-model="form.bays" list="bay-suggestions" type="number" min="0" placeholder="Number of bays" class="rounded-lg border-slate-200 hover:bg-slate-100" />
                        <InputError :message="form.errors.bays" />
                    </div>
                    <DialogFooter class="gap-2">
                        <Button type="button" variant="outline" class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100 cursor-pointer" @click="closeEdit">Cancel</Button>
                        <Button type="submit" :disabled="form.processing" class="rounded-lg cursor-pointer border-0 font-semibold disabled:opacity-60">
                            <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                            <Save v-else class="h-4 w-4" />
                            {{ form.processing ? 'Saving…' : 'Save Changes' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- ── Archive confirm dialog ─────────────────────────────── -->
        <!-- <AlertDialog v-model:open="archiveOpen">
            <AlertDialogContent class="rounded-2xl">
                <AlertDialogHeader>
                    <AlertDialogTitle>Archive Gate</AlertDialogTitle>
                    <AlertDialogDescription>
                        Are you sure you want to archive
                        <span class="font-semibold text-foreground">{{ selectedGate?.gate_name ?? 'this gate' }}</span>?
                        You can restore it later from the Trash.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel class="rounded-lg" @click="selectedGate = null">Cancel</AlertDialogCancel>
                    <AlertDialogAction class="rounded-lg bg-rose-600 text-white hover:bg-rose-700 border-0" @click="archiveGate">
                        Archive
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog> -->

    </AppLayout>
</template>
