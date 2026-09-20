<script setup lang="ts">
import { computed, ref } from 'vue';

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import Button from '@/components/ui/button/Button.vue';
import { RiCheckboxMultipleBlankLine, RiCheckboxMultipleLine, RiDownloadLine, RiMore2Line } from 'vue-remix-icons';

import DocumentPreviewDialog from '@/components/internal/documents/DocumentPreviewDialog.vue';
import DocumentsTable from '@/components/internal/documents/DocumentsTable.vue';
import DocumentRejectDialog from '@/components/internal/company/show/documents/DocumentRejectDialog.vue';
import DocumentActionConfirmDialog, { type ConfirmAction } from '@/components/internal/company/show/documents/DocumentActionConfirmDialog.vue';
import DownloadVerifiedDocumentsDialog from '@/components/internal/company/show/documents/DownloadVerifiedDocumentsDialog.vue';

import { useDocumentSelection } from '@/composables/useDocumentSelection';
import { can } from '@/lib/can';
import { isDocVerified } from '@/lib/company-documents';
import { companyDocumentPreview, companyPreviewActions } from '@/lib/document-preview';
import { download as downloadCompanyDocument } from '@/routes/companies/documents';
import type { CompanyDocument } from '@/types/company';

const props = defineProps<{
    company: {
        id: number;
        company_name: string;
        documents?: CompanyDocument[];
    };
}>();

/** Which document the page shows in its side panel (the page owns the panel). */
const previewedId = defineModel<number | null>('previewedId', { default: null });

const docs = computed(() => props.company.documents ?? []);
const rows = computed(() => docs.value.map((doc) => companyDocumentPreview(doc, props.company.id)));

const verifiedCount = computed(() => docs.value.filter((doc) => isDocVerified(doc)).length);

const { selectMode, selectedIds, toggleSelectMode, setSelected, selectAll, reset: resetSelection } = useDocumentSelection(() => docs.value.map((doc) => doc.id));

function findDoc(id: number): CompanyDocument | null {
    return docs.value.find((doc) => doc.id === id) ?? null;
}

function downloadSelected() {
    if (selectedIds.value.length === 0) return;

    for (const id of selectedIds.value) {
        const a = document.createElement('a');
        a.href = downloadCompanyDocument({ company: props.company.id, document: id }).url;
        a.setAttribute('download', '');
        a.style.display = 'none';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }

    resetSelection();
}

// Viewer dialog (double-click)
const viewerOpen = ref(false);
const viewerDocId = ref<number | null>(null);
const viewerDoc = computed(() => (viewerDocId.value === null ? null : findDoc(viewerDocId.value)));

function openViewer(id: number) {
    viewerDocId.value = id;
    viewerOpen.value = true;
}

const canVerify = computed(() => can('company_documents.verify'));
const canReject = computed(() => can('company_documents.reject'));

// Verify / unverify / download confirmation
const confirmOpen = ref(false);
const confirmAction = ref<ConfirmAction>('verify');
const confirmDoc = ref<CompanyDocument | null>(null);

function openConfirm(action: ConfirmAction, doc: CompanyDocument) {
    confirmAction.value = action;
    confirmDoc.value = doc;
    confirmOpen.value = true;
}

function confirmDownload(id: number) {
    const doc = findDoc(id);
    if (doc) openConfirm('download', doc);
}

// Reject (mark invalid)
const rejectOpen = ref(false);
const rejectDocId = ref<number | null>(null);

function openReject(docId: number) {
    rejectDocId.value = docId;
    rejectOpen.value = true;
}

function onViewerAction(key: 'verify' | 'unverify' | 'invalidate') {
    const doc = viewerDoc.value;
    if (!doc) return;

    if (key === 'invalidate') openReject(doc.id);
    else openConfirm(key, doc);
}

const bulkConfirmOpen = ref(false);
</script>

<template>
    <Card class="order-2 col-span-2 h-fit">
        <CardHeader class="flex flex-row justify-between">
            <div class="flex flex-col">
                <CardTitle>Documents</CardTitle>
                <CardDescription>Review submitted company documents.</CardDescription>
            </div>
            <div class="flex flex-1 justify-end gap-2">
                <DropdownMenu class="w-fit">
                    <DropdownMenuTrigger as-child class="m-0">
                        <div class="inline-flex">
                            <Button variant="header-actions" class="text-custom-shadow" size="icon" aria-label="Open document actions">
                                <RiMore2Line class="h-4 w-4 shrink-0" />
                            </Button>
                        </div>
                    </DropdownMenuTrigger>

                    <DropdownMenuContent align="end" class="w-fit">
                        <DropdownMenuItem class="group cursor-pointer" @click="toggleSelectMode">
                            <RiCheckboxMultipleBlankLine v-if="selectMode" class="h-4 w-4 shrink-0 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                            <RiCheckboxMultipleLine v-else class="h-4 w-4 shrink-0 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                            <span>{{ selectMode ? 'Cancel select' : 'Select' }}</span>
                        </DropdownMenuItem>
                        <DropdownMenuItem class="group cursor-pointer" @click="bulkConfirmOpen = true">
                            <RiDownloadLine class="shrink-0 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                            <span>Download all verified</span>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </CardHeader>

        <CardContent class="no-scrollbar min-h-0 flex-1 overflow-y-auto">
            <DocumentsTable
                :documents="rows"
                :previewed-id="previewedId"
                :select-mode="selectMode"
                :selected-ids="selectedIds"
                empty-description="Documents submitted by the company will appear here."
                @preview="previewedId = $event"
                @open="openViewer"
                @download="confirmDownload"
                @toggle="setSelected"
                @select-all="selectAll"
                @download-selected="downloadSelected"
            />
        </CardContent>
    </Card>

    <DocumentPreviewDialog
        v-model:open="viewerOpen"
        :doc="companyDocumentPreview(viewerDoc, company.id)"
        :actions="companyPreviewActions(viewerDoc, { verify: canVerify, invalidate: canReject })"
        @action="onViewerAction"
    />

    <DocumentRejectDialog v-model:open="rejectOpen" :document-id="rejectDocId" :company-id="company.id" />

    <DocumentActionConfirmDialog v-model:open="confirmOpen" :doc="confirmDoc" :action="confirmAction" :company-id="company.id" />

    <DownloadVerifiedDocumentsDialog v-model:open="bulkConfirmOpen" :company-id="company.id" :verified-count="verifiedCount" />
</template>
