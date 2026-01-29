<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';

import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

import Button from '@/components/ui/button/Button.vue';
import { edit, index, update } from '@/routes/routes';
import { toast } from 'vue-sonner';
import { Save } from 'lucide-vue-next';

/* =====================
   Types
===================== */

interface Gate {
    id: number;
    gate_name: string;
}

interface RouteModel {
    id: number;
    route_name: string;
    gate: Gate | null;
}

/* =====================
   Props
===================== */

const props = defineProps<{
    route: RouteModel;
    gates: Gate[];
}>();

/* =====================
   Breadcrumbs
===================== */

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Routes',
        href: index().url,
    },
    {
        title: 'Edit Route',
        href: edit(props.route.id).url,
    },
];

/* =====================
   Form
===================== */

const form = useForm<{
    route_name: string;
    gate_id: number | null;
}>({
    route_name: props.route.route_name,
    gate_id: props.route.gate?.id ?? null,
});

const submit = () => {
    form.put(update(props.route.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Route updated successfully');
        },
        onError: () => {
            toast.error('Failed to update route');
        },
    });
};
</script>

<template>
    <Head title="Edit Route" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div  class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <Card>
                <CardHeader>
                    <CardTitle>Edit Route</CardTitle>
                    <CardDescription>
                        Update route details and assigned gate.
                    </CardDescription>

                    <CardAction>
                        <Button as-child variant="link" size="sm">
                            <Link :href="index().url">Back to Routes</Link>
                        </Button>
                    </CardAction>
                </CardHeader>

                <CardContent>
                    <form class="space-y-6" @submit.prevent="submit">
                        <!-- Route Name -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium"
                                >Route Name</label
                            >
                            <input
                                v-model="form.route_name"
                                class="w-full rounded-md border px-3 py-2"
                                placeholder="Route name"
                            />
                            <InputError :message="form.errors.route_name" />
                        </div>

                        <!-- Gate Select -->
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Gate</label>

                            <Select
                                :model-value="form.gate_id?.toString()"
                                @update:model-value="
                                    form.gate_id = Number($event)
                                "
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Select a gate" />
                                </SelectTrigger>

                                <SelectContent>
                                    <SelectGroup>
                                        <SelectLabel>Gates</SelectLabel>
                                        <SelectItem
                                            v-for="gate in gates"
                                            :key="gate.id"
                                            :value="gate.id.toString()"
                                        >
                                            {{ gate.gate_name }}
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.gate_id" />
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-end gap-3">
                            <Button type="submit" size="sm" :disabled="form.processing"> <Save />
                                Update Route
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
