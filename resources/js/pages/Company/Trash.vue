<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { forceDelete, index, restore, trash } from '@/routes/companies';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { toast } from 'vue-sonner';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    AlertTriangle,
    ArchiveRestoreIcon,
    LucideTrash2,
} from 'lucide-vue-next';

/* ======================
 | Types
 ====================== */

interface Company {
    id: number;
    company_name: string;
    deleted_at: string;
    deleted_at_human: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

/* ======================
 | Props
 ====================== */

defineProps<{
    companies: {
        data: Company[];
        links: PaginationLink[];
    };
}>();

/* ======================
 | Breadcrumbs
 ====================== */

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Companies', href: index().url },
    { title: 'Trash', href: trash().url },
];

/* ======================
 | Dialog State
 ====================== */

const isDeleteDialogOpen = ref(false);
const deletingCompanyId = ref<number | null>(null);
const deleteConfirmText = ref('');
const DELETE_CONFIRM_TEXT = 'delete';

const isRestoreDialogOpen = ref(false);
const restoringCompanyId = ref<number | null>(null);

/* ======================
 | Actions
 ====================== */

const openRestoreDialog = (id: number) => {
    restoringCompanyId.value = id;
    isRestoreDialogOpen.value = true;
};

const confirmRestoreCompany = () => {
    if (!restoringCompanyId.value) return;

    router.patch(
        restore(restoringCompanyId.value).url,
        {},
        {
            onSuccess: () => {
                toast.success('Company restored');
                isRestoreDialogOpen.value = false;
                restoringCompanyId.value = null;
            },
            onError: () => toast.error('Restore failed'),
        },
    );
};

const openDeleteDialog = (id: number) => {
    deletingCompanyId.value = id;
    deleteConfirmText.value = '';
    isDeleteDialogOpen.value = true;
};

const forceDeleteCompany = () => {
    if (!deletingCompanyId.value) return;

    router.delete(forceDelete(deletingCompanyId.value).url, {
        onSuccess: () => {
            toast.success('Company permanently deleted');
            isDeleteDialogOpen.value = false;
            deletingCompanyId.value = null;
            deleteConfirmText.value = '';
        },
        onError: () => toast.error('Delete failed'),
    });
};

/* ======================
 | Cleanup
 ====================== */

watch(isDeleteDialogOpen, (open) => {
    if (!open) {
        deletingCompanyId.value = null;
        deleteConfirmText.value = '';
    }
});
</script>

<template>
    <Head title="Company Trash" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="space-y-1">
                    <h1 class="text-2xl font-bold">Deleted Companies</h1>
                    <p class="text-sm text-muted-foreground">
                        Permanently delete or restore companies.
                    </p>
                </div>

                <Button as-child variant="outline">
                    <Link :href="index().url">Back</Link>
                </Button>
            </div>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Company</TableHead>
                        <TableHead>Deleted At</TableHead>
                        <TableHead>Actions</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow
                        v-for="company in companies.data"
                        :key="company.id"
                    >
                        <TableCell class="font-medium">
                            {{ company.company_name }}
                        </TableCell>

                        <TableCell>
                            {{ company.deleted_at_human }}
                        </TableCell>

                        <TableCell class="space-x-2 text-right">
                            <Button
                                size="sm"
                                variant="secondary"
                                @click="openRestoreDialog(company.id)"
                            >
                                <ArchiveRestoreIcon />
                                Restore
                            </Button>

                            <Button
                                size="sm"
                                variant="destructive"
                                @click="openDeleteDialog(company.id)"
                            >
                                <LucideTrash2 />
                                Delete Forever
                            </Button>
                        </TableCell>
                    </TableRow>

                    <TableRow v-if="companies.data.length === 0">
                        <TableCell
                            colspan="3"
                            class="text-center text-muted-foreground"
                        >
                            No deleted companies found.
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>

            <!-- Pagination -->
            <div class="flex flex-wrap gap-2">
                <Button
                    v-for="link in companies.links"
                    :key="link.label"
                    size="sm"
                    variant="outline"
                    :disabled="!link.url"
                    @click="link.url && router.visit(link.url)"
                    :class="{
                        'bg-primary text-primary-foreground': link.active,
                    }"
                    v-html="link.label"
                />
            </div>

            <!-- DELETE DIALOG -->
            <Dialog v-model:open="isDeleteDialogOpen">
                <DialogContent
                    class="sm:max-w-md"
                    :disableOutsidePointerEvents="true"
                >
                    <DialogHeader class="space-y-3">
                        <div class="flex items-center gap-2 text-destructive">
                            <AlertTriangle class="h-5 w-5" />
                            <DialogTitle>Delete Company</DialogTitle>
                        </div>

                        <DialogDescription>
                            This action is
                            <span class="font-semibold text-destructive">
                                permanent</span
                            >. Type <strong>delete</strong> to confirm.
                        </DialogDescription>
                    </DialogHeader>

                    <Input
                        v-model="deleteConfirmText"
                        placeholder="Type delete to confirm"
                        class="focus-visible:ring-destructive"
                    />

                    <DialogFooter class="gap-2">
                        <Button
                            variant="outline"
                            @click="isDeleteDialogOpen = false"
                        >
                            Cancel
                        </Button>

                        <Button
                            variant="destructive"
                            :disabled="
                                deleteConfirmText.trim().toLowerCase() !==
                                DELETE_CONFIRM_TEXT
                            "
                            @click="forceDeleteCompany"
                        >
                            Permanently Delete
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- RESTORE DIALOG -->
            <Dialog v-model:open="isRestoreDialogOpen">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Restore Company</DialogTitle>
                        <DialogDescription>
                            This will make the company active again.
                        </DialogDescription>
                    </DialogHeader>

                    <DialogFooter class="gap-2">
                        <Button
                            variant="outline"
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
