<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

import { create, index, store } from '@/routes/roles';
import { type BreadcrumbItem } from '@/types';

/* -------------------------------------------------------------------------- */
/* Props (Inertia Resource)                                                    */
/* -------------------------------------------------------------------------- */
const props = defineProps<{
    permissions: {
        data: {
            id: number;
            name: string;
        }[];
    };
}>();

/* -------------------------------------------------------------------------- */
/* Breadcrumbs                                                                 */
/* -------------------------------------------------------------------------- */
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Roles Table',
        href: index().url,
    },
    {
        title: 'Create Role',
        href: create().url,
    },
];

/* -------------------------------------------------------------------------- */
/* Form                                                                        */
/* -------------------------------------------------------------------------- */
const form = useForm({
    name: '',
    permissions: [] as number[],
});

/* -------------------------------------------------------------------------- */
/* Submit                                                                      */
/* -------------------------------------------------------------------------- */
const submit = () => {
    form.post(store().url, {
        onSuccess: () => {
            toast.success('Role created successfully');
            form.reset();
        },
    });
};

const togglePermission = (checked: boolean | 'indeterminate', id: number) => {
    const isChecked = checked === true;

    form.permissions = isChecked
        ? form.permissions.includes(id)
            ? form.permissions
            : [...form.permissions, id]
        : form.permissions.filter((permissionId) => permissionId !== id);
};
</script>

<template>
    <Head title="Create Role" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 justify-center p-4">
            <Card class="w-full max-w-2xl">
                <CardHeader>
                    <CardTitle>Create Role</CardTitle>
                    <CardDescription>
                        Define a role name and assign permissions
                    </CardDescription>
                </CardHeader>

                <form @submit.prevent="submit">
                    <CardContent class="space-y-6">
                        <!-- Role Name -->
                        <div class="space-y-2">
                            <Label for="name">Role Name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="e.g. Admin"
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <!-- Permissions -->
                        <div class="space-y-3">
                            <Label>Permissions</Label>

                            <div class="grid grid-cols-2 gap-3">
                                <div
                                    v-for="permission in props.permissions.data"
                                    :key="permission.id"
                                    class="flex items-center gap-2"
                                >
                                    <!-- ✅ CORRECT RADIX CHECKBOX -->
                                    <Checkbox
                                        :id="`perm-${permission.id}`"
                                        :model-value="
                                            form.permissions.includes(
                                                permission.id,
                                            )
                                        "
                                        @update:modelValue="
                                            (checked) =>
                                                togglePermission(
                                                    checked,
                                                    permission.id,
                                                )
                                        "
                                    />

                                    <Label
                                        :for="`perm-${permission.id}`"
                                        class="font-normal"
                                    >
                                        {{ permission.name }}
                                    </Label>
                                </div>
                            </div>

                            <InputError :message="form.errors.permissions" />
                        </div>
                    </CardContent>

                    <CardFooter class="flex justify-end gap-2">
                        <Link :href="index().url">
                            <Button
                                type="button"
                                variant="outline"
                                :disabled="form.processing"
                            >
                                Cancel
                            </Button>
                        </Link>

                        <Button type="submit" :disabled="form.processing">
                            Create Role
                        </Button>
                    </CardFooter>
                </form>
            </Card>
        </div>
    </AppLayout>
</template>
