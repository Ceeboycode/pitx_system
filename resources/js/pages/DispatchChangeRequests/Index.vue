<script setup lang="ts">
import {
    approve,
    index as changeRequestsIndex,
    reject,
} from '@/actions/App/Http/Controllers/DispatchChangeRequestController';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import emptyRafikiUrl from '@/components/assets/Empty-rafiki.svg';
import AppLayout from '@/layouts/AppLayout.vue';

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
} from '@/components/ui/dialog';
import {
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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

import {
    RiArrowLeftLine,
    RiCheckLine,
    RiCloseCircleLine,
    RiEyeLine,
    RiFilter2Line,
    RiTimeLine,
} from 'vue-remix-icons';
import { PanelLayout, MainPanel, SidePanel } from '@/components/ui/_panels';
import { DispatchChangeRequestPreviewCard } from '@/components/internal/preview-cards';
import {
    ApproveDispatchChangeRequestDialog,
    RejectDispatchChangeRequestDialog,
} from '@/components/internal/dispatchChangeRequests';

type PaginationLink = { url: string | null; label: string; active: boolean };

type DispatchChangeRequest = {
    id: number;
    dispatch_id: number;
    requested_by: {
        id: number;
        name: string;
        email: string | null;
    };
    company_name: string;
    company_code: string;
    requested_field: string;
    old_value: unknown | null;
    old_value_display?: string | null;
    requested_value: unknown;
    requested_value_display?: string | null;
    reason: string;
    status: string;
    rejected_by?: { id: number; name: string } | null;
    rejection_reason?: string | null;
    approved_at?: string | null;
    created_at?: string | null;
    field_label?: string | null;
    dispatch?: {
        id: number;
        plate_number: string;
        status: string;
        driver?: { id: number; name: string } | null;
        gate?: { id: number; gate_name: string } | null;
        bay_number?: string | number | null;
    } | null;
};

import { type BreadcrumbItem } from '@/types';

import { index } from '@/routes/dispatches';

const props = defineProps<{
    changeRequests: {
        data: DispatchChangeRequest[];
        links: PaginationLink[];
        from: number | null;
        to: number | null;
        total: number;
    };
    statusCounts: { pending: number; approved: number; rejected: number };
    filters: { status: string; search: string | null };
}>();

const selectedStatus = ref<'all' | 'pending' | 'approved' | 'rejected'>(
    (props.filters.status as 'all' | 'pending' | 'approved' | 'rejected') ??
        'pending',
);
const filterOpen = ref(false);
const activeFilterCount = computed(() => selectedStatus.value === 'all' ? 0 : 1);
const approveModalOpen = ref(false);
const approveTarget = ref<DispatchChangeRequest | null>(null);
const rejectModalOpen = ref(false);
const selectedRequest = ref<DispatchChangeRequest | null>(null);
const previewedRequest = ref<DispatchChangeRequest | null>(null);
const openMenuId = ref<number | null>(null);
const approvingId = ref<number | null>(null);
const rejectingId = ref<number | null>(null);

const rejectForm = useForm({
    rejection_reason: '',
});

function applyFilters() {
    router.get(
        changeRequestsIndex().url,
        {
            search: props.filters.search || undefined,
            status: selectedStatus.value === 'all' ? undefined : selectedStatus.value,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['changeRequests', 'statusCounts', 'filters'],
        },
    );
    filterOpen.value = false;
}

function clearFilters() {
    selectedStatus.value = 'all';
    applyFilters();
}

// const breadcrumbs: BreadcrumbItem[] = [{ title: 'Change Requests', href: '#' }];

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dispatches', href: index().url },
    { title: 'Update Requests', href: changeRequestsIndex().url },
];

function formatFieldLabel(field: string): string {
    return field.replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function formatValue(value: unknown): string {
    if (value === null) return '—';
    if (typeof value === 'boolean') return value ? 'Yes' : 'No';
    if (typeof value === 'object') return JSON.stringify(value);
    return String(value);
}

function openRejectModal(request: DispatchChangeRequest) {
    selectedRequest.value = request;
    rejectForm.reset();
    rejectModalOpen.value = true;
}

function closeRejectModal() {
    rejectModalOpen.value = false;
    selectedRequest.value = null;
    rejectForm.reset();
}

function openApproveModal(request: DispatchChangeRequest) {
    approveTarget.value = request;
    approveModalOpen.value = true;
}

function closeApproveModal() {
    approveModalOpen.value = false;
    approveTarget.value = null;
}

function approveSelectedRequest() {
    if (!approveTarget.value) return;

    const request = approveTarget.value;
    approvingId.value = request.id;

    router.post(
        approve(request.id).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                closeApproveModal();
                approvingId.value = null;
            },
            onError: (errors) => {
                console.error('Approval error:', errors);
                approvingId.value = null;
            },
        },
    );
}

function rejectRequest() {
    if (!selectedRequest.value) return;

    rejectingId.value = selectedRequest.value.id;

    rejectForm.post(reject(selectedRequest.value.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            closeRejectModal();
            rejectingId.value = null;
        },
        onError: (errors) => {
            console.error('Rejection error:', errors);
            rejectingId.value = null;
        },
    });
}

function statusVariant(
    status: string,
): 'success' | 'warning' | 'outline' | 'destructive' {
    switch (status) {
        case 'pending':
            return 'warning';
        case 'approved':
            return 'success';
        case 'rejected':
            return 'destructive';
        default:
            return 'outline';
    }
}

function statusIcon(status: string) {
    switch (status) {
        case 'pending':
            return RiTimeLine;
        case 'approved':
            return RiCheckLine;
        case 'rejected':
            return RiCloseCircleLine;
        default:
            return RiEyeLine;
    }
}
</script>

<template>
    <Head title="Update Requests" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <PanelLayout>
            <MainPanel>
                <Card class="min-h-0 min-w-0 flex-1 lg:h-full">
                    <CardHeader class="flex flex-row items-start gap-3">
                        <Button as-child variant="header-actions" size="icon">
                            <Link :href="index().url" aria-label="Back to dispatches">
                                <RiArrowLeftLine class="h-4 w-4" />
                            </Link>
                        </Button>
                        <div class="flex flex-col">
                        <CardTitle class="flex items-center gap-2">
                            <span class="font-semibold">Update Requests</span>
                        </CardTitle>
                        <CardDescription>
                            Review and manage dispatch update requests.
                        </CardDescription>
                        </div>
                    </CardHeader>
                    <CardContent class="flex min-h-0 flex-1 flex-col space-y-4 py-2">
                        <div class="flex flex-row gap-2 lg:items-center lg:justify-between">
                            <div class="w-full">
                                <SearchInput
                                    :route="`${changeRequestsIndex().url}?status=${selectedStatus === 'all' ? '' : selectedStatus}`"
                                    :initial-value="props.filters.search"
                                    placeholder="Search dispatches, companies, or requesters..."
                                    :only="['changeRequests', 'statusCounts', 'filters']"
                                    :debounce="350"
                                />
                            </div>

                            <Popover v-model:open="filterOpen">
                                <PopoverTrigger as-child>
                                    <Button
                                        variant="header-actions"
                                        size="icon-text"
                                        class="rounded-full"
                                        :class="activeFilterCount ? 'bg-custom-secondary/20 transition-all duration-200 hover:bg-custom-secondary/80 hover:text-custom-bg-light' : ''"
                                    >
                                        <RiFilter2Line class="h-3.5 w-3.5" />
                                        <span class="hidden lg:flex">{{ activeFilterCount ? '1 filter active' : 'Filter' }}</span>
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent align="end">
                                    <div class="grid gap-y-2">
                                        <div class="flex flex-col gap-y-1">
                                            <p class="text-sm text-custom-shadow/80">Status</p>
                                            <Select v-model="selectedStatus">
                                                <SelectTrigger class="w-full"><SelectValue placeholder="Any status" /></SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="all">Any status</SelectItem>
                                                    <SelectItem value="pending">Pending</SelectItem>
                                                    <SelectItem value="approved">Approved</SelectItem>
                                                    <SelectItem value="rejected">Rejected</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <hr class="my-1 h-px border-0 bg-custom-bg-dark dark:bg-custom-bg-light" />
                                        <div class="flex items-center justify-between">
                                            <Button v-if="activeFilterCount" size="sm" variant="destructive" @click="clearFilters">Clear</Button>
                                            <div class="ml-auto flex gap-2">
                                                <Button size="sm" variant="ghost-outline" @click="filterOpen = false">Cancel</Button>
                                                <Button size="sm" variant="float-primary" @click="applyFilters">Apply</Button>
                                            </div>
                                        </div>
                                    </div>
                                </PopoverContent>
                            </Popover>
                        </div>
                        <TableCard :table-data-length="changeRequests.data.length">
                            <Table v-if="changeRequests.data.length > 0">
                                <TableHeader>
                                    <TableColumn>Dispatch</TableColumn>
                                    <TableColumn>Requester</TableColumn>
                                    <TableColumn>Company</TableColumn>
                                    <TableColumn>Change</TableColumn>
                                    <TableColumn>Reason</TableColumn>
                                    <TableColumn>Status</TableColumn>
                                </TableHeader>

                                <TableContent>
                                    <TableRow
                                        v-for="(request, rowIndex) in changeRequests.data"
                                        :key="request.id"
                                        :class="[
                                            rowIndex === changeRequests.data.length - 1 ? 'rounded-b-md border-b-0' : '',
                                            previewedRequest?.id === request.id ? 'bg-custom-secondary/10' : '',
                                        ]"
                                        @click.left="previewedRequest = request"
                                    >
                                        <TableData>
                                            <div class="flex min-w-0 flex-col">
                                                <span class="truncate text-sm font-semibold">{{ request.dispatch?.plate_number ?? '—' }}</span>
                                                <span class="truncate text-xs text-custom-shadow/70">{{ request.dispatch?.gate?.gate_name ?? '—' }} · Bay {{ request.dispatch?.bay_number }}</span>
                                            </div>
                                        </TableData>

                                        <TableData>
                                            <div class="flex min-w-0 flex-col">
                                                <span class="truncate text-sm font-semibold">{{ request.requested_by.name }}</span>
                                                <span class="truncate text-xs text-custom-shadow/70">{{ request.requested_by.email ?? '—' }}</span>
                                            </div>
                                        </TableData>

                                        <TableData>
                                            <div class="flex min-w-0 flex-col">
                                                <span class="truncate text-sm font-semibold">{{ request.company_name }}</span>
                                                <span class="truncate text-xs text-custom-shadow/70">{{ request.company_code }}</span>
                                            </div>
                                        </TableData>

                                        <TableData>
                                            <div class="flex min-w-0 flex-col">
                                                <span class="truncate text-sm font-semibold">{{ request.field_label || formatFieldLabel(request.requested_field) }}</span>
                                                <span class="truncate text-xs text-custom-shadow/70">
                                                    {{ request.old_value_display ?? formatValue(request.old_value) }}
                                                    →
                                                    {{ request.requested_value_display ?? formatValue(request.requested_value) }}
                                                </span>
                                            </div>
                                        </TableData>

                                        <TableData class="max-w-xs">
                                            <p class="truncate text-sm" :title="request.reason">{{ request.reason }}</p>
                                        </TableData>

                                        <TableData>
                                            <Badge :variant="statusVariant(request.status)">{{ request.status }}</Badge>
                                        </TableData>

                                        <TableMoreButton
                                            v-if="request.status === 'pending'"
                                            :open="openMenuId === request.id"
                                            @update:open="(value) => (openMenuId = value ? request.id : null)"
                                        >
                                            <DropdownMenuItem
                                                as-child
                                                class="cursor-pointer"
                                                @click="openApproveModal(request)"
                                            >
                                                <div :class="{ 'pointer-events-none opacity-50': approvingId === request.id }">
                                                    <RiCheckLine class="mr-1 h-4 w-4" />
                                                    {{ approvingId === request.id ? 'Approving...' : 'Approve' }}
                                                </div>
                                            </DropdownMenuItem>

                                            <DropdownMenuItem
                                                class="cursor-pointer"
                                                @click="openRejectModal(request)"
                                            >
                                                <RiCloseCircleLine class="mr-1 h-4 w-4" />
                                                Reject
                                            </DropdownMenuItem>
                                        </TableMoreButton>

                                        <TableData
                                            v-else
                                            class="pr-3 text-right text-xs font-semibold"
                                            :class="request.status === 'approved' ? 'text-emerald-600' : 'text-red-600'"
                                        >
                                            {{ request.status === 'approved' ? 'Approved' : 'Rejected' }}
                                        </TableData>
                                    </TableRow>
                                </TableContent>
                            </Table>

                            <div v-else class="flex min-h-0 flex-1 items-center justify-center p-6 text-center">
                                <div class="flex w-full max-w-md flex-col items-center justify-center gap-2">
                                    <img :src="emptyRafikiUrl" alt="" class="w-1/3 object-contain opacity-90" aria-hidden="true" />
                                    <div class="space-y-1">
                                        <p class="text-base font-semibold text-custom-shadow">No change requests found</p>
                                        <p class="text-sm text-custom-shadow/80">{{ selectedStatus === 'pending' ? 'No pending requests to review.' : 'This section is empty.' }}</p>
                                    </div>
                                </div>
                            </div>
                        </TableCard>

                        <InertiaPagination
                            v-if="changeRequests.links?.length"
                            :links="changeRequests.links"
                            :meta="{
                                from: changeRequests.from,
                                to: changeRequests.to,
                                total: changeRequests.total,
                            }"
                        />
                    </CardContent>
                </Card>
            </MainPanel>

            <SidePanel v-if="previewedRequest" class="hidden lg:flex">
                <DispatchChangeRequestPreviewCard :request="previewedRequest" @close="previewedRequest = null" />
            </SidePanel>
        </PanelLayout>
        
        <ApproveDispatchChangeRequestDialog
            v-model:open="approveModalOpen"
            :request="approveTarget"
            :processing="approvingId === approveTarget?.id"
            @confirm="approveSelectedRequest"
        />

        
        <RejectDispatchChangeRequestDialog
            v-model:open="rejectModalOpen"
            v-model:reason="rejectForm.rejection_reason"
            :request="selectedRequest"
            :error="rejectForm.errors.rejection_reason"
            :processing="rejectForm.processing"
            @confirm="rejectRequest"
        />
    </AppLayout>
</template>
