<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';

import {
    Table,
    TableColumn,
    TableHeader,
    TableContent,
    TableRow,
    TableCard,
    TableData,
    TableMoreButton,
} from '@/components/ui/_table';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

import {
    ArchiveGateDialog,
    CreateGateDialog,
    ToggleGateStatusDialog,
} from '@/components/internal/gate';

import AppLayout from '@/layouts/AppLayout.vue';
import { edit, index, trash } from '@/routes/gates';
import { type BreadcrumbItem, type User } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { PanelLayout, MainPanel, SidePanel } from '@/components/ui/_panels';
import { GatePreviewCard } from '@/components/internal/preview-cards';

import {
    RiArchive2Line,
    RiFilter2Line,
    RiMore2Line,
    RiEditLine,
    RiAddLine,
    RiShutDownLine,
    RiExternalLinkLine,
} from 'vue-remix-icons';

import { computed, onMounted, onUnmounted, ref } from 'vue';

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
            links: { url: string | null; label: string; active: boolean }[];
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

function currentFilterParams(): Record<string, string | undefined> {
    return {
        status: filterStatus.value === 'all' ? undefined : filterStatus.value,
        bays: filterBays.value || undefined,
    };
}

function applyFilters() {
    router.get(
        index().url,
        {
            search: props.filters?.search || undefined,
            ...currentFilterParams(),
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

const createOpen   = ref(false);
const previewedGate = ref<Gate | null>(null);
const toggleOpen = ref(false);
const togglingGate = ref<Gate | null>(null);
const archiveOpen = ref(false);
const archivingGate = ref<Gate | null>(null);
const openMenus = ref<Record<number, boolean>>({});

function statusClass(status: Gate['status']): string {
    return status === 'active'
        ? 'bg-emerald-100 text-emerald-700 border-emerald-200'
        : 'bg-slate-100 text-slate-500 border-0';
}

function statusDot(status: Gate['status']): string {
    return status === 'active' ? 'bg-emerald-500' : 'bg-slate-400';
}

function openPreview(gate: Gate) {
    previewedGate.value = gate;
}

function selectAdjacentGate(direction: 1 | -1) {
    if (!previewedGate.value) return;

    const list = props.gates.data;
    const currentIndex = list.findIndex((g) => g.id === previewedGate.value?.id);
    const nextIndex = currentIndex + direction;
    if (currentIndex === -1 || nextIndex < 0 || nextIndex >= list.length) return;

    openPreview(list[nextIndex]);
}

function handleRowNavigationKeydown(event: KeyboardEvent) {
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        selectAdjacentGate(1);
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        selectAdjacentGate(-1);
    }
}

onMounted(() => window.addEventListener('keydown', handleRowNavigationKeydown));
onUnmounted(() => window.removeEventListener('keydown', handleRowNavigationKeydown));

function openToggleDialog(gate: Gate) {
    togglingGate.value = gate;
    toggleOpen.value = true;
}

function openArchiveDialog(gate: Gate) {
    archivingGate.value = gate;
    archiveOpen.value = true;
}

</script>

<template>
    <Head title="Gates" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <PanelLayout>
            <MainPanel>
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
                                                <RiArchive2Line class="h-4 w-4 text-custom-shadow group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow transition-all duration-200" />
                                                Archives
                                            </Link>
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
                                    :route="index().url"
                                    :initial-value="props.filters?.search"
                                    placeholder="Search gates..."
                                    :only="['gates', 'filters']"
                                    :debounce="350"
                                    :extra-params="currentFilterParams"
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
                                                    ? ' bg-custom-secondary/20 hover:text-custom-bg-light hover:bg-custom-secondary/80 transition-all duration-200'
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
                                                        @click="applyFilters()"
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

                        <TableCard :table-data-length="props.gates.data.length">
                            <Table v-if="props.gates.data.length > 0">
                                <TableHeader>
                                    <TableColumn>Name</TableColumn>
                                    <TableColumn>Status</TableColumn>
                                    <TableColumn>Bays</TableColumn>
                                </TableHeader>

                                <TableContent>
                                    <TableRow
                                        v-for="(gate, rowIndex) in props.gates.data"
                                        :key="gate.id"
                                        :class="[
                                            rowIndex === props.gates.data.length - 1 ? 'rounded-b-md border-b-0' : '',
                                            previewedGate?.id === gate.id ? 'bg-custom-secondary/10' : '',
                                        ]"
                                        :status="gate.status === 'inactive' ? 'inactive' : 'default'"
                                        @click.left="openPreview(gate)"
                                        @dblclick="router.visit(edit(gate.id).url)"
                                    >
                                        <TableData class="pl-3 font-semibold capitalize">
                                            {{ gate.gate_name }}
                                        </TableData>

                                        <TableData>
                                            <Badge :class="['gap-1.5', statusClass(gate.status)]">
                                                <span :class="['h-1.5 w-1.5 rounded-full', statusDot(gate.status)]" />
                                                {{ gate.status === 'active' ? 'Active' : 'Inactive' }}
                                            </Badge>
                                        </TableData>

                                        <TableData>
                                            <span class="tabular-nums">{{ gate.bays }}</span>
                                        </TableData>

                                        <TableMoreButton
                                            :open="openMenus[gate.id] ?? false"
                                            @update:open="(value) => (openMenus[gate.id] = value)"
                                        >
                                            <DropdownMenuLabel>
                                                {{ gate.gate_name }}
                                            </DropdownMenuLabel>

                                            <DropdownMenuItem
                                                class="group hidden"
                                                @click="openPreview(gate)"
                                            >
                                                <RiExternalLinkLine class="h-4 w-4 text-custom-shadow group-hover:text-custom-bg-light dark:group-hover:text-custom-bg transition-all duration-200" />
                                                View
                                            </DropdownMenuItem>

                                            <DropdownMenuItem as-child class="group">
                                                <Link :href="edit(gate.id).url" class="flex items-center">
                                                    <RiEditLine class="h-4 w-4 text-custom-shadow group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow transition-all duration-200" />
                                                    Edit
                                                </Link>
                                            </DropdownMenuItem>

                                            <DropdownMenuItem class="group" @click="openToggleDialog(gate)">
                                                <RiShutDownLine class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                                                <span class="text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow">
                                                    {{ gate.status === 'active' ? 'Inactivate' : 'Activate' }}
                                                </span>
                                            </DropdownMenuItem>

                                            <DropdownMenuItem class="group" @click="openArchiveDialog(gate)">
                                                <RiArchive2Line class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                                                <span class="text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow">Archive</span>
                                            </DropdownMenuItem>
                                        </TableMoreButton>
                                    </TableRow>
                                </TableContent>
                            </Table>

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
                        </TableCard>

                        <InertiaPagination
                            :links="props.gates.links"
                            :meta="{ from: props.gates.from, to: props.gates.to, total: props.gates.total }"
                        />
                    </CardContent>
                </Card>
            </MainPanel>

            <SidePanel v-if="previewedGate" class="hidden lg:flex">
                <GatePreviewCard :gate="previewedGate" @close="previewedGate = null" />
            </SidePanel>
            

            
        </PanelLayout>

        <CreateGateDialog v-model:open="createOpen" />
        <ToggleGateStatusDialog v-model:open="toggleOpen" :gate="togglingGate" />
        <ArchiveGateDialog v-model:open="archiveOpen" :gate="archivingGate" />
    </AppLayout>
</template>
