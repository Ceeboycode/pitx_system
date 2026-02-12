<script setup lang="ts">
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { create, index } from '@/routes/vehicles';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';


import {
  Table,
  TableBody,
  TableCaption,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Button } from '@/components/ui/button'

defineProps<{
    vehicles: {
        data: any[];
        links: any[];
    };
}>();

const filteredVehicles = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return vehicles.value;

    return vehicles.value.filter((v) => {
        const haystack = [
            v.vehicle_type?.type_name ?? '',
            v.plate_number ?? '',
            v.company?.company_name ?? '',
            v.route?.route_name ?? '',
        ]
            .join(' ')
            .toLowerCase();

        return haystack.includes(q);
    });
});

const perPage = ref(5);
const page = ref(1);

const total = computed(() => filteredVehicles.value.length);
const from = computed(() => (total.value === 0 ? 0 : (page.value - 1) * perPage.value + 1));
const to = computed(() => Math.min(page.value * perPage.value, total.value));
const lastPage = computed(() => Math.max(1, Math.ceil(total.value / perPage.value)));

const paginatedVehicles = computed(() => {
    const start = (page.value - 1) * perPage.value;
    return filteredVehicles.value.slice(start, start + perPage.value);
});

const resetPageIfNeeded = () => {
    if (page.value > lastPage.value) page.value = 1;
};
const onSearchInput = (e: Event) => {
    search.value = (e.target as HTMLInputElement).value;
    page.value = 1;
};


const archiveVehicle = (vehicleId: number) => {
    vehicles.value = vehicles.value.filter((v) => v.id !== vehicleId);
    resetPageIfNeeded();
};

const goPrev = () => {
    if (page.value > 1) page.value--;
};
const goNext = () => {
    if (page.value < lastPage.value) page.value++;
};
</script>

<template>
    <Head title="Vehicles" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Card class="mx-10 mt-3">
                <CardHeader>
                    <CardTitle>Vehicles</CardTitle>
                    <CardDescription>List of all vehicles in the system.</CardDescription>

                    <CardAction>
                        <Button size="sm" asChild>
                            <Link :href="create().url"> <Plus /> Add Vehicle</Link>
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent class="space-y-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div class="w-full max-w-sm">
                            <input
                                :value="search"
                                @input="onSearchInput"
                                type="text"
                                placeholder="Search vehicles..."
                                class="w-full rounded-md border bg-background px-3 py-2 text-sm outline-none focus:ring-2"
                            />
                        </div>
                    </div>

                    <!-- Table -->
                    <Table>
                        <TableCaption>List of Vehicles</TableCaption>

                        <TableHeader>
                            <TableRow>
                                <TableHead>Vehicle Type</TableHead>
                                <TableHead>Plate number</TableHead>
                                <TableHead>Company</TableHead>
                                <TableHead>Route</TableHead>
                                <TableHead>Action</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow v-for="vehicle in paginatedVehicles" :key="vehicle.id">
                                <TableCell>{{ vehicle.vehicle_type?.type_name ?? '—' }}</TableCell>
                                <TableCell>{{ vehicle.plate_number ?? '—' }}</TableCell>
                                <TableCell>{{ vehicle.company?.company_name ?? '—' }}</TableCell>
                                <TableCell>{{ vehicle.route?.route_name ?? '—' }}</TableCell>

                                <TableCell>
                                    <div class="flex items-center space-x-2">
                                        <Button size="sm" variant="ghost" asChild>
                                            <Link href="#">
                                                <Eye class="mr-2 h-4 w-4" />
                                                View
                                            </Link>
                                        </Button>

                                        <Button size="sm" variant="secondary" asChild>
                                            <Link href="#">
                                                <Pencil class="mr-2 h-4 w-4" />
                                                Edit
                                            </Link>
                                        </Button>

                                        <Button
                                            size="sm"
                                            variant="archive"
                                            class="cursor-pointer"
                                            @click="archiveVehicle(vehicle.id)"
                                        >
                                            <Archive class="mr-2 h-4 w-4" />
                                            Archive
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>

                            <!-- Empty State -->
                            <TableRow v-if="paginatedVehicles.length === 0">
                                <TableCell colspan="5" class="py-10 text-center text-muted-foreground">
                                    No vehicles found.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-muted-foreground">
                            Showing {{ from }} to {{ to }} of {{ total }} results
                        </p>

                        <div class="flex items-center gap-2">
                            <Button size="sm" variant="outline" class="cursor-pointer" @click="goPrev" :disabled="page === 1">
                                Prev
                            </Button>

                            <span class="text-sm">
                                Page <span class="font-medium">{{ page }}</span> / {{ lastPage }}
                            </span>

                            <Button
                                size="sm"
                                variant="outline"
                                class="cursor-pointer"
                                @click="goNext"
                                :disabled="page === lastPage"
                            >
                                Next
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
