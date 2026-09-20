<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';

import {
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    DropdownMenuItem,
    DropdownMenuLabel,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
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
import SearchInput from '@/components/SearchInput.vue';
import { MainPanel, PanelLayout, SidePanel } from '@/components/ui/_panels';
import { RestoreGateDialog } from '@/components/internal/gate';
import { GatePreviewCard } from '@/components/internal/preview-cards';
import { Table, TableCard, TableColumn, TableContent, TableData, TableHeader, TableMoreButton, TableRow } from '@/components/ui/_table';

import AppLayout from '@/layouts/AppLayout.vue';
import { edit, index, trash } from '@/routes/gates';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

import { RiArrowLeftLine, RiFilter2Line, RiRestartLine } from 'vue-remix-icons';

import { computed, ref, watch } from 'vue';


interface Gate {
    id: number;
    gate_name: string;
    deleted_at_human: string | null;
}

interface PaginatedGates {
    data: Gate[];
    links: any[];
    from: number | null;
    to: number | null;
    total: number;
}


const props = defineProps<{
    gates: PaginatedGates;
    filters: {
        search: string | null;
        status: string | null;
        bays: string | null;
    };
}>();

const filterStatus = ref(props.filters.status ?? 'all');
const filterBays = ref(props.filters.bays ?? '');
const filterOpen = ref(false);

const activeFilterCount = computed(() => {
    let count = 0;
    if (filterStatus.value !== 'all') count++;
    if (filterBays.value) count++;
    return count;
});

function applyFilters() {
    router.get(
        trash().url,
        {
            search: props.filters.search || undefined,
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
        trash().url,
        { search: props.filters.search || undefined },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['gates', 'filters'],
        },
    );
    filterOpen.value = false;
}


const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Gates', href: index().url },
    { title: 'Trash', href: '#' },
];


const restoreOpen = ref(false);
const previewedGate = ref<Gate | null>(null);
const openMenuId = ref<number | null>(null);

// Drop the preview once its row leaves the list (e.g. after restoring it).
watch(() => props.gates.data, (rows) => {
    if (previewedGate.value && !rows.some((row) => row.id === previewedGate.value?.id)) {
        previewedGate.value = null;
    }
});

const selectedGate = ref<Gate | null>(null);

function openRestoreDialog(gate: Gate) {
    selectedGate.value = gate;
    restoreOpen.value = true;
}


</script>

<template>
    <Head title="Archived Gates" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <PanelLayout>
            <MainPanel>
                <Card class="min-h-0 min-w-0 flex-1 lg:h-full">
                    <CardHeader class="flex flex-row items-start gap-3">
                        <Button as-child variant="header-actions" size="icon">
                            <Link :href="index().url" aria-label="Back to gates">
                                <RiArrowLeftLine class="h-4 w-4" />
                            </Link>
                        </Button>
                        <div class="flex min-w-0 flex-col">
                            <CardTitle class="font-semibold">Archived Gates</CardTitle>
                            <CardDescription>Restore archived gates to the active gates list.</CardDescription>
                        </div>
                    </CardHeader>

                    <CardContent class="flex min-h-0 flex-1 flex-col space-y-4 py-2">
                        <div class="flex flex-row gap-2 lg:items-center lg:justify-between">
                            <div class="w-full">
                                <SearchInput
                                    :route="`${trash().url}?status=${filterStatus === 'all' ? '' : filterStatus}&bays=${filterBays}`"
                                    :initial-value="filters.search"
                                    placeholder="Search archived gates..."
                                    :only="['gates', 'filters', 'flash']"
                                    :debounce="350"
                                />
                            </div>

                            <Popover v-model:open="filterOpen">
                                <PopoverTrigger as-child>
                                    <Button
                                        variant="header-actions"
                                        size="icon-text"
                                        class="rounded-full"
                                        :class="activeFilterCount > 0 ? 'bg-custom-secondary/20 transition-all duration-200 hover:bg-custom-secondary/80 hover:text-custom-bg-light' : ''"
                                    >
                                        <RiFilter2Line class="h-3.5 w-3.5" />
                                        <span class="hidden lg:flex">
                                            {{ activeFilterCount > 0
                                                ? (activeFilterCount === 1 ? '1 filter active' : `${activeFilterCount} filters active`)
                                                : 'Filter' }}
                                        </span>
                                    </Button>
                                </PopoverTrigger>

                                <PopoverContent align="end">
                                    <div class="grid gap-y-2">
                                        <div class="flex flex-col gap-y-1">
                                            <p class="text-sm text-custom-shadow/80">Status</p>
                                            <Select v-model="filterStatus">
                                                <SelectTrigger class="w-full">
                                                    <SelectValue placeholder="Any status" class="flex justify-start" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="all">Any status</SelectItem>
                                                    <SelectItem value="active">Active</SelectItem>
                                                    <SelectItem value="inactive">Inactive</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div class="flex flex-col gap-y-1">
                                            <p class="text-sm text-custom-shadow/80">No. of Bays</p>
                                            <Input
                                                v-model="filterBays"
                                                type="number"
                                                min="0"
                                                placeholder="e.g. 5"
                                                class="bg-custom-bg"
                                            />
                                        </div>

                                        <hr class="my-1 h-px border-0 bg-custom-bg-dark dark:bg-custom-bg-light" />

                                        <div class="flex w-full flex-row items-center justify-between">
                                            <Button
                                                v-if="activeFilterCount > 0"
                                                size="sm"
                                                variant="destructive"
                                                @click="clearFilters"
                                            >
                                                Clear
                                            </Button>
                                            <div class="ml-auto flex items-center gap-2">
                                                <Button variant="ghost-outline" size="sm" @click="filterOpen = false">
                                                    Cancel
                                                </Button>
                                                <Button size="sm" variant="float-primary" @click="applyFilters">
                                                    Apply
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                </PopoverContent>
                            </Popover>
                        </div>

                        <TableCard :table-data-length="props.gates.data.length">
                            <Table v-if="props.gates.data.length > 0">
                                <TableHeader>
                                    <TableColumn>Name</TableColumn>
                                    <TableColumn>Archived At</TableColumn>
                                </TableHeader>

                                <TableContent>
                                    <TableRow
                                        v-for="(gate, rowIndex) in props.gates.data"
                                        :key="gate.id"
                                        :class="[
                                            rowIndex === props.gates.data.length - 1 ? 'rounded-b-md border-b-0' : '',
                                            previewedGate?.id === gate.id ? 'bg-custom-secondary/10' : '',
                                        ]"
                                        @click.left="previewedGate = gate"
                                        @dblclick="router.visit(edit(gate.id).url)"
                                    >
                                        <TableData class="font-semibold capitalize">{{ gate.gate_name }}</TableData>
                                        <TableData>{{ gate.deleted_at_human ?? '—' }}</TableData>

                                        <TableMoreButton
                                            :open="openMenuId === gate.id"
                                            @update:open="(value) => (openMenuId = value ? gate.id : null)"
                                        >
                                            <DropdownMenuLabel>{{ gate.gate_name }}</DropdownMenuLabel>
                                            <DropdownMenuItem class="group cursor-pointer" @click="openRestoreDialog(gate)">
                                                <RiRestartLine class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg" />
                                                Restore
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
                                        <p class="text-base font-semibold text-custom-shadow">No archived gates found</p>
                                        <p class="text-sm text-custom-shadow/80">
                                            {{ filters.search || activeFilterCount > 0 ? 'Try adjusting your search or filters.' : 'Nothing has been archived yet.' }}
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
                <GatePreviewCard :gate="previewedGate" archived @close="previewedGate = null" />
            </SidePanel>
        </PanelLayout>

        
        <RestoreGateDialog v-model:open="restoreOpen" :gate="selectedGate" />
    </AppLayout>
</template>
