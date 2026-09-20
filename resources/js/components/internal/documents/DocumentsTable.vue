<script setup lang="ts">
import { computed, ref } from 'vue';

import documentsUrl from '@/components/assets/Documents-rafiki.svg';
import { Badge } from '@/components/ui/_badge';
import { CheckboxInput } from '@/components/ui/_checkbox';
import { Table, TableCard, TableColumn, TableContent, TableData, TableHeader, TableMoreButton, TableRow } from '@/components/ui/_table';
import { Button } from '@/components/ui/button';
import { DropdownMenuItem, DropdownMenuLabel } from '@/components/ui/dropdown-menu';
import { RiCheckboxMultipleBlankLine, RiCheckboxMultipleLine, RiDownloadLine } from 'vue-remix-icons';

import type { PreviewDocument } from '@/lib/document-preview';

/**
 * The document list of a Documents tab (company or vehicle). A click previews a row, a double-click
 * opens the viewer, and in select mode a click ticks the row for the multi-download instead.
 */
const props = withDefaults(
    defineProps<{
        documents: PreviewDocument[];
        previewedId?: number | null;
        selectMode?: boolean;
        selectedIds?: number[];
        emptyDescription?: string;
    }>(),
    {
        previewedId: null,
        selectMode: false,
        selectedIds: () => [],
        emptyDescription: 'Submitted documents will appear here.',
    },
);

const emit = defineEmits<{
    preview: [id: number];
    open: [id: number];
    download: [id: number];
    toggle: [id: number, checked: boolean];
    selectAll: [];
    downloadSelected: [];
}>();

const openMenuId = ref<number | null>(null);

const allSelected = computed(() => props.documents.length > 0 && props.selectedIds.length === props.documents.length);

function isSelected(id: number): boolean {
    return props.selectedIds.includes(id);
}

function isHighlighted(id: number): boolean {
    return props.selectMode ? isSelected(id) : props.previewedId === id;
}

function onRowClick(id: number) {
    if (props.selectMode) emit('toggle', id, !isSelected(id));
    else emit('preview', id);
}

function onRowDblClick(id: number) {
    if (!props.selectMode) emit('open', id);
}
</script>

<template>
    <div class="flex min-h-0 mt-2 flex-col gap-3">
        <div v-if="props.selectMode" class="flex justify-between">
            <Button variant="float" @click="emit('selectAll')">
                <RiCheckboxMultipleBlankLine v-if="allSelected" class="h-4 w-4 shrink-0" />
                <RiCheckboxMultipleLine v-else class="h-4 w-4 shrink-0" />
                <span>{{ allSelected ? 'Deselect all' : 'Select all' }}</span>
            </Button>
            <Button v-if="props.documents.length > 0" variant="float" :disabled="props.selectedIds.length === 0" @click="emit('downloadSelected')">
                <RiDownloadLine class="h-4 w-4 shrink-0" />
                <span>{{ allSelected ? 'Download all' : `Download (${props.selectedIds.length})` }}</span>
            </Button>
        </div>

        <TableCard :table-data-length="props.documents.length">
            <Table v-if="props.documents.length > 0">
                <TableHeader>
                    <TableColumn v-if="props.selectMode" class="w-8" />
                    <TableColumn>Document</TableColumn>
                    <TableColumn>File</TableColumn>
                    <TableColumn>Status</TableColumn>
                </TableHeader>

                <TableContent>
                    <TableRow
                        v-for="(doc, rowIndex) in props.documents"
                        :key="doc.id"
                        :class="[
                            rowIndex === props.documents.length - 1 ? 'rounded-b-md border-b-0' : '',
                            isHighlighted(doc.id) ? 'bg-custom-secondary/10' : '',
                            'items-center'
                        ]"
                        @click.left="onRowClick(doc.id)"
                        @dblclick="onRowDblClick(doc.id)"
                    >
                        <TableData v-if="props.selectMode" class="w-6" @click.stop>
                            <CheckboxInput
                                :model-value="isSelected(doc.id)"
                                :aria-label="`Select ${doc.typeLabel}`"
                                @update:model-value="emit('toggle', doc.id, $event)"
                                class="mt-1"
                            />
                        </TableData>

                        <TableData class="font-semibold">
                            <span class="truncate">{{ doc.typeLabel }}</span>
                        </TableData>

                        <TableData class="max-w-64">
                            <span class="flex min-w-0 items-center gap-2 text-custom-shadow" :title="doc.fileName ?? ''">
                                <span class="truncate">{{ doc.fileName || '—' }}</span>
                            </span>
                        </TableData>

                        <TableData>
                            <div class="flex flex-wrap items-center gap-2">
                                <Badge v-if="doc.status" :class="['gap-1.5', doc.status.class]">
                                    <span :class="['h-1.5 w-1.5 rounded-full', doc.status.dot]" />
                                    {{ doc.status.label }}
                                </Badge>
                                <Badge
                                    v-if="doc.expiredBadge"
                                    class="gap-1.5 border-rose-200 bg-rose-100 text-rose-600 dark:border-rose-800 dark:bg-rose-950 dark:text-rose-400"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-rose-500" />
                                    Expired
                                </Badge>
                            </div>
                        </TableData>

                        <TableMoreButton :open="openMenuId === doc.id" @update:open="(value) => (openMenuId = value ? doc.id : null)">
                            <DropdownMenuLabel>{{ doc.typeLabel }}</DropdownMenuLabel>
                            <DropdownMenuItem class="group cursor-pointer" :disabled="!doc.downloadUrl" @click="emit('download', doc.id)">
                                <RiDownloadLine class="h-4 w-4 text-custom-shadow transition-all duration-200 group-hover:text-custom-bg-light dark:group-hover:text-custom-bg" />
                                Download
                            </DropdownMenuItem>
                        </TableMoreButton>
                    </TableRow>
                </TableContent>
            </Table>

            <div v-else class="flex min-h-0 flex-1 items-center justify-center p-6 text-center">
                <div class="flex w-full max-w-md flex-col items-center gap-2">
                    <img :src="documentsUrl" alt="" class="w-1/3 object-contain opacity-90" aria-hidden="true" />
                    <div class="space-y-1">
                        <p class="text-base text-center font-semibold text-custom-shadow">No documents found</p>
                        <p class="text-sm text-custom-shadow/80">{{ props.emptyDescription }}</p>
                    </div>
                </div>
            </div>
        </TableCard>
    </div>
</template>
