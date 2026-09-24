<script setup lang="ts">
import { computed } from 'vue'

import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import {
    RiBusLine,
    RiRouteLine,
    RiSaveLine,
    RiShieldCheckLine,
    RiUserLine,
} from 'vue-remix-icons'

const props = defineProps<{
    form: {
        vehicle_type_id: number | string | null
        processing?: boolean
        documents: Array<{ file: File | null }>
    }
    selectedRouteName?: string | null
    vehicleTypeName?: string | null
    requiredDocumentsCount: number
    userName: string
    submitLabel?: string
    readonly?: boolean
}>()

const uploadedCount = computed(() => {
    return props.form.documents.filter((doc) => doc.file).length
})
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-start gap-3">
            <div class="rounded-md border p-2 text-muted-foreground">
                <RiBusLine class="h-4 w-4 shrink-0" />
            </div>
            <div class="min-w-0">
                <p class="text-sm font-semibold">Vehicle Type</p>
                <p class="text-sm text-muted-foreground">
                    {{ vehicleTypeName || 'Not selected' }}
                </p>
            </div>
        </div>

        <div class="flex items-start gap-3">
            <div class="rounded-md border p-2 text-muted-foreground">
                <RiRouteLine class="h-4 w-4 shrink-0" />
            </div>
            <div class="min-w-0">
                <p class="text-sm font-semibold">Assigned Route</p>
                <p class="text-sm text-muted-foreground">
                    {{ selectedRouteName || 'No route selected' }}
                </p>
            </div>
        </div>

        <div class="flex items-start gap-3">
            <div class="rounded-md border p-2 text-muted-foreground">
                <RiShieldCheckLine class="h-4 w-4 shrink-0" />
            </div>
            <div class="min-w-0">
                <p class="text-sm font-semibold">Documents</p>
                <p class="text-sm text-muted-foreground">
                    {{ uploadedCount }} of {{ requiredDocumentsCount }} attached
                </p>
            </div>
        </div>

        <div class="flex items-start gap-3">
            <div class="rounded-md border p-2 text-muted-foreground">
                <RiUserLine class="h-4 w-4 shrink-0" />
            </div>
            <div class="min-w-0">
                <p class="text-sm font-semibold">Submitted By</p>
                <p class="text-sm text-muted-foreground">
                    {{ userName }}
                </p>
            </div>
        </div>

        <Separator />

        <Button
            v-if="!readonly"
            type="submit"
            class="w-full bg-blue-600 text-white hover:bg-blue-900"
            :disabled="form.processing"
        >
            <RiSaveLine class="mr-2 h-4 w-4 shrink-0" />
            {{ form.processing ? 'Saving...' : (submitLabel || 'Save Vehicle') }}
        </Button>
    </div>
</template>
