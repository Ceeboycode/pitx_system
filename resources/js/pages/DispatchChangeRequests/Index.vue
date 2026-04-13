<script setup lang="ts">
import {
    approve,
    index as changeRequestsIndex,
    reject,
} from '@/actions/App/Http/Controllers/DispatchChangeRequestController';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

import InertiaPagination from '@/components/InertiaPagination.vue';
import InputError from '@/components/InputError.vue';
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
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Label } from '@/components/ui/label';
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
    CheckCircle2,
    Clock,
    Eye,
    Filter,
    MoreHorizontal,
    XCircle,
} from 'lucide-vue-next';

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
    filters: { status: string };
}>();

const selectedStatus = ref<'all' | 'pending' | 'approved' | 'rejected'>(
    (props.filters.status as 'all' | 'pending' | 'approved' | 'rejected') ??
        'pending',
);
const approveModalOpen = ref(false);
const approveTarget = ref<DispatchChangeRequest | null>(null);
const rejectModalOpen = ref(false);
const selectedRequest = ref<DispatchChangeRequest | null>(null);
const approvingId = ref<number | null>(null);
const rejectingId = ref<number | null>(null);

const rejectForm = useForm({
    rejection_reason: '',
});

watch(selectedStatus, (val) => {
    router.get(
        changeRequestsIndex().url,
        { status: val },
        { preserveScroll: true, preserveState: true, replace: true },
    );
});

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
): 'default' | 'secondary' | 'outline' | 'destructive' {
    switch (status) {
        case 'pending':
            return 'secondary';
        case 'approved':
            return 'default';
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
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <!-- <span>Change Requests</span> -->
                        <!-- TODO: make the text straight, not wrapped -->
                        Change Requests
                        <span class="ml-2 flex flex-1 items-center">
                            <hr class="h-px w-full border border-rose-500" />
                            <div class="rounded-xs border-7 border-rose-500">
                                <div
                                    class="rounded-xs border-3 border-white"
                                ></div>
                            </div>
                        </span>
                    </CardTitle>
                    <CardDescription class="mt-1">
                        Review and approve or reject change requests from
                        companies.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div
                        class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div
                            class="flex min-w-50/100 flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div class="flex flex-wrap items-center gap-2">
                                <Select v-model="selectedStatus">
                                    <SelectTrigger
                                        class="h-8 w-fit cursor-pointer rounded-lg border-slate-200 shadow-sm"
                                    >
                                        <Filter
                                            class="h-3.5 w-3.5 text-slate-600"
                                        />
                                        <SelectValue
                                            placeholder="All Statuses"
                                            class="flex justify-start"
                                        />
                                    </SelectTrigger>
                                    <SelectContent class="rounded-lg shadow-lg">
                                        <SelectItem
                                            key="all"
                                            value="all"
                                            class="cursor-pointer text-sm"
                                            >All Statuses</SelectItem
                                        >
                                        <SelectItem
                                            key="pending"
                                            value="pending"
                                            class="cursor-pointer text-sm"
                                            >Pending</SelectItem
                                        >
                                        <SelectItem
                                            key="approved"
                                            value="approved"
                                            class="cursor-pointer text-sm"
                                            >Approved</SelectItem
                                        >
                                        <SelectItem
                                            key="rejected"
                                            value="rejected"
                                            class="cursor-pointer text-sm"
                                            >Rejected</SelectItem
                                        >
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader class="border-y border-slate-200">
                                <TableRow class="gap-2">
                                    <TableHead
                                        class="px-0 text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Dispatch</TableHead
                                    >
                                    <TableHead
                                        class="px-0 text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Requester</TableHead
                                    >
                                    <TableHead
                                        class="px-0 text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Company</TableHead
                                    >
                                    <TableHead
                                        class="px-0 text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Change</TableHead
                                    >
                                    <TableHead
                                        class="px-0 text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Reason</TableHead
                                    >
                                    <TableHead
                                        class="px-0 text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Status</TableHead
                                    >
                                    <TableHead
                                        class="px-0 text-right text-[11px] font-bold tracking-widest text-muted-foreground uppercase"
                                        >Action</TableHead
                                    >
                                </TableRow>
                            </TableHeader>

                            <TableBody class="border-y border-slate-200">
                                <TableRow
                                    v-if="changeRequests.data.length === 0"
                                >
                                    <TableCell
                                        colspan="7"
                                        class="py-20 text-center"
                                    >
                                        <div
                                            class="flex flex-col items-center gap-2 text-muted-foreground"
                                        >
                                            <Eye class="h-8 w-8 opacity-30" />
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
                                        class="group transition-colors hover:bg-muted/50"
                                    >
                                        <TableCell class="px-0">
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

                                        <TableCell class="px-0 text-right">
                                            <DropdownMenu
                                                v-if="
                                                    request.status === 'pending'
                                                "
                                            >
                                                <DropdownMenuTrigger as-child>
                                                    <Button
                                                        variant="outline"
                                                        class="cursor-pointer rounded-lg text-muted-foreground hover:bg-muted hover:text-foreground"
                                                    >
                                                        <MoreHorizontal
                                                            class="h-4 w-4"
                                                        />
                                                        <span class="sr-only"
                                                            >Actions</span
                                                        >
                                                    </Button>
                                                </DropdownMenuTrigger>

                                                <DropdownMenuContent
                                                    align="end"
                                                    class="w-fit rounded-lg border-slate-200 shadow-lg"
                                                >
                                                    <DropdownMenuItem
                                                        as-child
                                                        class="cursor-pointer text-emerald-600 focus:text-emerald-600"
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
                                                                    ? 'Approving…'
                                                                    : 'Approve'
                                                            }}
                                                        </div>
                                                    </DropdownMenuItem>

                                                    <DropdownMenuSeparator />

                                                    <DropdownMenuItem
                                                        class="cursor-pointer text-red-600 focus:text-red-600"
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

                                    <!-- Rejection Reason Row -->
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

        <!-- Approve Confirmation Modal -->
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
                                ? 'Approving…'
                                : 'Confirm Approve'
                        }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Rejection Reason Modal -->
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
                                    ? 'Rejecting…'
                                    : 'Reject Request'
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
