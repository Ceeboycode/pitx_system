<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import Button from '@/components/ui/button/Button.vue';
import {
    Card,
    CardAction,
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
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
defineProps<{
    gates: {
        id: number;
        gate_name: string;
    }[];
}>();

import { create, index, store } from '@/routes/routes';
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Routes',
        href: index().url,
    },
    {
        title: 'Create Route',
        href: create().url,
    },
];
const form = useForm({
    route_name: '',
    gate_id: null,
});

const submit = () => {
    form.post(store().url, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
        onError: () => {
            toast.error('Failed to create route');
        },
    });
};
</script>

<template>
    <Head title="Create Route" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <Card class="w-full">
                <CardHeader>
                    <CardTitle>Create Route</CardTitle>
                    <CardDescription>
                        Fill in the details to create a new route.
                    </CardDescription>
                    <CardAction>
                        <Button as-child variant="link" size="sm">
                            <Link :href="index().url">Back to Routes</Link>
                        </Button>
                    </CardAction>
                </CardHeader>
                <CardContent>
                    <form class="space-y-6" @submit.prevent="submit">
                        <!-- Gate Select -->
                        <div class="space-y-2">
                            <Label>Gate</Label>

                            <Select v-model="form.gate_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select a gate" />
                                </SelectTrigger>

                                <SelectContent>
                                    <SelectGroup>
                                        <SelectLabel>Gates</SelectLabel>

                                        <SelectItem
                                            v-for="gate in gates"
                                            :key="gate.id"
                                            :value="gate.id"
                                        >
                                            {{ gate.gate_name }}
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>

                            <InputError :message="form.errors.gate_id" />
                        </div>

                        <!-- Route Name -->
                        <div class="space-y-2">
                            <Label>Route Name</Label>
                            <Input
                                v-model="form.route_name"
                                placeholder="Route name"
                            />
                            <InputError :message="form.errors.route_name" />
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-end gap-3">
                            <Button
                                type="submit"
                                size="sm"
                                :disabled="form.processing"
                            >
                                Save Route
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
