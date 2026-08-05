<script setup lang="ts">
import CompanyDashboardController from '@/actions/App/Http/Controllers/CompanyDashboardController';
import { storeResubmission } from '@/actions/App/Http/Controllers/CompanyRegistration';
import InputError from '@/components/InputError.vue';
import AllTheDataRafikiUrl from '@/components/assets/All-the-data-rafiki.svg';
import BusDriverRafikiUrl from '@/components/assets/Bus-driver-rafiki.svg';
import FilingSystemRafikiUrl from '@/components/assets/Filing-system-rafiki.svg';
import WarningRafikiUrl from '@/components/assets/Warning-rafiki.svg';
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
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Calendar as CalendarPicker } from '@/components/ui/calendar';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Separator } from '@/components/ui/separator';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { CalendarDate } from '@internationalized/date';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import {
    RiAddLine,
    RiCalendarLine,
    RiCloseLine,
    RiDashboardHorizontalLine,
    RiDeleteBinLine,
    RiDownloadLine,
    RiEyeLine,
    RiFileTextLine,
    RiLoaderLine,
    RiRefreshLine,
} from 'vue-remix-icons';

type DocRow = {
    id: number;
    doc_type: string;
    status: string;
    original_name?: string | null;
    remarks?: string | null;
    expires_at?: string | null;
    mime_type?: string | null;
    file_size?: number | null;
    file_type?: string | null;
    can_preview?: boolean;
    preview_url?: string | null;
    download_url?: string | null;
};

type UploadRules = {
    extensions: string[];
    accept: string;
    maxKb: number;
    maxMb: number;
    previewableExtensions: string[];
};

type ResubmissionDocumentInput = {
    file: File | null;
    issued_at: string;
    expires_at: string;
};

type SupportingDocumentInput = ResubmissionDocumentInput & {
    id: number;
    title: string;
};

const props = defineProps<{
    embedded?: boolean;
    company: {
        id: number;
        company_name: string;
        company_code?: string | null;
        status: string;
        documents?: DocRow[];
    };
    meta: {
        title: string;
        description: string;
        icon: string;
        color: string;
    };
    uploadRules?: UploadRules;
}>();

const fallbackUploadRules: UploadRules = {
    extensions: ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'],
    accept: '.pdf,.doc,.docx,.jpg,.jpeg,.png',
    maxKb: 5120,
    maxMb: 5,
    previewableExtensions: ['pdf', 'jpg', 'jpeg', 'png'],
};

const uploadRules = computed(() => props.uploadRules ?? fallbackUploadRules);
const allowedFileTypesText = computed(() =>
    uploadRules.value.extensions
        .map((extension) => extension.toUpperCase())
        .join(', '),
);
const maxFileSizeText = computed(() => `${uploadRules.value.maxMb} MB`);

function humanize(text?: string | null) {
    if (!text) return '—';
    return text.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

const allDocs = computed(() => props.company.documents ?? []);
const actionRequiredDocs = computed(() =>
    allDocs.value.filter((doc) => ['invalid', 'expired'].includes(doc.status)),
);

const statusIllustration = computed(() => {
    switch (props.meta.icon) {
        case 'clock':
            return AllTheDataRafikiUrl;
        case 'warning':
            return WarningRafikiUrl;
        case 'check':
            return BusDriverRafikiUrl;
        case 'draft':
            return FilingSystemRafikiUrl;
        default:
            return null;
    }
});

let timer: ReturnType<typeof setInterval> | null = null;
const refreshing = ref(false);

function doRefresh() {
    refreshing.value = true;
    router.reload({
        onFinish: () => {
            refreshing.value = false;
        },
    });
}

onMounted(() => {
    if (props.company.status === 'for_verification') {
        timer = setInterval(doRefresh, 30_000);
    }
});

onUnmounted(() => {
    if (timer) clearInterval(timer);
});

const resubmitForm = useForm({
    documents: {} as Record<string, ResubmissionDocumentInput>,
    supporting_documents: [] as SupportingDocumentInput[],
});

for (const doc of actionRequiredDocs.value) {
    resubmitForm.documents[doc.doc_type] = {
        file: null,
        issued_at: '',
        expires_at: '',
    };
}

const openDatePicker = ref<string | null>(null);
const confirmResubmissionOpen = ref(false);
const previewOpen = ref(false);
const previewFile = ref<{
    title: string;
    name: string;
    size: string;
    url: string;
    type: 'image' | 'pdf';
} | null>(null);
const filePreviewUrls = ref<Record<string, string>>({});
const documentInputResetKeys = ref<Record<string, number>>({});
const supportingInputResetKeys = ref<Record<number, number>>({});
let supportingDocumentId = 0;

const selectedResubmissionDocuments = computed(() => [
    ...actionRequiredDocs.value
        .filter((doc) => resubmitForm.documents[doc.doc_type]?.file)
        .map((doc) => ({
            label: humanize(doc.doc_type),
            file: resubmitForm.documents[doc.doc_type].file as File,
        })),
    ...resubmitForm.supporting_documents
        .filter((document) => document.file)
        .map((document, index) => ({
            label: document.title || `Supporting Document ${index + 1}`,
            file: document.file as File,
        })),
]);

function parseCalendarDate(value: string): CalendarDate | undefined {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return undefined;
    const [year, month, day] = value.split('-').map(Number);

    try {
        return new CalendarDate(year, month, day);
    } catch {
        return undefined;
    }
}

function selectDocumentDate(
    docType: string,
    field: 'issued_at' | 'expires_at',
    value: CalendarDate | undefined,
) {
    resubmitForm.documents[docType][field] = value
        ? `${value.year}-${String(value.month).padStart(2, '0')}-${String(value.day).padStart(2, '0')}`
        : '';
    openDatePicker.value = null;
}

function handleFile(docType: string, event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    resubmitForm.documents[docType].file = file;
    resubmitForm.clearErrors(`documents.${docType}.file` as never);
    setPreviewUrl(`documents.${docType}`, file);
}

function submitResubmission() {
    confirmResubmissionOpen.value = false;
    resubmitForm.post(storeResubmission().url, {
        forceFormData: true,
        preserveScroll: true,
    });
}

function requestResubmission() {
    confirmResubmissionOpen.value = true;
}

function supportingDocError(
    index: number,
    field: 'title' | 'file' | 'issued_at' | 'expires_at',
): string | undefined {
    return (resubmitForm.errors as Record<string, string>)[
        `supporting_documents.${index}.${field}`
    ];
}

function resubmitError(field: string): string | undefined {
    return (resubmitForm.errors as Record<string, string>)[field];
}

function addSupportingDocument() {
    supportingDocumentId += 1;
    resubmitForm.supporting_documents.push({
        id: supportingDocumentId,
        title: '',
        file: null,
        issued_at: '',
        expires_at: '',
    });
}

function removeSupportingDocument(index: number) {
    const document = resubmitForm.supporting_documents[index];
    if (!document) return;

    revokePreviewUrl(`supporting_documents.${document.id}`);
    delete supportingInputResetKeys.value[document.id];
    resubmitForm.supporting_documents.splice(index, 1);
}

function handleSupportingFile(index: number, event: Event) {
    const input = event.target as HTMLInputElement;
    const document = resubmitForm.supporting_documents[index];
    if (!document) return;

    const file = input.files?.[0] ?? null;
    document.file = file;
    resubmitForm.clearErrors(`supporting_documents.${index}.file` as never);
    setPreviewUrl(`supporting_documents.${document.id}`, file);
}

function removeDocumentFile(docType: string) {
    resubmitForm.documents[docType].file = null;
    revokePreviewUrl(`documents.${docType}`);
    documentInputResetKeys.value[docType] =
        (documentInputResetKeys.value[docType] ?? 0) + 1;
}

function removeSupportingFile(index: number) {
    const document = resubmitForm.supporting_documents[index];
    if (!document) return;

    document.file = null;
    revokePreviewUrl(`supporting_documents.${document.id}`);
    supportingInputResetKeys.value[document.id] =
        (supportingInputResetKeys.value[document.id] ?? 0) + 1;
}

function setPreviewUrl(key: string, file: File | null) {
    revokePreviewUrl(key);

    if (file && canPreviewFile(file)) {
        filePreviewUrls.value[key] = URL.createObjectURL(file);
    }
}

function revokePreviewUrl(key: string) {
    if (!filePreviewUrls.value[key]) return;

    URL.revokeObjectURL(filePreviewUrls.value[key]);
    delete filePreviewUrls.value[key];
}

function fileExtension(file?: File | null) {
    return file?.name.split('.').pop()?.toLowerCase() ?? '';
}

function canPreviewFile(file?: File | null) {
    return (
        !!file &&
        uploadRules.value.previewableExtensions.includes(fileExtension(file))
    );
}

function previewType(file: File): 'image' | 'pdf' {
    return fileExtension(file) === 'pdf' ? 'pdf' : 'image';
}

function documentPreviewType(doc: DocRow): 'image' | 'pdf' {
    const mimeType = (doc.mime_type ?? '').toLowerCase();
    const fileName = (doc.original_name ?? '').toLowerCase();
    const isImage =
        mimeType.startsWith('image/') ||
        ['.jpg', '.jpeg', '.png'].some((extension) =>
            fileName.endsWith(extension),
        );

    return isImage ? 'image' : 'pdf';
}

function openUploadedDocumentPreview(doc: DocRow) {
    if (!doc.can_preview || !doc.preview_url) return;

    previewFile.value = {
        title: humanize(doc.doc_type),
        name: doc.original_name ?? humanize(doc.doc_type),
        size: formatFileSize(doc.file_size),
        url: doc.preview_url,
        type: documentPreviewType(doc),
    };
    previewOpen.value = true;
}

function openSelectedFilePreview(
    key: string,
    title: string,
    file?: File | null,
) {
    const url = filePreviewUrls.value[key];

    if (!file || !url || !canPreviewFile(file)) return;

    previewFile.value = {
        title,
        name: file.name,
        size: formatFileSize(file.size),
        url,
        type: previewType(file),
    };
    previewOpen.value = true;
}

function formatFileSize(bytes?: number | null) {
    if (!bytes || bytes <= 0) return '0 B';

    const units = ['B', 'KB', 'MB', 'GB'];
    let value = bytes;
    let unitIndex = 0;

    while (value >= 1024 && unitIndex < units.length - 1) {
        value /= 1024;
        unitIndex++;
    }

    return `${value.toFixed(value >= 10 || unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
}

onUnmounted(() => {
    Object.keys(filePreviewUrls.value).forEach(revokePreviewUrl);
});
</script>

<template>
    <Head v-if="!embedded" title="Registration Status" />

    <div
        :class="
            embedded
                ? 'w-full'
                : 'flex min-h-screen items-center justify-center bg-custom-bg p-6 dark:bg-custom-bg-dark'
        "
    >
        <div
            class="flex w-full flex-col items-center rounded-md border border-dashed border-custom-bg-dark p-6 text-center text-custom-shadow dark:border-none dark:bg-custom-bg-dark dark:shadow-sm dark:shadow-white/5"
        >
            <img
                v-if="statusIllustration"
                :src="statusIllustration"
                alt=""
                class="w-1/3 object-contain opacity-90"
                aria-hidden="true"
            />
            <p class="mb-2 text-2xl font-semibold text-custom-shadow">
                {{ meta.title }}
            </p>
            <p class="text-custom-shadow">
                {{ meta.description }}
            </p>
        </div>

        <div v-if="allDocs.length" class="mt-2 space-y-2">
            <section
                v-for="doc in allDocs"
                :key="doc.id"
                class="rounded-md border p-3 transition-colors"
                :class="{
                    'border-custom-accent-3 bg-custom-accent-3/10':
                        doc.status === 'verified',
                    'border-dashed border-custom-bg-dark dark:border-custom-bg-light':
                        doc.status === 'pending',
                    'border-dashed border-destructive/40 bg-destructive/10':
                        doc.status === 'expired' || doc.status === 'invalid',
                }"
            >
                <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-2">
                        <p
                            class="truncate text-sm font-semibold text-custom-shadow"
                        >
                            {{ humanize(doc.doc_type) }}
                        </p>
                        <Badge
                            variant="outline"
                            class="shrink-0 border-none text-custom-shadow"
                            :class="{
                                'bg-custom-accent-3 text-custom-bg-light dark:text-custom-bg-dark':
                                    doc.status === 'verified',
                                'bg-custom-bg-dark dark:bg-custom-bg-light':
                                    doc.status === 'pending',
                                'bg-destructive/30':
                                    doc.status === 'expired' ||
                                    doc.status === 'invalid',
                            }"
                        >
                            {{ humanize(doc.status) }}
                        </Badge>
                    </div>
                    <p
                        v-if="doc.original_name"
                        class="truncate text-custom-shadow/80"
                    >
                        {{ doc.original_name }}
                    </p>
                    <div
                        class="mt-2 flex flex-wrap items-center justify-between gap-2"
                    >
                        <p class="text-xs text-custom-shadow/70">
                            {{ doc.file_type ?? 'Unknown file' }}
                            <template v-if="doc.file_size">
                                · {{ formatFileSize(doc.file_size) }}
                            </template>
                        </p>
                        <div class="flex shrink-0 flex-wrap items-center gap-1">
                            <Button
                                v-if="doc.can_preview && doc.preview_url"
                                type="button"
                                variant="ghost-outline"
                                size="icon-text"
                                @click="openUploadedDocumentPreview(doc)"
                            >
                                <RiEyeLine class="h-4 w-4" />
                                Preview
                            </Button>
                            <Button
                                v-else-if="doc.download_url"
                                variant="ghost-outline"
                                size="icon-text"
                                as-child
                            >
                                <a :href="doc.download_url">
                                    <RiDownloadLine class="h-4 w-4" />
                                    Download
                                </a>
                            </Button>
                        </div>
                    </div>
                    <p
                        v-if="doc.status === 'invalid' && doc.remarks"
                        class="mt-2 rounded-md bg-destructive/10 p-2 text-custom-shadow"
                    >
                        <span class="font-semibold">Reason:</span>
                        {{ doc.remarks }}
                    </p>
                    <p
                        v-if="doc.status === 'expired'"
                        class="mt-2 rounded-md bg-destructive/10 p-2 text-xs text-custom-shadow"
                    >
                        <span class="font-semibold">Expired:</span>
                        {{
                            doc.expires_at ??
                            'Document validity date has passed.'
                        }}
                    </p>
                </div>

                <div
                    v-if="
                        company.status === 'needs_revision' &&
                        resubmitForm.documents[doc.doc_type]
                    "
                    class="mt-2"
                >
                    <div class="grid gap-2 sm:grid-cols-2">
                        <div class="space-y-1 sm:col-span-2">
                            <Label :for="`file_${doc.doc_type}`"
                                >Document</Label
                            >
                            <p class="text-xs text-custom-shadow/70">
                                Allowed: {{ allowedFileTypesText }}. Maximum:
                                {{ maxFileSizeText }}.
                            </p>
                            <Input
                                :id="`file_${doc.doc_type}`"
                                :key="documentInputResetKeys[doc.doc_type] ?? 0"
                                type="file"
                                :accept="uploadRules.accept"
                                class="cursor-pointer p-0 pr-3 file:mr-3 file:h-full file:cursor-pointer file:border-0 file:border-r file:border-custom-bg-dark file:bg-custom-bg-dark file:px-3 file:text-sm file:text-custom-shadow hover:file:bg-custom-bg"
                                @change="handleFile(doc.doc_type, $event)"
                            />
                            <div
                                v-if="resubmitForm.documents[doc.doc_type].file"
                                class="flex flex-wrap items-center justify-between gap-2 rounded-md border border-custom-bg-dark/40 bg-custom-bg-dark/10 px-3 py-2 text-xs text-custom-shadow dark:border-custom-bg-light/30"
                            >
                                <div class="min-w-0">
                                    <p class="truncate font-semibold">
                                        {{
                                            resubmitForm.documents[doc.doc_type]
                                                .file?.name
                                        }}
                                    </p>
                                    <p class="text-custom-shadow/70">
                                        {{
                                            formatFileSize(
                                                resubmitForm.documents[
                                                    doc.doc_type
                                                ].file?.size,
                                            )
                                        }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-1">
                                    <Button
                                        v-if="
                                            canPreviewFile(
                                                resubmitForm.documents[
                                                    doc.doc_type
                                                ].file,
                                            )
                                        "
                                        type="button"
                                        variant="ghost-outline"
                                        size="icon-text"
                                        @click="
                                            openSelectedFilePreview(
                                                `documents.${doc.doc_type}`,
                                                humanize(doc.doc_type),
                                                resubmitForm.documents[
                                                    doc.doc_type
                                                ].file,
                                            )
                                        "
                                    >
                                        <RiEyeLine class="h-4 w-4" />
                                        Preview
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="ghost-outline"
                                        size="icon-text"
                                        @click="
                                            removeDocumentFile(doc.doc_type)
                                        "
                                    >
                                        <RiCloseLine class="h-4 w-4" />
                                        Remove
                                    </Button>
                                </div>
                            </div>
                            <InputError
                                :message="
                                    resubmitForm.errors[
                                        `documents.${doc.doc_type}.file`
                                    ]
                                "
                            />
                        </div>
                        <div class="space-y-1">
                            <Label :for="`iss_${doc.doc_type}`"
                                >Issue Date</Label
                            >
                            <Popover
                                :open="
                                    openDatePicker ===
                                    `${doc.doc_type}_issued_at`
                                "
                                @update:open="
                                    (open) =>
                                        (openDatePicker = open
                                            ? `${doc.doc_type}_issued_at`
                                            : null)
                                "
                            >
                                <div class="flex">
                                    <Input
                                        :id="`iss_${doc.doc_type}`"
                                        v-model="
                                            resubmitForm.documents[doc.doc_type]
                                                .issued_at
                                        "
                                        type="text"
                                        inputmode="numeric"
                                        maxlength="10"
                                        placeholder="YYYY-MM-DD"
                                        class="rounded-r-none"
                                    />
                                    <PopoverTrigger as-child>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="icon"
                                            class="shrink-0 rounded-l-none border border-custom-bg-dark bg-custom-bg hover:bg-custom-secondary/20 dark:border-none dark:bg-custom-bg-dark dark:shadow-sm dark:shadow-white/5"
                                            aria-label="Choose issue date"
                                        >
                                            <RiCalendarLine class="h-4 w-4" />
                                        </Button>
                                    </PopoverTrigger>
                                </div>
                                <PopoverContent
                                    align="start"
                                    class="w-auto p-0"
                                >
                                    <CalendarPicker
                                        :model-value="
                                            parseCalendarDate(
                                                resubmitForm.documents[
                                                    doc.doc_type
                                                ].issued_at,
                                            )
                                        "
                                        initial-focus
                                        @update:model-value="
                                            (value) =>
                                                selectDocumentDate(
                                                    doc.doc_type,
                                                    'issued_at',
                                                    value as
                                                        | CalendarDate
                                                        | undefined,
                                                )
                                        "
                                    />
                                </PopoverContent>
                            </Popover>
                            <InputError
                                :message="
                                    resubmitForm.errors[
                                        `documents.${doc.doc_type}.issued_at`
                                    ]
                                "
                            />
                        </div>
                        <div class="space-y-1">
                            <Label :for="`exp_${doc.doc_type}`"
                                >Expiration Date</Label
                            >
                            <Popover
                                :open="
                                    openDatePicker ===
                                    `${doc.doc_type}_expires_at`
                                "
                                @update:open="
                                    (open) =>
                                        (openDatePicker = open
                                            ? `${doc.doc_type}_expires_at`
                                            : null)
                                "
                            >
                                <div class="flex">
                                    <Input
                                        :id="`exp_${doc.doc_type}`"
                                        v-model="
                                            resubmitForm.documents[doc.doc_type]
                                                .expires_at
                                        "
                                        type="text"
                                        inputmode="numeric"
                                        maxlength="10"
                                        placeholder="YYYY-MM-DD"
                                        class="rounded-r-none"
                                    />
                                    <PopoverTrigger as-child>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="icon"
                                            class="shrink-0 rounded-l-none border border-custom-bg-dark bg-custom-bg hover:bg-custom-secondary/20 dark:border-none dark:bg-custom-bg-dark dark:shadow-sm dark:shadow-white/5"
                                            aria-label="Choose expiration date"
                                        >
                                            <RiCalendarLine class="h-4 w-4" />
                                        </Button>
                                    </PopoverTrigger>
                                </div>
                                <PopoverContent
                                    align="start"
                                    class="w-auto p-0"
                                >
                                    <CalendarPicker
                                        :model-value="
                                            parseCalendarDate(
                                                resubmitForm.documents[
                                                    doc.doc_type
                                                ].expires_at,
                                            )
                                        "
                                        initial-focus
                                        @update:model-value="
                                            (value) =>
                                                selectDocumentDate(
                                                    doc.doc_type,
                                                    'expires_at',
                                                    value as
                                                        | CalendarDate
                                                        | undefined,
                                                )
                                        "
                                    />
                                </PopoverContent>
                            </Popover>
                            <InputError
                                :message="
                                    resubmitForm.errors[
                                        `documents.${doc.doc_type}.expires_at`
                                    ]
                                "
                            />
                        </div>
                    </div>
                </div>
            </section>

            <section
                v-if="company.status === 'needs_revision'"
                class="rounded-md border border-dashed border-custom-bg-dark p-3 dark:border-custom-bg-light"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-custom-shadow">
                            Supporting Documents
                            <span class="text-xs text-custom-shadow/80"
                                >(optional)</span
                            >
                        </p>
                        <p class="text-xs text-custom-shadow/70">
                            Allowed: {{ allowedFileTypesText }}. Maximum:
                            {{ maxFileSizeText }}.
                        </p>
                    </div>
                    <Button
                        type="button"
                        variant="ghost-outline"
                        size="icon-text"
                        @click="addSupportingDocument"
                    >
                        <RiAddLine class="h-4 w-4" />
                        Add
                    </Button>
                </div>

                <div
                    v-for="(
                        document, index
                    ) in resubmitForm.supporting_documents"
                    :key="document.id"
                    class="mt-3 rounded-md border border-custom-bg-dark/40 p-3 dark:border-custom-bg-light/30"
                >
                    <div class="mb-2 flex items-start justify-between gap-2">
                        <p class="font-semibold text-custom-shadow">
                            Supporting Document {{ index + 1 }}
                        </p>
                        <Button
                            type="button"
                            variant="ghost-outline"
                            size="icon"
                            aria-label="Remove supporting document"
                            @click="removeSupportingDocument(index)"
                        >
                            <RiDeleteBinLine class="h-4 w-4" />
                        </Button>
                    </div>

                    <div class="grid gap-2 sm:grid-cols-2">
                        <div class="space-y-1 sm:col-span-2">
                            <Label :for="`supporting_title_${document.id}`"
                                >Title</Label
                            >
                            <Input
                                :id="`supporting_title_${document.id}`"
                                v-model="document.title"
                                class="bg-custom-bg dark:bg-custom-bg-dark"
                            />
                            <InputError
                                :message="supportingDocError(index, 'title')"
                            />
                        </div>
                        <div class="space-y-1 sm:col-span-2">
                            <Label :for="`supporting_file_${document.id}`"
                                >Document</Label
                            >
                            <Input
                                :id="`supporting_file_${document.id}`"
                                :key="
                                    supportingInputResetKeys[document.id] ?? 0
                                "
                                type="file"
                                :accept="uploadRules.accept"
                                class="cursor-pointer p-0 pr-3 file:mr-3 file:h-full file:cursor-pointer file:border-0 file:border-r file:border-custom-bg-dark file:bg-custom-bg-dark file:px-3 file:text-sm file:text-custom-shadow hover:file:bg-custom-bg"
                                @change="handleSupportingFile(index, $event)"
                            />
                            <div
                                v-if="document.file"
                                class="flex flex-wrap items-center justify-between gap-2 rounded-md border border-custom-bg-dark/40 bg-custom-bg-dark/10 px-3 py-2 text-xs text-custom-shadow dark:border-custom-bg-light/30"
                            >
                                <div class="min-w-0">
                                    <p class="truncate font-semibold">
                                        {{ document.file.name }}
                                    </p>
                                    <p class="text-custom-shadow/70">
                                        {{ formatFileSize(document.file.size) }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-1">
                                    <Button
                                        v-if="canPreviewFile(document.file)"
                                        type="button"
                                        variant="ghost-outline"
                                        size="icon-text"
                                        @click="
                                            openSelectedFilePreview(
                                                `supporting_documents.${document.id}`,
                                                document.title ||
                                                    `Supporting Document ${index + 1}`,
                                                document.file,
                                            )
                                        "
                                    >
                                        <RiEyeLine class="h-4 w-4" />
                                        Preview
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="ghost-outline"
                                        size="icon-text"
                                        @click="removeSupportingFile(index)"
                                    >
                                        <RiCloseLine class="h-4 w-4" />
                                        Remove
                                    </Button>
                                </div>
                            </div>
                            <InputError
                                :message="supportingDocError(index, 'file')"
                            />
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div
            v-else
            class="mt-2 rounded-md border border-dashed border-custom-bg-dark p-3 text-center text-sm text-custom-shadow dark:border-custom-bg-light"
        >
            No documents found.
        </div>

        <Separator class="my-4" />

        <div class="flex flex-row items-center justify-end gap-2">
            <!-- CODE: <p class="text-xs text-custom-shadow/80">
                <template v-if="company.status === 'for_verification'">
                    This page refreshes automatically every 30 seconds.
                </template>
                <template v-else-if="company.status === 'needs_revision'">
                    Resubmit flagged documents for another review.
                </template>
            </p> -->

            <!-- CODE: <div class="flex items-center gap-2"> -->
            <Button
                variant="float"
                size="icon-text"
                :disabled="refreshing"
                @click="doRefresh"
            >
                <RiRefreshLine
                    class="h-4 w-4"
                    :class="refreshing ? 'animate-spin' : ''"
                />
                <span class="hidden lg:block">Refresh</span>
            </Button>
            <Button
                v-if="company.status === 'verified'"
                variant="float-primary"
                size="icon-text"
                as-child
            >
                <Link :href="CompanyDashboardController.index().url">
                    <RiDashboardHorizontalLine
                        class="h-4 w-4"
                        :class="refreshing ? 'animate-spin' : ''"
                    />
                    <span class="hidden lg:block">Dashboard</span>
                </Link>
            </Button>
            <div
                v-if="
                    company.status === 'needs_revision' &&
                    actionRequiredDocs.length
                "
            >
                <Button
                    variant="float-primary"
                    class="w-full"
                    :disabled="resubmitForm.processing"
                    @click="requestResubmission"
                >
                    <RiLoaderLine
                        v-if="resubmitForm.processing"
                        class="size-4 animate-spin"
                    />
                    {{ resubmitForm.processing ? 'Submitting...' : 'Resubmit' }}
                </Button>
                <InputError :message="resubmitError('session')" class="mt-2" />
            </div>
        </div>

        <p
            v-if="!embedded"
            class="mt-4 text-center text-xs text-custom-shadow/70"
        >
            Need help?
            <a
                href="mailto:support@example.com"
                class="font-semibold text-custom-accent-3 hover:underline"
            >
                Contact support
            </a>
        </p>

        <AlertDialog v-model:open="confirmResubmissionOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Resubmit Documents</AlertDialogTitle>
                    <AlertDialogDescription>
                        These documents will be sent back to the verification
                        team.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <div
                    class="max-h-56 space-y-2 overflow-auto text-sm text-custom-shadow"
                >
                    <div
                        v-for="document in selectedResubmissionDocuments"
                        :key="`${document.label}-${document.file.name}`"
                        class="flex items-center gap-2 rounded-md border border-custom-bg-dark/40 px-3 py-2 dark:border-custom-bg-light/30"
                    >
                        <RiFileTextLine class="h-4 w-4 shrink-0" />
                        <div class="min-w-0">
                            <p class="truncate font-semibold">
                                {{ document.label }}
                            </p>
                            <p class="truncate text-xs text-custom-shadow/70">
                                {{ document.file.name }} ·
                                {{ formatFileSize(document.file.size) }}
                            </p>
                        </div>
                    </div>
                    <p
                        v-if="!selectedResubmissionDocuments.length"
                        class="rounded-md border border-destructive/40 bg-destructive/10 px-3 py-2 text-destructive"
                    >
                        No files are selected yet.
                    </p>
                </div>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        :disabled="
                            !selectedResubmissionDocuments.length ||
                            resubmitForm.processing
                        "
                        @click="submitResubmission"
                    >
                        Submit
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <Dialog v-model:open="previewOpen">
            <DialogContent
                class="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden p-0"
            >
                <DialogHeader class="border-b px-6 py-4">
                    <DialogTitle class="truncate">
                        {{ previewFile?.title }}
                    </DialogTitle>
                    <DialogDescription class="truncate">
                        {{ previewFile?.name }} · {{ previewFile?.size }}
                    </DialogDescription>
                </DialogHeader>
                <div
                    class="flex-1 overflow-auto bg-custom-bg-dark/10 p-4 dark:bg-custom-bg-light/10"
                >
                    <div
                        v-if="previewFile?.type === 'image'"
                        class="flex min-h-[55vh] items-center justify-center"
                    >
                        <img
                            :src="previewFile.url"
                            :alt="previewFile.name"
                            class="max-h-[70vh] w-auto max-w-full rounded-md border bg-white object-contain"
                        />
                    </div>
                    <div
                        v-else-if="previewFile?.type === 'pdf'"
                        class="h-[70vh] overflow-hidden rounded-md border bg-white"
                    >
                        <iframe
                            :src="previewFile.url"
                            class="h-full w-full"
                            title="PDF preview"
                        />
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
