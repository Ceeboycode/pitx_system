<script setup lang="ts">

import { ref, computed } from 'vue'

import {
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent
} from '@/components/ui/card'
import {
  DropdownMenu,
  DropdownMenuTrigger,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
} from '@/components/ui/dropdown-menu'
import {
  Popover,
  PopoverTrigger,
  PopoverContent,
} from '@/components/ui/popover'
import Button from '@/components/ui/button/Button.vue'
import { Badge } from '@/components/ui/_badge'
import {
    DocumentTable,
    DocumentTableCard,
    DocumentTableContent,
    DocumentTableData,
    DocumentTableMoreButton,
    DocumentTableRow,
} from '@/components/ui/_document-table'

import { download as downloadCompanyDocument } from '@/routes/companies/documents';

import documentsUrl from '@/components/assets/Documents-rafiki.svg';

import {
  RiMore2Line,
  RiCheckboxMultipleBlankLine,
  RiCheckboxMultipleLine,
  RiDownloadLine,
  RiChat4Line,
  RiFile2Fill,

} from 'vue-remix-icons'

import DocumentPreviewDialog from '@/components/internal/documents/DocumentPreviewDialog.vue'
import { companyDocumentPreview, companyPreviewActions } from '@/lib/document-preview'
import DocumentRejectDialog from '@/components/internal/company/show/documents/DocumentRejectDialog.vue'
import DocumentActionConfirmDialog, { type ConfirmAction } from '@/components/internal/company/show/documents/DocumentActionConfirmDialog.vue'
import DownloadVerifiedDocumentsDialog from '@/components/internal/company/show/documents/DownloadVerifiedDocumentsDialog.vue'

import { formatDate, formatDateTime, humanize, isExpired } from '@/lib/format'
import { canPreview } from '@/lib/files'
import {
  docStatusClass as statusClass,
  docStatusDot as statusDot,
  isDocVerified,
} from '@/lib/company-documents'
import DocumentStatusBadge from '@/components/internal/company/show/documents/DocumentStatusBadge.vue'
import type { CompanyDocument } from '@/types/company'

const props = defineProps<{
    company: {
        id: number;
        company_name: string;
        documents?: CompanyDocument[];
    };
}>();

const openMenus = ref<Record<number, {
  open: boolean
  x: number
  y: number
  mode: 'trigger' | 'context'
}>>({})

function setMenuState(docId: number, next: { open: boolean; x: number; y: number; mode: 'trigger' | 'context' }) {
    openMenus.value = {
        ...openMenus.value,
        [docId]: next,
    }
}

function openRowMenu(event: MouseEvent, doc: CompanyDocument) {
    event.preventDefault()

    setMenuState(doc.id, {
        open: true,
        x: event.clientX,
        y: event.clientY,
        mode: 'context',
    })
}

const company = computed(() => props.company);
const docs = computed(() => props.company.documents ?? []);

const bulkConfirmOpen = ref(false);
const verifiedCount = computed(
    () => docs.value.filter((d) => isDocVerified(d)).length,
);

function openBulkConfirm() {
    bulkConfirmOpen.value = true;
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

const previewOpen = ref(false);
const previewDoc = ref<CompanyDocument | null>(null);

function openPreview(doc: CompanyDocument) {
    previewDoc.value = doc;
    previewOpen.value = true;
}

const confirmOpen = ref(false);
const confirmAction = ref<ConfirmAction>('verify');
const confirmDoc = ref<CompanyDocument | null>(null);

function openConfirm(action: ConfirmAction, doc: CompanyDocument) {
    confirmAction.value = action;
    confirmDoc.value = doc;
    confirmOpen.value = true;
}

const rejectOpen = ref(false);
const rejectDocId = ref<number | null>(null);

function openReject(docId: number) {
    rejectDocId.value = docId;
    rejectOpen.value = true;
}

</script>

<template>
  <Card class="order-2 col-span-2">
    <CardHeader class="flex flex-row justify-between">
        <div class="flex flex-col">
          <CardTitle>Documents</CardTitle>
          <CardDescription>Review subumitted company documents.</CardDescription>
        </div>
        <div class="flex flex-1 justify-end gap-2">
            <DropdownMenu class="w-fit">
                <DropdownMenuTrigger as-child class="m-0">
                    <div class="inline-flex">
                        <Button
                            variant="header-actions"
                            class="text-custom-shadow"
                            size="icon"
                            aria-label="Open company actions"
                        >
                            <RiMore2Line class="h-4 w-4 shrink-0" />
                        </Button>
                    </div>
                </DropdownMenuTrigger>

                <DropdownMenuContent align="end" class="w-fit">
                    <DropdownMenuItem
                        class="group cursor-pointer"
                        @click="toggleSelectMode"
                    >
                        <RiCheckboxMultipleBlankLine v-if="selectMode" class="h-4 w-4 shrink-0 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                        <RiCheckboxMultipleLine v-else class="h-4 w-4 shrink-0 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                        <span v-if="selectMode" class="text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow">
                            Cancel select
                        </span>
                        <span v-else>
                            Select
                        </span>
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        class="group cursor-pointer"
                        @click="openBulkConfirm()"
                    >
                        <RiDownloadLine class="shrink-0 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                        <span>Download all verified</span>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </CardHeader>
    <CardContent class="no-scrollbar min-h-0 flex-1 overflow-y-auto">
        <div v-if="selectMode" class="pt-2 flex justify-between">
            <Button
                v-if="selectMode"
                variant="float"
                @click="selectAll"
            >
                <RiCheckboxMultipleBlankLine v-if="allSelected" class="h-4 w-4 shrink-0" />
                <RiCheckboxMultipleLine v-else class="h-4 w-4 shrink-0" />
                <span>
                    {{ allSelected ? 'Deselect all' : 'Select all' }}
                </span>
            </Button>
            <Button
                v-if="docs.length > 0"
                variant="float"
                :disabled="selectMode && selectedDocIds.length === 0"
                @click="downloadSelected()"
            >
                <RiDownloadLine class="h-4 w-4 shrink-0" />
                <span class="">
                    {{ allSelected ? `Download all` : (selectMode && `Download (${selectedDocIds.length})`)}}
                </span>
            </Button>
        </div>
        <DocumentTableCard :docs-data-length="docs.length" class="mt-4">
            <DocumentTable v-if="docs.length > 0">
                <DocumentTableContent>
                    <!-- TODO: may need to change some attributes for the updated right click functionality -->
                    <DocumentTableRow
                        v-for="(doc, rowIndex) in docs"
                        :key="doc.id"
                        :class="[
                            rowIndex === docs.length - 1 ? 'rounded-b-md border-b-0' : '',
                            !selectMode ? 'group/row' : ''
                        ]"
                        :status="doc.status === 'inactive' ? 'inactive' : 'default'"
                        @click.left="openPreview(doc)"
                        @contextmenu.prevent="openRowMenu($event, doc)"
                    >
                        <div
                            class="flex items-start pt-1 overflow-hidden transition-all duration-200 bg-orange-300"
                            :class="selectMode ? 'w-5 opacity-100 me-2' : 'w-0 opacity-0'"
                        >
                            <input
                                type="checkbox"
                                class="h-4 w-4"
                                :checked="selectedDocIds.includes(doc.id)"
                                @change="setDoc(doc.id, ($event.target as HTMLInputElement).checked)"
                            />
                        </div>

                        <DocumentTableData class="flex-1">
                            <div class="min-w-0">
                                <div
                                    class="flex flex-wrap items-center gap-2"
                                >
                                    <p
                                        class="text-sm font-semibold text-foreground"
                                    >
                                        {{ humanize(doc.doc_type) }}
                                    </p>
                                    <DocumentStatusBadge :doc="doc" />
                                </div>

                                <div>
                                    <button
                                        v-if="canPreview(doc)"
                                        class="cursor-pointer flex items-center gap-2 text-sm text-muted-foreground underline-offset-2 hover:underline"
                                        :title="doc.original_name ?? ''"
                                        @click="openPreview(doc)"
                                    >
                                        <RiFile2Fill class="h-4- w-4 shrink-0" />
                                        <span class="truncate">{{
                                            doc.original_name ?? '—'
                                        }}</span>
                                    </button>
                                </div>

                                <div class="overflow-hidden max-h-0 opacity-0 group-hover/row:max-h-96 group-hover/row:opacity-100 transition-all delay-200 duration-200 flex-col">
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
                                                    <RiChat4Line
                                                        class="h-4 w-4 shrink-0"
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
                        </DocumentTableData>

                        <DocumentTableMoreButton
                            :open="openMenus[doc.id]?.open ?? false"
                            :x="openMenus[doc.id]?.x ?? 0"
                            :y="openMenus[doc.id]?.y ?? 0"
                            :mode="openMenus[doc.id]?.mode ?? 'trigger'"
                            @update:open="(value) => {
                                const current = openMenus[doc.id] ?? { open: false, x: 0, y: 0, mode: 'trigger' }

                                setMenuState(doc.id, {
                                    ...current,
                                    open: value,
                                    mode: value ? current.mode : 'trigger',
                                })
                            }"
                        >
                            <DropdownMenuLabel>
                                <span>{{ humanize(doc.doc_type) }}</span>
                            </DropdownMenuLabel>
                            <DropdownMenuItem
                                as-child
                                class="group cursor-pointer hover:bg-slate-100"
                                @click="
                                    openConfirm('download', doc)
                                "
                            >
                                <RiDownloadLine class="h-4 w-4 shrink-0 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg" />
                                Download
                            </DropdownMenuItem>
                        </DocumentTableMoreButton>
                    </DocumentTableRow>
                </DocumentTableContent>
            </DocumentTable>

            <div v-else class="flex min-h-0 flex-1 items-center justify-center p-6 text-center">
                <div class="flex w-full max-w-md flex-col items-center justify-center gap-2">
                    <img
                        :src="documentsUrl"
                        alt=""
                        class="w-1/3 object-contain opacity-90"
                        aria-hidden="true"
                    />
                    <div class="space-y-1">
                        <p class="text-custom-shadow text-base font-semibold">No documents found</p>
                        <p class="text-custom-shadow/80 text-sm">
                            Documents submitted by the company will appear here.
                        </p>
                    </div>
                </div>
            </div>
        </DocumentTableCard>
        <div>
            <!-- TODO: add smooth transition for when this div appears and when it disappears-->
            <div v-if="selectMode" class="pt-2 flex justify-between">
                <Button
                    v-if="selectMode"
                    variant="float"
                    @click="selectAll"
                >
                    <RiCheckboxMultipleBlankLine v-if="allSelected" class="h-4 w-4 shrink-0" />
                    <RiCheckboxMultipleLine v-else class="h-4 w-4 shrink-0" />
                    <span>
                        {{ allSelected ? 'Deselect all' : 'Select all' }}
                    </span>
                </Button>
                <Button
                    v-if="docs.length > 0"
                    variant="float"
                    :disabled="selectMode && selectedDocIds.length === 0"
                    @click="downloadSelected()"
                >
                    <RiDownloadLine class="h-4 w-4 shrink-0" />
                    <span class="">
                        {{ allSelected ? `Download all` : (selectMode && `Download (${selectedDocIds.length})`)}}
                    </span>
                </Button>
            </div>
            <div class="py-0 border border-custom-bg-dark rounded-md mt-4">
                <div
                    v-for="doc in docs"
                    :key="doc.id"
                    class="grid grid-cols-[auto_1fr_auto] transition-colors text-custom-shadow/80 transition-colors hover:bg-custom-secondary/10 hover:text-custom-shadow dark:border-custom-bg-light py-1.5 px-3"
                    :class="!selectMode ? 'group/row' : ''"
                >

                    <div
                        class="flex items-start pt-1 overflow-hidden transition-all duration-200"
                        :class="selectMode ? 'w-5 opacity-100 me-2' : 'w-0 opacity-0'"
                    >
                    <!-- TODO: use the checkbox component here-->
                        <input
                            type="checkbox"
                            class="h-4 w-4"
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
                            <DocumentStatusBadge :doc="doc" />
                        </div>

                        <div>
                            <button
                                v-if="canPreview(doc)"
                                class="cursor-pointer flex items-center gap-2 text-sm text-muted-foreground underline-offset-2 hover:underline"
                                :title="doc.original_name ?? ''"
                                @click="openPreview(doc)"
                            >
                                <RiFile2Fill class="h-4- w-4 shrink-0" />
                                <span class="truncate">{{
                                    doc.original_name ?? '—'
                                }}</span>
                            </button>
                        </div>

                        <div class="overflow-hidden max-h-0 opacity-0 group-hover/row:max-h-96 group-hover/row:opacity-100 transition-all delay-200 duration-200 flex-col">
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
                                            <RiChat4Line
                                                class="h-4 w-4 shrink-0"
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
                                    variant="table-more"
                                    size="icon-more"
                                >
                                    <RiMore2Line
                                        class="h-4 w-4 shrink-0"
                                    />

                                </Button>
                            </DropdownMenuTrigger>

                            <DropdownMenuContent
                                align="end"
                                class="w-fit rounded-xl border-slate-200 shadow-lg"
                            >
                                <DropdownMenuItem
                                    class="rounded-lg cursor-pointer hover:bg-slate-100"
                                    @click="
                                        openConfirm('download', doc)
                                    "
                                >
                                    <RiDownloadLine
                                        class="h-4 w-4 shrink-0"
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

<DocumentPreviewDialog
    v-model:open="previewOpen"
    :doc="companyDocumentPreview(previewDoc, company.id)"
    :actions="companyPreviewActions(previewDoc)"
    @action="(key) => previewDoc && (key === 'verify' ? openConfirm('verify', previewDoc) : openReject(previewDoc.id))"
/>

<DocumentRejectDialog
    v-model:open="rejectOpen"
    :document-id="rejectDocId"
    :company-id="company.id"
/>

<DocumentActionConfirmDialog
    v-model:open="confirmOpen"
    :doc="confirmDoc"
    :action="confirmAction"
    :company-id="company.id"
/>

<DownloadVerifiedDocumentsDialog
    v-model:open="bulkConfirmOpen"
    :company-id="company.id"
    :verified-count="verifiedCount"
/>
</template>
