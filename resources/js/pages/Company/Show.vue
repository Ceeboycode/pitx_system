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

import { index, show } from '@/routes/companies';
import {
    destroy as destroyDoc,
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
    created_at?: string | null; // Uploaded At
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

        // ✅ Representative details (make sure backend sends these fields)
        authorized_representative_name?: string | null;
        authorized_representative_position?: string | null;
        authorized_representative_contact?: string | null;

        documents?: CompanyDocument[];
    };
}>();

const company = computed(() => props.company);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Companies', href: index().url },
    { title: 'Company Details', href: show({ company: company.value.id }).url },
];

function formatDate(date?: string | null) {
    if (!date) return '—';
    return new Date(date).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function formatDateTime(date?: string | null) {
    if (!date) return '—';
    return new Date(date).toLocaleString(undefined, {
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

/**
 * Forms
 */
const actionForm = useForm({}); // verify/unverify/delete

/**
 * Confirm dialog for verify/unverify(delete)/download
 */
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

    if (confirmAction.value === 'unverify' && doc.status === 'invalid') {
        return 'Uninvalid document?';
    }

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

    if (confirmAction.value === 'unverify' && doc.status === 'invalid') {
        return `This will move "${name}" back to pending.`;
    }

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
    if (!doc) return;
    if (actionForm.processing || rejectForm.processing) return;

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

/**
 * Invalid/Reject remarks (BIG dialog)
 */
const rejectOpen = ref(false);
const rejectDocId = ref<number | null>(null);
const rejectForm = useForm<{ remarks: string }>({ remarks: '' });

function openReject(docId: number) {
    rejectDocId.value = docId;
    rejectForm.reset();
    rejectForm.clearErrors();
    rejectOpen.value = true;
}

function submitReject() {
    if (!rejectDocId.value) return;
    if (rejectForm.processing) return;

    rejectForm.patch(
        reject({ company: company.value.id, document: rejectDocId.value }).url,
        {
            preserveScroll: true,
            onSuccess: () => {
                rejectOpen.value = false;
                rejectDocId.value = null;
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
                <!-- Page header -->
                <div class="flex items-start justify-between gap-4">
                    <div class="space-y-1">
                        <h1 class="text-2xl leading-tight font-semibold">
                            {{ company.company_name }}
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            Review company profile, representative info, and
                            submitted documents.
                        </p>
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
                    <Badge variant="outline"
                        >Code: {{ company.company_code ?? '—' }}</Badge
                    >
                    <Badge :variant="statusVariant(company.status ?? null)">{{
                        humanize(company.status)
                    }}</Badge>
                    <Badge variant="outline">
                        {{
                            company.business_type
                                ? humanize(company.business_type)
                                : '—'
                        }}
                    </Badge>
                </div>

                <!-- Top grid: Company + Representative -->
                <div class="grid gap-4 lg:grid-cols-3">
                    <!-- Company Details -->
                    <Card class="lg:col-span-2">
                        <CardHeader>
                            <div class="flex items-center gap-2">
                                <Building2
                                    class="h-5 w-5 text-muted-foreground"
                                />
                                <div>
                                    <CardTitle>Company Details</CardTitle>
                                    <CardDescription
                                        >Basic information and contact
                                        details.</CardDescription
                                    >
                                </div>
                            </div>
                        </CardHeader>

                        <CardContent>
                            <Table class="w-full">
                                <TableBody>
                                    <TableRow>
                                        <TableCell
                                            class="w-56 py-3 text-sm font-medium text-muted-foreground"
                                            >Email</TableCell
                                        >
                                        <TableCell class="py-3">{{
                                            company.company_email ?? '—'
                                        }}</TableCell>
                                    </TableRow>
                                    <TableRow>
                                        <TableCell
                                            class="w-56 py-3 text-sm font-medium text-muted-foreground"
                                            >Phone</TableCell
                                        >
                                        <TableCell class="py-3">{{
                                            company.company_phone ?? '—'
                                        }}</TableCell>
                                    </TableRow>
                                    <TableRow>
                                        <TableCell
                                            class="w-56 py-3 text-sm font-medium text-muted-foreground"
                                            >Address</TableCell
                                        >
                                        <TableCell class="py-3">{{
                                            company.company_address ?? '—'
                                        }}</TableCell>
                                    </TableRow>
                                    <TableRow>
                                        <TableCell
                                            class="w-56 py-3 text-sm font-medium text-muted-foreground"
                                            >Registration No.</TableCell
                                        >
                                        <TableCell class="py-3">{{
                                            company.registration_number ?? '—'
                                        }}</TableCell>
                                    </TableRow>
                                    <TableRow>
                                        <TableCell
                                            class="w-56 py-3 text-sm font-medium text-muted-foreground"
                                            >Created</TableCell
                                        >
                                        <TableCell class="py-3">
                                            {{
                                                formatDate(company.created_at)
                                            }}
                                            •
                                            {{ company.creator?.name ?? 'N/A' }}
                                        </TableCell>
                                    </TableRow>
                                    <TableRow>
                                        <TableCell
                                            class="w-56 py-3 text-sm font-medium text-muted-foreground"
                                            >Last Updated</TableCell
                                        >
                                        <TableCell class="py-3">
                                            {{
                                                company.updated_at_human ?? '—'
                                            }}
                                            •
                                            {{ company.updater?.name ?? 'N/A' }}
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>

                    <!-- Representative Details -->
                    <Card>
                        <CardHeader>
                            <div class="flex items-center gap-2">
                                <UserRound
                                    class="h-5 w-5 text-muted-foreground"
                                />
                                <div>
                                    <CardTitle>Representative</CardTitle>
                                    <CardDescription
                                        >Who to contact for
                                        coordination.</CardDescription
                                    >
                                </div>
                            </div>
                        </CardHeader>

                        <CardContent class="space-y-3">
                            <div class="space-y-1">
                                <div class="text-xs text-muted-foreground">
                                    Name
                                </div>
                                <div class="text-sm font-medium">
                                    {{
                                        company.authorized_representative_name ??
                                        '—'
                                    }}
                                </div>
                            </div>

                            <div class="space-y-1">
                                <div class="text-xs text-muted-foreground">
                                    Position
                                </div>
                                <div class="text-sm">
                                    {{
                                        company.authorized_representative_position ??
                                        '—'
                                    }}
                                </div>
                            </div>

                            <div class="space-y-1">
                                <div class="text-xs text-muted-foreground">
                                    Contact
                                </div>
                                <div class="text-sm">
                                    {{
                                        company.authorized_representative_contact ??
                                        '—'
                                    }}
                                </div>
                            </div>

                            <div
                                v-if="!repHasAny"
                                class="rounded-md border p-3 text-xs text-muted-foreground"
                            >
                                No representative details provided.
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Documents -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center gap-2">
                            <FileText class="h-5 w-5 text-muted-foreground" />
                            <div>
                                <CardTitle>Document Details</CardTitle>
                                <CardDescription>
                                    Review uploaded documents, verify, mark
                                    invalid, download, or delete.
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>File</TableHead>
                                    <TableHead>Remarks</TableHead>
                                    <TableHead>Uploaded By</TableHead>
                                    <TableHead>Uploaded At</TableHead>
                                    <TableHead>Verified By</TableHead>
                                    <TableHead>Verified At</TableHead>
                                    <TableHead class="w-20 text-right"
                                        >Action</TableHead
                                    >
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <TableRow
                                    v-for="doc in company.documents ?? []"
                                    :key="doc.id"
                                >
                                    <TableCell>{{
                                        humanize(doc.doc_type)
                                    }}</TableCell>

                                    <TableCell>
                                        <Badge
                                            :variant="statusVariant(doc.status)"
                                        >
                                            {{ humanize(doc.status) }}
                                        </Badge>
                                    </TableCell>

                                    <TableCell
                                        class="max-w-[240px] truncate"
                                        :title="doc.original_name ?? ''"
                                    >
                                        {{ doc.original_name ?? '—' }}
                                    </TableCell>

                                    <!-- Remarks: clean View button -->
                                    <TableCell class="w-28">
                                        <template v-if="doc.remarks">
                                            <Popover>
                                                <PopoverTrigger as-child>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        class="h-8"
                                                    >
                                                        <MessageSquareText
                                                            class="mr-2 h-4 w-4"
                                                        />
                                                        View
                                                    </Button>
                                                </PopoverTrigger>

                                                <PopoverContent
                                                    align="start"
                                                    class="w-80"
                                                >
                                                    <div class="space-y-2">
                                                        <div
                                                            class="text-sm font-medium"
                                                        >
                                                            Remarks
                                                        </div>
                                                        <div
                                                            class="text-sm whitespace-pre-wrap text-muted-foreground"
                                                        >
                                                            {{ doc.remarks }}
                                                        </div>
                                                        <div
                                                            class="text-xs text-muted-foreground"
                                                        >
                                                            {{
                                                                humanize(
                                                                    doc.doc_type,
                                                                )
                                                            }}
                                                            •
                                                            {{
                                                                humanize(
                                                                    doc.status,
                                                                )
                                                            }}
                                                        </div>
                                                    </div>
                                                </PopoverContent>
                                            </Popover>
                                        </template>

                                        <template v-else>
                                            <span class="text-muted-foreground"
                                                >—</span
                                            >
                                        </template>
                                    </TableCell>

                                    <TableCell>{{
                                        doc.uploader?.name ?? '—'
                                    }}</TableCell>
                                    <TableCell>{{
                                        formatDateTime(doc.created_at ?? null)
                                    }}</TableCell>
                                    <TableCell>{{
                                        doc.verifier?.name ?? '—'
                                    }}</TableCell>
                                    <TableCell>{{
                                        formatDateTime(doc.verified_at ?? null)
                                    }}</TableCell>

                                    <!-- Actions dropdown -->
                                    <TableCell class="text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
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
                                                class="w-56"
                                            >
                                                <DropdownMenuLabel
                                                    >Actions</DropdownMenuLabel
                                                >
                                                <DropdownMenuSeparator />

                                                <DropdownMenuItem
                                                    v-if="
                                                        doc.status ===
                                                        'verified'
                                                    "
                                                    class="cursor-pointer"
                                                    @click="
                                                        openConfirm(
                                                            'unverify',
                                                            doc,
                                                        )
                                                    "
                                                >
                                                    <RotateCcw
                                                        class="mr-2 h-4 w-4"
                                                    />
                                                    Unverify
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    v-else-if="
                                                        doc.status === 'invalid'
                                                    "
                                                    class="cursor-pointer"
                                                    @click="
                                                        openConfirm(
                                                            'unverify',
                                                            doc,
                                                        )
                                                    "
                                                >
                                                    <RotateCcw
                                                        class="mr-2 h-4 w-4"
                                                    />
                                                    Uninvalid
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    v-else
                                                    class="cursor-pointer"
                                                    @click="
                                                        openConfirm(
                                                            'verify',
                                                            doc,
                                                        )
                                                    "
                                                >
                                                    <CheckCircle2
                                                        class="mr-2 h-4 w-4"
                                                    />
                                                    Verify
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    class="cursor-pointer"
                                                    @click="openReject(doc.id)"
                                                >
                                                    <XCircle
                                                        class="mr-2 h-4 w-4"
                                                    />
                                                    Invalid (remarks)
                                                </DropdownMenuItem>

                                                <DropdownMenuSeparator />

                                                <DropdownMenuItem
                                                    class="cursor-pointer"
                                                    @click="
                                                        openConfirm(
                                                            'download',
                                                            doc,
                                                        )
                                                    "
                                                >
                                                    <Download
                                                        class="mr-2 h-4 w-4"
                                                    />
                                                    Download
                                                </DropdownMenuItem>

                                                <DropdownMenuItem
                                                    class="cursor-pointer text-destructive"
                                                    @click="
                                                        openConfirm(
                                                            'delete',
                                                            doc,
                                                        )
                                                    "
                                                >
                                                    <Trash2
                                                        class="mr-2 h-4 w-4"
                                                    />
                                                    Delete
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>

                                <TableRow
                                    v-if="
                                        (company.documents ?? []).length === 0
                                    "
                                >
                                    <TableCell
                                        colspan="9"
                                        class="py-8 text-center text-muted-foreground"
                                    >
                                        No documents uploaded yet.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Invalid remarks dialog -->
        <Dialog v-model:open="rejectOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Invalid Document</DialogTitle>
                    <DialogDescription
                        >Add remarks so the company knows what to
                        fix.</DialogDescription
                    >
                </DialogHeader>

                <Separator />

                <div class="space-y-2">
                    <Label>Remarks *</Label>
                    <Textarea
                        v-model="rejectForm.remarks"
                        placeholder="Reason for invalid..."
                    />
                    <InputError
                        class="mt-1"
                        :message="rejectForm.errors.remarks"
                    />
                </div>

                <DialogFooter class="gap-2">
                    <Button
                        variant="outline"
                        @click="rejectOpen = false"
                        :disabled="rejectForm.processing"
                    >
                        Cancel
                    </Button>
                    <Button
                        variant="destructive"
                        @click="submitReject"
                        :disabled="rejectForm.processing"
                    >
                        {{
                            rejectForm.processing
                                ? 'Invalidating...'
                                : 'Invalid'
                        }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Confirm dialog -->
        <AlertDialog v-model:open="confirmOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{{ confirmTitle() }}</AlertDialogTitle>
                    <AlertDialogDescription>{{
                        confirmDescription()
                    }}</AlertDialogDescription>
                </AlertDialogHeader>

                <AlertDialogFooter>
                    <AlertDialogCancel
                        :disabled="actionForm.processing"
                        @click="confirmOpen = false"
                    >
                        Cancel
                    </AlertDialogCancel>
                    <AlertDialogAction
                        :disabled="actionForm.processing"
                        @click="runConfirmedAction"
                    >
                        {{
                            actionForm.processing ? 'Processing...' : 'Continue'
                        }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
