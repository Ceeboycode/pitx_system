<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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
    CheckCircle2,
    Clock3,
    ExternalLink,
    Eye,
    FileDown,
    MoreHorizontal,
    XCircle,
} from 'lucide-vue-next';

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
    };
}>();

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

function badgeVariant(
    status: ChangeRequest['status'],
): 'default' | 'secondary' | 'destructive' {
    if (status === 'approved') return 'default';
    if (status === 'rejected') return 'destructive';
    return 'secondary';
}

function approveRequest(item: ChangeRequest) {
    approvingId.value = item.id;
    router.post(
        `/company-profile-change-requests/${item.id}/approve`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                approvingId.value = null;
            },
        },
    );
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

    rejectForm.post(
        `/company-profile-change-requests/${selected.value.id}/reject`,
        {
            preserveScroll: true,
            onSuccess: () => {
                rejectModalOpen.value = false;
                selected.value = null;
                rejectForm.reset();
            },
        },
    );
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
    const ext = (doc.original_name ?? doc.file_path ?? '')
        .split('.')
        .pop()
        ?.toLowerCase();
    return ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext ?? '');
}

function isPdfDoc(doc: SupportingDocument | null): boolean {
    if (!doc) return false;
    if (doc.mime_type) return doc.mime_type === 'application/pdf';
    const ext = (doc.original_name ?? doc.file_path ?? '')
        .split('.')
        .pop()
        ?.toLowerCase();
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

    openPreviewDoc(
        {
            doc_type: 'company_logo',
            original_name: title,
            mime_type: 'image/*',
            preview_url: url,
        },
        previewRequest.value ?? undefined,
    );
}

function primaryPreviewDoc(item: ChangeRequest): SupportingDocument | null {
    const logo =
        item.logo_change?.new_preview_url ??
        item.logo_change?.old_preview_url ??
        null;
    if (logo) {
        return {
            doc_type: 'company_logo',
            original_name: 'Company Logo',
            mime_type: 'image/*',
            preview_url: logo,
        };
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
    <AppLayout>
        <Head title="Company Profile Change Requests" />

        <div class="space-y-5 p-4 md:p-6">
            <div class="space-y-1">
                <h1 class="text-2xl font-semibold tracking-tight">
                    Company Profile Change Requests
                </h1>
                <p class="text-sm text-muted-foreground">
                    Review and approve external company profile updates.
                </p>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div class="rounded-xl border bg-card p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p
                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                        >
                            Pending
                        </p>
                        <Clock3 class="h-4 w-4 text-amber-500" />
                    </div>
                    <p class="mt-2 text-2xl font-bold text-amber-600">
                        {{
                            requests.data.filter((r) => r.status === 'pending')
                                .length
                        }}
                    </p>
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
                        {{
                            requests.data.filter((r) => r.status === 'approved')
                                .length
                        }}
                    </p>
                </div>
                <div class="rounded-xl border bg-card p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p
                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                        >
                            Rejected
                        </p>
                        <XCircle class="h-4 w-4 text-rose-500" />
                    </div>
                    <p class="mt-2 text-2xl font-bold text-rose-600">
                        {{
                            requests.data.filter((r) => r.status === 'rejected')
                                .length
                        }}
                    </p>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Requests</CardTitle>
                    <CardDescription
                        >{{ requests.data.length }} total
                        requests</CardDescription
                    >
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Company</TableHead>
                                <TableHead>Requester</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Changes</TableHead>
                                <TableHead class="text-right">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="item in requests.data"
                                :key="item.id"
                            >
                                <TableCell>
                                    <div class="text-sm font-medium">
                                        {{ item.company.company_name }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ item.company.company_code ?? '—' }}
                                    </div>
                                </TableCell>
                                <TableCell>{{
                                    item.requester?.name ?? '—'
                                }}</TableCell>
                                <TableCell>
                                    <Badge
                                        :variant="badgeVariant(item.status)"
                                        >{{ item.status }}</Badge
                                    >
                                </TableCell>
                                <TableCell>
                                    <div class="space-y-1 text-xs">
                                        <div
                                            v-for="(
                                                newValue, key
                                            ) in item.requested_values"
                                            :key="key"
                                            class="rounded border px-2 py-1"
                                        >
                                            <span class="font-medium"
                                                >{{
                                                    normalizeFieldName(
                                                        String(key),
                                                    )
                                                }}:</span
                                            >
                                            <span
                                                class="ml-1 text-muted-foreground"
                                                >{{
                                                    displayValue(
                                                        item.current_values?.[
                                                            String(key)
                                                        ],
                                                    )
                                                }}</span
                                            >
                                            <span class="mx-1">→</span>
                                            <span>{{
                                                displayValue(newValue)
                                            }}</span>
                                        </div>

                                        <div
                                            v-if="item.logo_change?.has_change"
                                            class="rounded border border-purple-200 bg-purple-50 px-2 py-1.5"
                                        >
                                            <div
                                                class="font-medium text-purple-700"
                                            >
                                                Company Logo
                                            </div>
                                            <div
                                                class="mt-1 flex flex-wrap items-center gap-2"
                                            >
                                                <button
                                                    v-if="
                                                        item.logo_change
                                                            .old_preview_url
                                                    "
                                                    type="button"
                                                    @click="
                                                        previewRequest = item;
                                                        openLogoPreview(
                                                            item.logo_change
                                                                .old_preview_url,
                                                            'Current Logo',
                                                        );
                                                    "
                                                    class="inline-flex items-center rounded border border-purple-300 bg-white px-2 py-1 text-[11px] font-medium text-purple-700 hover:bg-purple-100"
                                                >
                                                    <Eye class="mr-1 h-3 w-3" />
                                                    Preview Current
                                                </button>

                                                <button
                                                    v-if="
                                                        item.logo_change
                                                            .new_preview_url
                                                    "
                                                    type="button"
                                                    @click="
                                                        previewRequest = item;
                                                        openLogoPreview(
                                                            item.logo_change
                                                                .new_preview_url,
                                                            'Requested Logo',
                                                        );
                                                    "
                                                    class="inline-flex items-center rounded border border-purple-300 bg-white px-2 py-1 text-[11px] font-medium text-purple-700 hover:bg-purple-100"
                                                >
                                                    <Eye class="mr-1 h-3 w-3" />
                                                    Preview Requested
                                                </button>

                                                <span
                                                    v-if="
                                                        item.logo_change
                                                            .is_remove
                                                    "
                                                    class="text-[11px] font-medium text-rose-700"
                                                >
                                                    Requested action: Remove
                                                    logo
                                                </span>
                                            </div>
                                        </div>

                                        <div
                                            v-for="(
                                                doc, index
                                            ) in item.supporting_documents ??
                                            []"
                                            :key="`doc-${item.id}-${index}`"
                                            class="rounded border border-blue-200 bg-blue-50 px-2 py-1.5"
                                        >
                                            <div
                                                class="font-medium text-blue-700"
                                            >
                                                {{ humanize(doc.doc_type) }}
                                            </div>
                                            <div
                                                class="text-[11px] text-muted-foreground"
                                            >
                                                {{
                                                    doc.original_name ??
                                                    'Pending upload'
                                                }}
                                            </div>
                                            <div
                                                class="mt-1 flex items-center gap-1.5"
                                                v-if="doc.preview_url"
                                            >
                                                <button
                                                    v-if="canPreviewDoc(doc)"
                                                    type="button"
                                                    @click="
                                                        openPreviewDoc(
                                                            doc,
                                                            item,
                                                        )
                                                    "
                                                    class="inline-flex items-center rounded border border-blue-300 bg-white px-2 py-1 text-[11px] font-medium text-blue-700 hover:bg-blue-100"
                                                >
                                                    <Eye class="mr-1 h-3 w-3" />
                                                    Preview
                                                </button>
                                                <a
                                                    v-else
                                                    :href="doc.preview_url"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="inline-flex items-center rounded border border-blue-300 bg-white px-2 py-1 text-[11px] font-medium text-blue-700 hover:bg-blue-100"
                                                >
                                                    <Eye class="mr-1 h-3 w-3" />
                                                    Open
                                                </a>
                                                <a
                                                    :href="doc.preview_url"
                                                    download
                                                    class="inline-flex items-center rounded border border-blue-300 bg-white px-2 py-1 text-[11px] font-medium text-blue-700 hover:bg-blue-100"
                                                >
                                                    <FileDown
                                                        class="mr-1 h-3 w-3"
                                                    />
                                                    Download
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell class="text-right">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="h-8 w-8 rounded-lg"
                                            >
                                                <MoreHorizontal
                                                    class="h-4 w-4"
                                                />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent
                                            align="end"
                                            class="w-44"
                                        >
                                            <DropdownMenuItem
                                                v-if="item.company.show_url"
                                                as-child
                                            >
                                                <a
                                                    :href="
                                                        item.company.show_url
                                                    "
                                                    class="flex items-center"
                                                >
                                                    <ExternalLink
                                                        class="mr-2 h-3.5 w-3.5"
                                                    />
                                                    Show
                                                </a>
                                            </DropdownMenuItem>

                                            <DropdownMenuItem
                                                v-if="primaryPreviewDoc(item)"
                                                @click="
                                                    openPrimaryPreview(item)
                                                "
                                            >
                                                <Eye class="mr-2 h-3.5 w-3.5" />
                                                Preview
                                            </DropdownMenuItem>

                                            <DropdownMenuItem
                                                v-if="primaryPreviewDoc(item)"
                                                @click="downloadPrimary(item)"
                                            >
                                                <FileDown
                                                    class="mr-2 h-3.5 w-3.5"
                                                />
                                                Download
                                            </DropdownMenuItem>

                                            <DropdownMenuItem
                                                v-if="
                                                    item.status === 'pending' &&
                                                    !primaryPreviewDoc(item)
                                                "
                                                :disabled="
                                                    approvingId === item.id
                                                "
                                                @click="approveRequest(item)"
                                            >
                                                <CheckCircle2
                                                    class="mr-2 h-3.5 w-3.5"
                                                />
                                                Approve
                                            </DropdownMenuItem>

                                            <DropdownMenuItem
                                                v-if="
                                                    item.status === 'pending' &&
                                                    !primaryPreviewDoc(item)
                                                "
                                                @click="openReject(item)"
                                            >
                                                <XCircle
                                                    class="mr-2 h-3.5 w-3.5"
                                                />
                                                Reject
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="requests.data.length === 0">
                                <TableCell
                                    colspan="5"
                                    class="py-8 text-center text-sm text-muted-foreground"
                                >
                                    No profile change requests found.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <Dialog
                :open="rejectModalOpen"
                @update:open="rejectModalOpen = $event"
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Reject profile change request</DialogTitle>
                    </DialogHeader>

                    <textarea
                        v-model="rejectForm.rejection_reason"
                        class="w-full rounded-md border px-3 py-2 text-sm"
                        rows="4"
                        placeholder="Explain why this request is rejected"
                    />
                    <p
                        v-if="rejectForm.errors.rejection_reason"
                        class="text-xs text-destructive"
                    >
                        {{ rejectForm.errors.rejection_reason }}
                    </p>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            @click="rejectModalOpen = false"
                            >Cancel</Button
                        >
                        <Button
                            variant="destructive"
                            :disabled="rejectForm.processing || !selected"
                            @click="rejectSelected"
                        >
                            Reject Request
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog
                :open="previewOpen"
                @update:open="
                    (v) => {
                        if (!v) closePreviewDoc();
                        else previewOpen = true;
                    }
                "
            >
                <DialogContent
                    class="flex max-h-[90vh] w-full max-w-4xl flex-col gap-0 overflow-hidden rounded-2xl p-0"
                >
                    <DialogHeader class="border-b border-slate-100 px-5 py-4">
                        <DialogTitle class="truncate text-base font-semibold">
                            {{
                                previewDoc?.original_name ??
                                humanize(previewDoc?.doc_type)
                            }}
                        </DialogTitle>
                        <DialogDescription>
                            {{ humanize(previewDoc?.doc_type) }}
                        </DialogDescription>
                    </DialogHeader>

                    <div
                        v-if="previewDoc && isImageDoc(previewDoc)"
                        class="flex min-h-[50vh] items-center justify-center bg-slate-50 p-6"
                    >
                        <img
                            :src="previewDoc.preview_url!"
                            :alt="
                                previewDoc.original_name ??
                                humanize(previewDoc.doc_type)
                            "
                            class="max-h-[72vh] max-w-full rounded border bg-white object-contain shadow-sm"
                        />
                    </div>

                    <div
                        v-else-if="previewDoc && isPdfDoc(previewDoc)"
                        class="h-[70vh] w-full bg-slate-100"
                    >
                        <iframe
                            :src="previewDoc.preview_url!"
                            class="h-full w-full"
                            frameborder="0"
                            @error="pdfLoadError = true"
                        />

                        <div
                            v-if="pdfLoadError"
                            class="flex h-full items-center justify-center p-6 text-center text-sm text-muted-foreground"
                        >
                            Failed to load preview. Use Open in New Tab.
                        </div>
                    </div>

                    <div
                        v-else
                        class="flex min-h-[40vh] items-center justify-center p-6 text-sm text-muted-foreground"
                    >
                        Preview is not supported for this file type.
                    </div>

                    <div
                        class="flex items-center justify-end gap-2 border-t border-slate-100 px-5 py-3"
                    >
                        <Button
                            v-if="canTakePreviewAction() && previewRequest"
                            size="sm"
                            :disabled="approvingId === previewRequest.id"
                            @click="approveRequest(previewRequest)"
                        >
                            Approve
                        </Button>
                        <Button
                            v-if="canTakePreviewAction() && previewRequest"
                            size="sm"
                            variant="destructive"
                            @click="openRejectFromPreview"
                        >
                            Invalidate
                        </Button>
                        <a
                            v-if="previewDoc?.preview_url"
                            :href="previewDoc.preview_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center rounded border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100"
                        >
                            Open in New Tab
                        </a>
                        <Button
                            size="sm"
                            variant="outline"
                            @click="closePreviewDoc"
                        >
                            Close
                        </Button>
                    </div>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
