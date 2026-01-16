<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, show, store, trash, update } from '@/routes/companies';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

/* ======================
 | Types
 ====================== */

interface Company {
    id: number;
    company_name: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedCompanies {
    data: Company[];
    links: PaginationLink[];
}

/* ======================
 | Props
 ====================== */

defineProps<{
    companies: PaginatedCompanies;
}>();

/* ======================
 | Breadcrumbs
 ====================== */

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Companies',
        href: index().url,
    },
];
/* ======================
 | useful Data
 ====================== */
const form = useForm({
    company_name: '',
});

const editForm = useForm({
    company_name: '',
});

/* ======================
 | Actions
 ====================== */

const viewCompany = (id: number) => {
    router.visit(show(id).url);
};

/* ======================
 | Dialog State
 ====================== */
const isCreateDialogOpen = ref(false);
const isEditDialogOpen = ref(false);
const isDeleteDialogOpen = ref(false);
const editingCompanyId = ref<number | null>(null);
const deletingCompanyId = ref<number | null>(null);

const editCompany = (company: { id: number; company_name: string }) => {
    editingCompanyId.value = company.id;
    editForm.company_name = company.company_name;
    isEditDialogOpen.value = true;
};
/* ======================
 | Submit
 ====================== */
const createCompany = () => {
    // Submit the form to create a new company
    form.post(store().url, {
        onSuccess: () => {
            form.reset();
            isCreateDialogOpen.value = false;
            toast.success('Company created successfully!');
        },
        onError: () => {
            toast.error(
                'Failed to create company. Please check the form for errors.',
            );
        },
    });
};
// Update Company
const updateCompany = () => {
    if (!editingCompanyId.value) return;

    editForm.put(update(editingCompanyId.value).url, {
        onSuccess: () => {
            editForm.reset();
            isEditDialogOpen.value = false;
            editingCompanyId.value = null;
            toast.success('Company updated successfully!');
        },
        onError: () => {
            toast.error(
                'Failed to update company. Please check the form for errors.',
            );
        },
    });
};

// Delete Company
const confirmDeleteCompany = () => {
    if (!deletingCompanyId.value) return;

    router.delete(update(deletingCompanyId.value).url, {
        onSuccess: () => {
            toast.success('Company archived successfully!');
            isDeleteDialogOpen.value = false;
            deletingCompanyId.value = null;
        },
        onError: () => {
            toast.error('Failed to archive company.');
        },
    });
};
</script>

<template>
    <Head title="Companies" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <h1 class="mb-4 text-2xl font-bold">Companies</h1>
            <!-- Trash button -->
            <Link :href="trash().url">
                <Button>Trash</Button>
            </Link>

            <!-- Create Company Dialog -->
            <Dialog v-model:open="isCreateDialogOpen">
                <!-- everything goes inside -->
                <DialogTrigger as-child>
                    <Button variant="default"> Create New Company </Button>
                </DialogTrigger>

                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Create New Company</DialogTitle>
                        <DialogDescription>
                            Fill out the form below to create a new company.
                        </DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-4 py-4">
                        <div class="grid gap-2">
                            <Label for="name">Company Name</Label>
                            <Input
                                id="name"
                                v-model="form.company_name"
                                placeholder="Enter company name"
                            />
                            <InputError :message="form.errors.company_name" />
                        </div>
                    </div>
                    <DialogFooter>
                        <DialogClose as-child>
                            <Button variant="secondary"> Cancel </Button>
                        </DialogClose>

                        <Button @click="createCompany"> Save </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Table -->
            <table class="min-w-full border border-gray-300">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border px-4 py-2">Company Name</th>
                        <th class="border px-4 py-2">Action</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="company in companies.data" :key="company.id">
                        <td class="border px-4 py-2">
                            {{ company.company_name }}
                        </td>

                        <td class="border px-4 py-2">
                            <button
                                class="rounded bg-violet-600 px-3 py-1 text-white hover:bg-violet-700"
                                @click="viewCompany(company.id)"
                            >
                                View
                            </button>

                            <button
                                class="rounded bg-blue-600 px-3 py-1 text-white hover:bg-blue-700"
                                @click="editCompany(company)"
                            >
                                Edit
                            </button>

                            <button
                                class="rounded bg-gray-600 px-3 py-1 text-white hover:bg-gray-700"
                                @click="
                                    deletingCompanyId = company.id;
                                    isDeleteDialogOpen = true;
                                "
                            >
                                Archive
                            </button>
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
                    @click="
                        link.url &&
                        router.visit(link.url, { preserveState: true })
                    "
                    class="rounded border px-3 py-1"
                    :class="{
                        'bg-blue-600 text-white': link.active,
                        'cursor-not-allowed text-gray-400': !link.url,
                    }"
                />
            </div>

            <!-- Edit Company Dialog -->
            <Dialog v-model:open="isEditDialogOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Edit Company</DialogTitle>
                    </DialogHeader>

                    <div class="grid gap-4 py-4">
                        <Label>Company Name</Label>
                        <Input v-model="editForm.company_name" />

                        <p
                            v-if="editForm.errors.company_name"
                            class="text-sm text-red-500"
                        >
                            {{ editForm.errors.company_name }}
                        </p>
                    </div>

                    <DialogFooter>
                        <Button
                            variant="secondary"
                            @click="isEditDialogOpen = false"
                        >
                            Cancel
                        </Button>

                        <Button
                            :disabled="editForm.processing"
                            @click="updateCompany"
                        >
                            Update
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Archive Company Dialog -->
            <Dialog v-model:open="isDeleteDialogOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle> Archive Company </DialogTitle>
                        <DialogDescription>
                            Are you sure you want to archive this company? You
                            can restore it later from the Trash.
                        </DialogDescription>
                    </DialogHeader>

                    <DialogFooter>
                        <Button
                            variant="secondary"
                            @click="isDeleteDialogOpen = false"
                        >
                            Cancel
                        </Button>

                        <Button
                            variant="default"
                            @click="confirmDeleteCompany"
                        >
                            Archive
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
