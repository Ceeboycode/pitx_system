<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import InertiaPagination from '@/components/InertiaPagination.vue';
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
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Textarea } from '@/components/ui/textarea';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    RiCheckboxCircleLine as CheckCircle2,
    RiCloseCircleLine as XCircle,
    RiExternalLinkLine as ExternalLink,
    RiEyeLine as Eye,
    RiFileDownloadLine as FileDown,
    RiFilter2Line as Filter,
    RiMore2Line as MoreHorizontal,
} from 'vue-remix-icons';
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
    filters: { status: string; search: string | null };
}>();

const selectedStatus = ref<'all' | 'pending' | 'approved' | 'rejected'>(
    (props.filters.status as 'all' | 'pending' | 'approved' | 'rejected') ?? 'pending',
);
const filterOpen = ref(false);
const activeFilterCount = computed(() => selectedStatus.value === 'all' ? 0 : 1);

const rejectModalOpen = ref(false);
const selected = ref<ChangeRequest | null>(null);
const approvingId = ref<number | null>(null);

const previewOpen = ref(false);
const previewDoc = ref<SupportingDocument | null>(null);
const pdfLoadError = ref(false);
const previewRequest = ref<ChangeRequest | null>(null);

const rejectForm = useForm({ rejection_reason: '' });

function applyFilters() {
    router.get(
        '/company-profile-change-requests',
        {
            search: props.filters.search || undefined,
            status: selectedStatus.value === 'all' ? undefined : selectedStatus.value,
        },
        { preserveScroll: true, preserveState: true, replace: true, only: ['requests', 'filters'] },
    );
    filterOpen.value = false;
}

function clearFilters() {
    selectedStatus.value = 'all';
    applyFilters();
}

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

function openPreviewDoc(doc: SupportingDocument, request?: ChangeRequest) {
    previewDoc.value = doc;
    pdfLoadError.value = false;
    previewRequest.value = request ?? null;
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
        <div class="flex h-full min-h-0 w-full flex-1 flex-col gap-4">
            <Card class="min-h-0 min-w-0 flex-1 lg:h-full">
                <CardHeader class="flex flex-row gap-2">
                    <div class="flex flex-col">
                        <CardTitle class="flex items-center gap-2"><span class="font-semibold">Change Requests</span></CardTitle>
                        <CardDescription>Review and approve external company profile updates.</CardDescription>
                    </div>
                </CardHeader>

                <CardContent class="flex min-h-0 flex-1 flex-col space-y-4 py-2">
                    <div class="flex flex-row gap-2 lg:items-center lg:justify-between">
                        <div class="w-full">
                            <SearchInput
                                :route="`/company-profile-change-requests?status=${selectedStatus === 'all' ? '' : selectedStatus}`"
                                :initial-value="props.filters.search"
                                placeholder="Search companies or requesters..."
                                :only="['requests', 'filters', 'flash']"
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

                    <Card :class="['flex min-h-0 max-h-fit flex-1 flex-col overflow-hidden border border-custom-bg-dark py-0 shadow-none dark:border-custom-bg-light dark:inset-shadow-none', requests.data.length ? 'border-solid' : 'border-dashed']">
                        <div v-if="requests.data.length" class="flex min-h-0 flex-1 flex-col overflow-hidden">
                            <div class="shrink-0 rounded-t-md bg-custom-bg dark:bg-custom-bg-light">
                                <div class="grid grid-cols-[1.4fr_1.2fr_.8fr_2fr_5rem] gap-2 border-b border-custom-bg-dark dark:border-custom-bg-light">
                                    <div class="flex h-10 items-center pl-3 text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Company</div>
                                    <div class="flex h-10 items-center text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Requester</div>
                                    <div class="flex h-10 items-center text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Status</div>
                                    <div class="flex h-10 items-center text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Requested Changes</div>
                                    <div class="flex h-10 items-center justify-end pr-3 text-xs font-semibold uppercase tracking-widest text-custom-shadow/80">Actions</div>
                                </div>
                            </div>

                            <div class="no-scrollbar min-h-0 flex-1 overflow-y-auto">
                                <div
                                    v-for="(item, rowIndex) in requests.data"
                                    :key="item.id"
                                    :class="['grid grid-cols-[1.4fr_1.2fr_.8fr_2fr_5rem] items-center gap-2 border-b border-custom-bg-dark text-custom-shadow/80 transition-colors hover:bg-custom-secondary/10 hover:text-custom-shadow dark:border-custom-bg-light', rowIndex === requests.data.length - 1 ? 'rounded-b-md border-b-0' : '']"
                                >
                                    <div class="flex min-w-0 flex-col py-2 pl-3">
                                        <span class="truncate font-semibold capitalize">{{ item.company.company_name }}</span>
                                        <span class="truncate font-mono text-xs text-custom-shadow/70">{{ item.company.company_code ?? '—' }}</span>
                                    </div>
                                    <div class="flex min-w-0 flex-col py-2">
                                        <span class="truncate text-sm">{{ item.requester?.name ?? '—' }}</span>
                                        <span class="truncate text-xs text-custom-shadow/70">{{ item.requester?.email ?? '—' }}</span>
                                    </div>
                                    <div class="flex py-2"><Badge :variant="badgeVariant(item.status)" class="capitalize">{{ item.status }}</Badge></div>
                                    <div class="flex min-w-0 flex-wrap gap-1 py-2 text-xs">
                                        <span v-for="(_, key) in item.requested_values" :key="key" class="rounded bg-custom-bg px-2 py-1 text-custom-shadow dark:bg-custom-bg-light">{{ normalizeFieldName(String(key)) }}</span>
                                        <span v-if="item.logo_change?.has_change" class="rounded bg-blue-100 px-2 py-1 text-blue-700">Company Logo</span>
                                        <span v-for="(doc, index) in item.supporting_documents ?? []" :key="index" class="rounded bg-rose-100 px-2 py-1 text-rose-700">{{ humanize(doc.doc_type) }}</span>
                                        <span v-if="!Object.keys(item.requested_values).length && !item.logo_change?.has_change && !(item.supporting_documents ?? []).length">—</span>
                                    </div>
                                    <div class="flex justify-end py-2 pr-3" @click.stop>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child><Button variant="table-more" size="icon-more"><MoreHorizontal class="h-4 w-4" /></Button></DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuLabel>{{ item.company.company_name }}</DropdownMenuLabel>
                                                <template v-if="item.status === 'pending' && !primaryPreviewDoc(item)">
                                                    <DropdownMenuItem class="group" :disabled="approvingId === item.id" @click="approveRequest(item)"><CheckCircle2 class="h-4 w-4 text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light" />Approve</DropdownMenuItem>
                                                    <DropdownMenuItem class="group" @click="openReject(item)"><XCircle class="h-4 w-4 text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light" />Reject</DropdownMenuItem>
                                                    <DropdownMenuSeparator />
                                                </template>
                                                <DropdownMenuItem v-if="item.company.show_url" as-child class="group"><a :href="item.company.show_url"><ExternalLink class="h-4 w-4 text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light" />View Company</a></DropdownMenuItem>
                                                <DropdownMenuItem v-if="primaryPreviewDoc(item)" class="group" @click="openPrimaryPreview(item)"><Eye class="h-4 w-4 text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light" />Preview Document</DropdownMenuItem>
                                                <DropdownMenuItem v-if="primaryPreviewDoc(item)" class="group" @click="downloadPrimary(item)"><FileDown class="h-4 w-4 text-custom-shadow transition-all duration-300 group-hover:text-custom-bg-light" />Download</DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="flex min-h-0 flex-1 items-center justify-center p-6 text-center">
                            <div class="flex w-full max-w-md flex-col items-center gap-2">
                                <img :src="emptyRafikiUrl" alt="" class="w-1/3 object-contain opacity-90" aria-hidden="true" />
                                <div class="space-y-1"><p class="text-base font-semibold text-custom-shadow">No change requests found</p><p class="text-sm text-custom-shadow/80">Try adjusting your search or filters.</p></div>
                            </div>
                        </div>
                    </Card>

                    <InertiaPagination v-if="requests.links?.length" :links="requests.links" :meta="{ from: requests.from, to: requests.to, total: requests.total }" />
                </CardContent>
            </Card>
        </div>

        
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
                        placeholder="Explain why this request is being rejected..."
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

                
                <div v-else-if="previewDoc && isPdfDoc(previewDoc)" class="h-[70vh] w-full bg-muted/20">
                    <iframe :src="previewDoc.preview_url!" class="h-full w-full" frameborder="0" @error="pdfLoadError = true" />
                    <div v-if="pdfLoadError" class="flex h-full items-center justify-center p-6 text-center text-sm text-muted-foreground">
                        Failed to load preview. Use "Open in New Tab" below.
                    </div>
                </div>

                
                <div v-else class="flex min-h-[40vh] items-center justify-center p-6 text-sm text-muted-foreground">
                    Preview not supported for this file type.
                </div>

                
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
