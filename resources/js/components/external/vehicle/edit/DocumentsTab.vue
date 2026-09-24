<script setup lang="ts">
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';

import DocumentPreviewDialog from '@/components/internal/documents/DocumentPreviewDialog.vue';
import DocumentsTable from '@/components/internal/documents/DocumentsTable.vue';
import VehicleDocumentsForm from '@/components/internal/company/vehicles/VehicleDocumentsForm.vue';
import { InputMessage } from '@/components/ui/_input-message';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { RiCheckboxMultipleBlankLine, RiCheckboxMultipleLine, RiDownloadLine, RiMore2Line } from 'vue-remix-icons';

import { useDocumentSelection } from '@/composables/useDocumentSelection';
import { can } from '@/lib/can';
import { hasDocsNeedingResubmission } from '@/lib/company-vehicle';
import { vehicleDocumentPreview } from '@/lib/document-preview';

import type { VehicleDocument, VehicleForm, VehicleModel } from './types';

const props = defineProps<{
    vehicle: VehicleModel;
    docTypes: Record<string, string>;
    canEdit: boolean;
}>();

const form = defineModel<VehicleForm>('form', { required: true });

const emit = defineEmits<{
    submit: [];
    reset: [];
}>();

/** Which document the page shows in its side panel (the page owns the panel). */
const previewedId = defineModel<number | null>('previewedId', { default: null });

const canDownload = can('external_vehicle_documents.download');

const docs = computed(() => props.vehicle.documents ?? []);

function toPreview(doc: VehicleDocument) {
    return { ...vehicleDocumentPreview(doc), downloadUrl: doc.download_url ?? doc.file_url ?? null };
}

const rows = computed(() => docs.value.map(toPreview));

const { selectMode, selectedIds, toggleSelectMode, setSelected, selectAll, reset: resetSelection } = useDocumentSelection(() =>
    docs.value.map((doc) => doc.id),
);

function findDoc(id: number): VehicleDocument | null {
    return docs.value.find((doc) => doc.id === id) ?? null;
}

function downloadDoc(doc: VehicleDocument) {
    const url = doc.download_url ?? doc.file_url;

    if (!url) return;

    if (!canDownload) {
        toast.error('You do not have permission to download documents.');

        return;
    }

    const a = document.createElement('a');
    a.href = url;
    a.setAttribute('download', doc.file_name ?? 'document');
    a.style.display = 'none';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}

function downloadById(id: number) {
    const doc = findDoc(id);

    if (doc) downloadDoc(doc);
}

function downloadSelected() {
    selectedIds.value.forEach(downloadById);
    resetSelection();
}

function downloadAllVerified() {
    docs.value.filter((doc) => doc.status === 'verified').forEach(downloadDoc);
}

// Viewer dialog (double-click)
const viewerOpen = ref(false);
const viewerDocId = ref<number | null>(null);
const viewerDoc = computed(() => {
    const doc = viewerDocId.value === null ? null : findDoc(viewerDocId.value);

    return doc ? toPreview(doc) : null;
});

function openViewer(id: number) {
    viewerDocId.value = id;
    viewerOpen.value = true;
}

const canResubmit = computed(() => props.canEdit && hasDocsNeedingResubmission(props.vehicle));
</script>

<template>
    <div class="space-y-4">
        <Card>
            <CardHeader class="flex flex-row justify-between">
                <div class="flex flex-col">
                    <CardTitle>Documents</CardTitle>
                    <CardDescription>Review the documents submitted for this vehicle.</CardDescription>
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
                            <DropdownMenuItem class="group cursor-pointer" :disabled="!canDownload" @click="downloadAllVerified">
                                <RiDownloadLine class="h-4 w-4 shrink-0 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
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
                    empty-description="Documents submitted for this vehicle will appear here."
                    @preview="previewedId = $event"
                    @open="openViewer"
                    @download="downloadById"
                    @toggle="setSelected"
                    @select-all="selectAll"
                    @download-selected="downloadSelected"
                />
            </CardContent>
        </Card>

        <Card v-if="canEdit">
            <CardHeader class="flex flex-row items-start justify-between gap-4">
                <div class="flex flex-col">
                    <CardTitle>Resubmit Documents</CardTitle>
                    <CardDescription>
                        {{
                            canResubmit
                                ? 'Replace invalid or expired documents. Saving sends the vehicle back for verification.'
                                : 'No documents need resubmission. Only invalid or expired documents can be replaced.'
                        }}
                    </CardDescription>
                </div>
                <div class="flex flex-row items-center gap-2">
                    <Button
                        :variant="!form.isDirty || form.processing ? 'disabled' : 'float'"
                        :disabled="!form.isDirty || form.processing"
                        @click="emit('reset')"
                    >
                        Cancel
                    </Button>
                    <Button
                        :variant="!form.isDirty || form.processing ? 'disabled' : 'float-primary'"
                        size="icon-text"
                        :disabled="!form.isDirty || form.processing"
                        @click="emit('submit')"
                    >
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </Button>
                </div>
            </CardHeader>

            <CardContent class="space-y-2">
                <InputMessage variant="destructive" :message="form.errors.documents" />

                <VehicleDocumentsForm
                    :documents="form.documents"
                    :doc-types="docTypes"
                    :errors="form.errors"
                    @set-file="(index, file) => (form.documents[index].file = file)"
                />
            </CardContent>
        </Card>

        <DocumentPreviewDialog v-model:open="viewerOpen" :doc="viewerDoc" />
    </div>
</template>
