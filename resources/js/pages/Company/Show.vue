<script setup lang="ts">
import ArchiveCompanyDialog from '@/components/company/ArchiveCompanyDialog.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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

import {
    Accordion,
    AccordionContent,
    AccordionTrigger,
    AccordionItem
} from '@/components/ui/accordion';

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
    Mail,
    Phone,
    MapPin,
    ChevronDown,
    ChevronRight,
    User,
    IdCard,
    Ellipsis,
    File,
    ListChecks,
    X,
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
        logo?: string | null;
        logo_url?: string | null;
    };
}>();

const company = computed(() => props.company);
const docs = computed(() => props.company.documents ?? []);



const canArchiveCompany = computed(() => can('companies.archive'));



const logoError = ref(false);
const showLogo = computed(() => !!company.value.logo_url && !logoError.value);

const companyInitials = computed(
    () =>
        (company.value.company_code ?? company.value.company_name ?? '')
            .replace(/[^A-Za-z0-9]/g, '')
            .slice(0, 2)
            .toUpperCase() || '??',
);



const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Companies', href: index().url },
    { title: 'Company Details', href: show({ company: company.value.id }).url },
];



const archiveOpen = ref(false);



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

const selectMode = ref(false);
const selectedDocIds = ref<number[]>([]);

function toggleSelectMode() {
    selectMode.value = !selectMode.value;
    if (!selectMode.value) selectedDocIds.value = [];
}

function setDoc(id: number, checked: boolean) {
    const idx = selectedDocIds.value.indexOf(id);
    if (checked && idx === -1) selectedDocIds.value = [...selectedDocIds.value, id];
    else if (!checked && idx !== -1) selectedDocIds.value = selectedDocIds.value.filter((x) => x !== id);
}

function downloadSelected() {
    if (selectedDocIds.value.length === 0) return;
    for (const id of selectedDocIds.value) {
        const url = downloadCompanyDocument({ company: company.value.id, document: id }).url;
        const a = document.createElement('a');
        a.href = url;
        a.setAttribute('download', '');
        a.style.display = 'none';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }
    selectMode.value = false;
    selectedDocIds.value = [];
}

const allSelected = computed(
    () => docs.value.length > 0 && selectedDocIds.value.length === docs.value.length,
);

function selectAll() {
    if (allSelected.value) {
        selectedDocIds.value = [];
    } else {
        selectedDocIds.value = docs.value.map((d) => d.id);
    }
}



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
}



const actionForm = useForm({});



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

function togglePreset(value: RemarkPresetValue) {
    const preset = remarkPresets.find((p) => p.value === value)!;
    const isSelected = selectedPresets.value.includes(value);

    if (isSelected) {
        selectedPresets.value = selectedPresets.value.filter((v) => v !== value);
        const lines = rejectForm.remarks
            .split('\n')
            .filter((line) => !line.startsWith(preset.text));
        rejectForm.remarks = lines.join('\n').trim();
    } else {
        selectedPresets.value = [...selectedPresets.value, value];
        const current = rejectForm.remarks.trim();
        rejectForm.remarks = current ? current + '\n' + preset.text : preset.text;
    }
}

function openReject(docId: number) {
    rejectDocId.value = docId;
    selectedPresets.value = [];
    rejectForm.reset();
    rejectForm.clearErrors();
    rejectOpen.value = true;
}

function closeReject() {
    rejectOpen.value = false;
    rejectDocId.value = null;
    selectedPresets.value = [];
    rejectForm.reset();
    rejectForm.clearErrors();
}

function submitReject() {
    if (!rejectDocId.value || rejectForm.processing) return;
    rejectForm.patch(
        reject({ company: company.value.id, document: rejectDocId.value }).url,
        {
            preserveScroll: true,
            onSuccess: () => closeReject(),
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
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <!-- TODO: make the background a gradient, the bottom color the theme colro for cards, and the top color the 'average' color of the company logo. if the company logo is null, use the theme primary color instead -->
            <!-- CODE: <Card class="bg-linear-to-r from-primary via-card-background to-card-background border-none"> -->
            <Card class="">
                <CardHeader class="py-0">
                    <div class="flex items-center gap-4">
                        <div
                            class="relative h-32 w-32 shrink-0 overflow-hidden rounded-lg border-2 bg-white shadow-sm"
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

                        <div class="gap-2 w-full">
                            <div class="flex flex-row gap-2 pb-2 w-full items-center">
                                <h1
                                    class="text-2xl leading-tight font-bold tracking-tight"
                                >
                                    {{ company.company_name }}
                                </h1>
                                <div class="ml-2 flex flex-1 items-center">
                                    <hr class="h-px w-full border border-rose-500" />
                                    <div class="border-7 border-rose-500 rounded-xs">
                                        <div class="border-3 border-white rounded-xs"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-between">
                                <div class="flex flex-wrap items-center gap-2">
                                    <Badge
                                        class="border-0 bg-muted font-mono text-foreground"
                                    >
                                        {{ company.company_code ?? '—' }}
                                    </Badge>
                                    <Badge :class="['', statusClass(company.status)]">
                                        <span
                                            :class="[
                                                'h-2 w-2 rounded-full',
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
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex shrink-0 items-center gap-2">
                                        <Button
                                            as-child
                                            variant="outline"
                                            class="rounded-lg bg-card border-slate-200 text-slate-600 hover:bg-slate-100 cursor-pointer"
                                        >
                                            <Link :href="index().url">
                                                <ArrowLeft class="h-4 w-4" />
                                            </Link>
                                        </Button>

                                        <Button
                                            v-if="canArchiveCompany"
                                            variant="outline"
                                            class="group/segment rounded-lg bg-card border-rose-200 text-rose-600 hover:bg-rose-50 hover:text-rose-700 gap-0 cursor-pointer"
                                            @click="archiveOpen = true"
                                        >
                                            <Archive class="h-4 w-4 shrink-0" />
                                            <span class="max-w-0 overflow-hidden whitespace-nowrap opacity-0 transition-all duration-300 group-hover/segment:ml-2 group-hover/segment:max-w-32 group-hover/segment:opacity-100">
                                                Archive Company
                                            </span>
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- CODE: <div class="ml-2 flex flex-1 items-center">
                            <hr class="h-px w-full border border-rose-500" />
                            <div class="border-7 border-rose-500 rounded-xs">
                                <div class="border-3 border-white rounded-xs"></div>
                            </div>
                        </div> -->
                    </div>
                </CardHeader>
            </Card>

            <div class="grid gap-4 lg:grid-cols-3 h-fit">
                <div class="grid gap-4 col-span-1 h-fit">
                    <Card class="py-6">
                        <CardHeader class="flex items-center justify-between">
                            <div>
                                <CardTitle>
                                    Company Details
                                </CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent class="px-6 grid divide-y gap-y-2 pt-2 border-t border-slate-100">                                        
                            <div class="py-2">
                                <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">
                                    Registration No.
                                </span>
                                <span class="font-mono text-sm">{{
                                    company.registration_number ?? '—'
                                }}</span>
                            </div>
                            <div class="py-2">
                                <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">
                                    Created
                                </span>
                                <span class="text-sm">
                                    {{ formatDate(company.created_at) }} ·
                                    {{ company.creator?.name ?? 'N/A' }}
                                </span>
                            </div>
                            <div class="py-2">
                                <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">
                                    Last Updated
                                </span>
                                <span class="text-sm">
                                    {{ company.updated_at_human ?? '—' }} ·
                                    {{ company.updater?.name ?? 'N/A' }}
                                </span>
                            </div>
                            <div class="grid gap-y-2 pt-2">
                                <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">
                                    Contacts
                                </span>
                                <div class="items-center flex">
                                    <div class="h-full mr-4">
                                        <Mail class="h-4 w-4 inline-block text-primary" />
                                    </div>
                                    <a
                                        v-if="company.company_email"
                                        :href="`mailto:${company.company_email}`"
                                        class="text-sm hover:underline underline-offset-2"
                                    >{{ company.company_email }}</a>
                                    <span v-else class="text-sm">—</span>
                                </div>
                                <div class="items-center flex">
                                    <div class="h-full mr-4">
                                        <Phone class="h-4 w-4 inline-block text-primary" />
                                    </div>
                                    <a
                                        v-if="company.company_phone"
                                        :href="`tel:${company.company_phone}`"
                                        class="text-sm hover:underline underline-offset-2"
                                    >{{ company.company_phone }}</a>
                                    <span v-else class="text-sm">—</span>
                                </div>
                                <div class="items-center flex">
                                    <div class="h-full mr-4">
                                        <MapPin class="h-4 w-4 inline-block text-primary" />
                                    </div>
                                    <span class="text-sm">{{
                                        company.company_address ?? '—'
                                    }}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card class="py-6">
                        <CardHeader class="flex items-center justify-between">
                            <div>
                                <CardTitle>
                                    Representative
                                </CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent class="px-6 grid divide-y gap-y-2 pt-2 border-t border-slate-100">                                        
                            <div class="grid gap-y-2 pt-2">
                                <span class="text-xs font-semibold tracking-widest text-muted-foreground uppercase block">
                                    Contacts
                                </span>
                                <div class="items-center flex">
                                    <div class="h-full mr-4">
                                        <Mail class="h-4 w-4 inline-block text-primary" />
                                    </div>
                                    <span class="text-sm">
                                        {{
                                            company.authorized_representative_name ??
                                            '—'
                                        }}
                                    </span>
                                </div>
                                <div class="items-center flex">
                                    <div class="h-full mr-4">
                                        <IdCard class="h-4 w-4 inline-block text-primary" />
                                    </div>
                                    <span class="text-sm">
                                        {{
                                            company.authorized_representative_position ??
                                            '—'
                                        }}
                                    </span>
                                </div>
                                <div class="items-center flex">
                                    <div class="h-full mr-4">
                                        <Phone class="h-4 w-4 inline-block text-primary" />
                                    </div>
                                    <a
                                        v-if="company.authorized_representative_contact"
                                        :href="`tel:${company.authorized_representative_contact}`"
                                        class="text-sm hover:underline underline-offset-2"
                                    >{{ company.authorized_representative_contact }}</a>
                                    <span v-else class="text-sm">—</span>
                                </div>
                                <div v-if="!repHasAny" class="px-5 py-4">
                                    <p class="text-xs text-muted-foreground">
                                        No representative details provided.
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            
                <div class="grid gap-4 col-span-2 h-fit">
                    <Card>
                        <CardHeader class="flex items-center justify-between">
                            <div>
                                <CardTitle>
                                    Documents
                                </CardTitle>
                            </div>
                        </CardHeader>

                        <CardContent class="border-t border-slate-100">
                            
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

                            
                            <div v-else>
                                <div class="flex justify-between py-4">
                                    <div class="flex gap-2">
                                        <Button
                                            variant="outline"
                                            class="group/segment shrink-0 rounded-lg cursor-pointer gap-0 cursor-pointer hover:bg-slate-100 text-slate-600"
                                            @click="toggleSelectMode"
                                        >
                                            <X v-if="selectMode" class="h-4 w-4 shrink-0" />
                                            <ListChecks v-else class="h-4 w-4 shrink-0" />
                                            <span v-if="!selectMode" class="max-w-0 overflow-hidden whitespace-nowrap opacity-0 transition-all duration-300 group-hover/segment:ml-2 group-hover/segment:max-w-16 group-hover/segment:opacity-100">
                                                Select
                                            </span>
                                        </Button>
                                        <Transition
                                            enter-active-class="transition-all duration-200"
                                            enter-from-class="opacity-0 scale-95"
                                            enter-to-class="opacity-100 scale-100"
                                            leave-active-class="transition-all duration-150"
                                            leave-from-class="opacity-100 scale-100"
                                            leave-to-class="opacity-0 scale-95"
                                        >
                                            <Button
                                                v-if="selectMode"
                                                variant="outline"
                                                class="shrink-0 rounded-lg text-slate-600 hover:bg-slate-100 cursor-pointer"
                                                @click="selectAll"
                                            >
                                                {{ allSelected ? 'Deselect All' : 'Select All' }}
                                            </Button>
                                        </Transition>
                                    </div>
                                    <Button
                                        v-if="docs.length > 0"
                                        variant="outline"
                                        class="group/segment shrink-0 rounded-lg text-slate-600 hover:bg-slate-100 cursor-pointer gap-0"
                                        :disabled="selectMode && selectedDocIds.length === 0"
                                        @click="selectMode ? downloadSelected() : openBulkConfirm()"
                                    >
                                        <Download class="h-4 w-4 shrink-0" />
                                        <span class="max-w-0 overflow-hidden whitespace-nowrap opacity-0 transition-all duration-300 group-hover/segment:ml-2 group-hover/segment:max-w-48 group-hover/segment:opacity-100">
                                            {{ selectMode ? `Download Selected (${selectedDocIds.length})` : 'Download All Verified' }}
                                        </span>
                                    </Button>
                                </div>
                                <div class="divide-y divide-slate-100 py-0">
                                    <div
                                        v-for="doc in docs"
                                        :key="doc.id"
                                        class="grid grid-cols-[auto_1fr_auto] py-2 transition-colors"
                                        :class="!selectMode ? 'group/row' : ''"
                                    >
                                        
                                        <div
                                            class="flex items-start pt-1 overflow-hidden transition-all duration-300"
                                            :class="selectMode ? 'w-5 opacity-100 me-2' : 'w-0 opacity-0'"
                                        >
                                            <input
                                                type="checkbox"
                                                class="h-4 w-4 cursor-pointer rounded-lg accent-primary"
                                                :checked="selectedDocIds.includes(doc.id)"
                                                @change="setDoc(doc.id, ($event.target as HTMLInputElement).checked)"
                                            />
                                        </div>

                                        
                                        <div class="min-w-0">
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
                                                    class="cursor-pointer flex items-center gap-2 text-sm text-muted-foreground underline-offset-2 hover:underline"
                                                    :title="doc.original_name ?? ''"
                                                    @click="openPreview(doc)"
                                                >
                                                    <File class="h-4- w-4 shrink-0" />
                                                    <span class="truncate">{{
                                                        doc.original_name ?? '—'
                                                    }}</span>
                                                </button>
                                            </div>

                                            <div class="overflow-hidden max-h-0 opacity-0 group-hover/row:max-h-96 group-hover/row:opacity-100 transition-all delay-200 duration-300 flex-col">
                                                <div
                                                    class="flex flex-row items-center gap-x-10 text-xs text-muted-foreground"
                                                >
                                                    <div class="flex flex-col w-40 gap-y-1">
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
                                                    </div>
                                                    <div class="flex flex-col flex-1 gap-y-1">
                                                        <span v-if="doc.uploader"
                                                            >Uploaded by:
                                                            <span
                                                                class="font-medium text-foreground"
                                                            >
                                                                {{ doc.uploader.name }}
                                                            </span>
                                                            on
                                                            <span
                                                                class="font-medium text-foreground"
                                                            >
                                                                {{
                                                                    formatDateTime(
                                                                        doc.created_at,
                                                                    )
                                                                }}
                                                            </span>
                                                        </span>
                                                        <span v-if="doc.verifier">
                                                            Verified by:
                                                            <span
                                                                class="font-medium text-foreground"
                                                            >
                                                                {{ doc.verifier.name }}
                                                            </span>
                                                            on
                                                            <span
                                                                class="font-medium text-foreground"
                                                            >
                                                                {{
                                                                    formatDateTime( 
                                                                        doc.verified_at,
                                                                    )
                                                                }}
                                                            </span>
                                                        </span>
                                                    </div>
                                                </div>

                                                <div v-if="doc.remarks" class="pt-2">
                                                    <Popover>
                                                        <PopoverTrigger as-child>
                                                            <Button
                                                                variant="outline"
                                                                class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100"
                                                            >
                                                                <MessageSquareText
                                                                    class="h-4 w-4"
                                                                />
                                                                View Remarks
                                                            </Button>
                                                        </PopoverTrigger>
                                                        <PopoverContent
                                                            align="start"
                                                            class="w-80 rounded-lg border-slate-200 bg-white shadow-lg"
                                                        >
                                                            <div>
                                                                <p
                                                                    class="text-sm font-semibold pb-2"
                                                                >
                                                                    Remarks
                                                                </p>
                                                                <p
                                                                    class="text-sm whitespace-pre-wrap text-muted-foreground"
                                                                >
                                                                    {{ doc.remarks }}
                                                                </p>
                                                            </div>
                                                        </PopoverContent>
                                                    </Popover>
                                                </div>
                                            </div>
                                        </div>

                                        
                                        <div class="flex items-start">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger as-child>
                                                    <Button
                                                        variant="outline"
                                                        class="rounded-lg border text-muted-foreground hover:bg-slate-100 hover:text-foreground cursor-pointer"
                                                        :disabled="
                                                            actionForm.processing ||
                                                            rejectForm.processing
                                                        "
                                                    >
                                                        <MoreHorizontal
                                                            class="h-4 w-4"
                                                        />
                                                        
                                                    </Button>
                                                </DropdownMenuTrigger>

                                                <DropdownMenuContent
                                                    align="end"
                                                    class="w-fit rounded-xl border-slate-200 shadow-lg"
                                                >
                                                    <DropdownMenuLabel
                                                        class="text-xs font-semibold tracking-widest text-muted-foreground uppercase"
                                                    >
                                                        {{ humanize(doc.doc_type) }}
                                                    </DropdownMenuLabel>
                                                    <DropdownMenuSeparator />

                                                    <DropdownMenuItem
                                                        v-if="canPreview(doc)"
                                                        class="rounded-lg cursor-pointer hover:bg-slate-100"
                                                        @click="openPreview(doc)"
                                                    >
                                                        <Eye
                                                            class="h-4 w-4"
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
                                                        class="rounded-lg cursor-pointer hover:bg-slate-100"
                                                        @click="
                                                            openConfirm('download', doc)
                                                        "
                                                    >
                                                        <Download
                                                            class="h-4 w-4"
                                                        />Download
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>

        
        <ArchiveCompanyDialog
            v-if="canArchiveCompany"
            v-model:open="archiveOpen"
            :company="company""
        />

        
        <Dialog v-model:open="previewOpen">
            <DialogContent
                class="flex max-h-[90vh] w-full flex-col gap-0 rounded-lg py-4 px-6"
                className="[&>button:last-child]:hidden"
            >
                <DialogHeader
                    class="shrink-0"
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
                                <span class="text-xs text-muted-foreground">{{
                                    humanize(previewDoc?.doc_type)
                                }}</span>
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
                            </DialogDescription>
                        </div>
                    </div>
                </DialogHeader>

                <div class="relative flex-1 overflow-auto py-4">
                    <div
                        v-if="previewDoc && isImage(previewDoc)"
                        class="flex min-h-[50vh] items-center justify-center"
                    >
                        <img
                            :src="fileUrl(previewDoc)"
                            :alt="
                                previewDoc.original_name ?? previewDoc.doc_type
                            "
                            class="max-h-[70vh] max-w-full rounded-lg object-contain"
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
                            class="flex h-full flex-col items-center justify-center"
                        >
                            <FileText class="h-12 w-12 opacity-30" />
                            <p class="text-sm">
                                Your browser cannot preview this PDF inline.
                            </p>
                            <Button
                                as-child
                                variant="outline"
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
                    v-if="previewDoc"
                    class="shrink-0 flex flex-row items-center"
                >
                    <p class="flex-1 text-xs text-muted-foreground">
                        Issued:
                        {{ formatDate(previewDoc?.issued_at ?? null) }}<br>
                        Expires:
                        {{ formatDate(previewDoc?.expires_at ?? null) }}
                    </p>
                    <p class="flex-1 text-xs text-muted-foreground">
                        Uploaded:
                        {{ formatDateTime(previewDoc?.created_at ?? null) }}
                    </p>
                    <div class="flex flex-1 flex-row gap-x-2 justify-end">
                        <Popover>
                            <PopoverTrigger as-child>
                                <Button
                                    variant="outline"
                                    class="rounded-lg cursor-pointer hover:bg-slate-100"
                                >
                                    <Ellipsis class="h-4 w-4" />
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent
                                align="end"
                                class="w-fit rounded-lg border-slate-200 shadow-lg p-0 gap-2"
                            >
                                <div
                                    v-if="previewDoc.status !== 'verified'"
                                    class="cursor-pointer flex items-center gap-2 rounded-lg px-4 py-2 text-sm hover:bg-slate-100"
                                    :disabled="
                                        actionForm.processing ||
                                        rejectForm.processing
                                    "
                                    @click="
                                        openConfirm('verify', previewDoc);
                                        closePreview();
                                    "
                                >
                                    <CheckCircle2 class="h-4 w-4" />
                                    Verify
                                </div>
                                <div
                                    v-if="previewDoc.status !== 'invalid'"
                                    class="cursor-pointer flex items-center gap-2 rounded-lg px-4 py-2 text-sm hover:bg-slate-100"
                                    :disabled="
                                        actionForm.processing ||
                                        rejectForm.processing
                                    "
                                    @click="
                                        openReject(previewDoc.id);
                                        closePreview();
                                    "
                                >
                                    <XCircle class="h-4 w-4" />
                                    Mark as Invalid
                                </div>
                                <a
                                    :href="
                                        downloadCompanyDocument({
                                            company: company.id,
                                            document: previewDoc.id,
                                        }).url
                                    "
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="cursor-pointer flex items-center gap-2 rounded-lg px-4 py-2 text-sm hover:bg-slate-100"
                                >
                                    <Download class="h-4 w-4" />
                                    Download
                                </a>
                            </PopoverContent>
                        </Popover>
                        <Button
                            variant="outline"
                            class="rounded-lg cursor-pointer hover:bg-slate-100"
                            @click="closePreview"
                            >Close</Button
                        >
                    </div>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        
        <Dialog v-model:open="rejectOpen">
            <DialogContent class="overflow-y-auto rounded-lg sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Mark as Invalid</DialogTitle>
                    <DialogDescription>
                        Select one or more reasons below.
                    </DialogDescription>
                </DialogHeader>
                <div class="space-y-4">
                    
                    <div>
                        <p
                            class="mb-2.5 text-[11px] font-semibold tracking-widest text-muted-foreground uppercase"
                        >
                            Reasons
                        </p>
                        <div class="grid grid-cols-1 gap-1.5 sm:grid-cols-2">
                            <div
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
                                <span
                                    :class="[
                                        'flex h-4 w-4 shrink-0 items-center justify-center rounded-sm border-2 transition-colors',
                                        selectedPresets.includes(preset.value)
                                            ? 'border-rose-600 bg-rose-600'
                                            : 'border-slate-400 bg-white',
                                    ]"
                                >
                                    <svg
                                        v-if="selectedPresets.includes(preset.value)"
                                        class="h-3 w-3 text-white"
                                        viewBox="0 0 12 12"
                                        fill="none"
                                    >
                                        <path
                                            d="M2 6l3 3 5-5"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                    </svg>
                                </span>
                                <span class="leading-snug">{{
                                    preset.label
                                }}</span>
                            </div>
                        </div>

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

                    <div class="space-y-1.5">
                        <Label
                            class="text-[11px] font-semibold tracking-widest text-muted-foreground uppercase"
                        >
                            Remarks <span class="text-rose-500">*</span>
                        </Label>
                        <Textarea
                            v-model="rejectForm.remarks"
                            placeholder="Select reasons above or write your own..."
                            class="min-h-25 rounded-lg text-sm"
                        />
                        <InputError
                            class="mt-1"
                            :message="rejectForm.errors.remarks"
                        />
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <Button
                        variant="outline"
                        class="rounded-lg cursor-pointer"
                        :disabled="rejectForm.processing"
                        @click="closeReject"
                    >
                        Cancel
                    </Button>
                    <Button
                        class="rounded-lg border-0 bg-rose-600 text-white hover:bg-rose-700 cursor-pointer"
                        :disabled="
                            rejectForm.processing || !rejectForm.remarks.trim()
                        "
                        @click="submitReject"
                    >
                        {{
                            rejectForm.processing
                                ? 'Invalidating...'
                                : 'Mark as Invalid'
                        }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        
        <AlertDialog v-model:open="confirmOpen">
            <AlertDialogContent class="rounded-lg p-4">
                <AlertDialogHeader>
                    <AlertDialogTitle>{{ confirmTitle() }}</AlertDialogTitle>
                    <AlertDialogDescription>{{
                        confirmDescription()
                    }}</AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel
                        variant="outline"    
                        class="rounded-lg cursor-pointer hover:bg-slate-100"
                        :disabled="actionForm.processing"
                        @click="confirmOpen = false"
                        >Cancel</AlertDialogCancel
                    >
                    <AlertDialogAction
                        variant="outline"
                        class="rounded-lg border-0 bg-primary text-white cursor-pointer"
                        :disabled="actionForm.processing"
                        @click="runConfirmedAction"
                    >
                        {{ actionForm.processing ? 'Processing...' : 'Continue' }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        
        <AlertDialog v-model:open="bulkConfirmOpen">
            <AlertDialogContent class="rounded-lg p-4">
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
                        variant="outline"
                        class="rounded-lg"
                        @click="bulkConfirmOpen = false"
                        >Cancel</AlertDialogCancel
                    >
                    <AlertDialogAction
                        variant="outline"
                        class="rounded-lg border-0 bg-primary text-white cursor-pointer hover:bg-slate-100"
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
