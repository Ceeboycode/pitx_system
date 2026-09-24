<script setup lang="ts">
import { DocumentUploadField, type DocumentUploadRules } from '@/components/documents'

type VehicleDocumentItem = {
    document_type: string
    status?: string | null
    existing_file_name?: string | null
    file: File | null
    issued_at: string
    expires_at: string
}

defineProps<{
    documents: VehicleDocumentItem[]
    docTypes: Record<string, string>
    errors: Record<string, string>
    readonly?: boolean
}>()

const emit = defineEmits<{
    setFile: [index: number, file: File | null]
}>()

const vehicleUploadRules: DocumentUploadRules = {
    extensions: ['pdf', 'jpg', 'jpeg', 'png', 'webp'],
    accept: '.pdf,.jpg,.jpeg,.png,.webp',
    maxMb: 5,
    previewableExtensions: ['pdf', 'jpg', 'jpeg', 'png', 'webp'],
}

function canReupload(status?: string | null) {
    if (!status) return true
    return status === 'expired' || status === 'invalid'
}

function isPhotoDocument(documentType: string) {
    return documentType === 'puv_identification_markings'
}

function fileHint(documentType: string) {
    if (isPhotoDocument(documentType)) {
        return 'Upload a clear photo of the bus showing the PUV identification markings.'
    }

    return undefined
}

function showDates(documentType: string) {
    return !isPhotoDocument(documentType)
}

function noteFor(status?: string | null) {
    if (status && !canReupload(status)) {
        return 'Reupload (and date edits) are only allowed for invalid or expired documents.'
    }

    return undefined
}
</script>

<template>
    <div class="space-y-4">
        <DocumentUploadField
            v-for="(document, index) in documents"
            :key="document.document_type"
            :label="docTypes[document.document_type]"
            required
            :hint="fileHint(document.document_type)"
            :note="noteFor(document.status)"
            :file="document.file"
            :existing-file-name="document.existing_file_name"
            :issued-at="document.issued_at"
            :expires-at="document.expires_at"
            :show-dates="showDates(document.document_type)"
            :disabled="readonly || !canReupload(document.status)"
            :accept="vehicleUploadRules.accept"
            :upload-rules="vehicleUploadRules"
            :file-error="errors[`documents.${index}.file`]"
            :issued-at-error="errors[`documents.${index}.issued_at`]"
            :expires-at-error="errors[`documents.${index}.expires_at`]"
            @update:file="(file) => emit('setFile', index, file)"
            @update:issued-at="(value) => (document.issued_at = value)"
            @update:expires-at="(value) => (document.expires_at = value)"
        />
    </div>
</template>
