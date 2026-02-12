<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { edit, index, update } from '@/routes/vehicles';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';

type NamedOption = {
    id: number;
};

const props = defineProps<{
    vehicle: {
        id: number;
        plate_number: string;
        body_number: string | null;
        capacity: number | null;
        company_id: number | null;
        route_id: number | null;
        vehicle_type_id: number | null;
    };
    companies: (NamedOption & {
        company_name: string;
    })[];
    routes: (NamedOption & {
        route_name: string;
    })[];
    vehicleTypes: (NamedOption & {
        type_name: string;
    })[];
}>();

const companySearch = ref('');
const routeSearch = ref('');
const vehicleTypeSearch = ref('');

const includesText = (value: string, query: string) =>
    value.toLowerCase().includes(query.trim().toLowerCase());

const filteredCompanies = computed(() =>
    props.companies.filter((company) =>
        includesText(company.company_name, companySearch.value),
    ),
);

const filteredRoutes = computed(() =>
    props.routes.filter((route) =>
        includesText(route.route_name, routeSearch.value),
    ),
);

const filteredVehicleTypes = computed(() =>
    props.vehicleTypes.filter((vehicleType) =>
        includesText(vehicleType.type_name, vehicleTypeSearch.value),
    ),
);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Vehicles',
        href: index().url,
    },
    {
        title: 'Edit Vehicle',
        href: edit({ vehicle: props.vehicle.id }).url,
    },
];

const form = useForm({
    plate_number: props.vehicle.plate_number ?? '',
    body_number: props.vehicle.body_number ?? '',
    capacity: props.vehicle.capacity ?? undefined,
    company_id: props.vehicle.company_id,
    route_id: props.vehicle.route_id,
    vehicle_type_id: props.vehicle.vehicle_type_id,
});

const submit = () => {
    form.put(update({ vehicle: props.vehicle.id }).url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Vehicle updated successfully!');
        },
    });
};
</script>

<template>
    <Head title="Edit Vehicle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Card class="mx-5">
                <CardHeader>
                    <CardTitle>Edit Vehicle</CardTitle>
                    <CardDescription>
                        Update the details for this vehicle.
                    </CardDescription>
                </CardHeader>

                <CardContent>
                    <form class="space-y-8" @submit.prevent="submit">
                        <div class="space-y-4">
                            <p class="text-sm font-medium">Assignment</p>

                            <div class="grid gap-4 md:grid-cols-3">
                                <div class="space-y-2">
                                    <Label
                                        >Company
                                        <span class="text-red-500"
                                            >*</span
                                        ></Label
                                    >
                                    <Select v-model="form.company_id">
                                        <SelectTrigger class="w-full">
                                            <SelectValue
                                                placeholder="Select a company"
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <div class="p-2">
                                                    <Input
                                                        v-model="companySearch"
                                                        placeholder="Search company..."
                                                        autocomplete="off"
                                                        @keydown.stop
                                                    />
                                                </div>
                                                <SelectLabel
                                                    >Companies</SelectLabel
                                                >
                                                <SelectItem
                                                    v-for="company in filteredCompanies"
                                                    :key="company.id"
                                                    :value="company.id"
                                                >
                                                    {{ company.company_name }}
                                                </SelectItem>
                                                <p
                                                    v-if="
                                                        filteredCompanies.length ===
                                                        0
                                                    "
                                                    class="px-2 py-1 text-sm text-muted-foreground"
                                                >
                                                    No companies found.
                                                </p>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        :message="form.errors.company_id"
                                    />
                                </div>

                                <div class="space-y-2">
                                    <Label
                                        >Route
                                        <span class="text-red-500"
                                            >*</span
                                        ></Label
                                    >
                                    <Select v-model="form.route_id">
                                        <SelectTrigger class="w-full">
                                            <SelectValue
                                                placeholder="Select route"
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <div class="p-2">
                                                    <Input
                                                        v-model="routeSearch"
                                                        placeholder="Search route..."
                                                        autocomplete="off"
                                                        @keydown.stop
                                                    />
                                                </div>
                                                <SelectLabel
                                                    >Routes</SelectLabel
                                                >
                                                <SelectItem
                                                    v-for="route in filteredRoutes"
                                                    :key="route.id"
                                                    :value="route.id"
                                                >
                                                    {{ route.route_name }}
                                                </SelectItem>
                                                <p
                                                    v-if="
                                                        filteredRoutes.length ===
                                                        0
                                                    "
                                                    class="px-2 py-1 text-sm text-muted-foreground"
                                                >
                                                    No routes found.
                                                </p>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        :message="form.errors.route_id"
                                    />
                                </div>

                                <div class="space-y-2">
                                    <Label
                                        >Vehicle Type
                                        <span class="text-red-500"
                                            >*</span
                                        ></Label
                                    >
                                    <Select v-model="form.vehicle_type_id">
                                        <SelectTrigger class="w-full">
                                            <SelectValue
                                                placeholder="Select vehicle type"
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <div class="p-2">
                                                    <Input
                                                        v-model="
                                                            vehicleTypeSearch
                                                        "
                                                        placeholder="Search vehicle type..."
                                                        autocomplete="off"
                                                        @keydown.stop
                                                    />
                                                </div>
                                                <SelectLabel
                                                    >Vehicle Types</SelectLabel
                                                >
                                                <SelectItem
                                                    v-for="vehicleType in filteredVehicleTypes"
                                                    :key="vehicleType.id"
                                                    :value="vehicleType.id"
                                                >
                                                    {{ vehicleType.type_name }}
                                                </SelectItem>
                                                <p
                                                    v-if="
                                                        filteredVehicleTypes.length ===
                                                        0
                                                    "
                                                    class="px-2 py-1 text-sm text-muted-foreground"
                                                >
                                                    No vehicle types found.
                                                </p>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        :message="form.errors.vehicle_type_id"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4 border-t pt-6">
                            <p class="text-sm font-medium">Vehicle Details</p>

                            <div class="grid gap-4 md:grid-cols-3">
                                <div class="space-y-2">
                                    <Label for="plate_number"
                                        >Plate Number
                                        <span class="text-red-500"
                                            >*</span
                                        ></Label
                                    >
                                    <Input
                                        id="plate_number"
                                        v-model="form.plate_number"
                                        maxlength="6"
                                        placeholder="e.g. ABC123"
                                        class="uppercase"
                                    />
                                    <p class="text-xs text-muted-foreground">
                                        Maximum 6 characters.
                                    </p>
                                    <InputError
                                        :message="form.errors.plate_number"
                                    />
                                </div>

                                <div class="space-y-2">
                                    <Label for="body_number">Body Number</Label>
                                    <Input
                                        id="body_number"
                                        v-model="form.body_number"
                                        placeholder="Enter body number"
                                    />
                                    <InputError
                                        :message="form.errors.body_number"
                                    />
                                </div>

                                <div class="space-y-2">
                                    <Label for="capacity">Capacity</Label>
                                    <Input
                                        id="capacity"
                                        v-model.number="form.capacity"
                                        type="number"
                                        min="1"
                                        placeholder="Enter capacity"
                                    />
                                    <InputError
                                        :message="form.errors.capacity"
                                    />
                                </div>
                            </div>
                        </div>

                        <div
                            class="flex items-center justify-end gap-2 border-t pt-4"
                        >
                            <Button type="button" variant="outline" as-child>
                                <Link :href="index().url">Cancel</Link>
                            </Button>
                            <Button type="submit" :disabled="form.processing">
                                <Save class="mr-2 h-4 w-4" />
                                {{
                                    form.processing
                                        ? 'Saving...'
                                        : 'Update Vehicle'
                                }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
