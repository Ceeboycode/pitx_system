<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import {
    Card,
    CardHeader,
    CardTitle,
    CardDescription,
    CardContent,
} from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuTrigger,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';
import {
    Popover,
    PopoverTrigger,
    PopoverContent,
} from '@/components/ui/popover';
import Button from '@/components/ui/button/Button.vue';
import { Badge } from '@/components/ui/badge';
import {
    DocumentTable,
    DocumentTableCard,
    DocumentTableContent,
    DocumentTableData,
    DocumentTableMoreButton,
    DocumentTableRow,
} from '@/components/ui/_document-table';

import documentsUrl from '@/components/assets/Documents-rafiki.svg';

import {
    RiMore2Line,
    RiCheckboxMultipleBlankLine,
    RiCheckboxMultipleLine,
    RiDownloadLine,
    RiMessage2Fill,
    RiFile2Fill,
    RiCheckLine,
    RiRestartLine,
    RiCloseCircleLine,
    RiEyeLine,
} from 'vue-remix-icons';

import {
    DocumentPreviewDialog,
    InvalidateDocumentDialog,
} from '@/components/vehicles/show';

import { formatDate, formatDateTime, isExpired } from '@/lib/format';
import { can } from '@/lib/can';
import {
    vehicleDocumentStatusClass,
    vehicleDocumentStatusDot,
    vehicleDocumentStatusLabel,
    humanize,
} from '@/lib/vehicle-status';

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

const vehicle = computed(() => props.vehicle);
const docs = computed(() => props.vehicle.documents ?? []);

const canVerifyVehicleDocument = computed(() => can('vehicles.verifyDocument'));
const canUnverifyVehicleDocument = computed(() => can('vehicles.unverifyDocument'));
const canInvalidateVehicleDocument = computed(() => can('vehicles.invalidateDocument'));

const openMenus = ref<Record<number, {
    open: boolean;
    x: number;
    y: number;
    mode: 'trigger' | 'context';
}>>({});

function setMenuState(docId: number, next: { open: boolean; x: number; y: number; mode: 'trigger' | 'context' }) {
    openMenus.value = {
        ...openMenus.value,
        [docId]: next,
    };
}

function openRowMenu(event: MouseEvent, doc: VehicleDocument) {
    event.preventDefault();
    setMenuState(doc.id, {
        open: true,
        x: event.clientX,
        y: event.clientY,
        mode: 'context',
    });
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

function canPreviewFile(doc: VehicleDocument): boolean {
    if (!doc.file_url && !doc.file_name) return false;
    const name = doc.file_name?.toLowerCase() ?? '';
    const mime = doc.file_mime_type?.toLowerCase() ?? '';
    return (
        mime.startsWith('image/') ||
        mime === 'application/pdf' ||
        name.endsWith('.pdf') ||
        name.endsWith('.png') ||
        name.endsWith('.jpg') ||
        name.endsWith('.jpeg') ||
        name.endsWith('.webp')
    );
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

function downloadSelected() {
    if (selectedDocIds.value.length === 0) return;
    for (const id of selectedDocIds.value) {
        const doc = docs.value.find((d) => d.id === id);
        if (doc) downloadDoc(doc);
    }
    selectMode.value = false;
    selectedDocIds.value = [];
}

function downloadAllVerified() {
    const verifiedDocs = docs.value.filter((d) => d.status === 'verified');
    for (const doc of verifiedDocs) {
        downloadDoc(doc);
    }
}

// Preview Dialog State
const previewOpen = ref(false);
const previewDoc = ref<VehicleDocument | null>(null);

function openPreview(doc: VehicleDocument) {
    previewDoc.value = doc;
    previewOpen.value = true;
}

// Invalidate Dialog State
const invalidateOpen = ref(false);
const actionDoc = ref<VehicleDocument | null>(null);

function openInvalidate(doc: VehicleDocument) {
    actionDoc.value = doc;
    invalidateOpen.value = true;
}

// Verify / Unverify Confirm Dialog State
const confirmOpen = ref(false);
const actionType = ref<'verify' | 'unverify'>('verify');
const actionForm = useForm({});

function openConfirm(type: 'verify' | 'unverify', doc: VehicleDocument) {
    actionType.value = type;
    actionDoc.value = doc;
    confirmOpen.value = true;
}

function submitConfirm() {
    if (!actionDoc.value) return;

    const endpoint =
        actionType.value === 'verify'
            ? `/vehicles/${vehicle.value.id}/documents/${actionDoc.value.id}/verify`
            : `/vehicles/${vehicle.value.id}/documents/${actionDoc.value.id}/unverify`;

    actionForm.patch(endpoint, {
        preserveScroll: true,
        onSuccess: () => {
            confirmOpen.value = false;
            actionDoc.value = null;
            previewOpen.value = false;
        },
    });
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
                                <Button
                                    variant="header-actions"
                                    class="text-custom-shadow"
                                    size="icon"
                                    aria-label="Open document actions"
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
                                @click="downloadAllVerified()"
                            >
                                <RiDownloadLine class="shrink-0 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-shadow" />
                                <span>Download all verified</span>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </CardHeader>
            <CardContent class="no-scrollbar min-h-0 flex-1 overflow-y-auto">
                <div v-if="selectMode" class="flex justify-between pb-3">
                    <Button
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
                        <span>
                            {{ allSelected ? `Download all` : (selectMode && `Download (${selectedDocIds.length})`) }}
                        </span>
                    </Button>
                </div>

                <DocumentTableCard :docs-data-length="docs.length">
                    <DocumentTable v-if="docs.length > 0">
                        <DocumentTableContent>
                            <DocumentTableRow
                                v-for="(doc, rowIndex) in docs"
                                :key="doc.id"
                                :class="[
                                    rowIndex === docs.length - 1 ? 'rounded-b-md border-b-0' : '',
                                    !selectMode ? 'group/row' : '',
                                ]"
                                :status="doc.status === 'inactive' || doc.status === 'invalid' ? 'inactive' : 'default'"
                                @click.left="openPreview(doc)"
                                @contextmenu.prevent="openRowMenu($event, doc)"
                            >
                                <div
                                    class="flex items-start pt-1 overflow-hidden transition-all duration-200"
                                    :class="selectMode ? 'w-5 opacity-100 me-2' : 'w-0 opacity-0'"
                                >
                                    <input
                                        type="checkbox"
                                        class="h-4 w-4 rounded cursor-pointer"
                                        :checked="selectedDocIds.includes(doc.id)"
                                        @click.stop
                                        @change="setDoc(doc.id, ($event.target as HTMLInputElement).checked)"
                                    />
                                </div>

                                <DocumentTableData class="flex-1">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="text-sm font-semibold text-foreground">
                                                {{ humanize(doc.document_type) }}
                                            </p>
                                            <Badge :class="['gap-1.5', vehicleDocumentStatusClass(doc.status)]">
                                                <span :class="['h-1.5 w-1.5 rounded-full', vehicleDocumentStatusDot(doc.status)]" />
                                                {{ vehicleDocumentStatusLabel(doc.status) }}
                                            </Badge>
                                            <Badge
                                                v-if="isExpired(doc.expires_at) && doc.status !== 'expired' && doc.status !== 'invalid'"
                                                class="gap-1.5 border-rose-200 bg-rose-100 text-rose-600 dark:border-rose-800 dark:bg-rose-950 dark:text-rose-400"
                                            >
                                                <span class="h-1.5 w-1.5 rounded-full bg-rose-500" />
                                                Expired
                                            </Badge>
                                        </div>

                                        <div class="pt-1">
                                            <button
                                                v-if="canPreviewFile(doc)"
                                                type="button"
                                                class="cursor-pointer flex items-center gap-2 text-sm text-muted-foreground underline-offset-2 hover:underline"
                                                :title="doc.file_name ?? ''"
                                                @click.stop="openPreview(doc)"
                                            >
                                                <RiFile2Fill class="h-4 w-4 shrink-0" />
                                                <span class="truncate">{{ doc.file_name ?? '—' }}</span>
                                            </button>
                                            <span v-else class="text-sm text-muted-foreground">{{ doc.file_name ?? '—' }}</span>
                                        </div>

                                        <div class="overflow-hidden max-h-0 opacity-0 group-hover/row:max-h-96 group-hover/row:opacity-100 transition-all delay-200 duration-200 flex-col pt-1">
                                            <div class="flex flex-row items-center gap-x-10 text-xs text-muted-foreground">
                                                <div class="flex flex-col w-48 gap-y-1">
                                                    <span v-if="doc.issued_at">
                                                        Issued:
                                                        <span class="font-medium text-foreground">
                                                            {{ formatDate(doc.issued_at) }}
                                                        </span>
                                                    </span>
                                                    <span v-if="doc.expires_at">
                                                        Expires:
                                                        <span
                                                            :class="[
                                                                'font-medium',
                                                                isExpired(doc.expires_at)
                                                                    ? 'text-rose-600 dark:text-rose-400'
                                                                    : 'text-foreground',
                                                            ]"
                                                        >
                                                            {{ formatDate(doc.expires_at) }}
                                                        </span>
                                                    </span>
                                                </div>
                                                <div class="flex flex-col flex-1 gap-y-1">
                                                    <span v-if="doc.created_at">
                                                        Uploaded on:
                                                        <span class="font-medium text-foreground">
                                                            {{ formatDateTime(doc.created_at) }}
                                                        </span>
                                                    </span>
                                                </div>
                                            </div>

                                            <div v-if="doc.remarks" class="pt-2">
                                                <Popover>
                                                    <PopoverTrigger as-child>
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            class="rounded-lg gap-1.5"
                                                            @click.stop
                                                        >
                                                            <RiMessage2Fill class="h-3.5 w-3.5 shrink-0" />
                                                            View Remarks
                                                        </Button>
                                                    </PopoverTrigger>
                                                    <PopoverContent
                                                        align="start"
                                                        class="w-80 rounded-lg p-3 shadow-lg"
                                                    >
                                                        <div>
                                                            <p class="text-sm font-semibold pb-1">Remarks</p>
                                                            <p class="text-sm whitespace-pre-wrap text-muted-foreground">{{ doc.remarks }}</p>
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
                                        const current = openMenus[doc.id] ?? { open: false, x: 0, y: 0, mode: 'trigger' };
                                        setMenuState(doc.id, {
                                            ...current,
                                            open: value,
                                            mode: value ? current.mode : 'trigger',
                                        });
                                    }"
                                >
                                    <DropdownMenuLabel>
                                        <span>{{ humanize(doc.document_type) }}</span>
                                    </DropdownMenuLabel>
                                    <DropdownMenuSeparator />

                                    <DropdownMenuItem
                                        v-if="canPreviewFile(doc)"
                                        class="cursor-pointer gap-2"
                                        @click="openPreview(doc)"
                                    >
                                        <RiEyeLine class="h-4 w-4 shrink-0" /> Preview
                                    </DropdownMenuItem>

                                    <DropdownMenuItem
                                        v-if="canVerifyVehicleDocument && doc.status !== 'verified'"
                                        class="cursor-pointer gap-2 text-emerald-600 focus:text-emerald-700"
                                        @click="openConfirm('verify', doc)"
                                    >
                                        <RiCheckLine class="h-4 w-4 shrink-0" /> Verify
                                    </DropdownMenuItem>

                                    <DropdownMenuItem
                                        v-if="canUnverifyVehicleDocument && doc.status === 'verified'"
                                        class="cursor-pointer gap-2 text-amber-600 focus:text-amber-700"
                                        @click="openConfirm('unverify', doc)"
                                    >
                                        <RiRestartLine class="h-4 w-4 shrink-0" /> Move to Pending
                                    </DropdownMenuItem>

                                    <DropdownMenuItem
                                        v-if="canInvalidateVehicleDocument && doc.status !== 'invalid'"
                                        class="cursor-pointer gap-2 text-rose-600 focus:text-rose-700"
                                        @click="openInvalidate(doc)"
                                    >
                                        <RiCloseCircleLine class="h-4 w-4 shrink-0" /> Mark Invalid
                                    </DropdownMenuItem>

                                    <DropdownMenuItem
                                        v-if="doc.file_url"
                                        class="cursor-pointer gap-2"
                                        @click="downloadDoc(doc)"
                                    >
                                        <RiDownloadLine class="h-4 w-4 shrink-0" /> Download
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
                                    Documents submitted for this vehicle will appear here.
                                </p>
                            </div>
                        </div>
                    </div>
                </DocumentTableCard>
            </CardContent>
        </Card>

        <!-- Preview Dialog -->
        <DocumentPreviewDialog
            v-model:open="previewOpen"
            :doc="previewDoc"
            :can-verify="canVerifyVehicleDocument"
            :can-unverify="canUnverifyVehicleDocument"
            :can-invalidate="canInvalidateVehicleDocument"
            @verify="(d) => openConfirm('verify', d)"
            @unverify="(d) => openConfirm('unverify', d)"
            @invalidate="(d) => openInvalidate(d)"
        />

        <!-- Invalidate Document Dialog -->
        <InvalidateDocumentDialog
            v-model:open="invalidateOpen"
            :doc="actionDoc"
            :vehicle-id="vehicle.id"
        />

        <!-- Verify / Unverify Confirm Dialog -->
        <Dialog v-model:open="confirmOpen">
            <DialogContent class="max-w-md px-6" :show-close-button="false">
                <DialogHeader class="px-0">
                    <DialogTitle>
                        {{ actionType === 'verify' ? 'Verify document?' : 'Move back to pending?' }}
                    </DialogTitle>
                    <DialogDescription>
                        {{ actionType === 'verify' ? 'This will mark' : 'This will revert' }}
                        <span class="font-semibold text-custom-accent-3">{{
                            humanize(actionDoc?.document_type)
                        }}</span>
                        {{ actionType === 'verify' ? 'as verified.' : 'back to pending review.' }}
                    </DialogDescription>
                </DialogHeader>
                <Separator />
                <DialogFooter class="pt-3 gap-2 sm:justify-end">
                    <Button
                        variant="ghost-outline"
                        :disabled="actionForm.processing"
                        @click="confirmOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        :variant="actionType === 'verify' ? 'float-primary' : 'destructive'"
                        :disabled="actionForm.processing"
                        @click="submitConfirm"
                    >
                        {{ actionForm.processing ? 'Processing...' : 'Confirm' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
