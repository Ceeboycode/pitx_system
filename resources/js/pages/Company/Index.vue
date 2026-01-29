<script setup lang="ts">
/* ======================================================
   Imports
====================================================== */
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import InertiaPagination from '@/components/InertiaPagination.vue';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  Card,
  CardAction,
  CardContent,
  CardHeader,
  CardTitle,
  CardDescription,
} from '@/components/ui/card';
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

import { index, show, store, trash, update } from '@/routes/companies';
import { type BreadcrumbItem } from '@/types';

/* ======================================================
   Types
====================================================== */
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

/* ======================================================
   Props
====================================================== */
defineProps<{
  companies: PaginatedCompanies;
}>();

/* ======================================================
   Breadcrumbs
====================================================== */
const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Companies',
    href: index().url,
  },
];

/* ======================================================
   Forms
====================================================== */
const form = useForm({ company_name: '' });
const editForm = useForm({ company_name: '' });

/* ======================================================
   Dialog State
====================================================== */
const isCreateDialogOpen = ref(false);
const isEditDialogOpen = ref(false);
const isDeleteDialogOpen = ref(false);
const editingCompanyId = ref<number | null>(null);
const deletingCompanyId = ref<number | null>(null);

/* ======================================================
   Actions
====================================================== */
const viewCompany = (id: number) => {
  router.visit(show(id).url);
};

const createCompany = () => {
  form.post(store().url, {
    onSuccess: () => {
      form.reset();
      isCreateDialogOpen.value = false;
      toast.success('Company created successfully!');
    },
    onError: () => toast.error('Failed to create company.'),
  });
};

const editCompany = (company: Company) => {
  editingCompanyId.value = company.id;
  editForm.company_name = company.company_name;
  isEditDialogOpen.value = true;
};

const updateCompany = () => {
  if (!editingCompanyId.value) return;

  editForm.put(update(editingCompanyId.value).url, {
    onSuccess: () => {
      editForm.reset();
      isEditDialogOpen.value = false;
      editingCompanyId.value = null;
      toast.success('Company updated successfully!');
    },
    onError: () => toast.error('Failed to update company.'),
  });
};

const confirmDeleteCompany = () => {
  if (!deletingCompanyId.value) return;

  router.delete(update(deletingCompanyId.value).url, {
    onSuccess: () => {
      toast.success('Company archived successfully!');
      isDeleteDialogOpen.value = false;
      deletingCompanyId.value = null;
    },
    onError: () => toast.error('Failed to archive company.'),
  });
};
</script>

<template>
  <Head title="Companies" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 flex flex-col gap-4">
      <Card>
        <!-- Card Header -->
        <CardHeader class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <CardTitle>Companies</CardTitle>
            <CardDescription>List of all companies in the system.</CardDescription>
          </div>

          <div class="flex gap-2">
            <!-- Create Company Dialog -->
            <Dialog v-model:open="isCreateDialogOpen">
              <DialogTrigger asChild>
                <Button size="sm">Create New Company</Button>
              </DialogTrigger>

              <DialogContent class="sm:max-w-md">
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
                  <DialogClose asChild>
                    <Button variant="secondary">Cancel</Button>
                  </DialogClose>
                  <Button @click="createCompany">Save</Button>
                </DialogFooter>
              </DialogContent>
            </Dialog>

            <!-- Trash Button -->
            <Link :href="trash().url">
              <Button size="sm" variant="outline">Trash</Button>
            </Link>
          </div>
        </CardHeader>

        <!-- Table -->
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Company Name</TableHead>
                <TableHead>Action</TableHead>
              </TableRow>
            </TableHeader>

            <TableBody>
              <TableRow v-for="company in companies.data" :key="company.id">
                <TableCell>{{ company.company_name }}</TableCell>
                <TableCell class="space-x-2">
                  <Button asChild size="sm" variant="outline">
                    <Link :href="show(company.id).url">View</Link>
                  </Button>
                  <Button size="sm" @click="editCompany(company)">Edit</Button>
                  <Button
                    size="sm"
                    variant="archive"
                    @click="deletingCompanyId = company.id; isDeleteDialogOpen = true"
                  >
                    Archive
                  </Button>
                </TableCell>
              </TableRow>

              <TableRow v-if="companies.data.length === 0">
                <TableCell colspan="2" class="text-center text-sm text-gray-500">
                  No companies found.
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>

        <!-- Pagination -->
        <CardAction class="justify-end p-4">
          <InertiaPagination :links="companies.links" />
        </CardAction>
      </Card>

      <!-- Edit Company Dialog -->
      <Dialog v-model:open="isEditDialogOpen">
        <DialogContent class="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Edit Company</DialogTitle>
          </DialogHeader>

          <div class="grid gap-4 py-4">
            <Label>Company Name</Label>
            <Input v-model="editForm.company_name" />
            <InputError :message="editForm.errors.company_name" />
          </div>

          <DialogFooter>
            <DialogClose asChild>
              <Button variant="secondary">Cancel</Button>
            </DialogClose>
            <Button :disabled="editForm.processing" @click="updateCompany">Update</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <!-- Archive Company Dialog -->
      <Dialog v-model:open="isDeleteDialogOpen">
        <DialogContent class="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Archive Company</DialogTitle>
            <DialogDescription>
              Are you sure you want to archive this company? You can restore it later from the Trash.
            </DialogDescription>
          </DialogHeader>

          <DialogFooter>
            <Button variant="secondary" @click="isDeleteDialogOpen = false">Cancel</Button>
            <Button variant="destructive" @click="confirmDeleteCompany">Archive</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>
