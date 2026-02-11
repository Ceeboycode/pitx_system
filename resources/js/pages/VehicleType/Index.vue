<script setup lang="ts">
import CreateVehicleTypeDialog from '@/components/vehicleType/CreateVehicleTypeDialog.vue';
import DeleteVehicleTypeDialog from '@/components/vehicleType/DeleteVehicleTypeDialog.vue';
import EditVehicleTypeDialog from '@/components/vehicleType/EditVehicleTypeDialog.vue';

import InertiaPagination from '@/components/InertiaPagination.vue';
import SearchInput from '@/components/SearchInput.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
// import {
//     Dialog,
//     DialogClose,
//     DialogContent,
//     DialogDescription,
//     DialogFooter,
//     DialogHeader,
//     DialogTitle,
//     DialogTrigger,
// } from '@/components/ui/dialog';
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

import AppLayout from '@/layouts/AppLayout.vue';


import { index, show } from '@/routes/vehicle-types';

import { Head, Link } from '@inertiajs/vue3';

import { Edit, Eye, Plus, Trash2 } from 'lucide-vue-next';

import { type BreadcrumbItem } from '@/types';

import { ref } from 'vue';

type VehicleType = {
    id: number;
    type_name: string;
    is_active: boolean;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Vehicle Types', href: index().url },
];

const props = defineProps<{
    vehicleTypes: any;
    filters: { search: string | null }; // Active filters
}>();

const createOpen = ref(false);
const editOpen = ref(false);
const deleteOpen = ref(false);
const selectedVehicleType = ref<VehicleType | null>(null);

function openEdit(vehicle_type: VehicleType) {
    selectedVehicleType.value = vehicle_type;
    editOpen.value = true;
}

function openDelete(vehicle_type: VehicleType) {
    selectedVehicleType.value = vehicle_type;
    deleteOpen.value = true;
}
</script>

<template>
    <Head title="Vehicle Types" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >

            <Card class="">
                <CardHeader>
                    <CardTitle>Vehicle Types</CardTitle>
                    <CardDescription>
                        List of all vehicle types in the system.
                    </CardDescription>

                    <CardAction>
                        <Button class="cursor-pointer" size="sm" @click="createOpen = true">
                            <Plus class="mr-2 h-4 w-4" />
                            New Vehicle Type
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent class="space-y-4">
                    <div
                        class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="w-full max-w-sm">
                            <SearchInput
                                :route="index().url"
                                :initial-value="filters.search"
                                placeholder="Search vehicle types..."
                                :only="['vehicleTypes', 'filters']"
                                :debounce="350"
                            />
                        </div>

                        <div class="flex gap-2 sm:justify-end">
                            <Button class="cursor-pointer" size="sm" variant="outline">
                                <Upload class="mr-2 h-4 w-4" />
                                Import
                            </Button>

                            <Button class="cursor-pointer" size="sm" variant="outline">
                                <Download class="mr-2 h-4 w-4" />
                                Export
                            </Button>
                        </div>
                    </div>

                    <Table>
                        <TableCaption> List of vehicle types. </TableCaption>

                        <TableHeader>
                            <TableRow>
                                <TableHead>Vehicle Type</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Actions</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow
                                v-for="vehicle in vehicleTypes.data"
                                :key="vehicle.id"
                            >
                                <TableCell class="capitalize">
                                    {{ vehicle.type_name }}
                                </TableCell>

                                <TableCell>
                                    <Badge
                                        :variant="
                                            vehicle.is_active
                                                ? 'success'
                                                : 'destructive'
                                        "
                                    >
                                        {{
                                            vehicle.is_active
                                                ? 'Active'
                                                : 'Inactive'
                                        }}
                                    </Badge>
                                </TableCell>

                                <TableCell class="space-x-2">
                                    <Button as-child size="sm" variant="ghost">
                                        <Link
                                            :href="
                                                show({
                                                    vehicle_type: vehicle.id,
                                                }).url
                                            "
                                        >
                                            <Eye class="mr-2 h-4 w-4" />
                                            View
                                        </Link>
                                    </Button>

                                    <!-- Edit -->
                                    <Button
                                        class="cursor-pointer"
                                        size="sm"
                                        variant="default"
                                        @click="openEdit(vehicle)"
                                    >
                                        <Edit class="mr-2 h-4 w-4" />
                                        Edit
                                    </Button>

                                    <Button
                                        class="cursor-pointer"
                                        size="sm"
                                        variant="destructive"
                                        @click="openDelete(vehicle)"
                                    >
                                        <Trash2 class="mr-2 h-4 w-4" />
                                        Delete
                                    </Button>
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="vehicleTypes.data.length === 0">
                                <TableCell
                                    colspan="3"
                                    class="py-10 text-center text-muted-foreground"
                                >
                                    No vehicle types found.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <InertiaPagination
                        :links="vehicleTypes.links"
                        :meta="{
                            from: vehicleTypes.from,
                            to: vehicleTypes.to,
                            total: vehicleTypes.total,
                        }"
                    />
                </CardContent>
            </Card>

            <CreateVehicleTypeDialog v-model:open="createOpen" />

            <EditVehicleTypeDialog
                v-if="selectedVehicleType"
                v-model:open="editOpen"
                :vehicle_type="selectedVehicleType"
            />

            <DeleteVehicleTypeDialog
                v-if="selectedVehicleType"
                v-model:open="deleteOpen"
                :vehicle_type="selectedVehicleType"
            />
        </div>
    </AppLayout>
</template>
