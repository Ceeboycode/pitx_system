<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router, Link } from '@inertiajs/vue3'
import { type BreadcrumbItem } from '@/types'
import { index, restore, forceDelete, trash } from '@/routes/companies'
import { ref, watch } from 'vue'
import { toast } from 'vue-sonner'

import { Button } from '@/components/ui/button'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'

/* ======================
 | Types
 ====================== */

interface Company {
    id: number
    company_name: string
    deleted_at: string
    deleted_at_human: string
}

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

/* ======================
 | Props
 ====================== */

defineProps<{
    companies: {
        data: Company[]
        links: PaginationLink[]
    }
}>()

/* ======================
 | Breadcrumbs
 ====================== */

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Companies', href: index().url },
    { title: 'Trash', href: trash().url },
]

/* ======================
 | Delete Dialog State
 ====================== */

const isDeleteDialogOpen = ref(false)
const deletingCompanyId = ref<number | null>(null)
const deleteConfirmText = ref('')
const DELETE_CONFIRM_TEXT = 'delete'
const isRestoreDialogOpen = ref(false)
const restoringCompanyId = ref<number | null>(null)


/* ======================
 | Actions
 ====================== */

const openRestoreDialog = (id: number) => {
    restoringCompanyId.value = id
    isRestoreDialogOpen.value = true
}


const confirmRestoreCompany = () => {
    if (!restoringCompanyId.value) return

    router.post(restore(restoringCompanyId.value).url, {}, {
        onSuccess: () => {
            toast.success('Company restored')
            isRestoreDialogOpen.value = false
            restoringCompanyId.value = null
        },
        onError: () => toast.error('Restore failed'),
    })
}

const openDeleteDialog = (id: number) => {
    deletingCompanyId.value = id
    deleteConfirmText.value = ''
    isDeleteDialogOpen.value = true
}

const forceDeleteCompany = () => {
    if (!deletingCompanyId.value) return

    router.delete(forceDelete(deletingCompanyId.value).url, {
        onSuccess: () => {
            toast.success('Company permanently deleted')
            isDeleteDialogOpen.value = false
            deletingCompanyId.value = null
            deleteConfirmText.value = ''
        },
        onError: () => toast.error('Delete failed'),
    })
}

/* ======================
 | Cleanup when dialog closes
 ====================== */

watch(isDeleteDialogOpen, (open) => {
    if (!open) {
        deletingCompanyId.value = null
        deleteConfirmText.value = ''
    }
})
</script>

<template>
    <Head title="Company Trash" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <Link :href="index().url">
                <Button variant="default">Back</Button>
            </Link>
            <h1 class="mb-4 text-2xl font-bold text-red-600">
                Deleted Companies
            </h1>
            <table class="min-w-full border border-gray-300">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border px-4 py-2">Company</th>
                        <th class="border px-4 py-2">Deleted At</th>
                        <th class="border px-4 py-2">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="company in companies.data"
                        :key="company.id"
                    >
                        <td class="border px-4 py-2">
                            {{ company.company_name }}
                        </td>

                        <td class="border px-4 py-2">
                            {{ company.deleted_at_human }}
                        </td>

                        <td class="border px-4 py-2 space-x-2">
                            <Button
                                size="sm"
                                variant="secondary"
                                @click="openRestoreDialog(company.id)"
                            >
                                Restore
                            </Button>

                            <Button
                                size="sm"
                                variant="destructive"
                                @click="openDeleteDialog(company.id)"
                            >
                                Delete Forever
                            </Button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="mt-4 flex flex-wrap gap-2">
                <button
                    v-for="link in companies.links"
                    :key="link.label"
                    v-html="link.label"
                    :disabled="!link.url"
                    @click="link.url && router.visit(link.url)"
                    class="rounded border px-3 py-1"
                    :class="{
                        'bg-blue-600 text-white': link.active,
                        'cursor-not-allowed text-gray-400': !link.url,
                    }"
                />
            </div>

            <!-- Delete Confirmation Dialog -->
            <Dialog v-model:open="isDeleteDialogOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle class="text-red-600">
                            Delete Company
                        </DialogTitle>
                        <DialogDescription>
                            This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="space-y-4 py-4">
                        <p>
                            Please type
                            <span class="font-semibold text-red-600">
                                delete
                            </span>
                            to confirm deletion.
                        </p>

                        <Input
                            v-model="deleteConfirmText"
                            placeholder="Type delete to confirm"
                        />
                    </div>

                    <DialogFooter>
                        <Button
                            variant="secondary"
                            @click="isDeleteDialogOpen = false"
                        >
                            Cancel
                        </Button>

                        <Button
                            variant="destructive"
                            :disabled="
                                deleteConfirmText.toLowerCase() !==
                                DELETE_CONFIRM_TEXT
                            "
                            @click="forceDeleteCompany"
                        >
                            Delete
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog v-model:open="isRestoreDialogOpen">
    <DialogContent>
        <DialogHeader>
            <DialogTitle>Restore Company</DialogTitle>
            <DialogDescription>
                Are you sure you want to restore this company?
            </DialogDescription>
        </DialogHeader>

        <DialogFooter>
            <Button
                variant="secondary"
                @click="isRestoreDialogOpen = false"
            >
                Cancel
            </Button>

            <Button
                variant="default"
                @click="confirmRestoreCompany"
            >
                Restore
            </Button>
        </DialogFooter>
    </DialogContent>
</Dialog>

        </div>
    </AppLayout>
</template>
