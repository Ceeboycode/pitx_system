<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { create, index, store } from '@/routes/vehicles';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import Button from '@/components/ui/button/Button.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import InputError from '@/components/InputError.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

/**
 * Data interfaces coming from the controller (match DB column names)
 */
interface Company {
    id: number;
    company_name: string;
}

interface RouteItem {
    id: number;
    route_name: string;
}

interface VehicleType {
    id: number;
    type_name: string;
}

interface PageProps {
    companies: Company[];
    routes: RouteItem[];
    vehicleTypes: VehicleType[];
}

/**
 * Form interface (match vehicles table columns)
 * IDs are strings because shadcn Select binds best to string values.
 */
interface VehicleForm {
    plate_number: string;
    body_number: string;
    capacity: string;
    company_id: string;
    route_id: string;
    vehicle_type_id: string;
}

const props = defineProps<PageProps>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Vehicles', href: index().url },
    { title: 'Create Vehicle', href: create().url },
];

const form = useForm<VehicleForm>({
    plate_number: '',
    body_number: '',
    capacity: '',
    company_id: '',
    route_id: '',
    vehicle_type_id: '',
});

const submit = () => {
    form.post(store().url, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
        onError: () => {
            toast.error('Failed to create vehicle');
        },
    });
};
</script>

<template>
    <Head title="Create Vehicle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 justify-center p-4">
            <Card class="w-full max-w-2xl">
                <CardHeader>
                    <CardTitle>Create Vehicle</CardTitle>
                    <CardDescription>
                        Fill in the details to register a new vehicle
                    </CardDescription>
                </CardHeader>

                <form @submit.prevent="submit">
                    <CardContent class="space-y-6">
                        <!-- Plate Number -->
                        <div class="space-y-2">
                            <Label for="plate_number">Plate Number</Label>
                            <Input
                                id="plate_number"
                                v-model="form.plate_number"
                                placeholder="e.g. ABC-1234"
                            />
                            <p
                                v-if="form.errors.plate_number"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.plate_number }}
                            </p>
                        </div>

                        <!-- Body Number -->
                        <div class="space-y-2">
                            <Label for="body_number">Body Number</Label>
                            <Input
                                id="body_number"
                                v-model="form.body_number"
                                placeholder="e.g. BODY-001"
                            />
                            <p
                                v-if="form.errors.body_number"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.body_number }}
                            </p>
                        </div>

                        <!-- Capacity -->
                        <div class="space-y-2">
                            <Label for="capacity">Capacity</Label>
                            <Input
                                id="capacity"
                                v-model="form.capacity"
                                type="number"
                                min="1"
                                placeholder="e.g. 14"
                            />
                            <p
                                v-if="form.errors.capacity"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.capacity }}
                            </p>
                        </div>

                        <!-- Company -->
                        <div class="space-y-2">
                            <Label>Company</Label>
                            <Select v-model="form.company_id">
                                <SelectTrigger>
                                    <SelectValue
                                        placeholder="Select a company"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="company in props.companies"
                                        :key="company.id"
                                        :value="company.id.toString()"
                                    >
                                        {{ company.company_name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p
                                v-if="form.errors.company_id"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.company_id }}
                            </p>
                        </div>

                        <!-- Route -->
                        <div class="space-y-2">
                            <Label>Route</Label>
                            <Select v-model="form.route_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select a route" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="route in props.routes"
                                        :key="route.id"
                                        :value="route.id.toString()"
                                    >
                                        {{ route.route_name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p
                                v-if="form.errors.route_id"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.route_id }}
                            </p>
                        </div>

                        <!-- Vehicle Type -->
                        <div class="space-y-2">
                            <Label>Vehicle Type</Label>
                            <Select v-model="form.vehicle_type_id">
                                <SelectTrigger>
                                    <SelectValue
                                        placeholder="Select vehicle type"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="type in props.vehicleTypes"
                                        :key="type.id"
                                        :value="type.id.toString()"
                                    >
                                        {{ type.type_name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p
                                v-if="form.errors.vehicle_type_id"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.vehicle_type_id }}
                            </p>
                        </div>
                    </CardContent>

                    <CardFooter class="flex justify-end gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            :as="'a'"
                            :href="index().url"
                        >
                            Cancel
                        </Button>

                        <Button type="submit" :disabled="form.processing">
                            Save Vehicle
                        </Button>
                    </CardFooter>
                </form>
            </Card>
        </div>
    </AppLayout>
</template>
