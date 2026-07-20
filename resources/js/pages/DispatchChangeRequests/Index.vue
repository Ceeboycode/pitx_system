<script setup lang="ts">
import {
    approve,
    index as changeRequestsIndex,
    reject,
} from '@/actions/App/Http/Controllers/DispatchChangeRequestController';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import InertiaPagination from '@/components/InertiaPagination.vue';
import InputError from '@/components/InputError.vue';
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
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
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
    RiCheckboxCircleLine as CheckCircle2,
    RiCloseCircleLine as XCircle,
    RiEyeLine as Eye,
    RiFilter2Line as Filter,
    RiMore2Line as MoreHorizontal,
    RiTimeLine as Clock,
} from 'vue-remix-icons';

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

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Change Requests', href: '#' }];

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
            return Clock;
        case 'approved':
            return CheckCircle2;
        case 'rejected':
            return XCircle;
        default:
            return Eye;
    }
}
</script>

<template>
    <Head title="Change Requests" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full min-h-0 w-full flex-1 flex-col gap-4">
            <Card class="min-h-0 min-w-0 flex-1 lg:h-full">
                <CardHeader class="flex flex-row gap-2">
                    <div class="flex flex-col">
                    <CardTitle class="flex items-center gap-2">
                        <span class="font-semibold">Change Requests</span>
                    </CardTitle>
                    <CardDescription>
                        Review and approve or reject change requests from
                        companies.
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
                                    :class="activeFilterCount ? 'bg-custom-secondary/20 transition-all duration-300 hover:bg-custom-secondary/80 hover:text-custom-bg-light' : ''"
                                >
                                    <Filter class="h-3.5 w-3.5" />
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
                    <Card
                        :class="[
                            'flex min-h-0 flex-1 max-h-fit flex-col overflow-hidden border border-custom-bg-dark py-0 shadow-none dark:border-custom-bg-light dark:inset-shadow-none',
                            changeRequests.data.length === 0 ? 'border-dashed' : 'border-solid',
                        ]"
                    >
                    <div class="no-scrollbar min-h-0 flex-1 overflow-auto">
                        <Table>
                            <TableHeader
                                v-if="changeRequests.data.length > 0"
                                class="border-b border-custom-bg-dark bg-custom-bg dark:border-custom-bg-light dark:bg-custom-bg-light"
                            >
                                <TableRow class="gap-2">
                                    <TableHead
                                        class="pl-3 text-xs font-semibold tracking-widest text-custom-shadow/80 uppercase"
                                        >Dispatch</TableHead
                                    >
                                    <TableHead
                                        class="px-0 text-xs font-semibold tracking-widest text-custom-shadow/80 uppercase"
                                        >Requester</TableHead
                                    >
                                    <TableHead
                                        class="px-0 text-xs font-semibold tracking-widest text-custom-shadow/80 uppercase"
                                        >Company</TableHead
                                    >
                                    <TableHead
                                        class="px-0 text-xs font-semibold tracking-widest text-custom-shadow/80 uppercase"
                                        >Change</TableHead
                                    >
                                    <TableHead
                                        class="px-0 text-xs font-semibold tracking-widest text-custom-shadow/80 uppercase"
                                        >Reason</TableHead
                                    >
                                    <TableHead
                                        class="px-0 text-xs font-semibold tracking-widest text-custom-shadow/80 uppercase"
                                        >Status</TableHead
                                    >
                                    <TableHead
                                        class="pr-3 text-right text-xs font-semibold tracking-widest text-custom-shadow/80 uppercase"
                                        >Action</TableHead
                                    >
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <TableRow
                                    v-if="changeRequests.data.length === 0"
                                >
                                    <TableCell
                                        colspan="7"
                                        class="py-20 text-center"
                                    >
                                        <div
                                            class="flex flex-col items-center gap-2 text-custom-shadow/80"
                                        >
                                                <img
                                                    :src="emptyRafikiUrl"
                                                    alt=""
                                                    class="w-32 object-contain opacity-90"
                                                    aria-hidden="true"
                                                />
                                            <p class="text-sm font-medium">
                                                No change requests found
                                            </p>
                                            <p class="text-xs">
                                                {{
                                                    selectedStatus === 'pending'
                                                        ? 'No pending requests to review.'
                                                        : 'This section is empty.'
                                                }}
                                            </p>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <template
                                    v-for="request in changeRequests.data"
                                    :key="request.id"
                                >
                                    <TableRow
                                        class="group border-b border-custom-bg-dark text-custom-shadow/80 transition-colors hover:bg-custom-secondary/10 hover:text-custom-shadow dark:border-custom-bg-light"
                                    >
                                        <TableCell class="pl-3">
                                            <div class="space-y-0.5">
                                                <p
                                                    class="text-sm font-semibold"
                                                >
                                                    {{
                                                        request.dispatch
                                                            ?.plate_number ??
                                                        '—'
                                                    }}
                                                </p>
                                                <p
                                                    class="text-xs text-muted-foreground"
                                                >
                                                    {{
                                                        request.dispatch?.gate
                                                            ?.gate_name ?? '—'
                                                    }}
                                                    · Bay
                                                    {{
                                                        request.dispatch
                                                            ?.bay_number
                                                    }}
                                                </p>
                                            </div>
                                        </TableCell>

                                        <TableCell class="px-0">
                                            <div class="space-y-0.5">
                                                <p class="text-sm font-medium">
                                                    {{
                                                        request.requested_by
                                                            .name
                                                    }}
                                                </p>
                                                <p
                                                    class="text-xs text-muted-foreground"
                                                >
                                                    {{
                                                        request.requested_by
                                                            .email ?? '—'
                                                    }}
                                                </p>
                                            </div>
                                        </TableCell>

                                        <TableCell class="px-0">
                                            <div class="space-y-0.5">
                                                <p class="text-sm font-medium">
                                                    {{ request.company_name }}
                                                </p>
                                                <p
                                                    class="text-xs text-muted-foreground"
                                                >
                                                    {{ request.company_code }}
                                                </p>
                                            </div>
                                        </TableCell>

                                        <TableCell class="px-0">
                                            <div class="space-y-0.5">
                                                <p class="text-sm font-medium">
                                                    {{
                                                        request.field_label ||
                                                        formatFieldLabel(
                                                            request.requested_field,
                                                        )
                                                    }}
                                                </p>
                                                <p
                                                    class="text-xs text-muted-foreground"
                                                >
                                                    {{
                                                        request.old_value_display ??
                                                        formatValue(
                                                            request.old_value,
                                                        )
                                                    }}
                                                    →
                                                    {{
                                                        request.requested_value_display ??
                                                        formatValue(
                                                            request.requested_value,
                                                        )
                                                    }}
                                                </p>
                                            </div>
                                        </TableCell>

                                        <TableCell class="px-0">
                                            <p
                                                class="max-w-xs truncate text-sm"
                                                :title="request.reason"
                                            >
                                                {{ request.reason }}
                                            </p>
                                        </TableCell>

                                        <TableCell class="px-0">
                                            <Badge
                                                :variant="
                                                    statusVariant(
                                                        request.status,
                                                    )
                                                "
                                            >
                                                {{ request.status }}
                                            </Badge>
                                        </TableCell>

                                        <TableCell class="pr-3 text-right">
                                            <DropdownMenu
                                                v-if="
                                                    request.status === 'pending'
                                                "
                                            >
                                                <DropdownMenuTrigger as-child>
                                                    <Button
                                                        variant="table-more"
                                                        size="icon-more"
                                                    >
                                                        <MoreHorizontal
                                                            class="h-4 w-4"
                                                        />
                                                        
                                                    </Button>
                                                </DropdownMenuTrigger>

                                                <DropdownMenuContent align="end" class="">
                                                    <DropdownMenuLabel>Actions</DropdownMenuLabel>

                                                    <DropdownMenuItem
                                                        as-child
                                                        class="cursor-pointer rounded-lg text-emerald-600 focus:text-emerald-600"
                                                        @click="
                                                            openApproveModal(
                                                                request,
                                                            )
                                                        "
                                                    >
                                                        <div
                                                            :class="{
                                                                'pointer-events-none opacity-50':
                                                                    approvingId ===
                                                                    request.id,
                                                            }"
                                                        >
                                                            <CheckCircle2
                                                                class="mr-2 h-4 w-4"
                                                            />
                                                            {{
                                                                approvingId ===
                                                                request.id
                                                                    ? 'Approving...'
                                                                    : 'Approve'
                                                            }}
                                                        </div>
                                                    </DropdownMenuItem>

                                                    <DropdownMenuSeparator />

                                                    <DropdownMenuItem
                                                        class="cursor-pointer rounded-lg text-red-600 focus:text-red-600"
                                                        @click="
                                                            openRejectModal(
                                                                request,
                                                            )
                                                        "
                                                    >
                                                        <XCircle
                                                            class="mr-2 h-4 w-4"
                                                        />
                                                        Reject
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>

                                            <span
                                                v-else
                                                class="text-xs font-medium"
                                                :class="
                                                    request.status ===
                                                    'approved'
                                                        ? 'text-emerald-600'
                                                        : 'text-red-600'
                                                "
                                            >
                                                {{
                                                    request.status ===
                                                    'approved'
                                                        ? 'Approved'
                                                        : 'Rejected'
                                                }}
                                            </span>
                                        </TableCell>
                                    </TableRow>

                                    
                                    <TableRow
                                        v-if="
                                            request.status === 'rejected' &&
                                            request.rejection_reason
                                        "
                                        class="bg-red-50/50 hover:bg-red-50"
                                    >
                                        <TableCell
                                            colspan="6"
                                            class="pr-6 pl-6"
                                        >
                                            <div class="space-y-2 py-3">
                                                <div
                                                    class="flex items-start gap-2"
                                                >
                                                    <XCircle
                                                        class="mt-0.5 h-4 w-4 shrink-0 text-red-600"
                                                    />
                                                    <div
                                                        class="flex-1 space-y-1"
                                                    >
                                                        <p
                                                            class="text-xs font-semibold text-red-900"
                                                        >
                                                            Rejection Reason
                                                        </p>
                                                        <p
                                                            class="text-sm text-red-800"
                                                        >
                                                            {{
                                                                request.rejection_reason
                                                            }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </template>
                            </TableBody>
                        </Table>
                    </div>
                    </Card>

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
        </div>

        
        <Dialog
            :open="approveModalOpen"
            @update:open="
                (v) => {
                    if (!v) closeApproveModal();
                    else approveModalOpen = true;
                }
            "
        >
            <DialogContent class="sm:max-w-sm">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <CheckCircle2 class="h-4 w-4 text-emerald-600" />
                        Approve Change Request
                    </DialogTitle>
                    <DialogDescription v-if="approveTarget">
                        Confirm approval for
                        {{
                            approveTarget.dispatch?.plate_number ??
                            'this dispatch'
                        }}
                        ·
                        {{
                            approveTarget.field_label ||
                            formatFieldLabel(approveTarget.requested_field)
                        }}
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        class="w-full md:w-auto"
                        @click="closeApproveModal"
                        :disabled="approvingId === approveTarget?.id"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        class="w-full md:w-auto"
                        :disabled="approvingId === approveTarget?.id"
                        @click="approveSelectedRequest"
                    >
                        {{
                            approvingId === approveTarget?.id
                                ? 'Approving...'
                                : 'Confirm Approve'
                        }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        
        <Dialog v-model:open="rejectModalOpen">
            <DialogContent class="sm:max-w-sm">
                <form class="space-y-4" @submit.prevent="rejectRequest">
                    <DialogHeader>
                        <DialogTitle class="flex items-center gap-2">
                            <XCircle class="h-4 w-4" />
                            Reject Change Request
                        </DialogTitle>
                        <DialogDescription v-if="selectedRequest">
                            {{ selectedRequest.dispatch?.plate_number }} ·
                            {{
                                selectedRequest.field_label ||
                                formatFieldLabel(
                                    selectedRequest.requested_field,
                                )
                            }}
                        </DialogDescription>
                    </DialogHeader>

                    <Separator />

                    <div class="space-y-2">
                        <Label for="rejection_reason"
                            >Reason for Rejection</Label
                        >
                        <textarea
                            id="rejection_reason"
                            v-model="rejectForm.rejection_reason"
                            placeholder="Explain why this request is being rejected..."
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            rows="3"
                        />
                        <InputError
                            :message="rejectForm.errors.rejection_reason"
                        />
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            class="w-full md:w-auto"
                            @click="closeRejectModal"
                            :disabled="rejectForm.processing"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            variant="destructive"
                            class="w-full md:w-auto"
                            :disabled="rejectForm.processing"
                        >
                            {{
                                rejectForm.processing
                                    ? 'Rejecting...'
                                    : 'Reject Request'
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
