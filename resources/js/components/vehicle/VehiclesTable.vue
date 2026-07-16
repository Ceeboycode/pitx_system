<script setup lang="ts">
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type Vehicle = {
    id: number;
    plate_number?: string | null;
    body_number?: string | null;
    capacity?: string | number | null;
    created_at_human?: string | null;
    deleted_at_human?: string | null;
    company?: { company_name: string } | null;
    route?: { route_name: string } | null;
    vehicle_type?: { type_name: string } | null;
    deleter?: { name: string } | null;
};

const props = withDefaults(
    defineProps<{
        vehicles: Vehicle[];
        mode: 'active' | 'archived';
        caption?: string;
    }>(),
    {
        caption: 'Vehicles',
    },
);
</script>

<template>
    <Table>
        <!-- CODE: <TableCaption>{{ caption }}</TableCaption> -->

        <TableHeader>
            <TableRow>
                <TableHead>Company</TableHead>
                <TableHead>Routes</TableHead>
                <TableHead>Vehicle type</TableHead>
                <TableHead>Plate number</TableHead>
                <TableHead>Body number</TableHead>
                <TableHead>Capacity</TableHead>

                <TableHead>
                    {{ mode === 'archived' ? 'Archived At' : 'Created At' }}
                </TableHead>

                <TableHead v-if="mode === 'archived'">Archived By</TableHead>

                <TableHead class="text-right">Action</TableHead>
            </TableRow>
        </TableHeader>

        <TableBody>
            <TableRow v-for="vehicle in vehicles" :key="vehicle.id">
                <TableCell>{{
                    vehicle.company?.company_name || '-'
                }}</TableCell>
                <TableCell>{{ vehicle.route?.route_name || '-' }}</TableCell>
                <TableCell>{{
                    vehicle.vehicle_type?.type_name || '-'
                }}</TableCell>
                <TableCell>{{ vehicle.plate_number || '-' }}</TableCell>
                <TableCell>{{ vehicle.body_number || '-' }}</TableCell>
                <TableCell>{{ vehicle.capacity ?? '-' }}</TableCell>

                <TableCell>
                    {{
                        mode === 'archived'
                            ? vehicle.deleted_at_human || '-'
                            : vehicle.created_at_human || '-'
                    }}
                </TableCell>

                <TableCell v-if="mode === 'archived'">
                    {{ vehicle.deleter?.name || '-' }}
                </TableCell>

                <TableCell class="text-right">
                    <div class="flex flex-wrap justify-end gap-2">
                        <slot name="actions" :vehicle="vehicle" />
                    </div>
                </TableCell>
            </TableRow>

            <TableRow v-if="vehicles.length === 0">
                <TableCell
                    :colspan="mode === 'archived' ? 9 : 8"
                    class="py-10 text-center text-muted-foreground"
                >
                    No vehicles found.
                </TableCell>
            </TableRow>
        </TableBody>
    </Table>
</template>
