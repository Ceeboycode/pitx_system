<script setup lang="ts">
import InertiaPagination from '@/components/InertiaPagination.vue';
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';

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

import AppLayout from '@/layouts/AppLayout.vue';
import { index, restore, trash } from '@/routes/gates';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

import { RiArrowLeftSLine, RiFilter2Line, RiMore2Line, RiRestartLine } from 'vue-remix-icons';

import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';


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
const selectedGate = ref<Gate | null>(null);

function openRestoreDialog(gate: Gate) {
    selectedGate.value = gate;
    restoreOpen.value = true;
}

function closeRestoreDialog() {
    restoreOpen.value = false;
    selectedGate.value = null;
}


function restoreGate() {
    if (!selectedGate.value) return;
    router.post(
        restore(selectedGate.value.id).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => closeRestoreDialog(),
            onError: () => toast.error('Failed to restore gate.'),
        },
    );
}
</script>

<template>
    <Head title="Archived Gates" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full min-h-0 w-full flex-1 flex-col gap-4">
            <Card class="min-h-0 min-w-0 flex-1 lg:h-full">
                <CardHeader class="flex flex-row items-start gap-3">
                    <Button as-child variant="header-actions" size="icon">
                        <Link :href="index().url" aria-label="Back to gates">
                            <RiArrowLeftSLine class="h-4 w-4" />
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
                                    :class="activeFilterCount > 0 ? 'bg-custom-secondary/20 transition-all duration-300 hover:bg-custom-secondary/80 hover:text-custom-bg-light' : ''"
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

                    <Card
                        :class="[
                            'flex min-h-0 max-h-fit flex-1 flex-col overflow-hidden border border-custom-bg-dark py-0 shadow-none dark:border-custom-bg-light dark:inset-shadow-none',
                            props.gates.data.length === 0 ? 'border-dashed' : 'border-solid',
                        ]"
                    >
                        <div v-if="props.gates.data.length > 0" class="flex min-h-0 flex-1 flex-col overflow-hidden">
                            <div class="shrink-0 rounded-t-md bg-custom-bg dark:bg-custom-bg-light">
                                <div class="grid grid-cols-3 gap-2 border-b border-custom-bg-dark dark:border-custom-bg-light">
                                    <div class="col-span-1 flex h-10 items-center justify-start pl-3 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Name</div>
                                    <div class="col-span-1 flex h-10 items-center justify-start text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Archived At</div>
                                    <div class="col-span-1 flex h-10 items-center justify-end pr-3 text-left text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Actions</div>
                                </div>
                            </div>

                            <div class="no-scrollbar min-h-0 flex-1 overflow-y-auto">
                                <div
                                    v-for="(gate, rowIndex) in props.gates.data"
                                    :key="gate.id"
                                    :class="[
                                        'grid grid-cols-3 items-center border-b border-custom-bg-dark text-custom-shadow/80 transition-colors hover:bg-custom-secondary/10 hover:text-custom-shadow dark:border-custom-bg-light',
                                        rowIndex === props.gates.data.length - 1 ? 'rounded-b-md border-b-0' : '',
                                    ]"
                                >
                                    <div class="col-span-1 flex justify-start py-1.5 pl-3 font-semibold capitalize">
                                        {{ gate.gate_name }}
                                    </div>
                                    <div class="col-span-1 flex justify-start py-1.5 text-sm">
                                        {{ gate.deleted_at_human ?? '—' }}
                                    </div>
                                    <div class="col-span-1 flex justify-end py-1.5 pr-3 text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button variant="table-more" size="icon-more">
                                                    <RiMore2Line class="h-4 w-4" />
                                                    
                                                </Button>
                                            </DropdownMenuTrigger>

                                            <DropdownMenuContent align="end" class="">
                                                <DropdownMenuLabel>{{ gate.gate_name }}</DropdownMenuLabel>
                                                <DropdownMenuItem class="group" @click="openRestoreDialog(gate)">
                                                    <RiRestartLine class="h-4 w-4 text-custom-shadow group-hover:text-custom-bg-light dark:group-hover:text-custom-bg" />
                                                    Restore Gate
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </div>
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
                                    <p class="text-base font-semibold text-custom-shadow">No archived gates found</p>
                                    <p class="text-sm text-custom-shadow/80">
                                        {{ filters.search || activeFilterCount > 0 ? 'Try adjusting your search or filters.' : 'Nothing has been archived yet.' }}
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
        </div>

        
        <AlertDialog v-model:open="restoreOpen">
            <AlertDialogContent class="rounded-lg p-4">
                <AlertDialogHeader>
                    <AlertDialogTitle>Restore Gate</AlertDialogTitle>
                    <AlertDialogDescription>
                        Are you sure you want to restore
                        <span class="font-semibold text-foreground">{{
                            selectedGate?.gate_name ?? 'this gate'
                        }}</span
                        >? It will be moved back to the active gates list.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel
                        class="rounded-lg cursor-pointer hover:bg-slate-100"
                        @click="closeRestoreDialog"
                        >Cancel</AlertDialogCancel
                    >
                    <AlertDialogAction
                        class="rounded-lg border-0 text-white cursor-pointer bg-primary hover:bg-primary/90"
                        @click="restoreGate"
                    >
                        <RiRestartLine class="h-4 w-4" />
                        Restore Gate
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
