<script setup lang="ts">
import ArchiveCompanyDialog from '@/components/company/ArchiveCompanyDialog.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

import { can } from '@/lib/can';

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
import { Checkbox } from '@/components/ui/checkbox';
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
    Archive,
    ArrowLeft,
    Building2,
    CheckCircle2,
    CircleAlert,
    Clock3,
    Download,
    Eye,
    FileArchive,
    Files,
    FileText,
    MessageSquareText,
    MoreHorizontal,
    RotateCcw,
    UserRound,
    XCircle,
} from 'lucide-vue-next';

/* ── Types ──────────────────────────────────────────────────────── */

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

/* ── Props ───────────────────────────────────────────────────────── */

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
        logo?: string | null;
        logo_url?: string | null;
    };
}>();

const company = computed(() => props.company);
const docs = computed(() => props.company.documents ?? []);

/* ── Permissions ─────────────────────────────────────────────────── */

const canArchiveCompany = computed(() => can('companies.archive'));

/* ── Logo helpers ────────────────────────────────────────────────── */

const logoError = ref(false);
const showLogo = computed(() => !!company.value.logo_url && !logoError.value);

const companyInitials = computed(
    () =>
        (company.value.company_code ?? company.value.company_name ?? '')
            .replace(/[^A-Za-z0-9]/g, '')
            .slice(0, 2)
            .toUpperCase() || '??',
);

/* ── Breadcrumbs ─────────────────────────────────────────────────── */

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Companies', href: index().url },
    { title: 'Company Details', href: show({ company: company.value.id }).url },
];

/* ── Archive dialog ──────────────────────────────────────────────── */

const archiveOpen = ref(false);

/* ── Helpers ─────────────────────────────────────────────────────── */

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

function statusClass(status?: string | null): string {
    switch (status) {
        case 'verified':
            return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'for_verification':
        case 'docs_completed':
            return 'bg-violet-100 text-violet-700 border-violet-200';
        case 'pending':
            return 'bg-amber-100 text-amber-700 border-amber-200';
        case 'invalid':
        case 'expired':
        case 'rejected':
            return 'bg-rose-100 text-rose-600 border-rose-200';
        default:
            return 'bg-slate-100 text-slate-500 border-0';
    }
}

function statusDot(status?: string | null): string {
    switch (status) {
        case 'verified':
            return 'bg-emerald-500';
        case 'for_verification':
        case 'docs_completed':
            return 'bg-violet-500';
        case 'pending':
            return 'bg-amber-500';
        case 'invalid':
        case 'expired':
        case 'rejected':
            return 'bg-rose-500';
        default:
            return 'bg-slate-400';
    }
}

/* ── Download verified ZIP ───────────────────────────────────────── */

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
        try {
            document.body.removeChild(form);
        } catch {}
    }, 1000);
}

const bulkConfirmOpen = ref(false);
const verifiedCount = computed(
    () => docs.value.filter((d) => d.status === 'verified').length,
);
function openBulkConfirm() {
    bulkConfirmOpen.value = true;
}
function runBulkDownload() {
    bulkConfirmOpen.value = false;
    downloadVerifiedZip();
}

/* ── File preview helpers ────────────────────────────────────────── */

function fileUrl(doc: CompanyDocument): string {
    if (!doc.file_path) return '';
    return `/storage/${doc.file_path}`;
}
function isImage(doc: CompanyDocument): boolean {
    if (doc.mime_type) return doc.mime_type.startsWith('image/');
    const ext = (doc.original_name ?? doc.file_path ?? '')
        .split('.')
        .pop()
        ?.toLowerCase();
    return ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext ?? '');
}
function isPdf(doc: CompanyDocument): boolean {
    if (doc.mime_type) return doc.mime_type === 'application/pdf';
    const ext = (doc.original_name ?? doc.file_path ?? '')
        .split('.')
        .pop()
        ?.toLowerCase();
    return ext === 'pdf';
}
function canPreview(doc: CompanyDocument): boolean {
    return isImage(doc) || isPdf(doc);
}

/* ── Preview dialog ──────────────────────────────────────────────── */

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

/* ── Action form ─────────────────────────────────────────────────── */

const actionForm = useForm({});

/* ── Confirm dialog ──────────────────────────────────────────────── */

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
    switch (confirmAction.value) {
        case 'verify':
            return 'Verify document?';
        case 'unverify':
            return 'Unverify document?';
        case 'delete':
            return 'Delete document?';
        case 'download':
            return 'Download document?';
    }
}

function confirmDescription() {
    const doc = confirmDoc.value;
    if (!doc) return '';
    const name = doc.original_name ?? humanize(doc.doc_type);
    switch (confirmAction.value) {
        case 'verify':
            return `This will mark "${name}" as verified.`;
        case 'unverify':
            return `This will set "${name}" back to pending.`;
        case 'delete':
            return `This will permanently remove "${name}" and delete the file.`;
        case 'download':
            return `This will open "${name}" in a new tab.`;
    }
}

function runConfirmedAction() {
    const doc = confirmDoc.value;
    if (!doc || actionForm.processing || rejectForm.processing) return;
    const urls = {
        verify: verify({ company: company.value.id, document: doc.id }).url,
        unverify: unverify({ company: company.value.id, document: doc.id }).url,
        delete: destroyDoc({ company: company.value.id, document: doc.id }).url,
        download: downloadCompanyDocument({
            company: company.value.id,
            document: doc.id,
        }).url,
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
            onSuccess: () => {
                confirmOpen.value = false;
                confirmDoc.value = null;
            },
        });
        return;
    }
    actionForm.patch(urls[confirmAction.value], {
        preserveScroll: true,
        onSuccess: () => {
            confirmOpen.value = false;
            confirmDoc.value = null;
        },
    });
}

/* ── Reject dialog ───────────────────────────────────────────────── */

const remarkPresets = [
    {
        value: 'missing_signature',
        label: 'Missing signature',
        text: 'Missing signature. Please upload a signed copy.',
    },
    {
        value: 'blurred',
        label: 'Blurred / unreadable file',
        text: 'The file is blurred or unreadable. Please upload a clearer scan or photo.',
    },
    {
        value: 'wrong_document',
        label: 'Wrong document uploaded',
        text: 'Wrong document uploaded. Please upload the correct document.',
    },
    {
        value: 'expired',
        label: 'Expired document',
        text: 'Document appears expired. Please upload a valid or updated document.',
    },
    {
        value: 'mismatch_name',
        label: 'Company name mismatch',
        text: 'Company name does not match our records. Please upload the correct document.',
    },
    {
        value: 'mismatch_details',
        label: 'Details mismatch / incomplete',
        text: 'Some details are missing or do not match. Please review and re-upload.',
    },
    {
        value: 'needs_stamp',
        label: 'Missing stamp / seal',
        text: 'Missing stamp or seal. Please upload a stamped or sealed copy.',
    },
    {
        value: 'missing_pages',
        label: 'Missing pages / incomplete scan',
        text: 'Incomplete document (missing pages). Please upload the complete file.',
    },
    {
        value: 'reupload_pdf',
        label: 'Please re-upload as PDF',
        text: 'Please re-upload the document as a PDF for verification.',
    },
] as const;

type RemarkPresetValue = (typeof remarkPresets)[number]['value'];

const selectedPresets = ref<RemarkPresetValue[]>([]);
const rejectOpen = ref(false);
const rejectDocId = ref<number | null>(null);
const rejectForm = useForm<{ remarks: string }>({ remarks: '' });

/* Auto-build the remarks textarea from selected presets */
watch(
    selectedPresets,
    (vals) => {
        const lines = vals
            .map((v) => remarkPresets.find((p) => p.value === v)?.text ?? '')
            .filter(Boolean);
        rejectForm.remarks = lines.join('\n');
    },
    { deep: true },
);

function togglePreset(value: RemarkPresetValue) {
    const idx = selectedPresets.value.indexOf(value);
    if (idx === -1) {
        selectedPresets.value = [...selectedPresets.value, value];
    } else {
        selectedPresets.value = selectedPresets.value.filter(
            (v) => v !== value,
        );
    }
}

function openReject(docId: number) {
    rejectDocId.value = docId;
    selectedPresets.value = [];
    rejectForm.reset();
    rejectForm.clearErrors();
    rejectOpen.value = true;
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
                selectedPresets.value = [];
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

const totalDocs = computed(() => docs.value.length);
const verifiedDocs = computed(
    () => docs.value.filter((doc) => doc.status === 'verified').length,
);
const pendingDocs = computed(
    () =>
        docs.value.filter((doc) =>
            ['pending', 'for_verification'].includes(doc.status),
        ).length,
);
const flaggedDocs = computed(
    () =>
        docs.value.filter(
            (doc) =>
                ['invalid', 'expired', 'rejected'].includes(doc.status) ||
                isExpired(doc.expires_at),
        ).length,
);
</script>

<template>
    <Head :title="company.company_name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="w-full px-4 py-6 sm:px-6">
            <div class="mx-auto w-full max-w-6xl space-y-6">
                <!-- ── Page header ────────────────────────────────── -->
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div
                            class="relative h-14 w-14 shrink-0 overflow-hidden rounded-xl border-2 bg-muted shadow-sm"
                        >
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
                                <span
                                    class="text-lg font-bold text-primary select-none"
                                    >{{ companyInitials }}</span
                                >
                            </div>
                        </div>

                        <div class="space-y-1">
                            <h1
                                class="text-2xl leading-tight font-bold tracking-tight"
                            >
                                {{ company.company_name }}
                            </h1>
                            <p class="text-sm text-muted-foreground">
                                Review company profile, representative info, and
                                submitted documents.
                            </p>
                        </div>
                    </div>

                    <!-- Header actions -->
                    <div class="flex shrink-0 items-center gap-2">
                        <Button
                            as-child
                            variant="outline"
                            size="sm"
                            class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100"
                        >
                            <Link :href="index().url">
                                <ArrowLeft class="mr-2 h-4 w-4" />
                                Back
                            </Link>
                        </Button>

                        <Button
                            v-if="canArchiveCompany"
                            variant="outline"
                            size="sm"
                            class="rounded-lg border-rose-200 text-rose-600 hover:bg-rose-50 hover:text-rose-700"
                            @click="archiveOpen = true"
                        >
                            <Archive class="mr-2 h-4 w-4" />
                            Archive
                        </Button>
                    </div>
                </div>

                <!-- Summary badges -->
                <div class="flex flex-wrap items-center gap-2">
                    <Badge
                        class="gap-1.5 border-0 bg-muted font-mono text-foreground"
                    >
                        {{ company.company_code ?? '—' }}
                    </Badge>
                    <Badge :class="['gap-1.5', statusClass(company.status)]">
                        <span
                            :class="[
                                'h-1.5 w-1.5 rounded-full',
                                statusDot(company.status),
                            ]"
                        />
                        {{ humanize(company.status) }}
                    </Badge>
                    <Badge class="border-0 bg-slate-100 text-slate-600">
                        {{
                            company.business_type
                                ? humanize(company.business_type)
                                : '—'
                        }}
                    </Badge>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <Card class="border-slate-200 shadow-sm">
                        <CardContent
                            class="flex items-center justify-between p-4"
                        >
                            <div>
                                <p
                                    class="text-[11px] font-semibold tracking-widest text-muted-foreground uppercase"
                                >
                                    Total Documents
                                </p>
                                <p class="mt-1 text-2xl font-bold">
                                    {{ totalDocs }}
                                </p>
                            </div>
                            <div class="rounded-lg bg-slate-100 p-2.5">
                                <Files class="h-4 w-4 text-slate-600" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="border-emerald-200 shadow-sm">
                        <CardContent
                            class="flex items-center justify-between p-4"
                        >
                            <div>
                                <p
                                    class="text-[11px] font-semibold tracking-widest text-emerald-700/80 uppercase"
                                >
                                    Verified
                                </p>
                                <p
                                    class="mt-1 text-2xl font-bold text-emerald-700"
                                >
                                    {{ verifiedDocs }}
                                </p>
                            </div>
                            <div class="rounded-lg bg-emerald-100 p-2.5">
                                <CheckCircle2
                                    class="h-4 w-4 text-emerald-700"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="border-amber-200 shadow-sm">
                        <CardContent
                            class="flex items-center justify-between p-4"
                        >
                            <div>
                                <p
                                    class="text-[11px] font-semibold tracking-widest text-amber-700/80 uppercase"
                                >
                                    Pending Review
                                </p>
                                <p
                                    class="mt-1 text-2xl font-bold text-amber-700"
                                >
                                    {{ pendingDocs }}
                                </p>
                            </div>
                            <div class="rounded-lg bg-amber-100 p-2.5">
                                <Clock3 class="h-4 w-4 text-amber-700" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="border-rose-200 shadow-sm">
                        <CardContent
                            class="flex items-center justify-between p-4"
                        >
                            <div>
                                <p
                                    class="text-[11px] font-semibold tracking-widest text-rose-700/80 uppercase"
                                >
                                    Flagged / Expired
                                </p>
                                <p
                                    class="mt-1 text-2xl font-bold text-rose-700"
                                >
                                    {{ flaggedDocs }}
                                </p>
                            </div>
                            <div class="rounded-lg bg-rose-100 p-2.5">
                                <CircleAlert class="h-4 w-4 text-rose-700" />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- ── Top grid ───────────────────────────────────── -->
                <div class="grid gap-4 lg:grid-cols-3">
                    <!-- Company details -->
                    <Card class="lg:col-span-2">
                        <CardHeader class="border-b border-slate-100 pb-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="relative h-10 w-10 shrink-0 overflow-hidden rounded-lg border bg-muted shadow-sm"
                                >
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
                                        <Building2
                                            class="h-4 w-4 text-primary/60"
                                        />
                                    </div>
                                </div>
                                <div>
                                    <CardTitle class="text-base"
                                        >Company Details</CardTitle
                                    >
                                    <CardDescription
                                        >Basic information and contact
                                        details.</CardDescription
                                    >
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent class="divide-y divide-slate-100 p-0">
                            <div
                                class="flex items-center justify-between px-6 py-3"
                            >
                                <span
                                    class="text-[11px] font-semibold tracking-widest text-muted-foreground uppercase"
                                    >Email</span
                                >
                                <span class="text-sm">{{
                                    company.company_email ?? '—'
                                }}</span>
                            </div>
                            <div
                                class="flex items-center justify-between px-6 py-3"
                            >
                                <span
                                    class="text-[11px] font-semibold tracking-widest text-muted-foreground uppercase"
                                    >Phone</span
                                >
                                <span class="text-sm">{{
                                    company.company_phone ?? '—'
                                }}</span>
                            </div>
                            <div
                                class="flex items-start justify-between gap-4 px-6 py-3"
                            >
                                <span
                                    class="text-[11px] font-semibold tracking-widest text-muted-foreground uppercase"
                                    >Address</span
                                >
                                <span class="text-right text-sm">{{
                                    company.company_address ?? '—'
                                }}</span>
                            </div>
                            <div
                                class="flex items-center justify-between px-6 py-3"
                            >
                                <span
                                    class="text-[11px] font-semibold tracking-widest text-muted-foreground uppercase"
                                    >Registration No.</span
                                >
                                <span class="font-mono text-sm">{{
                                    company.registration_number ?? '—'
                                }}</span>
                            </div>
                            <div
                                class="flex items-center justify-between px-6 py-3"
                            >
                                <span
                                    class="text-[11px] font-semibold tracking-widest text-muted-foreground uppercase"
                                    >Created</span
                                >
                                <span class="text-sm text-muted-foreground">
                                    {{ formatDate(company.created_at) }} ·
                                    {{ company.creator?.name ?? 'N/A' }}
                                </span>
                            </div>
                            <div
                                class="flex items-center justify-between px-6 py-3"
                            >
                                <span
                                    class="text-[11px] font-semibold tracking-widest text-muted-foreground uppercase"
                                    >Last Updated</span
                                >
                                <span class="text-sm text-muted-foreground">
                                    {{ company.updated_at_human ?? '—' }} ·
                                    {{ company.updater?.name ?? 'N/A' }}
                                </span>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Representative -->
                    <Card>
                        <CardHeader class="border-b border-slate-100 pb-4">
                            <CardTitle
                                class="flex items-center gap-2 text-base"
                            >
                                <UserRound class="h-4 w-4 text-blue-700" />
                                Representative
                            </CardTitle>
                            <CardDescription
                                >Who to contact for
                                coordination.</CardDescription
                            >
                        </CardHeader>
                        <CardContent class="divide-y divide-slate-100 p-0">
                            <div class="px-5 py-3">
                                <p
                                    class="mb-0.5 text-[11px] font-semibold tracking-widest text-muted-foreground uppercase"
                                >
                                    Name
                                </p>
                                <p class="text-sm font-medium">
                                    {{
                                        company.authorized_representative_name ??
                                        '—'
                                    }}
                                </p>
                            </div>
                            <div class="px-5 py-3">
                                <p
                                    class="mb-0.5 text-[11px] font-semibold tracking-widest text-muted-foreground uppercase"
                                >
                                    Position
                                </p>
                                <p class="text-sm">
                                    {{
                                        company.authorized_representative_position ??
                                        '—'
                                    }}
                                </p>
                            </div>
                            <div class="px-5 py-3">
                                <p
                                    class="mb-0.5 text-[11px] font-semibold tracking-widest text-muted-foreground uppercase"
                                >
                                    Contact
                                </p>
                                <p class="text-sm">
                                    {{
                                        company.authorized_representative_contact ??
                                        '—'
                                    }}
                                </p>
                            </div>
                            <div v-if="!repHasAny" class="px-5 py-4">
                                <p class="text-xs text-muted-foreground">
                                    No representative details provided.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- ── Documents card ─────────────────────────────── -->
                <Card>
                    <CardHeader class="border-b border-slate-100 pb-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <CardTitle
                                    class="flex items-center gap-2 text-base"
                                >
                                    <FileText class="h-4 w-4 text-blue-700" />
                                    Document Details
                                </CardTitle>
                                <CardDescription class="mt-0.5">
                                    Review, verify, preview, or download
                                    submitted documents.
                                </CardDescription>
                            </div>

                            <Button
                                v-if="docs.length > 0"
                                variant="outline"
                                size="sm"
                                class="shrink-0 rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100"
                                @click="openBulkConfirm"
                            >
                                <FileArchive class="mr-2 h-4 w-4" />
                                Download All (Verified Only)
                            </Button>
                        </div>
                    </CardHeader>

                    <CardContent class="p-0">
                        <!-- Empty state -->
                        <div
                            v-if="docs.length === 0"
                            class="flex flex-col items-center gap-3 py-20 text-center"
                        >
                            <div
                                class="flex h-14 w-14 items-center justify-center rounded-2xl bg-muted"
                            >
                                <FileText
                                    class="h-6 w-6 text-muted-foreground/40"
                                />
                            </div>
                            <div>
                                <p class="text-sm font-semibold">
                                    No documents uploaded yet.
                                </p>
                                <p class="mt-0.5 text-xs text-muted-foreground">
                                    Documents submitted by the company will
                                    appear here.
                                </p>
                            </div>
                        </div>

                        <!-- Document rows -->
                        <div v-else class="divide-y divide-slate-100">
                            <div
                                v-for="doc in docs"
                                :key="doc.id"
                                class="grid grid-cols-[1fr_auto] gap-4 px-6 py-4 transition-colors hover:bg-muted/30"
                            >
                                <!-- Left: doc info -->
                                <div class="min-w-0 space-y-2">
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <p
                                            class="text-sm font-semibold text-foreground"
                                        >
                                            {{ humanize(doc.doc_type) }}
                                        </p>
                                        <Badge
                                            :class="[
                                                'gap-1.5',
                                                statusClass(doc.status),
                                            ]"
                                        >
                                            <span
                                                :class="[
                                                    'h-1.5 w-1.5 rounded-full',
                                                    statusDot(doc.status),
                                                ]"
                                            />
                                            {{ humanize(doc.status) }}
                                        </Badge>
                                        <Badge
                                            v-if="
                                                isExpired(doc.expires_at) &&
                                                doc.status !== 'expired'
                                            "
                                            class="gap-1.5 border-rose-200 bg-rose-100 text-rose-600"
                                        >
                                            <span
                                                class="h-1.5 w-1.5 rounded-full bg-rose-500"
                                            />
                                            Expired
                                        </Badge>
                                    </div>

                                    <div>
                                        <button
                                            v-if="canPreview(doc)"
                                            class="flex items-center gap-1.5 text-sm text-primary underline-offset-2 hover:underline"
                                            :title="doc.original_name ?? ''"
                                            @click="openPreview(doc)"
                                        >
                                            <Eye class="h-3.5 w-3.5 shrink-0" />
                                            <span class="max-w-xs truncate">{{
                                                doc.original_name ?? '—'
                                            }}</span>
                                        </button>
                                        <span
                                            v-else
                                            class="text-sm text-muted-foreground"
                                        >
                                            {{ doc.original_name ?? '—' }}
                                        </span>
                                    </div>

                                    <div
                                        class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground"
                                    >
                                        <span v-if="doc.issued_at"
                                            >Issued:
                                            <span
                                                class="font-medium text-foreground"
                                                >{{
                                                    formatDate(doc.issued_at)
                                                }}</span
                                            ></span
                                        >
                                        <span v-if="doc.expires_at">
                                            Expires:
                                            <span
                                                :class="[
                                                    'font-medium',
                                                    isExpired(doc.expires_at)
                                                        ? 'text-rose-600'
                                                        : 'text-foreground',
                                                ]"
                                            >
                                                {{ formatDate(doc.expires_at) }}
                                            </span>
                                        </span>
                                        <span v-if="doc.uploader"
                                            >Uploaded by:
                                            <span
                                                class="font-medium text-foreground"
                                                >{{ doc.uploader.name }}</span
                                            ></span
                                        >
                                        <span v-if="doc.created_at"
                                            >on
                                            <span
                                                class="font-medium text-foreground"
                                                >{{
                                                    formatDateTime(
                                                        doc.created_at,
                                                    )
                                                }}</span
                                            ></span
                                        >
                                        <span v-if="doc.verifier">
                                            Verified by:
                                            <span
                                                class="font-medium text-foreground"
                                                >{{ doc.verifier.name }}</span
                                            >
                                            <span v-if="doc.verified_at">
                                                on
                                                {{
                                                    formatDateTime(
                                                        doc.verified_at,
                                                    )
                                                }}</span
                                            >
                                        </span>
                                    </div>

                                    <div v-if="doc.remarks">
                                        <Popover>
                                            <PopoverTrigger as-child>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    class="h-7 rounded-lg border-slate-200 text-xs text-slate-600 hover:bg-slate-100"
                                                >
                                                    <MessageSquareText
                                                        class="mr-1.5 h-3.5 w-3.5"
                                                    />
                                                    View Remarks
                                                </Button>
                                            </PopoverTrigger>
                                            <PopoverContent
                                                align="start"
                                                class="w-80 rounded-xl"
                                            >
                                                <div class="space-y-2">
                                                    <p
                                                        class="text-sm font-semibold"
                                                    >
                                                        Remarks
                                                    </p>
                                                    <p
                                                        class="text-sm whitespace-pre-wrap text-muted-foreground"
                                                    >
                                                        {{ doc.remarks }}
                                                    </p>
                                                    <p
                                                        class="text-xs text-muted-foreground"
                                                    >
                                                        {{
                                                            humanize(
                                                                doc.doc_type,
                                                            )
                                                        }}
                                                        ·
                                                        {{
                                                            humanize(doc.status)
                                                        }}
                                                    </p>
                                                </div>
                                            </PopoverContent>
                                        </Popover>
                                    </div>
                                </div>

                                <!-- Right: actions dropdown -->
                                <div class="flex items-start pt-0.5">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="h-8 w-8 rounded-lg text-muted-foreground hover:bg-muted hover:text-foreground"
                                                :disabled="
                                                    actionForm.processing ||
                                                    rejectForm.processing
                                                "
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
                                            class="w-52 rounded-xl border-slate-200 shadow-lg"
                                        >
                                            <DropdownMenuLabel
                                                class="text-xs font-semibold tracking-widest text-muted-foreground uppercase"
                                            >
                                                {{ humanize(doc.doc_type) }}
                                            </DropdownMenuLabel>
                                            <DropdownMenuSeparator />

                                            <DropdownMenuItem
                                                v-if="canPreview(doc)"
                                                class="rounded-lg text-blue-700 focus:bg-blue-50 focus:text-blue-700"
                                                @click="openPreview(doc)"
                                            >
                                                <Eye
                                                    class="mr-2 h-4 w-4"
                                                />Preview
                                            </DropdownMenuItem>

                                            <DropdownMenuItem
                                                v-else
                                                class="cursor-not-allowed rounded-lg text-slate-500 opacity-50 focus:bg-slate-50 focus:text-slate-500"
                                                disabled
                                            >
                                                <Eye class="mr-2 h-4 w-4" />No
                                                preview available
                                            </DropdownMenuItem>

                                            <DropdownMenuItem
                                                class="rounded-lg text-blue-700 focus:bg-blue-50 focus:text-blue-700"
                                                @click="
                                                    openConfirm('download', doc)
                                                "
                                            >
                                                <Download
                                                    class="mr-2 h-4 w-4"
                                                />Download
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- ── Archive dialog ─────────────────────────────────────── -->
        <ArchiveCompanyDialog
            v-if="canArchiveCompany"
            v-model:open="archiveOpen"
            :company="company"
        />

        <!-- ── File Preview Dialog ────────────────────────────────── -->
        <Dialog v-model:open="previewOpen">
            <DialogContent
                class="flex max-h-[90vh] w-full max-w-4xl flex-col gap-0 overflow-hidden rounded-2xl p-0"
            >
                <DialogHeader
                    class="shrink-0 border-b border-slate-100 bg-slate-50 px-6 py-4"
                >
                    <div class="flex items-center justify-between gap-4">
                        <div class="min-w-0 space-y-1">
                            <DialogTitle class="truncate text-base">
                                {{
                                    previewDoc?.original_name ??
                                    humanize(previewDoc?.doc_type)
                                }}
                            </DialogTitle>
                            <DialogDescription
                                class="flex flex-wrap items-center gap-2"
                            >
                                <Badge
                                    :class="[
                                        'gap-1.5',
                                        statusClass(previewDoc?.status),
                                    ]"
                                >
                                    <span
                                        :class="[
                                            'h-1.5 w-1.5 rounded-full',
                                            statusDot(previewDoc?.status),
                                        ]"
                                    />
                                    {{ humanize(previewDoc?.status) }}
                                </Badge>
                                <span class="text-xs text-muted-foreground">{{
                                    humanize(previewDoc?.doc_type)
                                }}</span>
                                <span
                                    v-if="previewDoc?.expires_at"
                                    class="text-xs text-muted-foreground"
                                >
                                    · Expires:
                                    {{ formatDate(previewDoc?.expires_at) }}
                                </span>
                            </DialogDescription>
                        </div>

                        <!-- Action buttons moved into preview dialog -->
                        <div
                            v-if="previewDoc"
                            class="flex shrink-0 items-center gap-2"
                        >
                            <!-- Verify -->
                            <Button
                                v-if="previewDoc.status !== 'verified'"
                                variant="outline"
                                size="sm"
                                class="rounded-lg border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 hover:text-emerald-800"
                                :disabled="
                                    actionForm.processing ||
                                    rejectForm.processing
                                "
                                @click="
                                    openConfirm('verify', previewDoc);
                                    closePreview();
                                "
                            >
                                <CheckCircle2 class="mr-2 h-4 w-4" />
                                Verify
                            </Button>

                            <!-- Unverify 
                            <Button
                                v-if="previewDoc.status === 'verified'"
                                variant="outline"
                                size="sm"
                                class="rounded-lg border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100 hover:text-amber-800"
                                :disabled="
                                    actionForm.processing ||
                                    rejectForm.processing
                                "
                                @click="
                                    openConfirm('unverify', previewDoc);
                                    closePreview();
                                "
                            >
                                <RotateCcw class="mr-2 h-4 w-4" />
                                Unverify
                            </Button> -->

                            <!-- Invalid -->
                            <Button
                                variant="outline"
                                size="sm"
                                class="rounded-lg border-rose-200 bg-rose-50 text-rose-600 hover:bg-rose-100 hover:text-rose-700"
                                :disabled="
                                    actionForm.processing ||
                                    rejectForm.processing
                                "
                                @click="
                                    openReject(previewDoc.id);
                                    closePreview();
                                "
                            >
                                <XCircle class="mr-2 h-4 w-4" />
                                Invalid
                            </Button>

                            <!-- Download -->
                            <Button
                                variant="outline"
                                size="sm"
                                class="rounded-lg border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100"
                                as-child
                            >
                                <a
                                    :href="
                                        downloadCompanyDocument({
                                            company: company.id,
                                            document: previewDoc.id,
                                        }).url
                                    "
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <Download class="mr-2 h-4 w-4" />Download
                                </a>
                            </Button>
                        </div>
                    </div>
                </DialogHeader>

                <div class="relative flex-1 overflow-auto bg-muted/30">
                    <div
                        v-if="previewDoc && isImage(previewDoc)"
                        class="flex min-h-[50vh] items-center justify-center p-6"
                    >
                        <img
                            :src="fileUrl(previewDoc)"
                            :alt="
                                previewDoc.original_name ?? previewDoc.doc_type
                            "
                            class="max-h-[70vh] max-w-full rounded-lg object-contain shadow-md"
                            @error="
                                (e) => ((e.target as HTMLImageElement).src = '')
                            "
                        />
                    </div>
                    <div
                        v-else-if="previewDoc && isPdf(previewDoc)"
                        class="h-[70vh] w-full"
                    >
                        <iframe
                            v-if="!pdfLoadError"
                            :src="fileUrl(previewDoc)"
                            class="h-full w-full border-0"
                            @error="pdfLoadError = true"
                        />
                        <div
                            v-else
                            class="flex h-full flex-col items-center justify-center gap-4 text-muted-foreground"
                        >
                            <FileText class="h-12 w-12 opacity-30" />
                            <p class="text-sm">
                                Your browser cannot preview this PDF inline.
                            </p>
                            <Button
                                as-child
                                variant="outline"
                                size="sm"
                                class="rounded-lg"
                            >
                                <a
                                    :href="fileUrl(previewDoc)"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <Eye class="mr-2 h-4 w-4" />Open in new tab
                                </a>
                            </Button>
                        </div>
                    </div>
                </div>

                <DialogFooter
                    class="shrink-0 border-t border-slate-100 bg-white px-6 py-3"
                >
                    <p class="flex-1 text-xs text-muted-foreground">
                        Issued:
                        {{ formatDate(previewDoc?.issued_at ?? null) }} ·
                        Expires:
                        {{ formatDate(previewDoc?.expires_at ?? null) }} ·
                        Uploaded:
                        {{ formatDateTime(previewDoc?.created_at ?? null) }}
                    </p>
                    <Button
                        variant="outline"
                        size="sm"
                        class="rounded-lg"
                        @click="closePreview"
                        >Close</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- ── Reject dialog ──────────────────────────────────────── -->
        <Dialog v-model:open="rejectOpen">
            <DialogContent class="rounded-2xl sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Mark as Invalid</DialogTitle>
                    <DialogDescription>
                        Select one or more reasons below. The remarks field will
                        be built automatically — you can still edit it before
                        submitting.
                    </DialogDescription>
                </DialogHeader>

                <Separator />

                <div class="space-y-4">
                    <!-- Stackable checkboxes -->
                    <div>
                        <p
                            class="mb-2.5 text-[11px] font-semibold tracking-widest text-muted-foreground uppercase"
                        >
                            Reasons
                        </p>
                        <div class="grid grid-cols-1 gap-1.5 sm:grid-cols-2">
                            <label
                                v-for="preset in remarkPresets"
                                :key="preset.value"
                                :class="[
                                    'flex cursor-pointer items-center gap-3 rounded-lg border px-3 py-2.5 text-sm transition-colors',
                                    selectedPresets.includes(preset.value)
                                        ? 'border-rose-300 bg-rose-50 text-rose-700'
                                        : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50',
                                ]"
                                @click="togglePreset(preset.value)"
                            >
                                <Checkbox
                                    :checked="
                                        selectedPresets.includes(preset.value)
                                    "
                                    :class="
                                        selectedPresets.includes(preset.value)
                                            ? 'border-rose-400 data-[state=checked]:border-rose-600 data-[state=checked]:bg-rose-600'
                                            : ''
                                    "
                                    @click.stop
                                    @update:checked="togglePreset(preset.value)"
                                />
                                <span class="leading-snug">{{
                                    preset.label
                                }}</span>
                            </label>
                        </div>

                        <!-- Selected count pill -->
                        <p
                            v-if="selectedPresets.length > 0"
                            class="mt-2 text-xs font-medium text-rose-600"
                        >
                            {{ selectedPresets.length }} reason{{
                                selectedPresets.length > 1 ? 's' : ''
                            }}
                            selected
                        </p>
                    </div>

                    <!-- Remarks textarea — auto-filled, still editable -->
                    <div class="space-y-1.5">
                        <Label
                            class="text-[11px] font-semibold tracking-widest text-muted-foreground uppercase"
                        >
                            Remarks <span class="text-rose-500">*</span>
                        </Label>
                        <Textarea
                            v-model="rejectForm.remarks"
                            placeholder="Select reasons above or write your own…"
                            class="min-h-25 rounded-lg text-sm"
                        />
                        <InputError
                            class="mt-1"
                            :message="rejectForm.errors.remarks"
                        />
                        <p class="text-[11px] text-muted-foreground">
                            You can edit the auto-generated text or write your
                            own.
                        </p>
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <Button
                        variant="outline"
                        class="rounded-lg"
                        :disabled="rejectForm.processing"
                        @click="rejectOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        class="rounded-lg border-0 bg-rose-600 text-white hover:bg-rose-700"
                        :disabled="
                            rejectForm.processing || !rejectForm.remarks.trim()
                        "
                        @click="submitReject"
                    >
                        {{
                            rejectForm.processing
                                ? 'Invalidating…'
                                : 'Mark as Invalid'
                        }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- ── Row action confirm dialog ─────────────────────────── -->
        <AlertDialog v-model:open="confirmOpen">
            <AlertDialogContent class="rounded-2xl">
                <AlertDialogHeader>
                    <AlertDialogTitle>{{ confirmTitle() }}</AlertDialogTitle>
                    <AlertDialogDescription>{{
                        confirmDescription()
                    }}</AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel
                        class="rounded-lg"
                        :disabled="actionForm.processing"
                        @click="confirmOpen = false"
                        >Cancel</AlertDialogCancel
                    >
                    <AlertDialogAction
                        class="rounded-lg border-0 bg-blue-700 text-white hover:bg-blue-800"
                        :disabled="actionForm.processing"
                        @click="runConfirmedAction"
                    >
                        {{ actionForm.processing ? 'Processing…' : 'Continue' }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <!-- ── Bulk Download Confirm ──────────────────────────────── -->
        <AlertDialog v-model:open="bulkConfirmOpen">
            <AlertDialogContent class="rounded-2xl">
                <AlertDialogHeader>
                    <AlertDialogTitle
                        >Download verified documents?</AlertDialogTitle
                    >
                    <AlertDialogDescription>
                        This will download a ZIP containing only verified
                        documents for this company.
                        <span v-if="verifiedCount > 0"
                            >({{ verifiedCount }} verified)</span
                        >
                        <span v-else> No verified documents found.</span>
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel
                        class="rounded-lg"
                        @click="bulkConfirmOpen = false"
                        >Cancel</AlertDialogCancel
                    >
                    <AlertDialogAction
                        class="rounded-lg border-0 bg-blue-700 text-white hover:bg-blue-800"
                        :disabled="verifiedCount === 0"
                        @click="runBulkDownload"
                    >
                        Continue
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
