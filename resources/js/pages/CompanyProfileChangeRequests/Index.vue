<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

import InertiaPagination from '@/components/InertiaPagination.vue';

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
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    ArrowRight,
    Building2,
    CheckCircle2,
    Clock3,
    ExternalLink,
    Eye,
    FileDown,
    MoreHorizontal,
    RefreshCw,
    XCircle,
    Filter
} from 'lucide-vue-next';
import { type BreadcrumbItem } from '@/types';
import { index } from '@/routes/companies';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Companies', href: index().url },
    { title: 'Change Requests', href: '/company-profile-change-requests' },
];

type ChangeRequest = {
    id: number;
    company_id: number;
    status: 'pending' | 'approved' | 'rejected';
    requested_values: Record<string, unknown>;
    current_values: Record<string, unknown> | null;
    logo_change?: {
        has_change: boolean;
        new_preview_url?: string | null;
        old_preview_url?: string | null;
        is_remove?: boolean;
    };
    supporting_documents?: Array<{
        doc_type?: string | null;
        original_name?: string | null;
        mime_type?: string | null;
        issued_at?: string | null;
        expires_at?: string | null;
        file_path?: string | null;
        preview_url?: string | null;
    }>;
    rejection_reason?: string | null;
    approved_at?: string | null;
    created_at?: string | null;
    company: {
        id: number;
        company_name: string;
        company_code: string | null;
        status: string;
        show_url?: string | null;
    };
    requester: {
        id: number;
        name: string;
        email: string | null;
    } | null;
    approver?: {
        id: number;
        name: string;
    } | null;
};

type SupportingDocument = {
    doc_type?: string | null;
    original_name?: string | null;
    mime_type?: string | null;
    issued_at?: string | null;
    expires_at?: string | null;
    file_path?: string | null;
    preview_url?: string | null;
};

const props = defineProps<{
    requests: {
        data: ChangeRequest[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: { status: string };
}>();

const selectedStatus = ref<'all' | 'pending' | 'approved' | 'rejected'>(
    (props.filters.status as 'all' | 'pending' | 'approved' | 'rejected') ?? 'pending',
);

const rejectModalOpen = ref(false);
const selected = ref<ChangeRequest | null>(null);
const approvingId = ref<number | null>(null);

const previewOpen = ref(false);
const previewDoc = ref<SupportingDocument | null>(null);
const pdfLoadError = ref(false);
const previewRequest = ref<ChangeRequest | null>(null);
const reviewedRequestIds = ref<number[]>([]);

const rejectForm = useForm({ rejection_reason: '' });

const pending = computed(() =>
    props.requests.data.filter((item) => item.status === 'pending'),
);

watch(selectedStatus, (val) => {
    router.get(
        '/company-profile-change-requests',
        { status: val },
        { preserveScroll: true, preserveState: true, replace: true },
    );
});

function badgeVariant(status: ChangeRequest['status']): 'success' | 'warning' | 'destructive' {
    if (status === 'approved') return 'success';
    if (status === 'rejected') return 'destructive';
    return 'warning';
}

function approveRequest(item: ChangeRequest) {
    approvingId.value = item.id;
    router.post(`/company-profile-change-requests/${item.id}/approve`, {}, {
        preserveScroll: true,
        onFinish: () => { approvingId.value = null; },
    });
}

function hasPreviewed(item: ChangeRequest): boolean {
    return reviewedRequestIds.value.includes(item.id);
}

function openReject(item: ChangeRequest) {
    selected.value = item;
    rejectForm.reset();
    rejectModalOpen.value = true;
}

function rejectSelected() {
    if (!selected.value) return;
    rejectForm.post(`/company-profile-change-requests/${selected.value.id}/reject`, {
        preserveScroll: true,
        onSuccess: () => {
            rejectModalOpen.value = false;
            selected.value = null;
            rejectForm.reset();
        },
    });
}

function displayValue(value: unknown): string {
    if (value === null || value === undefined || value === '') return '—';
    if (typeof value === 'boolean') return value ? 'Yes' : 'No';
    if (typeof value === 'object') return JSON.stringify(value);
    return String(value);
}

function normalizeFieldName(field: string): string {
    return field.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function humanize(value: string | null | undefined): string {
    if (!value) return '—';
    return value.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function isImageDoc(doc: SupportingDocument | null): boolean {
    if (!doc) return false;
    if (doc.mime_type) return doc.mime_type.startsWith('image/');
    const ext = (doc.original_name ?? doc.file_path ?? '').split('.').pop()?.toLowerCase();
    return ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext ?? '');
}

function isPdfDoc(doc: SupportingDocument | null): boolean {
    if (!doc) return false;
    if (doc.mime_type) return doc.mime_type === 'application/pdf';
    const ext = (doc.original_name ?? doc.file_path ?? '').split('.').pop()?.toLowerCase();
    return ext === 'pdf';
}

function canPreviewDoc(doc: SupportingDocument | null): boolean {
    return isImageDoc(doc) || isPdfDoc(doc);
}

function openPreviewDoc(doc: SupportingDocument, request?: ChangeRequest) {
    previewDoc.value = doc;
    pdfLoadError.value = false;
    previewRequest.value = request ?? null;
    if (request && !reviewedRequestIds.value.includes(request.id)) {
        reviewedRequestIds.value.push(request.id);
    }
    previewOpen.value = true;
}

function closePreviewDoc() {
    previewOpen.value = false;
    previewDoc.value = null;
    pdfLoadError.value = false;
    previewRequest.value = null;
}

function openRejectFromPreview() {
    const request = previewRequest.value;
    closePreviewDoc();
    if (!request) return;
    openReject(request);
}

function openLogoPreview(url: string | null | undefined, title: string) {
    if (!url) return;
    openPreviewDoc({
        doc_type: 'company_logo',
        original_name: title,
        mime_type: 'image/*',
        preview_url: url,
    }, previewRequest.value ?? undefined);
}

function primaryPreviewDoc(item: ChangeRequest): SupportingDocument | null {
    const logo = item.logo_change?.new_preview_url ?? item.logo_change?.old_preview_url ?? null;
    if (logo) {
        return { doc_type: 'company_logo', original_name: 'Company Logo', mime_type: 'image/*', preview_url: logo };
    }
    const doc = (item.supporting_documents ?? []).find((d) => !!d.preview_url);
    return doc ?? null;
}

function openPrimaryPreview(item: ChangeRequest) {
    const doc = primaryPreviewDoc(item);
    if (!doc) return;
    openPreviewDoc(doc, item);
}

function downloadPrimary(item: ChangeRequest) {
    const doc = primaryPreviewDoc(item);
    if (!doc?.preview_url) return;
    window.open(doc.preview_url, '_blank', 'noopener,noreferrer');
}

function canTakePreviewAction(): boolean {
    if (previewRequest.value?.status !== 'pending') return false;
    const isCurrentLogoPreview =
        previewDoc.value?.doc_type === 'company_logo' &&
        previewDoc.value?.original_name === 'Current Logo';
    return !isCurrentLogoPreview;
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
                            <hr class="h-px w-full border border-rose-500 " />
                            <div class="border-7 border-rose-500 rounded-xs">
                                <div class="border-3 border-white rounded-xs"></div>
                            </div>
                        </span>
                    </CardTitle>
                    <CardDescription class="mt-1">
                        Review and approve external company profile updates.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div
                        class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between min-w-50/100">
                            <div class="flex flex-wrap items-center gap-2">
                                <Select
                                    v-model="selectedStatus"
                                >
                                    <SelectTrigger
                                        class="cursor-pointer h-8 w-fit rounded-lg border-slate-200 shadow-sm"
                                    >
                                        <Filter class="h-3.5 w-3.5 text-slate-600" />
                                        <SelectValue placeholder="All Statuses" class="justify-start flex"/>
                                    </SelectTrigger>
                                    <SelectContent class="rounded-lg shadow-lg">
                                        <SelectItem key="all" value="all" class="cursor-pointer text-sm"
                                            >All Statuses</SelectItem
                                        >
                                        <SelectItem key="pending" value="pending" class="cursor-pointer text-sm"
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
                                <!-- <CardDescription class="mt-1">
                                    {{ requests.data.length }} total · {{ pending.length }} pending
                                </CardDescription> -->
                            </div>
                        </div>  
                    </div>
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader class="border-y border-slate-200">
                                <TableRow class="gap-2">
                                    <TableHead class="px-0 text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Company</TableHead>
                                    <TableHead class="px-0 text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Requester</TableHead>
                                    <TableHead class="px-0 text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Status</TableHead>
                                    <TableHead class="px-0 text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Requested Changes</TableHead>
                                    <TableHead class="px-0 text-right text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Action</TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody class="border-y border-slate-200">
                                <!-- Empty state -->
                                <TableRow v-if="requests.data.length === 0" class="hover:bg-transparent">
                                    <TableCell colspan="5" class="py-20 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-muted">
                                                <RefreshCw class="h-6 w-6 text-muted-foreground/40" />
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-foreground">No change requests found</p>
                                                <p class="mt-0.5 text-xs text-muted-foreground">All caught up!</p>
                                            </div>
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <TableRow
                                    v-for="item in requests.data"
                                    :key="item.id"
                                    :class="item.status === 'pending' ? 'border-l-2 border-l-blue-500' : ''"
                                    class="group transition-colors hover:bg-muted/30"
                                >
                                    <!-- Company -->
                                    <TableCell class="px-0">
                                        <div class="flex items-center gap-2.5">
                                            <div>
                                                <div class="text-sm font-semibold">{{ item.company.company_name }}</div>
                                                <div class="text-xs text-muted-foreground">
                                                    {{ item.company.company_code ?? '—' }}
                                                </div>
                                            </div>
                                        </div>
                                    </TableCell>

                                    <!-- Requester -->
                                    <TableCell class="px-0">
                                        <div class="text-sm">{{ item.requester?.name ?? '—' }}</div>
                                        <div class="text-xs text-muted-foreground">{{ item.requester?.email ?? '' }}</div>
                                    </TableCell>

                                    <!-- Status -->
                                    <TableCell class="px-0">
                                        <Badge :variant="badgeVariant(item.status)" class="capitalize">
                                            {{ item.status }}
                                        </Badge>
                                        <div v-if="item.approved_at" class="mt-1 text-[11px] text-muted-foreground">
                                            {{ item.approved_at }}
                                        </div>
                                    </TableCell>

                                    <!-- Changes -->
                                    <TableCell class="max-w-sm px-0">
                                        <div class="space-y-1.5 text-xs">
                                            <!-- Field changes -->
                                            <div
                                                v-for="(newValue, key) in item.requested_values"
                                                :key="key"
                                                class="flex flex-wrap items-center gap-1 rounded-md bg-muted/60 px-2 py-1"
                                            >
                                                <span class="font-semibold text-foreground">{{ normalizeFieldName(String(key)) }}</span>
                                                <span class="text-muted-foreground line-through">{{ displayValue(item.current_values?.[String(key)]) }}</span>
                                                <ArrowRight class="h-3 w-3 text-muted-foreground/50" />
                                                <span class="font-medium text-foreground">{{ displayValue(newValue) }}</span>
                                            </div>

                                            <!-- Logo change -->
                                            <div
                                                v-if="item.logo_change?.has_change"
                                                class="rounded-md border border-blue-200 bg-blue-50 px-2 py-1.5"
                                            >
                                                <div class="mb-1 font-semibold text-blue-700">Company Logo</div>
                                                <div class="flex flex-wrap gap-1.5">
                                                    <button
                                                        v-if="item.logo_change.old_preview_url"
                                                        type="button"
                                                        @click="previewRequest = item; openLogoPreview(item.logo_change.old_preview_url, 'Current Logo')"
                                                        class="inline-flex items-center gap-1 rounded border border-blue-300 bg-white px-2 py-0.5 text-[11px] font-medium text-blue-700 transition-colors hover:bg-blue-50"
                                                    >
                                                        <Eye class="h-3 w-3" /> Current
                                                    </button>
                                                    <button
                                                        v-if="item.logo_change.new_preview_url"
                                                        type="button"
                                                        @click="previewRequest = item; openLogoPreview(item.logo_change.new_preview_url, 'Requested Logo')"
                                                        class="inline-flex items-center gap-1 rounded border border-blue-300 bg-white px-2 py-0.5 text-[11px] font-medium text-blue-700 transition-colors hover:bg-blue-50"
                                                    >
                                                        <Eye class="h-3 w-3" /> Requested
                                                    </button>
                                                    <span v-if="item.logo_change.is_remove" class="text-[11px] font-medium text-red-600">
                                                        Remove logo requested
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Supporting documents -->
                                            <div
                                                v-for="(doc, index) in item.supporting_documents ?? []"
                                                :key="`doc-${item.id}-${index}`"
                                                class="rounded-md border border-red-200 bg-red-50 px-2 py-1.5"
                                            >
                                                <div class="mb-0.5 font-semibold text-red-700">{{ humanize(doc.doc_type) }}</div>
                                                <div class="text-[11px] text-muted-foreground">{{ doc.original_name ?? 'Pending upload' }}</div>
                                                <div v-if="doc.preview_url" class="mt-1 flex gap-1.5">
                                                    <button
                                                        v-if="canPreviewDoc(doc)"
                                                        type="button"
                                                        @click="openPreviewDoc(doc, item)"
                                                        class="inline-flex items-center gap-1 rounded border border-red-300 bg-white px-2 py-0.5 text-[11px] font-medium text-red-700 transition-colors hover:bg-red-50"
                                                    >
                                                        <Eye class="h-3 w-3" /> Preview
                                                    </button>
                                                    <a
                                                        v-else
                                                        :href="doc.preview_url"
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        class="inline-flex items-center gap-1 rounded border border-red-300 bg-white px-2 py-0.5 text-[11px] font-medium text-red-700 transition-colors hover:bg-red-50"
                                                    >
                                                        <Eye class="h-3 w-3" /> Open
                                                    </a>
                                                    <a
                                                        :href="doc.preview_url"
                                                        download
                                                        class="inline-flex items-center gap-1 rounded border border-red-300 bg-white px-2 py-0.5 text-[11px] font-medium text-red-700 transition-colors hover:bg-red-50"
                                                    >
                                                        <FileDown class="h-3 w-3" /> Download
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </TableCell>

                                    <!-- Actions -->
                                    <TableCell class="px-0 text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button variant="outline" class="rounded-lg text-muted-foreground hover:bg-muted hover:text-foreground cursor-pointer">
                                                    <MoreHorizontal class="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end" class="w-fit rounded-lg border-slate-200 shadow-lg">

                                                <!-- Pending primary actions -->
                                                <template v-if="item.status === 'pending' && !primaryPreviewDoc(item)">
                                                    <DropdownMenuItem
                                                        :disabled="approvingId === item.id"
                                                        class="rounded-lg hover:bg-slate-100 cursor-pointer"
                                                        @click="approveRequest(item)"
                                                    >
                                                        <CheckCircle2 class="h-4 w-4" />
                                                        Approve
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        class="rounded-lg hover:bg-slate-100 cursor-pointer"
                                                        @click="openReject(item)"
                                                    >
                                                        <XCircle class="h-4 w-4" />
                                                        Reject
                                                    </DropdownMenuItem>
                                                    <DropdownMenuSeparator />
                                                </template>

                                                <!-- Secondary actions -->
                                                <DropdownMenuItem
                                                    v-if="item.company.show_url"
                                                    as-child
                                                    class="rounded-lg"
                                                >
                                                    <a :href="item.company.show_url" class="flex items-center">
                                                        <ExternalLink class="mr-2 h-3.5 w-3.5" />
                                                        View Company
                                                    </a>
                                                </DropdownMenuItem>
                                                <DropdownMenuItem
                                                    v-if="primaryPreviewDoc(item)"
                                                    class="rounded-lg"
                                                    @click="openPrimaryPreview(item)"
                                                >
                                                    <Eye class="mr-2 h-3.5 w-3.5" />
                                                    Preview Document
                                                </DropdownMenuItem>
                                                <DropdownMenuItem
                                                    v-if="primaryPreviewDoc(item)"
                                                    class="rounded-lg"
                                                    @click="downloadPrimary(item)"
                                                >
                                                    <FileDown class="mr-2 h-3.5 w-3.5" />
                                                    Download
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <InertiaPagination
                        v-if="requests.links?.length"
                        :links="requests.links"
                        :meta="{ from: requests.from, to: requests.to, total: requests.total }"
                    />
                </CardContent>
            </Card>
        </div>

        <!-- Reject Modal -->
        <Dialog :open="rejectModalOpen" @update:open="rejectModalOpen = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Reject Change Request</DialogTitle>
                    <DialogDescription>
                        Provide a reason for rejection. This will be visible to the requester.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-2 py-2">
                    <Textarea
                        v-model="rejectForm.rejection_reason"
                        rows="4"
                        placeholder="Explain why this request is being rejected…"
                        class="resize-none"
                    />
                    <p v-if="rejectForm.errors.rejection_reason" class="text-xs text-destructive">
                        {{ rejectForm.errors.rejection_reason }}
                    </p>
                </div>

                <DialogFooter class="gap-2">
                    <Button variant="outline" @click="rejectModalOpen = false">Cancel</Button>
                    <Button
                        variant="destructive"
                        :disabled="rejectForm.processing || !selected"
                        @click="rejectSelected"
                    >
                        Confirm Rejection
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Preview Modal -->
        <Dialog
            :open="previewOpen"
            @update:open="(v) => { if (!v) closePreviewDoc(); else previewOpen = true; }"
        >
            <DialogContent class="flex max-h-[90vh] w-full max-w-4xl flex-col gap-0 overflow-hidden rounded-2xl p-0">
                <DialogHeader class="border-b px-5 py-4">
                    <DialogTitle class="truncate text-base font-semibold">
                        {{ previewDoc?.original_name ?? humanize(previewDoc?.doc_type) }}
                    </DialogTitle>
                    <DialogDescription>
                        {{ humanize(previewDoc?.doc_type) }}
                    </DialogDescription>
                </DialogHeader>

                <!-- Image -->
                <div
                    v-if="previewDoc && isImageDoc(previewDoc)"
                    class="flex min-h-[50vh] items-center justify-center bg-muted/30 p-6"
                >
                    <img
                        :src="previewDoc.preview_url!"
                        :alt="previewDoc.original_name ?? humanize(previewDoc.doc_type)"
                        class="max-h-[72vh] max-w-full rounded-lg border bg-white object-contain shadow"
                    />
                </div>

                <!-- PDF -->
                <div v-else-if="previewDoc && isPdfDoc(previewDoc)" class="h-[70vh] w-full bg-muted/20">
                    <iframe :src="previewDoc.preview_url!" class="h-full w-full" frameborder="0" @error="pdfLoadError = true" />
                    <div v-if="pdfLoadError" class="flex h-full items-center justify-center p-6 text-center text-sm text-muted-foreground">
                        Failed to load preview. Use "Open in New Tab" below.
                    </div>
                </div>

                <!-- Unsupported -->
                <div v-else class="flex min-h-[40vh] items-center justify-center p-6 text-sm text-muted-foreground">
                    Preview not supported for this file type.
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-between border-t px-5 py-3">
                    <a
                        v-if="previewDoc?.preview_url"
                        :href="previewDoc.preview_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 rounded border px-3 py-1.5 text-xs font-medium text-foreground transition-colors hover:bg-muted"
                    >
                        <ExternalLink class="h-3.5 w-3.5" />
                        Open in New Tab
                    </a>

                    <div class="ml-auto flex items-center gap-2">
                        <template v-if="canTakePreviewAction() && previewRequest">
                            <Button
                                size="sm"
                                class="border-0 bg-blue-600 text-white hover:bg-blue-700"
                                :disabled="approvingId === previewRequest.id"
                                @click="approveRequest(previewRequest)"
                            >
                                <CheckCircle2 class="mr-1.5 h-3.5 w-3.5" />
                                Approve
                            </Button>
                            <Button
                                size="sm"
                                variant="destructive"
                                @click="openRejectFromPreview"
                            >
                                <XCircle class="mr-1.5 h-3.5 w-3.5" />
                                Reject
                            </Button>
                        </template>
                        <Button size="sm" variant="outline" @click="closePreviewDoc">Close</Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>