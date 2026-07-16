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
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import { edit, index, update } from '@/routes/vehicles';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Bus,
    Building2,
    Loader2,
    Route as RouteIcon,
    Save,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';

type NamedOption = { id: number };

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
    companies: (NamedOption & { company_name: string })[];
    routes: (NamedOption & { route_name: string })[];
    vehicleTypes: (NamedOption & { type_name: string })[];
}>();

const companySearch     = ref('');
const routeSearch       = ref('');
const vehicleTypeSearch = ref('');

const includesText = (value: string, query: string) =>
    value.toLowerCase().includes(query.trim().toLowerCase());

const filteredCompanies    = computed(() => props.companies.filter((c) => includesText(c.company_name, companySearch.value)));
const filteredRoutes       = computed(() => props.routes.filter((r) => includesText(r.route_name, routeSearch.value)));
const filteredVehicleTypes = computed(() => props.vehicleTypes.filter((v) => includesText(v.type_name, vehicleTypeSearch.value)));

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Vehicles', href: index().url },
    { title: 'Edit Vehicle', href: edit({ vehicle: props.vehicle.id }).url },
];

const form = useForm({
    plate_number:    props.vehicle.plate_number ?? '',
    body_number:     props.vehicle.body_number ?? '',
    capacity:        props.vehicle.capacity ?? undefined,
    company_id:      props.vehicle.company_id,
    route_id:        props.vehicle.route_id,
    vehicle_type_id: props.vehicle.vehicle_type_id,
});

const submit = () => {
    form.put(update({ vehicle: props.vehicle.id }).url, {
        preserveScroll: true,
        onSuccess: () => { toast.success('Vehicle updated successfully!'); },
    });
};
</script>

<template>
    <Head title="Edit Vehicle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 md:p-6">

            
            <div class="mx-5 flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                        <Bus class="h-3.5 w-3.5" />
                        Vehicles · Edit
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight">Edit Vehicle</h1>
                    <p class="text-sm text-muted-foreground">
                        Update the details for this vehicle.
                    </p>
                </div>

                <Button
                    as-child
                    size="sm"
                    variant="outline"
                    class="shrink-0 rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100"
                >
                    <Link :href="index().url">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back to Vehicles
                    </Link>
                </Button>
            </div>

            
            <Card class="mx-5">
                <form @submit.prevent="submit">

                    
                    <CardHeader class="border-b border-slate-100 pb-4">
                        <CardTitle class="flex items-center gap-2 text-base">
                            <Building2 class="h-4 w-4 text-blue-700" />
                            Assignment
                        </CardTitle>
                        <CardDescription>
                            Link this vehicle to a company, route, and type.
                        </CardDescription>
                    </CardHeader>

                    <CardContent class="space-y-5 pt-5">
                        <div class="grid gap-5 md:grid-cols-3">

                            
                            <div class="space-y-1.5">
                                <Label class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                                    Company <span class="text-rose-500">*</span>
                                </Label>
                                <Select v-model="form.company_id">
                                    <SelectTrigger class="w-full rounded-lg border-slate-200 focus:ring-blue-500">
                                        <SelectValue placeholder="Select a company" />
                                    </SelectTrigger>
                                    <SelectContent class="rounded-xl">
                                        <SelectGroup>
                                            <div class="p-2">
                                                <Input
                                                    v-model="companySearch"
                                                    placeholder="Search company..."
                                                    autocomplete="off"
                                                    class="rounded-lg border-slate-200 text-sm"
                                                    @keydown.stop
                                                />
                                            </div>
                                            <SelectLabel class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                                                Companies
                                            </SelectLabel>
                                            <SelectItem
                                                v-for="company in filteredCompanies"
                                                :key="company.id"
                                                :value="company.id"
                                                class="rounded-lg"
                                            >
                                                {{ company.company_name }}
                                            </SelectItem>
                                            <p v-if="filteredCompanies.length === 0" class="px-2 py-2 text-xs text-muted-foreground">
                                                No companies found.
                                            </p>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.company_id" />
                            </div>

                            
                            <div class="space-y-1.5">
                                <Label class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                                    Route <span class="text-rose-500">*</span>
                                </Label>
                                <Select v-model="form.route_id">
                                    <SelectTrigger class="w-full rounded-lg border-slate-200 focus:ring-blue-500">
                                        <SelectValue placeholder="Select route" />
                                    </SelectTrigger>
                                    <SelectContent class="rounded-xl">
                                        <SelectGroup>
                                            <div class="p-2">
                                                <Input
                                                    v-model="routeSearch"
                                                    placeholder="Search route..."
                                                    autocomplete="off"
                                                    class="rounded-lg border-slate-200 text-sm"
                                                    @keydown.stop
                                                />
                                            </div>
                                            <SelectLabel class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                                                Routes
                                            </SelectLabel>
                                            <SelectItem
                                                v-for="route in filteredRoutes"
                                                :key="route.id"
                                                :value="route.id"
                                                class="rounded-lg"
                                            >
                                                {{ route.route_name }}
                                            </SelectItem>
                                            <p v-if="filteredRoutes.length === 0" class="px-2 py-2 text-xs text-muted-foreground">
                                                No routes found.
                                            </p>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.route_id" />
                            </div>

                            
                            <div class="space-y-1.5">
                                <Label class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                                    Vehicle Type <span class="text-rose-500">*</span>
                                </Label>
                                <Select v-model="form.vehicle_type_id">
                                    <SelectTrigger class="w-full rounded-lg border-slate-200 focus:ring-blue-500">
                                        <SelectValue placeholder="Select vehicle type" />
                                    </SelectTrigger>
                                    <SelectContent class="rounded-xl">
                                        <SelectGroup>
                                            <div class="p-2">
                                                <Input
                                                    v-model="vehicleTypeSearch"
                                                    placeholder="Search vehicle type..."
                                                    autocomplete="off"
                                                    class="rounded-lg border-slate-200 text-sm"
                                                    @keydown.stop
                                                />
                                            </div>
                                            <SelectLabel class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                                                Vehicle Types
                                            </SelectLabel>
                                            <SelectItem
                                                v-for="vehicleType in filteredVehicleTypes"
                                                :key="vehicleType.id"
                                                :value="vehicleType.id"
                                                class="rounded-lg"
                                            >
                                                {{ vehicleType.type_name }}
                                            </SelectItem>
                                            <p v-if="filteredVehicleTypes.length === 0" class="px-2 py-2 text-xs text-muted-foreground">
                                                No vehicle types found.
                                            </p>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.vehicle_type_id" />
                            </div>

                        </div>

                        <Separator />

                        
                        <div class="space-y-1.5">
                            <div class="flex items-center gap-2">
                                <Bus class="h-4 w-4 text-blue-700" />
                                <p class="text-sm font-semibold text-foreground">Vehicle Details</p>
                            </div>
                            <p class="text-xs text-muted-foreground">Registration and identification details.</p>
                        </div>

                        <div class="grid gap-5 md:grid-cols-3">

                            
                            <div class="space-y-1.5">
                                <Label for="plate_number" class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                                    Plate Number <span class="text-rose-500">*</span>
                                </Label>
                                <Input
                                    id="plate_number"
                                    v-model="form.plate_number"
                                    maxlength="6"
                                    placeholder="e.g. ABC123"
                                    class="rounded-lg border-slate-200 uppercase focus-visible:ring-blue-500"
                                />
                                <p class="text-xs text-muted-foreground">Maximum 6 characters.</p>
                                <InputError :message="form.errors.plate_number" />
                            </div>

                            
                            <div class="space-y-1.5">
                                <Label for="body_number" class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                                    Body Number
                                </Label>
                                <Input
                                    id="body_number"
                                    v-model="form.body_number"
                                    placeholder="Enter body number"
                                    class="rounded-lg border-slate-200 focus-visible:ring-blue-500"
                                />
                                <InputError :message="form.errors.body_number" />
                            </div>

                            
                            <div class="space-y-1.5">
                                <Label for="capacity" class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                                    Capacity
                                </Label>
                                <Input
                                    id="capacity"
                                    v-model.number="form.capacity"
                                    type="number"
                                    min="1"
                                    placeholder="Enter capacity"
                                    class="rounded-lg border-slate-200 focus-visible:ring-blue-500"
                                />
                                <InputError :message="form.errors.capacity" />
                            </div>

                        </div>

                        <Separator />

                        
                        <div class="flex items-center justify-end gap-3">
                            <Button
                                type="button"
                                variant="outline"
                                as-child
                                class="rounded-lg border-slate-200 text-slate-600 hover:bg-slate-100"
                            >
                                <Link :href="index().url">Cancel</Link>
                            </Button>

                            <Button
                                type="submit"
                                :disabled="form.processing"
                                class="rounded-lg bg-blue-700 text-white hover:bg-blue-800 border-0 shadow-sm font-semibold disabled:opacity-60"
                            >
                                <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                                <Save v-else class="mr-2 h-4 w-4" />
                                {{ form.processing ? 'Saving...' : 'Update Vehicle' }}
                            </Button>
                        </div>

                    </CardContent>
                </form>
            </Card>
        </div>
    </AppLayout>
</template>