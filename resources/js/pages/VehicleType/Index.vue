<script setup lang="ts">
/* ======================================================
   Imports
====================================================== */
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
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

/* ======================================================
   Forms
====================================================== */
const createForm = useForm({
    type_name: '',
});

const editForm = useForm({
    type_name: '',
    is_active: 1,
});

const editingId = ref<number | null>(null);
const deletingId = ref<number | null>(null);
const deleteConfirmText = ref('');

/* ======================================================
   Actions — Create
====================================================== */
const createVehicle = () => {
    createForm.post(store().url, {
        onSuccess: () => {
            createForm.reset();
            createOpen.value = false;
        },
        onError: () => {
            toast.error('Failed to add vehicle type.');
        },
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
        onError: () => {
            toast.error('Failed to update vehicle type.');
        },
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
        onError: () => {
            toast.error('Failed to delete vehicle type.');
        },
    });
};
</script>

<template>
    <Head title="Vehicle Types" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 rounded-xl p-4">
            <!-- ================= CREATE ================= -->
            <Dialog v-model:open="createOpen">
                <DialogTrigger as-child>
                    <Button>Add Vehicle Type</Button>
                </DialogTrigger>

                <DialogContent class="sm:max-w-md">
                    <form @submit.prevent="createVehicle">
                        <DialogHeader>
                            <DialogTitle>Add Vehicle Type</DialogTitle>
                            <DialogDescription
                                >Create a new vehicle type.</DialogDescription
                            >
                        </DialogHeader>

                        <div class="mt-4 space-y-2">
                            <Label>Vehicle Type</Label>
                            <Input v-model="createForm.type_name" />
                            <InputError
                                :message="createForm.errors.type_name"
                            />
                        </div>

                        <DialogFooter class="mt-6">
                            <Button
                                type="submit"
                                :disabled="createForm.processing"
                            >
                                Save
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- ================= TABLE ================= -->
            <div class="overflow-x-auto rounded-lg border">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                Vehicle Type
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                            >
                                Status
                            </th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"
                            >
                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y bg-white">
                        <tr
                            v-for="vehicle in vehicleTypes.data"
                            :key="vehicle.id"
                        >
                            <td class="px-6 py-4 text-sm">
                                {{ vehicle.type_name }}
                            </td>

                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                                    :class="
                                        vehicle.is_active
                                            ? 'bg-green-100 text-green-700'
                                            : 'bg-red-100 text-red-700'
                                    "
                                >
                                    {{
                                        vehicle.is_active
                                            ? 'Active'
                                            : 'Inactive'
                                    }}
                                </span>
                            </td>

                            <td class="space-x-2 px-6 py-4 text-right">
                                <Link
                                    :href="show(vehicle.id).url"
                                    class="rounded bg-gray-600 px-3 py-1 text-sm text-white hover:bg-gray-700"
                                >
                                    View
                                </Link>
                                <button
                                    class="rounded bg-blue-600 px-3 py-1 text-sm text-white hover:bg-blue-700"
                                    @click="openEditDialog(vehicle)"
                                >
                                    Edit
                                </button>

                                <button
                                    class="rounded bg-red-600 px-3 py-1 text-sm text-white hover:bg-red-700"
                                    @click="openDeleteDialog(vehicle)"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>

                        <tr v-if="vehicleTypes.data.length === 0">
                            <td
                                colspan="4"
                                class="px-6 py-4 text-center text-sm text-gray-500"
                            >
                                No vehicle types found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- ================= PAGINATION ================= -->
            <div class="flex gap-1">
                <button
                    v-for="link in vehicleTypes.links"
                    :key="link.label"
                    v-html="link.label"
                    :disabled="!link.url"
                    @click="link.url && $inertia.visit(link.url)"
                    class="rounded border px-3 py-1 text-sm hover:bg-gray-100 disabled:opacity-50"
                />
            </div>

            <!-- ================= EDIT ================= -->
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
                            <Button
                                type="submit"
                                :disabled="editForm.processing"
                            >
                                Update
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- ================= DELETE ================= -->
            <Dialog v-model:open="deleteOpen">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle class="text-red-600"
                            >Confirm Delete</DialogTitle
                        >
                        <DialogDescription>
                            This action cannot be undone. Type
                            <strong>delete</strong> to confirm.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="mt-4">
                        <Input
                            v-model="deleteConfirmText"
                            placeholder="delete"
                        />
                    </div>

                    <DialogFooter class="mt-6 flex gap-2">
                        <Button variant="outline" @click="deleteOpen = false">
                            Cancel
                        </Button>

                        <Button
                            variant="destructive"
                            :disabled="
                                deleteConfirmText.toLowerCase() !== 'delete'
                            "
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
