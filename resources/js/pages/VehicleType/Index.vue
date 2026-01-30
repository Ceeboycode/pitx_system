<script setup lang="ts">
/* ======================================================
   Imports
====================================================== */
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { Badge } from '@/components/ui/badge';
import InertiaPagination from '@/components/InertiaPagination.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardAction, CardContent } from '@/components/ui/card';
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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import InertiaPagination from '@/components/InertiaPagination.vue';

import { destroy, index, show, store, update } from '@/routes/vehicle-types';
import { type BreadcrumbItem } from '@/types';

/* ======================================================
   Types
====================================================== */
interface VehicleType {
    id: number;
    type_name: string;
    is_active: boolean;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface VehicleTypesPagination {
    data: VehicleType[];
    links: PaginationLink[];
}

/* ======================================================
   Props
====================================================== */
defineProps<{
    vehicleTypes: VehicleTypesPagination;
}>();

/* ======================================================
   Breadcrumbs
====================================================== */
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Vehicle Types',
        href: index().url,
    },
];

/* ======================================================
   Dialog State
====================================================== */
const createOpen = ref(false);
const editOpen = ref(false);
const deleteOpen = ref(false);
const editingId = ref<number | null>(null);
const deletingId = ref<number | null>(null);
const deleteConfirmText = ref('');

/* ======================================================
   Forms
====================================================== */
const createForm = useForm({ type_name: '' });
const editForm = useForm({ type_name: '', is_active: 1 });

/* ======================================================
   Actions — Create
====================================================== */
const createVehicle = () => {
    createForm.post(store().url, {
        onSuccess: () => {
            createForm.reset();
            createOpen.value = false;
        },
        onError: () => toast.error('Failed to add vehicle type.'),
    });
};

/* ======================================================
   Actions — Edit
====================================================== */
const openEditDialog = (vehicle: VehicleType) => {
    editingId.value = vehicle.id;
    editForm.type_name = vehicle.type_name;
    editForm.is_active = vehicle.is_active ? 1 : 0;
    editOpen.value = true;
};

const updateVehicle = () => {
    if (!editingId.value) return;

    editForm.put(update(editingId.value).url, {
        onSuccess: () => {
            editForm.reset();
            editOpen.value = false;
        },
        onError: () => toast.error('Failed to update vehicle type.'),
    });
};

/* ======================================================
   Actions — Delete
====================================================== */
const openDeleteDialog = (vehicle: VehicleType) => {
    deletingId.value = vehicle.id;
    deleteConfirmText.value = '';
    deleteOpen.value = true;
};

const confirmDelete = () => {
    if (!deletingId.value) return;

    useForm({}).delete(destroy(deletingId.value).url, {
        preserveScroll: true,
        onSuccess: () => {
            deleteOpen.value = false;
        },
        onError: () => toast.error('Failed to delete vehicle type.'),
    });
};
</script>

<template>
    <Head title="Vehicle Types" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <!-- ================= CARD ================= -->
            <Card>
                <div class="flex items-center justify-between p-4">
                    <div>
                        <h3 class="text-lg font-semibold">Vehicle Types</h3>
                        <p class="text-sm text-gray-500">
                            List of all vehicle types in the system.
                        </p>
                    </div>

          <div class="flex gap-2">
            <!-- CREATE -->
            <Dialog v-model:open="createOpen">
              <DialogTrigger as-child>
                <Button size="sm">Add Vehicle Type</Button>
              </DialogTrigger>
              <DialogContent class="sm:max-w-md">
                <DialogHeader>
                  <DialogTitle>Add Vehicle Type</DialogTitle>
                  <DialogDescription>Create a new vehicle type.</DialogDescription>
                </DialogHeader>
                <form @submit.prevent="createVehicle" class="mt-2 space-y-2">
                  <Label>Vehicle Type</Label>
                  <Input v-model="createForm.type_name" />
                  <InputError :message="createForm.errors.type_name" />
                  <DialogFooter>
                    <DialogClose asChild>
                      <Button variant="secondary" size="sm">Cancel</Button>
                    </DialogClose>
                    <Button type="submit" size="sm" :disabled="createForm.processing">Save</Button>
                  </DialogFooter>
                </form>
              </DialogContent>
            </Dialog>
          </div>
        </div>

                <!-- TABLE -->
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Vehicle Type</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Action</TableHead>
                            </TableRow>
                        </TableHeader>

            <TableBody>
              <TableRow v-for="vehicle in vehicleTypes.data" :key="vehicle.id">
                <TableCell>{{ vehicle.type_name }}</TableCell>
                <TableCell>
                  <span
                    class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                    :class="vehicle.is_active
                      ? 'bg-green-100 text-green-700'
                      : 'bg-red-100 text-red-700'"
                  >
                    {{ vehicle.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </TableCell>
                <TableCell class="space-x-2">
                  <Button asChild size="sm" variant="outline">
                    <Link :href="show(vehicle.id).url">View</Link>
                  </Button>
                  <Button size="sm" @click="openEditDialog(vehicle)">Edit</Button>
                  <Button size="sm" variant="destructive" @click="openDeleteDialog(vehicle)">Delete</Button>
                </TableCell>
              </TableRow>

                            <TableRow v-if="vehicleTypes.data.length === 0">
                                <TableCell
                                    colspan="3"
                                    class="text-center text-sm text-gray-500"
                                >
                                    No vehicle types found.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>

                <!-- PAGINATION -->
                <CardAction class="justify-end p-4">
                    <InertiaPagination :links="vehicleTypes.links" />
                </CardAction>
            </Card>

            <!-- ================= EDIT DIALOG ================= -->
            <Dialog v-model:open="editOpen">
                <DialogContent class="sm:max-w-md">
                    <form @submit.prevent="updateVehicle">
                        <DialogHeader>
                            <DialogTitle>Edit Vehicle Type</DialogTitle>
                            <DialogDescription
                                >Update vehicle type details.</DialogDescription
                            >
                        </DialogHeader>

                        <div class="mt-4 space-y-4">
                            <div>
                                <Label>Vehicle Type</Label>
                                <Input v-model="editForm.type_name" />
                                <InputError
                                    :message="editForm.errors.type_name"
                                />
                            </div>

                            <div>
                                <Label>Status</Label>
                                <select
                                    v-model="editForm.is_active"
                                    class="w-full rounded-md border px-3 py-2 text-sm"
                                >
                                    <option :value="1">Active</option>
                                    <option :value="0">Inactive</option>
                                </select>
                                <InputError
                                    :message="editForm.errors.is_active"
                                />
                            </div>
                        </div>

            <DialogFooter class="mt-6">
              <DialogClose asChild>
                <Button variant="secondary" size="sm">Cancel</Button>
              </DialogClose>
              <Button size="sm" type="submit" :disabled="editForm.processing">Update</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      <!-- ================= DELETE DIALOG ================= -->
      <Dialog v-model:open="deleteOpen">
        <DialogContent class="sm:max-w-md">
          <DialogHeader>
            <DialogTitle class="text-red-600">Confirm Delete</DialogTitle>
            <DialogDescription>
              This action cannot be undone. Type <strong>delete</strong> to confirm.
            </DialogDescription>
          </DialogHeader>

          <div class="mt-4">
            <Input v-model="deleteConfirmText" placeholder="delete" />
          </div>

          <DialogFooter class="mt-6 flex gap-2">
            <Button variant="outline" @click="deleteOpen = false">Cancel</Button>
            <Button
              variant="destructive"
              :disabled="deleteConfirmText.toLowerCase() !== 'delete'"
              @click="confirmDelete"
            >
              Delete
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>
