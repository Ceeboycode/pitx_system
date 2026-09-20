<script setup lang="ts">
import { computed, ref } from 'vue';

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import Button from '@/components/ui/button/Button.vue';
import { RiCheckboxMultipleBlankLine, RiCheckboxMultipleLine, RiDownloadLine, RiMore2Line } from 'vue-remix-icons';

import DocumentPreviewDialog from '@/components/internal/documents/DocumentPreviewDialog.vue';
import DocumentsTable from '@/components/internal/documents/DocumentsTable.vue';
import { InvalidateDocumentDialog } from '@/components/internal/vehicles';
import VehicleDocumentConfirmDialog from '@/components/internal/vehicles/show/VehicleDocumentConfirmDialog.vue';

import { useDocumentSelection } from '@/composables/useDocumentSelection';
import { can } from '@/lib/can';
import { vehicleDocumentPreview, vehiclePreviewActions } from '@/lib/document-preview';

type VehicleDocument = {
    id: number;
    document_type: string;
    file_name?: string | null;
    file_mime_type?: string | null;
    file_size?: number | null;
    file_url?: string | null;
    status?: string | null;
    issued_at?: string | null;
    expires_at?: string | null;
    remarks?: string | null;
    created_at?: string | null;
    updated_at?: string | null;
};

type VehicleModel = {
    id: number;
    plate_number?: string | null;
    company?: {
        id: number;
        company_name: string;
        company_code?: string | null;
    } | null;
    documents?: VehicleDocument[];
};

const props = defineProps<{
    vehicle: VehicleModel;
}>();

/** Which document the page shows in its side panel (the page owns the panel). */
const previewedId = defineModel<number | null>('previewedId', { default: null });

const docs = computed(() => props.vehicle.documents ?? []);
const rows = computed(() => docs.value.map((doc) => vehicleDocumentPreview(doc)));

const canVerifyVehicleDocument = computed(() => can('vehicle_documents.verify'));
const canUnverifyVehicleDocument = computed(() => can('vehicle_documents.unverify'));
const canInvalidateVehicleDocument = computed(() => can('vehicle_documents.invalidate'));

const { selectMode, selectedIds, toggleSelectMode, setSelected, selectAll, reset: resetSelection } = useDocumentSelection(() => docs.value.map((doc) => doc.id));

function findDoc(id: number): VehicleDocument | null {
    return docs.value.find((doc) => doc.id === id) ?? null;
}

function downloadDoc(doc: VehicleDocument) {
    if (!doc.file_url) return;

    const a = document.createElement('a');
    a.href = doc.file_url;
    a.setAttribute('download', doc.file_name ?? 'document');
    a.setAttribute('target', '_blank');
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
const viewerDoc = computed(() => (viewerDocId.value === null ? null : findDoc(viewerDocId.value)));

function openViewer(id: number) {
    viewerDocId.value = id;
    viewerOpen.value = true;
}

// Invalidate / verify / unverify dialogs
const invalidateOpen = ref(false);
const actionDoc = ref<VehicleDocument | null>(null);

function openInvalidate(doc: VehicleDocument) {
    actionDoc.value = doc;
    invalidateOpen.value = true;
}

const confirmOpen = ref(false);
const actionType = ref<'verify' | 'unverify'>('verify');

function openConfirm(type: 'verify' | 'unverify', doc: VehicleDocument) {
    actionType.value = type;
    actionDoc.value = doc;
    confirmOpen.value = true;
}

function onViewerAction(key: 'verify' | 'unverify' | 'invalidate') {
    const doc = viewerDoc.value;
    if (!doc) return;

    if (key === 'invalidate') openInvalidate(doc);
    else openConfirm(key, doc);
}
</script>

<template>
    <div class="space-y-4">
        <Card class="order-2 col-span-2">
            <CardHeader class="flex flex-row justify-between">
                <div class="flex flex-col">
                    <CardTitle>Documents</CardTitle>
                    <CardDescription>Review and manage submitted vehicle documents.</CardDescription>
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
                            <DropdownMenuItem class="group cursor-pointer" @click="downloadAllVerified">
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

        <DocumentPreviewDialog
            v-model:open="viewerOpen"
            :doc="vehicleDocumentPreview(viewerDoc)"
            :actions="vehiclePreviewActions(viewerDoc, {
                verify: canVerifyVehicleDocument,
                unverify: canUnverifyVehicleDocument,
                invalidate: canInvalidateVehicleDocument,
            })"
            @action="onViewerAction"
        />

        <InvalidateDocumentDialog v-model:open="invalidateOpen" :doc="actionDoc" :vehicle-id="vehicle.id" />

        <VehicleDocumentConfirmDialog
            v-model:open="confirmOpen"
            :doc="actionDoc"
            :action="actionType"
            :vehicle-id="vehicle.id"
            @done="actionDoc = null; viewerOpen = false"
        />
    </div>
</template>
