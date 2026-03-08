<!-- resources/js/Pages/Company/Show.vue -->
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import InputError from '@/components/InputError.vue';
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
import { Textarea } from '@/components/ui/textarea';

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

import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

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

import { index, show } from '@/routes/companies';
import {
    destroy as destroyDoc,
    downloadBulk,
    download as downloadCompanyDocument,
    reject,
    unverify,
    verify,
} from '@/routes/companies/documents';

import {
    ArrowLeft,
    Building2,
    CheckCircle2,
    Download,
    Eye,
    FileArchive,
    FileText,
    MessageSquareText,
    MoreHorizontal,
    RotateCcw,
    Trash2,
    UserRound,
    XCircle,
} from 'lucide-vue-next';

type UserMini = { id?: number; name: string };

type CompanyDocument = {
    id: number;
    doc_type: string;
    status: string;
    original_name?: string | null;
    file_path?: string;
    mime_type?: string | null;
    created_at?: string | null;
    issued_at?: string | null;
    expires_at?: string | null;
    remarks?: string | null;
    uploader?: UserMini | null;
    verifier?: UserMini | null;
    verified_at?: string | null;
};

const props = defineProps<{
    company: {
        id: number;
        company_name: string;
        company_code?: string | null;
        company_email?: string | null;
        company_phone?: string | null;
        company_address?: string | null;
        business_type?: 'corporate' | 'sole_proprietorship' | null;
        registration_number?: string | null;
        status?: string | null;
        created_at?: string | null;
        updated_at_human?: string | null;
        creator?: { name: string } | null;
        updater?: { name: string } | null;
        authorized_representative_name?: string | null;
        authorized_representative_position?: string | null;
        authorized_representative_contact?: string | null;
        documents?: CompanyDocument[];
        // ── Logo ──────────────────────────────────────────────────
        logo?: string | null;         // relative path stored in DB
        logo_url?: string | null;     // resolved public URL from controller
    };
}>();

const company = computed(() => props.company);
const docs = computed(() => props.company.documents ?? []);

// ── Logo helpers ──────────────────────────────────────────────────
const logoError = ref(false);
const showLogo  = computed(() => !!company.value.logo_url && !logoError.value)

const companyInitials = computed(() =>
    (company.value.company_code ?? company.value.company_name ?? '')
        .replace(/[^A-Za-z0-9]/g, '')
        .slice(0, 2)
        .toUpperCase() || '??',
)

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Companies', href: index().url },
    { title: 'Company Details', href: show({ company: company.value.id }).url },
];

// ─── Helpers ──────────────────────────────────────────────────────────────────
function formatDate(date?: string | null) {
    if (!date) return '—';
    const d = new Date(date);
    if (Number.isNaN(d.getTime())) return '—';
    return d.toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function formatDateTime(date?: string | null) {
    if (!date) return '—';
    const d = new Date(date);
    if (Number.isNaN(d.getTime())) return '—';
    return d.toLocaleString(undefined, {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
    });
}

function humanize(text?: string | null) {
    if (!text) return '—';
    return text.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function isExpired(expiresAt?: string | null): boolean {
    if (!expiresAt) return false;
    const d = new Date(expiresAt);
    if (Number.isNaN(d.getTime())) return false;
    return d.getTime() < Date.now();
}

function statusVariant(
    status?: string | null,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'verified':
            return 'default';
        case 'pending':
        case 'docs_completed':
        case 'for_verification':
            return 'secondary';
        case 'invalid':
        case 'expired':
            return 'destructive';
        default:
            return 'outline';
    }
}

// ─── Download VERIFIED docs as ZIP ────────────────────────────────────────────
function downloadVerifiedZip() {
    const { url } = downloadBulk({ company: company.value.id });

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    form.style.display = 'none';

    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value =
        (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)
            ?.content ?? '';
    form.appendChild(csrf);

    document.body.appendChild(form);
    form.submit();

    setTimeout(() => {
        try { document.body.removeChild(form); } catch {}
    }, 1000);
}

const bulkConfirmOpen = ref(false);
const verifiedCount = computed(
    () => docs.value.filter((d) => d.status === 'verified').length,
);

function openBulkConfirm() { bulkConfirmOpen.value = true; }
function runBulkDownload() { bulkConfirmOpen.value = false; downloadVerifiedZip(); }

// ─── File preview helpers ──────────────────────────────────────────────────────
function fileUrl(doc: CompanyDocument): string {
    if (!doc.file_path) return '';
    return `/storage/${doc.file_path}`;
}

function isImage(doc: CompanyDocument): boolean {
    if (doc.mime_type) return doc.mime_type.startsWith('image/');
    const ext = (doc.original_name ?? doc.file_path ?? '').split('.').pop()?.toLowerCase();
    return ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext ?? '');
}

function isPdf(doc: CompanyDocument): boolean {
    if (doc.mime_type) return doc.mime_type === 'application/pdf';
    const ext = (doc.original_name ?? doc.file_path ?? '').split('.').pop()?.toLowerCase();
    return ext === 'pdf';
}

function canPreview(doc: CompanyDocument): boolean {
    return isImage(doc) || isPdf(doc);
}

// ─── Preview dialog ───────────────────────────────────────────────────────────
const previewOpen = ref(false);
const previewDoc = ref<CompanyDocument | null>(null);
const pdfLoadError = ref(false);

function openPreview(doc: CompanyDocument) {
    previewDoc.value = doc;
    pdfLoadError.value = false;
    previewOpen.value = true;
}

function closePreview() {
    previewOpen.value = false;
    previewDoc.value = null;
}

// ─── Action form ──────────────────────────────────────────────────────────────
const actionForm = useForm({});

// ─── Confirm dialog (row actions) ─────────────────────────────────────────────
type ConfirmAction = 'verify' | 'unverify' | 'delete' | 'download';
const confirmOpen = ref(false);
const confirmAction = ref<ConfirmAction>('verify');
const confirmDoc = ref<CompanyDocument | null>(null);

function openConfirm(action: ConfirmAction, doc: CompanyDocument) {
    confirmAction.value = action;
    confirmDoc.value = doc;
    confirmOpen.value = true;
}

function confirmTitle() {
    const doc = confirmDoc.value;
    if (!doc) return '';
    switch (confirmAction.value) {
        case 'verify':   return 'Verify document?';
        case 'unverify': return 'Unverify document?';
        case 'delete':   return 'Delete document?';
        case 'download': return 'Download document?';
    }
}

function confirmDescription() {
    const doc = confirmDoc.value;
    if (!doc) return '';
    const name = doc.original_name ?? humanize(doc.doc_type);
    switch (confirmAction.value) {
        case 'verify':   return `This will mark "${name}" as verified.`;
        case 'unverify': return `This will set "${name}" back to pending.`;
        case 'delete':   return `This will permanently remove "${name}" and delete the file.`;
        case 'download': return `This will open "${name}" in a new tab.`;
    }
}

function runConfirmedAction() {
    const doc = confirmDoc.value;
    if (!doc || actionForm.processing || rejectForm.processing) return;

    const urls = {
        verify:   verify({ company: company.value.id, document: doc.id }).url,
        unverify: unverify({ company: company.value.id, document: doc.id }).url,
        delete:   destroyDoc({ company: company.value.id, document: doc.id }).url,
        download: downloadCompanyDocument({ company: company.value.id, document: doc.id }).url,
    } as const;

    if (confirmAction.value === 'download') {
        window.open(urls.download, '_blank', 'noopener,noreferrer');
        confirmOpen.value = false;
        confirmDoc.value = null;
        return;
    }

    if (confirmAction.value === 'delete') {
        actionForm.delete(urls.delete, {
            preserveScroll: true,
            onSuccess: () => { confirmOpen.value = false; confirmDoc.value = null; },
        });
        return;
    }

    actionForm.patch(urls[confirmAction.value], {
        preserveScroll: true,
        onSuccess: () => { confirmOpen.value = false; confirmDoc.value = null; },
    });
}

// ─── Reject dialog + presets ─────────────────────────────────────────────────
const remarkPresets = [
    { value: 'missing_signature', label: 'Missing signature' },
    { value: 'blurred',           label: 'Blurred / unreadable file' },
    { value: 'wrong_document',    label: 'Wrong document uploaded' },
    { value: 'expired',           label: 'Expired document' },
    { value: 'mismatch_name',     label: 'Company name mismatch' },
    { value: 'mismatch_details',  label: 'Details mismatch / incomplete' },
    { value: 'needs_stamp',       label: 'Missing stamp / seal' },
    { value: 'missing_pages',     label: 'Missing pages / incomplete scan' },
    { value: 'reupload_pdf',      label: 'Please re-upload as PDF' },
    { value: 'other',             label: 'Other (write your own)' },
] as const;

const presetTextMap: Record<string, string> = {
    missing_signature: 'Missing signature. Please upload a signed copy.',
    blurred:           'The file is blurred/unreadable. Please upload a clearer scan/photo.',
    wrong_document:    'Wrong document uploaded. Please upload the correct document.',
    expired:           'Document appears expired. Please upload a valid/updated document.',
    mismatch_name:     'Company name does not match our records. Please upload the correct document.',
    mismatch_details:  'Some details are missing or do not match. Please review and re-upload.',
    needs_stamp:       'Missing stamp/seal. Please upload a stamped/sealed copy.',
    missing_pages:     'Incomplete document (missing pages). Please upload the complete file.',
    reupload_pdf:      'Please re-upload the document as a PDF for verification.',
    other:             '',
};

const selectedRemarkPreset = ref<string | null>(null);
const rejectOpen   = ref(false);
const rejectDocId  = ref<number | null>(null);
const rejectForm   = useForm<{ remarks: string }>({ remarks: '' });

function openReject(docId: number) {
    rejectDocId.value = docId;
    selectedRemarkPreset.value = null;
    rejectForm.reset();
    rejectForm.clearErrors();
    rejectOpen.value = true;
}

function applyPreset(value: string) {
    selectedRemarkPreset.value = value;
    const text = presetTextMap[value] ?? '';
    if (value !== 'other') rejectForm.remarks = text;
}

function submitReject() {
    if (!rejectDocId.value || rejectForm.processing) return;
    rejectForm.patch(
        reject({ company: company.value.id, document: rejectDocId.value }).url,
        {
            preserveScroll: true,
            onSuccess: () => {
                rejectOpen.value = false;
                rejectDocId.value = null;
                selectedRemarkPreset.value = null;
                rejectForm.reset();
            },
        },
    );
}

const repHasAny = computed(() => {
    const c = company.value;
    return !!(
        c.authorized_representative_name ||
        c.authorized_representative_position ||
        c.authorized_representative_contact
    );
});
</script>

<template>
    <Head :title="company.company_name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="w-full px-4 py-6 sm:px-6">
            <div class="mx-auto w-full max-w-6xl space-y-4">

                <!-- ── Page header ──────────────────────────────────────── -->
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-center gap-4">

                        <!-- Company logo / initials avatar -->
                        <div class="relative h-14 w-14 shrink-0 overflow-hidden rounded-xl border-2 bg-muted shadow-sm">
                            <img
                                v-if="showLogo"
                                :src="company.logo_url!"
                                :alt="company.company_name"
                                class="h-full w-full object-cover"
                                @error="logoError = true"
                            />
                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center bg-primary/10"
                            >
                                <span class="text-lg font-bold text-primary select-none">
                                    {{ companyInitials }}
                                </span>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <h1 class="text-2xl leading-tight font-semibold">
                                {{ company.company_name }}
                            </h1>
                            <p class="text-sm text-muted-foreground">
                                Review company profile, representative info, and submitted documents.
                            </p>
                        </div>
                    </div>

                    <Button as-child variant="outline" size="sm">
                        <Link :href="index().url" class="cursor-pointer">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Back
                        </Link>
                    </Button>
                </div>

                <!-- Summary badges -->
                <div class="flex flex-wrap items-center gap-2">
                    <Badge variant="outline">Code: {{ company.company_code ?? '—' }}</Badge>
                    <Badge :variant="statusVariant(company.status ?? null)">{{ humanize(company.status) }}</Badge>
                    <Badge variant="outline">
                        {{ company.business_type ? humanize(company.business_type) : '—' }}
                    </Badge>
                </div>

                <!-- ── Top grid ─────────────────────────────────────────── -->
                <div class="grid gap-4 lg:grid-cols-3">

                    <!-- Company details card -->
                    <Card class="lg:col-span-2">
                        <CardHeader>
                            <div class="flex items-center gap-3">

                                <!-- Logo beside the card title (larger display) -->
                                <div class="relative h-12 w-12 shrink-0 overflow-hidden rounded-lg border bg-muted shadow-sm">
                                    <img
                                        v-if="showLogo"
                                        :src="company.logo_url!"
                                        :alt="company.company_name"
                                        class="h-full w-full object-cover"
                                        @error="logoError = true"
                                    />
                                    <div
                                        v-else
                                        class="flex h-full w-full items-center justify-center bg-primary/10"
                                    >
                                        <Building2 class="h-5 w-5 text-primary/60" />
                                    </div>
                                </div>

                                <div>
                                    <CardTitle>Company Details</CardTitle>
                                    <CardDescription>Basic information and contact details.</CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <Table class="w-full">
                                <TableBody>
                                    <TableRow>
                                        <TableCell class="w-56 py-3 text-sm font-medium text-muted-foreground">Email</TableCell>
                                        <TableCell class="py-3">{{ company.company_email ?? '—' }}</TableCell>
                                    </TableRow>
                                    <TableRow>
                                        <TableCell class="w-56 py-3 text-sm font-medium text-muted-foreground">Phone</TableCell>
                                        <TableCell class="py-3">{{ company.company_phone ?? '—' }}</TableCell>
                                    </TableRow>
                                    <TableRow>
                                        <TableCell class="w-56 py-3 text-sm font-medium text-muted-foreground">Address</TableCell>
                                        <TableCell class="py-3">{{ company.company_address ?? '—' }}</TableCell>
                                    </TableRow>
                                    <TableRow>
                                        <TableCell class="w-56 py-3 text-sm font-medium text-muted-foreground">Registration No.</TableCell>
                                        <TableCell class="py-3">{{ company.registration_number ?? '—' }}</TableCell>
                                    </TableRow>
                                    <TableRow>
                                        <TableCell class="w-56 py-3 text-sm font-medium text-muted-foreground">Created</TableCell>
                                        <TableCell class="py-3">
                                            {{ formatDate(company.created_at) }} • {{ company.creator?.name ?? 'N/A' }}
                                        </TableCell>
                                    </TableRow>
                                    <TableRow>
                                        <TableCell class="w-56 py-3 text-sm font-medium text-muted-foreground">Last Updated</TableCell>
                                        <TableCell class="py-3">
                                            {{ company.updated_at_human ?? '—' }} • {{ company.updater?.name ?? 'N/A' }}
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>

                    <!-- Representative card — unchanged -->
                    <Card>
                        <CardHeader>
                            <div class="flex items-center gap-2">
                                <UserRound class="h-5 w-5 text-muted-foreground" />
                                <div>
                                    <CardTitle>Representative</CardTitle>
                                    <CardDescription>Who to contact for coordination.</CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div class="space-y-1">
                                <div class="text-xs text-muted-foreground">Name</div>
                                <div class="text-sm font-medium">{{ company.authorized_representative_name ?? '—' }}</div>
                            </div>
                            <div class="space-y-1">
                                <div class="text-xs text-muted-foreground">Position</div>
                                <div class="text-sm">{{ company.authorized_representative_position ?? '—' }}</div>
                            </div>
                            <div class="space-y-1">
                                <div class="text-xs text-muted-foreground">Contact</div>
                                <div class="text-sm">{{ company.authorized_representative_contact ?? '—' }}</div>
                            </div>
                            <div v-if="!repHasAny" class="rounded-md border p-3 text-xs text-muted-foreground">
                                No representative details provided.
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Documents Card — unchanged from original -->
                <Card>
                    <CardHeader>
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-center gap-2">
                                <FileText class="h-5 w-5 text-muted-foreground" />
                                <div>
                                    <CardTitle>Document Details</CardTitle>
                                    <CardDescription>Review, verify, preview, or download submitted documents.</CardDescription>
                                </div>
                            </div>

                            <Button
                                v-if="docs.length > 0"
                                variant="outline"
                                size="sm"
                                class="shrink-0"
                                @click="openBulkConfirm"
                            >
                                <FileArchive class="mr-2 h-4 w-4" />
                                Download All (Verified Only)
                            </Button>
                        </div>
                    </CardHeader>

                    <CardContent>
                        <div class="w-full overflow-x-auto">
                            <Table class="min-w-[1200px]">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead class="whitespace-nowrap">Type</TableHead>
                                        <TableHead class="whitespace-nowrap">Status</TableHead>
                                        <TableHead class="whitespace-nowrap">File</TableHead>
                                        <TableHead class="whitespace-nowrap">Issued At</TableHead>
                                        <TableHead class="whitespace-nowrap">Expires At</TableHead>
                                        <TableHead class="whitespace-nowrap">Remarks</TableHead>
                                        <TableHead class="whitespace-nowrap">Uploaded By</TableHead>
                                        <TableHead class="whitespace-nowrap">Uploaded At</TableHead>
                                        <TableHead class="whitespace-nowrap">Verified By</TableHead>
                                        <TableHead class="whitespace-nowrap">Verified At</TableHead>
                                        <TableHead class="w-20 text-right whitespace-nowrap">Action</TableHead>
                                    </TableRow>
                                </TableHeader>

                                <TableBody>
                                    <TableRow v-for="doc in docs" :key="doc.id">
                                        <TableCell class="whitespace-nowrap">{{ humanize(doc.doc_type) }}</TableCell>

                                        <TableCell class="whitespace-nowrap">
                                            <Badge :variant="statusVariant(doc.status)">{{ humanize(doc.status) }}</Badge>
                                            <Badge v-if="isExpired(doc.expires_at)" variant="destructive" class="ml-2">Expired</Badge>
                                        </TableCell>

                                        <TableCell class="max-w-[320px]">
                                            <button
                                                v-if="canPreview(doc)"
                                                class="flex w-full items-center gap-1.5 truncate text-left text-sm text-primary underline-offset-2 hover:underline"
                                                :title="doc.original_name ?? ''"
                                                @click="openPreview(doc)"
                                            >
                                                <Eye class="h-3.5 w-3.5 shrink-0" />
                                                <span class="truncate">{{ doc.original_name ?? '—' }}</span>
                                            </button>
                                            <span v-else class="block w-full truncate text-sm" :title="doc.original_name ?? ''">
                                                {{ doc.original_name ?? '—' }}
                                            </span>
                                        </TableCell>

                                        <TableCell class="whitespace-nowrap">{{ formatDate(doc.issued_at ?? null) }}</TableCell>

                                        <TableCell class="whitespace-nowrap">
                                            <span :class="isExpired(doc.expires_at) ? 'font-medium text-destructive' : ''">
                                                {{ formatDate(doc.expires_at ?? null) }}
                                            </span>
                                        </TableCell>

                                        <TableCell class="w-28 whitespace-nowrap">
                                            <template v-if="doc.remarks">
                                                <Popover>
                                                    <PopoverTrigger as-child>
                                                        <Button variant="outline" size="sm" class="h-8">
                                                            <MessageSquareText class="mr-2 h-4 w-4" />
                                                            View
                                                        </Button>
                                                    </PopoverTrigger>
                                                    <PopoverContent align="start" class="w-80">
                                                        <div class="space-y-2">
                                                            <div class="text-sm font-medium">Remarks</div>
                                                            <div class="text-sm whitespace-pre-wrap text-muted-foreground">{{ doc.remarks }}</div>
                                                            <div class="text-xs text-muted-foreground">
                                                                {{ humanize(doc.doc_type) }} • {{ humanize(doc.status) }}
                                                            </div>
                                                        </div>
                                                    </PopoverContent>
                                                </Popover>
                                            </template>
                                            <template v-else>
                                                <span class="text-muted-foreground">—</span>
                                            </template>
                                        </TableCell>

                                        <TableCell class="whitespace-nowrap">{{ doc.uploader?.name ?? '—' }}</TableCell>
                                        <TableCell class="whitespace-nowrap">{{ formatDateTime(doc.created_at ?? null) }}</TableCell>
                                        <TableCell class="whitespace-nowrap">{{ doc.verifier?.name ?? '—' }}</TableCell>
                                        <TableCell class="whitespace-nowrap">{{ formatDateTime(doc.verified_at ?? null) }}</TableCell>

                                        <TableCell class="text-right whitespace-nowrap">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger as-child>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        :disabled="actionForm.processing || rejectForm.processing"
                                                    >
                                                        <MoreHorizontal class="h-4 w-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>

                                                <DropdownMenuContent align="end" class="w-56">
                                                    <DropdownMenuLabel>Actions</DropdownMenuLabel>
                                                    <DropdownMenuSeparator />

                                                    <DropdownMenuItem v-if="canPreview(doc)" class="cursor-pointer" @click="openPreview(doc)">
                                                        <Eye class="mr-2 h-4 w-4" />
                                                        Preview
                                                    </DropdownMenuItem>

                                                    <DropdownMenuSeparator v-if="canPreview(doc)" />

                                                    <DropdownMenuItem
                                                        v-if="doc.status === 'verified'"
                                                        class="cursor-pointer"
                                                        @click="openConfirm('unverify', doc)"
                                                    >
                                                        <RotateCcw class="mr-2 h-4 w-4" />
                                                        Unverify
                                                    </DropdownMenuItem>

                                                    <DropdownMenuItem v-else class="cursor-pointer" @click="openConfirm('verify', doc)">
                                                        <CheckCircle2 class="mr-2 h-4 w-4" />
                                                        Verify
                                                    </DropdownMenuItem>

                                                    <DropdownMenuItem class="cursor-pointer" @click="openReject(doc.id)">
                                                        <XCircle class="mr-2 h-4 w-4" />
                                                        Invalid (remarks)
                                                    </DropdownMenuItem>

                                                    <DropdownMenuSeparator />

                                                    <DropdownMenuItem class="cursor-pointer" @click="openConfirm('download', doc)">
                                                        <Download class="mr-2 h-4 w-4" />
                                                        Download
                                                    </DropdownMenuItem>

                                                    <DropdownMenuItem
                                                        class="cursor-pointer text-destructive focus:text-destructive"
                                                        @click="openConfirm('delete', doc)"
                                                    >
                                                        <Trash2 class="mr-2 h-4 w-4" />
                                                        Delete
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </TableCell>
                                    </TableRow>

                                    <TableRow v-if="docs.length === 0">
                                        <TableCell colspan="11" class="py-8 text-center text-muted-foreground">
                                            No documents uploaded yet.
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- ── File Preview Dialog ──────────────────────────────────────────── -->
        <Dialog v-model:open="previewOpen">
            <DialogContent class="flex max-h-[90vh] w-full max-w-4xl flex-col gap-0 overflow-hidden p-0">
                <DialogHeader class="shrink-0 border-b px-6 py-4">
                    <div class="flex items-center justify-between gap-4">
                        <div class="min-w-0 space-y-0.5">
                            <DialogTitle class="truncate text-base">
                                {{ previewDoc?.original_name ?? humanize(previewDoc?.doc_type) }}
                            </DialogTitle>
                            <DialogDescription class="flex items-center gap-2 text-xs">
                                <Badge :variant="statusVariant(previewDoc?.status)" class="text-[10px]">
                                    {{ humanize(previewDoc?.status) }}
                                </Badge>
                                <span>{{ humanize(previewDoc?.doc_type) }}</span>
                                <span v-if="previewDoc?.expires_at" class="text-muted-foreground">
                                    • Expires: {{ formatDate(previewDoc?.expires_at) }}
                                </span>
                            </DialogDescription>
                        </div>

                        <Button v-if="previewDoc" variant="outline" size="sm" class="shrink-0" as-child>
                            <a :href="downloadCompanyDocument({ company: company.id, document: previewDoc.id }).url" target="_blank" rel="noopener noreferrer">
                                <Download class="mr-2 h-4 w-4" />Download
                            </a>
                        </Button>
                    </div>
                </DialogHeader>

                <div class="relative flex-1 overflow-auto bg-muted/30">
                    <div v-if="previewDoc && isImage(previewDoc)" class="flex min-h-[50vh] items-center justify-center p-6">
                        <img
                            :src="fileUrl(previewDoc)"
                            :alt="previewDoc.original_name ?? previewDoc.doc_type"
                            class="max-h-[70vh] max-w-full rounded-md object-contain shadow-md"
                            @error="(e) => ((e.target as HTMLImageElement).src = '')"
                        />
                    </div>

                    <div v-else-if="previewDoc && isPdf(previewDoc)" class="h-[70vh] w-full">
                        <iframe v-if="!pdfLoadError" :src="fileUrl(previewDoc)" class="h-full w-full border-0" @error="pdfLoadError = true" />
                        <div v-else class="flex h-full flex-col items-center justify-center gap-4 text-muted-foreground">
                            <FileText class="h-12 w-12 opacity-30" />
                            <p class="text-sm">Your browser cannot preview this PDF inline.</p>
                            <Button as-child variant="secondary" size="sm">
                                <a :href="fileUrl(previewDoc)" target="_blank" rel="noopener noreferrer">
                                    <Eye class="mr-2 h-4 w-4" />Open in new tab
                                </a>
                            </Button>
                        </div>
                    </div>
                </div>

                <DialogFooter class="shrink-0 border-t px-6 py-3">
                    <p class="flex-1 text-xs text-muted-foreground">
                        Issued: {{ formatDate(previewDoc?.issued_at ?? null) }} •
                        Expires: {{ formatDate(previewDoc?.expires_at ?? null) }} •
                        Uploaded: {{ formatDateTime(previewDoc?.created_at ?? null) }}
                    </p>
                    <Button variant="outline" size="sm" @click="closePreview">Close</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- ── Reject dialog ────────────────────────────────────────────────── -->
        <Dialog v-model:open="rejectOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Invalid Document</DialogTitle>
                    <DialogDescription>
                        Choose a preset remark (optional) then edit the message if needed.
                    </DialogDescription>
                </DialogHeader>

                <Separator />

                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label>Remarks Preset</Label>
                        <Select
                            :model-value="selectedRemarkPreset ?? undefined"
                            @update:model-value="(v) => applyPreset(String(v))"
                        >
                            <SelectTrigger><SelectValue placeholder="Select a preset..." /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="p in remarkPresets" :key="p.value" :value="p.value">
                                    {{ p.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-2">
                        <Label>Remarks *</Label>
                        <Textarea v-model="rejectForm.remarks" placeholder="Reason for invalid..." />
                        <InputError class="mt-1" :message="rejectForm.errors.remarks" />
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <Button variant="outline" @click="rejectOpen = false" :disabled="rejectForm.processing">Cancel</Button>
                    <Button variant="destructive" @click="submitReject" :disabled="rejectForm.processing">
                        {{ rejectForm.processing ? 'Invalidating...' : 'Invalid' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- ── Row action confirm dialog ──────────────────────────────────────── -->
        <AlertDialog v-model:open="confirmOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{{ confirmTitle() }}</AlertDialogTitle>
                    <AlertDialogDescription>{{ confirmDescription() }}</AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel :disabled="actionForm.processing" @click="confirmOpen = false">Cancel</AlertDialogCancel>
                    <AlertDialogAction :disabled="actionForm.processing" @click="runConfirmedAction">
                        {{ actionForm.processing ? 'Processing...' : 'Continue' }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <!-- ── Bulk Download Confirm ───────────────────────────────────────────── -->
        <AlertDialog v-model:open="bulkConfirmOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Download verified documents?</AlertDialogTitle>
                    <AlertDialogDescription>
                        This will download a ZIP containing only verified documents for this company.
                        <span v-if="verifiedCount > 0">({{ verifiedCount }} verified)</span>
                        <span v-else> No verified documents found.</span>
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="bulkConfirmOpen = false">Cancel</AlertDialogCancel>
                    <AlertDialogAction :disabled="verifiedCount === 0" @click="runBulkDownload">Continue</AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
