<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { FileText } from 'lucide-vue-next';

type VehicleDocumentItem = {
    document_type: string;
    status?: string | null;
    existing_file_name?: string | null;
    file: File | null;
    issued_at: string;
    expires_at: string;
};

defineProps<{
    documents: VehicleDocumentItem[];
    docTypes: Record<string, string>;
    errors: Record<string, string>;
    readonly?: boolean;
}>();

const emit = defineEmits<{
    setFile: [index: number, event: Event];
}>();

function fileName(document: VehicleDocumentItem) {
    return (
        document.file?.name ?? document.existing_file_name ?? 'No file selected'
    );
}

function canReupload(status?: string | null) {
    if (!status) return true;

    return status === 'pending' || status === 'rejected';
}
</script>

<template>
    <div class="space-y-4">
        <div
            v-for="(document, index) in documents"
            :key="document.document_type"
            class="rounded-lg border"
        >
            <div class="flex items-center justify-between gap-3 p-4">
                <div class="flex items-center gap-3">
                    <div class="rounded-md border p-2 text-muted-foreground">
                        <FileText class="h-4 w-4" />
                    </div>

                    <div>
                        <p class="text-sm font-medium">
                            {{ docTypes[document.document_type] }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            PDF or image file
                        </p>
                    </div>
                </div>

                <Badge
                    :variant="
                        document.file || document.existing_file_name
                            ? 'secondary'
                            : 'outline'
                    "
                >
                    {{
                        document.file || document.existing_file_name
                            ? 'Attached'
                            : 'Pending'
                    }}
                </Badge>
            </div>

            <Separator />

            <div class="grid gap-4 p-4 md:grid-cols-3">
                <div class="space-y-2 md:col-span-3">
                    <Label :for="`file-${index}`">File</Label>
                    <Input
                        :id="`file-${index}`"
                        :disabled="readonly || !canReupload(document.status)"
                        type="file"
                        accept=".pdf,.jpg,.jpeg,.png,.webp"
                        @change="emit('setFile', index, $event)"
                    />
                    <p class="text-xs text-muted-foreground">
                        {{ fileName(document) }}
                    </p>
                    <p
                        v-if="document.status && !canReupload(document.status)"
                        class="text-xs text-muted-foreground"
                    >
                        Reupload is only allowed for pending or rejected
                        documents.
                    </p>
                    <InputError :message="errors[`documents.${index}.file`]" />
                </div>

                <div class="space-y-2">
                    <Label :for="`issued_at-${index}`">Issued At</Label>
                    <Input
                        :id="`issued_at-${index}`"
                        v-model="document.issued_at"
                        :disabled="readonly"
                        type="date"
                    />
                    <InputError
                        :message="errors[`documents.${index}.issued_at`]"
                    />
                </div>

                <div class="space-y-2">
                    <Label :for="`expires_at-${index}`">Expires At</Label>
                    <Input
                        :id="`expires_at-${index}`"
                        v-model="document.expires_at"
                        :disabled="readonly"
                        type="date"
                    />
                    <InputError
                        :message="errors[`documents.${index}.expires_at`]"
                    />
                </div>

                <div class="space-y-2">
                    <Label>Document Type</Label>
                    <Input
                        :model-value="docTypes[document.document_type]"
                        disabled
                    />
                </div>
            </div>
        </div>
    </div>
</template>
