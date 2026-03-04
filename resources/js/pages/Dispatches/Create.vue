<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { create, index, store } from '@/routes/dispatches';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';

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
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type VehicleOption = {
    id: number;
    plate_number: string;
    body_number: string;
    route_name?: string | null;
    type_name?: string | null;
};

const props = defineProps<{
    company: {
        id: number;
        name: string;
        code: string;
    };
    vehicles: VehicleOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dispatches', href: index().url },
    {
        title: `Create - ${props.company.code}`,
        href: create({ company: props.company.id }).url,
    },
];

const form = useForm({
    vehicle_id: null as number | null,
    pax_count: 0,
    bay_number: '',
    remarks: '',
});

function submit() {
    form.post(store({ company: props.company.id }).url);
}
</script>

<template>
    <Head title="Create Dispatch" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <Card class="mx-5">
                    <CardHeader>
                        <CardTitle>Create Dispatch</CardTitle>
                        <CardDescription>
                            Company:
                            <span class="font-medium">
                                {{ props.company.code }} -
                                {{ props.company.name }}
                            </span>
                        </CardDescription>
                    </CardHeader>

                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Vehicle -->
                            <div class="space-y-2">
                                <Label>Vehicle</Label>

                                <Select
                                    :model-value="
                                        form.vehicle_id?.toString() ?? ''
                                    "
                                    @update:model-value="
                                        (v) =>
                                            (form.vehicle_id = v
                                                ? Number(v)
                                                : null)
                                    "
                                >
                                    <SelectTrigger>
                                        <SelectValue
                                            placeholder="Select a vehicle"
                                        />
                                    </SelectTrigger>

                                    <SelectContent>
                                        <SelectItem
                                            v-for="v in props.vehicles"
                                            :key="v.id"
                                            :value="String(v.id)"
                                        >
                                            {{ v.plate_number }} •
                                            {{ v.body_number }}
                                            <span v-if="v.route_name">
                                                • {{ v.route_name }}</span
                                            >
                                            <span v-if="v.type_name">
                                                • {{ v.type_name }}</span
                                            >
                                        </SelectItem>
                                    </SelectContent>
                                </Select>

                                <p
                                    v-if="form.errors.vehicle_id"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.vehicle_id }}
                                </p>
                            </div>

                            <!-- Pax -->
                            <div class="space-y-2">
                                <Label for="pax_count">Pax Count</Label>
                                <Input
                                    id="pax_count"
                                    type="number"
                                    min="0"
                                    v-model.number="form.pax_count"
                                />
                                <p
                                    v-if="form.errors.pax_count"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.pax_count }}
                                </p>
                            </div>

                            <!-- Bay -->
                            <div class="space-y-2">
                                <Label for="bay_number">Bay Number</Label>
                                <Input
                                    id="bay_number"
                                    v-model="form.bay_number"
                                    placeholder="Bay number"
                                />
                                <p
                                    v-if="form.errors.bay_number"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.bay_number }}
                                </p>
                            </div>

                            <!-- Remarks (Native Textarea) -->
                            <div class="space-y-2">
                                <Label for="remarks">Remarks</Label>
                                <textarea
                                    id="remarks"
                                    v-model="form.remarks"
                                    rows="4"
                                    class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                    placeholder="Remarks"
                                ></textarea>

                                <p
                                    v-if="form.errors.remarks"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.remarks }}
                                </p>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center justify-end gap-2">
                                <Button
                                    variant="outline"
                                    type="button"
                                    as-child
                                >
                                    <Link :href="index().url">Cancel</Link>
                                </Button>

                                <Button
                                    type="submit"
                                    :disabled="
                                        form.processing || !form.vehicle_id
                                    "
                                >
                                    Save Dispatch
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
        </div>
    </AppLayout>
</template>
