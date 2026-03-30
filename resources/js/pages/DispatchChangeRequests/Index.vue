<script setup lang="ts">
import {
    approve,
    reject,
} from '@/actions/App/Http/Controllers/DispatchChangeRequestController';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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
import { Separator } from '@/components/ui/separator';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

import {
    CheckCircle2,
    Clock,
    Eye,
    MoreHorizontal,
    XCircle,
} from 'lucide-vue-next';

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
    requested_value: unknown;
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

const props = defineProps<{
    changeRequests?: DispatchChangeRequest[];
}>();

const selectedStatus = ref<'all' | 'pending' | 'approved' | 'rejected'>(
    'pending',
);
const rejectModalOpen = ref(false);
const selectedRequest = ref<DispatchChangeRequest | null>(null);
const approvingId = ref<number | null>(null);
const rejectingId = ref<number | null>(null);

const rejectForm = useForm({
    rejection_reason: '',
});

const filteredRequests = computed(() => {
    if (!props.changeRequests) return [];

    return props.changeRequests.filter((req) => {
        if (selectedStatus.value === 'all') return true;
        return req.status === selectedStatus.value;
    });
});

const pendingCount = computed(
    () =>
        props.changeRequests?.filter((r) => r.status === 'pending').length ?? 0,
);

const approvedCount = computed(
    () =>
        props.changeRequests?.filter((r) => r.status === 'approved').length ??
        0,
);

const rejectedCount = computed(
    () =>
        props.changeRequests?.filter((r) => r.status === 'rejected').length ??
        0,
);

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

function approveRequest(request: DispatchChangeRequest) {
    approvingId.value = request.id;

    router.post(
        approve(request.id).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
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
    <AppLayout>
        <Head title="Dispatch Change Requests" />

        <div class="space-y-5 p-4 md:p-6">
            <div
                class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
            >
                <div class="space-y-1">
                    <h1 class="text-2xl font-semibold tracking-tight">
                        Dispatch Change Requests
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Review and approve or reject change requests from
                        companies
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div class="rounded-xl border bg-card p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p
                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                        >
                            Pending
                        </p>
                        <Clock class="h-4 w-4 text-amber-500" />
                    </div>
                    <p class="mt-2 text-2xl font-bold text-amber-600">
                        {{ pendingCount }}
                    </p>
                    <p class="text-xs text-muted-foreground">awaiting action</p>
                </div>

                <div class="rounded-xl border bg-card p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p
                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                        >
                            Approved
                        </p>
                        <CheckCircle2 class="h-4 w-4 text-emerald-500" />
                    </div>
                    <p class="mt-2 text-2xl font-bold text-emerald-600">
                        {{ approvedCount }}
                    </p>
                    <p class="text-xs text-muted-foreground">completed</p>
                </div>

                <div class="rounded-xl border bg-card p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p
                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                        >
                            Rejected
                        </p>
                        <XCircle class="h-4 w-4 text-red-500" />
                    </div>
                    <p class="mt-2 text-2xl font-bold text-red-600">
                        {{ rejectedCount }}
                    </p>
                    <p class="text-xs text-muted-foreground">not approved</p>
                </div>
            </div>

            <Card class="shadow-sm">
                <CardHeader class="pb-4">
                    <div
                        class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
                    >
                        <div>
                            <CardTitle class="text-base"
                                >Change Requests</CardTitle
                            >
                            <CardDescription class="mt-0.5 text-xs">
                                {{ filteredRequests.length }} request{{
                                    filteredRequests.length !== 1 ? 's' : ''
                                }}
                                shown
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>

                <CardContent>
                    <Tabs v-model="selectedStatus" class="w-full">
                        <TabsList class="grid w-full grid-cols-4">
                            <TabsTrigger value="pending">
                                Pending
                                <Badge
                                    v-if="pendingCount > 0"
                                    variant="secondary"
                                    class="ml-1.5 flex h-5 w-5 items-center justify-center rounded-full p-0 text-xs"
                                >
                                    {{ pendingCount }}
                                </Badge>
                            </TabsTrigger>
                            <TabsTrigger value="approved">
                                Approved
                            </TabsTrigger>
                            <TabsTrigger value="rejected">
                                Rejected
                            </TabsTrigger>
                            <TabsTrigger value="all">All</TabsTrigger>
                        </TabsList>

                        <TabsContent
                            v-for="status in [
                                'pending',
                                'approved',
                                'rejected',
                                'all',
                            ]"
                            :key="status"
                            :value="status"
                            class="mt-4"
                        >
                            <div class="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow
                                            class="bg-muted/40 hover:bg-muted/40"
                                        >
                                            <TableHead
                                                class="pl-6 font-semibold"
                                                >Dispatch</TableHead
                                            >
                                            <TableHead class="font-semibold"
                                                >Requester</TableHead
                                            >
                                            <TableHead class="font-semibold"
                                                >Company</TableHead
                                            >
                                            <TableHead class="font-semibold"
                                                >Change</TableHead
                                            >
                                            <TableHead class="font-semibold"
                                                >Reason</TableHead
                                            >
                                            <TableHead class="font-semibold"
                                                >Status</TableHead
                                            >
                                            <TableHead
                                                class="pr-6 text-right font-semibold"
                                                >Action</TableHead
                                            >
                                        </TableRow>
                                    </TableHeader>

                                    <TableBody>
                                        <TableRow
                                            v-if="filteredRequests.length === 0"
                                        >
                                            <TableCell
                                                colspan="7"
                                                class="py-20 text-center"
                                            >
                                                <div
                                                    class="flex flex-col items-center gap-2 text-muted-foreground"
                                                >
                                                    <Eye
                                                        class="h-8 w-8 opacity-30"
                                                    />
                                                    <p
                                                        class="text-sm font-medium"
                                                    >
                                                        No change requests found
                                                    </p>
                                                    <p class="text-xs">
                                                        {{
                                                            selectedStatus ===
                                                            'pending'
                                                                ? 'No pending requests to review.'
                                                                : 'This section is empty.'
                                                        }}
                                                    </p>
                                                </div>
                                            </TableCell>
                                        </TableRow>

                                        <template
                                            v-for="request in filteredRequests"
                                            :key="request.id"
                                        >
                                            <TableRow
                                                class="group transition-colors hover:bg-muted/50"
                                            >
                                                <TableCell class="pl-6">
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
                                                                request.dispatch
                                                                    ?.gate
                                                                    ?.gate_name ??
                                                                '—'
                                                            }}
                                                            · Bay
                                                            {{
                                                                request.dispatch
                                                                    ?.bay_number
                                                            }}
                                                        </p>
                                                    </div>
                                                </TableCell>

                                                <TableCell>
                                                    <div class="space-y-0.5">
                                                        <p
                                                            class="text-sm font-medium"
                                                        >
                                                            {{
                                                                request
                                                                    .requested_by
                                                                    .name
                                                            }}
                                                        </p>
                                                        <p
                                                            class="text-xs text-muted-foreground"
                                                        >
                                                            {{
                                                                request
                                                                    .requested_by
                                                                    .email ??
                                                                '—'
                                                            }}
                                                        </p>
                                                    </div>
                                                </TableCell>

                                                <TableCell>
                                                    <div class="space-y-0.5">
                                                        <p
                                                            class="text-sm font-medium"
                                                        >
                                                            {{
                                                                request.company_name
                                                            }}
                                                        </p>
                                                        <p
                                                            class="text-xs text-muted-foreground"
                                                        >
                                                            {{
                                                                request.company_code
                                                            }}
                                                        </p>
                                                    </div>
                                                </TableCell>

                                                <TableCell>
                                                    <div class="space-y-0.5">
                                                        <p
                                                            class="text-sm font-medium"
                                                        >
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
                                                                formatValue(
                                                                    request.old_value,
                                                                )
                                                            }}
                                                            →
                                                            {{
                                                                formatValue(
                                                                    request.requested_value,
                                                                )
                                                            }}
                                                        </p>
                                                    </div>
                                                </TableCell>

                                                <TableCell>
                                                    <p
                                                        class="max-w-xs truncate text-sm"
                                                        :title="request.reason"
                                                    >
                                                        {{ request.reason }}
                                                    </p>
                                                </TableCell>

                                                <TableCell>
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

                                                <TableCell
                                                    class="pr-6 text-right"
                                                >
                                                    <DropdownMenu
                                                        v-if="
                                                            request.status ===
                                                            'pending'
                                                        "
                                                    >
                                                        <DropdownMenuTrigger
                                                            as-child
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                class="h-8 w-8"
                                                            >
                                                                <MoreHorizontal
                                                                    class="h-4 w-4"
                                                                />
                                                                <span
                                                                    class="sr-only"
                                                                    >Actions</span
                                                                >
                                                            </Button>
                                                        </DropdownMenuTrigger>

                                                        <DropdownMenuContent
                                                            align="end"
                                                            class="w-44"
                                                        >
                                                            <DropdownMenuItem
                                                                as-child
                                                                class="cursor-pointer text-emerald-600 focus:text-emerald-600"
                                                                @click="
                                                                    approveRequest(
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
                                                    request.status ===
                                                        'rejected' &&
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
                                                                    Rejection
                                                                    Reason
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
                        </TabsContent>
                    </Tabs>
                </CardContent>
            </Card>
        </div>

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
