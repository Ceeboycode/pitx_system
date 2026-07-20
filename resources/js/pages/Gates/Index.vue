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
import Separator from '@/components/ui/separator/Separator.vue'

import AppLayout from '@/layouts/AppLayout.vue';
import { index, show, store, trash, update } from '@/routes/gates';
import { type BreadcrumbItem, type User } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

import {
    RiArchive2Line,
    RiFilter2Line,
    RiLoaderLine,
    RiMore2Line,
    RiEditLine,
    RiAddLine,
    RiShutDownLine,
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
    picture_path: string | null;
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
const createGateSuggestionsOpen = ref(false);
const editGateSuggestionsOpen = ref(false);
const createBaySuggestionsOpen = ref(false);
const editBaySuggestionsOpen = ref(false);
const pictureInputRef = ref<HTMLInputElement | null>(null);

const filteredGateSuggestions = computed(() => {
    const query = form.gate_name.trim().toLowerCase();
    return gateSuggestions.filter((suggestion) =>
        suggestion.toLowerCase().includes(query),
    );
});

const filteredBaySuggestions = computed(() => {
    const query = String(form.bays).trim();
    return baySuggestions.filter((suggestion) => suggestion.includes(query));
});

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
    location: '',
    picture: null as File | null,
});
const picturePreview = ref<string | null>(null);

function selectPicture(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    form.picture = file;
    if (picturePreview.value?.startsWith('blob:')) URL.revokeObjectURL(picturePreview.value);
    picturePreview.value = file ? URL.createObjectURL(file) : selectedGate.value?.picture_url ?? null;
}

function removePicture() {
    if (picturePreview.value?.startsWith('blob:')) URL.revokeObjectURL(picturePreview.value);
    picturePreview.value = null;
    form.picture = null;
    if (pictureInputRef.value) pictureInputRef.value.value = '';
}

function resetGateForm() {
    if (picturePreview.value?.startsWith('blob:')) URL.revokeObjectURL(picturePreview.value);
    picturePreview.value = null;
    form.reset();
    form.clearErrors();
}

const createOpen   = ref(false);
const editOpen     = ref(false);
const selectedGate = ref<Gate | null>(null);
const previewedGate = ref<Gate | null>(null);
const toggleOpen = ref(false);
const togglingGate = ref<Gate | null>(null);

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
    form.location      = gate.location.is_placeholder ? '' : gate.location.label;
    form.picture       = null;
    picturePreview.value = gate.picture_url;
    editOpen.value     = true;
}

function closeEdit() {
    editOpen.value     = false;
    selectedGate.value = null;
    resetGateForm();
}

function openPreview(gate: Gate) {
    previewedGate.value = gate;
}

function openToggleDialog(gate: Gate) {
    togglingGate.value = gate;
    toggleOpen.value = true;
}

function confirmToggle() {
    if (!togglingGate.value) return;

    router.patch(`/gates/${togglingGate.value.id}/toggle-status`, {}, {
        preserveScroll: true,
        onFinish: () => {
            togglingGate.value = null;
            toggleOpen.value = false;
        },
    });
}

const createGate = () => {
    form.transform((data) => data);
    form.post(store().url, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            createOpen.value = false;
            resetGateForm();
        },
    });
};

const editGate = () => {
    if (!selectedGate.value) return;
    form.transform((data) => ({ ...data, _method: 'put' })).post(update(selectedGate.value.id).url, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => closeEdit(),
        onFinish: () => form.transform((data) => data),
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
                        <!-- TODO: move this in the app topbar -->
                        <!-- CODE: <div class="ml-2 flex flex-1 items-center">
                            <hr class="h-px w-full border border-custom-primary" />
                            cant decide which one looks better -->
                            <!-- CODE: <div class="border-12 border-custom-primary">
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
                                        <button
                                            type="button"
                                            class="flex items-center"
                                            @click="createOpen = true"
                                        >
                                            <RiAddLine class="h-4 w-4 hover:text-custom-bg-light" />
                                            Add Gate
                                        </button>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        as-child
                                        class="cursor-pointer group"
                                    >
                                        <Link :href="trash().url" class="flex items-center">
                                            <RiArchive2Line class="h-4 w-4 text-custom-shadow group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow transition-all duration-300" />
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
                                placeholder="Search gates..."
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
                                        <div class="flex flex-col gap-y-1">
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

                                        <div class="flex flex-col gap-y-1">
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
                                    <!-- CODE: <TableHead class="uppercase tracking-widest">Created By</TableHead> -->
                                    <div class="col-span-1 flex h-10 font-semibold items-center justify-end px-0 pr-3 text-left text-xs uppercase tracking-widest text-custom-shadow/80">Actions</div>
                                </div>
                                <!-- CODE: <TableRow class="border-b-0 hover:bg-transparent">
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

                                    <!-- CODE: <TableCell class="text-sm text-muted-foreground">{{ gate.creator?.name ?? '—' }}</TableCell> -->

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

                                            <DropdownMenuContent align="end" class="">
                                                <DropdownMenuLabel>
                                                    {{ gate.gate_name }}
                                                </DropdownMenuLabel>

                                                <DropdownMenuItem
                                                    class="group hidden"
                                                    @click="openPreview(gate)"
                                                >
                                                    <RiExternalLinkLine class="h-4 w-4 text-custom-shadow group-hover:text-custom-bg-light dark:group-hover:text-custom-bg transition-all duration-300" />
                                                    View
                                                </DropdownMenuItem>

                                                <DropdownMenuItem as-child class="group lg:hidden">
                                                    <Link :href="show(gate.id).url" class="flex items-center">
                                                        <RiExternalLinkLine class="h-4 w-4 text-custom-shadow group-hover:text-custom-bg-light dark:group-hover:text-custom-bg transition-all duration-300" />
                                                        View
                                                    </Link>
                                                </DropdownMenuItem>

                                                <DropdownMenuItem class="group" @click="openEdit(gate)">
                                                    <RiEditLine class="h-4 w-4 text-custom-shadow group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow transition-all duration-300" />
                                                    Edit
                                                </DropdownMenuItem>

                                                <DropdownMenuItem class="group" @click="openToggleDialog(gate)">
                                                    <RiShutDownLine class="h-4 w-4 text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                                                    <span class="text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow">
                                                        {{ gate.status === 'active' ? 'Set as Inactive' : 'Set as Active' }}
                                                    </span>
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </div>

                                <!-- CODE: <TableRow
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

                        <!-- CODE: <div class="flex items-center justify-between gap-3 border-b border-custom-bg-dark pb-3">
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

                            <!-- CODE: <div
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

                    <!-- CODE: <hr class="border-custom-bg-dark dark:border-custom-bg-light my-4"> -->
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
                        <!-- CODE: <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-custom-bg text-custom-shadow/70">
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

        
        <Dialog v-model:open="createOpen">
            <DialogContent class="">
                <DialogHeader>
                    <DialogTitle>Add Gate</DialogTitle>
                    <!-- CODE: <DialogDescription>Provide the details below.</DialogDescription> -->
                </DialogHeader>
                <form class="flex flex-col gap-y-2 px-6" @submit.prevent="createGate">
                    <div class="relative space-y-1">
                        <Label for="create_gate_name">Gate Name</Label>
                        <Input
                            id="create_gate_name"
                            v-model="form.gate_name"
                            placeholder="Select or type a gate name"
                            autocomplete="off"
                            @focus="createGateSuggestionsOpen = true"
                            @blur="createGateSuggestionsOpen = false"
                        />
                        <div
                            v-if="createGateSuggestionsOpen && filteredGateSuggestions.length"
                            class="absolute z-50 mt-1 max-h-60 w-full overflow-y-auto rounded-md border border-custom-bg-dark bg-custom-bg-light p-1 text-custom-shadow shadow-md [scrollbar-width:none] [&::-webkit-scrollbar]:hidden dark:border-custom-bg-light dark:bg-custom-bg dark:shadow-none dark:inset-shadow-sm dark:inset-shadow-white/5"
                        >
                            <button
                                v-for="opt in filteredGateSuggestions"
                                :key="opt"
                                type="button"
                                class="relative flex w-full cursor-default select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-custom-bg dark:hover:bg-custom-bg-light"
                                @mousedown.prevent="form.gate_name = opt; createGateSuggestionsOpen = false"
                            >
                                {{ opt }}
                            </button>
                        </div>
                        <InputError :message="form.errors.gate_name" />
                    </div>
                    <div class="space-y-1">
                        <Label for="create_status">Status</Label>
                        <Select v-model="form.status">
                            <SelectTrigger id="create_status" class="w-full">
                                <SelectValue placeholder="Select status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="active">Active</SelectItem>
                                <SelectItem value="inactive">Inactive</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.status" />
                    </div>
                    <div class="relative space-y-1">
                        <Label for="create_bays">Bays</Label>
                        <Input
                            id="create_bays"
                            v-model="form.bays"
                            inputmode="numeric"
                            placeholder="Number of bays"
                            autocomplete="off"
                            @focus="createBaySuggestionsOpen = true"
                            @blur="createBaySuggestionsOpen = false"
                        />
                        <div
                            v-if="createBaySuggestionsOpen && filteredBaySuggestions.length"
                            class="absolute z-50 mt-1 max-h-60 w-full overflow-y-auto rounded-md border border-custom-bg-dark bg-custom-bg-light p-1 text-custom-shadow shadow-md [scrollbar-width:none] [&::-webkit-scrollbar]:hidden dark:border-custom-bg-light dark:bg-custom-bg dark:shadow-none dark:inset-shadow-sm dark:inset-shadow-white/5"
                        >
                            <button
                                v-for="opt in filteredBaySuggestions"
                                :key="opt"
                                type="button"
                                class="relative flex w-full cursor-default select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-custom-bg dark:hover:bg-custom-bg-light"
                                @mousedown.prevent="form.bays = opt; createBaySuggestionsOpen = false"
                            >
                                    {{ opt }}
                            </button>
                        </div>
                        <InputError :message="form.errors.bays" />
                    </div>
                    <div class="space-y-1">
                        <Label for="create_location">Location</Label>
                        <Input id="create_location" v-model="form.location" placeholder="e.g. Ground Floor boarding concourse" />
                        <InputError :message="form.errors.location" />
                    </div>
                    <div class="space-y-1">
                        <Label for="create_picture">Gate Picture</Label>
                        <div class="flex items-center gap-3 rounded-md border border-dashed border-custom-bg-dark p-3 dark:border-none dark:bg-custom-bg-dark dark:shadow-sm dark:shadow-white/5">
                            <div role="button" tabindex="0" :aria-label="picturePreview ? 'Change gate picture' : 'Upload gate picture'" class="group relative h-24 w-24 shrink-0 cursor-pointer overflow-hidden rounded-md border transition-colors" :class="picturePreview ? 'border-none' : 'border-dashed border-custom-bg-dark dark:border-custom-bg-light'" @click="pictureInputRef?.click()" @keydown.enter.prevent="pictureInputRef?.click()" @keydown.space.prevent="pictureInputRef?.click()">
                                <img v-if="picturePreview" :src="picturePreview" alt="Gate picture preview" class="h-full w-full object-cover transition duration-200 group-hover:brightness-30" />
                                <div v-if="picturePreview" class="pointer-events-none absolute inset-0 flex items-center justify-center opacity-0 transition-opacity duration-200 group-hover:opacity-100 group-focus-visible:opacity-100"><RiImageAddLine class="h-7 w-7 text-custom-shadow" /></div>
                                <div v-else class="flex h-full w-full items-center justify-center"><RiImageAddLine class="h-6 w-6 text-custom-shadow/80" /></div>
                                <Button v-if="picturePreview" type="button" aria-label="Remove gate picture" class="absolute right-1 top-1 z-10 flex h-6 w-6 cursor-pointer items-center rounded-full border border-custom-shadow/50 text-custom-shadow transition-all duration-300 hover:border-destructive hover:bg-destructive/20 hover:text-destructive" @click.stop="removePicture"><RiCloseLine class="h-4 w-4" /></Button>
                            </div>
                            <div class="space-y-1">
                                <p class="text-sm text-custom-shadow/80"><span class="font-semibold">File format: </span>.jpg, .png or .webp<br /><span class="font-semibold">Max. file size: </span>2 MB<br /><span class="font-semibold">Recommended: </span>landscape image</p>
                                <input id="create_picture" ref="pictureInputRef" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="selectPicture" />
                            </div>
                        </div>
                        <InputError :message="form.errors.picture" />
                    </div>

                    <Separator/>
                    <DialogFooter class="gap-2">
                        <Button variant="ghost-outline" @click="createOpen = false">Cancel</Button>
                        <Button type="submit" variant="float-primary" :disabled="form.processing">
                            <RiLoaderLine v-if="form.processing" class="h-4 w-4 animate-spin" />
                            <!-- CODE: <Save v-else class="h-4 w-4" /> -->
                            {{ form.processing ? 'Adding...' : 'Add' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        
        <Dialog v-model:open="editOpen">
            <DialogContent class="">
                <DialogHeader>
                    <DialogTitle>Edit Gate</DialogTitle>
                </DialogHeader>
                <form class="flex flex-col gap-y-2 px-6" @submit.prevent="editGate">
                    <div class="relative space-y-1">
                        <Label for="edit_gate_name">Gate Name</Label>
                        <Input
                            id="edit_gate_name"
                            v-model="form.gate_name"
                            placeholder="Select or type a gate name"
                            autocomplete="off"
                            @focus="editGateSuggestionsOpen = true"
                            @blur="editGateSuggestionsOpen = false"
                        />
                        <div
                            v-if="editGateSuggestionsOpen && filteredGateSuggestions.length"
                            class="absolute z-50 mt-1 max-h-60 w-full overflow-y-auto rounded-md border border-custom-bg-dark bg-custom-bg-light p-1 text-custom-shadow shadow-md [scrollbar-width:none] [&::-webkit-scrollbar]:hidden dark:border-custom-bg-light dark:bg-custom-bg dark:shadow-none dark:inset-shadow-sm dark:inset-shadow-white/5"
                        >
                            <button
                                v-for="opt in filteredGateSuggestions"
                                :key="opt"
                                type="button"
                                class="relative flex w-full cursor-default select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-custom-bg dark:hover:bg-custom-bg-light"
                                @mousedown.prevent="form.gate_name = opt; editGateSuggestionsOpen = false"
                            >
                                {{ opt }}
                            </button>
                        </div>
                        <InputError :message="form.errors.gate_name" />
                    </div>
                    <div class="space-y-1">
                        <Label for="edit_status">Status</Label>
                        <Select v-model="form.status">
                            <SelectTrigger id="edit_status" class="w-full">
                                <SelectValue placeholder="Select status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="active">Active</SelectItem>
                                <SelectItem value="inactive">Inactive</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.status" />
                    </div>
                    <div class="relative space-y-1">
                        <Label for="edit_bays">Bays</Label>
                        <Input
                            id="edit_bays"
                            v-model="form.bays"
                            inputmode="numeric"
                            placeholder="Number of bays"
                            autocomplete="off"
                            @focus="editBaySuggestionsOpen = true"
                            @blur="editBaySuggestionsOpen = false"
                        />
                        <div
                            v-if="editBaySuggestionsOpen && filteredBaySuggestions.length"
                            class="absolute z-50 mt-1 max-h-60 w-full overflow-y-auto rounded-md border border-custom-bg-dark bg-custom-bg-light p-1 text-custom-shadow shadow-md [scrollbar-width:none] [&::-webkit-scrollbar]:hidden dark:border-custom-bg-light dark:bg-custom-bg dark:shadow-none dark:inset-shadow-sm dark:inset-shadow-white/5"
                        >
                            <button
                                v-for="opt in filteredBaySuggestions"
                                :key="opt"
                                type="button"
                                class="relative flex w-full cursor-default select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-custom-bg dark:hover:bg-custom-bg-light"
                                @mousedown.prevent="form.bays = opt; editBaySuggestionsOpen = false"
                            >
                                {{ opt }}
                            </button>
                        </div>
                        <InputError :message="form.errors.bays" />
                    </div>
                    <div class="space-y-1">
                        <Label for="edit_location">Location</Label>
                        <Input id="edit_location" v-model="form.location" placeholder="e.g. Ground Floor boarding concourse" />
                        <InputError :message="form.errors.location" />
                    </div>
                    <div class="space-y-1">
                        <Label for="edit_picture">Gate Picture</Label>
                        <div class="flex items-center gap-3 rounded-md border border-dashed border-custom-bg-dark p-3 dark:border-none dark:bg-custom-bg-dark dark:shadow-sm dark:shadow-white/5">
                            <div role="button" tabindex="0" :aria-label="picturePreview ? 'Change gate picture' : 'Upload gate picture'" class="group relative h-24 w-24 shrink-0 cursor-pointer overflow-hidden rounded-md border transition-colors" :class="picturePreview ? 'border-none' : 'border-dashed border-custom-bg-dark dark:border-custom-bg-light'" @click="pictureInputRef?.click()" @keydown.enter.prevent="pictureInputRef?.click()" @keydown.space.prevent="pictureInputRef?.click()">
                                <img v-if="picturePreview" :src="picturePreview" alt="Gate picture preview" class="h-full w-full object-cover transition duration-200 group-hover:brightness-30" />
                                <div v-if="picturePreview" class="pointer-events-none absolute inset-0 flex items-center justify-center opacity-0 transition-opacity duration-200 group-hover:opacity-100 group-focus-visible:opacity-100"><RiImageAddLine class="h-7 w-7 text-custom-shadow" /></div>
                                <div v-else class="flex h-full w-full items-center justify-center"><RiImageAddLine class="h-6 w-6 text-custom-shadow/80" /></div>
                                <Button v-if="picturePreview" type="button" aria-label="Remove gate picture" class="absolute right-1 top-1 z-10 flex h-6 w-6 cursor-pointer items-center rounded-full border border-custom-shadow/50 text-custom-shadow transition-all duration-300 hover:border-destructive hover:bg-destructive/20 hover:text-destructive" @click.stop="removePicture"><RiCloseLine class="h-4 w-4" /></Button>
                            </div>
                            <div class="space-y-1">
                                <p class="text-sm text-custom-shadow/80"><span class="font-semibold">File format: </span>.jpg, .png or .webp<br /><span class="font-semibold">Max. file size: </span>2 MB<br /><span class="font-semibold">Recommended: </span>landscape image</p>
                                <input id="edit_picture" ref="pictureInputRef" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="selectPicture" />
                            </div>
                        </div>
                        <InputError :message="form.errors.picture" />
                    </div>

                    <Separator/>
                    <DialogFooter class="gap-2">
                        <Button type="button" variant="ghost-outline" @click="closeEdit">Cancel</Button>
                        <Button type="submit" variant="float-primary" :disabled="form.processing">
                            <RiLoaderLine v-if="form.processing" class="h-4 w-4 animate-spin" />
                            <!-- CODE: <Save v-else class="h-4 w-4" /> -->
                            {{ form.processing ? 'Saving...' : 'Save Changes' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="toggleOpen">
            <DialogContent class="px-6">
                <DialogHeader class="px-0">
                    <DialogTitle>
                        Set gate status
                    </DialogTitle>
                    <DialogDescription class="mt-4">
                        Are you sure you want to set
                        <span class="font-semibold text-custom-accent-3">{{ togglingGate?.gate_name ?? 'this gate' }}</span>
                        as {{ togglingGate?.status === 'active' ? 'inactive' : 'active' }}?
                    </DialogDescription>
                </DialogHeader>
                <Separator class="mb-4" />
                <DialogFooter class="gap-2 sm:justify-end">
                    <Button variant="ghost-outline" @click="toggleOpen = false; togglingGate = null">Cancel</Button>
                    <Button :variant="togglingGate?.status === 'active' ? 'destructive' : 'float-primary'" @click="confirmToggle">
                        <RiShutDownLine class="h-4 w-4" />
                        {{ togglingGate?.status === 'active' ? 'Inactivate' : 'Activate' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- CODE: <AlertDialog v-model:open="archiveOpen">
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
