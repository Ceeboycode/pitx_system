<script setup lang="ts">
import { computed } from 'vue'

import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import {
    Bus,
    Route as RouteIcon,
    Save,
    ShieldCheck,
    UserCircle2,
} from 'lucide-vue-next'

const props = defineProps<{
    form: {
        vehicle_type: string
        processing?: boolean
        documents: Array<{ file: File | null }>
    }
    selectedRouteName?: string | null
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
                <Bus class="h-4 w-4" />
            </div>
            <div class="min-w-0">
                <p class="text-sm font-medium">Vehicle Type</p>
                <p class="text-sm text-muted-foreground">
                    {{ form.vehicle_type || 'Not selected' }}
                </p>
            </div>
        </div>

        <div class="flex items-start gap-3">
            <div class="rounded-md border p-2 text-muted-foreground">
                <RouteIcon class="h-4 w-4" />
            </div>
            <div class="min-w-0">
                <p class="text-sm font-medium">Assigned Route</p>
                <p class="text-sm text-muted-foreground">
                    {{ selectedRouteName || 'No route selected' }}
                </p>
            </div>
        </div>

        <div class="flex items-start gap-3">
            <div class="rounded-md border p-2 text-muted-foreground">
                <ShieldCheck class="h-4 w-4" />
            </div>
            <div class="min-w-0">
                <p class="text-sm font-medium">Documents</p>
                <p class="text-sm text-muted-foreground">
                    {{ uploadedCount }} of {{ requiredDocumentsCount }} attached
                </p>
            </div>
        </div>

        <div class="flex items-start gap-3">
            <div class="rounded-md border p-2 text-muted-foreground">
                <UserCircle2 class="h-4 w-4" />
            </div>
            <div class="min-w-0">
                <p class="text-sm font-medium">Submitted By</p>
                <p class="text-sm text-muted-foreground">
                    {{ userName }}
                </p>
            </div>
        </div>

        <Separator />

        <Button
            v-if="!readonly"
            type="submit"
            class="w-full"
            :disabled="form.processing"
        >
            <Save class="mr-2 h-4 w-4" />
            {{ form.processing ? 'Saving...' : (submitLabel || 'Save Vehicle') }}
        </Button>
    </div>
</template>
